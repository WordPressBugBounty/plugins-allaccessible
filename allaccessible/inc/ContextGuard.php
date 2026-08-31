<?php
/**
 * Context Guard for AllAccessible
 *
 * Single decision point for "should the widget be printed on this request?".
 * Used by WidgetLoader (widget <script>) and ContextInjector (context block)
 * so both stay in lockstep, and surfaced in the admin bar for debugging.
 *
 * The widget must not run on requests that are not real, visitor-facing pages:
 *  - wp-admin
 *  - 404s (otherwise the API registers the 404 URL as a monitored page)
 *  - feeds (not HTML)
 *  - Customizer / post previews
 *  - page-builder editing sessions. Several builders (Oxygen, Divi, Elementor,
 *    Beaver Builder, WPBakery, Bricks, Brizy, Thrive, Cornerstone, Avada)
 *    render their editor as a FRONTEND request flagged only by a query param,
 *    so is_admin() is false and the widget + scanner would run inside the editor.
 *
 * Site owners can extend the decision with the `allaccessible_skip_widget` filter.
 *
 * WordPress conditionals are called only when they exist so this file can be
 * exercised by tests/context-guard-test.php without a WordPress bootstrap.
 *
 * @package AllAccessible
 * @since 2.1.6
 */

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

class AllAccessible_ContextGuard {

    /**
     * Query params that mark a page-builder editing/preview session.
     *
     * Presence alone is enough (Beaver Builder launches on a bare `?fl_builder`
     * and Brizy on a bare `?brizy-edit-iframe`, so a value check would miss
     * both). Params here MUST be vendor-namespaced: matching a bare English
     * word would strip the widget from ordinary visitor URLs — see
     * BUILDER_QUERY_PARAMS_WITH_VALUE for the ambiguous ones.
     */
    const BUILDER_QUERY_PARAMS = array(
        'ct_builder',        // Oxygen
        'ct_inner',          // Oxygen (inner content)
        'et_fb',             // Divi Visual Builder
        'elementor-preview', // Elementor
        'fl_builder',        // Beaver Builder (often valueless)
        'vc_editable',       // WPBakery
        'vc_action',         // WPBakery
        'brizy-edit',        // Brizy (valueless)
        'brizy-edit-iframe', // Brizy (valueless)
        'fusion_load_nonce', // Avada Live
        'fb-edit',           // Avada Live (front-end editor)
    );

    /**
     * Builder params whose NAME is an ordinary word, so presence alone is not
     * evidence of a builder session: `/shop/?bricks=clay` on a masonry store is
     * a real visitor URL. These only count when the value matches what the
     * builder actually sends.
     */
    const BUILDER_QUERY_PARAMS_WITH_VALUE = array(
        'bricks'      => array('run'),          // Bricks
        'tve'         => array('true', '1'),    // Thrive Architect
        'cornerstone' => array('1', 'true'),    // Cornerstone
    );

    /**
     * True when the widget must NOT be printed for the current request.
     *
     * @return bool
     */
    public static function should_skip_widget() {
        return self::skip_reason() !== '';
    }

    /**
     * Human-readable reason the widget is skipped (for the admin bar / debug),
     * or '' when the widget should load.
     *
     * @return string
     */
    public static function skip_reason() {
        if (self::wp_true('is_admin')) {
            return 'admin';
        }
        if (self::wp_true('is_404')) {
            return '404';
        }
        if (self::wp_true('is_feed')) {
            return 'feed';
        }
        if (self::wp_true('is_customize_preview')) {
            return 'customizer';
        }
        // `preview` is a PUBLIC query var: WP_Query sets is_preview() from it with
        // no nonce and no login. An editor pasting a preview link into email or
        // Slack would otherwise strip the widget for every anonymous visitor who
        // clicks it. Only treat it as a preview for a user who can actually edit.
        if (self::wp_true('is_preview') && self::current_user_can_edit()) {
            return 'preview';
        }
        if (self::in_builder_session()) {
            return 'page builder';
        }
        if (function_exists('apply_filters') && apply_filters('allaccessible_skip_widget', false)) {
            return 'filter';
        }
        return '';
    }

    /**
     * Is this request a page-builder editing session?
     *
     * A builder session is ALWAYS an authenticated request from someone who can
     * edit content. Requiring that first is what makes the query-param check
     * safe: without it any anonymous visitor whose URL happens to carry one of
     * these params (a shop facet, a campaign tag, a shared link) silently loses
     * the accessibility widget on a live page.
     *
     * @return bool
     */
    public static function in_builder_session() {
        if (!self::current_user_can_edit()) {
            return false;
        }

        foreach (self::BUILDER_QUERY_PARAMS as $param) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only detection, no state change.
            // isset(), not !empty(): Beaver Builder and Brizy pass these bare.
            if (isset($_GET[$param])) {
                return true;
            }
        }

        foreach (self::BUILDER_QUERY_PARAMS_WITH_VALUE as $param => $values) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only detection, no state change.
            if (!isset($_GET[$param])) {
                continue;
            }
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only detection, no state change.
            $value = is_scalar($_GET[$param]) ? strtolower((string) $_GET[$param]) : '';
            if (in_array($value, $values, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Can the current user edit content?
     *
     * Outside WordPress (standalone test) both functions are absent and this
     * returns false, so the guard fails OPEN — the widget renders. That is the
     * safe direction: a missing check must never hide the product.
     */
    private static function current_user_can_edit() {
        if (!function_exists('is_user_logged_in') || !function_exists('current_user_can')) {
            return false;
        }
        return is_user_logged_in() && current_user_can('edit_posts');
    }

    /**
     * Call a WordPress conditional only if it is defined (keeps this file
     * loadable outside WordPress, e.g. in the standalone test).
     */
    private static function wp_true($fn) {
        return function_exists($fn) && (bool) call_user_func($fn);
    }
}
