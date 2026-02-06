<?php
/**
 * AllAccessible API Client
 *
 * Handles communication with Symfony backend API for:
 * - Accessibility audit scores
 * - Compliance status
 * - Site analysis results
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
    const API_BASE_URL = 'https://api.allaccessible.org'; // Lambda API for site options, audit scores

    /**
     * Cache expiration times
     */
    const CACHE_AUDIT_SCORES = 'aacb_cache_audit_scores';
    const CACHE_SITE_STATUS = 'aacb_cache_site_status';
    const CACHE_DURATION = 1800; // 30 minutes

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
     * Get audit scores for current site
     *
     * @param bool $force_refresh Force refresh from API (skip cache)
     * @return array|WP_Error Audit scores or error
     */
    public function get_audit_scores($force_refresh = false) {
        // Check cache first unless force refresh
        if (!$force_refresh) {
            $cached = get_transient(self::CACHE_AUDIT_SCORES);
            if ($cached !== false) {
                return $cached;
            }
        }

        $site_id = get_option('aacb_siteID');
        $account_id = get_option('aacb_accountID');

        if (!$site_id || !$account_id) {
            return new WP_Error(
                'no_site_id',
                __('Site not connected to AllAccessible account. Please configure your account settings.', 'allaccessible')
            );
        }

        $response = $this->make_request('/audit-scores', array(
            'siteId' => $site_id,
            'accountId' => $account_id,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        // Cache the successful response
        set_transient(self::CACHE_AUDIT_SCORES, $response, self::CACHE_DURATION);

        return $response;
    }

    /**
     * Get site compliance status
     *
     * @param bool $force_refresh Force refresh from API (skip cache)
     * @return array|WP_Error Compliance status or error
     */
    public function get_compliance_status($force_refresh = false) {
        // Check cache first unless force refresh
        if (!$force_refresh) {
            $cached = get_transient(self::CACHE_SITE_STATUS);
            if ($cached !== false) {
                return $cached;
            }
        }

        $site_id = get_option('aacb_siteID');

        if (!$site_id) {
            return new WP_Error(
                'no_site_id',
                __('Site not connected to AllAccessible account.', 'allaccessible')
            );
        }

        $response = $this->make_request('/compliance-status', array(
            'siteId' => $site_id,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        // Cache the successful response
        set_transient(self::CACHE_SITE_STATUS, $response, self::CACHE_DURATION);

        return $response;
    }

    /**
     * Trigger a new site audit
     *
     * @return array|WP_Error Response or error
     */
    public function trigger_audit() {
        $site_id = get_option('aacb_siteID');
        $account_id = get_option('aacb_accountID');

        if (!$site_id || !$account_id) {
            return new WP_Error(
                'no_site_id',
                __('Site not connected to AllAccessible account.', 'allaccessible')
            );
        }

        $response = $this->make_request('/trigger-audit', array(
            'siteId' => $site_id,
            'accountId' => $account_id,
            'url' => get_site_url(),
        ), 'POST');

        // Clear cache after triggering audit
        delete_transient(self::CACHE_AUDIT_SCORES);
        delete_transient(self::CACHE_SITE_STATUS);

        return $response;
    }

    /**
     * Make API request
     *
     * @param string $endpoint API endpoint (e.g., '/audit-scores')
     * @param array $data Request data
     * @param string $method HTTP method (GET or POST)
     * @return array|WP_Error Response data or error
     */
    private function make_request($endpoint, $data = array(), $method = 'POST') {
        $url = self::API_BASE_URL . $endpoint;

        $args = array(
            'method' => $method,
            'timeout' => 15,
            'sslverify' => true,
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'AllAccessible-WordPress/' . AACB_VERSION,
            ),
        );

        if ($method === 'POST') {
            $args['body'] = json_encode($data);
        } else {
            $url = add_query_arg($data, $url);
        }

        $response = wp_remote_request($url, $args);

        // Handle HTTP errors
        if (is_wp_error($response)) {
            return new WP_Error(
                'api_request_failed',
                sprintf(
                    /* translators: %s: error message */
                    __('API request failed: %s', 'allaccessible'),
                    $response->get_error_message()
                )
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        // Handle non-200 responses
        if ($response_code !== 200) {
            $error_message = __('Unknown API error', 'allaccessible');

            // Try to parse error message from response
            $decoded = json_decode($body, true);
            if ($decoded && isset($decoded['error'])) {
                $error_message = $decoded['error'];
            } elseif ($decoded && isset($decoded['message'])) {
                $error_message = $decoded['message'];
            }

            return new WP_Error(
                'api_error_' . $response_code,
                sprintf(
                    /* translators: 1: HTTP status code, 2: error message */
                    __('API error (%1$d): %2$s', 'allaccessible'),
                    $response_code,
                    $error_message
                )
            );
        }

        // Decode JSON response
        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'json_decode_error',
                __('Failed to decode API response', 'allaccessible')
            );
        }

        return $decoded;
    }

    /**
     * Clear all API caches
     */
    public function clear_cache() {
        delete_transient(self::CACHE_AUDIT_SCORES);
        delete_transient(self::CACHE_SITE_STATUS);
    }

    /**
     * Get formatted audit score for display
     *
     * @param array $scores Audit scores from API
     * @return array Formatted score data
     */
    public function format_audit_score($scores) {
        if (is_wp_error($scores)) {
            return array(
                'score' => 0,
                'level' => 'unknown',
                'color' => 'aacx-gray-500',
                'label' => __('Not Available', 'allaccessible'),
                'error' => $scores->get_error_message(),
            );
        }

        $score = isset($scores['overall_score']) ? (int) $scores['overall_score'] : 0;

        // Determine level and color
        if ($score >= 90) {
            $level = 'excellent';
            $color = 'aacx-success';
            $label = __('Excellent', 'allaccessible');
        } elseif ($score >= 75) {
            $level = 'good';
            $color = 'aacx-secondary';
            $label = __('Good', 'allaccessible');
        } elseif ($score >= 50) {
            $level = 'needs_work';
            $color = 'aacx-warning';
            $label = __('Needs Work', 'allaccessible');
        } else {
            $level = 'critical';
            $color = 'aacx-danger';
            $label = __('Critical', 'allaccessible');
        }

        return array(
            'score' => $score,
            'level' => $level,
            'color' => $color,
            'label' => $label,
            'wcag_a' => isset($scores['wcag_a']) ? $scores['wcag_a'] : null,
            'wcag_aa' => isset($scores['wcag_aa']) ? $scores['wcag_aa'] : null,
            'wcag_aaa' => isset($scores['wcag_aaa']) ? $scores['wcag_aaa'] : null,
            'issues_count' => isset($scores['issues_count']) ? $scores['issues_count'] : 0,
            'last_scan' => isset($scores['last_scan']) ? $scores['last_scan'] : null,
        );
    }

    /**
     * Get compliance badge HTML
     *
     * @param string $level Compliance level (A, AA, AAA)
     * @param bool $compliant Is site compliant at this level
     * @return string HTML badge
     */
    public function get_compliance_badge($level, $compliant) {
        $class = $compliant ? 'aacx-badge-success' : 'aacx-badge-warning';
        $text = $compliant
            ? sprintf(__('WCAG %s Compliant', 'allaccessible'), $level)
            : sprintf(__('WCAG %s Non-Compliant', 'allaccessible'), $level);

        return sprintf(
            '<span class="aacx-badge %s">%s</span>',
            esc_attr($class),
            esc_html($text)
        );
    }

    /**
     * Get site validation data from API (includes tier, limits, usage, config)
     *
     * @param bool $force_refresh Force refresh from API (skip cache)
     * @return object|WP_Error Site validation data or error
     */
    public function get_site_options($force_refresh = false) {
        // Check cache first unless force refresh
        if (!$force_refresh) {
            $cached = get_transient('aacb_site_options_cache');
            if ($cached !== false) {
                return $cached;
            }
        }

        $account_id = get_option('aacb_accountID');

        if (!$account_id) {
            return new WP_Error(
                'no_account_id',
                __('Account ID not found', 'allaccessible')
            );
        }

        // Call /validate endpoint with comprehensive data
        $response = wp_remote_post('https://api.allaccessible.org/validate', array(
            'method' => 'POST',
            'sslverify' => false,
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
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if ($data) {
            // Save siteID if returned (for backwards compatibility)
            if (isset($data->siteID)) {
                update_option('aacb_siteID', $data->siteID);
            }

            // Cache for 30 minutes
            set_transient('aacb_site_options_cache', $data, self::CACHE_DURATION);
            return $data;
        }

        return new WP_Error('invalid_response', __('Invalid API response', 'allaccessible'));
    }

    /**
     * Get current subscription tier from API
     *
     * Always fetches tier from /validate API endpoint to ensure tier updates
     * (free → paid) are reflected immediately without WordPress option storage.
     *
     * @return string Subscription tier: 'free', 'trial', 'starter', 'legacy', 'enterprise'
     */
    public function get_subscription_tier() {
        $site_options = $this->get_site_options();

        if (is_wp_error($site_options)) {
            error_log('AllAccessible - API Error getting tier: ' . $site_options->get_error_message());
            // Only fallback to 'free' if API is completely unavailable
            // This ensures we don't serve wrong tier if API is temporarily down
            return 'free';
        }

        // Extract tier from _meta.pricingTier (correct location in API response)
        if (isset($site_options->_meta->pricingTier)) {
            $tier = $site_options->_meta->pricingTier;
            error_log('AllAccessible - Tier from API: ' . $tier);
            return $tier;
        }

        // Fallback: Old location (top-level tier field)
        if (isset($site_options->tier)) {
            $tier = $site_options->tier;
            error_log('AllAccessible - Tier from API (legacy field): ' . $tier);
            return $tier;
        }

        // If API returned but no tier field, default to free
        error_log('AllAccessible - No tier in API response, defaulting to free');
        return 'free';
    }

    /**
     * Check if account is paid (from /validate response)
     *
     * @return bool True if paid subscription (starter, enterprise, legacy, or trial)
     */
    public function is_paid_account() {
        $site_options = $this->get_site_options();

        if (is_wp_error($site_options)) {
            return false;
        }

        // Primary check: paid field from /validate
        if (isset($site_options->paid)) {
            return (bool) $site_options->paid;
        }

        // Fallback: Check tier (includes trial as paid for feature access)
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
     * Get site ID from API (for building URLs)
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
}

// Initialize the API client
AllAccessible_ApiClient::get_instance();
