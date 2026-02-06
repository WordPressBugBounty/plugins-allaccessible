<?php
/**
 * Widget Loader for AllAccessible
 *
 * Handles frontend widget injection:
 * - Free users: Load default widget
 * - Premium users: Load customized widget from API
 *
 * @package AllAccessible
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

class AllAccessible_WidgetLoader {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_head', array($this, 'load_widget'));
    }

    /**
     * Load accessibility widget on frontend
     */
    public function load_widget() {
        // Don't load in admin or Divi/Elementor builders
        if (is_admin() || !empty($_GET['et_fb'])) {
            return;
        }

        $account_id = get_option('aacb_accountID');

        // Free users: Load free widget from CDN
        if (empty($account_id)) {
            $account_id = 'wp_vFtGhKjLm'; // Free for tracking purposes
            $widget_src = 'https://api.allaccessible.org/widget/wp_vFtGhKjLm.js';
        } else {
            // Premium users: Load custom widget from CDN
            $widget_src = 'https://api.allaccessible.org/widget/' . $account_id . '.js';
        }

        // Check for preview mode
        $is_preview = isset($_GET['aacb_preview']) && $_GET['aacb_preview'];
        ?>
        <script
            data-accessible-account-id="<?php echo esc_attr($account_id); ?>"
            data-site-url="<?php echo esc_url(get_bloginfo('wpurl')); ?>"
            id="allAccessibleWidget"
            src="<?php echo esc_url($widget_src); ?>"
            defer>
        </script>
        <?php if ($is_preview): ?>
        <script>
            window.addEventListener('load', function() {
                var aacbButton = document.getElementById('accessibility-trigger-button');
                if (aacbButton) {
                    aacbButton.click();
                }
            });
        </script>
        <?php endif;
    }
}

// Initialize widget loader
add_action('plugins_loaded', function() {
    AllAccessible_WidgetLoader::get_instance();
});
