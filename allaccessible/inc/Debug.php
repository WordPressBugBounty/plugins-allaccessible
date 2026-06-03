<?php
/**
 * AllAccessible — centralized debug-mode helper.
 */

if (!defined('ABSPATH')) { exit; }

final class AllAccessible_Debug {

    /**
     * Cache the toggle for the duration of a single request
     *
     * @var bool|null
     */
    private static $cached_enabled = null;

    /**
     * Is debug logging enabled? Reads aacb_options.debug_mode once per
     * request and caches the value.
     */
    public static function is_enabled(): bool {
        if (self::$cached_enabled === null) {
            $opts = get_option('aacb_options', array());
            self::$cached_enabled = !empty($opts['debug_mode']);
        }
        return self::$cached_enabled;
    }

    /**
     * Emit a console.groupCollapsed payload to the browser DevTools.
     * No-op when debug_mode is off.
     *
     * @param string $label   Surface name shown in the console group title.
     *                        Example: "EditorMetaBox — post 42".
     * @param mixed  $payload Anything JSON-encodable. Usually an
     *                        associative array of {requested, response,
     *                        render_branch, ...}.
     * @param array  $opts    Optional:
     *                        - 'group_method' => 'group'|'groupCollapsed'
     *                          (default 'groupCollapsed' to keep noise low)
     *                        - 'console_method' => 'log'|'table'
     *                          (default 'log'; use 'table' for row-shaped
     *                           arrays where columns help readability)
     */
    public static function console(string $label, $payload, array $opts = array()): void {
        if (!self::is_enabled()) {
            return;
        }

        $group_method   = isset($opts['group_method'])   ? (string) $opts['group_method']   : 'groupCollapsed';
        $console_method = isset($opts['console_method']) ? (string) $opts['console_method'] : 'log';

        // Whitelist to avoid injecting arbitrary method names into the
        // emitted JS. Anything not on this list falls back to safe defaults.
        $allowed_group   = array('group', 'groupCollapsed');
        $allowed_console = array('log', 'table', 'info', 'warn');
        if (!in_array($group_method, $allowed_group, true))     $group_method = 'groupCollapsed';
        if (!in_array($console_method, $allowed_console, true)) $console_method = 'log';

        $title_safe   = '[AllAccessible] ' . $label;
        $payload_json = wp_json_encode($payload);

        // Stable formatting so console output diff-reads cleanly between
        // pageloads when debugging. Title goes through wp_json_encode for
        // proper JS string escaping (handles quotes, backslashes, etc).
        $title_json = wp_json_encode($title_safe);

        echo '<script>(function(){console.' . esc_html($group_method) . '(' . $title_json . ');console.' . esc_html($console_method) . '(' . $payload_json . ');console.groupEnd();})();</script>';
    }

    /**
     * Convenience — dump common API-response context in one call.
     * Standardizes the shape across plugin surfaces so debug output is
     * grep-friendly in the console.
     */
    public static function api(string $surface, $request, $response): void {
        if (!self::is_enabled()) {
            return;
        }
        self::console($surface, array(
            'request'      => $request,
            'is_wp_error'  => is_wp_error($response),
            'error'        => is_wp_error($response) ? $response->get_error_message() : null,
            'response'     => is_wp_error($response) ? null : $response,
        ));
    }

    /**
     * Hard failure — something that should never happen and we want to
     * see aggregated across the installed base. Sent to error reporting.
     *
     * @param string                $surface  Where it happened (e.g.
     *                                        "PostLinkBackfill::link_single").
     * @param string|\Throwable|\WP_Error $err Message string, exception, or WP_Error.
     * @param array                 $context  Extra fields (post_id, batch_size, etc).
     */
    public static function error(string $surface, $err, array $context = array()): void {
        $message = self::error_to_string($err);
        $payload = array_merge(array('surface' => $surface), $context);

        // 1. error_log — always.
        error_log(sprintf('[AllAccessible] %s ERROR: %s', $surface, $message));

        // 2. Browser console — only when debug toggle on. Reuses the
        //    existing console pipeline so the user sees the same
        //    structured payload they'd see for normal debug dumps.
        if (self::is_enabled()) {
            self::console($surface . ' [error]', array_merge(
                array('message' => $message),
                $payload
            ), array('console_method' => 'warn'));
        }

        // 3. Error reporting. Throwables go to capture_exception (preserves
        //    stack trace); strings + WP_Errors go to capture_message.
        if (class_exists('AllAccessible_Sentry')) {
            if ($err instanceof \Throwable) {
                AllAccessible_Sentry::capture_exception($err, $payload);
            } else {
                AllAccessible_Sentry::capture_message(
                    sprintf('[%s] %s', $surface, $message),
                    'error',
                    $payload
                );
            }
        }
    }

    /**
     * Soft failure — something recoverable but worth noting. Error
     * reporting still captures (severity=warning) so we can see rate patterns.
     */
    public static function warn(string $surface, string $message, array $context = array()): void {
        $payload = array_merge(array('surface' => $surface), $context);

        error_log(sprintf('[AllAccessible] %s WARN: %s', $surface, $message));

        if (self::is_enabled()) {
            self::console($surface . ' [warn]', array_merge(
                array('message' => $message),
                $payload
            ), array('console_method' => 'warn'));
        }

        if (class_exists('AllAccessible_Sentry')) {
            AllAccessible_Sentry::capture_message(
                sprintf('[%s] %s', $surface, $message),
                'warning',
                $payload
            );
        }
    }

    /**
     * Informational — cron skips, cooldown hits, eligibility gates.
     * NEVER goes to error reporting (would flood it). error_log +
     * console only. Use this instead of bare error_log so a future
     * "route all noisy info elsewhere" decision is one-file.
     */
    public static function info(string $surface, string $message, array $context = array()): void {
        $payload = array_merge(array('surface' => $surface), $context);

        error_log(sprintf('[AllAccessible] %s INFO: %s', $surface, $message));

        if (self::is_enabled()) {
            self::console($surface . ' [info]', array_merge(
                array('message' => $message),
                $payload
            ));
        }
    }

    /**
     * Normalize anything throwable/wp-erroric/stringy into a single
     * human-readable line.
     */
    private static function error_to_string($err): string {
        if (is_string($err)) {
            return $err;
        }
        if ($err instanceof \Throwable) {
            return sprintf('%s: %s', get_class($err), $err->getMessage());
        }
        if (is_wp_error($err)) {
            return sprintf('WP_Error[%s]: %s', $err->get_error_code(), $err->get_error_message());
        }
        return wp_json_encode($err);
    }
}
