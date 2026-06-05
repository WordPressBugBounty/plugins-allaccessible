<?php
/**
Plugin Name: AllAccessible
Plugin URI: https://www.allaccessible.org/platform/wordpress/
Description: Unlock true digital accessibility with AllAccessible - a comprehensive WordPress plugin driving your website towards WCAG/ADA compliance. Empower your users with a fully customizable accessibility widget, plus agentic AI remediation that auto-suggests fixes for your team to approve.
Version: 2.1.2
Requires at least: 5.5
Tested up to: 7.0
Requires PHP: 7.4
Author: AllAccessible Team
Author URI: https://www.allaccessible.org/
Text Domain: allaccessible
Domain Path: /languages
 */

/**
 * Copyright (C) 2024 AllAccessible.
 * This file is part of AllAccessible.
 *
 * AllAccessible is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * AllAccessible is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with AllAccessible. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package     AllAccessible
 * @author      AllAccessible Team
 * @copyright   2024 AllAccessible
 * @license     GPL-2.0+
 */

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

// Core Components
require_once plugin_dir_path(__FILE__) . 'inc/constants.php';
require_once plugin_dir_path(__FILE__) . 'inc/Debug.php';              
require_once plugin_dir_path(__FILE__) . 'inc/SentryClient.php';       
require_once plugin_dir_path(__FILE__) . 'inc/SentryBrowser.php';      
AllAccessible_Sentry::init();
AllAccessible_SentryBrowser::register();
require_once plugin_dir_path(__FILE__) . 'inc/VersionManager.php';
require_once plugin_dir_path(__FILE__) . 'inc/UrlCanonicalizer.php';   

// Widget & Frontend
require_once plugin_dir_path(__FILE__) . 'inc/WidgetLoader.php';

// Admin Interface
require_once plugin_dir_path(__FILE__) . 'inc/OnboardingWizard.php';
require_once plugin_dir_path(__FILE__) . 'inc/SettingsPage.php';
require_once plugin_dir_path(__FILE__) . 'inc/WidgetCustomizer.php';
require_once plugin_dir_path(__FILE__) . 'inc/UsageDashboard.php';
require_once plugin_dir_path(__FILE__) . 'inc/ConversionCTA.php';
require_once plugin_dir_path(__FILE__) . 'inc/FeatureComparison.php';
require_once plugin_dir_path(__FILE__) . 'inc/DashboardBanner.php';
require_once plugin_dir_path(__FILE__) . 'inc/DeactivationSurvey.php';
require_once plugin_dir_path(__FILE__) . 'inc/DashboardLayout.php';

// API Integration (Premium Features)
require_once plugin_dir_path(__FILE__) . 'inc/api/ApiClient.php';
require_once plugin_dir_path(__FILE__) . 'inc/TierGate.php';                    
require_once plugin_dir_path(__FILE__) . 'inc/ContextInjector.php';             
require_once plugin_dir_path(__FILE__) . 'inc/AgenticFixes/Labels.php';          
require_once plugin_dir_path(__FILE__) . 'inc/AgenticFixesDashboardWidget.php';  
require_once plugin_dir_path(__FILE__) . 'inc/AgenticFixesPage.php';             
require_once plugin_dir_path(__FILE__) . 'inc/ImageManagerPage.php';             
require_once plugin_dir_path(__FILE__) . 'inc/SitemapDetector.php';             
require_once plugin_dir_path(__FILE__) . 'inc/ScanTriggerPanel.php';           
require_once plugin_dir_path(__FILE__) . 'inc/ConnectionStatusCard.php';       
require_once plugin_dir_path(__FILE__) . 'inc/EditorMetaBox.php';    
require_once plugin_dir_path(__FILE__) . 'inc/PostListColumn.php';   
require_once plugin_dir_path(__FILE__) . 'inc/PostLinkBackfill.php'; 
require_once plugin_dir_path(__FILE__) . 'inc/AdminBar.php';         
require_once plugin_dir_path(__FILE__) . 'inc/ReviewNudge.php';      
AllAccessible_PostListColumn::register();
AllAccessible_PostLinkBackfill::register();
AllAccessible_AdminBar::register();
AllAccessible_ReviewNudge::register();


