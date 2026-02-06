<?php
/**
Plugin Name: AllAccessible
Plugin URI: https://www.allaccessible.org/platform/wordpress/
Description: Unlock true digital accessibility with AllAccessible - a comprehensive WordPress plugin driving your website towards WCAG/ADA compliance. Empower your users with a fully customizable accessibility widget, and enhance their experience with our premium AI-powered features.
Version: 2.0.4
Requires PHP: 7
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
require_once plugin_dir_path(__FILE__) . 'inc/VersionManager.php';

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
// require_once plugin_dir_path(__FILE__) . 'inc/api/RestController.php'; // Disabled - Page scores feature hidden for now
// require_once plugin_dir_path(__FILE__) . 'inc/EditorMetaBox.php'; // Disabled - Page scores feature hidden for now


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
}
register_activation_hook(__FILE__, 'AllAccessible_Activation');

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

        // Note: Tier is no longer stored in WordPress options
        // It's always fetched fresh from the /validate API endpoint
        // This ensures tier updates (free → paid) are reflected immediately

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
