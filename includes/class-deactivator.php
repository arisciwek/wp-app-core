<?php
/**
 * Plugin Deactivator Class
 *
 * @package     WP_App_Core
 * @subpackage  Includes
 * @version     1.0.0
 * @author      arisciwek
 *
 * Description: Menangani proses deaktivasi plugin:
 *              - Capability cleanup (remove from all roles)
 *              - Platform role removal (only in development mode)
 *              - Settings cleanup
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial creation
 * - Platform role and capability cleanup
 */

use WPAppCore\Models\Settings\PlatformPermissionModel;

// Load RoleManager
require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-role-manager.php';

class WP_App_Core_Deactivator {
    private static function debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[WP_App_Core_Deactivator] {$message}");
        }
    }

    private static function should_clear_data() {
        $dev_settings = get_option('wp_app_core_development_settings', [
            'enable_development' => 0,
            'clear_data_on_deactivate' => 0
        ]);

        // Both development mode and clear data option must be enabled
        return !empty($dev_settings['enable_development']) && !empty($dev_settings['clear_data_on_deactivate']);
    }

    public static function deactivate() {
        try {
            $should_clear_data = self::should_clear_data();

            // Always remove capabilities from all roles
            self::remove_capabilities();

            // Only remove roles in development mode
            if ($should_clear_data) {
                self::remove_roles();
                self::cleanupOptions();
            }

            self::debug("Plugin deactivation complete");

        } catch (\Exception $e) {
            self::debug("Error during deactivation: " . $e->getMessage());
        }
    }

    private static function remove_capabilities() {
        try {
            $permission_model = new PlatformPermissionModel();
            $capabilities = array_keys($permission_model->getAllCapabilities());

            // Remove platform capabilities from all roles
            foreach (get_editable_roles() as $role_name => $role_info) {
                $role = get_role($role_name);
                if (!$role) continue;

                foreach ($capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }

            self::debug("Platform capabilities removed from all roles");
        } catch (\Exception $e) {
            self::debug("Error removing capabilities: " . $e->getMessage());
        }
    }

    private static function remove_roles() {
        try {
            // Remove all platform roles using RoleManager
            $plugin_roles = WP_App_Core_Role_Manager::getRoleSlugs();
            foreach ($plugin_roles as $role_slug) {
                if (WP_App_Core_Role_Manager::roleExists($role_slug)) {
                    remove_role($role_slug);
                    self::debug("Removed role: {$role_slug}");
                }
            }

            self::debug("All platform roles removed successfully");
        } catch (\Exception $e) {
            self::debug("Error removing roles: " . $e->getMessage());
        }
    }

    private static function cleanupOptions() {
        try {
            delete_option('wp_app_core_version');
            delete_option('wp_app_core_platform_settings');
            delete_option('wp_app_core_email_settings');
            delete_option('wp_app_core_security_authentication');
            delete_option('wp_app_core_security_session');
            delete_option('wp_app_core_security_policy');
            delete_option('wp_app_core_development_settings');
            self::debug("Platform settings cleared");
        } catch (\Exception $e) {
            self::debug("Error cleaning up options: " . $e->getMessage());
        }
    }
}
