<?php
/**
 * Dashboard Banner
 *
 * Shows admin notice prompting users to complete wizard
 *
 * @package AllAccessible
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

class AllAccessible_DashboardBanner {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_notices', array($this, 'show_setup_notice'));
        add_action('wp_ajax_aacb_dismiss_setup_notice', array($this, 'dismiss_notice'));
    }

    /**
     * Show setup notice if wizard not completed
     */
    public function show_setup_notice() {
        // Only show if:
        // - Wizard not completed
        // - No account ID
        // - User has capability
        // - Notice not dismissed
        if (
            get_option('aacb_wizard_completed') ||
            get_option('aacb_accountID') ||
            !current_user_can('manage_options') ||
            get_option('aacb_setup_notice_dismissed')
        ) {
            return;
        }

        // Don't show on AllAccessible pages (they'll see the wizard)
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'allaccessible') !== false) {
            return;
        }

        $wizard_url = admin_url('admin.php?page=allaccessible-wizard');
        ?>
        <div class="notice notice-info is-dismissible aacb-setup-notice" data-dismissible="aacb-setup-notice">
            <div style="display: flex; align-items: center; padding: 8px 0;">
                <div style="flex-shrink: 0; margin-right: 16px;">
                    <img src="<?php echo esc_url(AACB_IMG . 'bug.svg'); ?>"
                         alt="AllAccessible"
                         style="width: 48px; height: 48px;">
                </div>
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 4px 0; font-size: 16px; font-weight: 600;">
                        <?php _e('Get the Most Out of AllAccessible! 🚀', 'allaccessible'); ?>
                    </h3>
                    <p style="margin: 0; font-size: 14px;">
                        <?php _e('Complete the quick 2-minute setup to activate your accessibility widget and unlock premium features.', 'allaccessible'); ?>
                    </p>
                </div>
                <div style="flex-shrink: 0; margin-left: 16px;">
                    <a href="<?php echo esc_url($wizard_url); ?>"
                       class="button button-primary button-hero"
                       style="padding: 12px 24px; height: auto; line-height: 1.4; display: inline-flex; align-items: center; text-decoration: none;">
                        <?php _e('Start Setup', 'allaccessible'); ?> →
                    </a>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Handle dismiss
            $('.aacb-setup-notice').on('click', '.notice-dismiss', function() {
                $.post(ajaxurl, {
                    action: 'aacb_dismiss_setup_notice',
                    nonce: '<?php echo wp_create_nonce('aacb_dismiss_setup_notice'); ?>'
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX handler to dismiss notice
     */
    public function dismiss_notice() {
        check_ajax_referer('aacb_dismiss_setup_notice', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error();
        }

        update_option('aacb_setup_notice_dismissed', true);
        wp_send_json_success();
    }
}

// Initialize dashboard banner
add_action('plugins_loaded', function() {
    AllAccessible_DashboardBanner::get_instance();
});
