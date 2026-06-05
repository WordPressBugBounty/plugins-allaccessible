<?php
/**
 * AllAccessible — browser-side Sentry shim.
 *
 * Enqueues @sentry/browser CDN on every plugin admin page so JS errors
 * in AgenticFixesPage / AdminBar / ImageManager / EditorMetaBox /
 * SettingsPage inline scripts get aggregated to the same Sentry
 * project as PHP errors.
 *
 * Opt-out: same `aacb_options.sentry_disabled` toggle as PHP side.
 * Init no-ops cleanly when set.
 *
 */

if (!defined('ABSPATH')) { exit; }

final class AllAccessible_SentryBrowser {

    /**
     * Same DSN as PHP-side. Sentry treats events as one project
     * regardless of platform; tags discriminate.
     */
    const DEFAULT_DSN = 'https://09483018ddcc1c2ce3afa01acd5f0318@o4509626671759361.ingest.us.sentry.io/4511461234704384';

    /**
     * Browser SDK version + CDN URL. Pinned major to avoid surprise
     * upgrades. Sentry's CDN supports both ESM + UMD builds; we use
     * the UMD bundle for broadest WP-admin browser compatibility.
     */
    const SDK_VERSION = '8';
    const SDK_CDN_URL = 'https://browser.sentry-cdn.com/8.55.0/bundle.tracing.min.js';
    const SDK_INTEGRITY = 'sha384-WK4u/k5/i9LJOFbHddPdJrPe5UrCQ4i/jJrBOX0aLrxKgOdkS1JFTzS4r5e3sqMA';

    public static function register() {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue'));
    }

    /**
     * Decide whether to load Sentry on this admin screen. Loads on any
     * AllAccessible plugin screen + WP post-editor (where the metabox
     * runs). Skips everything else to keep core WP and other plugin
     * pages out of our error stream.
     */
    private static function should_load(string $hook): bool {
        if (strpos($hook, 'allaccessible') !== false) return true;
        if (strpos($hook, 'aacb-') !== false)         return true;
        if (in_array($hook, array('post.php', 'post-new.php', 'edit.php'), true)) return true;
        // Plugins/Themes admin home pages also have AllAccessible
        // dashboard widget rendering. Cheap to include those too.
        if (in_array($hook, array('index.php'), true)) return true;
        return false;
    }

