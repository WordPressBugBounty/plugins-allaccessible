<?php
/**
 * Deactivation Survey
 *
 * Modern modal that appears when user deactivates plugin
 * Gathers feedback and offers help before deactivation
 *
 * @package AllAccessible
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

class AllAccessible_DeactivationSurvey {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_footer', array($this, 'render_modal'));
        add_action('wp_ajax_aacb_deactivation_feedback', array($this, 'submit_feedback'));
    }

    /**
     * Render deactivation modal
     */
    public function render_modal() {
        // Only on plugins page
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'plugins') {
            return;
        }
        ?>
        <style>
        #aacb-deactivation-modal {
            display: none;
            position: fixed;
            z-index: 999999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
        }

        #aacb-deactivation-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .aacb-modal-content {
            background: white;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .aacb-reason-input {
            display: none;
            margin-top: 12px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
        }

        .aacb-reason-input.active {
            display: block;
        }
        </style>

        <div id="aacb-deactivation-modal">
            <div class="aacb-modal-content">
                <!-- Header -->
                <div style="padding: 24px; border-bottom: 1px solid #e2e8f0;">
                    <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 12px;">
                        <img src="<?php echo esc_url(AACB_IMG . 'bug.svg'); ?>" alt="AllAccessible" style="width: 48px; height: 48px;">
                        <div>
                            <h2 style="margin: 0; font-size: 24px; font-weight: 700; color: #1e293b;">
                                <?php _e('Quick Feedback', 'allaccessible'); ?>
                            </h2>
                            <p style="margin: 4px 0 0 0; font-size: 14px; color: #64748b;">
                                <?php _e('Help us improve AllAccessible', 'allaccessible'); ?>
                            </p>
                        </div>
                    </div>
                    <p style="margin: 0; font-size: 14px; color: #475569;">
                        <?php _e('If you have a moment, please let us know why you\'re deactivating AllAccessible. All submissions are anonymous and help us improve.', 'allaccessible'); ?>
                    </p>
                </div>

                <!-- Body -->
                <div style="padding: 24px;">
                    <form id="aacb-deactivation-form">
                        <div style="display: flex; flex-direction: column; gap: 12px;">

                            <!-- Reason 1: Stopped Working -->
                            <label style="display: flex; align-items: start; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s;" class="aacb-reason-option">
                                <input type="radio" name="reason" value="stopped-working" style="margin-top: 4px; margin-right: 12px;">
                                <div style="flex: 1;">
                                    <strong style="color: #1e293b;"><?php _e('The plugin stopped working', 'allaccessible'); ?></strong>
                                    <div class="aacb-reason-input" data-reason="stopped-working">
                                        <p style="margin: 0 0 8px 0; font-size: 13px; color: #64748b;">
                                            <?php printf(__('We\'re sorry! Please %scontact support%s or describe the issue:', 'allaccessible'), '<a href="https://app.allaccessible.org" target="_blank" style="color: #1d4ed8; text-decoration: underline;">', '</a>'); ?>
                                        </p>
                                        <textarea name="stopped-working-details" rows="3" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;"></textarea>
                                    </div>
                                </div>
                            </label>

                            <!-- Reason 2: Missing Features -->
                            <label style="display: flex; align-items: start; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s;" class="aacb-reason-option">
                                <input type="radio" name="reason" value="missing-features" style="margin-top: 4px; margin-right: 12px;">
                                <div style="flex: 1;">
                                    <strong style="color: #1e293b;"><?php _e('Missing features I need', 'allaccessible'); ?></strong>
                                    <div class="aacb-reason-input" data-reason="missing-features">
                                        <p style="margin: 0 0 8px 0; font-size: 13px; color: #64748b;">
                                            <?php _e('What features would make you stay?', 'allaccessible'); ?>
                                        </p>
                                        <textarea name="missing-features-details" rows="3" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;"></textarea>
                                    </div>
                                </div>
                            </label>

                            <!-- Reason 3: Found Better -->
                            <label style="display: flex; align-items: start; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s;" class="aacb-reason-option">
                                <input type="radio" name="reason" value="found-better" style="margin-top: 4px; margin-right: 12px;">
                                <div style="flex: 1;">
                                    <strong style="color: #1e293b;"><?php _e('Found a better plugin', 'allaccessible'); ?></strong>
                                    <div class="aacb-reason-input" data-reason="found-better">
                                        <p style="margin: 0 0 8px 0; font-size: 13px; color: #64748b;">
                                            <?php _e('Which plugin are you switching to?', 'allaccessible'); ?>
                                        </p>
                                        <input type="text" name="found-better-details" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;" placeholder="Plugin name...">
                                    </div>
                                </div>
                            </label>

                            <!-- Reason 4: Temporary -->
                            <label style="display: flex; align-items: start; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s;" class="aacb-reason-option">
                                <input type="radio" name="reason" value="temporary" style="margin-top: 4px; margin-right: 12px;">
                                <div style="flex: 1;">
                                    <strong style="color: #1e293b;"><?php _e('Temporary - troubleshooting an issue', 'allaccessible'); ?></strong>
                                </div>
                            </label>

                            <!-- Reason 5: No longer needed -->
                            <label style="display: flex; align-items: start; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s;" class="aacb-reason-option">
                                <input type="radio" name="reason" value="no-longer-needed" style="margin-top: 4px; margin-right: 12px;">
                                <div style="flex: 1;">
                                    <strong style="color: #1e293b;"><?php _e('I don\'t need accessibility features anymore', 'allaccessible'); ?></strong>
                                </div>
                            </label>

                            <!-- Reason 6: Other -->
                            <label style="display: flex; align-items: start; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s;" class="aacb-reason-option">
                                <input type="radio" name="reason" value="other" style="margin-top: 4px; margin-right: 12px;">
                                <div style="flex: 1;">
                                    <strong style="color: #1e293b;"><?php _e('Other', 'allaccessible'); ?></strong>
                                    <div class="aacb-reason-input" data-reason="other">
                                        <textarea name="other-details" rows="3" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;" placeholder="<?php esc_attr_e('Please tell us more...', 'allaccessible'); ?>"></textarea>
                                    </div>
                                </div>
                            </label>

                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div style="padding: 20px 24px; border-top: 1px solid #e2e8f0; background: #f8fafc; display: flex; justify-content: space-between; align-items: center; border-radius: 0 0 12px 12px;">
                    <button id="aacb-deactivate-skip" style="padding: 10px 20px; border: 2px solid #cbd5e1; background: white; color: #64748b; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px;">
                        <?php _e('Skip & Deactivate', 'allaccessible'); ?>
                    </button>
                    <div style="display: flex; gap: 12px;">
                        <button id="aacb-deactivate-cancel" style="padding: 10px 20px; border: 2px solid #cbd5e1; background: white; color: #475569; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px;">
                            <?php _e('Cancel', 'allaccessible'); ?>
                        </button>
                        <button id="aacb-deactivate-submit" style="padding: 10px 24px; background: #1d4ed8; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px;">
                            <?php _e('Submit & Deactivate', 'allaccessible'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            let deactivateUrl = '';

            // Intercept deactivation link
            $('tr[data-slug="allaccessible"] .deactivate a').on('click', function(e) {
                e.preventDefault();
                deactivateUrl = $(this).attr('href');
                $('#aacb-deactivation-modal').addClass('active');
            });

            // Show/hide reason inputs
            $('input[name="reason"]').on('change', function() {
                // Hide all inputs
                $('.aacb-reason-input').removeClass('active');
                $('.aacb-reason-option').css('border-color', '#e2e8f0');

                // Show selected input
                const reason = $(this).val();
                $(this).closest('.aacb-reason-option').css('border-color', '#1d4ed8');
                $('.aacb-reason-input[data-reason="' + reason + '"]').addClass('active');
            });

            // Cancel button
            $('#aacb-deactivate-cancel').on('click', function() {
                $('#aacb-deactivation-modal').removeClass('active');
            });

            // Skip & Deactivate
            $('#aacb-deactivate-skip').on('click', function() {
                window.location.href = deactivateUrl;
            });

            // Submit & Deactivate
            $('#aacb-deactivate-submit').on('click', function() {
                const $btn = $(this);
                const reason = $('input[name="reason"]:checked').val();

                if (!reason) {
                    alert('<?php esc_js(_e('Please select a reason', 'allaccessible')); ?>');
                    return;
                }

                // Get comment from the active reason input
                let comment = '';
                const activeInput = $('.aacb-reason-input.active');
                if (activeInput.length) {
                    const textarea = activeInput.find('textarea');
                    const input = activeInput.find('input[type="text"]');
                    comment = textarea.length ? textarea.val() : (input.length ? input.val() : '');
                }

                console.log('🔍 Deactivation Feedback:', {reason, comment});

                // Show loading
                $btn.text('<?php esc_js(_e('Submitting...', 'allaccessible')); ?>').prop('disabled', true);

                // Submit feedback - use 'comment' field name to match Symfony
                $.post(ajaxurl, {
                    action: 'aacb_deactivation_feedback',
                    reason: reason,
                    comment: comment,
                    nonce: '<?php echo wp_create_nonce('aacb_deactivation'); ?>'
                }, function(response) {
                    console.log('✅ Feedback submitted:', response);
                    window.location.href = deactivateUrl;
                }).fail(function(xhr) {
                    console.error('❌ Feedback failed:', xhr);
                    window.location.href = deactivateUrl;
                });
            });

            // Close on background click
            $('#aacb-deactivation-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).removeClass('active');
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Submit deactivation feedback
     */
    public function submit_feedback() {
        check_ajax_referer('aacb_deactivation', 'nonce');

        $reason = sanitize_text_field($_POST['reason'] ?? '');
        $comment = sanitize_textarea_field($_POST['comment'] ?? '');
        $site_url = get_bloginfo('url');

        // Prepare plugin data as JSON (matching Symfony endpoint)
        $plugin_data = json_encode(array(
            'version' => AACB_VERSION,
            'url' => $site_url,
        ));

        // Build form data matching Symfony /api/wp-feedback endpoint exactly like old plugin
        $form_data = array(
            'type' => 'deactivation',
            'reason' => $reason,
            'plugin' => $plugin_data,
        );

        // Add comment field with pattern: comment-{reason}
        // This matches old plugin format and Symfony's findNonEmptyCommentKeys expects this
        if (!empty($comment)) {
            $comment_field = 'comment-' . $reason; // e.g., 'comment-stopped-working'
            $form_data[$comment_field] = $comment;
        }

        // Send to Symfony API endpoint (URL-encoded form data)
        wp_remote_post('https://app.allaccessible.org/api/wp-feedback', array(
            'body' => $form_data,
            'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
            'timeout' => 5,
        ));

        wp_send_json_success();
    }
}

// Initialize deactivation survey
add_action('plugins_loaded', function() {
    AllAccessible_DeactivationSurvey::get_instance();
});
