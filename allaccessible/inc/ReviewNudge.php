<?php
/**
 * AllAccessible — review nudge.
 */

if (!defined('ABSPATH')) { exit; }

final class AllAccessible_ReviewNudge {

    const OPTION         = 'aacb_review_nudge';
    const FIRST_SEEN_OPT = 'aacb_first_activated_at';
    const REVIEW_URL     = 'https://wordpress.org/support/plugin/allaccessible/reviews/#new-post';
    const FEEDBACK_URL   = 'https://www.allaccessible.org/feedback';
    const MIN_AGE_DAYS   = 14;
    const SNOOZE_DAYS    = 14;
    const AJAX_ACTION    = 'aacb_review_nudge_action';

    public static function register() {
        add_action('admin_init', array(__CLASS__, 'stamp_first_seen'));
        add_action('admin_notices', array(__CLASS__, 'maybe_render'));
        add_action('wp_ajax_' . self::AJAX_ACTION, array(__CLASS__, 'ajax_handle'));
    }

    public static function stamp_first_seen() {
        if (!get_option(self::FIRST_SEEN_OPT)) {
            update_option(self::FIRST_SEEN_OPT, time(), false);
        }
    }

    /**
     * Should the nudge show right now?
     */
    private static function should_show(): bool {
        if (!current_user_can('manage_options')) return false;

        // Engaged user only — account linked.
        if (empty(get_option('aacb_accountID'))) return false;

        // State check: done forever, or snoozed into the future.
        $state = get_option(self::OPTION, array());
        if (!empty($state['done'])) return false;
        if (!empty($state['snooze_until']) && time() < (int) $state['snooze_until']) return false;

        // Age gate.
        $first = (int) get_option(self::FIRST_SEEN_OPT, 0);
        if ($first === 0) return false; // not stamped yet
        if (time() - $first < self::MIN_AGE_DAYS * DAY_IN_SECONDS) return false;

        // Screen gate — only AllAccessible pages + Plugins + Dashboard.
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $id = $screen ? (string) $screen->id : '';
        $allowed = (strpos($id, 'allaccessible') !== false)
            || in_array($id, array('plugins', 'dashboard'), true);
        if (!$allowed) return false;

        return true;
    }

