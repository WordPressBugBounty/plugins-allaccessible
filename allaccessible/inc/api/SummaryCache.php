<?php
/**
 * Cache policy for slow, non-critical API aggregates (manifest summary).
 *
 * The manifest summary is served by a backend that can exceed 10s on a cold
 * start for large sites. Before 2.1.7 a failed fetch was not cached at all,
 * so every wp-admin Dashboard render re-fired the request and blocked for
 * the full client timeout. Policy:
 *
 *   remember_success  -> fresh copy (FRESH_TTL) + last-good copy (LAST_GOOD_TTL)
 *   remember_failure  -> sentinel (FAILURE_TTL); should_skip_network() is true
 *                        while it lives, so admin pages render immediately
 *   last_good         -> stale copy to show while the network is skipped
 *   forget            -> approve/revert invalidation drops every copy
 *
 * Pure transient plumbing, no WordPress beyond {get,set,delete}_transient,
 * so it is covered by trunk/tests/summary-cache-test.php without a WP boot.
 *
 * @package AllAccessible
 * @since 2.1.7
 */

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

final class AllAccessible_SummaryCache {
    /** Fresh copy lifetime. Matches the pre-2.1.7 CACHE_DURATION. */
    const FRESH_TTL     = 1800;      // 30 min
    /** How long a stale copy may be served while the API is failing. */
    const LAST_GOOD_TTL = 86400;     // 24 h
    /** Back-off after a failed fetch before the network is tried again. */
    const FAILURE_TTL   = 300;       // 5 min

    /** @return mixed|false */
    public static function fresh($key) {
        return get_transient($key);
    }

    /** @return mixed|false */
    public static function last_good($key) {
        return get_transient($key . '_lastgood');
    }

    /** @return bool */
    public static function should_skip_network($key) {
        return get_transient($key . '_failed') !== false;
    }

    public static function remember_success($key, $data) {
        set_transient($key, $data, self::FRESH_TTL);
        set_transient($key . '_lastgood', $data, self::LAST_GOOD_TTL);
        delete_transient($key . '_failed');
    }

    public static function remember_failure($key) {
        set_transient($key . '_failed', 1, self::FAILURE_TTL);
    }

    public static function forget($key) {
        delete_transient($key);
        delete_transient($key . '_lastgood');
        delete_transient($key . '_failed');
    }
}