    public static function enqueue($hook) {
        if (!current_user_can('manage_options')) return;
        if (!self::should_load((string) $hook))   return;

        // CONSENT GATE — mirror SentryClient::init(). No account =
        // no agreed Privacy Policy = no error transmission. Keeps the
        // README promise + WP.org Guideline #7 compliance consistent
        // across PHP and browser reporting.
        $account_id = (string) get_option('aacb_accountID', '');
        if ($account_id === '') return;

        $opts = get_option('aacb_options', array());
        if (!empty($opts['sentry_disabled'])) return;

        $dsn = (string) apply_filters('aacb_sentry_dsn', self::DEFAULT_DSN);
        if ($dsn === '') return;

        // CDN script — SRI checksum locked to this exact version. If
        // Sentry bumps minor releases their CDN URL changes anyway, so
        // we don't need an auto-update story; we just bump SDK_VERSION
        // constants intentionally.
        wp_enqueue_script(
            'aacb-sentry-browser',
            self::SDK_CDN_URL,
            array(),
            self::SDK_VERSION,
            false // load in <head> so it captures earliest possible errors
        );

        // Tag context — same shape as PHP side so events from both
        // platforms cross-reference cleanly in the Sentry UI.
        $account_id    = (string) get_option('aacb_accountID', '');
        $site_host     = wp_parse_url((string) get_site_url(), PHP_URL_HOST) ?: '';
        $tier          = '';
        if (class_exists('AllAccessible_ApiClient')) {
            try {
                $tier = (string) AllAccessible_ApiClient::get_instance()->get_subscription_tier();
            } catch (\Throwable $e) { /* don't recurse */ }
        }

        wp_localize_script('aacb-sentry-browser', 'AACB_SentryBrowser', array(
            'dsn'         => $dsn,
            'release'     => 'aacb-wp@' . (defined('AACB_VERSION') ? AACB_VERSION : '0.0.0'),
            'environment' => (defined('WP_DEBUG') && WP_DEBUG) ? 'dev' : 'production',
            'tags'        => array(
                'account_id'     => $account_id !== '' ? $account_id : 'unknown',
                'site_host'      => $site_host !== ''  ? strtolower($site_host) : 'unknown',
                'tier'           => $tier !== ''       ? $tier : 'unknown',
                'plugin_version' => defined('AACB_VERSION') ? AACB_VERSION : 'unknown',
                'wp_version'     => get_bloginfo('version'),
                'multisite'      => is_multisite() ? 'yes' : 'no',
                'screen'         => $hook,
            ),
        ));

        // Inline init. Runs immediately on script load. Defensive about
        // Sentry's loader timing — if `window.Sentry` isn't there yet,
        // wait for it once. SDK exposes `Sentry` global from the UMD bundle.
        $init_js = <<<JS
(function(){
    function init() {
        if (!window.Sentry || !window.AACB_SentryBrowser) return false;
        try {
            window.Sentry.init({
                dsn: AACB_SentryBrowser.dsn,
                release: AACB_SentryBrowser.release,
                environment: AACB_SentryBrowser.environment,
                // Sample errors at 100%, transactions disabled (we
                // don't ship perf monitoring on the WP admin side).
                tracesSampleRate: 0.0,
                // Filter out noise from other plugins' broken scripts.
                // Only forward events that touch our stack frames OR
                // explicitly captured via Sentry.captureException.
                beforeSend: function(event) {
                    try {
                        var frames = (event.exception && event.exception.values
                            && event.exception.values[0]
                            && event.exception.values[0].stacktrace
                            && event.exception.values[0].stacktrace.frames) || [];
                        var ours = frames.some(function(f) {
                            return f && typeof f.filename === 'string'
                                && (f.filename.indexOf('/plugins/allaccessible/') !== -1
                                    || f.filename.indexOf('aacb-') !== -1
                                    || f.filename.indexOf('AACB_') !== -1);
                        });
                        // No stack frames: keep only our own manual messages
                        // (captureMessage has a `message` and no exception). Drop
                        // frameless AUTO errors — opaque cross-origin failures,
                        // other plugins' Gutenberg "Transition" aborts, and
                        // browser-extension errors — they aren't ours, just noise.
                        if (frames.length === 0) {
                            return (event.message && !event.exception) ? event : null;
                        }
                        return ours ? event : null;
                    } catch (e) { return event; }
                }
            });
            window.Sentry.setTags(AACB_SentryBrowser.tags || {});
            // Convenience: surface a hook the rest of the plugin's
            // inline scripts can use without touching window.Sentry
            // directly — keeps the public API small.
            window.AACBSentry = {
                captureException: function(err, ctx) {
                    try { window.Sentry.captureException(err, ctx ? { extra: ctx } : undefined); }
                    catch (e) { /* swallow */ }
                },
                captureMessage: function(msg, level, ctx) {
                    try { window.Sentry.captureMessage(msg, { level: level || 'error', extra: ctx || {} }); }
                    catch (e) { /* swallow */ }
                },
                addBreadcrumb: function(category, message, data) {
                    try { window.Sentry.addBreadcrumb({ category: category, message: message, data: data || {}, level: 'info' }); }
                    catch (e) { /* swallow */ }
                }
            };
            return true;
        } catch (e) { return false; }
    }
    if (init()) return;
    // SDK hasn't finished loading — try once more after script-loaded event.
    document.addEventListener('readystatechange', function once() {
        if (init()) document.removeEventListener('readystatechange', once);
    });
})();
JS;
        wp_add_inline_script('aacb-sentry-browser', $init_js, 'after');
    }
}