    public static function maybe_render() {
        if (!self::should_show()) return;

        $nonce = wp_create_nonce(self::AJAX_ACTION);
        $ajax  = admin_url('admin-ajax.php');
        ?>
        <div class="notice notice-info is-dismissible aacb-review-nudge"
             data-nonce="<?php echo esc_attr($nonce); ?>"
             data-ajax="<?php echo esc_url($ajax); ?>"
             data-review-url="<?php echo esc_url(self::REVIEW_URL); ?>"
             style="border-left-color:#7c3aed;">

            <div class="aacb-nudge-step" data-step="sentiment">
                <p style="font-size:14px;line-height:1.6;margin:0.5em 0;">
                    <strong><?php esc_html_e('How\'s your experience with AllAccessible so far?', 'allaccessible'); ?></strong>
                </p>
                <p style="margin:0.5em 0 1em;">
                    <button type="button" class="button button-primary aacb-nudge-sentiment" data-sentiment="good">
                        <?php esc_html_e('Great 😊', 'allaccessible'); ?>
                    </button>
                    <button type="button" class="button aacb-nudge-sentiment" data-sentiment="bad" style="margin-left:6px;">
                        <?php esc_html_e('Could be better', 'allaccessible'); ?>
                    </button>
                    <button type="button" class="button-link aacb-nudge-snooze" style="margin-left:10px;color:#646970;">
                        <?php esc_html_e('Maybe later', 'allaccessible'); ?>
                    </button>
                </p>
            </div>

            <!-- STEP 2A: happy → review CTA -->
            <div class="aacb-nudge-step" data-step="happy" hidden>
                <p style="font-size:14px;line-height:1.6;margin:0.5em 0;">
                    <strong><?php esc_html_e('That\'s wonderful to hear!', 'allaccessible'); ?></strong>
                    <?php esc_html_e('A quick review on WordPress.org helps other site owners find us — it takes about 30 seconds and means a lot.', 'allaccessible'); ?>
                </p>
                <p style="margin:0.5em 0 1em;">
                    <a href="<?php echo esc_url(self::REVIEW_URL); ?>" target="_blank" rel="noopener"
                       class="button button-primary aacb-nudge-review">
                        <?php esc_html_e('Leave a review', 'allaccessible'); ?> ★
                    </a>
                    <button type="button" class="button aacb-nudge-already">
                        <?php esc_html_e('I already did', 'allaccessible'); ?>
                    </button>
                </p>
            </div>

            <!-- STEP 2B: unhappy → inline feedback form (no navigation) -->
            <div class="aacb-nudge-step" data-step="unhappy" hidden>
                <p style="font-size:14px;line-height:1.6;margin:0.5em 0;">
                    <strong><?php esc_html_e('Sorry to hear that — we\'d like to make it right.', 'allaccessible'); ?></strong>
                    <?php esc_html_e('Tell us what\'s not working and we\'ll follow up. This goes straight to our team.', 'allaccessible'); ?>
                </p>
                <p style="margin:0.5em 0;">
                    <textarea class="aacb-nudge-comment" rows="3" style="width:100%;max-width:560px;"
                              placeholder="<?php esc_attr_e('What could be better?', 'allaccessible'); ?>"></textarea>
                </p>
                <p style="margin:0.5em 0 1em;">
                    <button type="button" class="button button-primary aacb-nudge-send-feedback">
                        <?php esc_html_e('Send feedback', 'allaccessible'); ?>
                    </button>
                    <a href="<?php echo esc_url(AACB_SUPPORT); ?>" target="_blank" rel="noopener"
                       class="button" style="margin-left:6px;">
                        <?php esc_html_e('Contact support', 'allaccessible'); ?>
                    </a>
                </p>
            </div>

            <!-- STEP 3: thank-you (shown after feedback sent or already-reviewed) -->
            <div class="aacb-nudge-step" data-step="thanks" hidden>
                <p style="font-size:14px;line-height:1.6;margin:0.5em 0 1em;">
                    <strong><?php esc_html_e('Thank you — we appreciate you.', 'allaccessible'); ?></strong>
                </p>
            </div>
        </div>
        <script>
        (function(){
            var box = document.querySelector('.aacb-review-nudge');
            if (!box) return;

            function send(payload){
                var fd = new FormData();
                fd.append('action', '<?php echo esc_js(self::AJAX_ACTION); ?>');
                fd.append('_ajax_nonce', box.dataset.nonce);
                Object.keys(payload).forEach(function(k){ fd.append(k, payload[k]); });
                return fetch(box.dataset.ajax, {method:'POST', credentials:'same-origin', body:fd}).catch(function(){});
            }
            function showStep(name){
                box.querySelectorAll('.aacb-nudge-step').forEach(function(el){
                    el.hidden = (el.dataset.step !== name);
                });
            }

            // Step 1 — sentiment branch
            box.querySelectorAll('.aacb-nudge-sentiment').forEach(function(btn){
                btn.addEventListener('click', function(){
                    var s = btn.dataset.sentiment;
                    send({ nudge_act: 'sentiment', sentiment: s });
                    showStep(s === 'good' ? 'happy' : 'unhappy');
                });
            });

            // "Maybe later" — snooze 14d
            var snooze = box.querySelector('.aacb-nudge-snooze');
            if (snooze) snooze.addEventListener('click', function(){
                send({ nudge_act: 'snooze' });
                box.style.display = 'none';
            });

            // Happy → review link (marks done) / already did
            var review = box.querySelector('.aacb-nudge-review');
            if (review) review.addEventListener('click', function(){
                send({ nudge_act: 'review' }); // link opens via anchor default
            });
            var already = box.querySelector('.aacb-nudge-already');
            if (already) already.addEventListener('click', function(){
                send({ nudge_act: 'done' });
                showStep('thanks');
                setTimeout(function(){ box.style.display = 'none'; }, 2500);
            });

            // Unhappy → inline feedback to existing wp-feedback endpoint
            var sendFb = box.querySelector('.aacb-nudge-send-feedback');
            if (sendFb) sendFb.addEventListener('click', function(){
                var ta = box.querySelector('.aacb-nudge-comment');
                var comment = ta ? ta.value.trim() : '';
                sendFb.disabled = true;
                send({ nudge_act: 'feedback', comment: comment }).then(function(){
                    showStep('thanks');
                    setTimeout(function(){ box.style.display = 'none'; }, 2500);
                });
            });

            // WP native × = snooze (forgiving, re-asks in 14d)
            box.addEventListener('click', function(e){
                if (e.target.classList.contains('notice-dismiss')) send({ nudge_act: 'snooze' });
            });
        })();
        </script>
        <?php
    }

    public static function ajax_handle() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'forbidden'), 403);
        }
        check_ajax_referer(self::AJAX_ACTION);

        $act   = isset($_POST['nudge_act']) ? sanitize_key($_POST['nudge_act']) : '';
        $state = (array) get_option(self::OPTION, array());

        switch ($act) {
            case 'sentiment':
                // Record sentiment for analytics; don't mark done yet —
                // the user still has a step-2 action to take.
                $state['sentiment'] = sanitize_key($_POST['sentiment'] ?? '');
                $state['sentiment_at'] = time();
                break;

            case 'review':   // clicked the wp.org review link
            case 'done':     // "I already did"
                $state['done'] = true;
                break;

            case 'feedback':
                $state['done'] = true;
                $comment = isset($_POST['comment']) ? sanitize_textarea_field($_POST['comment']) : '';
                self::relay_feedback($comment);
                break;

            case 'snooze':
                $state['snooze_until'] = time() + self::SNOOZE_DAYS * DAY_IN_SECONDS;
                break;

            default:
                wp_send_json_error(array('message' => 'unknown action'), 400);
        }

        update_option(self::OPTION, $state, false);
        wp_send_json_success(array('act' => $act));
    }

    /**
     * Relay review-nudge feedback to the existing AllAccessible feedback
     * endpoint — same one DeactivationSurvey uses, same form shape
     */
    private static function relay_feedback(string $comment) {
        if ($comment === '') return;
        if (!function_exists('wp_remote_post')) return;

        $plugin_data = wp_json_encode(array(
            'version' => defined('AACB_VERSION') ? AACB_VERSION : 'unknown',
            'url'     => get_bloginfo('url'),
        ));

        wp_remote_post('https://app.allaccessible.org/api/wp-feedback', array(
            'body' => array(
                'type'                  => 'review_nudge',
                'reason'                => 'could_be_better',
                'plugin'                => $plugin_data,
                'comment-could_be_better' => $comment,
            ),
            'headers'  => array('Content-Type' => 'application/x-www-form-urlencoded'),
            'timeout'  => 5,
            'blocking' => false,
        ));
    }
}
