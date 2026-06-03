<?php
/**
 * Sitemap auto-detection for WordPress installs.
 *
 * @package AllAccessible
 * @since   2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AllAccessible_SitemapDetector {

    const CACHE_KEY     = 'aacb_sitemap_url_cache';
    const CACHE_SECONDS = 12 * HOUR_IN_SECONDS;

    /**
     * Best-effort sitemap URL for this site. Cached.
     *
     * @param bool $force_refresh Bypass cache when true.
     * @return string|null
     */
    public static function detect($force_refresh = false) {
        if ($force_refresh) {
            delete_transient(self::CACHE_KEY . '_candidates_v2');
        }
        $candidates = self::all_candidates();
        return !empty($candidates) ? $candidates[0]['url'] : null;
    }

    /**
     * Return ALL candidates with detection method + reachability.
     *
     * @return array  list of ['url' => ..., 'source' => 'yoast'|'rankmath'|...,
     *                'reachable' => bool]
     */
    public static function all_candidates() {
        $cache_key = self::CACHE_KEY . '_candidates_v2';
        $cached = get_transient($cache_key);
        if (is_array($cached)) return $cached;
        $potential = array();
        if (class_exists('WPSEO_Sitemaps') || defined('WPSEO_VERSION')) {
            $potential[] = array('url' => home_url('/sitemap_index.xml'), 'source' => 'yoast');
        }
        if (class_exists('RankMath\\Sitemap\\Sitemap') || defined('RANK_MATH_VERSION')) {
            $potential[] = array('url' => home_url('/sitemap_index.xml'), 'source' => 'rankmath');
        }
        if (defined('AIOSEO_DIR_URL') || class_exists('AIOSEO\\Plugin\\AIOSEO')) {
            $potential[] = array('url' => home_url('/sitemap.xml'), 'source' => 'aioseo');
        }
        if (defined('SEOPRESS_VERSION') || class_exists('SeoPress')) {
            $potential[] = array('url' => home_url('/sitemaps.xml'), 'source' => 'seopress');
        }
        if (self::is_core_sitemap_enabled()) {
            $potential[] = array('url' => home_url('/wp-sitemap.xml'), 'source' => 'wp-core');
        }
        // Generic root locations to probe regardless of plugins.
        $potential[] = array('url' => home_url('/sitemap.xml'),       'source' => 'http-probe');
        $potential[] = array('url' => home_url('/sitemap_index.xml'), 'source' => 'http-probe');
        $verified = array();
        $seen = array();
        foreach ($potential as $c) {
            $url = $c['url'];
            if ($url === '' || isset($seen[$url])) continue;
            $seen[$url] = true;
            if (self::sitemap_ok($url)) {
                $verified[] = array('url' => $url, 'source' => $c['source'], 'reachable' => true);
            }
        }

        // Cache 1h. Empty result is cached too (negative cache) so a
        // sitemap-less site doesn't re-probe on every render.
        set_transient($cache_key, $verified, HOUR_IN_SECONDS);
        return $verified;
    }

    /* ─── strategies ────────────────────────────────────────────────── */

    /**
     * Core sitemap module (since WP 5.5) honors the `wp_sitemaps_enabled`
     * filter. apply_filters() defaults to true if the filter never fires.
     */
    private static function is_core_sitemap_enabled() {
        if (!function_exists('apply_filters')) return false;
        // WP 5.5 introduced wp_sitemaps_get_server — gates whether the
        // sitemap is actually emitted. Check function presence first as a
        // proxy for WP version.
        if (!function_exists('wp_sitemaps_get_server')) return false;
        return (bool) apply_filters('wp_sitemaps_enabled', true);
    }

    /**
     * Verify a URL is a LIVE, REAL XML sitemap — not a soft-404 or a
     * redirect-to-home.
     */
    private static function sitemap_ok($url) {
        $response = wp_remote_get($url, array(
            'timeout'     => 5,
            'redirection' => 3,   // follow e.g. /sitemap.xml → /sitemap_index.xml
            'sslverify'   => false,
            'headers'     => array('Accept' => 'application/xml,text/xml,*/*'),
        ));
        if (is_wp_error($response)) return false;

        // Final status after redirects. A redirect-to-home soft-404 lands
        // on an HTML 200 here, which the signature check below rejects.
        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code !== 200) return false;

        $ctype = (string) wp_remote_retrieve_header($response, 'content-type');
        $body  = (string) wp_remote_retrieve_body($response);
        $head  = ltrim(substr($body, 0, 1024));

        $looks_xml = (stripos($ctype, 'xml') !== false)
            || stripos($head, '<?xml') === 0
            || stripos($head, '<urlset') !== false
            || stripos($head, '<sitemapindex') !== false;

        return $looks_xml;
    }
}
