<?php
/**
 * File: class-activator.php
 * Path: /wp-app-core/includes/class-activator.php
 * Description: Handles plugin activation
 *
 * @package     WP_App_Core
 * @subpackage  Includes
 * @version     1.0.0
 * @author      arisciwek
 *
 * Description: Menangani proses aktivasi plugin.
 *              Termasuk di dalamnya:
 *              - Setup platform roles dan capabilities
 *              - Menambahkan versi plugin ke options table
 *
 * Dependencies:
 * - WP_App_Core_Role_Manager untuk role management
 * - WPAppCore\Models\Settings\PlatformPermissionModel untuk setup capabilities
 * - WPAppCore\Database\Installer untuk database table installation
 * - WordPress Options API
 *
 * Changelog:
 * 1.0.1 - 2025-10-19
 * - Added database table installation
 *
 * 1.0.0 - 2025-10-19
 * - Initial creation
 * - Added platform role creation
 * - Added capabilities setup
 * - Added version management
 */

use WPAppCore\Models\Settings\PlatformPermissionModel;
use WPAppCore\Database\Installer;

// Load RoleManager
require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-role-manager.php';

class WP_App_Core_Activator {
    private static function logError($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("WP_App_Core_Activator Error: {$message}");
        }
    }

    public static function activate() {
        try {
            // 1. Install database tables
            try {
                Installer::run();
                self::logError("Database tables installed successfully");
            } catch (\Exception $e) {
                self::logError('Error installing database tables: ' . $e->getMessage());
                throw $e;
            }

            // 2. Create platform roles if they don't exist
            $all_roles = WP_App_Core_Role_Manager::getRoles();

            foreach ($all_roles as $role_slug => $role_name) {
                if (!get_role($role_slug)) {
                    add_role(
                        $role_slug,
                        $role_name,
                        [] // Start with empty capabilities
                    );
                    self::logError("Created role: {$role_slug}");
                }
            }

            // 3. Initialize permission model and add capabilities
            try {
                $permission_model = new PlatformPermissionModel();
                $permission_model->addCapabilities(); // This will add caps to admin and platform roles
                self::logError("Platform capabilities added successfully");
            } catch (\Exception $e) {
                self::logError('Error adding capabilities: ' . $e->getMessage());
            }

            // 4. Add plugin version
            self::addVersion();

            // Flush rewrite rules
            flush_rewrite_rules();

        } catch (\Exception $e) {
            self::logError('Critical error during activation: ' . $e->getMessage());
            throw $e;
        }
    }

    private static function addVersion() {
        add_option('wp_app_core_version', WP_APP_CORE_VERSION);
    }
}
