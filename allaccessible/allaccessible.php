<?php
/**
Plugin Name: AllAccessible
Plugin URI: https://www.allaccessible.org/platform/wordpress/
Description: Unlock true digital accessibility with AllAccessible - a comprehensive WordPress plugin driving your website towards WCAG/ADA compliance. Empower your users with a fully customizable accessibility widget, and enhance their experience with our premium AI-powered features.
Version: 1.3.4
Requires PHP: 7
Author: AllAccessible Team
Author URI: https://www.allaccessible.org/
Text Domain: allaccessible
Domain Path: /languages
 */

/**
 * Copyright (C) 2023 AllAccessible.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @package     AllAccessible
 * @version     1.3.4
 * @since       1.0
 * @author      AllAccessible Team
 * @copyright   Copyright (c) 2022 AllAccessible
 * @link        https://www.allaccessible.org/
 * @license     http://www.gnu.org/licenses/gpl.html
 */

if(!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}
// =============================================
// Define Global Constants
// =============================================
aacb_define_constants();
function aacb_define_constants() {
//    $aacb_siteOptions = aacb_siteOptions();
    define('AACB_NAME', isset($aacb_siteOptions->isWhitelabel) && $aacb_siteOptions->isWhitelabel ? _e( "Accessibility", 'allaccessible' ) : 'AllAccessible');
    define('AACB_VERSION','1.3.4');
    define('AACB_WP_MIN_VERSION','5.0');
    define('AACB_TEXT','allaccessible');
    define('AACB_DIR', dirname( plugin_basename( __FILE__ ) ) );
    define('AACB_URL', plugin_dir_url( __FILE__ ) );
    define('AACB_PATH', plugin_dir_path( __FILE__ ) );
    define('AACB_JS', AACB_URL . trailingslashit('assets') );
    define('AACB_CSS', AACB_URL . trailingslashit('assets') );
    define('AACB_IMG', AACB_URL . trailingslashit('assets') );
    define('AACB_INC', AACB_PATH . trailingslashit('inc') );
    define('AACB_SUPPORT','https://support.allaccessible.org/');
}
function aacb_load_textdomain() {
    load_plugin_textdomain( 'allaccessible', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'aacb_load_textdomain' );

/**
 *  Load AllAccessible options
 */
function AllAccessible_getSiteOptions() {
    $apiUrl      ='https://app.allaccessible.org/api/get-site-options/';
    $apiResponse = wp_remote_post( $apiUrl,
        [
            'method'   =>'POST',
            'sslverify'=> false,
            'headers'  => [
                'content-type'=>'application/json',
            ],
            'body'       => json_encode(array('siteId'=> get_option('aacb_siteID')))
        ]
    );

    return json_decode( wp_remote_retrieve_body( $apiResponse ) );

}

/**
 *  Load AllAccessible Settings Page
 */
function AllAccessible_settings() {
    // Removes all admin notices for this page
    remove_all_actions( 'admin_notices' );
    //TODO - improve
    $GLOBALS['aacb_accountID'] = get_option('aacb_accountID');

    if(!get_option('aacb_siteID') && get_option('aacb_accountID')){
        $apiUrl      ='https://app.allaccessible.org/api/get-site-id/';
        $apiResponse = wp_remote_post( $apiUrl,
            [
                'method'   =>'POST',
                'sslverify'=> false,
                'headers'  => [
                    'content-type'=>'application/json',
                ],
                'body'       => json_encode(array('pageUrl'=> get_bloginfo('wpurl'),'accountID'=> get_option('aacb_accountID')))
            ]
        );
        $apiBody     = json_decode( wp_remote_retrieve_body( $apiResponse ) );

        update_option('aacb_siteID', $apiBody );
    }

    $GLOBALS['aacb_accountID'] = get_option('aacb_siteID');

    if(get_option('aacb_siteID')){
        $GLOBALS['aacb_siteOptions'] = AllAccessible_getSiteOptions();
    }

    include AACB_INC .'AllAccessibleSettings.php';
}

//=========================
// Add Script to Header
//=========================

function AllAccessible_loadWidget() {
    if (!is_admin() && empty($_GET['et_fb'])) {
        $account_id = get_option('aacb_accountID') ? esc_attr(get_option('aacb_accountID')) : 'wp_vFtGhKjLm';
        $script_src = "https://app.allaccessible.org/api/{$account_id}.js";
        $aacbPreview = false;
        if(isset($_GET['aacb_preview']) && $_GET['aacb_preview']){
            $aacbPreview = true;
        }
        ?>
        <script data-accessible-account-id="<?php echo $account_id; ?>" id="allAccessibleWidget" src="<?php echo $script_src; ?>" defer></script>
        <?php if($aacbPreview) { ?>
            <script>
                function previewAllAccessible() {
                    window.addEventListener('load', (event) => {
                        var aacbButton = document.getElementById('accessibility-trigger-button');
                        if (aacbButton) {
                            aacbButton.click();
                        }
                    });
                }
            </script>
        <?php }
    }
}

add_action('wp_head', 'AllAccessible_loadWidget');

register_activation_hook( __FILE__ ,'AllAccessible_Activation');
register_deactivation_hook( __FILE__ ,'AllAccessible_Deactivation');


// =============================================
// Load JS and CSS
// =============================================

/**
 * Add admin scripts
 */
function AllAccessible_LoadScript($hook){
    // Check if we're on the AllAccessible page
    if ('toplevel_page_allaccessible'== $hook ) {
        wp_enqueue_style('allaccessible-spectrum-style', AACB_CSS .'spectrum.min.css',array(),AACB_VERSION);
        wp_enqueue_script('allaccessible-spectrum', AACB_CSS .'spectrum.js',array(),AACB_VERSION);
        wp_enqueue_style('allaccessible-select2', AACB_CSS .'select2.min.css',array(),AACB_VERSION);
        wp_enqueue_script('allaccessible-select2-script', AACB_CSS .'select2.min.js',array(),AACB_VERSION);
    }

    wp_enqueue_style('allaccessible-admin-style', AACB_CSS .'allaccessible-style.css',array(),AACB_VERSION);
    wp_register_script('allaccessible-custom', AACB_JS .'allaccessible-custom.js',array(),AACB_VERSION);
    $ajax_data = array(
        'ajax_url' => admin_url('admin-ajax.php')
    );
    wp_localize_script('allaccessible-custom', 'ajax_object', $ajax_data);
    wp_enqueue_script('allaccessible-custom');
}
add_action('admin_enqueue_scripts','AllAccessible_LoadScript');

function aacb_dismiss_notice() {
    update_option('aacb_hide_premium_notice', true);
    echo 'success';
    wp_die();
}
add_action('wp_ajax_aacb_dismiss_notice', 'aacb_dismiss_notice');

// ===================================================
// Setup Core AllAccessible Admin Options and Settings
// ===================================================

/**
 *  Admin dashboard menu bar
 */
add_action('admin_menu','AllAccessible_integration');

function AllAccessible_integration() {
    add_menu_page(
        AACB_NAME,
        AACB_NAME,
        'manage_options',
        'allaccessible',
        'allaccessible_settings',
        'data:image/svg+xml;base64,PHN2ZyBpZD0iTGF5ZXJfMSIgZGF0YS1uYW1lPSJMYXllciAxIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB2aWV3Qm94PSIwIDAgMzE3LjkgMzE3Ljg5Ij48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImxpbmVhci1ncmFkaWVudCIgeDE9IjE4My4yOSIgeTE9IjIxOC4yMiIgeDI9IjE4My4yOSIgeTI9IjQxLjI0IiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHN0b3Agb2Zmc2V0PSIwIiBzdG9wLWNvbG9yPSIjMGY4Y2ZhIi8+PHN0b3Agb2Zmc2V0PSIxIiBzdG9wLWNvbG9yPSIjNTRiOGZmIi8+PC9saW5lYXJHcmFkaWVudD48bGluZWFyR3JhZGllbnQgaWQ9ImxpbmVhci1ncmFkaWVudC0yIiB4MT0iMTU4Ljk0IiB5MT0iMzA5LjQ3IiB4Mj0iMTU4Ljk2IiB5Mj0iMjMuMDgiIGdyYWRpZW50VW5pdHM9InVzZXJTcGFjZU9uVXNlIj48c3RvcCBvZmZzZXQ9IjAiIHN0b3AtY29sb3I9IiMwOTU3YjAiLz48c3RvcCBvZmZzZXQ9IjEiIHN0b3AtY29sb3I9IiMyMTc5ZWIiLz48L2xpbmVhckdyYWRpZW50PjwvZGVmcz48cGF0aCBkPSJNMjU2LjYyLDU3Ljg4bC05Ny45LDk3LjlMMTEwLDEwN2MtMTcuOTMtMTcuOTMtNDUsOS4xOC0yNy4xMSwyNy4xMWw2Mi4yLDYyLjJhMTkuMDgsMTkuMDgsMCwwLDAsMTMuMzUsNS41OWguNjdhMTkuMTQsMTkuMTQsMCwwLDAsMTMuMzUtNS41OUwyODMuNzMsODVDMzAxLjY3LDY3LjA2LDI3NC41NSw0MCwyNTYuNjIsNTcuODhaIiBzdHlsZT0iZmlsbC1ydWxlOmV2ZW5vZGQ7ZmlsbDp1cmwoI2xpbmVhci1ncmFkaWVudCkiLz48cGF0aCBkPSJNMTU5LDYwLjMxYTI3Ljg2LDI3Ljg2LDAsMSwxLTI3Ljg2LDI3Ljg1QTI3Ljg1LDI3Ljg1LDAsMCwxLDE1OSw2MC4zMVpNMTU5LDBhMTU4Ljg4LDE1OC44OCwwLDEsMCwxMzguOSw4MS42MiwzMC40OCwzMC40OCwwLDAsMS03LDEwLjU0TDI3OC4yNiwxMDQuOGExMzEuMTEsMTMxLjExLDAsMCwxLTI1Ljc1LDE0NS44MmwtNDcuMzUtNDcuMzVjLTE3LjkzLTE3LjkzLTQ1LDkuMTgtMjcuMTEsMjcuMTFMMjIxLjY2LDI3NEExMzEuMjEsMTMxLjIxLDAsMCwxLDk2LDI3My44M2w0My42LTQzLjZjMTcuOTQtMTcuOTQtOS4xOS00NS0yNy4xMS0yNy4xMUw2NS4xNiwyNTAuNEExMzEsMTMxLDAsMCwxLDI0Mi4yOSw1Ny44OGw3LjE3LTcuMTZhMjkuMzMsMjkuMzMsMCwwLDEsMTcuMTktOC42N0ExNTguNDEsMTU4LjQxLDAsMCwwLDE1OSwwWiIgc3R5bGU9ImZpbGwtcnVsZTpldmVub2RkO2ZpbGw6dXJsKCNsaW5lYXItZ3JhZGllbnQtMikiLz48L3N2Zz4=',
        25
    );
}

/**
 * AllAccessible Initial Activation
 */
function AllAccessible_activation() {
    global $wpdb;
    $options = get_option('aacb_options');

    if ( ( $options['aacb_installed'] != 1 ) || ( ! is_array( $options ) ) ) {
        $opt = array(
            'aacb_installed'          => 1,
        );
        update_option('aacb_options', $opt );
    }

}

/**
 * AllAccessible Admin Notices
 */
function aacb_activated_notice() {
    $options = get_option('aacb_options');
    $hide_notice = get_option('aacb_hide_premium_notice');

    if ($options['aacb_installed'] && !get_option('aacb_accountID') && !$hide_notice) {
        $settings_url = admin_url('options-general.php?page=allaccessible');
        ?>
        <div id="aacb-premium-notice" class="notice notice-success is-dismissible">
            <p><?php _e( 'Activate AllAccessible Premium to Unlock Ai Accessibility Features.', 'allaccessible' ); ?> <a href="<?php echo $settings_url; ?>"><?php _e( 'Click Here', 'allaccessible' ); ?></a></p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'aacb_activated_notice');

/**
 * AllAccessible Setting Saver
 */
function AllAccessible_save_settings() {

    $opt_name = isset($_POST['opt_name']) ? sanitize_text_field($_POST['opt_name']) : null;
    $opt_value = isset($_POST['opt_value']) ? sanitize_text_field($_POST['opt_value']) : null;
    $update = update_option($opt_name, $opt_value);

}
add_action('wp_ajax_AllAccessible_save_settings','AllAccessible_save_settings');

/**
 * AllAccessible Deactivation
 */
function AllAccessible_Deactivation() {
    delete_option( 'aacb_options' );
    delete_option( 'aacb_installed' );
    delete_option( 'aacb_siteID' );
    delete_option( 'aacb_accountID');
    delete_option('aacb_hide_premium_notice');
}
/**
 * Deactivation Survey for Dev purposes.
 */
include ( AACB_INC ."/AllAccessibleDeactivationForm.php" );
add_filter('allaccessible_deactivate_feedback_form_plugins', function($plugins) {

    $plugins[] = (object)array(
        'slug'		=> AACB_TEXT,
        'version'	=> AACB_VERSION,
        'url' => get_bloginfo('wpurl')
    );

    return $plugins;

});

/**
 * Logs a debug statement to /wp-content/debug.log
 *
 * @param   string
 */
function AllAccessible_log_debug( $message ) {
    if ( WP_DEBUG === true ) {
        if ( is_array( $message ) || is_object( $message ) ) {
            error_log( print_r( $message, true ) );
        } else {
            error_log( esc_attr($message) );
        }
    }
}

/**
 * Hide all the unrelated notices from plugin page.
 *
 * @since 1.3
 * @return void
 */
function hide_admin_notices() {
    // Bail if we're not on a AllAccessible screen.
    if ( empty( $_REQUEST['page'] ) || ! preg_match( '/allaccessible/', esc_html( wp_unslash( $_REQUEST['page'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        return;
    }
    global $wp_filter;

    $notices_type = array(
        'user_admin_notices',
        'admin_notices',
        'all_admin_notices',
    );

    foreach ( $notices_type as $type ) {
        if ( empty( $wp_filter[ $type ]->callbacks ) || ! is_array( $wp_filter[ $type ]->callbacks ) ) {
            continue;
        }

        foreach ( $wp_filter[ $type ]->callbacks as $priority => $hooks ) {
            foreach ( $hooks as $name => $arr ) {
                if ( is_object( $arr['function'] ) && $arr['function'] instanceof \Closure ) {
                    unset( $wp_filter[ $type ]->callbacks[ $priority ][ $name ] );
                    continue;
                }
                $class = ! empty( $arr['function'][0] ) && is_object( $arr['function'][0] ) ? strtolower( get_class( $arr['function'][0] ) ) : '';

                if ( ! empty( $class ) && preg_match( '/^(?:aacb)/', $class ) ) {
                    continue;
                }
                if ( ! empty( $name ) && ! preg_match( '/^(?:aacb)/', $name ) ) {
                    unset( $wp_filter[ $type ]->callbacks[ $priority ][ $name ] );
                }
            }
        }
    }
}
// Hide the unrelated admin notices.
add_action( 'admin_print_scripts', 'hide_admin_notices');