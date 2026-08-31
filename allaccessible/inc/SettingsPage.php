<?php
/**
 * AllAccessible Settings Page
 *
 * @package AllAccessible
 * @since   2.0.0
 */

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

class AllAccessible_SettingsPage {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Submenu order (by priority): Dashboard=10, Agentic=15, Image=18, Widget=19, Account=20.
        add_action('admin_menu', array($this, 'register_settings_page'));
        add_action('admin_menu', array($this, 'register_widget_page'), 19);
        add_action('admin_menu', array($this, 'register_account_page'), 20);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_aacb_save_quick_toggles', array($this, 'ajax_save_quick_toggles'));
    }

    public function register_settings_page() {
        // Top-level menu
        add_menu_page(
            __('AllAccessible', 'allaccessible'),
            __('AllAccessible', 'allaccessible'),
            'manage_options',
            'allaccessible',
            array($this, 'render_settings_page'),
            'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 317.9 317.89"><path d="M256.62,57.88l-97.9,97.9L110,107c-17.93-17.93-45,9.18-27.11,27.11l62.2,62.2a19.08,19.08,0,0,0,13.35,5.59h.67a19.14,19.14,0,0,0,13.35-5.59L283.73,85C301.67,67.06,274.55,40,256.62,57.88Z" style="fill-rule:evenodd;fill:#fff"/><path d="M159,60.31a27.86,27.86,0,1,1-27.86,27.85A27.85,27.85,0,0,1,159,60.31ZM159,0a158.88,158.88,0,1,0,138.9,81.62,30.48,30.48,0,0,1-7,10.54L278.26,104.8a131.11,131.11,0,0,1-25.75,145.82l-47.35-47.35c-17.93-17.93-45,9.18-27.11,27.11L221.66,274A131.21,131.21,0,0,1,96,273.83l43.6-43.6c17.94-17.94-9.19-45-27.11-27.11L65.16,250.4A131,131,0,0,1,242.29,57.88l7.17-7.16a29.33,29.33,0,0,1,17.19-8.67A158.41,158.41,0,0,0,159,0Z" style="fill-rule:evenodd;fill:#fff"/></svg>'),
            25
        );

        // Default submenu = same slug so the parent menu highlights when active
        add_submenu_page(
            'allaccessible',
            __('Dashboard', 'allaccessible'),
            __('Dashboard', 'allaccessible'),
            'manage_options',
            'allaccessible',
            array($this, 'render_settings_page')
        );
    }

    public function register_account_page() {
        add_submenu_page(
            'allaccessible',
            __('Account & Settings', 'allaccessible'),
            __('Account', 'allaccessible'),
            'manage_options',
            'allaccessible-account',
            array($this, 'render_account_page')
        );
    }

    public function register_widget_page() {
        add_submenu_page(
            'allaccessible',
            __('Widget Customizer', 'allaccessible'),
            __('Widget Customizer', 'allaccessible'),
            'manage_options',
            'allaccessible-widget',
            array($this, 'render_widget_page')
        );
    }

    /**
     * Standalone Widget Customizer page.
     */
    public function render_widget_page() {
        if (!get_option('aacb_wizard_completed') && !get_option('aacb_accountID')) {
            wp_redirect(admin_url('admin.php?page=allaccessible-wizard'));
            exit;
        }

        $account_id = get_option('aacb_accountID');
        $has_account = !empty($account_id);
        $site_options = null;
        $widget_settings_url = 'https://app.allaccessible.org/widget';
        $account_tier = 'unknown';

        if ($has_account) {
            $api_client = AllAccessible_ApiClient::get_instance();
            $site_options        = $api_client->get_site_options();
            $account_tier        = $api_client->get_subscription_tier();
            $widget_settings_url = $api_client->get_widget_settings_url();
        }

        $is_paid_tier = in_array($account_tier, array('starter', 'legacy', 'enterprise'), true);
        ?>
        <div class="allaccessible-admin" style="margin: -10px 0 0 -20px;">
            <div class="aacx-v2" style="background: var(--aacx-bg); min-height: 100vh;">
                <div class="aacx-v2__page">
                    <header class="aacx-v2__page-header">
                        <div>
                            <div class="aacx-v2__page-eyebrow"><?php _e('Widget', 'allaccessible'); ?></div>
                            <h1><?php _e('Widget Customizer', 'allaccessible'); ?></h1>
                            <p class="aacx-v2__page-desc"><?php _e('Customize the AllAccessible widget that visitors see on your site. Changes save instantly and clear the visitor cache.', 'allaccessible'); ?></p>
                        </div>
                    </header>

                    <?php if ($has_account && $is_paid_tier && $site_options && !is_wp_error($site_options)) : ?>
                        <?php aacb_render_widget_customizer($site_options, $widget_settings_url); ?>
                    <?php elseif (!$has_account) : ?>
                        <div class="aacx-v2__card aacx-v2__card--elevated">
                            <div class="aacx-v2__card-body">
                                <div class="aacx-v2__empty">
                                    <p class="aacx-v2__empty-title"><?php _e('Connect your AllAccessible account', 'allaccessible'); ?></p>
                                    <p><?php _e('Finish setup to customize your widget.', 'allaccessible'); ?></p>
                                    <p style="margin-top: var(--aacx-space-4);">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=allaccessible-wizard')); ?>" class="aacx-v2__btn aacx-v2__btn--primary"><?php _e('Start setup', 'allaccessible'); ?></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="aacx-v2__card aacx-v2__card--elevated">
                            <div class="aacx-v2__card-body">
                                <div class="aacx-v2__empty">
                                    <p class="aacx-v2__empty-title"><?php _e('Widget customization is a paid feature', 'allaccessible'); ?></p>
                                    <p><?php _e('Upgrade your AllAccessible plan to customize the widget color, position, icon, and shape — plus enable agentic AI remediation.', 'allaccessible'); ?></p>
                                    <p style="margin-top: var(--aacx-space-4);">
                                        <a href="https://app.allaccessible.org/billing" target="_blank" class="aacx-v2__btn aacx-v2__btn--primary"><?php _e('Upgrade plan', 'allaccessible'); ?></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function enqueue_assets($hook) {
        $allowed_hooks = array(
            'toplevel_page_allaccessible',
            'allaccessible_page_allaccessible-account',
            'allaccessible_page_allaccessible-widget',
        );
        if (!in_array($hook, $allowed_hooks, true)) {
            return;
        }

        // Legacy admin styles (still used by the Dashboard screen)
        wp_enqueue_style(
            'allaccessible-admin',
            AACB_URL . 'assets/admin.css',
            array(),
            AACB_VERSION
        );

        // v2 design system — Account & Settings screen, future screens
        wp_enqueue_style(
            'aacx-v2-admin',
            AACB_CSS . 'admin-v2.css',
            array(),
            aacb_asset_ver('admin-v2.css')
        );

        // Suppress noisy core notices on our own screens
        $custom_css = "
            .notice, .updated, .error, .update-nag,
            div.notice, div.updated, div.error, div.update-nag {
                display: none !important;
            }
        ";
        wp_add_inline_style('allaccessible-admin', $custom_css);
    }
    public function render_settings_page() {
        // Redirect to wizard if not completed
        if (!get_option('aacb_wizard_completed') && !get_option('aacb_accountID')) {
            wp_redirect(admin_url('admin.php?page=allaccessible-wizard'));
            exit;
        }

        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        remove_all_actions('user_admin_notices');
        remove_all_actions('network_admin_notices');

        $account_id = get_option('aacb_accountID');
        $has_account = !empty($account_id);
        $site_url = get_bloginfo('wpurl');

        $account_tier = 'unknown';
        $is_paid = false;
        $exceeded_limits = array();
        $usage_summary = null;
        $audit_data = null;
        $site_options = null;
        $addon_url = 'https://app.allaccessible.org/billing';

        if ($has_account) {
            $api_client = AllAccessible_ApiClient::get_instance();

            $site_options    = $api_client->get_site_options();
            $account_tier    = $api_client->get_subscription_tier();
            $is_paid         = $api_client->is_paid_account();
            $exceeded_limits = $api_client->get_exceeded_limits();
            $usage_summary   = $api_client->get_usage_summary();
            $addon_url       = $api_client->get_addon_url();
            $audits_url      = $api_client->get_audits_url();
            $widget_settings_url = $api_client->get_widget_settings_url();
            $audit_data = null;
            $bulk_for_count = $api_client->get_pages_audit_bulk(
                false,
                array(),
                array('tasks', 'aggregation', 'pageviews', 'manifest', 'categorization')
            );
            $aacb_hero_aggregation = (is_array($bulk_for_count) && isset($bulk_for_count['aggregation']))
                ? $bulk_for_count['aggregation']
                : null;
            $aacb_hero_task_stats  = (is_array($bulk_for_count) && isset($bulk_for_count['tasks']))
                ? $bulk_for_count['tasks']
                : null;
            $aacb_hero_pageviews   = (is_array($bulk_for_count) && isset($bulk_for_count['pageviews']))
                ? $bulk_for_count['pageviews']
                : null;
            $aacb_hero_manifest    = (is_array($bulk_for_count) && isset($bulk_for_count['manifest']))
                ? $bulk_for_count['manifest']
                : null;
            $aacb_hero_categorization = (is_array($bulk_for_count) && isset($bulk_for_count['categorization']))
                ? $bulk_for_count['categorization']
                : null;

            AllAccessible_Debug::console('Dashboard (SettingsPage)', array(
                'account_tier'       => $account_tier,
                'is_paid'            => $is_paid,
                'exceeded_limits'    => $exceeded_limits,
                'site_options_type'  => is_object($site_options) ? get_class($site_options) : gettype($site_options),
                'bulk_is_error'      => is_wp_error($bulk_for_count),
                'bulk_error'         => is_wp_error($bulk_for_count) ? $bulk_for_count->get_error_message() : null,
                'bulk_keys'          => is_array($bulk_for_count) ? array_keys($bulk_for_count) : null,
                'aggregation'        => $aacb_hero_aggregation,
                'task_stats'         => $aacb_hero_task_stats,
                'pageviews'          => $aacb_hero_pageviews,
                'manifest'           => $aacb_hero_manifest,
                'categorization_keys'=> is_array($aacb_hero_categorization) ? array_keys($aacb_hero_categorization) : null,
            ));
        }

        $is_paid_tier = in_array($account_tier, array('starter', 'legacy', 'enterprise'));
        $features_list  = array();
        if (is_object($site_options) && isset($site_options->features) && is_array($site_options->features)) {
            $features_list = $site_options->features;
        } elseif (is_array($site_options) && isset($site_options['features']) && is_array($site_options['features'])) {
            $features_list = $site_options['features'];
        }
        $has_agentic    = in_array('agentic_fixes_approve', $features_list, true);
        $is_legacy_v1   = ($account_tier === 'legacy') || ($has_account && !$has_agentic && $is_paid_tier);
        $has_reports    = in_array('reports_export', $features_list, true)
                       || in_array('pdf_export',     $features_list, true);
        // Deep-link site token from /validate. Used for app-side links
        // (Reports quick action, Tasks page, etc).
        $deeplink_token = '';
        $deeplink_sub   = (int) get_option('aacb_siteID');
        if (is_object($site_options) && !empty($site_options->siteID)) {
            $deeplink_token = (string) $site_options->siteID;
        }
        $deeplink_base = ($deeplink_token && $deeplink_sub)
            ? sprintf('https://app.allaccessible.org/site/%s/%d', rawurlencode($deeplink_token), $deeplink_sub)
            : 'https://app.allaccessible.org';
        
        // legacy upgrade CTA across the plugin points at the same URL.
        // $api_client only exists inside the $has_account block above; guard so
        
        // CTAs that read $migration_url only render for legacy accounts anyway).
        $migration_url = '';
        if ($has_account && isset($api_client)) {
            $migration_url = $api_client->get_migration_url();
        }

        $show_success = isset($_GET['wizard']) && $_GET['wizard'] === 'complete';
        ?>
        <?php
        // Hero stat data shared by the header + the 4-up hero grid below.
        $extras_hero = array();
        if ($has_account) {
            $site_score = null;
            $remediation_score = null;
            $is_live_estimate = false;
            if (isset($aacb_hero_aggregation) && is_array($aacb_hero_aggregation) && !empty($aacb_hero_aggregation['hasData'])) {
                $site_score = isset($aacb_hero_aggregation['overallScore']) ? (float) $aacb_hero_aggregation['overallScore'] : null;
                $remediation_score = isset($aacb_hero_aggregation['remediationScore']) ? (float) $aacb_hero_aggregation['remediationScore'] : null;
                $is_live_estimate = !empty($aacb_hero_aggregation['isLiveEstimate']);
            }
            $extras_hero['site_score']        = $site_score;
            $extras_hero['remediation_score'] = $remediation_score;
            $extras_hero['is_live_estimate']  = $is_live_estimate;
            $extras_hero['fixes_url']        = admin_url('admin.php?page=aacb-agentic-fixes');
            $extras_hero['site_id_external'] = $deeplink_token;
            $live_audited   = (is_array($bulk_for_count) && isset($bulk_for_count['audited_count']))
                ? (int) $bulk_for_count['audited_count']
                : null;
            if ($live_audited !== null) {
                $extras_hero['pages_scanned'] = $live_audited;
            } elseif (isset($aacb_hero_aggregation) && is_array($aacb_hero_aggregation)) {
                $extras_hero['pages_scanned'] = isset($aacb_hero_aggregation['totalPagesScanned'])
                    ? (int) $aacb_hero_aggregation['totalPagesScanned']
                    : null;
            }
            if (is_array($bulk_for_count) && isset($bulk_for_count['images']) && is_array($bulk_for_count['images'])) {
                $extras_hero['images_total']        = (int) ($bulk_for_count['images']['total']        ?? 0);
                $extras_hero['images_ai_generated'] = (int) ($bulk_for_count['images']['ai_generated'] ?? 0);
                $extras_hero['images_pending']      = (int) ($bulk_for_count['images']['pending']      ?? 0);
            }
            if (isset($aacb_hero_manifest) && is_array($aacb_hero_manifest)) {
                $cs = is_array($aacb_hero_manifest['countsByStatus'] ?? null) ? $aacb_hero_manifest['countsByStatus'] : array();
                $extras_hero['pending_fixes'] = (int) ($cs['draft']    ?? 0);
                $extras_hero['active_fixes']  = (int) ($cs['approved'] ?? 0);
            }
            if (is_array($aacb_hero_task_stats) && !empty($aacb_hero_task_stats['has_audit_data'])) {
                $extras_hero['has_task_data']         = true;
                $extras_hero['ai_resolved_count']     = (int) ($aacb_hero_task_stats['ai_resolved_count']     ?? 0);
                $extras_hero['manual_required_count'] = (int) ($aacb_hero_task_stats['manual_required_count'] ?? 0);
                $extras_hero['completed_count']       = (int) ($aacb_hero_task_stats['completed_count']       ?? 0);
                $extras_hero['reopened_count']        = (int) ($aacb_hero_task_stats['reopened_count']        ?? 0);
                $extras_hero['ignored_count']         = (int) ($aacb_hero_task_stats['ignored_count']         ?? 0);
                $extras_hero['total_actionable']      = (int) ($aacb_hero_task_stats['total_actionable']      ?? 0);
            }
            $usage_bars = array();
            $us = is_object($site_options) ? ($site_options->usageSummary ?? null) : null;
            if (is_array($aacb_hero_pageviews) && !empty($aacb_hero_pageviews['limit'])) {
                $pv_lim = (int) $aacb_hero_pageviews['limit'];
                $pv_cur = (int) ($aacb_hero_pageviews['current'] ?? 0);
                $usage_bars[] = array(
                    'label'   => __('Pageviews', 'allaccessible'),
                    'current' => $pv_cur,
                    'limit'   => $pv_lim,
                    'pct'     => (int) round(($pv_cur / max(1, $pv_lim)) * 100),
                );
            } elseif (is_object($us) && isset($us->pageviews_monthly)) {
                $node  = $us->pageviews_monthly;
                $limit = isset($node->limit) ? (int) $node->limit : 0;
                if ($limit > 0) {
                    $current = isset($node->current) ? (int) $node->current : (isset($node->used) ? (int) $node->used : 0);
                    $pct     = isset($node->percent) ? (int) round((float) $node->percent) : (int) round(($current / max(1, $limit)) * 100);
                    $usage_bars[] = array(
                        'label'   => __('Pageviews', 'allaccessible'),
                        'current' => $current,
                        'limit'   => $limit,
                        'pct'     => $pct,
                    );
                }
            }
            $ai_quota = is_object($site_options) ? ($site_options->aiQuota ?? null) : null;
            $ai_lifetime = is_object($site_options) ? ($site_options->aiInteractionsLifetime ?? null) : null;
            $extras_hero['ai_quota'] = $ai_quota;
            $extras_hero['ai_interactions_lifetime'] = $ai_lifetime;
            $extras_hero['usage_bars'] = $usage_bars;
        }
        $fixes_pending_count = $extras_hero['pending_fixes'] ?? 0;
        $fixes_active_count  = $extras_hero['active_fixes']  ?? 0;
        $image_slug = (class_exists('ImageManagerPage') && defined('ImageManagerPage::PAGE_SLUG'))
            ? ImageManagerPage::PAGE_SLUG
            : 'aacb-image-manager';
        ?>
        <div class="allaccessible-admin" style="margin: -10px 0 0 -20px;">
        <div class="aacx-v2" style="background: var(--aacx-bg); min-height: 100vh;">
        <div class="aacx-v2__page">

            <!-- Page header -->
            <header class="aacx-v2__page-header">
                <div>
                    <div class="aacx-v2__page-eyebrow"><?php esc_html_e('Site overview', 'allaccessible'); ?></div>
                    <div style="display: flex; gap: var(--aacx-space-3); align-items: center; flex-wrap: wrap;">
                        <h1 style="margin: 0;"><?php esc_html_e('AllAccessible', 'allaccessible'); ?></h1>
                        <?php if ($has_account):
                            $tier_meta = self::tier_meta($account_tier);
                            $tier_variant = isset($tier_meta['v2_variant']) ? $tier_meta['v2_variant'] : 'primary';
                            ?>
                            <span class="aacx-v2__badge aacx-v2__badge--<?php echo esc_attr($tier_variant); ?>"><?php echo esc_html($tier_meta['label']); ?></span>
                            <?php if ($site_options && !is_wp_error($site_options)) : ?>
                                <span class="aacx-v2__badge aacx-v2__badge--ok"><?php esc_html_e('Widget connected', 'allaccessible'); ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <p class="aacx-v2__page-desc"><?php esc_html_e('Accessibility widget and human-in-the-loop agentic AI remediation for your site.', 'allaccessible'); ?></p>
                </div>
                <div style="display: flex; gap: var(--aacx-space-2); flex-shrink: 0;">
                    <a href="<?php echo esc_url($site_url . '/?aacb_preview=true'); ?>" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--secondary"><?php esc_html_e('Preview widget', 'allaccessible'); ?></a>
                    <?php if ($has_account): ?>
                        <a href="<?php echo esc_url($deeplink_base); ?>" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--primary"><?php esc_html_e('Open dashboard', 'allaccessible'); ?> →</a>
                    <?php endif; ?>
                </div>
            </header>

            <?php if ($has_account) : ?>
                <?php
                $aacb_schedule = $api_client->get_scan_schedule();
                aacb_render_overview_hero($extras_hero, array(
                    'has_agentic'   => $has_agentic,
                    'fixes_url'     => isset($extras_hero['fixes_url']) ? $extras_hero['fixes_url'] : admin_url('admin.php?page=aacb-agentic-fixes'),
                    'issues_anchor' => '#aacb-overview-issues',
                    // "View pages" → the native WordPress Pages list (stays in
                    // WP, no app bounce).
                    'pages_url'     => admin_url('edit.php?post_type=page'),
                    'schedule'      => is_wp_error($aacb_schedule) ? null : $aacb_schedule,
                ));
                ?>

                <?php if ($is_legacy_v1) : ?>
                    <!-- Legacy V1 widget — Agentic Fixes is not available on
                         this plan. Surface a prominent upgrade prompt above
                         the AI/Quick-actions row so customers can self-serve
                         the upgrade. Plan card on Account also nudges. -->
                    <div class="aacx-v2__banner aacx-v2__banner--ai" style="margin-bottom: var(--aacx-space-6);">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: var(--aacx-space-2); margin-bottom: var(--aacx-space-1);">
                                <span class="aacx-v2__ai-badge"><?php esc_html_e('AllAccessible AI', 'allaccessible'); ?></span>
                                <strong><?php esc_html_e('Upgrade to unlock Agentic Fixes', 'allaccessible'); ?></strong>
                            </div>
                            <p style="font-size: var(--aacx-text-sm); margin: 0;">
                                <?php esc_html_e('Your current plan uses the legacy AllAccessible widget, which predates agentic AI remediation. Upgrade to the current plan tier to unlock auto-suggested fixes, larger pageview caps, and the full AllAccessible AI feature set.', 'allaccessible'); ?>
                            </p>
                        </div>
                        <a href="<?php echo esc_url($migration_url); ?>" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--primary" style="flex-shrink: 0;">
                            <?php esc_html_e('Upgrade plan', 'allaccessible'); ?>
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                <?php endif; ?>

                <?php
                $categorization_raw = $is_legacy_v1 ? null : $aacb_hero_categorization;
                $categorization     = (is_array($categorization_raw) && !empty($categorization_raw['has_audit_data']))
                    ? $categorization_raw : null;
                $top_rules = array();
                if ($categorization && !empty($categorization['rules'])) {
                    $top_rules = array_slice($categorization['rules'], 0, 10);
                }
                // PHP 7.4-compatible (no match expression).
                $severity_variant = static function ($s) {
                    $map = array(
                        'critical' => 'danger', 'serious' => 'warn',
                        'moderate' => 'primary', 'minor' => 'ok',
                    );
                    $key = strtolower((string) $s);
                    return isset($map[$key]) ? $map[$key] : 'primary';
                };
                $status_label = static function ($st) {
                    $map = array(
                        'ai_resolved'     => __('AI Resolved', 'allaccessible'),
                        'manual_required' => __('Manual', 'allaccessible'),
                        'completed'       => __('Completed', 'allaccessible'),
                        'reopened'        => __('Reopened', 'allaccessible'),
                        'ignored'         => __('Ignored', 'allaccessible'),
                    );
                    return isset($map[$st])
                        ? $map[$st]
                        : ucfirst(str_replace('_', ' ', (string) $st));
                };
                ?>

                <!-- 2/3 + 1/3 split: Recent rules table (or legacy upgrade card) + Quick actions rail -->
                <section id="aacb-overview-issues" style="display: grid; gap: var(--aacx-space-6); grid-template-columns: 2fr 1fr; margin-bottom: var(--aacx-space-8); align-items: start; scroll-margin-top: var(--aacx-space-8);" aria-label="<?php esc_attr_e('Activity and quick actions', 'allaccessible'); ?>">

                    <?php if ($is_legacy_v1) : ?>
                        <!-- LEFT (2/3): Legacy upgrade card -->
                        <div class="aacx-v2__card aacx-v2__card--elevated">
                            <div class="aacx-v2__card-header">
                                <div>
                                    <span class="aacx-v2__badge aacx-v2__badge--primary" style="margin-bottom: var(--aacx-space-2);">
                                        <?php esc_html_e('Legacy widget', 'allaccessible'); ?>
                                    </span>
                                    <h3 style="margin-top: var(--aacx-space-1);"><?php esc_html_e('Agentic Fixes not available on your plan', 'allaccessible'); ?></h3>
                                </div>
                            </div>
                            <div class="aacx-v2__card-body">
                                <p style="color: var(--aacx-text); margin-bottom: var(--aacx-space-4);">
                                    <?php esc_html_e('The legacy AllAccessible widget keeps running, but the agentic AI remediation pipeline requires a current plan tier. Upgrade to bring auto-suggested fixes, larger pageview caps, and continuous accessibility scoring to this site.', 'allaccessible'); ?>
                                </p>
                                <a href="<?php echo esc_url($migration_url); ?>" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--primary">
                                    <?php esc_html_e('See upgrade options', 'allaccessible'); ?>
                                    <span aria-hidden="true">→</span>
                                </a>
                            </div>
                        </div>
                    <?php elseif (!empty($top_rules)) : ?>
                        <div class="aacx-v2__card aacx-v2__card--elevated" id="aacb-rules-detail">
                            <div class="aacx-v2__card-header" style="flex-wrap: wrap; gap: var(--aacx-space-3);">
                                <div>
                                    <span class="aacx-v2__ai-badge" style="margin-bottom: var(--aacx-space-2); display: inline-flex;">
                                        <?php esc_html_e('AllAccessible AI', 'allaccessible'); ?>
                                    </span>
                                    <h3 style="margin: var(--aacx-space-1) 0 0;"><?php esc_html_e('Issues from your latest scan', 'allaccessible'); ?></h3>
                                    <p style="font-size: var(--aacx-text-sm); color: var(--aacx-text-muted); margin-top: var(--aacx-space-1);">
                                        <?php esc_html_e('Grouped by rule, newest scan first.', 'allaccessible'); ?>
                                    </p>
                                </div>
                                <div style="display: flex; gap: var(--aacx-space-2); flex-shrink: 0;">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=aacb-agentic-fixes')); ?>" class="aacx-v2__btn aacx-v2__btn--ai aacx-v2__btn--sm">
                                        <?php esc_html_e('Agentic Fixes', 'allaccessible'); ?>
                                        <span aria-hidden="true">→</span>
                                    </a>
                                    <a href="<?php echo esc_url($deeplink_base . '/tasks'); ?>" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--secondary aacx-v2__btn--sm">
                                        <?php esc_html_e('View all', 'allaccessible'); ?>
                                        <span aria-hidden="true">↗</span>
                                    </a>
                                </div>
                            </div>
                            <div class="aacx-v2__card-body" style="padding: 0;">
                                <table class="aacx-v2__table aacb-rules-table" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th style="width: 110px;"><?php esc_html_e('Severity', 'allaccessible'); ?></th>
                                            <th><?php esc_html_e('Rule', 'allaccessible'); ?></th>
                                            <th style="width: 80px; text-align: right;"><?php esc_html_e('Issues', 'allaccessible'); ?></th>
                                            <th style="width: 90px;"><?php esc_html_e('WCAG', 'allaccessible'); ?></th>
                                            <th style="width: 140px;"><?php esc_html_e('Status', 'allaccessible'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_rules as $rule) :
                                            $sev   = (string) ($rule['severity']         ?? 'minor');
                                            $name  = (string) ($rule['rule_name']        ?? ($rule['rule_id'] ?? ''));
                                            $count = (int)    ($rule['violations_count'] ?? 0);
                                            $wcag  = (string) ($rule['wcag_label']       ?? '');
                                            $st    = (string) ($rule['remediation_status'] ?? ($rule['remediation_type'] ?? ''));
                                        ?>
                                            <tr>
                                                <td><span class="aacx-v2__badge aacx-v2__badge--<?php echo esc_attr($severity_variant($sev)); ?>" style="white-space: nowrap;"><?php echo esc_html(ucfirst($sev)); ?></span></td>
                                                <td style="color: var(--aacx-text-strong); font-weight: var(--aacx-weight-medium);"><?php echo esc_html($name); ?></td>
                                                <td style="font-variant-numeric: tabular-nums; color: var(--aacx-text-muted); text-align: right; padding-right: var(--aacx-space-4);"><?php echo esc_html(number_format_i18n($count)); ?></td>
                                                <td>
                                                    <?php if ($wcag !== '') : ?>
                                                        <span class="aacx-v2__badge" style="white-space: nowrap;" title="<?php echo esc_attr('WCAG ' . $wcag); ?>"><?php echo esc_html($wcag); ?></span>
                                                    <?php else : ?>
                                                        <span style="color: var(--aacx-text-muted);">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="aacx-v2__badge" style="white-space: nowrap;"><?php echo esc_html($status_label($st)); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <style>
                                    .aacb-rules-table th,
                                    .aacb-rules-table td {
                                        padding: var(--aacx-space-3) var(--aacx-space-4);
                                        vertical-align: middle;
                                    }
                                    .aacb-rules-table th {
                                        padding-top: var(--aacx-space-3);
                                        padding-bottom: var(--aacx-space-3);
                                    }
                                </style>
                            </div>
                            <?php if ($fixes_pending_count > 0) : ?>
                                <div class="aacx-v2__card-footer">
                                    <p style="font-size: var(--aacx-text-sm); color: var(--aacx-text); margin: 0;">
                                        <strong>
                                            <?php echo esc_html(sprintf(_n('%s fix manifest ready for your review.', '%s fix manifests ready for your review.', $fixes_pending_count, 'allaccessible'), number_format_i18n($fixes_pending_count))); ?>
                                        </strong>
                                    </p>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=aacb-agentic-fixes')); ?>" class="aacx-v2__btn aacx-v2__btn--ai aacx-v2__btn--sm">
                                        <?php esc_html_e('Review manifests', 'allaccessible'); ?>
                                        <span aria-hidden="true">→</span>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else : ?>
                        <!-- LEFT (2/3): Empty state — no audits yet OR endpoint down -->
                        <div class="aacx-v2__card aacx-v2__card--ai aacx-v2__card--elevated">
                            <div class="aacx-v2__card-header">
                                <div>
                                    <span class="aacx-v2__ai-badge" style="margin-bottom: var(--aacx-space-2); display: inline-flex;"><?php esc_html_e('AllAccessible AI', 'allaccessible'); ?></span>
                                    <h3 style="margin-top: var(--aacx-space-1);"><?php esc_html_e('Awaiting first scan', 'allaccessible'); ?></h3>
                                </div>
                            </div>
                            <div class="aacx-v2__card-body">
                                <p style="color: var(--aacx-text); margin-bottom: var(--aacx-space-4);">
                                    <?php esc_html_e('AllAccessible agents are getting ready to scan your site. Detected accessibility issues will appear here grouped by rule once your first crawl completes.', 'allaccessible'); ?>
                                </p>
                                <a href="<?php echo esc_url($deeplink_base . '/coverage'); ?>" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--ai">
                                    <?php esc_html_e('Open coverage', 'allaccessible'); ?>
                                    <span aria-hidden="true">↗</span>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- RIGHT (1/3): Quick actions rail — chunkier, two groups. -->
                    <div class="aacx-v2__card aacx-v2__card--elevated">
                        <div class="aacx-v2__card-header">
                            <h3 style="margin: 0;"><?php esc_html_e('Quick actions', 'allaccessible'); ?></h3>
                        </div>
                        <div class="aacx-v2__card-body" style="display: flex; flex-direction: column; gap: var(--aacx-space-4);">

                            <!-- In-plugin pages -->
                            <div>
                                <p class="aacx-v2__page-eyebrow" style="margin-bottom: var(--aacx-space-2);"><?php esc_html_e('In WordPress', 'allaccessible'); ?></p>
                                <div style="display: flex; flex-direction: column; gap: var(--aacx-space-2);">
                                    <?php if (!$is_legacy_v1) : ?>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=aacb-agentic-fixes')); ?>" class="aacx-v2__btn aacx-v2__btn--ai" style="justify-content: space-between;">
                                            <span><?php esc_html_e('Agentic Fixes', 'allaccessible'); ?></span>
                                            <span aria-hidden="true">→</span>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=' . $image_slug)); ?>" class="aacx-v2__btn aacx-v2__btn--secondary" style="justify-content: space-between;">
                                        <span><?php esc_html_e('Image AI', 'allaccessible'); ?></span>
                                        <span aria-hidden="true">→</span>
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=allaccessible-widget')); ?>" class="aacx-v2__btn aacx-v2__btn--secondary" style="justify-content: space-between;">
                                        <span><?php esc_html_e('Widget customizer', 'allaccessible'); ?></span>
                                        <span aria-hidden="true">→</span>
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=allaccessible-account')); ?>" class="aacx-v2__btn aacx-v2__btn--secondary" style="justify-content: space-between;">
                                        <span><?php esc_html_e('Account & settings', 'allaccessible'); ?></span>
                                        <span aria-hidden="true">→</span>
                                    </a>
                                </div>
                            </div>

                            <!-- App deep-links — granular surfaces the plugin doesn't host. -->
                            <div style="padding-top: var(--aacx-space-3); border-top: 1px solid var(--aacx-border);">
                                <p class="aacx-v2__page-eyebrow" style="margin-bottom: var(--aacx-space-2);"><?php esc_html_e('On AllAccessible', 'allaccessible'); ?></p>
                                <div style="display: flex; flex-direction: column; gap: var(--aacx-space-2);">
                                    <a href="<?php echo esc_url($deeplink_base); ?>" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--ghost" style="justify-content: space-between;">
                                        <span><?php esc_html_e('Site overview', 'allaccessible'); ?></span>
                                        <span aria-hidden="true">↗</span>
                                    </a>
                                    <a href="<?php echo esc_url($deeplink_base . '/accessibility-audits'); ?>" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--ghost" style="justify-content: space-between;">
                                        <span><?php esc_html_e('Audit history', 'allaccessible'); ?></span>
                                        <span aria-hidden="true">↗</span>
                                    </a>
                                    <a href="<?php echo esc_url($deeplink_base . '/tasks'); ?>" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--ghost" style="justify-content: space-between;">
                                        <span><?php esc_html_e('Tasks', 'allaccessible'); ?></span>
                                        <span aria-hidden="true">↗</span>
                                    </a>
                                    <?php if ($has_reports) : ?>
                                        <a href="<?php echo esc_url($deeplink_base . '/reports'); ?>" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--ghost" style="justify-content: space-between;">
                                            <span><?php esc_html_e('Reports', 'allaccessible'); ?></span>
                                            <span aria-hidden="true">↗</span>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?php echo esc_url($site_url . '/?aacb_preview=true'); ?>" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--ghost" style="justify-content: space-between;">
                                        <span><?php esc_html_e('Preview widget', 'allaccessible'); ?></span>
                                        <span aria-hidden="true">↗</span>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>

                </section>

            <?php endif; ?>

            <?php if ($has_account && !empty($exceeded_limits)): ?>
                <div class="aacx-v2__banner aacx-v2__banner--warn" style="margin-bottom: var(--aacx-space-6);">
                    <div style="flex: 1;">
                        <strong><?php esc_html_e('Usage limit exceeded', 'allaccessible'); ?></strong>
                        <p style="margin-top: var(--aacx-space-1);">
                            <?php
                            $limit_names = array_map(function ($limit) {
                                return str_replace('_', ' ', ucwords($limit, '_'));
                            }, $exceeded_limits);
                            printf(
                                _n(
                                    'You have exceeded your %s limit. Your widget has been temporarily downgraded to free features. Upgrade your plan to restore premium functionality.',
                                    'You have exceeded the following limits: %s. Your widget has been temporarily downgraded to free features. Upgrade your plan to restore premium functionality.',
                                    count($exceeded_limits),
                                    'allaccessible'
                                ),
                                '<strong>' . implode(', ', $limit_names) . '</strong>'
                            );
                            ?>
                        </p>
                    </div>
                    <a href="<?php echo esc_url($addon_url); ?>" target="_blank" class="aacx-v2__btn aacx-v2__btn--primary aacx-v2__btn--sm" style="flex-shrink: 0;"><?php esc_html_e('Upgrade plan', 'allaccessible'); ?></a>
                </div>
            <?php endif; ?>

            <?php /* Widget Customizer is now its own submenu page (allaccessible-widget). */ ?>

            <?php if ($has_account && $account_tier === 'free'): ?>
                <?php aacb_render_feature_comparison($addon_url); ?>
            <?php endif; ?>

            <?php if ($has_account && $site_options && !is_wp_error($site_options)): ?>
                <?php aacb_render_smart_cta($site_options, $account_tier, $addon_url); ?>
            <?php endif; ?>

            <?php if (!$has_account): ?>
                <div class="aacx-v2__card aacx-v2__card--elevated">
                    <div class="aacx-v2__card-body">
                        <div class="aacx-v2__empty">
                            <p class="aacx-v2__empty-title"><?php esc_html_e('Complete setup to get started', 'allaccessible'); ?></p>
                            <p><?php esc_html_e('Your accessibility widget is ready to go. Complete the quick setup wizard to activate your widget.', 'allaccessible'); ?></p>
                            <p style="margin-top: var(--aacx-space-4);">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=allaccessible-wizard')); ?>" class="aacx-v2__btn aacx-v2__btn--primary aacx-v2__btn--lg"><?php esc_html_e('Start setup wizard', 'allaccessible'); ?> →</a>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
        </div>
        </div>
        <?php
    }

    /* =====================================================================
     * Account & Settings
     * ===================================================================== */
    public function render_account_page() {
        if (!get_option('aacb_wizard_completed') && !get_option('aacb_accountID')) {
            wp_redirect(admin_url('admin.php?page=allaccessible-wizard'));
            exit;
        }

        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        remove_all_actions('user_admin_notices');
        remove_all_actions('network_admin_notices');

        $account_id  = (string) get_option('aacb_accountID');
        $site_id     = (string) get_option('aacb_siteID');
        $has_account = !empty($account_id);

        $account_tier    = 'unknown';
        $is_paid_tier    = false;
        $is_developer    = false;
        $usage_summary   = null;
        $addon_url       = 'https://app.allaccessible.org/billing';
        $billing_url     = 'https://app.allaccessible.org/billing';
        $widget_url      = 'https://app.allaccessible.org';
        $exceeded_limits = array();

        if ($has_account) {
            $api_client      = AllAccessible_ApiClient::get_instance();
            $account_tier    = $api_client->get_subscription_tier();
            $usage_summary   = $api_client->get_usage_summary();
            $addon_url       = $api_client->get_addon_url();
            $billing_url     = method_exists($api_client, 'get_billing_url')
                ? $api_client->get_billing_url()
                : 'https://app.allaccessible.org/billing';
            $widget_url      = $api_client->get_widget_settings_url();
            $exceeded_limits = $api_client->get_exceeded_limits();
            $is_paid_tier    = in_array($account_tier, array('starter', 'legacy', 'enterprise', 'trial'), true);
            $is_developer    = in_array($account_tier, array('enterprise', 'legacy'), true);

            if (method_exists($api_client, 'get_site_id')) {
                $fresh_site_id = $api_client->get_site_id();
                if (!empty($fresh_site_id)) { $site_id = (string) $fresh_site_id; }
            }
        }

        // Quick toggles — stored inside the existing aacb_options array so
        // we never introduce a new option key.
        $aacb_options    = (array) get_option('aacb_options', array());
        $headless_mode   = !empty($aacb_options['headless_mode']);
        $remove_branding = !empty($aacb_options['remove_branding']);
        $debug_mode      = !empty($aacb_options['debug_mode']);
        AllAccessible_Debug::console('Account (SettingsPage)', array(
            'has_account'      => $has_account,
            'account_id'       => $account_id,
            'site_id'          => $site_id,
            'tier'             => $account_tier,
            'is_paid'          => $is_paid_tier,
            'is_developer'     => $is_developer,
            'exceeded_limits'  => $exceeded_limits,
            'addon_url'        => $addon_url,
            'billing_url'      => $billing_url,
            'widget_url'       => $widget_url,
            'toggles'          => array(
                'headless_mode'   => $headless_mode,
                'remove_branding' => $remove_branding,
                'debug_mode'      => $debug_mode,
            ),
            'usage_summary'    => $usage_summary,
        ));
        $pv_used = null; $pv_limit = null; $pv_pct = null;
        $pv_node = null;
        if (is_object($usage_summary) && isset($usage_summary->pageviews_monthly)) {
            $pv_node = $usage_summary->pageviews_monthly;
            $pv_used  = isset($pv_node->current) ? (int) $pv_node->current
                      : (isset($pv_node->used) ? (int) $pv_node->used : null);
            $pv_limit = isset($pv_node->limit) ? (int) $pv_node->limit : null;
            $pv_pct   = isset($pv_node->percent) ? (int) round((float) $pv_node->percent)
                      : (($pv_limit && $pv_used !== null) ? min(100, (int) round(($pv_used / max(1, $pv_limit)) * 100)) : null);
        } elseif (is_array($usage_summary) && isset($usage_summary['pageviews_monthly'])) {
            $pv_node = $usage_summary['pageviews_monthly'];
            $pv_used  = isset($pv_node['current']) ? (int) $pv_node['current']
                      : (isset($pv_node['used']) ? (int) $pv_node['used'] : null);
            $pv_limit = isset($pv_node['limit']) ? (int) $pv_node['limit'] : null;
            $pv_pct   = isset($pv_node['percent']) ? (int) round((float) $pv_node['percent'])
                      : (($pv_limit && $pv_used !== null) ? min(100, (int) round(($pv_used / max(1, $pv_limit)) * 100)) : null);
        }
        if ($pv_pct === null) { $pv_pct = 0; }
        $pv_badge_variant = $pv_pct >= 90 ? 'danger' : ($pv_pct >= 75 ? 'warn' : 'ok');

        $tier_meta = self::tier_meta($account_tier);

        // Nonces (preserve existing names; the toggles nonce is additive)
        $reset_nonce   = wp_create_nonce('aacb_reset_plugin');
        $clear_nonce   = wp_create_nonce('aacb_clear_cache');
        $toggles_nonce = wp_create_nonce('aacb_save_quick_toggles');

        // Initial tab (query string-driven; JS handles client-side swap)
        $allowed_tabs = array('connection', 'plan', 'widget', 'advanced');
        $active_tab   = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'connection';
        if (!in_array($active_tab, $allowed_tabs, true)) { $active_tab = 'connection'; }
        ?>
        <div class="aacx-v2" style="margin: -10px 0 0 -20px; min-height: 100vh;">
            <div class="aacx-v2__page">

                <header class="aacx-v2__page-header">
                    <div>
                        <p class="aacx-v2__page-eyebrow"><?php esc_html_e('AllAccessible', 'allaccessible'); ?></p>
                        <div class="aacx-v2__page-title">
                            <h1><?php esc_html_e('Account & Settings', 'allaccessible'); ?></h1>
                            <?php if ($has_account): ?>
                                <span class="aacx-v2__badge aacx-v2__badge--<?php echo esc_attr($tier_meta['v2_variant']); ?>">
                                    <?php echo esc_html($tier_meta['label']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="aacx-v2__page-desc">
                            <?php esc_html_e('Manage your AllAccessible account, plan, widget settings, and integrations.', 'allaccessible'); ?>
                        </p>
                    </div>
                    <?php if ($has_account): ?>
                        <a href="https://app.allaccessible.org" target="_blank" rel="noopener"
                           class="aacx-v2__btn aacx-v2__btn--secondary">
                            <?php esc_html_e('Open full dashboard', 'allaccessible'); ?>
                            <span aria-hidden="true">→</span>
                        </a>
                    <?php endif; ?>
                </header>

                <?php if (!$has_account): ?>
                    <div class="aacx-v2__card aacx-v2__card--elevated">
                        <div class="aacx-v2__empty">
                            <p class="aacx-v2__empty-title"><?php esc_html_e('No AllAccessible account linked', 'allaccessible'); ?></p>
                            <p><?php esc_html_e('Complete the setup wizard to activate your accessibility widget.', 'allaccessible'); ?></p>
                            <p style="margin-top: var(--aacx-space-5);">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=allaccessible-wizard')); ?>"
                                   class="aacx-v2__btn aacx-v2__btn--primary">
                                    <?php esc_html_e('Start setup', 'allaccessible'); ?>
                                    <span aria-hidden="true">→</span>
                                </a>
                            </p>
                        </div>
                    </div>
                <?php else: ?>

                <?php /* Live region for save / clear feedback announcements */ ?>
                <div id="aacb-v2-live" class="screen-reader-text" aria-live="polite" aria-atomic="true"></div>

                <?php if (!empty($exceeded_limits)): ?>
                    <div class="aacx-v2__banner aacx-v2__banner--warn" role="status" style="margin-bottom: var(--aacx-space-6);">
                        <div>
                            <strong><?php esc_html_e('Usage limit reached', 'allaccessible'); ?></strong>
                            <p style="margin-top: var(--aacx-space-1);">
                                <?php esc_html_e('Your widget is running in free mode until usage resets or your plan is upgraded.', 'allaccessible'); ?>
                            </p>
                        </div>
                        <a href="<?php echo esc_url($addon_url); ?>" target="_blank" rel="noopener"
                           class="aacx-v2__btn aacx-v2__btn--primary aacx-v2__btn--sm" style="margin-left: auto;">
                            <?php esc_html_e('Manage plan', 'allaccessible'); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <div role="tablist" aria-label="<?php esc_attr_e('Account settings sections', 'allaccessible'); ?>" class="aacx-v2__tabs">
                    <?php
                    $tabs = array(
                        'connection' => __('Connection', 'allaccessible'),
                        'plan'       => __('Plan & Usage', 'allaccessible'),
                        'widget'     => __('Widget', 'allaccessible'),
                        'advanced'   => __('Advanced', 'allaccessible'),
                    );
                    foreach ($tabs as $tab_id => $tab_label) :
                        $is_active = ($tab_id === $active_tab);
                    ?>
                        <button type="button"
                                role="tab"
                                id="aacb-tab-<?php echo esc_attr($tab_id); ?>"
                                class="aacx-v2__tab"
                                aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                                aria-controls="aacb-panel-<?php echo esc_attr($tab_id); ?>"
                                tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
                                data-aacb-tab="<?php echo esc_attr($tab_id); ?>">
                            <?php echo esc_html($tab_label); ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- ── Connection tab ─────────────────────────────────── -->
                <section id="aacb-panel-connection"
                         role="tabpanel"
                         aria-labelledby="aacb-tab-connection"
                         class="aacx-v2__stack--lg"
                         <?php echo $active_tab === 'connection' ? '' : 'hidden'; ?>>
                    <div class="aacx-v2__card aacx-v2__card--elevated">
                        <div class="aacx-v2__card-header">
                            <div>
                                <h2><?php esc_html_e('Connection', 'allaccessible'); ?></h2>
                                <p class="aacx-v2__help" style="margin-top: var(--aacx-space-1);">
                                    <?php esc_html_e('How this site links to your AllAccessible account.', 'allaccessible'); ?>
                                </p>
                            </div>
                            <?php if (empty($exceeded_limits)) : ?>
                                <span class="aacx-v2__badge aacx-v2__badge--ok"><?php esc_html_e('Connected', 'allaccessible'); ?></span>
                            <?php else : ?>
                                <span class="aacx-v2__badge aacx-v2__badge--warn"><?php esc_html_e('Action required', 'allaccessible'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="aacx-v2__card-body aacx-v2__stack">
                            <div class="aacx-v2__field">
                                <label class="aacx-v2__label" for="aacb-account-id-display"><?php esc_html_e('Account ID', 'allaccessible'); ?></label>
                                <div class="aacx-v2__row">
                                    <code class="aacx-v2__id" id="aacb-account-id-display" aria-describedby="aacb-account-id-help">
                                        <?php echo esc_html($account_id); ?>
                                    </code>
                                    <button type="button"
                                            class="aacx-v2__btn aacx-v2__btn--secondary aacx-v2__btn--sm"
                                            data-aacb-copy="<?php echo esc_attr($account_id); ?>"
                                            aria-label="<?php esc_attr_e('Copy account ID', 'allaccessible'); ?>">
                                        <?php esc_html_e('Copy', 'allaccessible'); ?>
                                    </button>
                                </div>
                                <p class="aacx-v2__help" id="aacb-account-id-help">
                                    <?php esc_html_e('Identifies your AllAccessible account across every site you own.', 'allaccessible'); ?>
                                </p>
                            </div>

                            <?php if (!empty($site_id)) : ?>
                            <div class="aacx-v2__field">
                                <label class="aacx-v2__label" for="aacb-site-id-display"><?php esc_html_e('Site ID', 'allaccessible'); ?></label>
                                <div class="aacx-v2__row">
                                    <code class="aacx-v2__id" id="aacb-site-id-display" aria-describedby="aacb-site-id-help">
                                        <?php echo esc_html($site_id); ?>
                                    </code>
                                    <button type="button"
                                            class="aacx-v2__btn aacx-v2__btn--secondary aacx-v2__btn--sm"
                                            data-aacb-copy="<?php echo esc_attr($site_id); ?>"
                                            aria-label="<?php esc_attr_e('Copy site ID', 'allaccessible'); ?>">
                                        <?php esc_html_e('Copy', 'allaccessible'); ?>
                                    </button>
                                </div>
                                <p class="aacx-v2__help" id="aacb-site-id-help">
                                    <?php esc_html_e('Identifies this specific WordPress site within your account.', 'allaccessible'); ?>
                                </p>
                            </div>
                            <?php endif; ?>

                            <div class="aacx-v2__field">
                                <span class="aacx-v2__label"><?php esc_html_e('Site URL', 'allaccessible'); ?></span>
                                <code class="aacx-v2__id">
                                    <?php echo esc_html(get_bloginfo('wpurl')); ?>
                                </code>
                            </div>
                        </div>

                        <?php
                        // Defer to the existing connection status component
                        // for host / subdomain mismatch detection.
                        if (class_exists('AllAccessible_ConnectionStatusCard')) {
                            echo '<div class="aacx-v2__card-body" style="border-top: 1px solid var(--aacx-border);">';
                            AllAccessible_ConnectionStatusCard::render();
                            echo '</div>';
                        }
                        ?>
                    </div>
                </section>

                <!-- ── Plan & Usage tab ───────────────────────────────── -->
                <section id="aacb-panel-plan"
                         role="tabpanel"
                         aria-labelledby="aacb-tab-plan"
                         class="aacx-v2__stack--lg"
                         <?php echo $active_tab === 'plan' ? '' : 'hidden'; ?>>

                    <?php
                        $card_class = $is_paid_tier ? 'aacx-v2__card aacx-v2__card--featured' : 'aacx-v2__card aacx-v2__card--elevated';
                        $upgrade_label = $is_paid_tier
                            ? __('Manage subscription', 'allaccessible')
                            : __('Upgrade plan', 'allaccessible');
                    ?>

                    <div class="<?php echo esc_attr($card_class); ?>">
                        <div class="aacx-v2__card-header">
                            <div>
                                <p class="aacx-v2__page-eyebrow" style="margin-bottom: var(--aacx-space-1);">
                                    <?php esc_html_e('Current plan', 'allaccessible'); ?>
                                </p>
                                <h3><?php echo esc_html($tier_meta['long_label']); ?></h3>
                            </div>
                            <span class="aacx-v2__badge aacx-v2__badge--<?php echo esc_attr($tier_meta['v2_variant']); ?>">
                                <?php echo esc_html($tier_meta['label']); ?>
                            </span>
                        </div>
                        <div class="aacx-v2__card-body">
                            <ul style="list-style: none; padding: 0; margin: 0; display: grid; gap: var(--aacx-space-3);">
                                <?php foreach (self::tier_benefits($account_tier) as $benefit): ?>
                                    <li style="display: flex; align-items: flex-start; gap: var(--aacx-space-3);">
                                        <svg aria-hidden="true" focusable="false" width="20" height="20" viewBox="0 0 20 20" fill="none" style="flex-shrink: 0; margin-top: 2px; color: var(--aacx-ok-600);">
                                            <path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <span style="font-size: var(--aacx-text-sm); color: var(--aacx-text);"><?php echo wp_kses_post($benefit); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="aacx-v2__card-footer">
                            <p style="font-size: var(--aacx-text-xs); color: var(--aacx-text-muted); margin: 0;">
                                <?php esc_html_e('Plan changes update immediately across every site on this account.', 'allaccessible'); ?>
                            </p>
                            <a href="<?php echo esc_url($is_paid_tier ? $billing_url : $addon_url); ?>"
                               target="_blank" rel="noopener"
                               class="aacx-v2__btn aacx-v2__btn--primary">
                                <?php echo esc_html($upgrade_label); ?>
                                <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    </div>

                    <div class="aacx-v2__card aacx-v2__card--elevated">
                        <div class="aacx-v2__card-header">
                            <div>
                                <h3><?php esc_html_e('Pageview usage', 'allaccessible'); ?></h3>
                                <p class="aacx-v2__help" style="margin-top: var(--aacx-space-1);">
                                    <?php esc_html_e('Resets at the start of each billing cycle.', 'allaccessible'); ?>
                                </p>
                            </div>
                            <?php if ($pv_limit && $pv_used !== null): ?>
                                <span class="aacx-v2__badge aacx-v2__badge--<?php echo esc_attr($pv_badge_variant); ?>">
                                    <?php echo esc_html($pv_pct); ?>%
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="aacx-v2__card-body">
                            <?php if ($pv_limit && $pv_used !== null): ?>
                                <?php
                                    $bar_color = $pv_pct >= 90 ? 'var(--aacx-danger-500)'
                                               : ($pv_pct >= 75 ? 'var(--aacx-warn-500)'
                                               : 'var(--aacx-ok-500)');
                                ?>
                                <div role="progressbar"
                                     aria-valuenow="<?php echo esc_attr($pv_pct); ?>"
                                     aria-valuemin="0"
                                     aria-valuemax="100"
                                     aria-label="<?php esc_attr_e('Pageviews used this cycle', 'allaccessible'); ?>"
                                     class="aacx-v2__usage-bar">
                                    <div class="aacx-v2__usage-fill" style="width: <?php echo esc_attr($pv_pct); ?>%; background: <?php echo esc_attr($bar_color); ?>;"></div>
                                </div>
                                <p style="margin-top: var(--aacx-space-3); font-size: var(--aacx-text-sm); color: var(--aacx-text-muted);">
                                    <?php
                                    printf(
                                        /* translators: 1: used pageviews, 2: limit */
                                        esc_html__('%1$s of %2$s pageviews this cycle.', 'allaccessible'),
                                        '<strong style="color: var(--aacx-text-strong);">' . esc_html(number_format_i18n($pv_used)) . '</strong>',
                                        '<strong style="color: var(--aacx-text-strong);">' . esc_html(number_format_i18n($pv_limit)) . '</strong>'
                                    );
                                    ?>
                                </p>
                                <?php if ($pv_pct >= 75): ?>
                                    <div class="aacx-v2__banner aacx-v2__banner--<?php echo $pv_pct >= 90 ? 'warn' : 'info'; ?>" style="margin-top: var(--aacx-space-4);">
                                        <div>
                                            <?php if ($pv_pct >= 90): ?>
                                                <?php esc_html_e('You are close to your monthly limit. Add a pageview pack to keep premium features active.', 'allaccessible'); ?>
                                            <?php else: ?>
                                                <?php esc_html_e('Heads up — you are above 75% of your monthly pageviews.', 'allaccessible'); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <p style="font-size: var(--aacx-text-sm); color: var(--aacx-text-muted);">
                                    <?php esc_html_e('Pageview tracking is active. Detailed numbers appear after the first billing cycle.', 'allaccessible'); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- ── Widget tab ─────────────────────────────────────── -->
                <section id="aacb-panel-widget"
                         role="tabpanel"
                         aria-labelledby="aacb-tab-widget"
                         class="aacx-v2__stack--lg"
                         <?php echo $active_tab === 'widget' ? '' : 'hidden'; ?>>

                    <div class="aacx-v2__card aacx-v2__card--elevated">
                        <div class="aacx-v2__card-header">
                            <div>
                                <h3><?php esc_html_e('Widget appearance', 'allaccessible'); ?></h3>
                                <p class="aacx-v2__help" style="margin-top: var(--aacx-space-1);">
                                    <?php esc_html_e('Color, position, icon, and label live in the Widget Customizer.', 'allaccessible'); ?>
                                </p>
                            </div>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=allaccessible')); ?>"
                               class="aacx-v2__btn aacx-v2__btn--secondary aacx-v2__btn--sm">
                                <?php esc_html_e('Open customizer', 'allaccessible'); ?>
                                <span aria-hidden="true">→</span>
                            </a>
                        </div>
                        <div class="aacx-v2__card-body">
                            <div class="aacx-v2__grid aacx-v2__grid--2" style="align-items: center;">
                                <div class="aacx-v2__widget-thumb" aria-hidden="true">
                                    <div class="aacx-v2__widget-thumb-bubble">
                                        <img src="<?php echo esc_url(AACB_IMG . 'bug.svg'); ?>" alt="" class="aacx-v2__widget-thumb-icon">
                                    </div>
                                </div>
                                <div class="aacx-v2__stack">
                                    <p style="font-size: var(--aacx-text-sm); color: var(--aacx-text);">
                                        <?php esc_html_e('Preview your live widget on the front-end to confirm placement and styling.', 'allaccessible'); ?>
                                    </p>
                                    <div class="aacx-v2__row">
                                        <a href="<?php echo esc_url(get_bloginfo('wpurl') . '/?aacb_preview=true'); ?>"
                                           target="_blank" rel="noopener"
                                           class="aacx-v2__btn aacx-v2__btn--primary">
                                            <?php esc_html_e('Preview on site', 'allaccessible'); ?>
                                            <span aria-hidden="true">→</span>
                                        </a>
                                        <a href="<?php echo esc_url($widget_url); ?>" target="_blank" rel="noopener"
                                           class="aacx-v2__btn aacx-v2__btn--ghost">
                                            <?php esc_html_e('Advanced settings', 'allaccessible'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form id="aacb-quick-toggles-form" class="aacx-v2__card aacx-v2__card--elevated" novalidate>
                        <div class="aacx-v2__card-header">
                            <div>
                                <h3><?php esc_html_e('Quick toggles', 'allaccessible'); ?></h3>
                                <p class="aacx-v2__help" style="margin-top: var(--aacx-space-1);">
                                    <?php esc_html_e('Local overrides applied before the widget renders.', 'allaccessible'); ?>
                                </p>
                            </div>
                        </div>
                        <div class="aacx-v2__card-body aacx-v2__stack">

                            <div class="aacx-v2__field">
                                <div class="aacx-v2__row aacx-v2__row--between" style="align-items: flex-start;">
                                    <div style="flex: 1; padding-right: var(--aacx-space-4);">
                                        <label class="aacx-v2__label" for="aacb-headless-mode" style="display: inline-flex; align-items: center; gap: var(--aacx-space-2);">
                                            <?php esc_html_e('Headless mode', 'allaccessible'); ?>
                                            <?php if ($is_developer): ?>
                                                <span class="aacx-v2__badge aacx-v2__badge--beta"><?php esc_html_e('Beta', 'allaccessible'); ?></span>
                                            <?php endif; ?>
                                        </label>
                                        <p class="aacx-v2__help" id="aacb-headless-help">
                                            <?php esc_html_e('Skip the floating button and trigger the panel from your own UI. Recommended for custom theme integrations.', 'allaccessible'); ?>
                                        </p>
                                    </div>
                                    <input type="checkbox"
                                           id="aacb-headless-mode"
                                           name="headless_mode"
                                           value="1"
                                           aria-describedby="aacb-headless-help"
                                           <?php checked($headless_mode); ?>
                                           <?php if (!$is_developer): ?>disabled aria-disabled="true"<?php endif; ?>>
                                </div>
                                <?php if (!$is_developer): ?>
                                    <p class="aacx-v2__help">
                                        <?php
                                        printf(
                                            /* translators: %s: link to upgrade */
                                            wp_kses(__('Available on Enterprise. <a href="%s" target="_blank" rel="noopener">See plans</a>.', 'allaccessible'), array('a' => array('href' => true, 'target' => true, 'rel' => true))),
                                            esc_url($addon_url)
                                        );
                                        ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="aacx-v2__field">
                                <div class="aacx-v2__row aacx-v2__row--between" style="align-items: flex-start;">
                                    <div style="flex: 1; padding-right: var(--aacx-space-4);">
                                        <label class="aacx-v2__label" for="aacb-remove-branding"><?php esc_html_e('Hide widget branding', 'allaccessible'); ?></label>
                                        <p class="aacx-v2__help" id="aacb-branding-help">
                                            <?php esc_html_e('Remove the "Powered by" footer link inside the widget panel.', 'allaccessible'); ?>
                                        </p>
                                    </div>
                                    <input type="checkbox"
                                           id="aacb-remove-branding"
                                           name="remove_branding"
                                           value="1"
                                           aria-describedby="aacb-branding-help"
                                           <?php checked($remove_branding); ?>
                                           <?php if (!$is_paid_tier): ?>disabled aria-disabled="true"<?php endif; ?>>
                                </div>
                                <?php if (!$is_paid_tier): ?>
                                    <p class="aacx-v2__help">
                                        <?php
                                        printf(
                                            /* translators: %s: link to upgrade */
                                            wp_kses(__('Included with paid plans. <a href="%s" target="_blank" rel="noopener">See plans</a>.', 'allaccessible'), array('a' => array('href' => true, 'target' => true, 'rel' => true))),
                                            esc_url($addon_url)
                                        );
                                        ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                        </div>
                        <div class="aacx-v2__card-footer">
                            <span id="aacb-toggles-status" class="aacx-v2__help"></span>
                            <button type="submit"
                                    class="aacx-v2__btn aacx-v2__btn--primary"
                                    data-aacb-default-label="<?php esc_attr_e('Save changes', 'allaccessible'); ?>"
                                    data-aacb-saving-label="<?php esc_attr_e('Saving…', 'allaccessible'); ?>"
                                    data-aacb-saved-label="<?php esc_attr_e('Saved', 'allaccessible'); ?>">
                                <?php esc_html_e('Save changes', 'allaccessible'); ?>
                            </button>
                        </div>
                    </form>

                </section>

                <!-- ── Advanced tab ───────────────────────────────────── -->
                <section id="aacb-panel-advanced"
                         role="tabpanel"
                         aria-labelledby="aacb-tab-advanced"
                         class="aacx-v2__stack--lg"
                         <?php echo $active_tab === 'advanced' ? '' : 'hidden'; ?>>

                    <div class="aacx-v2__card aacx-v2__card--elevated">
                        <div class="aacx-v2__card-header">
                            <div>
                                <h3><?php esc_html_e('Diagnostics', 'allaccessible'); ?></h3>
                                <p class="aacx-v2__help" style="margin-top: var(--aacx-space-1);">
                                    <?php esc_html_e('Local identifiers and tools for troubleshooting.', 'allaccessible'); ?>
                                </p>
                            </div>
                        </div>
                        <div class="aacx-v2__card-body aacx-v2__stack">

                            <div class="aacx-v2__field">
                                <span class="aacx-v2__label"><?php esc_html_e('Plugin version', 'allaccessible'); ?></span>
                                <code class="aacx-v2__id" style="display: inline-block; width: max-content;">
                                    <?php echo esc_html(AACB_VERSION); ?>
                                </code>
                            </div>

                            <div class="aacx-v2__field">
                                <label class="aacx-v2__label" for="aacb-debug-mode" style="display: flex; align-items: flex-start; justify-content: space-between; gap: var(--aacx-space-4);">
                                    <span style="display: flex; flex-direction: column; gap: var(--aacx-space-1);">
                                        <?php esc_html_e('Debug logging', 'allaccessible'); ?>
                                        <span class="aacx-v2__help" id="aacb-debug-help">
                                            <?php esc_html_e('Write extra diagnostics to the WordPress debug log.', 'allaccessible'); ?>
                                        </span>
                                    </span>
                                    <input type="checkbox"
                                           id="aacb-debug-mode"
                                           name="debug_mode"
                                           value="1"
                                           aria-describedby="aacb-debug-help"
                                           form="aacb-quick-toggles-form"
                                           <?php checked($debug_mode); ?>>
                                </label>
                            </div>

                            <?php
                            
                            // Toggle is named `sentry_disabled` so the
                            // stored value mirrors the runtime check in
                            // AllAccessible_Sentry::init().
                            $sentry_disabled = !empty($aacb_options['sentry_disabled']);
                            ?>
                            <div class="aacx-v2__field" style="margin-top: var(--aacx-space-3);">
                                <label class="aacx-v2__label" for="aacb-sentry-disabled" style="display: flex; align-items: flex-start; justify-content: space-between; gap: var(--aacx-space-4);">
                                    <span style="display: flex; flex-direction: column; gap: var(--aacx-space-1);">
                                        <?php esc_html_e('Disable error reporting to AllAccessible', 'allaccessible'); ?>
                                        <span class="aacx-v2__help" id="aacb-sentry-help">
                                            <?php esc_html_e('AllAccessible records plugin errors (with your account ID + plugin version) so the team can spot issues affecting your install. Toggle on to stop sending. No personal data is collected.', 'allaccessible'); ?>
                                        </span>
                                    </span>
                                    <input type="checkbox"
                                           id="aacb-sentry-disabled"
                                           name="sentry_disabled"
                                           value="1"
                                           aria-describedby="aacb-sentry-help"
                                           form="aacb-quick-toggles-form"
                                           <?php checked($sentry_disabled); ?>>
                                </label>
                            </div>

                            <details class="aacb-dev-tools">
                                <summary class="aacb-dev-tools__summary">
                                    <span class="aacb-dev-tools__chevron" aria-hidden="true">▸</span>
                                    <span class="aacb-dev-tools__title"><?php esc_html_e('Developer tools', 'allaccessible'); ?></span>
                                    <span class="aacb-dev-tools__hint"><?php esc_html_e('Diagnose lookup · Sync · Flush caches', 'allaccessible'); ?></span>
                                </summary>
                                <div class="aacb-dev-tools__body">

                                    <div class="aacx-v2__field">
                                        <span class="aacx-v2__label"><?php esc_html_e('Diagnose page lookup', 'allaccessible'); ?></span>
                                        <p class="aacx-v2__help" id="aacb-diag-help" style="margin-bottom: var(--aacx-space-2);">
                                            <?php esc_html_e('Pick a published page or post to inspect the exact URL canonicalization and API response. Useful when a row shows "Not scanned" but you expect a score.', 'allaccessible'); ?>
                                        </p>
                                        <div style="display: flex; align-items: center; gap: var(--aacx-space-3); flex-wrap: wrap; margin-bottom: var(--aacx-space-2);">
                                            <input type="number"
                                                   id="aacb-diag-post-id"
                                                   min="1"
                                                   placeholder="<?php esc_attr_e('Post / page ID', 'allaccessible'); ?>"
                                                   class="aacx-v2__input"
                                                   style="max-width: 180px;">
                                            <button type="button"
                                                    id="aacb-diag-btn"
                                                    class="aacx-v2__btn aacx-v2__btn--secondary"
                                                    aria-describedby="aacb-diag-help"
                                                    data-nonce="<?php echo esc_attr(wp_create_nonce('aacb_diag_lookup')); ?>"
                                                    data-aacb-default-label="<?php esc_attr_e('Run diagnosis', 'allaccessible'); ?>"
                                                    data-aacb-busy-label="<?php esc_attr_e('Looking up…', 'allaccessible'); ?>">
                                                <?php esc_html_e('Run diagnosis', 'allaccessible'); ?>
                                            </button>
                                        </div>
                                        <pre id="aacb-diag-output"
                                             style="display: none; padding: var(--aacx-space-3); background: var(--aacx-slate-100); border-radius: var(--aacx-radius-sm); font-size: var(--aacx-text-xs); font-family: var(--aacx-font-mono); white-space: pre-wrap; word-break: break-all; max-height: 480px; overflow: auto;"
                                             aria-live="polite"></pre>
                                    </div>

                                    <div class="aacx-v2__field">
                                        <span class="aacx-v2__label"><?php esc_html_e('Sync post audit links', 'allaccessible'); ?></span>
                                        <p class="aacx-v2__help" id="aacb-backfill-help" style="margin-bottom: var(--aacx-space-2);">
                                            <?php esc_html_e('Manually run the post → audit linker. The background job runs about once a week (paid plans only, and only when your site receives traffic). Trigger here if the All Posts column or Editor box shows missing scores and you don\'t want to wait.', 'allaccessible'); ?>
                                        </p>
                                        <div style="display: flex; align-items: center; gap: var(--aacx-space-3); flex-wrap: wrap;">
                                            <button type="button"
                                                    id="aacb-backfill-run-now-btn"
                                                    class="aacx-v2__btn aacx-v2__btn--secondary"
                                                    aria-describedby="aacb-backfill-help"
                                                    data-nonce="<?php echo esc_attr(wp_create_nonce('aacb_backfill_run_now')); ?>"
                                                    data-aacb-default-label="<?php esc_attr_e('Sync now', 'allaccessible'); ?>"
                                                    data-aacb-busy-label="<?php esc_attr_e('Syncing…', 'allaccessible'); ?>">
                                                <?php esc_html_e('Sync now', 'allaccessible'); ?>
                                            </button>
                                            <span id="aacb-backfill-status" class="aacx-v2__help" aria-live="polite"></span>
                                        </div>
                                    </div>

                                    <div class="aacx-v2__field">
                                        <span class="aacx-v2__label"><?php esc_html_e('Flush plugin caches', 'allaccessible'); ?></span>
                                        <p class="aacx-v2__help" id="aacb-flush-help" style="margin-bottom: var(--aacx-space-2);">
                                            <?php esc_html_e('Wipes every plugin-side API transient (page scores, manifest, validate, images). Use when the API has been updated and the 2–10 min cache is still showing old data.', 'allaccessible'); ?>
                                        </p>
                                        <div style="display: flex; align-items: center; gap: var(--aacx-space-3); flex-wrap: wrap;">
                                            <button type="button"
                                                    id="aacb-flush-caches-btn"
                                                    class="aacx-v2__btn aacx-v2__btn--secondary"
                                                    aria-describedby="aacb-flush-help"
                                                    data-nonce="<?php echo esc_attr(wp_create_nonce('aacb_flush_caches')); ?>"
                                                    data-aacb-default-label="<?php esc_attr_e('Flush caches', 'allaccessible'); ?>"
                                                    data-aacb-busy-label="<?php esc_attr_e('Flushing…', 'allaccessible'); ?>">
                                                <?php esc_html_e('Flush caches', 'allaccessible'); ?>
                                            </button>
                                            <span id="aacb-flush-status" class="aacx-v2__help" aria-live="polite"></span>
                                        </div>
                                    </div>

                                </div>
                            </details>

                            <div class="aacx-v2__field">
                                <span class="aacx-v2__label"><?php esc_html_e('Clear widget caches', 'allaccessible'); ?></span>
                                <p class="aacx-v2__help" id="aacb-clear-help" style="margin-bottom: var(--aacx-space-2);">
                                    <?php esc_html_e('Flush local browser storage and server response cache so the widget reloads fresh on the next page view.', 'allaccessible'); ?>
                                </p>
                                <button type="button"
                                        id="aacb-clear-caches-btn"
                                        class="aacx-v2__btn aacx-v2__btn--secondary"
                                        aria-describedby="aacb-clear-help"
                                        data-aacb-default-label="<?php esc_attr_e('Clear widget caches', 'allaccessible'); ?>"
                                        data-aacb-busy-label="<?php esc_attr_e('Clearing…', 'allaccessible'); ?>"
                                        data-aacb-done-label="<?php esc_attr_e('Caches cleared', 'allaccessible'); ?>">
                                    <?php esc_html_e('Clear widget caches', 'allaccessible'); ?>
                                </button>
                            </div>

                        </div>
                    </div>

                    <div class="aacx-v2__card" style="border-color: var(--aacx-danger-500);">
                        <div class="aacx-v2__card-header">
                            <div>
                                <h3 style="color: var(--aacx-danger-700);"><?php esc_html_e('Reset plugin data', 'allaccessible'); ?></h3>
                                <p class="aacx-v2__help" style="margin-top: var(--aacx-space-1);">
                                    <?php esc_html_e('Disconnect this site and clear local options. Cannot be undone.', 'allaccessible'); ?>
                                </p>
                            </div>
                        </div>
                        <div class="aacx-v2__card-body">
                            <p style="font-size: var(--aacx-text-sm); color: var(--aacx-text);">
                                <?php esc_html_e('Use this if you are moving the plugin to a different account or starting over from the setup wizard. Uninstalling the plugin also removes these options automatically.', 'allaccessible'); ?>
                            </p>
                        </div>
                        <div class="aacx-v2__card-footer">
                            <span id="aacb-reset-status" class="aacx-v2__help"></span>
                            <button type="button"
                                    id="aacb-reset-plugin-btn"
                                    class="aacx-v2__btn aacx-v2__btn--danger">
                                <?php esc_html_e('Reset plugin data', 'allaccessible'); ?>
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Need help? — prominent support row. Support discovery
                     was a gap; surfacing it here (where account holders
                     land for settings) gives a one-click path to help +
                     docs before frustration turns into a bad review. -->
                <div class="aacb-support-row" style="margin-top: var(--aacx-space-8); padding: var(--aacx-space-4) var(--aacx-space-5); background: var(--aacx-slate-50); border: 1px solid var(--aacx-border); border-radius: var(--aacx-radius); display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: var(--aacx-space-3);">
                    <div>
                        <strong style="color: var(--aacx-text-strong);"><?php esc_html_e('Need help?', 'allaccessible'); ?></strong>
                        <span style="color: var(--aacx-text-muted); font-size: var(--aacx-text-sm);">
                            <?php esc_html_e('Our team is here for you — get answers fast.', 'allaccessible'); ?>
                        </span>
                    </div>
                    <div style="display: flex; gap: var(--aacx-space-2); flex-wrap: wrap;">
                        <a href="<?php echo esc_url(AACB_SUPPORT); ?>" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--primary aacx-v2__btn--sm">
                            <?php esc_html_e('Contact support', 'allaccessible'); ?>
                            <span aria-hidden="true">↗</span>
                        </a>
                        <a href="<?php echo esc_url(AACB_SUPPORT); ?>" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--secondary aacx-v2__btn--sm">
                            <?php esc_html_e('Knowledge base', 'allaccessible'); ?>
                            <span aria-hidden="true">↗</span>
                        </a>
                    </div>
                </div>

                <!-- Legal & Trust footer. Persistent links to the docs
                     that govern data handling + the consent the customer
                     gave at account creation. Surfaced here so the
                     references in error-reporting / privacy copy are one
                     click away from where the toggles live. -->
                <footer class="aacb-legal-footer" style="margin-top: var(--aacx-space-5); padding-top: var(--aacx-space-5); border-top: 1px solid var(--aacx-border); display: flex; flex-wrap: wrap; align-items: center; gap: var(--aacx-space-2) var(--aacx-space-4); font-size: var(--aacx-text-sm);">
                    <span style="color: var(--aacx-text-muted);"><?php esc_html_e('Legal & Trust:', 'allaccessible'); ?></span>
                    <a href="https://www.allaccessible.org/terms-conditions" target="_blank" rel="noopener"><?php esc_html_e('Terms & Conditions', 'allaccessible'); ?></a>
                    <a href="https://www.allaccessible.org/privacy-policy" target="_blank" rel="noopener"><?php esc_html_e('Privacy Policy', 'allaccessible'); ?></a>
                    <a href="https://www.allaccessible.org/gdpr" target="_blank" rel="noopener"><?php esc_html_e('GDPR', 'allaccessible'); ?></a>
                    <a href="https://www.allaccessible.org/cookies" target="_blank" rel="noopener"><?php esc_html_e('Cookies', 'allaccessible'); ?></a>
                    <a href="https://www.allaccessible.org/trust-center" target="_blank" rel="noopener"><?php esc_html_e('Trust Center', 'allaccessible'); ?></a>
                    <a href="https://www.allaccessible.org/partner-agreement" target="_blank" rel="noopener"><?php esc_html_e('Partner Agreement', 'allaccessible'); ?></a>
                </footer>

                <?php endif; /* end has_account */ ?>
            </div>
        </div>

        <?php /* Inline AJAX wiring for Advanced tab buttons — kept here so
                 the tab is self-contained and ships as one render. */ ?>
        <script>
        (function($) {
            $(document).on('click', '#aacb-diag-btn', function(e) {
                e.preventDefault();
                var $btn  = $(this);
                var $out  = $('#aacb-diag-output');
                var pid   = parseInt($('#aacb-diag-post-id').val(), 10);
                if (!pid || pid < 1) {
                    $out.text('Enter a valid post ID.').show();
                    return;
                }
                var defaultLabel = $btn.data('aacb-default-label') || 'Run diagnosis';
                var busyLabel    = $btn.data('aacb-busy-label')    || 'Looking up…';
                $btn.prop('disabled', true).text(busyLabel);
                $out.text('').show();
                $.post(ajaxurl, {
                    action:   'aacb_diag_lookup',
                    _wpnonce: $btn.data('nonce'),
                    post_id:  pid
                }).done(function(resp) {
                    $out.text(JSON.stringify(resp, null, 2));
                }).fail(function(xhr) {
                    $out.text('Error ' + xhr.status + ': ' + (xhr.responseText || 'request failed'));
                }).always(function() {
                    $btn.prop('disabled', false).text(defaultLabel);
                });
            });

            $(document).on('click', '#aacb-flush-caches-btn', function(e) {
                e.preventDefault();
                var $btn    = $(this);
                var $status = $('#aacb-flush-status');
                var defaultLabel = $btn.data('aacb-default-label') || 'Flush caches';
                var busyLabel    = $btn.data('aacb-busy-label')    || 'Flushing…';
                $btn.prop('disabled', true).text(busyLabel);
                $status.text('');
                $.post(ajaxurl, {
                    action:   'aacb_flush_caches',
                    _wpnonce: $btn.data('nonce')
                }).done(function(resp) {
                    if (resp && resp.success) {
                        $status.text('Done. Reload your WP admin pages to refetch.');
                    } else {
                        $status.text('Failed: ' + ((resp && resp.data && resp.data.message) || 'unknown'));
                    }
                }).fail(function(xhr) {
                    $status.text('Error ' + xhr.status);
                }).always(function() {
                    $btn.prop('disabled', false).text(defaultLabel);
                });
            });

            $(document).on('click', '#aacb-backfill-run-now-btn', function(e) {
                e.preventDefault();
                var $btn    = $(this);
                var $status = $('#aacb-backfill-status');
                var defaultLabel = $btn.data('aacb-default-label') || 'Sync now';
                var busyLabel    = $btn.data('aacb-busy-label')    || 'Syncing…';
                $btn.prop('disabled', true).text(busyLabel);
                $status.text('');
                $.post(ajaxurl, {
                    action:   'aacb_backfill_run_now',
                    _wpnonce: $btn.data('nonce')
                }).done(function(resp) {
                    if (resp && resp.success) {
                        var d = resp.data || {};
                        $status.text('Done. inserted=' + (d.inserted || 0) + ' updated=' + (d.updated || 0) + ' skipped=' + (d.skipped || 0));
                    } else {
                        $status.text('Failed: ' + ((resp && resp.data && resp.data.message) || 'unknown'));
                    }
                }).fail(function(xhr) {
                    $status.text('Error ' + xhr.status);
                }).always(function() {
                    $btn.prop('disabled', false).text(defaultLabel);
                });
            });
        })(jQuery);
        </script>
        <style>
            .aacx-v2 .aacx-v2__card + .aacx-v2__card {
                margin-top: var(--aacx-space-6);
            }
            .aacx-v2 section[id^="aacb-panel-"] + section[id^="aacb-panel-"] {
                margin-top: var(--aacx-space-6);
            }

            .aacx-v2 .aacx-v2__id {
                font-family: var(--aacx-font-mono);
                font-size: var(--aacx-text-sm);
                background: var(--aacx-slate-100);
                color: var(--aacx-text-strong);
                padding: var(--aacx-space-2) var(--aacx-space-3);
                border-radius: var(--aacx-radius-sm);
                word-break: break-all;
                flex: 1;
                line-height: 1.4;
            }
            .aacx-v2 .aacb-dev-tools {
                background: var(--aacx-slate-50);
                border: 1px solid var(--aacx-border);
                border-radius: var(--aacx-radius-sm);
                padding: 0;
                margin: 0;
            }
            .aacx-v2 .aacb-dev-tools__summary {
                display: flex;
                align-items: center;
                gap: var(--aacx-space-2);
                padding: var(--aacx-space-2) var(--aacx-space-3);
                cursor: pointer;
                list-style: none;
                user-select: none;
                font-size: var(--aacx-text-sm);
                color: var(--aacx-text);
                border-radius: var(--aacx-radius-sm);
            }
            .aacx-v2 .aacb-dev-tools__summary::-webkit-details-marker { display: none; }
            .aacx-v2 .aacb-dev-tools__summary:hover { background: var(--aacx-slate-100); }
            .aacx-v2 .aacb-dev-tools__summary:focus-visible {
                outline: 2px solid var(--aacx-primary-500);
                outline-offset: 2px;
            }
            .aacx-v2 .aacb-dev-tools__chevron {
                font-size: var(--aacx-text-base);
                color: var(--aacx-text-muted);
                transition: transform var(--aacx-transition);
                line-height: 1;
            }
            .aacx-v2 .aacb-dev-tools[open] .aacb-dev-tools__chevron {
                transform: rotate(90deg);
            }
            .aacx-v2 .aacb-dev-tools__title {
                font-weight: var(--aacx-weight-semibold);
                color: var(--aacx-text-strong);
            }
            .aacx-v2 .aacb-dev-tools__hint {
                margin-left: auto;
                font-size: var(--aacx-text-xs);
                color: var(--aacx-text-muted);
            }
            .aacx-v2 .aacb-dev-tools__body {
                padding: var(--aacx-space-3) var(--aacx-space-3) var(--aacx-space-2);
                display: flex;
                flex-direction: column;
                gap: var(--aacx-space-3);
                border-top: 1px solid var(--aacx-border);
            }
            @media (prefers-reduced-motion: reduce) {
                .aacx-v2 .aacb-dev-tools__chevron { transition: none; }
            }
            .aacx-v2 .aacx-v2__usage-bar {
                width: 100%;
                height: 10px;
                background: var(--aacx-slate-100);
                border-radius: var(--aacx-radius-pill);
                overflow: hidden;
            }
            .aacx-v2 .aacx-v2__usage-fill {
                height: 100%;
                transition: width var(--aacx-transition);
            }
            .aacx-v2 .aacx-v2__widget-thumb {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: var(--aacx-space-8);
                background: var(--aacx-slate-100);
                border-radius: var(--aacx-radius-md);
                min-height: 180px;
            }
            .aacx-v2 .aacx-v2__widget-thumb-bubble {
                width: 64px;
                height: 64px;
                border-radius: 50%;
                background: var(--aacx-primary-600);
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: var(--aacx-shadow-lg);
            }
            .aacx-v2 .aacx-v2__widget-thumb-icon {
                width: 32px;
                height: 32px;
                filter: brightness(0) invert(1);
            }
            .aacx-v2 input[type="checkbox"] {
                width: 18px;
                height: 18px;
                accent-color: var(--aacx-primary-600);
                cursor: pointer;
                flex-shrink: 0;
            }
            .aacx-v2 input[type="checkbox"]:disabled {
                cursor: not-allowed;
                opacity: 0.5;
            }
        </style>

        <?php if ($has_account) : ?>
        <script>
        (function () {
            'use strict';

            var ajaxUrl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
            var nonces  = {
                reset:   '<?php echo esc_js($reset_nonce); ?>',
                clear:   '<?php echo esc_js($clear_nonce); ?>',
                toggles: '<?php echo esc_js($toggles_nonce); ?>'
            };
            var live = document.getElementById('aacb-v2-live');
            function announce(msg) {
                if (!live) return;
                live.textContent = '';
                setTimeout(function () { live.textContent = msg; }, 30);
            }

            /* ── Tabs (WAI-ARIA tablist pattern) ─────────────────────── */
            var tabs   = Array.prototype.slice.call(document.querySelectorAll('[role="tab"][data-aacb-tab]'));
            var panels = {};
            tabs.forEach(function (t) {
                var id = t.getAttribute('data-aacb-tab');
                panels[id] = document.getElementById('aacb-panel-' + id);
            });

            function activate(tab) {
                tabs.forEach(function (t) {
                    var id = t.getAttribute('data-aacb-tab');
                    var active = (t === tab);
                    t.setAttribute('aria-selected', active ? 'true' : 'false');
                    t.setAttribute('tabindex', active ? '0' : '-1');
                    if (panels[id]) {
                        if (active) { panels[id].removeAttribute('hidden'); }
                        else { panels[id].setAttribute('hidden', ''); }
                    }
                });
                try {
                    var url = new URL(window.location.href);
                    url.searchParams.set('tab', tab.getAttribute('data-aacb-tab'));
                    window.history.replaceState({}, '', url.toString());
                } catch (e) { /* old browsers — ignore */ }
            }

            tabs.forEach(function (tab, idx) {
                tab.addEventListener('click', function () { activate(tab); tab.focus(); });
                tab.addEventListener('keydown', function (e) {
                    var nextIdx = null;
                    if (e.key === 'ArrowRight') { nextIdx = (idx + 1) % tabs.length; }
                    else if (e.key === 'ArrowLeft') { nextIdx = (idx - 1 + tabs.length) % tabs.length; }
                    else if (e.key === 'Home') { nextIdx = 0; }
                    else if (e.key === 'End')  { nextIdx = tabs.length - 1; }
                    if (nextIdx !== null) {
                        e.preventDefault();
                        activate(tabs[nextIdx]);
                        tabs[nextIdx].focus();
                    }
                });
            });

            /* ── Copy-to-clipboard ──────────────────────────────────── */
            document.querySelectorAll('[data-aacb-copy]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var value    = btn.getAttribute('data-aacb-copy') || '';
                    var label    = btn.getAttribute('aria-label') || '<?php echo esc_js(__('Copied', 'allaccessible')); ?>';
                    var original = btn.textContent;
                    var done = function () {
                        btn.textContent = '<?php echo esc_js(__('Copied', 'allaccessible')); ?>';
                        announce(label + ': <?php echo esc_js(__('copied to clipboard', 'allaccessible')); ?>');
                        setTimeout(function () { btn.textContent = original; }, 1800);
                    };
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(value).then(done).catch(function () {
                            window.prompt('<?php echo esc_js(__('Copy:', 'allaccessible')); ?>', value);
                        });
                    } else {
                        window.prompt('<?php echo esc_js(__('Copy:', 'allaccessible')); ?>', value);
                    }
                });
            });

            /* ── Quick toggles save ─────────────────────────────────── */
            var togglesForm = document.getElementById('aacb-quick-toggles-form');
            if (togglesForm) {
                togglesForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var btn       = togglesForm.querySelector('button[type="submit"]');
                    var status    = document.getElementById('aacb-toggles-status');
                    var saving    = btn.getAttribute('data-aacb-saving-label');
                    var saved     = btn.getAttribute('data-aacb-saved-label');
                    var defaultLb = btn.getAttribute('data-aacb-default-label');
                    btn.disabled  = true;
                    btn.textContent = saving;
                    announce(saving);

                    function readToggle(name) {
                        var el = togglesForm.querySelector('[name="' + name + '"]');
                        if (!el) {
                            // debug_mode lives in another panel but submits via form="" attr
                            el = document.querySelector('[name="' + name + '"][form="aacb-quick-toggles-form"]');
                        }
                        return (el && !el.disabled && el.checked) ? '1' : '0';
                    }

                    var fd = new FormData();
                    fd.append('action',           'aacb_save_quick_toggles');
                    fd.append('_wpnonce',         nonces.toggles);
                    fd.append('headless_mode',    readToggle('headless_mode'));
                    fd.append('remove_branding',  readToggle('remove_branding'));
                    fd.append('debug_mode',       readToggle('debug_mode'));
                    fd.append('sentry_disabled',  readToggle('sentry_disabled'));

                    fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
                        .then(function (r) { return r.json(); })
                        .then(function (json) {
                            if (json && json.success) {
                                btn.textContent = saved;
                                if (status) { status.textContent = saved; }
                                announce(saved);
                                setTimeout(function () {
                                    btn.disabled = false;
                                    btn.textContent = defaultLb;
                                    if (status) { status.textContent = ''; }
                                }, 2200);
                            } else {
                                btn.disabled = false;
                                btn.textContent = defaultLb;
                                if (status) { status.textContent = '<?php echo esc_js(__('Could not save. Try again.', 'allaccessible')); ?>'; }
                                announce('<?php echo esc_js(__('Save failed', 'allaccessible')); ?>');
                            }
                        })
                        .catch(function () {
                            btn.disabled = false;
                            btn.textContent = defaultLb;
                            if (status) { status.textContent = '<?php echo esc_js(__('Network error. Try again.', 'allaccessible')); ?>'; }
                        });
                });
            }

            /* ── Clear widget caches ────────────────────────────────── */
            var clearBtn = document.getElementById('aacb-clear-caches-btn');
            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    var defaultLb = clearBtn.getAttribute('data-aacb-default-label');
                    var busy      = clearBtn.getAttribute('data-aacb-busy-label');
                    var done      = clearBtn.getAttribute('data-aacb-done-label');
                    clearBtn.disabled = true;
                    clearBtn.textContent = busy;
                    announce(busy);
                    if (typeof window.aacxClearWidgetCaches === 'function') {
                        try { window.aacxClearWidgetCaches(); } catch (e) { /* swallow */ }
                    } else {
                        try {
                            if (typeof localStorage !== 'undefined') {
                                var toRemove = [];
                                for (var i = 0; i < localStorage.length; i++) {
                                    var k = localStorage.key(i);
                                    if (k && (k.indexOf('aacx_') === 0 || k.indexOf('aacx-') === 0)) { toRemove.push(k); }
                                }
                                toRemove.forEach(function (k) { localStorage.removeItem(k); });
                                localStorage.removeItem('overrideOptions');
                                localStorage.setItem('aacx_cache_invalidation', JSON.stringify({ ts: Date.now(), source: 'wp-plugin-settings' }));
                            }
                            document.cookie = 'aacxValidated=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                        } catch (e) { /* swallow */ }
                    }

                    /* Server-side: bust the WP transient cache. */
                    var fd = new FormData();
                    fd.append('action',   'aacb_clear_cache');
                    fd.append('_wpnonce', nonces.clear);

                    fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
                        .then(function (r) { return r.json().catch(function () { return {}; }); })
                        .then(function () {
                            clearBtn.textContent = done;
                            announce(done);
                            setTimeout(function () { clearBtn.disabled = false; clearBtn.textContent = defaultLb; }, 2200);
                        })
                        .catch(function () {
                            clearBtn.disabled = false;
                            clearBtn.textContent = defaultLb;
                        });
                });
            }

            /* ── Reset plugin data ──────────────────────────────────── */
            var resetBtn = document.getElementById('aacb-reset-plugin-btn');
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    var confirmMsg = '<?php echo esc_js(__('Reset all plugin data? This disconnects this site and cannot be undone.', 'allaccessible')); ?>';
                    if (!window.confirm(confirmMsg)) { return; }
                    var status   = document.getElementById('aacb-reset-status');
                    var original = resetBtn.textContent;
                    resetBtn.disabled = true;
                    resetBtn.textContent = '<?php echo esc_js(__('Resetting…', 'allaccessible')); ?>';
                    announce('<?php echo esc_js(__('Resetting plugin data', 'allaccessible')); ?>');

                    var fd = new FormData();
                    fd.append('action',   'aacb_reset_plugin_data');
                    fd.append('_wpnonce', nonces.reset);

                    fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
                        .then(function (r) { return r.json(); })
                        .then(function (json) {
                            if (json && json.success) {
                                if (status) { status.textContent = '<?php echo esc_js(__('Plugin data cleared. Reloading…', 'allaccessible')); ?>'; }
                                announce('<?php echo esc_js(__('Plugin data reset. Reloading.', 'allaccessible')); ?>');
                                setTimeout(function () {
                                    window.location.href = '<?php echo esc_js(admin_url('admin.php?page=allaccessible-wizard')); ?>';
                                }, 1200);
                            } else {
                                resetBtn.disabled = false;
                                resetBtn.textContent = original;
                                if (status) { status.textContent = '<?php echo esc_js(__('Could not reset plugin data. Try again.', 'allaccessible')); ?>'; }
                            }
                        })
                        .catch(function () {
                            resetBtn.disabled = false;
                            resetBtn.textContent = original;
                            if (status) { status.textContent = '<?php echo esc_js(__('Network error. Try again.', 'allaccessible')); ?>'; }
                        });
                });
            }
        })();
        </script>
        <?php endif; ?>
        <?php
    }

    /* =====================================================================
     * AJAX — quick toggles save (additive, preserves aacb_* prefix).
     * Writes into the existing aacb_options array so no new option key is
     * introduced. Verifies nonce + capability before persisting.
     * ===================================================================== */
    public function ajax_save_quick_toggles() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'allaccessible')), 403);
        }
        check_ajax_referer('aacb_save_quick_toggles', '_wpnonce');

        $existing = (array) get_option('aacb_options', array());

        $existing['headless_mode']    = isset($_POST['headless_mode'])    && (string) $_POST['headless_mode']    === '1';
        $existing['remove_branding']  = isset($_POST['remove_branding'])  && (string) $_POST['remove_branding']  === '1';
        $existing['debug_mode']       = isset($_POST['debug_mode'])       && (string) $_POST['debug_mode']       === '1';
        $existing['sentry_disabled']  = isset($_POST['sentry_disabled'])  && (string) $_POST['sentry_disabled']  === '1';

        update_option('aacb_options', $existing);

        wp_send_json_success(array(
            'message' => __('Settings saved', 'allaccessible'),
        ));
    }

    /**
     * Tier metadata used by the redesigned account header + plan card.
     * v2_variant maps to the design-system badge variant so the header
     * pill and Plan tab never drift out of sync.
     */
    private static function tier_meta($tier) {
        switch ($tier) {
            case 'trial':
                return array(
                    'label'      => __('Trial', 'allaccessible'),
                    'long_label' => __('Premium Trial — 7 days free', 'allaccessible'),
                    'v2_variant' => 'primary',
                );
            case 'starter':
                return array(
                    'label'      => __('Starter', 'allaccessible'),
                    'long_label' => __('Starter', 'allaccessible'),
                    'v2_variant' => 'ok',
                );
            case 'enterprise':
                return array(
                    'label'      => __('Enterprise', 'allaccessible'),
                    'long_label' => __('Enterprise', 'allaccessible'),
                    'v2_variant' => 'ai',
                );
            case 'legacy':
                return array(
                    'label'      => __('Legacy', 'allaccessible'),
                    'long_label' => __('Legacy Premium', 'allaccessible'),
                    'v2_variant' => 'primary',
                );
            case 'free':
                return array(
                    'label'      => __('Free', 'allaccessible'),
                    'long_label' => __('Free', 'allaccessible'),
                    'v2_variant' => 'primary',
                );
            default:
                return array(
                    'label'      => ucfirst((string) $tier),
                    'long_label' => sprintf(
                        /* translators: %s: tier name (e.g., "Enterprise") */
                        __('%s account', 'allaccessible'),
                        ucfirst((string) $tier)
                    ),
                    'v2_variant' => 'primary',
                );
        }
    }

    /**
     * Plan benefit checklist for the Plan card.
     *
     * Brand voice: outcome-focused, no fear marketing, no internal stack
     * components. AI mentions wrap "AllAccessible AI" in the v2 AI badge.
     */
    private static function tier_benefits($tier, $live_limits = array()) {
        $ai_badge = '<span class="aacx-v2__ai-badge">' . esc_html__('AllAccessible AI', 'allaccessible') . '</span>';
        $pv_limit = isset($live_limits['pageviews_monthly']) ? (int) $live_limits['pageviews_monthly'] : null;
        $pv_benefit = $pv_limit && $pv_limit > 0
            ? sprintf(
                /* translators: %s: monthly pageview limit (e.g. "10,000") */
                __('Up to %s pageviews per month', 'allaccessible'),
                number_format_i18n($pv_limit)
              )
            : __('Pageview cap based on your plan', 'allaccessible');
        $ai_benefit = sprintf(
            /* translators: %s: AI badge */
            __('Unlimited %s interactions — alt text and agentic fix proposals, no monthly cap', 'allaccessible'),
            $ai_badge
        );

        switch ($tier) {
            case 'enterprise':
                return array(
                    __('Unlimited pageviews and sites', 'allaccessible'),
                    sprintf(/* translators: %s: AI badge */ __('Human-in-the-loop agentic remediation powered by %s', 'allaccessible'), $ai_badge),
                    __('Priority support and dedicated success manager', 'allaccessible'),
                    __('Custom branding and white-label widget', 'allaccessible'),
                    __('Single sign-on (SSO) and audit log export', 'allaccessible'),
                );
            case 'starter':
                return array(
                    $pv_benefit,
                    $ai_benefit,
                    __('Continuous site scans and accessibility scoring', 'allaccessible'),
                    __('Hide widget branding', 'allaccessible'),
                    __('Email support', 'allaccessible'),
                );
            case 'trial':
                return array(
                    __('Full premium widget for 7 days', 'allaccessible'),
                    sprintf(/* translators: %s: AI badge */ __('Try %s agents on your real site', 'allaccessible'), $ai_badge),
                    __('Cancel any time — no card hold', 'allaccessible'),
                );
            case 'legacy':
                return array(
                    __('Premium widget on the legacy plan', 'allaccessible'),
                    sprintf(/* translators: %s: AI badge */ __('Includes %s agent features', 'allaccessible'), $ai_badge),
                    __('Continuous accessibility scoring', 'allaccessible'),
                );
            case 'free':
            default:
                return array(
                    __('Accessibility widget on every page', 'allaccessible'),
                    __('Keyboard navigation and screen reader helpers', 'allaccessible'),
                    sprintf(/* translators: %s: AI badge */ __('Upgrade for agentic remediation with %s', 'allaccessible'), $ai_badge),
                );
        }
    }
}

// Initialize settings page
add_action('plugins_loaded', function() {
    AllAccessible_SettingsPage::get_instance();
});
