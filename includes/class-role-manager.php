<?php
/**
 * Role Manager Class
 *
 * @package     WP_App_Core
 * @subpackage  Includes
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/includes/class-role-manager.php
 *
 * Description: Centralized role management for WP App Core plugin.
 *              Single source of truth untuk platform role definitions.
 *              Accessible untuk plugin lain dan internal components.
 *
 * Usage:
 * - Get all roles: WP_App_Core_Role_Manager::getRoles()
 * - Get role slugs: WP_App_Core_Role_Manager::getRoleSlugs()
 * - Check if role exists: WP_App_Core_Role_Manager::roleExists($slug)
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial creation
 * - Platform staff role definitions
 * - Role management utilities
 */

defined('ABSPATH') || exit;

class WP_App_Core_Role_Manager {
    /**
     * Get all available platform roles with their display names
     * Single source of truth for roles in the plugin
     *
     * @return array Array of role_slug => role_name pairs
     */
    public static function getRoles(): array {
        return [
            'platform_super_admin' => __('Platform Super Admin', 'wp-app-core'),
            'platform_admin' => __('Platform Admin', 'wp-app-core'),
            'platform_manager' => __('Platform Manager', 'wp-app-core'),
            'platform_support' => __('Platform Support', 'wp-app-core'),
            'platform_finance' => __('Platform Finance', 'wp-app-core'),
            'platform_analyst' => __('Platform Analyst', 'wp-app-core'),
            'platform_viewer' => __('Platform Viewer', 'wp-app-core'),
        ];
    }

    /**
     * Get only role slugs
     *
     * @return array Array of role slugs
     */
    public static function getRoleSlugs(): array {
        return array_keys(self::getRoles());
    }

    /**
     * Check if a role is managed by this plugin
     *
     * @param string $role_slug Role slug to check
     * @return bool True if role is managed by this plugin
     */
    public static function isPluginRole(string $role_slug): bool {
        return array_key_exists($role_slug, self::getRoles());
    }

    /**
     * Check if a WordPress role exists
     *
     * @param string $role_slug Role slug to check
     * @return bool True if role exists in WordPress
     */
    public static function roleExists(string $role_slug): bool {
        return get_role($role_slug) !== null;
    }

    /**
     * Get display name for a role
     *
     * @param string $role_slug Role slug
     * @return string|null Role display name or null if not found
     */
    public static function getRoleName(string $role_slug): ?string {
        $roles = self::getRoles();
        return $roles[$role_slug] ?? null;
    }


    /**
     * Get role description
     *
     * @param string $role_slug Role slug
     * @return string Role description
     */
    public static function getRoleDescription(string $role_slug): string {
        $descriptions = [
            'platform_super_admin' => __('Full access to all platform features and settings', 'wp-app-core'),
            'platform_admin' => __('Manage platform operations, tenants, and support', 'wp-app-core'),
            'platform_manager' => __('Oversee platform operations and analytics', 'wp-app-core'),
            'platform_support' => __('Handle customer support and helpdesk', 'wp-app-core'),
            'platform_finance' => __('Manage financial operations and billing', 'wp-app-core'),
            'platform_analyst' => __('Access analytics and generate reports', 'wp-app-core'),
            'platform_viewer' => __('View-only access to platform data', 'wp-app-core'),
        ];

        return $descriptions[$role_slug] ?? '';
    }

    /**
     * Create all platform roles in WordPress
     * Should be called during plugin activation
     *
     * @return void
     */
    public static function createRoles(): void {
        $roles = self::getRoles();

        foreach ($roles as $role_slug => $role_name) {
            // Check if role already exists
            if (!self::roleExists($role_slug)) {
                // Create role with basic 'read' capability
                add_role(
                    $role_slug,
                    $role_name,
                    ['read' => true]
                );
            }
        }
    }

    /**
     * Remove all platform roles from WordPress
     * Should be called during plugin deactivation/uninstall
     *
     * @return void
     */
    public static function removeRoles(): void {
        $role_slugs = self::getRoleSlugs();

        foreach ($role_slugs as $role_slug) {
            if (self::roleExists($role_slug)) {
                remove_role($role_slug);
            }
        }
    }
}
