<?php


if (!defined('ABSPATH')) { exit; }

final class AllAccessible_Sentry {

    
    const DEFAULT_DSN = 'https://09483018ddcc1c2ce3afa01acd5f0318@o4509626671759361.ingest.us.sentry.io/4511461234704384';

    
    const CLIENT_NAME = 'allaccessible-wp';

    /**
     * Per-request throttle — same exception fingerprint shouldn't send
     * more than once per pageload (a cascading failure can throw 100
     * times in one request). Keyed by message+file+line hash.
     */
    private static $seen_fingerprints = array();

    /**
     * Per-hour throttle — even across requests, cap total events from
     * one install at this number to avoid runaway sites flooding the
     * project quota. Transient-backed.
     */
    const HOURLY_CAP = 50;
    const HOURLY_CAP_TRANSIENT = 'aacb_sentry_hourly_count';

    /**
     * Initialized flag + parsed DSN parts. Set in init().
     */
    private static $initialized = false;
    private static $dsn_parsed  = null;
    private static $public_key  = '';
    private static $project_id  = '';
    private static $store_url   = '';

    /**
     * Breadcrumbs buffer — last 20 events captured before a real error.
     * Symmetric ring buffer; oldest dropped first when full.
     */
    private static $breadcrumbs = array();
    const MAX_BREADCRUMBS = 20;

    /**
     * Bootstrap. Called once from allaccessible.php after Debug.php
     * loads but before any AJAX handler is registered. Returns early
     * when the user has opted out OR has no AllAccessible account.
     */
    public static function init() {
        if (self::$initialized) return;

        // CONSENT GATE: only report errors when an AllAccessible account
        // is linked. The consent chain for error/diagnostic collection
        // runs through account signup → Terms → Privacy Policy (which
        
        // an account has agreed to nothing, so we send NOTHING — this
        // keeps the README's "no data without an account" promise true
        // and satisfies WP.org Guideline #7 (no external data
        // transmission without authenticated consent).
        $account_id = (string) get_option('aacb_accountID', '');
        if ($account_id === '') {
            self::$initialized = true; // mark so we don't re-check every call
            return;
        }

        $opts = get_option('aacb_options', array());
        if (!empty($opts['sentry_disabled'])) {
            self::$initialized = true; // mark so we don't re-check on every call
            return;
        }

        $dsn = (string) apply_filters('aacb_sentry_dsn', self::DEFAULT_DSN);
        if (!self::parse_dsn($dsn)) {
            self::$initialized = true;
            return;
        }

        // Chain error handlers — never clobber existing ones. WordPress
        // core, other plugins, and Query Monitor all set their own. We
        // call through to the previous handler so they keep working.
        $prev_exception = set_exception_handler(array(__CLASS__, 'handle_uncaught_exception'));
        $prev_error     = set_error_handler(array(__CLASS__, 'handle_php_error'));
        register_shutdown_function(array(__CLASS__, 'handle_shutdown'));

        // Stash prior handlers so our chained handlers can pass through.
        self::$prev_exception_handler = $prev_exception;
        self::$prev_error_handler     = $prev_error;

        self::$initialized = true;
    }

    private static $prev_exception_handler = null;
    private static $prev_error_handler     = null;

    
    private static function parse_dsn(string $dsn): bool {
        $parts = parse_url($dsn);
        if (!$parts || empty($parts['scheme']) || empty($parts['user']) || empty($parts['host']) || empty($parts['path'])) {
            return false;
        }
        self::$public_key = (string) $parts['user'];
        self::$project_id = ltrim((string) $parts['path'], '/');
        if (self::$public_key === '' || self::$project_id === '') return false;
        self::$store_url  = sprintf('%s://%s/api/%s/store/', $parts['scheme'], $parts['host'], self::$project_id);
        self::$dsn_parsed = $parts;
        return true;
    }

    
    public static function add_breadcrumb(string $category, string $message, array $data = array()) {
        if (!self::$initialized) self::init();
        if (!self::$dsn_parsed) return;
        self::$breadcrumbs[] = array(
            'timestamp' => microtime(true),
            'category'  => $category,
            'message'   => $message,
            'data'      => $data,
            'level'     => 'info',
        );
        if (count(self::$breadcrumbs) > self::MAX_BREADCRUMBS) {
            array_shift(self::$breadcrumbs);
        }
    }

