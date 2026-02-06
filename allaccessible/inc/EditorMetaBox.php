<?php
/**
 * AllAccessible Editor Meta Box
 *
 * Adds accessibility score meta box to post/page editor.
 * Shows page-specific audit results and quick actions.
 *
 * @package AllAccessible
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AllAccessible_EditorMetaBox {

    /**
     * Singleton instance
     */
    private static $instance = null;

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
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Add meta box to post/page editor
     */
    public function add_meta_box() {
        $premium = (bool) get_option('aacb_accountID');

        // Only show for premium users
        if (!$premium) {
            // Show upgrade prompt instead
            add_meta_box(
                'aacb_accessibility_score_upgrade',
                __('Accessibility Score', 'allaccessible'),
                array($this, 'render_upgrade_meta_box'),
                array('post', 'page'),
                'side',
                'high'
            );
            return;
        }

        add_meta_box(
            'aacb_accessibility_score',
            __('Accessibility Score', 'allaccessible'),
            array($this, 'render_meta_box'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    /**
     * Enqueue scripts for meta box
     */
    public function enqueue_scripts($hook) {
        // Only load on post/page editor
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }

        wp_enqueue_script(
            'allaccessible-editor-metabox',
            AACB_URL . '/src/admin/js/editor-metabox.js',
            array('jquery'),
            AACB_VERSION,
            true
        );

        wp_localize_script('allaccessible-editor-metabox', 'aacbEditorMeta', array(
            'rest_url' => rest_url('allaccessible/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'post_id' => get_the_ID(),
            'labels' => array(
                'loading' => __('Loading score...', 'allaccessible'),
                'error' => __('Error loading score', 'allaccessible'),
                'rescan_success' => __('Rescan initiated successfully', 'allaccessible'),
                'rescan_error' => __('Error initiating rescan', 'allaccessible'),
            ),
        ));
    }

    /**
     * Render accessibility score meta box
     */
    public function render_meta_box($post) {
        $post_url = get_permalink($post->ID);

        // If post is not published, show notice
        if ($post->post_status !== 'publish') {
            ?>
            <div class="aacb-metabox-wrapper">
                <p class="aacb-notice">
                    <?php _e('Publish this page to see its accessibility score.', 'allaccessible'); ?>
                </p>
            </div>
            <?php
            return;
        }

        ?>
        <div class="aacb-metabox-wrapper allaccessible-admin">
            <div id="aacb-metabox-loading" class="aacb-loading">
                <span class="spinner is-active" style="float: none;"></span>
                <p><?php _e('Loading accessibility score...', 'allaccessible'); ?></p>
            </div>

            <div id="aacb-metabox-content" style="display: none;">
                <!-- Score Display -->
                <div class="aacb-score-display aacx-text-center aacx-mb-4">
                    <div id="aacb-score-circle" class="aacb-score-circle aacx-mx-auto">
                        <div class="aacb-score-number aacx-text-4xl aacx-font-bold" id="aacb-score-value">
                            --
                        </div>
                        <div class="aacb-score-grade aacx-text-sm aacx-text-gray-600" id="aacb-score-grade">
                            -
                        </div>
                    </div>
                    <div class="aacb-wcag-level aacx-mt-2 aacx-text-sm" id="aacb-wcag-level">
                        <strong><?php _e('WCAG Level:', 'allaccessible'); ?></strong> <span id="aacb-wcag-value">--</span>
                    </div>
                </div>

                <!-- Issues Summary -->
                <div class="aacb-issues-summary aacx-mb-4">
                    <h4 class="aacx-text-sm aacx-font-semibold aacx-mb-2"><?php _e('Issues Found', 'allaccessible'); ?></h4>
                    <ul class="aacb-issues-list aacx-space-y-1">
                        <li class="aacb-issue-critical">
                            <span class="aacb-issue-dot aacx-bg-red-500"></span>
                            <span id="aacb-issues-critical">0</span>
                            <span class="aacx-text-sm"><?php _e('Critical', 'allaccessible'); ?></span>
                        </li>
                        <li class="aacb-issue-serious">
                            <span class="aacb-issue-dot aacx-bg-orange-500"></span>
                            <span id="aacb-issues-serious">0</span>
                            <span class="aacx-text-sm"><?php _e('Serious', 'allaccessible'); ?></span>
                        </li>
                        <li class="aacb-issue-moderate">
                            <span class="aacb-issue-dot aacx-bg-yellow-500"></span>
                            <span id="aacb-issues-moderate">0</span>
                            <span class="aacx-text-sm"><?php _e('Moderate', 'allaccessible'); ?></span>
                        </li>
                        <li class="aacb-issue-minor">
                            <span class="aacb-issue-dot aacx-bg-blue-500"></span>
                            <span id="aacb-issues-minor">0</span>
                            <span class="aacx-text-sm"><?php _e('Minor', 'allaccessible'); ?></span>
                        </li>
                    </ul>
                </div>

                <!-- Last Scan -->
                <div class="aacb-last-scan aacx-mb-4 aacx-text-xs aacx-text-gray-600" id="aacb-last-scan">
                    <?php _e('Last scanned:', 'allaccessible'); ?> <span id="aacb-last-scan-time">--</span>
                </div>

                <!-- Actions -->
                <div class="aacb-actions">
                    <button type="button" class="button button-secondary aacx-w-full aacx-mb-2" id="aacb-rescan-btn">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Rescan Page', 'allaccessible'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=allaccessible-issues&post_id=' . $post->ID); ?>" class="button button-primary aacx-w-full">
                        <?php _e('View Detailed Report', 'allaccessible'); ?>
                    </a>
                </div>
            </div>

            <div id="aacb-metabox-error" style="display: none;">
                <div class="notice notice-error inline">
                    <p><?php _e('Error loading accessibility score. Please try again.', 'allaccessible'); ?></p>
                </div>
                <button type="button" class="button button-secondary aacx-w-full" id="aacb-retry-btn">
                    <?php _e('Retry', 'allaccessible'); ?>
                </button>
            </div>
        </div>

        <style>
            .aacb-metabox-wrapper {
                padding: 12px;
            }
            .aacb-score-circle {
                width: 120px;
                height: 120px;
                border-radius: 50%;
                border: 8px solid #e5e7eb;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                position: relative;
            }
            .aacb-score-circle.score-excellent {
                border-color: #00aa62;
            }
            .aacb-score-circle.score-good {
                border-color: #54b8ff;
            }
            .aacb-score-circle.score-fair {
                border-color: #f59e0b;
            }
            .aacb-score-circle.score-poor {
                border-color: #ef4444;
            }
            .aacb-issues-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            .aacb-issues-list li {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 4px 0;
            }
            .aacb-issue-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                display: inline-block;
            }
            .aacb-loading {
                text-align: center;
                padding: 20px;
            }
            .aacb-notice {
                padding: 12px;
                background: #f0f6fc;
                border-left: 4px solid #54b8ff;
                margin: 0;
            }
        </style>
        <?php
    }

    /**
     * Render upgrade prompt for free users
     */
    public function render_upgrade_meta_box($post) {
        ?>
        <div class="aacb-metabox-wrapper allaccessible-admin">
            <div class="aacb-upgrade-prompt aacx-text-center aacx-py-4">
                <svg class="aacx-w-16 aacx-h-16 aacx-mx-auto aacx-mb-3 aacx-text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                </svg>
                <h4 class="aacx-text-lg aacx-font-semibold aacx-mb-2">
                    <?php _e('Premium Feature', 'allaccessible'); ?>
                </h4>
                <p class="aacx-text-sm aacx-text-gray-600 aacx-mb-4">
                    <?php _e('See accessibility scores for each page/post with a premium account.', 'allaccessible'); ?>
                </p>
                <a href="<?php echo admin_url('admin.php?page=allaccessible#pluginSettings'); ?>" class="button button-primary">
                    <?php _e('Upgrade to Premium', 'allaccessible'); ?>
                </a>
                <p class="aacx-text-xs aacx-text-gray-500 aacx-mt-3">
                    <?php _e('7-day free trial available', 'allaccessible'); ?>
                </p>
            </div>
        </div>
        <?php
    }
}

// Initialize editor meta box after WordPress is fully loaded
add_action('plugins_loaded', function() {
    AllAccessible_EditorMetaBox::get_instance();
});
