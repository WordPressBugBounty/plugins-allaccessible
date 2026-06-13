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

/**
 * Remove every trace of the plugin from the current site: all aacb_*
 * options (including the HMAC plugin secret — it must not survive in DB
 * backups or across reinstalls), all aacb_* transients, and scheduled
 * events. The wildcard sweep is the source of truth; it covers keys
 * added in any version without this file needing to track them.
 */
function aacb_uninstall_site() {
    global $wpdb;

    // Options + transients in one sweep. Transients live in wp_options as
    // _transient_aacb_* / _transient_timeout_aacb_*; site transients as
    // _site_transient_aacb_*.
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
          WHERE option_name LIKE 'aacb\\_%'
             OR option_name LIKE '\\_transient\\_aacb\\_%'
             OR option_name LIKE '\\_transient\\_timeout\\_aacb\\_%'
             OR option_name LIKE '\\_site\\_transient\\_aacb\\_%'
             OR option_name LIKE '\\_site\\_transient\\_timeout\\_aacb\\_%'"
    );

    // Post meta (e.g. _aacb_context_map attachment-map cache).
    $wpdb->query(
        "DELETE FROM {$wpdb->postmeta}
          WHERE meta_key LIKE 'aacb\\_%'
             OR meta_key LIKE '\\_aacb\\_%'"
    );

    // Scheduled events (wp_unschedule_hook clears all occurrences,
    // including single events scheduled with args).
    wp_unschedule_hook('aacb_post_link_backfill_run');
    wp_unschedule_hook('aacb_post_link_single');
    wp_unschedule_hook('aacb_fetch_plugin_secret_event');
    wp_unschedule_hook('aacb_daily_analytics_calculation'); // pre-2.1 legacy

    wp_cache_flush();
}

aacb_uninstall_site();

// For multisite installations
if (is_multisite()) {
    global $wpdb;
    $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);
        aacb_uninstall_site();
        restore_current_blog();
    }
}
