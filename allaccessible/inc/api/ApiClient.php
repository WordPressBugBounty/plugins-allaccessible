<?php
/**
 * AllAccessible API Client
 *
 * @package AllAccessible
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AllAccessible_ApiClient {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * API base URLs
     */
    const API_BASE_URL = 'https://api.allaccessible.org'; 

    /**
     * Canonical namespace for every plugin-facing endpoint.
     */
    const PLUGIN_API_PREFIX = '/integrations/v1';

    /**
     * Cache expiration times
     */
    const CACHE_AUDIT_SCORES = 'aacb_cache_audit_scores';
    const CACHE_SITE_STATUS = 'aacb_cache_site_status';
    const CACHE_DURATION = 1800; // 30 minutes
    // Negative-cache sentinel: set after a failed /validate so concurrent
    // consumers fail fast instead of each paying the full timeout+retry.
    const ERROR_SENTINEL_TRANSIENT = 'aacb_site_options_error';
    const ERROR_SENTINEL_TTL = 90;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Private constructor for singleton
    }

    /**
     * Clear all API caches
     */
    public function clear_cache() {
        delete_transient(self::CACHE_AUDIT_SCORES);
        delete_transient(self::CACHE_SITE_STATUS);
        delete_transient(self::ERROR_SENTINEL_TRANSIENT);
    }

    /**
     * Get site validation data from API.
     *
     * @param bool $force_refresh Force refresh from API (skip cache)
     * @return object|WP_Error Site validation data or error
     */
    public function get_site_options($force_refresh = false) {
        // Per-request memo — multiple consumers (TierGate, Sentry, columns,
        // settings) call this within a single request; only the first may
        // touch the network.
        static $memo = null;
        if (!$force_refresh && $memo !== null) {
            return $memo;
        }

        // Check cache first unless force refresh
        if (!$force_refresh) {
            $cached = get_transient('aacb_site_options_cache');
            if ($cached !== false) {
                $memo = $cached;
                return $cached;
            }
            // Negative cache: a recent failure means the API is unreachable —
            // fail fast rather than re-running the timeout+retry stack for
            // every caller on every admin render while it's down.
            if (get_transient(self::ERROR_SENTINEL_TRANSIENT)) {
                $memo = new WP_Error(
                    'api_unreachable',
                    __('AllAccessible API temporarily unreachable', 'allaccessible')
                );
                return $memo;
            }
        }

        $account_id = get_option('aacb_accountID');

        if (!$account_id) {
            // Not memoized: the wizard saves the account ID mid-request and
            // expects the next call to fetch fresh.
            return new WP_Error(
                'no_account_id',
                __('Account ID not found', 'allaccessible')
            );
        }

        // Call the validation endpoint with comprehensive data
        $response = $this->remote_with_retry('https://api.allaccessible.org/validate', array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode(array(
                'accountID' => $account_id,
                'is_shopify' => null,
                'browser' => isset($_SERVER['HTTP_USER_AGENT']) ? 'WordPress' : 'Unknown',
                'device' => 'Server',
                'pageUrl' => get_bloginfo('url'),
                'auditUrl' => get_bloginfo('url'),
            )),
            'timeout' => 15,
        ));

        if (is_wp_error($response)) {
            set_transient(self::ERROR_SENTINEL_TRANSIENT, 1, self::ERROR_SENTINEL_TTL);
            $memo = $response;
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if ($data) {
            $subdomain_id = 0;
            if (isset($data->subID) && is_numeric($data->subID)) {
                $subdomain_id = (int) $data->subID;
            } elseif (isset($data->subdomainID) && is_numeric($data->subdomainID)) {
                $subdomain_id = (int) $data->subdomainID;
            }
            if ($subdomain_id > 0) {
                update_option('aacb_siteID', $subdomain_id);
                update_option('aacb_siteID_version', AACB_VERSION);
            }

            set_transient('aacb_site_options_cache', $data, self::CACHE_DURATION);
            delete_transient(self::ERROR_SENTINEL_TRANSIENT);
            $memo = $data;
            return $data;
        }

        set_transient(self::ERROR_SENTINEL_TRANSIENT, 1, self::ERROR_SENTINEL_TTL);
        $memo = new WP_Error('invalid_response', __('Invalid API response', 'allaccessible'));
        return $memo;
    }

    /**
     * Get current subscription tier from API
     *
     * @return string Subscription tier: 'free', 'trial', 'starter', 'legacy', 'enterprise'
     */
    public function get_subscription_tier() {
        $site_options = $this->get_site_options();

        if (is_wp_error($site_options)) {
            return 'free';
        }

        if (isset($site_options->_meta->pricingTier)) {
            return $site_options->_meta->pricingTier;
        }

        if (isset($site_options->tier)) {
            return $site_options->tier;
        }

        // Free accounts legitimately come back with no pricingTier/tier field,
        // so a missing tier is the EXPECTED free-tier path, not an anomaly.
        // Log locally for debugging but do NOT report — warn() would send this
        // to error reporting and flood it with normal free-tier traffic.
        AllAccessible_Debug::info('ApiClient::get_subscription_tier', 'No tier in response — defaulting to free');
        return 'free';
    }

    /**
     * Check if account is paid
     *
     * @return bool True if paid subscription (starter, enterprise, legacy, or trial)
     */
    public function is_paid_account() {
        $site_options = $this->get_site_options();

        if (is_wp_error($site_options)) {
            return false;
        }

        if (isset($site_options->paid)) {
            return (bool) $site_options->paid;
        }

        $tier = $this->get_subscription_tier();
        return in_array($tier, array('starter', 'enterprise', 'legacy', 'trial'));
    }

    /**
     * Check if account has exceeded limits
     *
     * @return array Array of exceeded limit names, or empty array
     */
    public function get_exceeded_limits() {
        $site_options = $this->get_site_options();

        if (is_wp_error($site_options)) {
            return array();
        }

        if (isset($site_options->exceededLimits) && is_array($site_options->exceededLimits)) {
            return $site_options->exceededLimits;
        }

        return array();
    }

    /**
     * Get usage summary for display
     *
     * @return array|null Usage summary or null if not available
     */
    public function get_usage_summary() {
        $site_options = $this->get_site_options();

        if (is_wp_error($site_options)) {
            return null;
        }

        if (isset($site_options->usageSummary)) {
            return $site_options->usageSummary;
        }

        return null;
    }

    /**
     * Get site ID from API
     *
     * @return string|null Site ID or null if not available
     */
    public function get_site_id() {
        $site_options = $this->get_site_options();

        if (is_wp_error($site_options)) {
            // Fallback to WordPress stored siteID
            return get_option('aacb_siteID');
        }

        if (isset($site_options->siteID)) {
            return $site_options->siteID;
        }

        return get_option('aacb_siteID');
    }

    /**
     * Get subscription ID from API
     *
     * @return int|null Subscription ID or null if not available
     */
    public function get_subscription_id() {
        $site_options = $this->get_site_options();

        if (is_wp_error($site_options)) {
            return null;
        }

        if (isset($site_options->subID)) {
            return $site_options->subID;
        }

        return null;
    }

    /**
     * Get billing URL
     *
     * @return string Billing portal URL
     */
    public function get_billing_url() {
        return 'https://app.allaccessible.org/billing';
    }

    /**
     * Get addon URL for this site
     *
     * @return string Addon management URL
     */
    public function get_addon_url() {
        $site_id = $this->get_site_id();

        if ($site_id) {
            return 'https://app.allaccessible.org/site/' . $site_id . '/addons';
        }

        // Fallback to billing
        return $this->get_billing_url();
    }

    /**
     * Legacy V1 → current-tier migration landing page.
     */
    public function get_migration_url() {
        $site_id = $this->get_site_id();
        if ($site_id) {
            return 'https://app.allaccessible.org/site/' . rawurlencode((string) $site_id) . '/migration/offer';
        }
        return $this->get_billing_url();
    }

    /**
     * Get accessibility audits URL
     *
     * @return string Audits URL
     */
    public function get_audits_url() {
        $site_id = $this->get_site_id();
        $sub_id = $this->get_subscription_id();

        if ($site_id && $sub_id) {
            return 'https://app.allaccessible.org/site/' . $site_id . '/' . $sub_id . '/accessibility-audits';
        }

        // Fallback to site overview
        if ($site_id) {
            return 'https://app.allaccessible.org/site/' . $site_id;
        }

        return 'https://app.allaccessible.org';
    }

    /**
     * Get advanced widget settings URL
     *
     * @return string Widget settings URL
     */
    public function get_widget_settings_url() {
        $site_id = $this->get_site_id();
        $sub_id = $this->get_subscription_id();

        if ($site_id && $sub_id) {
            return 'https://app.allaccessible.org/site/' . $site_id . '/' . $sub_id . '/widget-settings';
        }

        // Fallback to site overview
        if ($site_id) {
            return 'https://app.allaccessible.org/site/' . $site_id;
        }

        return 'https://app.allaccessible.org';
    }

    /* ==================================================================
     * Agentic Fixes API
     * ================================================================== */

    const APP_BASE_URL              = 'https://app.allaccessible.org';
    const PLATFORM_HEADER           = 'wordpress';
    const CACHE_MANIFEST_SUMMARY    = 'aacb_cache_manifest_summary_v2';
    const PLUGIN_SECRET_OPTION      = 'aacb_plugin_secret';
    const PLUGIN_SECRET_CANON_OPT   = 'aacb_plugin_secret_canon';

    /**
     * Latest aggregated audit for this site.
     *
     * @param bool $force_refresh Skip transient cache when true.
     * @return array|WP_Error
     */
    public function get_audit_aggregation($force_refresh = false) {
        $cache_key = 'aacb_cache_audit_aggregation_v1';
        if (!$force_refresh) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }
        $result = $this->signed_app_request('GET', self::PLUGIN_API_PREFIX . '/scans/audit/aggregation', null);
        if (!is_wp_error($result)) {
            set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);
        }
        return $result;
    }

    /**
     * Trigger a single-page audit scan immediately.
     *
     * @param string $page_url Absolute URL to scan. Must live on this site.
     * @param string $source   One of: wp_admin_bar (default), wp_metabox,
     *                         app_manual, api_external. Backend will reject
     *                         unknown values silently → wp_admin_bar.
     * @return array|WP_Error
     */
    public function trigger_page_scan($page_url, $source = 'wp_admin_bar') {
        if (empty($page_url) || !is_string($page_url)) {
            return new WP_Error(
                'invalid_page_url',
                __('A page URL is required to trigger a scan.', 'allaccessible')
            );
        }
        $body = array(
            'pageUrl' => $page_url,
            'source'  => $source,
        );
        $result = $this->signed_app_request(
            'POST',
            self::PLUGIN_API_PREFIX . '/scans/start',
            $body
        );

        if (!is_wp_error($result)) {
            $this->bust_page_caches($page_url);
        }

        return $result;
    }

    /**
     * Invalidate every plugin transient that could surface a stale score
     * for the given URL.
     */
    public function bust_page_caches($page_url) {
        // Per-URL transients (warmed by get_page_audit).
        $url_hash = md5((string) $page_url);
        delete_transient('aacb_page_score_' . $url_hash);
        delete_transient('aacb_page_audit_meta_' . $url_hash);

        $reverse_id = url_to_postid((string) $page_url);
        $key_parts_zero = array(0, $url_hash);
        delete_transient('aacb_cache_page_audit_v1_' . md5(implode('|', $key_parts_zero)));
        if ($reverse_id > 0) {
            $key_parts_post = array((int) $reverse_id, $url_hash);
            delete_transient('aacb_cache_page_audit_v1_' . md5(implode('|', $key_parts_post)));
        }

        // Site-level surfaces that include this page in summaries.
        delete_transient('aacb_cache_audit_aggregation_v1');

        global $wpdb;
        if (isset($wpdb)) {
            $wpdb->query(
                "DELETE FROM {$wpdb->options}
                 WHERE option_name LIKE '_transient_aacb_cache_pages_audit_bulk_v2_%'
                    OR option_name LIKE '_transient_timeout_aacb_cache_pages_audit_bulk_v2_%'"
            );
        }
    }

    /**
     * Task stats counts
     *
     * @param bool $force_refresh
     * @return array|WP_Error  ['ai_resolved_count', 'manual_required_count',
     *                          'completed_count', 'ignored_count',
     *                          'total_actionable', 'has_audit_data', 'tier', ...]
     */
    public function get_task_stats($force_refresh = false) {
        $cache_key = 'aacb_cache_task_stats_v2';
        if (!$force_refresh) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }
        $result = $this->signed_app_request('GET', self::PLUGIN_API_PREFIX . '/tasks/stats', null);
        if (!is_wp_error($result)) {
            set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);
        }
        return $result;
    }

    /**
     * Full rule-by-rule list
     *
     * @param bool $force_refresh
     * @return array|WP_Error  ['rules' => [...], 'has_audit_data' => bool, 'tier']
     */
    /**
     * Per-page audit lookup.
     *
     * @param int|null    $post_id  WP post ID (preferred — indexed lookup)
     * @param string|null $page_url Permalink fallback (used when post_id
     *                              hasn't been linked server-side yet).
     * @return array|WP_Error  ['overall_score', 'issues', 'last_scan',
     *                          'audit_status', 'data_source', ...]
     */
    public function get_page_audit($post_id = null, $page_url = null) {
        $key_parts = array(
            $post_id ? (int) $post_id : 0,
            $page_url ? md5((string) $page_url) : '',
        );
        $cache_key = 'aacb_cache_page_audit_v1_' . md5(implode('|', $key_parts));
        $cached = get_transient($cache_key);
        if ($cached !== false) return $cached;

        $args = array();
        if ($post_id) $args['post_id'] = (int) $post_id;
        if ($page_url) $args['page_url'] = $page_url;
        if (empty($args)) {
            return new WP_Error('missing_arg', __('post_id or page_url required', 'allaccessible'));
        }

        $path = self::PLUGIN_API_PREFIX . '/audits/by-page?' . http_build_query($args);
        $result = $this->signed_app_request('GET', $path, null);
        if (!is_wp_error($result)) {
            set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);

            if ($page_url && is_array($result)) {
                $score = null;
                if (isset($result['overall_score']) && is_numeric($result['overall_score'])) {
                    $score = (int) $result['overall_score'];
                } elseif (isset($result['score_breakdown']['current']) && is_numeric($result['score_breakdown']['current'])) {
                    $score = (int) $result['score_breakdown']['current'];
                }
                if ($score !== null && class_exists('AllAccessible_AdminBar')) {
                    set_transient(
                        AllAccessible_AdminBar::PAGE_SCORE_TRANSIENT_PREFIX . md5($page_url),
                        $score,
                        5 * MINUTE_IN_SECONDS
                    );
                    if (!empty($result['audit_id']) && !empty($result['subdomain_id'])) {
                        set_transient(
                            AllAccessible_AdminBar::PAGE_AUDIT_META_TRANSIENT_PREFIX . md5($page_url),
                            array(
                                'audit_id'     => (int) $result['audit_id'],
                                'subdomain_id' => (int) $result['subdomain_id'],
                            ),
                            5 * MINUTE_IN_SECONDS
                        );
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Bulk per-page lookup for the WP All Posts / All Pages column.
     *
     * @param bool $force_refresh
     * @return array|WP_Error  ['pages' => [{post_id, page_url, audit_status, ...}, ...]]
     */
    public function get_pages_audit_bulk($force_refresh = false, array $post_ids = array(), array $include = array(), array $page_urls = array()) {
        $key_suffix = empty($post_ids) ? 'all' : md5(implode(',', array_map('intval', $post_ids)));
        $inc_suffix = empty($include) ? '' : '_inc_' . md5(implode(',', $include));
        $url_suffix = empty($page_urls) ? '' : '_url_' . md5(implode('|', $page_urls));
        $cache_key  = 'aacb_cache_pages_audit_bulk_v2_' . $key_suffix . $inc_suffix . $url_suffix;
        if (!$force_refresh) {
            $cached = get_transient($cache_key);
            if ($cached !== false) return $cached;
        }
        // post_ids + page_urls — backend matches by either post id or
        // canonical URL, covering rows not yet linked to a WP post id.
        // Without either, falls back to the "all pages up to limit" path.
        $args = array();
        if (!empty($post_ids)) {
            $args['post_ids'] = implode(',', array_map('intval', $post_ids));
        } else {
            $args['platform'] = '';
        }
        if (!empty($page_urls)) {
            // Pipe delimiter — commas appear in some URLs. Backend explode('|').
            $args['page_urls'] = implode('|', $page_urls);
        }
        // include= opt-in for Dashboard consolidation. Backend returns
        // additional data blocks alongside the standard pages array —
        // collapses several round trips into one.
        if (!empty($include)) {
            $args['include'] = implode(',', $include);
        }
        $path = self::PLUGIN_API_PREFIX . '/audits/by-subdomain?' . http_build_query($args);
        $result = $this->signed_app_request('GET', $path, null);
        if (!is_wp_error($result)) {
            // Shorter TTL when scoped to specific IDs — admin is
            // actively viewing those pages, fresh scores matter more.
            $ttl = empty($post_ids) ? 10 * MINUTE_IN_SECONDS : 2 * MINUTE_IN_SECONDS;
            set_transient($cache_key, $result, $ttl);
        }
        return $result;
    }

    /**
     * Background-link a WP post ID to its scanned page row.
     *
     * Fired fire-and-forget from EditorMetaBox on load. Backend links the post
     * id to the matching scanned page by canonical URL when not already linked.
     * Idempotent. Failure is silent — the URL fallback path keeps working.
     *
     * @param int    $post_id
     * @param string $page_url Permalink of the post
     * @return array|WP_Error
     */
    public function link_post_to_page($post_id, $page_url) {
        if (!$post_id || !$page_url) {
            return new WP_Error('missing_arg', __('post_id and page_url required', 'allaccessible'));
        }
        return $this->signed_app_request(
            'POST',
            self::PLUGIN_API_PREFIX . '/audits/link-post',
            array('post_id' => (int) $post_id, 'page_url' => (string) $page_url),
            false,
            array('blocking' => false)
        );
    }

    /**
     * Bulk variant of link_post_to_page — used by the activation /
     * daily-cron backfill that walks all published WP posts and pushes
     * their post_id ↔ permalink pairs to the backend in one shot per
     * batch. Server cap is 100 pairs per call; caller is expected to
     * chunk larger sets.
     *
     * @param array $pairs  [{post_id: int, page_url: string}, ...]
     * @return array|WP_Error  ['linked', 'skipped', 'missing']
     */
    public function link_posts_batch(array $pairs) {
        if (empty($pairs)) {
            return new WP_Error('missing_arg', __('pairs required', 'allaccessible'));
        }
        if (count($pairs) > 100) {
            return new WP_Error('batch_too_large', __('batch limited to 100 pairs', 'allaccessible'));
        }
        return $this->signed_app_request(
            'POST',
            self::PLUGIN_API_PREFIX . '/audits/link-post-batch',
            array('posts' => array_values($pairs))
        );
    }

    public function get_task_categorization($force_refresh = false) {
        // v2 cache key — see get_task_stats note. Field names are normalized
        // server-side now, so any pre-deploy transient with the old field
        // keys is stale.
        $cache_key = 'aacb_cache_task_categorization_v2';
        if (!$force_refresh) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }
        $result = $this->signed_app_request('GET', self::PLUGIN_API_PREFIX . '/tasks/categorization', null);
        if (!is_wp_error($result)) {
            set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);
        }
        return $result;
    }

    /**
     * Site-wide manifest aggregate. Hits the AllAccessible API. Cached as
     * a transient for 30 min so the dashboard widget can render without
     * hammering the API.
     *
     * @param bool $force_refresh Skip transient cache when true.
     * @return array|WP_Error
     */
    public function get_manifest_summary($force_refresh = false, $status = null) {
        $valid_status = in_array($status, array('draft', 'approved', 'reverted'), true) ? $status : null;
        $this->_summary_status_filter = $valid_status;
        // Cache key includes status so each tab keeps a separate transient.
        $cache_key = self::CACHE_MANIFEST_SUMMARY . ($valid_status ? ('_' . $valid_status) : '');
        if (!$force_refresh) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }

        $account_id = get_option('aacb_accountID');
        if (!$account_id) {
            return new WP_Error('no_account_id', __('Account not configured', 'allaccessible'));
        }

        // Server requires siteId — without it, an account that owns multiple
        // WP installs would see aggregate data across all of them. Always
        // resolve to the site that matches THIS WordPress install before
        // scoping the request.
        $site_id = $this->resolve_site_id();
        if (is_wp_error($site_id)) return $site_id;

        $args = array(
            'accountID' => $account_id,
            'siteId'    => $site_id,
            'locale'    => $this->wp_locale(),
        );
        // Optional status filter — UI passes draft|approved|reverted to drive
        // status tabs. Server defaults to 'draft' if unset.
        $status = isset($this->_summary_status_filter) ? $this->_summary_status_filter : null;
        if ($status) {
            $args['status'] = $status;
        }

        $url = self::API_BASE_URL . self::PLUGIN_API_PREFIX . '/manifest/summary?' . http_build_query($args);
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept'     => 'application/json',
                'User-Agent' => 'AllAccessible-WordPress/' . AACB_VERSION,
            ),
        ));

        $decoded = $this->decode_json_response($response);
        if (is_wp_error($decoded)) {
            return $decoded;
        }

        set_transient($cache_key, $decoded, self::CACHE_DURATION);
        return $decoded;
    }

    /** Per-call status filter — set by get_manifest_summary, read by URL builder. */
    private $_summary_status_filter = null;

    /**
     * Paginated alt-text grid for the Image Description Manager (KAN-19).
     * Read endpoint — simple query-string auth. Server validates account/site
     * ownership before returning rows.
     * See docs/plugin-architecture-internals.md (internal) for the response shape.
     *
     * @param array $args {
     *   @type int    $page    1-based page number (default 1)
     *   @type int    $perPage Items per page, max 100 (default 24)
     *   @type string $filter  'all' | 'missing' | 'ai' | 'manual'
     * }
     * @return array|WP_Error
     */
    public function get_plugin_images($args = array()) {
        $account_id = get_option('aacb_accountID');
        if (!$account_id) {
            return new WP_Error('no_account_id', __('Account not configured', 'allaccessible'));
        }
        $site_id = $this->resolve_site_id();
        if (is_wp_error($site_id)) return $site_id;

        $query = array(
            'accountID' => $account_id,
            'siteId'    => $site_id,
            'page'      => isset($args['page'])    ? max(1, (int) $args['page'])    : 1,
            'perPage'   => isset($args['perPage']) ? min(100, max(1, (int) $args['perPage'])) : 24,
            'filter'    => isset($args['filter']) && in_array($args['filter'], array('all','missing','ai','manual'), true)
                            ? $args['filter']
                            : 'all',
        );

        // Short transient (60s) — Image Manager page re-renders on every
        // pagination/filter click. Without this, every render hit Api.
        // Keyed on the query shape so different filters/pages don't share.
        //
        // Version-stamp pattern: the cache key embeds a stamp from the
        // `aacb_image_grid_cache_stamp` option. flush_image_grid_cache()
        // bumps the stamp, which changes EVERY future cache key — so the
        // next read misses regardless of whether the underlying object
        // cache was actually purged. A wpdb DELETE alone wasn't enough — it
        // only kills DB-backed transients, while a persistent object cache
        // layer kept serving stale rows.
        $stamp = (int) get_option('aacb_image_grid_cache_stamp', 1);
        $cache_key = 'aacb_cache_plugin_images_v1_' . $stamp . '_' . md5(serialize($query));
        $cached = get_transient($cache_key);
        if ($cached !== false) return $cached;

        $url = self::API_BASE_URL . self::PLUGIN_API_PREFIX . '/images?' . http_build_query($query);

        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept'     => 'application/json',
                'User-Agent' => 'AllAccessible-WordPress/' . AACB_VERSION,
            ),
        ));
        $decoded = $this->decode_json_response($response);
        if (!is_wp_error($decoded)) {
            set_transient($cache_key, $decoded, MINUTE_IN_SECONDS);
        }
        return $decoded;
    }

    /**
     * Full detail for a single manifest. Not cached — always fresh.
     */
    public function get_manifest_detail($manifest_id) {
        $manifest_id = (int) $manifest_id;
        if ($manifest_id <= 0) {
            return new WP_Error('bad_manifest_id', __('Invalid manifest ID', 'allaccessible'));
        }
        return $this->signed_app_request(
            'GET',
            self::PLUGIN_API_PREFIX . '/manifest/' . $manifest_id,
            null
        );
    }

    /**
     * Approve + activate a manifest. Free tier returns 403 from server.
     */
    public function approve_manifest($manifest_id) {
        $manifest_id = (int) $manifest_id;
        if ($manifest_id <= 0) {
            return new WP_Error('bad_manifest_id', __('Invalid manifest ID', 'allaccessible'));
        }
        $result = $this->signed_app_request(
            'POST',
            self::PLUGIN_API_PREFIX . '/manifest/' . $manifest_id . '/approve',
            new stdClass() // empty JSON object; server accepts no body fields here
        );
        if (!is_wp_error($result)) {
            $this->clear_cache();
            delete_transient(self::CACHE_MANIFEST_SUMMARY);
        }
        return $result;
    }

    /**
     * Revert a manifest with a customer-supplied reason.
     */
    public function revert_manifest($manifest_id, $reason) {
        $manifest_id = (int) $manifest_id;
        if ($manifest_id <= 0) {
            return new WP_Error('bad_manifest_id', __('Invalid manifest ID', 'allaccessible'));
        }
        $reason = is_string($reason) ? sanitize_text_field($reason) : '';
        if (strlen($reason) > 500) {
            $reason = substr($reason, 0, 500);
        }
        $result = $this->signed_app_request(
            'POST',
            self::PLUGIN_API_PREFIX . '/manifest/' . $manifest_id . '/revert',
            array('reason' => $reason)
        );
        if (!is_wp_error($result)) {
            $this->clear_cache();
            delete_transient(self::CACHE_MANIFEST_SUMMARY);
        }
        return $result;
    }

    /**
     * Edit a single fix payload (alt text wording, button label, etc.).
     * Only draft manifests are editable per server rules.
     */
    public function edit_fix($manifest_id, $fix_index, $value) {
        $manifest_id = (int) $manifest_id;
        $fix_index   = (int) $fix_index;
        if ($manifest_id <= 0 || $fix_index < 0) {
            return new WP_Error('bad_params', __('Invalid manifest or fix index', 'allaccessible'));
        }
        $value = is_string($value) ? wp_kses_post($value) : '';
        if ($value === '') {
            return new WP_Error('empty_value', __('Value is required', 'allaccessible'));
        }
        if (mb_strlen($value) > 500) {
            return new WP_Error('value_too_long', __('Value too long (max 500 characters)', 'allaccessible'));
        }
        $result = $this->signed_app_request(
            'PATCH',
            self::PLUGIN_API_PREFIX . '/manifest/' . $manifest_id . '/fix/' . $fix_index,
            array('value' => $value)
        );
        if (!is_wp_error($result)) {
            $this->clear_cache();
            delete_transient(self::CACHE_MANIFEST_SUMMARY);
        }
        return $result;
    }

    /**
     * Bulk approve up to 100 manifests in one call.
     */
    public function bulk_approve_manifests($site_id, array $manifest_ids) {
        $site_id = (int) $site_id;
        if ($site_id <= 0) {
            return new WP_Error('bad_site_id', __('Invalid site ID', 'allaccessible'));
        }
        $ids = array_values(array_filter(array_map('intval', $manifest_ids), function($v) { return $v > 0; }));
        if (empty($ids)) {
            return new WP_Error('no_ids', __('No manifest IDs supplied', 'allaccessible'));
        }
        $ids = array_slice($ids, 0, 100);
        $result = $this->signed_app_request(
            'POST',
            self::PLUGIN_API_PREFIX . '/site/' . $site_id . '/bulk-approve',
            array('manifestIds' => $ids)
        );
        if (!is_wp_error($result)) {
            $this->clear_cache();
            // CACHE_MANIFEST_SUMMARY base key is suffixed by status filter
            // (`_draft`, `_approved`, `_reverted`) when actually stored —
            // delete_transient on the base prefix leaves the per-status
            // transients intact. Wipe the prefix via wpdb so the next
            // render of any tab sees fresh data. Also nuke aggregation +
            // page-bulk caches because approved fixes change live scores.
            $this->bust_manifest_caches();
        }
        return $result;
    }

    /**
     * Invalidate every transient that surfaces manifest summaries, audit
     * aggregations, or per-page scores. Called after bulk approve so the
     * Agentic Fixes tabs + Dashboard tiles + page-list column all show
     * the freshly-approved state on next render. Cheap — runs zero queries
     * when no matching rows exist.
     */
    public function bust_manifest_caches() {
        global $wpdb;
        // Per-status manifest summary transients (draft/approved/reverted).
        delete_transient(self::CACHE_MANIFEST_SUMMARY);
        if (isset($wpdb)) {
            $wpdb->query(
                "DELETE FROM {$wpdb->options}
                 WHERE option_name LIKE '_transient_aacb_cache_manifest_summary_v2_%'
                    OR option_name LIKE '_transient_timeout_aacb_cache_manifest_summary_v2_%'
                    OR option_name LIKE '_transient_aacb_cache_pages_audit_bulk_v2_%'
                    OR option_name LIKE '_transient_timeout_aacb_cache_pages_audit_bulk_v2_%'"
            );
        }
        delete_transient('aacb_cache_audit_aggregation_v1');
    }

    /**
     * Override alt text on a single image row (KAN-19). Signed mutation sent
     * to the App service. Caller must validate input length (1-500 chars)
     * before this is called; server enforces the same bounds defensively.
     *
     * @param int    $image_id  Image row id.
     * @param string $text      New alt text. Trimmed by server.
     * @return array|WP_Error   Updated row projection on success.
     */
    public function override_image_alt_text($image_id, $text) {
        $image_id = (int) $image_id;
        if ($image_id <= 0) {
            return new WP_Error('bad_image_id', __('Invalid image ID', 'allaccessible'));
        }
        $text = is_string($text) ? sanitize_text_field($text) : '';
        if ($text === '' || mb_strlen($text) > 500) {
            return new WP_Error('bad_text', __('Alt text must be 1–500 characters', 'allaccessible'));
        }
        $result = $this->signed_app_request(
            'PATCH',
            self::PLUGIN_API_PREFIX . '/images/' . $image_id,
            array('text' => $text)
        );
        // Invalidate the images grid cache so the next page render reflects
        // this edit immediately (no 60s stale window).
        if (!is_wp_error($result)) {
            $this->flush_image_grid_cache();
        }
        return $result;
    }

    /**
     * Wipe every plugin-side API transient — pages bulk, per-page audit,
     * images grid, task stats, manifest summary, site options, audit
     * aggregation, scores, etc. Used by the "Flush plugin caches"
     * button on Account → Advanced and by mutation paths that need to
     * invalidate aggressively. Single targeted DELETE; no transient_keys()
     * enumeration cost.
     */
    public function flush_all_caches() {
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
              WHERE option_name LIKE '_transient_aacb_cache_%'
                 OR option_name LIKE '_transient_timeout_aacb_cache_%'
                 OR option_name LIKE '_transient_aacb_site_options_cache%'
                 OR option_name LIKE '_transient_timeout_aacb_site_options_cache%'"
        );
    }

    /**
     * Wipe every get_plugin_images() transient.
     */
    private function flush_image_grid_cache() {
        $current = (int) get_option('aacb_image_grid_cache_stamp', 1);
        update_option('aacb_image_grid_cache_stamp', $current + 1, false);
    }

    /**
     * Return the stored signing secret.
     */
    public function get_plugin_secret() {
        $secret = get_option(self::PLUGIN_SECRET_OPTION, '');
        $stamp  = (string) get_option('aacb_plugin_secret_version', '');
        if (!empty($secret) && $stamp === AACB_VERSION) {
            return $secret;
        }
        $fetched = $this->fetch_plugin_secret();
        return is_wp_error($fetched) ? '' : $fetched;
    }

    /**
     * One-shot fetch of the signing secret.
     * @return string|WP_Error
     */
    public function fetch_plugin_secret() {
        $account_id = get_option('aacb_accountID');
        if (!$account_id) {
            return new WP_Error('no_account_id', __('Account not configured', 'allaccessible'));
        }
        $site_url = get_site_url();

        $url = self::API_BASE_URL . self::PLUGIN_API_PREFIX . '/secret?' . http_build_query(array(
            'accountID' => $account_id,
            'siteUrl'   => $site_url,
        ));
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept'     => 'application/json',
                'User-Agent' => 'AllAccessible-WordPress/' . AACB_VERSION,
            ),
        ));

        $decoded = $this->decode_json_response($response);
        if (is_wp_error($decoded)) {
            return $decoded;
        }
        if (empty($decoded['pluginSecret']) || empty($decoded['canonicalSiteUrl'])) {
            return new WP_Error('bad_secret_response', __('Server returned invalid plugin secret payload', 'allaccessible'));
        }

        // Stored WITHOUT autoload — the signing secret must not ride
        // wp_load_alloptions() into every frontend request. delete+add
        // (rather than update_option's third arg) also flips autoload
        // on rows that existing installs already created as 'yes'.
        $this->save_option_no_autoload(self::PLUGIN_SECRET_OPTION,    $decoded['pluginSecret']);
        $this->save_option_no_autoload(self::PLUGIN_SECRET_CANON_OPT, $decoded['canonicalSiteUrl']);
        $this->save_option_no_autoload('aacb_plugin_secret_version',  AACB_VERSION);

        return $decoded['pluginSecret'];
    }

    /**
     * Persist an option with autoload=no on every WP version we support.
     */
    private function save_option_no_autoload($name, $value) {
        delete_option($name);
        add_option($name, $value, '', 'no');
    }

    /* ─── Scan trigger ───────────────────────── */

    /**
     * Resolve the per-site id (stored as aacb_siteID).
     *
     * @return int|WP_Error  Positive int site id, or WP_Error.
     */
    private function resolve_site_id() {
        $site_id = (int) get_option('aacb_siteID');
        $stamp   = (string) get_option('aacb_siteID_version', '');
        if ($site_id > 0 && $stamp === AACB_VERSION) {
            return $site_id;
        }
        $site_options = $this->get_site_options();
        if (is_wp_error($site_options)) {
            return $site_options;
        }
        $site_id = (int) get_option('aacb_siteID');
        if ($site_id <= 0) {
            return new WP_Error('no_site_id', __('No site associated with this account yet. Visit your WordPress homepage once to register this site, then return here.', 'allaccessible'));
        }
        return $site_id;
    }

    /**
     * Bust the site-options transient + persistent siteID option.
     */
    public function invalidate_site_resolution() {
        delete_transient('aacb_site_options_cache');
        delete_option('aacb_siteID');
        delete_option('aacb_siteID_version');
        delete_option(self::PLUGIN_SECRET_OPTION);
        delete_option(self::PLUGIN_SECRET_CANON_OPT);
        delete_option('aacb_plugin_secret_version');
    }

    /**
     * Start a scan workflow.
     *
     * @param string $sitemap_url Absolute sitemap URL (may be empty to skip ingest)
     * @param string $viewport    'desktop' | 'mobile' | 'both'
     * @return array|WP_Error
     *
     * @return bool true if dispatched, false if not configured
     */
    public function start_scan_workflow_async($sitemap_url = '', $viewport = 'both') {
        $sitemap_url = is_string($sitemap_url) ? esc_url_raw($sitemap_url) : '';
        if (!in_array($viewport, array('desktop', 'mobile', 'both'), true)) {
            $viewport = 'both';
        }

        $secret = get_option(self::PLUGIN_SECRET_OPTION, '');
        if (empty($secret)) {
            wp_schedule_single_event(time() + 1, 'aacb_fetch_plugin_secret_event');
            return false;
        }

        $account_id  = get_option('aacb_accountID');
        $canonical   = get_option(self::PLUGIN_SECRET_CANON_OPT) ?: get_site_url();
        $timestamp   = (string) time();
        $body        = array('sitemapUrl' => $sitemap_url, 'viewport' => $viewport);
        $body_string = wp_json_encode($body);
        $scan_path   = self::PLUGIN_API_PREFIX . '/scans/start';
        $payload     = 'POST' . "\n" . $scan_path . "\n" . $timestamp . "\n" . $body_string;
        $signature   = hash_hmac('sha256', $payload, $secret);

        wp_remote_post(self::APP_BASE_URL . $scan_path, array(
            'blocking' => false,   // fire-and-forget — don't wait for response
            'timeout'  => 5,       // connection setup window only
            'headers'  => array(
                'Accept'           => 'application/json',
                'Content-Type'     => 'application/json',
                'User-Agent'       => 'AllAccessible-WordPress/' . AACB_VERSION,
                'X-AAcb-AccountID' => $account_id,
                'X-AAcb-Timestamp' => $timestamp,
                'X-AAcb-Signature' => $signature,
                'X-AAcb-SiteUrl'   => $canonical,
                'X-AAcb-Platform'  => self::PLATFORM_HEADER,
            ),
            'body' => $body_string,
        ));

        delete_transient(self::CACHE_MANIFEST_SUMMARY . '_draft');
        delete_transient(self::CACHE_MANIFEST_SUMMARY . '_approved');
        delete_transient('aacb_cache_audit_aggregation_v1');
        update_option('aacb_last_scan_triggered_at', time());
        update_option('aacb_last_scan_sitemap', $sitemap_url);

        return true;
    }

    /**
     * Fetch tier-aware scan schedule from the App service. 
     *
     * @return array|WP_Error  { scanFrequency, scanFrequencyLabel, scanFrequencyDays,
     *                          planTier, lastScanAt, nextScanAt, sitemapUrl }
     */
    public function get_scan_schedule($force_refresh = false) {
        $cache_key = 'aacb_cache_scan_schedule';
        if (!$force_refresh) {
            $cached = get_transient($cache_key);
            if ($cached !== false) return $cached;
        }
        $result = $this->signed_app_request('GET', self::PLUGIN_API_PREFIX . '/scans/schedule', null);
        if (!is_wp_error($result)) {
            set_transient($cache_key, $result, HOUR_IN_SECONDS);
        }
        return $result;
    }

    /**
     * Read the last-triggered timestamp + sitemap URL for UI display.
     *
     * @return array { triggeredAt: int|null, sitemapUrl: string|null }
     */
    public function get_last_scan_meta() {
        $ts = (int) get_option('aacb_last_scan_triggered_at', 0);
        return array(
            'triggeredAt' => $ts > 0 ? $ts : null,
            'sitemapUrl'  => (string) get_option('aacb_last_scan_sitemap', ''),
        );
    }

    /**
     * Check whether this site can start a crawl right now.
     *
     * @return array|WP_Error
     */
    public function check_scan_eligibility() {
        $site_id = $this->resolve_site_id();
        if (is_wp_error($site_id)) return $site_id;
        $url = self::API_BASE_URL . '/api/crawler/eligibility?' . http_build_query(array(
            'subdomainId' => $site_id,
        ));
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept'     => 'application/json',
                'User-Agent' => 'AllAccessible-WordPress/' . AACB_VERSION,
            ),
        ));
        return $this->decode_json_response($response);
    }

    /**
     * Poll scan progress. 
     *
     * @param int $job_id Returned by start_scan() or trigger_page_scan().
     * @return array|WP_Error  ['job' => [...], 'pages' => [...], 'counts' => [...]]
     */
    public function get_scan_status($job_id) {
        $job_id = (int) $job_id;
        if ($job_id <= 0) {
            return new WP_Error('bad_job_id', __('Invalid scan job ID', 'allaccessible'));
        }
        $path = self::PLUGIN_API_PREFIX . '/scans/status?' . http_build_query(array(
            'jobId' => $job_id,
        ));
        return $this->signed_app_request('GET', $path, null);
    }

    /**
     * Send a signed request to the AllAccessible App service. 
     *
     * @param string            $method  GET / POST / PATCH
     * @param string            $path    Absolute path (begins with /)
     * @param array|object|null $body    Body to send as JSON. Pass null for GET.
     * @param bool              $_retry  Internal — set on the post-401 retry.
     * @param array             $opts    ['blocking' => false] dispatches
     *                                   fire-and-forget: no retry, no response.
     * @return array|WP_Error
     */
    private function signed_app_request($method, $path, $body, $_retry = false, $opts = array()) {
        $secret = $this->get_plugin_secret();
        if (empty($secret)) {
            return new WP_Error('no_plugin_secret', __('Plugin secret unavailable. Re-validate license to refresh credentials.', 'allaccessible'));
        }
        $account_id   = get_option('aacb_accountID');
        $canonical    = get_option(self::PLUGIN_SECRET_CANON_OPT) ?: get_site_url();
        $timestamp    = (string) time();
        $body_string  = ($body === null) ? '' : wp_json_encode($body);
        $sign_path    = strtok((string) $path, '?');
        $payload      = $method . "\n" . $sign_path . "\n" . $timestamp . "\n" . $body_string;
        $signature    = hash_hmac('sha256', $payload, $secret);

        $args = array(
            'method'  => $method,
            'timeout' => 15,
            'headers' => array(
                'Accept'              => 'application/json',
                'Content-Type'        => 'application/json',
                'User-Agent'          => 'AllAccessible-WordPress/' . AACB_VERSION,
                'X-AAcb-AccountID'    => $account_id,
                'X-AAcb-Timestamp'    => $timestamp,
                'X-AAcb-Signature'    => $signature,
                'X-AAcb-SiteUrl'      => $canonical,
                'X-AAcb-Platform'     => self::PLATFORM_HEADER,
            ),
        );
        if ($body !== null) {
            $args['body'] = $body_string;
        }

        if (isset($opts['blocking']) && $opts['blocking'] === false) {
            $args['blocking'] = false;
            $args['timeout']  = 5; // connection setup window only
            wp_remote_request(self::APP_BASE_URL . $path, $args);
            return array('dispatched' => true);
        }

        $response = $this->remote_with_retry(self::APP_BASE_URL . $path, $args);

        if (!$_retry && !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 401) {
            $refetched = $this->fetch_plugin_secret();
            if (!is_wp_error($refetched)) {
                return $this->signed_app_request($method, $path, $body, true);
            }
        }

        return $this->decode_json_response($response);
    }

    /**
     * Map the WordPress locale into the form the API expects.
     */
    private function wp_locale() {
        $locale = function_exists('get_locale') ? get_locale() : 'en_US';
        return sanitize_text_field($locale);
    }

    /**
     * Issue an HTTP request with one retry on TRANSIENT failures (connection
     * timeouts, 502/503/504). A single blip is logged non-reporting (info) and
     * retried after a short backoff; only a persistent failure falls through to
     * the caller's error reporting. Cuts Sentry noise from one-off cURL 28 /
     * 5xx against slow upstreams (WORDPRESS-PLUGIN-6/7/8) without hiding real
     * outages (two consecutive failures still report).
     */
    private function remote_with_retry($url, $args) {
        $attempts = 2;
        $delay_ms = 300;
        $response = null;
        for ($i = 1; $i <= $attempts; $i++) {
            $response = wp_remote_request($url, $args);
            if (!$this->is_transient_failure($response) || $i === $attempts) {
                return $response;
            }
            AllAccessible_Debug::info('ApiClient::retry', 'Transient API failure — retrying', array(
                'attempt' => $i,
                'reason'  => $this->failure_reason($response),
            ));
            usleep($delay_ms * 1000);
            $delay_ms *= 2;
        }
        return $response;
    }

    /**
     * Is this response a transient failure worth one retry?
     * cURL 28 (timeout), cURL 7 (connect failed), connection reset, or 502/503/504.
     */
    private function is_transient_failure($response) {
        if (is_wp_error($response)) {
            $msg = $response->get_error_message();
            return strpos($msg, 'cURL error 28') !== false
                || stripos($msg, 'timed out') !== false
                || strpos($msg, 'cURL error 7') !== false
                || stripos($msg, 'connection reset') !== false;
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        return in_array($code, array(502, 503, 504), true);
    }

    private function failure_reason($response) {
        return is_wp_error($response)
            ? $response->get_error_message()
            : ('HTTP ' . wp_remote_retrieve_response_code($response));
    }

    /**
     * Shared response decoder.
     */
    private function decode_json_response($response) {
        if (is_wp_error($response)) {
            // Transient connectivity (timeout / cURL 28 / connection reset) is
            // expected background noise on a hot path — log at warn for rate
            // visibility, not error. Genuinely unexpected transport failures
            // still go to error.
            $err_code = $response->get_error_code();
            $err_msg  = $response->get_error_message();
            $transient = ($err_code === 'http_request_failed')
                || strpos($err_msg, 'Operation timed out') !== false
                || strpos($err_msg, 'timed out') !== false
                || strpos($err_msg, 'Connection reset') !== false
                || strpos($err_msg, 'Could not resolve host') !== false;
            if ($transient) {
                AllAccessible_Debug::warn('ApiClient::transport', $err_msg);
            } else {
                AllAccessible_Debug::error('ApiClient::transport', $response);
            }
            return new WP_Error('api_request_failed', sprintf(
                /* translators: %s: error message */
                __('API request failed: %s', 'allaccessible'),
                $response->get_error_message()
            ));
        }
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if ($code !== 200) {
            $msg = is_array($decoded) && isset($decoded['error']) ? $decoded['error'] : ('HTTP ' . $code);
            $surface = 'ApiClient::http_' . $code;
            $payload = array('http_status' => $code, 'body' => $decoded);
            if ($code >= 500) {
                AllAccessible_Debug::error($surface, $msg, $payload);
            } elseif ($code === 401 || $code === 429) {
                AllAccessible_Debug::warn($surface, $msg, $payload);
            }
            return new WP_Error('api_error_' . $code, $msg, array('status' => $code, 'body' => $decoded));
        }
        if (json_last_error() !== JSON_ERROR_NONE) {
            AllAccessible_Debug::error('ApiClient::json_decode', json_last_error_msg(), array(
                'body_head' => substr((string) $body, 0, 200),
            ));
            return new WP_Error('json_decode_error', __('Failed to decode API response', 'allaccessible'));
        }
        return $decoded;
    }
}

// Initialize the API client
AllAccessible_ApiClient::get_instance();
