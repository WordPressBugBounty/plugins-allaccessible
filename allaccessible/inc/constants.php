<?php
/**
 * AllAccessible Constants
 *
 * Centralized location for all plugin constants
 *
 * @package     AllAccessible
 * @since       1.4.0
 */

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

// Plugin Version - THE SINGLE SOURCE OF TRUTH
define('AACB_VERSION', '2.1.2');

// Plugin Information
define('AACB_NAME', isset($GLOBALS['aacb_siteOptions']->isWhitelabel) && $GLOBALS['aacb_siteOptions']->isWhitelabel ? __("Accessibility", 'allaccessible') : 'AllAccessible');
define('AACB_WP_MIN_VERSION', '5.5');
define('AACB_TEXT', 'allaccessible');

// Plugin Paths
define('AACB_DIR', dirname(plugin_basename(__FILE__), 2));
define('AACB_URL', plugin_dir_url(dirname(__FILE__, 1)));
define('AACB_PATH', plugin_dir_path(dirname(__FILE__, 1)));
define('AACB_JS', AACB_URL . trailingslashit('assets'));
define('AACB_CSS', AACB_URL . trailingslashit('assets'));
define('AACB_IMG', AACB_URL . trailingslashit('assets'));
define('AACB_INC', AACB_PATH . trailingslashit('inc'));
define('AACB_SUPPORT', 'https://support.allaccessible.org/');

if (!function_exists('aacb_asset_ver')) {
    /**
     * Cache-busting version string for a bundled asset.
     *
     * In dev (WP_DEBUG) we key on the file's mtime so edits to CSS/JS show on
     * save without bumping AACB_VERSION — kills the "?ver= cache" tax where the
     * browser served a stale stylesheet because the version constant hadn't
     * changed. In production we fall back to AACB_VERSION so the URL is stable
     * and CDN-cacheable, and only changes on release.
     *
     * @param string $relative_to_assets e.g. 'admin-v2.css' (path under /assets/)
     * @return string
     */
    function aacb_asset_ver($relative_to_assets) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $path = AACB_PATH . 'assets/' . ltrim((string) $relative_to_assets, '/');
            $mtime = @filemtime($path);
            if ($mtime) {
                return (string) $mtime;
            }
        }
        return AACB_VERSION;
    }
}
