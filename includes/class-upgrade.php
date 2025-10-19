<?php
/**
 * File: class-upgrade.php
 * Path: /wp-app-core/includes/class-upgrade.php
 * Description: Handles plugin upgrades and migrations
 *
 * @package     WP_App_Core
 * @subpackage  Includes
 * @version     1.0.0
 * @author      arisciwek
 *
 * Description: Menangani upgrade plugin saat versi berubah.
 *              Ensures backward compatibility dan data migration.
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial creation
 * - Added version checking mechanism
 * - Added upgrade routine for v1.0.1 (read capability)
 */

use WPAppCore\Models\Settings\PlatformPermissionModel;

class WP_App_Core_Upgrade {
    /**
     * Version option name in database
     */
    const VERSION_OPTION = 'wp_app_core_version';

    /**
     * Check and run upgrades if needed
     */
    public static function check_and_upgrade() {
        $current_version = get_option(self::VERSION_OPTION, '0.0.0');
        $new_version = WP_APP_CORE_VERSION;

        // Skip if same version
        if (version_compare($current_version, $new_version, '=')) {
            return;
        }

        self::log("Upgrading from {$current_version} to {$new_version}");

        // Run upgrade routines based on version
        if (version_compare($current_version, '1.0.1', '<')) {
            self::upgrade_to_1_0_1();
        }

        if (version_compare($current_version, '1.0.2', '<')) {
            self::upgrade_to_1_0_2();
        }

        // Update version in database
        update_option(self::VERSION_OPTION, $new_version);
        self::log("Upgrade completed to version {$new_version}");
    }

    /**
     * Upgrade routine for version 1.0.1
     * Adds base role 'platform_staff' to all existing platform users
     */
    private static function upgrade_to_1_0_1() {
        self::log("Running upgrade to 1.0.1 - Implementing base role system");

        try {
            // Load Role Manager
            require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-role-manager.php';

            // 1. Create base role if not exists
            $base_role = WP_App_Core_Role_Manager::getBaseRole();
            $base_role_name = WP_App_Core_Role_Manager::getBaseRoleName();

            if (!get_role($base_role)) {
                add_role($base_role, $base_role_name, ['read' => true]);
                self::log("Created base role: {$base_role} with 'read' capability");
            }

            // 2. Get all users with platform admin roles
            $admin_roles = WP_App_Core_Role_Manager::getAdminRoles();
            $users_updated = 0;

            foreach ($admin_roles as $admin_role => $role_name) {
                // Get all users with this admin role
                $users = get_users(['role' => $admin_role]);

                foreach ($users as $user) {
                    // Check if user already has base role
                    if (!in_array($base_role, $user->roles)) {
                        // Add base role (this will not remove existing roles)
                        $user->add_role($base_role);
                        $users_updated++;
                        self::log("Added base role '{$base_role}' to user {$user->ID} ({$user->user_login})");
                    }
                }
            }

            self::log("Upgrade to 1.0.1 completed - {$users_updated} users updated with base role");
            return true;

        } catch (\Exception $e) {
            self::log("Error in upgrade to 1.0.1: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Upgrade routine for version 1.0.2
     * Implements base role system - adds platform_staff role to all platform users
     */
    private static function upgrade_to_1_0_2() {
        self::log("Running upgrade to 1.0.2 - Implementing base role system");

        try {
            // Load Role Manager
            require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-role-manager.php';

            // 1. Create base role if not exists
            $base_role = WP_App_Core_Role_Manager::getBaseRole();
            $base_role_name = WP_App_Core_Role_Manager::getBaseRoleName();

            if (!get_role($base_role)) {
                add_role($base_role, $base_role_name, ['read' => true]);
                self::log("Created base role: {$base_role} with 'read' capability");
            } else {
                // Ensure base role has 'read' capability
                $role = get_role($base_role);
                if ($role && !$role->has_cap('read')) {
                    $role->add_cap('read');
                    self::log("Added 'read' capability to existing base role: {$base_role}");
                }
            }

            // 2. Get all users with platform admin roles and add base role
            $admin_roles = WP_App_Core_Role_Manager::getAdminRoles();
            $users_updated = 0;

            foreach ($admin_roles as $admin_role => $role_name) {
                // Get all users with this admin role
                $users = get_users(['role' => $admin_role]);

                foreach ($users as $user) {
                    // Check if user already has base role
                    if (!in_array($base_role, $user->roles)) {
                        // Add base role (this will not remove existing roles)
                        $user->add_role($base_role);
                        $users_updated++;
                        self::log("Added base role '{$base_role}' to user {$user->ID} ({$user->user_login})");
                    }
                }
            }

            self::log("Upgrade to 1.0.2 completed - {$users_updated} users updated with base role");

            // Flush cache to ensure role changes take effect immediately
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
                self::log("Cache flushed - role changes active");
            }

            return true;

        } catch (\Exception $e) {
            self::log("Error in upgrade to 1.0.2: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log upgrade messages
     */
    private static function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("WP_App_Core_Upgrade: {$message}");
        }
    }
}
