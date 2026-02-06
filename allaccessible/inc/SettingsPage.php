<?php
/**
 * AllAccessible Settings Page - Clean v2.0
 *
 * Shows:
 * - No account: Redirect to wizard
 * - With account: Configuration + account info
 *
 * @package AllAccessible
 * @since 2.0.0
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
        add_action('admin_menu', array($this, 'register_settings_page'));
        add_action('admin_menu', array($this, 'register_account_page'), 20);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function register_settings_page() {
        // Add top-level menu
        add_menu_page(
            __('AllAccessible', 'allaccessible'),
            __('AllAccessible', 'allaccessible'),
            'manage_options',
            'allaccessible',
            array($this, 'render_settings_page'),
            'data:image/svg+xml;base64,PHN2ZyBpZD0iTGF5ZXJfMSIgZGF0YS1uYW1lPSJMYXllciAxIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB2aWV3Qm94PSIwIDAgMzE3LjkgMzE3Ljg5Ij48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImxpbmVhci1ncmFkaWVudCIgeDE9IjE4My4yOSIgeTE9IjIxOC4yMiIgeDI9IjE4My4yOSIgeTI9IjQxLjI0IiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHN0b3Agb2Zmc2V0PSIwIiBzdG9wLWNvbG9yPSIjMGY4Y2ZhIi8+PHN0b3Agb2Zmc2V0PSIxIiBzdG9wLWNvbG9yPSIjNTRiOGZmIi8+PC9saW5lYXJHcmFkaWVudD48bGluZWFyR3JhZGllbnQgaWQ9ImxpbmVhci1ncmFkaWVudC0yIiB4MT0iMTU4Ljk0IiB5MT0iMzA5LjQ3IiB4Mj0iMTU4Ljk2IiB5Mj0iMjMuMDgiIGdyYWRpZW50VW5pdHM9InVzZXJTcGFjZU9uVXNlIj48c3RvcCBvZmZzZXQ9IjAiIHN0b3AtY29sb3I9IiMwOTU3YjAiLz48c3RvcCBvZmZzZXQ9IjEiIHN0b3AtY29sb3I9IiMyMTc5ZWIiLz48L2xpbmVhckdyYWRpZW50PjwvZGVmcz48cGF0aCBkPSJNMjU2LjYyLDU3Ljg4bC05Ny45LDk3LjlMMTEwLDEwN2MtMTcuOTMtMTcuOTMtNDUsOS4xOC0yNy4xMSwyNy4xMWw2Mi4yLDYyLjJhMTkuMDgsMTkuMDgsMCwwLDAsMTMuMzUsNS41OWguNjdhMTkuMTQsMTkuMTQsMCwwLDAsMTMuMzUtNS41OUwyODMuNzMsODVDMzAxLjY3LDY3LjA2LDI3NC41NSw0MCwyNTYuNjIsNTcuODhaIiBzdHlsZT0iZmlsbC1ydWxlOmV2ZW5vZGQ7ZmlsbDp1cmwoI2xpbmVhci1ncmFkaWVudCkiLz48cGF0aCBkPSJNMTU5LDYwLjMxYTI3Ljg2LDI3Ljg2LDAsMSwxLTI3Ljg2LDI3Ljg1QTI3Ljg1LDI3Ljg1LDAsMCwxLDE1OSw2MC4zMVpNMTU5LDBhMTU4Ljg4LDE1OC44OCwwLDEsMCwxMzguOSw4MS42MiwzMC40OCwzMC40OCwwLDAsMS03LDEwLjU0TDI3OC4yNiwxMDQuOGExMzEuMTEsMTMxLjExLDAsMCwxLTI1Ljc1LDE0NS44MmwtNDcuMzUtNDcuMzVjLTE3LjkzLTE3LjkzLTQ1LDkuMTgtMjcuMTEsMjcuMTFMMjIxLjY2LDI3NEExMzEuMjEsMTMxLjIxLDAsMCwxLDk2LDI3My44M2w0My42LTQzLjZjMTcuOTQtMTcuOTQtOS4xOS00NS0yNy4xMS0yNy4xMUw2NS4xNiwyNTAuNEExMzEsMTMxLDAsMCwxLDI0Mi4yOSw1Ny44OGw3LjE3LTcuMTZhMjkuMzMsMjkuMzMsMCwwLDEsMTcuMTktOC42N0ExNTguNDEsMTU4LjQxLDAsMCwwLDE1OSwwWiIgc3R5bGU9ImZpbGwtcnVsZTpldmVub2RkO2ZpbGw6dXJsKCNsaW5lYXItZ3JhZGllbnQtMikiLz48L3N2Zz4=',
            25
        );

        // Add first submenu (same slug) to ensure proper menu highlighting
        add_submenu_page(
            'allaccessible',
            __('Dashboard', 'allaccessible'),
            __('Dashboard', 'allaccessible'),
            'manage_options',
            'allaccessible', // Same slug as parent
            array($this, 'render_settings_page')
        );
    }

    public function register_account_page() {
        add_submenu_page(
            'allaccessible',
            __('Account Info', 'allaccessible'),
            __('Account', 'allaccessible'),
            'manage_options',
            'allaccessible-account',
            array($this, 'render_account_page')
        );
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_allaccessible' && $hook !== 'allaccessible_page_allaccessible-account') {
            return;
        }

        wp_enqueue_style(
            'allaccessible-admin',
            AACB_URL . 'assets/admin.css',
            array(),
            AACB_VERSION
        );

        // Hide WordPress admin notices on our pages for clean experience
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

        // Remove ALL admin notices
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        remove_all_actions('user_admin_notices');
        remove_all_actions('network_admin_notices');

        // Get account info
        $account_id = get_option('aacb_accountID');
        $has_account = !empty($account_id);
        $site_url = get_bloginfo('wpurl');

        // Get REAL tier from API (not WordPress cached tier)
        $account_tier = 'unknown';
        $is_paid = false;
        $exceeded_limits = array();
        $usage_summary = null;
        $audit_data = null;
        $site_options = null;
        $addon_url = 'https://app.allaccessible.org/billing'; // Default

        if ($has_account) {
            $api_client = AllAccessible_ApiClient::get_instance();

            // Get site validation data (tier, limits, usage, config)
            $site_options = $api_client->get_site_options();

            // Debug: Log what API returned
            error_log('AllAccessible Debug - API Response: ' . print_r($site_options, true));

            // Get current subscription tier from /validate
            $account_tier = $api_client->get_subscription_tier();

            // Debug: Log detected tier
            error_log('AllAccessible Debug - Detected Tier: ' . $account_tier);

            // Check if paid account
            $is_paid = $api_client->is_paid_account();

            // Check exceeded limits
            $exceeded_limits = $api_client->get_exceeded_limits();

            // Get usage summary
            $usage_summary = $api_client->get_usage_summary();

            // Get app URLs
            $addon_url = $api_client->get_addon_url();
            $audits_url = $api_client->get_audits_url();
            $widget_settings_url = $api_client->get_widget_settings_url();

            // Get accessibility scores
            $scores = $api_client->get_audit_scores();
            if ($scores && !is_wp_error($scores)) {
                $audit_data = $api_client->format_audit_score($scores);
            }
        }

        // Determine if paid tier (for UI display)
        $is_paid_tier = in_array($account_tier, array('starter', 'legacy', 'enterprise'));

        // Show success message if just completed wizard
        $show_success = isset($_GET['wizard']) && $_GET['wizard'] === 'complete';
        ?>
        <div class="allaccessible-admin" style="margin: -10px 0 0 -20px;">
            <div class="aacx-min-h-screen aacx-bg-aacx-gray-50">

                <!-- Header -->
                <div class="aacx-bg-white aacx-border-b aacx-border-aacx-gray-200 aacx-shadow-sm">
                    <div class="aacx-max-w-7xl aacx-mx-auto aacx-px-6 aacx-py-6">
                        <div class="aacx-flex aacx-items-center aacx-justify-between">
                            <div class="aacx-flex aacx-items-center aacx-gap-6">
                                <img src="<?php echo esc_url(AACB_IMG . 'logo.svg'); ?>" height="48" alt="AllAccessible" style="padding: 4px;">
                                <?php if ($has_account): ?>
                                <div class="aacx-pl-6 aacx-border-l-2 aacx-border-aacx-gray-300">
                                    <p class="aacx-text-sm aacx-font-semibold aacx-text-aacx-slate-600">
                                        <?php
                                        switch ($account_tier) {
                                            case 'starter':
                                                _e('Starter Plan', 'allaccessible');
                                                break;
                                            case 'enterprise':
                                                _e('Enterprise Plan', 'allaccessible');
                                                break;
                                            case 'legacy':
                                                _e('Legacy Premium', 'allaccessible');
                                                break;
                                            case 'free':
                                                _e('Free Forever', 'allaccessible');
                                                break;
                                            default:
                                                echo esc_html(ucfirst($account_tier));
                                        }
                                        ?>
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="aacx-flex aacx-gap-3">
                                <a href="<?php echo esc_url($site_url . '/?aacb_preview=true'); ?>"
                                   target="_blank"
                                   class="aacx-px-6 aacx-py-3 aacx-border-2 aacx-border-aacx-gray-300 aacx-text-aacx-slate-700 aacx-rounded-lg aacx-font-semibold hover:aacx-bg-aacx-gray-50 aacx-transition-colors aacx-inline-flex aacx-items-center">
                                    <svg class="aacx-w-5 aacx-h-5 aacx-mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <?php _e('Preview Widget', 'allaccessible'); ?>
                                </a>
                                <?php if ($has_account): ?>
                                <a href="https://app.allaccessible.org"
                                   target="_blank"
                                   class="aacx-px-6 aacx-py-3 aacx-bg-aacx-primary-600 aacx-text-white aacx-rounded-lg aacx-font-semibold hover:aacx-bg-aacx-primary-700 aacx-transition-colors">
                                    <?php _e('Dashboard', 'allaccessible'); ?> →
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="aacx-max-w-7xl aacx-mx-auto aacx-px-6 aacx-py-8">

                    <?php if ($has_account): ?>
                    <!-- Hero Stats -->
                    <?php aacb_render_dashboard_hero($account_tier, $site_options); ?>
                    <?php endif; ?>

                    <?php if ($show_success): ?>
                    <!-- Welcome Checklist - Redesigned -->
                    <div class="hidden aacx-bg-white aacx-rounded-xl aacx-shadow-xl aacx-border-2 aacx-border-aacx-secondary-200 aacx-overflow-hidden aacx-mb-8">
                        <div class="aacx-bg-aacx-secondary-50 aacx-px-8 aacx-py-6 aacx-border-b-2 aacx-border-aacx-secondary-200">
                            <div class="aacx-flex aacx-items-center aacx-gap-4">
                                <div class="aacx-w-14 aacx-h-14 aacx-bg-aacx-secondary-500 aacx-rounded-xl aacx-flex aacx-items-center aacx-justify-center aacx-shadow-lg">
                                    <svg class="aacx-w-8 aacx-h-8 aacx-text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="aacx-text-2xl aacx-font-bold aacx-text-aacx-slate-900">
                                        <?php _e('Welcome to AllAccessible!', 'allaccessible'); ?> 🎉
                                    </h3>
                                    <p class="aacx-text-sm aacx-text-aacx-slate-600 aacx-mt-1">
                                        <?php _e('Complete these steps to get started', 'allaccessible'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="aacx-p-8">
                            <div class="aacx-grid aacx-grid-cols-1 md:aacx-grid-cols-2 aacx-gap-6">

                                <!-- Step 1: Widget Active -->
                                <div class="aacx-flex aacx-items-start aacx-gap-4 aacx-p-4 aacx-bg-aacx-secondary-50 aacx-rounded-lg aacx-border aacx-border-aacx-secondary-200">
                                    <div class="aacx-w-8 aacx-h-8 aacx-bg-aacx-secondary-500 aacx-rounded-lg aacx-flex aacx-items-center aacx-justify-center aacx-flex-shrink-0">
                                        <svg class="aacx-w-5 aacx-h-5 aacx-text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="aacx-font-bold aacx-text-aacx-slate-900 aacx-mb-1"><?php _e('Widget is Active', 'allaccessible'); ?></p>
                                        <p class="aacx-text-sm aacx-text-aacx-slate-600"><?php _e('Your accessibility widget is live on all pages', 'allaccessible'); ?></p>
                                    </div>
                                </div>

                                <!-- Step 2: Preview Widget -->
                                <div class="aacx-flex aacx-items-start aacx-gap-4 aacx-p-4 aacx-bg-white aacx-rounded-lg aacx-border-2 aacx-border-aacx-gray-200 hover:aacx-border-aacx-primary-300 aacx-transition-all">
                                    <div class="aacx-w-8 aacx-h-8 aacx-bg-aacx-gray-200 aacx-rounded-lg aacx-flex aacx-items-center aacx-justify-center aacx-flex-shrink-0">
                                        <span class="aacx-text-sm aacx-font-bold aacx-text-aacx-slate-600">2</span>
                                    </div>
                                    <div>
                                        <p class="aacx-font-bold aacx-text-aacx-slate-900 aacx-mb-1"><?php _e('Preview Your Widget', 'allaccessible'); ?></p>
                                        <p class="aacx-text-sm aacx-text-aacx-slate-600">
                                            <a href="<?php echo esc_url($site_url . '/?aacb_preview=true'); ?>" target="_blank" class="aacx-text-aacx-primary-600 hover:aacx-text-aacx-primary-700 aacx-underline aacx-font-semibold">
                                                <?php _e('Click to see it in action', 'allaccessible'); ?> →
                                            </a>
                                        </p>
                                    </div>
                                </div>

                                <?php if ($is_paid_tier): ?>
                                <!-- Step 3: Customize -->
                                <div class="aacx-flex aacx-items-start aacx-gap-4 aacx-p-4 aacx-bg-white aacx-rounded-lg aacx-border-2 aacx-border-aacx-gray-200 hover:aacx-border-aacx-primary-300 aacx-transition-all">
                                    <div class="aacx-w-8 aacx-h-8 aacx-bg-aacx-gray-200 aacx-rounded-lg aacx-flex aacx-items-center aacx-justify-center aacx-flex-shrink-0">
                                        <span class="aacx-text-sm aacx-font-bold aacx-text-aacx-slate-600">3</span>
                                    </div>
                                    <div>
                                        <p class="aacx-font-bold aacx-text-aacx-slate-900 aacx-mb-1"><?php _e('Customize Widget', 'allaccessible'); ?></p>
                                        <p class="aacx-text-sm aacx-text-aacx-slate-600"><?php _e('Scroll down to adjust color, position, and size', 'allaccessible'); ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Step 4: Dashboard -->
                                <div class="aacx-flex aacx-items-start aacx-gap-4 aacx-p-4 aacx-bg-white aacx-rounded-lg aacx-border-2 aacx-border-aacx-gray-200 hover:aacx-border-aacx-primary-300 aacx-transition-all">
                                    <div class="aacx-w-8 aacx-h-8 aacx-bg-aacx-gray-200 aacx-rounded-lg aacx-flex aacx-items-center aacx-justify-center aacx-flex-shrink-0">
                                        <span class="aacx-text-sm aacx-font-bold aacx-text-aacx-slate-600"><?php echo $is_paid_tier ? '4' : '3'; ?></span>
                                    </div>
                                    <div>
                                        <p class="aacx-font-bold aacx-text-aacx-slate-900 aacx-mb-1"><?php _e('Explore Dashboard', 'allaccessible'); ?></p>
                                        <p class="aacx-text-sm aacx-text-aacx-slate-600">
                                            <a href="https://app.allaccessible.org" target="_blank" class="aacx-text-aacx-primary-600 hover:aacx-text-aacx-primary-700 aacx-underline aacx-font-semibold">
                                                <?php _e('Visit your dashboard', 'allaccessible'); ?> →
                                            </a>
                                        </p>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($has_account && !empty($exceeded_limits)): ?>
                    <!-- Limit Exceeded Alert -->
                    <div class="aacx-bg-orange-50 aacx-border aacx-border-orange-200 aacx-rounded-lg aacx-p-4 aacx-mb-6 aacx-flex aacx-items-start">
                        <svg class="aacx-w-6 aacx-h-6 aacx-text-orange-600 aacx-mr-3 aacx-flex-shrink-0 aacx-mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"/>
                        </svg>
                        <div>
                            <h3 class="aacx-font-bold aacx-text-orange-900 aacx-mb-1">
                                <?php _e('Usage Limit Exceeded', 'allaccessible'); ?>
                            </h3>
                            <p class="aacx-text-orange-800 aacx-mb-3">
                                <?php
                                $limit_names = array_map(function($limit) {
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
                            <a href="<?php echo esc_url($addon_url); ?>"
                               target="_blank"
                               class="aacx-inline-flex aacx-items-center aacx-px-4 aacx-py-2 aacx-bg-orange-600 aacx-text-white aacx-rounded-lg aacx-font-semibold hover:aacx-bg-orange-700 aacx-transition-colors aacx-text-sm">
                                <?php _e('Upgrade Plan', 'allaccessible'); ?>
                                <svg class="aacx-w-4 aacx-h-4 aacx-ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($has_account && $usage_summary): ?>
                    <!-- Usage Dashboard -->
                    <?php aacb_render_usage_dashboard($site_options, $account_tier, $addon_url, $audits_url); ?>
                    <?php endif; ?>

                    <?php if ($has_account && $audit_data): ?>
                    <!-- Accessibility Score (Premium/Trial with data) -->
                    <div class="aacx-bg-white aacx-rounded-lg aacx-shadow-sm aacx-border aacx-border-aacx-gray-200 aacx-p-8 aacx-mb-6">
                        <h2 class="aacx-text-2xl aacx-font-bold aacx-text-aacx-slate-900 aacx-mb-6">
                            <?php _e('Your Accessibility Score', 'allaccessible'); ?>
                        </h2>

                        <div class="aacx-grid aacx-grid-cols-1 md:aacx-grid-cols-4 aacx-gap-6 aacx-mb-6">
                            <!-- Overall Score -->
                            <div class="aacx-text-center">
                                <div class="aacx-inline-flex aacx-items-center aacx-justify-center aacx-w-32 aacx-h-32 aacx-rounded-full aacx-bg-gradient-to-br aacx-from-aacx-primary-500 aacx-to-aacx-primary-700 aacx-mb-3 aacx-shadow-xl">
                                    <span class="aacx-text-4xl aacx-font-black aacx-text-white">
                                        <?php echo esc_html($audit_data['score']); ?>%
                                    </span>
                                </div>
                                <p class="aacx-font-bold aacx-text-aacx-slate-900 aacx-text-lg"><?php _e('Overall', 'allaccessible'); ?></p>
                            </div>

                            <div class="aacx-text-center">
                                <div class="aacx-inline-flex aacx-items-center aacx-justify-center aacx-w-32 aacx-h-32 aacx-rounded-full aacx-bg-red-100 aacx-mb-3">
                                    <span class="aacx-text-4xl aacx-font-black aacx-text-red-600">
                                        <?php echo esc_html($audit_data['issues']['critical'] ?? 0); ?>
                                    </span>
                                </div>
                                <p class="aacx-font-semibold aacx-text-aacx-slate-900"><?php _e('Critical', 'allaccessible'); ?></p>
                            </div>

                            <div class="aacx-text-center">
                                <div class="aacx-inline-flex aacx-items-center aacx-justify-center aacx-w-32 aacx-h-32 aacx-rounded-full aacx-bg-orange-100 aacx-mb-3">
                                    <span class="aacx-text-4xl aacx-font-black aacx-text-orange-600">
                                        <?php echo esc_html($audit_data['issues']['serious'] ?? 0); ?>
                                    </span>
                                </div>
                                <p class="aacx-font-semibold aacx-text-aacx-slate-900"><?php _e('Serious', 'allaccessible'); ?></p>
                            </div>

                            <div class="aacx-text-center">
                                <div class="aacx-inline-flex aacx-items-center aacx-justify-center aacx-w-32 aacx-h-32 aacx-rounded-full aacx-bg-yellow-100 aacx-mb-3">
                                    <span class="aacx-text-4xl aacx-font-black aacx-text-yellow-600">
                                        <?php echo esc_html($audit_data['issues']['moderate'] ?? 0); ?>
                                    </span>
                                </div>
                                <p class="aacx-font-semibold aacx-text-aacx-slate-900"><?php _e('Moderate', 'allaccessible'); ?></p>
                            </div>
                        </div>

                        <div class="aacx-text-center">
                            <a href="https://app.allaccessible.org"
                               target="_blank"
                               class="aacx-inline-flex aacx-items-center aacx-px-8 aacx-py-3 aacx-bg-aacx-primary-600 aacx-text-white aacx-rounded-lg aacx-font-bold aacx-text-lg hover:aacx-bg-aacx-primary-700 aacx-shadow-lg hover:aacx-shadow-xl aacx-transition-all">
                                <?php _e('View Full Report', 'allaccessible'); ?>
                                <svg class="aacx-w-5 aacx-h-5 aacx-ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($has_account && $is_paid_tier && $site_options && !is_wp_error($site_options)): ?>
                    <!-- Widget Customizer for Paid Users -->
                    <?php aacb_render_widget_customizer($site_options, $widget_settings_url); ?>
                    <?php endif; ?>

                    <!-- Feature Comparison for Free Users -->
                    <?php if ($has_account && $account_tier === 'free'): ?>
                    <?php aacb_render_feature_comparison($addon_url); ?>
                    <?php endif; ?>

                    <!-- Smart Conversion CTA (after all content) -->
                    <?php if ($has_account && $site_options && !is_wp_error($site_options)): ?>
                    <?php aacb_render_smart_cta($site_options, $account_tier, $addon_url); ?>
                    <?php endif; ?>

                    <!-- No Account: Prompt to Complete Setup -->
                    <?php if (!$has_account): ?>
                    <div class="aacx-bg-white aacx-rounded-lg aacx-shadow-sm aacx-border aacx-border-aacx-gray-200 aacx-p-12 aacx-text-center">
                        <svg class="aacx-w-20 aacx-h-20 aacx-text-aacx-gray-400 aacx-mx-auto aacx-mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <h2 class="aacx-text-3xl aacx-font-bold aacx-text-aacx-slate-900 aacx-mb-3">
                            <?php _e('Complete Setup to Get Started', 'allaccessible'); ?>
                        </h2>
                        <p class="aacx-text-lg aacx-text-aacx-slate-600 aacx-mb-8 aacx-max-w-2xl aacx-mx-auto">
                            <?php _e('Your accessibility widget is ready to go! Complete the quick setup wizard to activate your widget and start making your site accessible.', 'allaccessible'); ?>
                        </p>
                        <a href="<?php echo admin_url('admin.php?page=allaccessible-wizard'); ?>"
                           class="aacx-inline-flex aacx-items-center aacx-px-8 aacx-py-4 aacx-bg-aacx-primary-600 aacx-text-white aacx-rounded-xl aacx-font-bold aacx-text-lg aacx-shadow-xl hover:aacx-bg-aacx-primary-700 hover:aacx-shadow-2xl hover:-aacx-translate-y-1 aacx-transition-all aacx-duration-200">
                            <?php _e('Start Setup Wizard', 'allaccessible'); ?> →
                        </a>
                    </div>
                    <?php endif; ?>

                    <!-- Widget Status / Upgrade Section -->
                    <?php if ($has_account && !$is_paid_tier): ?>
                    <!-- Free users: Show status cards only -->
                    <div class="aacx-bg-white aacx-rounded-lg aacx-shadow-sm aacx-border aacx-border-aacx-gray-200 aacx-p-8">
                        <h2 class="aacx-text-2xl aacx-font-bold aacx-text-aacx-slate-900 aacx-mb-6">
                            <?php _e('Your Account', 'allaccessible'); ?>
                        </h2>

                        <div class="aacx-grid aacx-grid-cols-1 md:aacx-grid-cols-3 aacx-gap-6">
                            <!-- Widget Status -->
                            <div class="aacx-border aacx-border-aacx-gray-200 aacx-rounded-lg aacx-p-6">
                                <div class="aacx-flex aacx-items-center aacx-mb-3">
                                    <svg class="aacx-w-6 aacx-h-6 aacx-text-aacx-secondary-600 aacx-mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                                    </svg>
                                    <h3 class="aacx-font-bold aacx-text-aacx-slate-900"><?php _e('Widget Status', 'allaccessible'); ?></h3>
                                </div>
                                <p class="aacx-text-sm aacx-text-aacx-secondary-600 aacx-font-semibold aacx-mb-2"><?php _e('✓ Active', 'allaccessible'); ?></p>
                                <p class="aacx-text-sm aacx-text-aacx-slate-600"><?php _e('Your widget is live on all pages', 'allaccessible'); ?></p>
                            </div>

                            <!-- Account -->
                            <div class="aacx-border aacx-border-aacx-gray-200 aacx-rounded-lg aacx-p-6">
                                <div class="aacx-flex aacx-items-center aacx-mb-3">
                                    <svg class="aacx-w-6 aacx-h-6 aacx-text-aacx-primary-600 aacx-mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                    </svg>
                                    <h3 class="aacx-font-bold aacx-text-aacx-slate-900"><?php _e('Account', 'allaccessible'); ?></h3>
                                </div>
                                <p class="aacx-text-sm aacx-text-aacx-slate-700 aacx-mb-2">
                                    <strong><?php _e('Free Forever', 'allaccessible'); ?></strong>
                                </p>
                                <p class="aacx-text-xs aacx-text-aacx-slate-500">
                                    <?php printf(__('ID: %s', 'allaccessible'), '<code>' . esc_html(substr($account_id, 0, 12)) . '...</code>'); ?>
                                </p>
                            </div>

                            <!-- Support -->
                            <div class="aacx-border aacx-border-aacx-gray-200 aacx-rounded-lg aacx-p-6">
                                <div class="aacx-flex aacx-items-center aacx-mb-3">
                                    <svg class="aacx-w-6 aacx-h-6 aacx-text-aacx-cyan-600 aacx-mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"/>
                                    </svg>
                                    <h3 class="aacx-font-bold aacx-text-aacx-slate-900"><?php _e('Need Help?', 'allaccessible'); ?></h3>
                                </div>
                                <p class="aacx-text-sm aacx-text-aacx-slate-600 aacx-mb-3">
                                    <?php _e('Login to your dashboard for support', 'allaccessible'); ?>
                                </p>
                                <a href="https://app.allaccessible.org" target="_blank" class="aacx-text-aacx-primary-600 hover:aacx-text-aacx-primary-700 aacx-font-semibold aacx-text-sm">
                                    <?php _e('Dashboard & Support', 'allaccessible'); ?> →
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render Account Info Page
     */
    public function render_account_page() {
        // Remove admin notices
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        remove_all_actions('user_admin_notices');
        remove_all_actions('network_admin_notices');

        $account_id = get_option('aacb_accountID');
        $has_account = !empty($account_id);

        // Get REAL tier from API
        $account_tier = 'unknown';
        if ($has_account) {
            $api_client = AllAccessible_ApiClient::get_instance();
            $account_tier = $api_client->get_subscription_tier();
        }
        ?>
        <div class="allaccessible-admin" style="margin: -10px 0 0 -20px;">
            <div class="aacx-min-h-screen aacx-bg-aacx-gray-50">

                <!-- Header -->
                <div class="aacx-bg-white aacx-border-b aacx-border-aacx-gray-200 aacx-shadow-sm">
                    <div class="aacx-max-w-7xl aacx-mx-auto aacx-px-6 aacx-py-6">
                        <h1 class="aacx-text-2xl aacx-font-bold aacx-text-aacx-slate-900">
                            <?php _e('Account Information', 'allaccessible'); ?>
                        </h1>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="aacx-max-w-4xl aacx-mx-auto aacx-px-6 aacx-py-8">

                    <?php if ($has_account): ?>
                    <div class="aacx-bg-white aacx-rounded-lg aacx-shadow-sm aacx-border aacx-border-aacx-gray-200 aacx-p-8">

                        <div class="aacx-mb-6 aacx-pb-6 aacx-border-b aacx-border-aacx-gray-200">
                            <h2 class="aacx-text-xl aacx-font-bold aacx-text-aacx-slate-900 aacx-mb-4">
                                <?php _e('Your Plan', 'allaccessible'); ?>
                            </h2>
                            <div class="aacx-flex aacx-items-center aacx-mb-4">
                                <?php
                                switch ($account_tier) {
                                    case 'trial':
                                        $badge_bg = 'aacx-bg-aacx-primary-100';
                                        $badge_text = 'aacx-text-aacx-primary-800';
                                        $tier_label = __('Premium Trial - 7 Days Free', 'allaccessible');
                                        break;
                                    case 'starter':
                                        $badge_bg = 'aacx-bg-aacx-secondary-100';
                                        $badge_text = 'aacx-text-aacx-secondary-800';
                                        $tier_label = __('Starter Plan - Paid', 'allaccessible');
                                        break;
                                    case 'enterprise':
                                        $badge_bg = 'aacx-bg-aacx-secondary-100';
                                        $badge_text = 'aacx-text-aacx-secondary-800';
                                        $tier_label = __('Enterprise Plan - Paid', 'allaccessible');
                                        break;
                                    case 'legacy':
                                        $badge_bg = 'aacx-bg-aacx-primary-100';
                                        $badge_text = 'aacx-text-aacx-primary-800';
                                        $tier_label = __('Legacy Premium', 'allaccessible');
                                        break;
                                    case 'free':
                                        $badge_bg = 'aacx-bg-aacx-gray-100';
                                        $badge_text = 'aacx-text-aacx-slate-800';
                                        $tier_label = __('Free Forever', 'allaccessible');
                                        break;
                                    default:
                                        $badge_bg = 'aacx-bg-aacx-gray-100';
                                        $badge_text = 'aacx-text-aacx-slate-800';
                                        $tier_label = sprintf(__('%s Account', 'allaccessible'), ucfirst($account_tier));
                                }
                                ?>
                                <div class="aacx-inline-block aacx-px-4 aacx-py-2 <?php echo esc_attr($badge_bg . ' ' . $badge_text); ?> aacx-rounded-lg aacx-font-bold aacx-text-lg">
                                    <?php echo esc_html($tier_label); ?>
                                </div>
                            </div>
                            <p class="aacx-text-aacx-slate-600">
                                <?php printf(__('Account ID: %s', 'allaccessible'), '<code class="aacx-bg-aacx-gray-100 aacx-px-2 aacx-py-1 aacx-rounded aacx-text-sm">' . esc_html($account_id) . '</code>'); ?>
                            </p>
                        </div>

                        <div class="aacx-mb-6 aacx-pb-6 aacx-border-b aacx-border-aacx-gray-200">
                            <h2 class="aacx-text-xl aacx-font-bold aacx-text-aacx-slate-900 aacx-mb-4">
                                <?php _e('Manage Your Settings', 'allaccessible'); ?>
                            </h2>
                            <p class="aacx-text-aacx-slate-600 aacx-mb-4">
                                <?php _e('Customize your widget appearance, manage accessibility features, and access advanced settings through your AllAccessible dashboard.', 'allaccessible'); ?>
                            </p>
                            <a href="https://app.allaccessible.org"
                               target="_blank"
                               class="aacx-inline-flex aacx-items-center aacx-px-6 aacx-py-3 aacx-bg-aacx-primary-600 aacx-text-white aacx-rounded-lg aacx-font-semibold hover:aacx-bg-aacx-primary-700 aacx-transition-colors">
                                <?php _e('Open Dashboard', 'allaccessible'); ?>
                                <svg class="aacx-w-5 aacx-h-5 aacx-ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        </div>

                        <div>
                            <h2 class="aacx-text-xl aacx-font-bold aacx-text-aacx-slate-900 aacx-mb-4">
                                <?php _e('Need Support?', 'allaccessible'); ?>
                            </h2>
                            <p class="aacx-text-aacx-slate-600 aacx-mb-4">
                                <?php _e('Our support team is here to help! Login to your dashboard to access support tickets, documentation, and live chat.', 'allaccessible'); ?>
                            </p>
                            <a href="https://app.allaccessible.org"
                               target="_blank"
                               class="aacx-text-aacx-primary-600 hover:aacx-text-aacx-primary-700 aacx-font-semibold">
                                <?php _e('Get Support', 'allaccessible'); ?> →
                            </a>
                        </div>

                        <div class="aacx-mt-6 aacx-pt-6 aacx-border-t aacx-border-aacx-gray-200">
                            <h2 class="aacx-text-xl aacx-font-bold aacx-text-aacx-slate-900 aacx-mb-4">
                                <?php _e('Reset Plugin Data', 'allaccessible'); ?>
                            </h2>
                            <p class="aacx-text-aacx-slate-600 aacx-mb-4">
                                <?php _e('This will clear all plugin settings and data, allowing you to start fresh without deleting the plugin. Your site will revert to the free version.', 'allaccessible'); ?>
                            </p>
                            <button type="button"
                                    id="aacb-reset-plugin-btn"
                                    class="aacx-px-6 aacx-py-3 aacx-bg-red-600 aacx-text-white aacx-rounded-lg aacx-font-semibold hover:aacx-bg-red-700 aacx-transition-colors">
                                <?php _e('Reset Plugin Data', 'allaccessible'); ?>
                            </button>
                            <div id="aacb-reset-message" class="aacx-mt-4"></div>
                        </div>

                        <script>
                        jQuery(document).ready(function($) {
                            $('#aacb-reset-plugin-btn').on('click', function() {
                                if (!confirm('<?php echo esc_js(__('Are you sure you want to reset all plugin data? This action cannot be undone and will disconnect your account.', 'allaccessible')); ?>')) {
                                    return;
                                }

                                var $btn = $(this).prop('disabled', true);
                                var originalText = $btn.text();
                                $btn.text('<?php echo esc_js(__('Resetting...', 'allaccessible')); ?>');

                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'aacb_reset_plugin_data',
                                        _wpnonce: '<?php echo wp_create_nonce('aacb_reset_plugin'); ?>'
                                    },
                                    success: function(response) {
                                        if (response.success) {
                                            $('#aacb-reset-message').html('<div class="aacx-p-4 aacx-bg-aacx-secondary-100 aacx-text-aacx-secondary-800 aacx-rounded-lg"><?php echo esc_js(__('Plugin data has been reset. Reloading...', 'allaccessible')); ?></div>');
                                            setTimeout(function() {
                                                window.location.href = '<?php echo admin_url('admin.php?page=allaccessible-wizard'); ?>';
                                            }, 1500);
                                        } else {
                                            $('#aacb-reset-message').html('<div class="aacx-p-4 aacx-bg-red-100 aacx-text-red-800 aacx-rounded-lg">' + (response.data || '<?php echo esc_js(__('Failed to reset plugin data.', 'allaccessible')); ?>') + '</div>');
                                            $btn.prop('disabled', false).text(originalText);
                                        }
                                    },
                                    error: function() {
                                        $('#aacb-reset-message').html('<div class="aacx-p-4 aacx-bg-red-100 aacx-text-red-800 aacx-rounded-lg"><?php echo esc_js(__('Failed to reset plugin data. Please try again.', 'allaccessible')); ?></div>');
                                        $btn.prop('disabled', false).text(originalText);
                                    }
                                });
                            });
                        });
                        </script>

                    </div>
                    <?php else: ?>
                    <!-- No Account -->
                    <div class="aacx-text-center aacx-py-12">
                        <svg class="aacx-w-16 aacx-h-16 aacx-text-aacx-gray-400 aacx-mx-auto aacx-mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <h2 class="aacx-text-2xl aacx-font-bold aacx-text-aacx-slate-900 aacx-mb-3">
                            <?php _e('No Account Connected', 'allaccessible'); ?>
                        </h2>
                        <p class="aacx-text-aacx-slate-600 aacx-mb-6">
                            <?php _e('Complete the setup wizard to activate your accessibility widget.', 'allaccessible'); ?>
                        </p>
                        <a href="<?php echo admin_url('admin.php?page=allaccessible-wizard'); ?>"
                           class="aacx-inline-block aacx-px-8 aacx-py-3 aacx-bg-aacx-primary-600 aacx-text-white aacx-rounded-lg aacx-font-semibold hover:aacx-bg-aacx-primary-700">
                            <?php _e('Start Setup', 'allaccessible'); ?> →
                        </a>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize settings page
add_action('plugins_loaded', function() {
    AllAccessible_SettingsPage::get_instance();
});
