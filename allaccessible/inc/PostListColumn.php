<?php
/**
 * WordPress All Posts / All Pages "Accessibility" column.
 *
 * @package AllAccessible
 * @since   2.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AllAccessible_PostListColumn {

    /**
     * In-memory per-request map: permalink → bulk-row data.
     */
    private static ?array $url_map = null;

    /**
     * Lazy backstop. 
     */
    private static bool $lazy_prefetch_attempted = false;

    private static function lazy_prefetch_fallback() {
        if (self::$lazy_prefetch_attempted) return;
        self::$lazy_prefetch_attempted = true;

        global $wp_query;
        if (!$wp_query || empty($wp_query->posts)) return;

        $ids  = array();
        $urls = array();
        foreach ($wp_query->posts as $p) {
            if (!is_object($p) || !isset($p->ID)) continue;
            $canon = AllAccessible_UrlCanonicalizer::for_post((int) $p->ID);
            if ($canon === '') continue;
            $ids[]  = (int) $p->ID;
            $urls[] = $canon;
        }
        if (empty($ids)) return;

        $client = AllAccessible_ApiClient::get_instance();
        $bulk   = $client->get_pages_audit_bulk(false, $ids, array(), $urls);
        if (self::$url_map === null) self::$url_map = array();
        if (!is_wp_error($bulk) && is_array($bulk) && !empty($bulk['pages'])) {
            foreach ($bulk['pages'] as $row) {
                if (!empty($row['post_id'])) {
                    self::$url_map['post:' . (int) $row['post_id']] = $row;
                }
                $url = isset($row['page_url']) ? (string) $row['page_url'] : '';
                if ($url !== '') {
                    self::$url_map[self::normalize_url($url)] = $row;
                }
            }
        }
    }

    /**
     * AJAX diagnosis handler
     */
    public static function ajax_diagnose() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'forbidden'), 403);
        }
        check_ajax_referer('aacb_diag_lookup', '_wpnonce');

        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if ($post_id <= 0) {
            wp_send_json_error(array('message' => 'post_id required'), 400);
        }

        $post = get_post($post_id);
        $raw_permalink = $post ? (string) get_permalink($post_id) : '';
        $canonical     = $raw_permalink !== ''
            ? AllAccessible_UrlCanonicalizer::canonicalize($raw_permalink)
            : '';

        $client   = AllAccessible_ApiClient::get_instance();
        $response = $client->get_pages_audit_bulk(
            true, // force refresh — diagnostics must bypass cache
            array($post_id),
            array(),
            $canonical !== '' ? array($canonical) : array()
        );

        $row_match = null;
        if (!is_wp_error($response) && is_array($response) && !empty($response['pages'])) {
            foreach ($response['pages'] as $row) {
                $row_post_id = isset($row['post_id']) ? (int) $row['post_id'] : 0;
                $row_url     = isset($row['page_url']) ? (string) $row['page_url'] : '';
                $by_post_id  = ($row_post_id === $post_id);
                $by_url      = ($row_url !== '' && $row_url === $canonical);
                if ($by_post_id || $by_url) {
                    $row_match = array(
                        'matched_by'          => $by_post_id ? 'post_id' : 'canonical_url',
                        'row_post_id'         => $row_post_id ?: null,
                        'row_page_url'        => $row_url,
                        'row_score'           => $row['accessibility_score'] ?? null,
                        'row_audit_status'    => $row['audit_status'] ?? null,
                        'row_last_scan'       => $row['last_scan'] ?? null,
                    );
                    break;
                }
            }
        }

        wp_send_json_success(array(
            'inputs' => array(
                'post_id'         => $post_id,
                'post_status'     => $post ? $post->post_status : 'not_found',
                'post_type'       => $post ? $post->post_type : null,
                'raw_permalink'   => $raw_permalink,
                'canonical_url'   => $canonical,
                'site_url_option' => get_option('aacb_siteID'),
                'account_id'      => get_option('aacb_accountID'),
            ),
            'api_request' => array(
                'endpoint'  => '/integrations/v1/audits/by-subdomain',
                'post_ids'  => array($post_id),
                'page_urls' => $canonical !== '' ? array($canonical) : array(),
            ),
            'api_response' => array(
                'is_wp_error'   => is_wp_error($response),
                'error'         => is_wp_error($response) ? $response->get_error_message() : null,
                'pages_count'   => (!is_wp_error($response) && is_array($response) && isset($response['pages'])) ? count($response['pages']) : 0,
                'audited_count' => (!is_wp_error($response) && is_array($response)) ? ($response['audited_count'] ?? null) : null,
                'pages_total'   => (!is_wp_error($response) && is_array($response)) ? ($response['pages_count']   ?? null) : null,
                'first_3_rows'  => (!is_wp_error($response) && is_array($response) && !empty($response['pages']))
                                   ? array_slice($response['pages'], 0, 3)
                                   : array(),
            ),
            'lookup_result' => $row_match ?: array('matched_by' => 'none', 'reason' => 'no row returned for this post_id or URL'),
            'expected_match_key' => 'post:' . $post_id . ' OR ' . $canonical,
        ));
    }

    public static function register() {
        foreach (array('post', 'page') as $type) {
            add_filter("manage_{$type}_posts_columns",        array(__CLASS__, 'add_column'));
            add_action("manage_{$type}_posts_custom_column",  array(__CLASS__, 'render_column'), 10, 2);
        }
        // Inline CSS for badge + spinner + cell wrapper.
        add_action('admin_head-edit.php', array(__CLASS__, 'print_styles'));
        // Async swap-in script — replaces server-rendered spinners with
        // real scores once the batch endpoint responds. Page paints
        // instantly; scores arrive shortly after.
        add_action('admin_footer-edit.php', array(__CLASS__, 'print_async_script'));
        // Batch endpoint backing the async swap.
        add_action('wp_ajax_aacb_batch_scores', array(__CLASS__, 'ajax_batch_scores'));
        // Diagnostic AJAX handler — Account → Advanced tab.
        add_action('wp_ajax_aacb_diag_lookup', array(__CLASS__, 'ajax_diagnose'));
        // Flush every plugin transient — same panel.
        add_action('wp_ajax_aacb_flush_caches', array(__CLASS__, 'ajax_flush_caches'));
    }

    /**
     * AJAX handler
     */
    public static function ajax_flush_caches() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'forbidden'), 403);
        }
        check_ajax_referer('aacb_flush_caches', '_wpnonce');
        AllAccessible_ApiClient::get_instance()->flush_all_caches();
        wp_send_json_success(array('message' => 'caches flushed'));
    }

    /**
     * Hook into `the_posts` once per WP_Query on edit.php screens.
     *
     * @param array     $posts  Raw posts WP_Query just returned.
     * @param WP_Query  $query  The query instance.
     * @return array            Posts unchanged.
     */
    public static function prefetch_for_visible_posts($posts, $query) {
        if (!is_admin() || empty($posts)) return $posts;
        if (!$query || $query->get('post_type') === '' || !is_array($posts)) return $posts;
        global $pagenow;
        if ($pagenow !== 'edit.php') return $posts;
        if (!self::is_eligible()) return $posts;

        $ids  = array();
        $urls = array();
        foreach ($posts as $p) {
            if (!is_object($p) || !isset($p->ID)) continue;
            $canon = AllAccessible_UrlCanonicalizer::for_post((int) $p->ID);
            if ($canon === '') continue;
            $ids[]  = (int) $p->ID;
            $urls[] = $canon;
        }
        if (empty($ids)) return $posts;

        $client = AllAccessible_ApiClient::get_instance();
        $bulk   = $client->get_pages_audit_bulk(false, $ids, array(), $urls);
        if (self::$url_map === null) self::$url_map = array();
        if (!is_wp_error($bulk) && is_array($bulk) && !empty($bulk['pages'])) {
            foreach ($bulk['pages'] as $row) {
                if (!empty($row['post_id'])) {
                    self::$url_map['post:' . (int) $row['post_id']] = $row;
                }
                $url = isset($row['page_url']) ? (string) $row['page_url'] : '';
                if ($url !== '') {
                    self::$url_map[self::normalize_url($url)] = $row;
                }
            }
        }
        if (AllAccessible_Debug::is_enabled()) {
            self::$debug_payload = array(
                'requested_ids'  => $ids,
                'requested_urls' => $urls,
                'response_meta'  => is_wp_error($bulk) ? array('error' => $bulk->get_error_message()) : array(
                    'pages_returned' => isset($bulk['pages']) ? count($bulk['pages']) : 0,
                    'audited_count'  => $bulk['audited_count'] ?? null,
                    'pages_total'    => $bulk['pages_count']   ?? null,
                ),
                'map_keys'       => array_keys(self::$url_map),
            );
            add_action('admin_footer', array(__CLASS__, 'print_debug_console'));
        }
        return $posts;
    }

    /**
     * Per-request debug payload captured during prefetch. 
     */
    private static ?array $debug_payload = null;

    /**
     * Print a console.log dump
     */
    public static function print_debug_console() {
        if (self::$debug_payload === null) return;
        $payload = self::$debug_payload;
        // Build per-row lookup result so user sees post → resolved row
        // (or "missed") inline with the request/response context.
        $row_lookups = array();
        foreach ((array) $payload['requested_ids'] as $i => $pid) {
            $permalink = isset($payload['requested_urls'][$i]) ? $payload['requested_urls'][$i] : '';
            $row       = self::lookup_post((int) $pid);
            $row_lookups[] = array(
                'post_id'       => (int) $pid,
                'canonical'     => $permalink,
                'matched'       => $row !== null,
                'matched_by'    => $row !== null
                    ? (isset(self::$url_map['post:' . (int) $pid]) ? 'post_id' : 'canonical_url')
                    : null,
                'score'         => $row['accessibility_score'] ?? null,
                'audit_status'  => $row['audit_status']        ?? null,
                'last_audit_id' => $row['last_audit_id']       ?? null,
            );
        }
        $payload['row_lookups'] = $row_lookups;
        AllAccessible_Debug::console('PostListColumn', $payload);
    }

    public static function add_column(array $columns): array {
        // Skip the column on tiers that have no audit data to render.
        if (!self::is_eligible()) return $columns;

        // Insert before the date column for visual proximity with other
        // freshness signals. Falls back to appending if 'date' isn't set.
        if (!isset($columns['date'])) {
            $columns['aacb_a11y'] = __('Accessibility', 'allaccessible');
            return $columns;
        }
        $out = array();
        foreach ($columns as $k => $v) {
            if ($k === 'date') {
                $out['aacb_a11y'] = __('Accessibility', 'allaccessible');
            }
            $out[$k] = $v;
        }
        return $out;
    }

    public static function render_column($column_name, $post_id) {
        if ($column_name !== 'aacb_a11y') return;
        if (!self::is_eligible()) {
            echo '<span style="color:#999;">—</span>';
            return;
        }
        $canonical = AllAccessible_UrlCanonicalizer::for_post((int) $post_id);
        $post                = get_post((int) $post_id);
        $non_public_statuses = array('draft', 'pending', 'auto-draft', 'future', 'private');
        $is_draft_like       = $post && in_array($post->post_status, $non_public_statuses, true);
        $preview_url         = '';
        if ($is_draft_like && function_exists('get_preview_post_link')) {
            $preview_url = (string) get_preview_post_link($post);
            if ($preview_url !== '') {
                $preview_url = add_query_arg('aa_force_audit', 'draft_preview', $preview_url);
            }
        }

        printf(
            '<span class="aacb-col-cell" data-post-id="%d" data-canonical="%s" data-post-status="%s" data-preview-url="%s">'
            . '<span class="aacb-col-pending" title="%s" aria-label="%s">'
            . '<span class="aacb-col-pending-dot" aria-hidden="true"></span>'
            . '<span class="aacb-col-pending-text">%s</span>'
            . '</span>'
            . '</span>',
            (int) $post_id,
            esc_attr($canonical),
            esc_attr($post ? $post->post_status : ''),
            esc_attr($preview_url),
            esc_attr__('Loading accessibility score', 'allaccessible'),
            esc_attr__('Loading', 'allaccessible'),
            esc_html__('Loading…', 'allaccessible')
        );

        $debug_opts = get_option('aacb_options', array());
        if (!empty($debug_opts['debug_mode'])) {
            printf(
                "\n<!-- aacb-debug post_id=%d canonical=%s render=async-placeholder -->\n",
                (int) $post_id,
                esc_attr($canonical)
            );
        }
    }

    /**
     * AJAX batch handler
     */
    public static function ajax_batch_scores() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'forbidden'), 403);
        }
        check_ajax_referer('aacb_batch_scores', '_wpnonce');

        $raw_ids = isset($_POST['post_ids']) ? (array) $_POST['post_ids'] : array();
        $ids = array();
        foreach ($raw_ids as $pid) {
            $n = (int) $pid;
            if ($n > 0) $ids[] = $n;
        }
        $ids = array_values(array_unique($ids));
        if (count($ids) > 200) $ids = array_slice($ids, 0, 200);
        if (empty($ids)) {
            wp_send_json_success(array('rows' => new \stdClass()));
        }

        $urls = array();
        foreach ($ids as $pid) {
            $canon = AllAccessible_UrlCanonicalizer::for_post($pid);
            if ($canon !== '') $urls[] = $canon;
        }

        $client = AllAccessible_ApiClient::get_instance();
        $bulk   = $client->get_pages_audit_bulk(false, $ids, array(), $urls);

        $out = array();
        if (!is_wp_error($bulk) && is_array($bulk) && !empty($bulk['pages'])) {
            // Map by post_id first (preferred). Index by canonical URL too
            // so rows that came back URL-matched still resolve.
            $by_post = array();
            $by_url  = array();
            foreach ($bulk['pages'] as $row) {
                if (!empty($row['post_id'])) $by_post[(int) $row['post_id']] = $row;
                if (!empty($row['page_url'])) $by_url[(string) $row['page_url']] = $row;
            }
            foreach ($ids as $pid) {
                $row = $by_post[$pid] ?? null;
                if ($row === null) {
                    $canon = AllAccessible_UrlCanonicalizer::for_post($pid);
                    if ($canon !== '' && isset($by_url[$canon])) $row = $by_url[$canon];
                }
                if ($row !== null) {
                    $breakdown = isset($row['score_breakdown']) && is_array($row['score_breakdown'])
                        ? $row['score_breakdown']
                        : array();
                    $out[(string) $pid] = array(
                        'score'         => $row['accessibility_score'] ?? null,
                        'audit_status'  => $row['audit_status']        ?? null,
                        'last_scan'     => $row['last_scan']           ?? null,
                        'audit_id'      => $row['last_audit_id']       ?? null,
                        
                        // "+N potential" subline when applicable.
                        'potential'     => $breakdown['potential']         ?? null,
                        'raw'           => $breakdown['raw']               ?? null,
                        'pending_count' => isset($breakdown['manifest_pending']) ? (int) $breakdown['manifest_pending'] : 0,
                    );
                }
            }
        }
        wp_send_json_success(array('rows' => (object) $out));
    }

    /**
     * Print the small JS that drives the async score-load on edit.php.
     */
    public static function print_async_script() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->base !== 'edit') return;
        if (!self::is_eligible()) return;
        ?>
        <script>
        (function() {
            var cells = document.querySelectorAll('.aacb-col-cell[data-post-id]');
            if (!cells.length) return;

            var ids = [];
            cells.forEach(function(c) {
                var pid = parseInt(c.getAttribute('data-post-id'), 10);
                if (pid > 0) ids.push(pid);
            });
            if (!ids.length) return;

            var data = new FormData();
            data.append('action',   'aacb_batch_scores');
            data.append('_wpnonce', <?php echo wp_json_encode(wp_create_nonce('aacb_batch_scores')); ?>);
            ids.forEach(function(id) { data.append('post_ids[]', String(id)); });

            fetch(ajaxurl, { method: 'POST', body: data, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(resp) {
                    var rows = (resp && resp.success && resp.data && resp.data.rows) ? resp.data.rows : {};
                    cells.forEach(function(c) {
                        var pid = c.getAttribute('data-post-id');
                        var row = rows[pid];
                        c.innerHTML = renderCell(row, c);
                    });
                })
                .catch(function() {

                });

            function renderCell(row, cell) {
                if (!row || row.score === null || row.score === undefined) {
                    var status   = (cell && cell.getAttribute('data-post-status')) || '';
                    var nonPublic = ['draft','pending','auto-draft','future','private'];
                    if (nonPublic.indexOf(status) !== -1) {
                        // Draft-like: the anonymous crawler can't reach it.
                        var preview = (cell && cell.getAttribute('data-preview-url')) || '';
                        var label   = '<?php echo esc_js(__('Will scan on publish', 'allaccessible')); ?>';
                        var tip     = '<?php echo esc_js(__('Drafts are not reachable by the public crawler. Publish to scan automatically, or preview to scan now.', 'allaccessible')); ?>';
                        var html    = '<span class="aacb-col-draft" title="' + tip + '">'
                            + '<span class="aacb-col-draft-text" style="color:#6b7280;">' + label + '</span>'
                            + '</span>';
                        if (preview) {
                            var link = '<?php echo esc_js(__('Preview & scan', 'allaccessible')); ?>';
                            html += '<br><a href="' + preview + '" target="_blank" rel="noopener" class="aacb-col-draft-link" style="font-size:11px;">' + link + ' ↗</a>';
                        }
                        return html;
                    }
                    return '<span class="aacb-col-pending" title="<?php echo esc_js(__('Awaiting scan — AllAccessible will populate this on the next crawl', 'allaccessible')); ?>">'
                        + '<span class="aacb-col-pending-dot" aria-hidden="true"></span>'
                        + '<span class="aacb-col-pending-text"><?php echo esc_js(__('Awaiting scan', 'allaccessible')); ?></span>'
                        + '</span>';
                }
                var score  = parseInt(row.score, 10);
                var color  = score > 85 ? '#16a34a' : (score > 70 ? '#f59e0b' : '#dc2626');
                // Status surfaced via title only when stale/failed — fresh
                // is the default expectation, no need to label it.
                var status = (row.audit_status || '').toLowerCase();
                var title  = '';
                if      (status === 'stale')  title = '<?php echo esc_js(__('Score is stale — page hasn\'t been scanned recently', 'allaccessible')); ?>';
                else if (status === 'failed') title = '<?php echo esc_js(__('Last scan failed', 'allaccessible')); ?>';
                else                          title = '<?php echo esc_js(__('Accessibility score', 'allaccessible')); ?>';

                var html = '<span class="aacb-col-score" style="color:' + color + ';" title="' + title + '">' + score + '</span>'
                         + '<span class="aacb-col-score-suffix">/100</span>';

                // "↑ X potential" when manifest fixes are pending
                // approval. Subtle, single-line, links the customer's
                // attention to the Agentic Fixes queue.
                var potential = row.potential !== null && row.potential !== undefined ? parseInt(row.potential, 10) : null;
                if (potential !== null && potential > score) {
                    html += '<div class="aacb-col-potential" title="<?php echo esc_js(__('Approve pending fixes to reach this score', 'allaccessible')); ?>">'
                          + '↑ ' + potential + '% potential'
                          + '</div>';
                }

                if (row.last_scan) {
                    var ts = new Date(row.last_scan);
                    if (!isNaN(ts.getTime())) {
                        var diff = Math.floor((Date.now() - ts.getTime()) / 1000);
                        var when = diff < 60     ? 'just now'
                                  : diff < 3600  ? Math.floor(diff/60)    + 'm ago'
                                  : diff < 86400 ? Math.floor(diff/3600)  + 'h ago'
                                  :                Math.floor(diff/86400) + 'd ago';
                        html += '<div class="aacb-col-when" title="' + row.last_scan + '">' + when + '</div>';
                    }
                }
                return html;
            }
        })();
        </script>
        <?php
    }

    public static function print_styles() {
        ?>
        <style>
            .column-aacb_a11y { width: 110px; }
            .aacb-col-cell { display: inline-block; line-height: 1.3; }
            .aacb-col-score {
                font-size: 16px;
                font-weight: 600;
                font-variant-numeric: tabular-nums;
            }
            .aacb-col-score-suffix {
                font-size: 11px;
                color: #6b7280;
                margin-left: 1px;
            }
            .aacb-col-when {
                margin-top: 2px;
                font-size: 12px;
                color: #6b7280;
            }
            .aacb-col-potential {
                margin-top: 2px;
                font-size: 11px;
                color: #6366f1;
                font-weight: 500;
            }
            .aacb-col-pending {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                color: #6b7280;
                font-size: 12px;
            }
            .aacb-col-pending-dot {
                display: inline-block;
                width: 10px;
                height: 10px;
                border: 2px solid #d1d5db;
                border-top-color: #6366f1;
                border-radius: 50%;
                animation: aacb-spin 0.9s linear infinite;
            }
            @keyframes aacb-spin {
                to { transform: rotate(360deg); }
            }
            @media (prefers-reduced-motion: reduce) {
                .aacb-col-pending-dot { animation: none; border-top-color: #d1d5db; }
            }
        </style>
        <?php
    }

    /* ─── helpers ──────────────────────────────────────────────────── */

    /**
     * Tier gate.
     */
    private static function is_eligible(): bool {
        if (!get_option('aacb_accountID')) return false;
        if (!class_exists('AllAccessible_ApiClient')) return false;
        $client = AllAccessible_ApiClient::get_instance();
        $tier   = (string) $client->get_subscription_tier();
        if ($tier === 'free' || $tier === 'legacy') return false;

        $opts = $client->get_site_options();
        if (is_wp_error($opts) || !is_object($opts)) return false;
        $features = isset($opts->features) && is_array($opts->features) ? $opts->features : array();
        // image_alt_text_manager comes with the audit pipeline — using it
        // as a proxy for "this account has per-page audit data".
        return in_array('agentic_fixes_approve', $features, true)
            || in_array('image_alt_text_manager', $features, true);
    }

    /**
     * One-shot bulk fetch + permalink-keyed map. 
     */
    private static function lookup_post(int $post_id): ?array {
        if (self::$url_map === null) self::$url_map = array();

        // Prefer indexed post_id lookup when available.
        $by_id = 'post:' . (int) $post_id;
        if (isset(self::$url_map[$by_id])) return self::$url_map[$by_id];

        // URL fallback.
        $permalink = get_permalink($post_id);
        if (!$permalink) return null;
        $key = self::normalize_url($permalink);
        return self::$url_map[$key] ?? null;
    }

    /**
     * Thin wrapper — delegates to the central UrlCanonicalizer so every
     * plugin file produces identical keys for the same URL.
     */
    private static function normalize_url(string $url): string {
        return AllAccessible_UrlCanonicalizer::canonicalize($url);
    }
}