/**
 * Load translations
 */
function aacb_load_textdomain() {
    load_plugin_textdomain('allaccessible', false, basename(dirname(__FILE__)) . '/languages/');
}
add_action('init', 'aacb_load_textdomain');

/**
 * Plugin activation
 */
function AllAccessible_Activation() {
    $options = get_option('aacb_options');

    if (!is_array($options) || !isset($options['aacb_installed']) || $options['aacb_installed'] != 1) {
        $opt = array('aacb_installed' => 1);
        update_option('aacb_options', $opt);
    }

    if (class_exists('AllAccessible_PostLinkBackfill')) {
        AllAccessible_PostLinkBackfill::on_activate();
    }

    if (!get_option('aacb_accountID')) {
        set_transient('aacb_activation_redirect', 1, 60);
    }
}
register_activation_hook(__FILE__, 'AllAccessible_Activation');

/**
 * One-shot redirect to the onboarding wizard right after activation.
 */
function aacb_maybe_redirect_after_activation() {
    if (!get_transient('aacb_activation_redirect')) return;
    delete_transient('aacb_activation_redirect');

    if (wp_doing_ajax() || !is_admin()) return;
    if (isset($_GET['activate-multi'])) return;
    if (!current_user_can('manage_options')) return;

    wp_safe_redirect(admin_url('admin.php?page=allaccessible-wizard'));
    exit;
}
add_action('admin_init', 'aacb_maybe_redirect_after_activation');

/**
 * Plugin deactivation
 */
function AllAccessible_Deactivation() {
    // Clean up scheduled events
    wp_clear_scheduled_hook('aacb_daily_analytics_calculation');
}
register_deactivation_hook(__FILE__, 'AllAccessible_Deactivation');

/**
 * AJAX handler for saving account ID
 * Used by wizard and legacy settings page
 */
function AllAccessible_save_settings() {
    // Verify capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access');
        return;
    }

    // Verify nonce (support both old and new nonce names)
    $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field($_POST['_wpnonce']) : '';
    if (empty($nonce) || !wp_verify_nonce($nonce, 'allaccessible_save_settings')) {
        wp_send_json_error('Invalid security token');
        return;
    }

    // Save account ID if provided
    if (isset($_POST['aacb_accountID'])) {
        $account_id = sanitize_text_field($_POST['aacb_accountID']);
        update_option('aacb_accountID', $account_id);

        wp_send_json_success(array('message' => __('Account settings saved successfully', 'allaccessible')));
    }

    wp_send_json_error('No data to save');
}
add_action('wp_ajax_AllAccessible_save_settings', 'AllAccessible_save_settings');

/**
 * AJAX handler to clear API cache
 */
function aacb_clear_cache_ajax() {
    check_ajax_referer('aacb_clear_cache', '_wpnonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $api_client = AllAccessible_ApiClient::get_instance();
    $api_client->clear_cache();

    wp_send_json_success();
}
add_action('wp_ajax_aacb_clear_cache', 'aacb_clear_cache_ajax');

/**
 * AJAX handler to reset all plugin data
 * Allows users to start fresh without deleting the plugin
 *
 * @since 2.0.3
 */
function aacb_reset_plugin_data() {
    check_ajax_referer('aacb_reset_plugin', '_wpnonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Unauthorized access', 'allaccessible'));
    }

    // Delete all plugin options
    delete_option('aacb_options');
    delete_option('aacb_installed');
    delete_option('aacb_siteID');
    delete_option('aacb_accountID');
    delete_option('aacb_hide_premium_notice');
    delete_option('aacb_version');
    delete_option('aacb_wizard_completed');

    // Clear all transients
    delete_transient('aacb_site_options_cache');
    delete_transient('aacb_validation_cache');

    // Re-initialize with default options
    $opt = array('aacb_installed' => 1);
    update_option('aacb_options', $opt);

    wp_send_json_success(array(
        'message' => __('Plugin data has been reset successfully', 'allaccessible')
    ));
}
add_action('wp_ajax_aacb_reset_plugin_data', 'aacb_reset_plugin_data');

