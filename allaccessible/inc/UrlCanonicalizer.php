<?php
/**
 * URL canonicalization
 *
 * @package AllAccessible
 * @since   2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AllAccessible_UrlCanonicalizer {

    /**
     * Directory index filenames the canonicalizer treats as "no file" —
     * `/about/index.html` and `/about/` collapse to the same key. Must
     * match the AllAccessible API's index file list.
     */
    private const INDEX_FILES = ['index.html', 'index.php', 'default.aspx'];

    /**
     * Query string parameters dropped before sorting. Tracking junk that
     * doesn't change the page identity. Mirrors the API's TRACKING_PARAMS.
     */
    private const TRACKING_PARAMS = [
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'gclid', 'fbclid', 'mc_cid', 'mc_eid', 'msclkid',
        '_ga', '_gl', 'hsCtaTracking', '__hssc', '__hstc', '__hsfp',
    ];

    /**
     * Return canonical form of $url, or the original string if unparseable.
     * Never throws — callers can use the result as a stable dedup key.
     */
    public static function canonicalize(string $url): string {
        $url = trim($url);
        if ($url === '') return '';
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['host'])) {
            return $url;
        }

        $host  = self::canonicalize_host((string) $parts['host']);
        $port  = self::canonicalize_port($parts['port'] ?? null, $parts['scheme'] ?? null);
        $path  = self::canonicalize_path((string) ($parts['path']  ?? ''));
        $query = self::canonicalize_query((string) ($parts['query'] ?? ''));

        return 'https://' . $host . $port . $path . ($query !== '' ? '?' . $query : '');
    }

    /**
     * Convenience: canonicalize a WP post's permalink. Returns '' when
     * get_permalink() fails (post deleted, draft without slug, etc).
     */
    public static function for_post(int $post_id): string {
        $permalink = get_permalink($post_id);
        return $permalink ? self::canonicalize((string) $permalink) : '';
    }

    private static function canonicalize_host(string $host): string {
        $host = strtolower($host);
        // PHP 7.4-compatible (no str_starts_with).
        if (strncmp($host, 'www.', 4) === 0) {
            $host = substr($host, 4);
        }
        return $host;
    }

    private static function canonicalize_port($port, ?string $original_scheme): string {
        if ($port === null || $port === '') return '';
        $scheme = strtolower((string) $original_scheme);
        if (($scheme === 'http'  && (int) $port === 80) ||
            ($scheme === 'https' && (int) $port === 443)) {
            return '';
        }
        return ':' . (int) $port;
    }

    private static function canonicalize_path(string $path): string {
        if ($path === '' || $path === '/') return '';

        $segments = explode('/', $path);
        $last     = end($segments);
        if ($last !== false && in_array(strtolower($last), self::INDEX_FILES, true)) {
            array_pop($segments);
            $path = implode('/', $segments);
        }

        $path = rtrim($path, '/');
        return $path;
    }

    private static function canonicalize_query(string $query): string {
        if ($query === '' || $query === '?') return '';
        // PHP 7.4-compatible (no str_starts_with).
        if (strncmp($query, '?', 1) === 0) {
            $query = substr($query, 1);
        }
        if ($query === '') return '';

        $kept = [];
        foreach (explode('&', $query) as $pair) {
            if ($pair === '') continue;
            $eq      = strpos($pair, '=');
            $raw_key = $eq === false ? $pair : substr($pair, 0, $eq);
            $key     = urldecode($raw_key);
            if (in_array($key, self::TRACKING_PARAMS, true)) continue;
            $kept[] = ['key' => $key, 'pair' => $pair];
        }
        if (empty($kept)) return '';

        // Stable alphabetical sort on key. APO sorts the same way so
        // ?b=1&a=2 → ?a=2&b=1 on both sides.
        usort($kept, static fn($x, $y) => strcmp($x['key'], $y['key']));
        return implode('&', array_map(static fn($k) => $k['pair'], $kept));
    }
}
