<?php
/**
 * AllAccessible Uninstaller
 *
 * @package     AllAccessible
 * @since       1.3.7
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete all plugin options
delete_option('aacb_options');
delete_option('aacb_installed');
delete_option('aacb_siteID');
delete_option('aacb_accountID');
delete_option('aacb_hide_premium_notice');
delete_option('aacb_version');

// Clean up any transients
delete_transient('aacb_site_options_cache');

// For multisite installations
if (is_multisite()) {
    global $wpdb;
    $blogs = $wpdb->get_results("SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A);
    
    if ($blogs) {
        foreach ($blogs as $blog) {
            switch_to_blog($blog['blog_id']);
            
            // Delete options for each site
            delete_option('aacb_options');
            delete_option('aacb_installed');
            delete_option('aacb_siteID');
            delete_option('aacb_accountID');
            delete_option('aacb_hide_premium_notice');
            delete_option('aacb_version');
            
            // Clean up any transients
            delete_transient('aacb_site_options_cache');
            
            restore_current_blog();
        }
    }
}