/* =====================================================================
 * Agentic Fixes — AJAX handlers
 * ===================================================================== */

const AACB_MANIFEST_NONCE = 'aacb_manifest_action';

function aacb_assert_manifest_caller() {
    check_ajax_referer(AACB_MANIFEST_NONCE, '_wpnonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Unauthorized', 'allaccessible'), 403);
    }
}

function aacb_approve_manifest_ajax() {
    aacb_assert_manifest_caller();
    $manifest_id = isset($_POST['manifest_id']) ? (int) $_POST['manifest_id'] : 0;
    $result = AllAccessible_ApiClient::get_instance()->approve_manifest($manifest_id);
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message(), 400);
    }
    wp_send_json_success($result);
}
add_action('wp_ajax_aacb_approve_manifest', 'aacb_approve_manifest_ajax');

function aacb_revert_manifest_ajax() {
    aacb_assert_manifest_caller();
    $manifest_id = isset($_POST['manifest_id']) ? (int) $_POST['manifest_id'] : 0;
    $reason      = isset($_POST['reason'])      ? sanitize_text_field(wp_unslash($_POST['reason'])) : '';
    $result = AllAccessible_ApiClient::get_instance()->revert_manifest($manifest_id, $reason);
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message(), 400);
    }
    wp_send_json_success($result);
}
add_action('wp_ajax_aacb_revert_manifest', 'aacb_revert_manifest_ajax');

function aacb_edit_fix_ajax() {
    aacb_assert_manifest_caller();
    $manifest_id = isset($_POST['manifest_id']) ? (int) $_POST['manifest_id'] : 0;
    $fix_index   = isset($_POST['fix_index'])   ? (int) $_POST['fix_index']   : -1;
    $value       = isset($_POST['value'])       ? wp_kses_post(wp_unslash($_POST['value'])) : '';
    $result = AllAccessible_ApiClient::get_instance()->edit_fix($manifest_id, $fix_index, $value);

    if (is_wp_error($result)) {
        $payload = array(
            'message'     => $result->get_error_message(),
            'wp_code'     => $result->get_error_code(),
            'server_data' => $result->get_error_data(),
            'request'     => array(
                'manifest_id' => $manifest_id,
                'fix_index'   => $fix_index,
                'value_len'   => strlen((string) $value),
            ),
        );
        wp_send_json_error($payload, 400);
    }
    wp_send_json_success($result);
}
add_action('wp_ajax_aacb_edit_fix', 'aacb_edit_fix_ajax');

/**
 * Bulk approve.
 */
function aacb_bulk_approve_manifests_ajax() {
    aacb_assert_manifest_caller();
    $site_id = isset($_POST['site_id']) ? (int) $_POST['site_id'] : 0;
    $ids_raw = isset($_POST['manifest_ids']) ? (array) $_POST['manifest_ids'] : array();
    $ids     = array_values(array_filter(array_map('intval', $ids_raw), function($v) { return $v > 0; }));
    $result  = AllAccessible_ApiClient::get_instance()->bulk_approve_manifests($site_id, $ids);

    AllAccessible_Debug::api('bulk_approve_manifests', array(
        'site_id'      => $site_id,
        'manifest_ids' => $ids,
    ), $result);

    if (is_wp_error($result)) {
        $payload = array(
            'message'     => $result->get_error_message(),
            'wp_code'     => $result->get_error_code(),
            'server_data' => $result->get_error_data(),
            'request'     => array('site_id' => $site_id, 'manifest_ids' => $ids),
        );
        wp_send_json_error($payload, 400);
    }
    wp_send_json_success($result);
}
add_action('wp_ajax_aacb_bulk_approve_manifests', 'aacb_bulk_approve_manifests_ajax');