    /**
     * Public API — send an exception. Use this in catch blocks where
     * the exception is being swallowed but represents a real failure
     * (e.g. ApiClient request errors that get returned as WP_Error).
     */
    public static function capture_exception(\Throwable $e, array $extra = array()) {
        if (!self::$initialized) self::init();
        if (!self::$dsn_parsed) return;

        $fingerprint = md5($e->getMessage() . '|' . $e->getFile() . '|' . $e->getLine());
        if (isset(self::$seen_fingerprints[$fingerprint])) return;
        self::$seen_fingerprints[$fingerprint] = true;

        if (!self::within_hourly_cap()) return;

        $event = self::build_event($extra);
        $event['exception'] = array('values' => array(array(
            'type'        => get_class($e),
            'value'       => $e->getMessage(),
            'stacktrace'  => self::frames_to_stacktrace($e->getTrace(), $e->getFile(), $e->getLine()),
        )));
        $event['level'] = 'error';

        self::send_event($event);
    }

    /**
     * Public API — send a freeform message. Use for non-exception
     * conditions worth surfacing (e.g. AJAX nonce failures that the
     * caller wants to track without being an exception).
     */
    public static function capture_message(string $message, string $level = 'error', array $extra = array()) {
        if (!self::$initialized) self::init();
        if (!self::$dsn_parsed) return;

        $fingerprint = md5($message);
        if (isset(self::$seen_fingerprints[$fingerprint])) return;
        self::$seen_fingerprints[$fingerprint] = true;

        if (!self::within_hourly_cap()) return;

        $event = self::build_event($extra);
        $event['message'] = $message;
        $event['level']   = in_array($level, array('fatal','error','warning','info','debug'), true) ? $level : 'error';

        self::send_event($event);
    }

    /**
     * Build the common event envelope — tags, contexts, breadcrumbs.
     */
    private static function build_event(array $extra): array {
        $event = array(
            'event_id'     => self::uuid4(),
            'timestamp'    => gmdate('Y-m-d\TH:i:s\Z'),
            'platform'     => 'php',
            'logger'       => 'allaccessible.wp',
            'environment'  => (defined('WP_DEBUG') && WP_DEBUG) ? 'dev' : 'production',
            'release'      => defined('AACB_VERSION') ? ('aacb-wp@' . AACB_VERSION) : 'unknown',
            'server_name'  => function_exists('gethostname') ? (string) gethostname() : 'unknown',
            'tags'         => self::default_tags(),
            'contexts'     => self::default_contexts(),
            'extra'        => $extra,
            'breadcrumbs'  => array('values' => self::$breadcrumbs),
        );
        return $event;
    }

    
    private static function default_tags(): array {
        $account_id = (string) get_option('aacb_accountID', '');
        $site_url   = function_exists('get_site_url') ? (string) get_site_url() : '';
        $host = '';
        if ($site_url !== '') {
            $p = parse_url($site_url);
            $host = isset($p['host']) ? strtolower($p['host']) : '';
        }
        $tier = '';
        if (class_exists('AllAccessible_ApiClient')) {
            try {
                $tier = (string) AllAccessible_ApiClient::get_instance()->get_subscription_tier();
            } catch (\Throwable $e) { /* don't recurse */ }
        }
        $tags = array(
            'account_id'     => $account_id !== '' ? $account_id : 'unknown',
            'site_host'      => $host !== '' ? $host : 'unknown',
            'tier'           => $tier !== '' ? $tier : 'unknown',
            'plugin_version' => defined('AACB_VERSION') ? AACB_VERSION : 'unknown',
            'wp_version'     => function_exists('get_bloginfo') ? (string) get_bloginfo('version') : 'unknown',
            'php_version'    => PHP_VERSION,
            'multisite'      => function_exists('is_multisite') && is_multisite() ? 'yes' : 'no',
        );
        return $tags;
    }

    
    private static function default_contexts(): array {
        return array(
            'runtime' => array(
                'name'    => 'php',
                'version' => PHP_VERSION,
            ),
            'app' => array(
                'app_name'    => 'AllAccessible WP Plugin',
                'app_version' => defined('AACB_VERSION') ? AACB_VERSION : 'unknown',
            ),
            'user' => array(
                'id' => (string) get_option('aacb_accountID', 'anonymous'),
            ),
        );
    }

    
    private static function frames_to_stacktrace(array $trace, string $throw_file, int $throw_line): array {
        $frames = array();
        foreach (array_reverse($trace) as $f) {
            $frames[] = array(
                'filename' => $f['file']     ?? '?',
                'lineno'   => (int) ($f['line'] ?? 0),
                'function' => self::format_function($f),
            );
        }
        // Topmost frame = the actual throw location.
        $frames[] = array(
            'filename' => $throw_file,
            'lineno'   => $throw_line,
            'function' => '<throw>',
        );
        return array('frames' => $frames);
    }

