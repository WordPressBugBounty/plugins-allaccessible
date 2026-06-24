<?php
/**
 * Post-link backfill
 *
 * @package AllAccessible
 * @since   2.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AllAccessible_PostLinkBackfill {

    const CRON_HOOK         = 'aacb_post_link_backfill_run';
    const OFFSET_OPTION     = 'aacb_link_backfill_offset';
    const LAST_RUN_TRANSIENT = 'aacb_link_backfill_last_run';
    const BATCH_SIZE        = 100;        // backend cap
    const MAX_POSTS_PER_RUN = 5000;       // per-run safety
    const COOLDOWN_SECONDS  = 7 * DAY_IN_SECONDS; // weekly

    /**
     * Async per-post link hook.
     */
    const SINGLE_LINK_HOOK = 'aacb_post_link_single';

    public static function register() {
        add_action(self::CRON_HOOK, array(__CLASS__, 'run'));
        add_action('wp', array(__CLASS__, 'schedule_cron'));
        add_action('wp_ajax_aacb_backfill_run_now', array(__CLASS__, 'ajax_run_now'));
        add_action('transition_post_status', array(__CLASS__, 'on_publish_transition'), 10, 3);
        add_action(self::SINGLE_LINK_HOOK,   array(__CLASS__, 'link_single'), 10, 1);
    }

    /**
     * Wire the cron once. Idempotent — wp_next_scheduled returns the
     * existing timestamp if it's already booked.
     */
    public static function schedule_cron() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK);
        }
    }

    /**
     * Activation hook — fires the backfill immediately so a fresh install
     * doesn't have to wait a day for the first cron tick. Plugin
     * activation already calls this from the main bootstrap.
     */
    public static function on_activate() {
        // Reset offset so activation always re-walks from page 0.
        delete_option(self::OFFSET_OPTION);
        delete_transient(self::LAST_RUN_TRANSIENT);
        wp_schedule_single_event(time() + 60, self::CRON_HOOK);
    }

    /**
     * transition_post_status callback.
     */
    public static function on_publish_transition($new_status, $old_status, $post) {
        if ($new_status !== 'publish' || $old_status === 'publish') {
            return;
        }
        if (!$post || empty($post->ID) || empty($post->post_type)) {
            return;
        }
        if (!in_array($post->post_type, array('post', 'page'), true)) {
            return;
        }
        $args = array((int) $post->ID);
        if (wp_next_scheduled(self::SINGLE_LINK_HOOK, $args)) {
            return;
        }
        wp_schedule_single_event(time() + 5, self::SINGLE_LINK_HOOK, $args);
    }

    /**
     * Worker for SINGLE_LINK_HOOK.
     */
    public static function link_single($post_id) {
        $post_id = (int) $post_id;
        if ($post_id <= 0) return;
        if (!self::is_eligible()) {
            return; // free/legacy tier or no account — bulk cron also bails here
        }
        if (!class_exists('AllAccessible_ApiClient') || !class_exists('AllAccessible_UrlCanonicalizer')) {
            return;
        }

        $page_url = AllAccessible_UrlCanonicalizer::for_post($post_id);
        if ($page_url === '') {
            AllAccessible_Debug::warn('PostLinkBackfill::link_single', 'skipped — empty canonical URL', array('post_id' => $post_id));
            return;
        }

        $client = AllAccessible_ApiClient::get_instance();
        $result = $client->link_posts_batch(array(
            array('post_id' => $post_id, 'page_url' => $page_url),
        ));
        if (is_wp_error($result)) {
            if (self::is_benign_provisioning_error($result)) {
                AllAccessible_Debug::info('PostLinkBackfill::link_single', 'skipped — site not provisioned yet (will retry)', array('post_id' => $post_id));
            } elseif (self::is_transient_api_error($result)) {
                AllAccessible_Debug::warn('PostLinkBackfill::link_single', 'transient API failure: ' . $result->get_error_message(), array('post_id' => $post_id));
            } else {
                AllAccessible_Debug::error('PostLinkBackfill::link_single', $result, array('post_id' => $post_id));
            }
        }
    }

    /**
     * Is this WP_Error the expected "account exists but site not yet
     * provisioned" onboarding race?
     */
    private static function is_benign_provisioning_error($wp_error) {
        if (!is_wp_error($wp_error)) return false;
        $code = (string) $wp_error->get_error_code();
        if ($code === 'api_error_404') return true;
        // Credential-fetch race: the signing secret hasn't been retrieved yet
        // (account configured but /secret not fetched, or a transient blip the
        // transport layer already logged). get_plugin_secret() swallows the
        // underlying error to '', so error-reporting here just double-counts a
        // self-healing state — the secret re-fetches on the next run.
        if ($code === 'no_plugin_secret' || $code === 'no_account_id' || $code === 'bad_secret_response') return true;
        $data = $wp_error->get_error_data();
        if (is_array($data) && (int) ($data['status'] ?? 0) === 404) return true;
        // Belt-and-suspenders: match the message even if status drifts.
        return stripos((string) $wp_error->get_error_message(), 'no subdomain registered') !== false;
    }

    /**
     * Is this WP_Error a transient connectivity / upstream blip rather than a
     * real backfill bug? cURL 28 (timeout), connection reset, DNS, or a
     * gateway 5xx (502/503/504). These are expected background noise on a
     * weekly batch — the transport layer already warns on the timeout class
     * (see ApiClient::decode_json_response), so re-logging here at error
     * level just double-counts the same blip. Log at warn for rate
     * visibility instead. Mirror of AllAccessible_ApiClient::is_transient_failure.
     */
    private static function is_transient_api_error($wp_error) {
        if (!is_wp_error($wp_error)) return false;
        $code = (string) $wp_error->get_error_code();
        // HTTP 5xx gateway errors surface as api_error_502 / _503 / _504.
        if ($code === 'api_error_502' || $code === 'api_error_503' || $code === 'api_error_504') {
            return true;
        }
        $msg = (string) $wp_error->get_error_message();
        return strpos($msg, 'cURL error 28') !== false
            || stripos($msg, 'timed out') !== false
            || strpos($msg, 'cURL error 7') !== false
            || stripos($msg, 'connection reset') !== false
            || stripos($msg, 'could not resolve host') !== false;
    }

    /**
     * One backfill run.
     */
    public static function run($force = false) {
        if (!self::is_eligible()) {
            AllAccessible_Debug::info('PostLinkBackfill::run', 'skipped — ineligible (no account or free/legacy tier)');
            return array('status' => 'ineligible', 'processed' => 0);
        }
        if (!$force && get_transient(self::LAST_RUN_TRANSIENT)) {
            AllAccessible_Debug::info('PostLinkBackfill::run', 'skipped — weekly cooldown active');
            return array('status' => 'cooldown', 'processed' => 0);
        }

        if (!class_exists('AllAccessible_ApiClient')) {
            return array('status' => 'no_api_client', 'processed' => 0);
        }
        $client = AllAccessible_ApiClient::get_instance();
        if ($force) {
            delete_option(self::OFFSET_OPTION);
        }
        $offset           = (int) get_option(self::OFFSET_OPTION, 0);
        $processed        = 0;
        $total_linked     = 0;
        $total_skipped    = 0;
        $total_missing    = 0;
        $total_batches    = 0;
        $last_error       = null;

        while ($processed < self::MAX_POSTS_PER_RUN) {
            $q = new WP_Query(array(
                'post_type'      => array('post', 'page'),
                'post_status'    => 'publish',
                'posts_per_page' => self::BATCH_SIZE,
                'offset'         => $offset,
                'orderby'        => 'ID',
                'order'          => 'ASC',
                // Disable cache priming and term/meta fetch — we only
                // need ID + permalink, no other data on the post.
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                'fields'                 => 'ids',
            ));
            if (empty($q->posts)) break;

            $pairs = array();
            foreach ($q->posts as $post_id) {
                $page_url = AllAccessible_UrlCanonicalizer::for_post((int) $post_id);
                if ($page_url === '') continue;
                $pairs[] = array(
                    'post_id'  => (int) $post_id,
                    'page_url' => $page_url,
                );
            }

            if (!empty($pairs)) {
                $result = $client->link_posts_batch($pairs);
                if (is_wp_error($result)) {

                    $last_error = $result->get_error_message();
                    if (self::is_benign_provisioning_error($result)) {
                        AllAccessible_Debug::info('PostLinkBackfill::run', 'skipped — site not provisioned yet (will retry)', array(
                            'offset' => $offset,
                        ));
                    } elseif (self::is_transient_api_error($result)) {
                        // Transient timeout / gateway 5xx — the run just stops
                        // and resumes from this offset next week. Not a bug.
                        AllAccessible_Debug::warn('PostLinkBackfill::run', 'transient API failure — will resume next run: ' . $result->get_error_message(), array(
                            'offset'     => $offset,
                            'batch_size' => count($pairs),
                        ));
                    } else {
                        AllAccessible_Debug::error('PostLinkBackfill::run', $result, array(
                            'offset'     => $offset,
                            'batch_size' => count($pairs),
                        ));
                    }
                    break;
                }
                $inserted  = (int) ($result['inserted']  ?? 0);
                $updated   = (int) ($result['updated']   ?? 0);
                $unchanged = (int) ($result['unchanged'] ?? 0);
                $legacy_linked  = (int) ($result['linked']  ?? 0);
                $legacy_missing = (int) ($result['missing'] ?? 0);
                $skipped   = (int) ($result['skipped'] ?? 0);
                $total_linked  += $inserted + $updated + $legacy_linked;
                $total_skipped += $skipped + $unchanged;
                $total_missing += $legacy_missing;
                $total_batches++;
                AllAccessible_Debug::info('PostLinkBackfill::run', 'batch ok', array(
                    'offset'           => $offset,
                    'batch_size'       => count($pairs),
                    'inserted'         => $inserted,
                    'updated'          => $updated,
                    'unchanged'        => $unchanged,
                    'skipped'          => $skipped,
                    'legacy_linked'    => $legacy_linked,
                    'legacy_missing'   => $legacy_missing,
                ));
            }

            $offset    += self::BATCH_SIZE;
            $processed += count($q->posts);
            update_option(self::OFFSET_OPTION, $offset, false);

            // Last batch: WP_Query returned fewer than requested → done.
            if (count($q->posts) < self::BATCH_SIZE) {
                // Reset offset so the next weekly run starts fresh —
                // catches any posts published in between.
                delete_option(self::OFFSET_OPTION);
                break;
            }
        }

        if ($last_error === null) {
            set_transient(self::LAST_RUN_TRANSIENT, time(), self::COOLDOWN_SECONDS);
        }

        return array(
            'status'         => $last_error === null ? 'ok' : 'error',
            'processed'      => $processed,
            'total_batches'  => $total_batches,
            'total_linked'   => $total_linked,
            'total_skipped'  => $total_skipped,
            'total_missing'  => $total_missing,
            'last_error'     => $last_error,
            'next_offset'    => (int) get_option(self::OFFSET_OPTION, 0),
        );
    }

    /**
     * AJAX entry — wired in register().
     */
    public static function ajax_run_now() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'), 403);
        }
        check_ajax_referer('aacb_backfill_run_now', '_wpnonce');

        $summary = self::run(true);

        // Surface diagnostic context the admin needs to debug a no-op.
        $summary['tier']             = class_exists('AllAccessible_ApiClient')
            ? (string) AllAccessible_ApiClient::get_instance()->get_subscription_tier()
            : '';
        $summary['has_account']      = (bool) get_option('aacb_accountID');
        $summary['wizard_completed'] = (bool) get_option('aacb_wizard_completed');

        wp_send_json_success($summary);
    }

    /**
     * Tier gate — free + legacy V1 tiers have no audit data, so the
     * backend would refuse the linkage anyway. Skip to save HTTP cost.
     */
    private static function is_eligible(): bool {
        if (!get_option('aacb_accountID')) return false;
        if (!class_exists('AllAccessible_ApiClient')) return false;
        $tier = (string) AllAccessible_ApiClient::get_instance()->get_subscription_tier();
        return !in_array($tier, array('', 'free', 'legacy'), true);
    }
}