/* =====================================================================
 * Scan trigger 
 * ===================================================================== */

const AACB_SCAN_NONCE = 'aacb_scan_action';

function aacb_assert_scan_caller() {
    check_ajax_referer(AACB_SCAN_NONCE, '_wpnonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Unauthorized', 'allaccessible'), 403);
    }
}

/**
 * Return detected sitemap candidates.
 */
function aacb_detect_sitemap_ajax() {
    aacb_assert_scan_caller();
    if (!class_exists('AllAccessible_SitemapDetector')) {
        wp_send_json_error('Detector not loaded', 500);
    }
    $candidates = AllAccessible_SitemapDetector::all_candidates();
    $primary    = AllAccessible_SitemapDetector::detect(true);
    wp_send_json_success(array(
        'primary'    => $primary,
        'candidates' => $candidates,
    ));
}
add_action('wp_ajax_aacb_detect_sitemap', 'aacb_detect_sitemap_ajax');

/**
 * Start a scan.
 */
function aacb_start_scan_ajax() {
    aacb_assert_scan_caller();
    $sitemap_url = isset($_POST['sitemap_url']) ? esc_url_raw(wp_unslash($_POST['sitemap_url'])) : '';
    $viewport    = isset($_POST['viewport'])    ? sanitize_key($_POST['viewport'])             : 'both';

    $client = AllAccessible_ApiClient::get_instance();
    $dispatched = $client->start_scan_workflow_async($sitemap_url, $viewport);

    if (!$dispatched) {
        $secret = $client->get_plugin_secret();
        if (!empty($secret)) {
            $dispatched = $client->start_scan_workflow_async($sitemap_url, $viewport);
        }
    }

    if (!$dispatched) {
        wp_send_json_error(__('Still finishing setup — wait a few seconds and try again.', 'allaccessible'), 503);
    }

    wp_send_json_success(array(
        'queued'      => true,
        'message'     => __('Scan queued. Results appear in 2-5 minutes.', 'allaccessible'),
        'triggeredAt' => time(),
        'sitemapUrl'  => $sitemap_url,
    ));
}
add_action('wp_ajax_aacb_start_scan', 'aacb_start_scan_ajax');

/**
 * Poll scan progress.
 */
function aacb_scan_status_ajax() {
    aacb_assert_scan_caller();
    $job_id = isset($_POST['job_id']) ? (int) $_POST['job_id'] : 0;
    $result = AllAccessible_ApiClient::get_instance()->get_scan_status($job_id);
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message(), 400);
    }
    wp_send_json_success($result);
}
add_action('wp_ajax_aacb_scan_status', 'aacb_scan_status_ajax');

/**
 * Background fetch of the plugin secret.
 */
add_action('aacb_fetch_plugin_secret_event', function() {
    if (class_exists('AllAccessible_ApiClient')) {
        AllAccessible_ApiClient::get_instance()->fetch_plugin_secret();
    }
});

/**
 * Force-refresh.
 */
function aacb_verify_connection_ajax() {
    check_ajax_referer('aacb_verify_connection', '_wpnonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Unauthorized', 'allaccessible'), 403);
    }
    delete_transient('aacb_site_options_cache');
    delete_transient('aacb_validation_cache');
    delete_transient('aacb_cache_manifest_summary_v2');
    $client = AllAccessible_ApiClient::get_instance();
    $opts = $client->get_site_options(true);
    if (is_wp_error($opts)) {
        wp_send_json_error($opts->get_error_message(), 400);
    }
    wp_send_json_success(array('refreshed' => true));
}
add_action('wp_ajax_aacb_verify_connection', 'aacb_verify_connection_ajax');