    private static function format_function(array $f): string {
        $fn = $f['function'] ?? '?';
        if (!empty($f['class']) && !empty($f['type'])) {
            return $f['class'] . $f['type'] . $fn;
        }
        return $fn;
    }

    
    private static function send_event(array $event) {
        if (!function_exists('wp_remote_post')) return;
        $payload = wp_json_encode($event);
        if ($payload === false) return;

        $auth = sprintf(
            'Sentry sentry_version=7, sentry_client=%s/%s, sentry_key=%s',
            self::CLIENT_NAME,
            defined('AACB_VERSION') ? AACB_VERSION : '0.0.0',
            self::$public_key
        );

        wp_remote_post(self::$store_url, array(
            'method'   => 'POST',
            'timeout'  => 2,
            'blocking' => false, // critical — fire-and-forget
            'headers'  => array(
                'Content-Type'    => 'application/json',
                'X-Sentry-Auth'   => $auth,
                'User-Agent'      => self::CLIENT_NAME . '/' . (defined('AACB_VERSION') ? AACB_VERSION : '0.0.0'),
            ),
            'body'     => $payload,
        ));

        // Increment hourly counter (cap-checked separately so a flood
        // never grows the counter past the cap).
        $count = (int) get_transient(self::HOURLY_CAP_TRANSIENT);
        set_transient(self::HOURLY_CAP_TRANSIENT, $count + 1, HOUR_IN_SECONDS);
    }

    
    private static function within_hourly_cap(): bool {
        $count = (int) get_transient(self::HOURLY_CAP_TRANSIENT);
        return $count < self::HOURLY_CAP;
    }

    /**
     * Handlers chained from init().
     */
    public static function handle_uncaught_exception(\Throwable $e) {
        // Plugin-dir scope gate — same rule as handle_php_error and
        // handle_shutdown. Without this, ANY uncaught exception from
        // any other WP plugin (Give, Yoast, WooCommerce, etc.) gets
        
        // in the chain. Verified leak: Give plugin's "Target class
        // [...Harbor\Consent\Provider] does not exist" landed in our
        // project before this gate.
        //
        // Walks the throw location AND the stack to catch cases where
        // OUR code threw but the file path PHP reports is some wrapper
        // (rare, but defensive). If ANY frame is in our plugin dir we
        // own the throw and should capture.
        if (self::is_throwable_in_plugin_scope($e)) {
            self::capture_exception($e, array('handler' => 'uncaught_exception'));
        }
        if (self::$prev_exception_handler) {
            call_user_func(self::$prev_exception_handler, $e);
        }
    }

    /**
     * Returns true if the throw originated inside this plugin's dir,
     * either at the throw site or anywhere in the stack trace.
     */
    private static function is_throwable_in_plugin_scope(\Throwable $e): bool {
        $plugin_root = defined('AACB_VERSION') ? dirname(__FILE__, 2) : null;
        if (!$plugin_root) return false;

        // Throw site itself.
        if (strpos((string) $e->getFile(), $plugin_root) === 0) {
            return true;
        }
        // Walk frames — catches cases where a third-party wrapper
        // re-threw OUR exception (rare, but possible).
        foreach ($e->getTrace() as $frame) {
            $file = isset($frame['file']) ? (string) $frame['file'] : '';
            if ($file !== '' && strpos($file, $plugin_root) === 0) {
                return true;
            }
        }
        return false;
    }

    public static function handle_php_error($errno, $errstr, $errfile = '', $errline = 0) {
        // Only capture errors above warning. Notices/deprecations are
        // too noisy and almost always third-party plugin chatter.
        if (in_array($errno, array(E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR, E_WARNING, E_USER_WARNING), true)) {
            // Only report errors that originate from our plugin dir to
            // avoid being the de-facto error monitor for every other
            // WP plugin on the install.
            $plugin_root = defined('AACB_VERSION') ? dirname(__FILE__, 2) : null;
            if ($plugin_root && strpos((string) $errfile, $plugin_root) === 0) {
                self::capture_message(
                    sprintf('[php_error %d] %s', $errno, $errstr),
                    in_array($errno, array(E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR), true) ? 'error' : 'warning',
                    array(
                        'errno'   => $errno,
                        'errfile' => $errfile,
                        'errline' => $errline,
                    )
                );
            }
        }
        if (self::$prev_error_handler) {
            return call_user_func(self::$prev_error_handler, $errno, $errstr, $errfile, $errline);
        }
        return false; // let PHP run its normal handler
    }

    public static function handle_shutdown() {
        $err = error_get_last();
        if (!$err) return;
        if (!in_array($err['type'], array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR), true)) return;
        // Same scope gate as the error handler.
        $plugin_root = defined('AACB_VERSION') ? dirname(__FILE__, 2) : null;
        if (!$plugin_root || strpos((string) ($err['file'] ?? ''), $plugin_root) !== 0) return;
        self::capture_message(
            sprintf('[fatal %d] %s', $err['type'], $err['message']),
            'fatal',
            array('errfile' => $err['file'], 'errline' => $err['line'])
        );
    }

    
    private static function uuid4(): string {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return bin2hex($data);
    }
}
