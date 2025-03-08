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
define('AACB_VERSION', '1.3.7');

// Plugin Information
define('AACB_NAME', isset($GLOBALS['aacb_siteOptions']->isWhitelabel) && $GLOBALS['aacb_siteOptions']->isWhitelabel ? __("Accessibility", 'allaccessible') : 'AllAccessible');
define('AACB_WP_MIN_VERSION', '5.0');
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
