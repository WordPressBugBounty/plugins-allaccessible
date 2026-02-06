<?php
/**
 * AllAccessible Version Manager
 *
 * Handles version checking and upgrade routines
 *
 * @package     AllAccessible
 * @since       1.3.7
 */

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

class AllAccessible_VersionManager {
    
    /**
     * Initialize the version manager
     */
    public static function init() {
        add_action('plugins_loaded', array(__CLASS__, 'check_version'));
    }
    
    /**
     * Check if plugin version has changed and run upgrade routines if needed
     */
    public static function check_version() {
        $installed_version = get_option('aacb_version', '0');
        
        // If version has changed, run upgrade routines
        if (version_compare($installed_version, AACB_VERSION, '<')) {
            self::run_upgrade_routines($installed_version);
            
            // Update stored version number
            update_option('aacb_version', AACB_VERSION);
        }
    }
    
    /**
     * Run version-specific upgrade routines
     * 
     * @param string $from_version The previously installed version
     */
    private static function run_upgrade_routines($from_version) {
        // Example upgrade routine for version 1.3.0
        if (version_compare($from_version, '1.3.0', '<')) {
            self::upgrade_to_1_3_0();
        }
        
        // Example upgrade routine for version 1.3.5
        if (version_compare($from_version, '1.3.5', '<')) {
            self::upgrade_to_1_3_5();
        }
        
        // Add future upgrade routines here
        if (version_compare($from_version, '1.3.7', '<')) {
            self::upgrade_to_1_3_7();
        }

        // Upgrade to 2.0.0 - Handle wizard migration
        if (version_compare($from_version, '2.0.0', '<')) {
            self::upgrade_to_2_0_0();
        }

        // Always run this to ensure database is up to date
        self::update_db_check();
    }
    
    /**
     * Upgrade routine for version 1.3.0
     */
    private static function upgrade_to_1_3_0() {
        // Example: Add new option
        // add_option('aacb_new_feature_enabled', true);
    }
    
    /**
     * Upgrade routine for version 1.3.5
     */
    private static function upgrade_to_1_3_5() {
        // Example: Security improvements
        // delete_option('aacb_deprecated_setting');
    }
    
    /**
     * Upgrade routine for version 1.3.7
     */
    private static function upgrade_to_1_3_7() {
        // Migrate to new version management system
        $options = get_option('aacb_options', array());
        if (!empty($options) && !isset($options['version'])) {
            $options['version'] = AACB_VERSION;
            update_option('aacb_options', $options);
        }
    }

    /**
     * Upgrade routine for version 2.0.0
     * Handles migration from 1.x to 2.0 - new wizard system
     */
    private static function upgrade_to_2_0_0() {
        $account_id = get_option('aacb_accountID');

        // If user already has accountID (upgraded from 1.x), mark wizard as completed
        if (!empty($account_id)) {
            update_option('aacb_wizard_completed', true);

            // Set tier to 'unknown' since we don't know if they were free or premium in 1.x
            // They can continue using their existing account
            if (!get_option('aacb_account_tier')) {
                update_option('aacb_account_tier', 'legacy');
            }
        }

        // Clean up old options that are no longer used in 2.0
        delete_option('aacb_engagement_score');
        delete_option('aacb_widget_opens_count');
        delete_option('aacb_days_since_install');
        delete_option('aacb_conversion_events');
        delete_option('aacb_email_capture_shown');
        delete_option('aacb_email_capture_count');
    }

    /**
     * Update database check
     */
    private static function update_db_check() {
        // This is where you would run any database schema updates if needed
    }
}

// Initialize the version manager
AllAccessible_VersionManager::init();
