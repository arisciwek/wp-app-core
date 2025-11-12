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
            // Global scope options (wp_app_core_*)
            delete_option('wp_app_core_version');
            delete_option('wp_app_core_development_settings');

            // Local scope options (platform_*)
            delete_option('platform_settings');
            delete_option('platform_email_settings');
            delete_option('platform_security_authentication');
            delete_option('platform_security_session');
            delete_option('platform_security_policy');

            self::debug("Platform settings deleted from database");

            // Clear all caches
            self::clearAllCaches();

            self::debug("Platform settings and caches cleared");
        } catch (\Exception $e) {
            self::debug("Error cleaning up options: " . $e->getMessage());
        }
    }

    /**
     * Clear all caches (WordPress Object Cache, W3 Total Cache, etc.)
     *
     * @return void
     */
    private static function clearAllCaches() {
        try {
            // 1. Clear specific platform cache keys from PlatformCacheManager
            // Cache keys follow pattern: {option_name}_data
            $cache_keys = [
                'platform_settings_data',
                'platform_email_settings_data',
                'platform_security_authentication_data',
                'platform_security_session_data',
                'platform_security_policy_data',
            ];

            foreach ($cache_keys as $key) {
                wp_cache_delete($key, 'wp_app_core');
            }
            self::debug("Platform cache keys deleted: " . count($cache_keys) . " keys");

            // 2. Flush WordPress Object Cache (Memcached/Redis)
            wp_cache_flush();
            self::debug("WordPress Object Cache flushed");

            // 3. Clear W3 Total Cache if active
            if (function_exists('w3tc_flush_all')) {
                w3tc_flush_all();
                self::debug("W3 Total Cache flushed");
            }

            // 4. Clear WP Super Cache if active
            if (function_exists('wp_cache_clear_cache')) {
                wp_cache_clear_cache();
                self::debug("WP Super Cache flushed");
            }

            // 5. Clear transients related to platform
            global $wpdb;
            $wpdb->query(
                "DELETE FROM {$wpdb->options}
                WHERE option_name LIKE '_transient_platform_%'
                OR option_name LIKE '_transient_timeout_platform_%'
                OR option_name LIKE '_transient_wp_app_core_%'
                OR option_name LIKE '_transient_timeout_wp_app_core_%'"
            );
            self::debug("Platform transients deleted");

        } catch (\Exception $e) {
            self::debug("Error clearing caches: " . $e->getMessage());
        }
    }
}
