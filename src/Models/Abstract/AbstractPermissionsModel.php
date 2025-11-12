<?php
/**
 * Abstract Permissions Model
 *
 * @package     WP_App_Core
 * @subpackage  Models/Abstract
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Models/Abstract/AbstractPermissionsModel.php
 *
 * Description: Base model untuk permission management across all plugins.
 *              Provides standardized capability management via WordPress Roles API.
 *              Child plugins only need to define capabilities and default mappings.
 *
 * Usage in Child Plugin:
 * ```php
 * class CustomerPermissionModel extends AbstractPermissionsModel {
 *     protected function getRoleManagerClass(): string {
 *         return 'WP_Customer_Role_Manager';
 *     }
 *
 *     public function getAllCapabilities(): array {
 *         return [
 *             'view_customer_list' => 'Lihat Daftar Customer',
 *             // ... more capabilities
 *         ];
 *     }
 *
 *     public function getCapabilityGroups(): array {
 *         return [
 *             'customer' => [
 *                 'title' => 'Customer',
 *                 'description' => 'Customer Permissions',
 *                 'caps' => ['view_customer_list', ...]
 *             ]
 *         ];
 *     }
 *
 *     public function getDefaultCapabilitiesForRole(string $role_slug): array {
 *         return ['customer_admin' => ['view_customer_list' => true, ...]];
 *     }
 * }
 * ```
 *
 * Changelog:
 * 1.0.0 - 2025-01-12 (TODO-1206)
 * - Initial creation
 * - Standardized resetToDefault() pattern
 * - Role capability management
 * - Capability matrix generation
 */

namespace WPAppCore\Models\Abstract;

abstract class AbstractPermissionsModel {

    /**
     * Abstract methods - MUST be implemented by child class
     */
    abstract protected function getRoleManagerClass(): string;
    abstract public function getAllCapabilities(): array;
    abstract public function getCapabilityGroups(): array;
    abstract public function getCapabilityDescriptions(): array;
    abstract public function getDefaultCapabilitiesForRole(string $role_slug): array;

    /**
     * Get all available capabilities
     * Wrapper for abstract method - allows filtering
     *
     * @return array Array of capability_slug => capability_label pairs
     */
    public function getCapabilities(): array {
        $capabilities = $this->getAllCapabilities();

        // Allow filtering by plugin
        $role_manager = $this->getRoleManagerClass();
        $plugin_prefix = $this->getPluginPrefixFromRoleManager($role_manager);

        return apply_filters("{$plugin_prefix}_capabilities", $capabilities);
    }

    /**
     * Get capability groups for nested tabs
     * Wrapper for abstract method - allows filtering
     *
     * @return array Array of group_slug => group_data
     */
    public function getGroups(): array {
        $groups = $this->getCapabilityGroups();

        // Allow filtering by plugin
        $role_manager = $this->getRoleManagerClass();
        $plugin_prefix = $this->getPluginPrefixFromRoleManager($role_manager);

        return apply_filters("{$plugin_prefix}_capability_groups", $groups);
    }

    /**
     * Check if role has specific capability
     *
     * @param string $role_name Role slug
     * @param string $capability Capability slug
     * @return bool True if role has capability
     */
    public function roleHasCapability(string $role_name, string $capability): bool {
        $role = get_role($role_name);
        if (!$role) {
            return false;
        }
        return $role->has_cap($capability);
    }

    /**
     * Add default capabilities to roles
     * Called during plugin activation or when resetting permissions
     */
    public function addCapabilities(): void {
        $role_manager = $this->getRoleManagerClass();

        // Add all capabilities to administrator
        $admin = get_role('administrator');
        if ($admin) {
            foreach (array_keys($this->getAllCapabilities()) as $cap) {
                $admin->add_cap($cap);
            }
        }

        // Add default capabilities to plugin roles
        $plugin_roles = $role_manager::getRoleSlugs();
        foreach ($plugin_roles as $role_slug) {
            $role = get_role($role_slug);
            if ($role) {
                // Add 'read' capability explicitly - required for wp-admin access
                $role->add_cap('read');

                $default_caps = $this->getDefaultCapabilitiesForRole($role_slug);
                foreach ($default_caps as $cap => $enabled) {
                    // Skip 'read' as it's already added above
                    if ($cap === 'read') {
                        continue;
                    }

                    if ($enabled && isset($this->getAllCapabilities()[$cap])) {
                        $role->add_cap($cap);
                    } else if (!$enabled) {
                        $role->remove_cap($cap);
                    }
                }
            }
        }
    }

    /**
     * Reset permissions to default
     * Uses direct DB manipulation pattern from wp-app-core, wp-customer, wp-agency
     *
     * @return bool True if reset successful
     */
    public function resetToDefault(): bool {
        global $wpdb;

        try {
            $role_manager = $this->getRoleManagerClass();
            $plugin_prefix = $this->getPluginPrefixFromRoleManager($role_manager);

            error_log("[{$plugin_prefix}PermissionModel] resetToDefault() START - Using direct DB manipulation");

            // CRITICAL: Increase execution limits
            $old_time_limit = ini_get('max_execution_time');
            @set_time_limit(120);
            error_log("[{$plugin_prefix}PermissionModel] Time limit set to 120 seconds");

            // Get WordPress roles option from database
            $wp_user_roles = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = '{$wpdb->prefix}user_roles'");
            $roles = maybe_unserialize($wp_user_roles);
            error_log("[{$plugin_prefix}PermissionModel] Retrieved " . count($roles) . " roles from database");

            $modified = false;

            foreach ($roles as $role_name => $role_data) {
                error_log("[{$plugin_prefix}PermissionModel] Processing role: " . $role_name);

                // Only process plugin roles + administrator
                $is_plugin_role = $role_manager::isPluginRole($role_name);
                $is_admin = $role_name === 'administrator';

                if (!$is_plugin_role && !$is_admin) {
                    error_log("[{$plugin_prefix}PermissionModel] Skipping " . $role_name);
                    continue;
                }

                // Remove all plugin capabilities
                error_log("[{$plugin_prefix}PermissionModel] Removing plugin capabilities from " . $role_name);
                foreach (array_keys($this->getAllCapabilities()) as $cap) {
                    if (isset($roles[$role_name]['capabilities'][$cap])) {
                        unset($roles[$role_name]['capabilities'][$cap]);
                        $modified = true;
                    }
                }

                // Add capabilities back
                if ($role_name === 'administrator') {
                    error_log("[{$plugin_prefix}PermissionModel] Adding all capabilities to administrator");
                    foreach (array_keys($this->getAllCapabilities()) as $cap) {
                        $roles[$role_name]['capabilities'][$cap] = true;
                        $modified = true;
                    }
                } else if ($is_plugin_role) {
                    error_log("[{$plugin_prefix}PermissionModel] Adding default capabilities to " . $role_name);
                    // Add read capability
                    $roles[$role_name]['capabilities']['read'] = true;

                    // Add default capabilities
                    $default_caps = $this->getDefaultCapabilitiesForRole($role_name);
                    foreach ($default_caps as $cap => $enabled) {
                        if ($enabled && isset($this->getAllCapabilities()[$cap])) {
                            $roles[$role_name]['capabilities'][$cap] = true;
                            $modified = true;
                        }
                    }
                }
                error_log("[{$plugin_prefix}PermissionModel] Completed processing " . $role_name);
            }

            // Save back to database if modified
            if ($modified) {
                error_log("[{$plugin_prefix}PermissionModel] Saving modified roles to database");
                $updated = update_option($wpdb->prefix . 'user_roles', $roles);
                error_log("[{$plugin_prefix}PermissionModel] Database update result: " . ($updated ? 'SUCCESS' : 'NO CHANGE'));
            }

            error_log("[{$plugin_prefix}PermissionModel] All roles processed successfully");
            error_log("[{$plugin_prefix}PermissionModel] resetToDefault() END - returning TRUE");

            // Restore time limit
            @set_time_limit($old_time_limit);

            return true;

        } catch (\Exception $e) {
            error_log("[{$plugin_prefix}PermissionModel] EXCEPTION in resetToDefault(): " . $e->getMessage());
            error_log("[{$plugin_prefix}PermissionModel] Stack trace: " . $e->getTraceAsString());

            // Restore time limit
            if (isset($old_time_limit)) {
                @set_time_limit($old_time_limit);
            }

            error_log("[{$plugin_prefix}PermissionModel] resetToDefault() END - returning FALSE");
            return false;
        }
    }

    /**
     * Update role capabilities
     * Updates single capability for a role (called from AJAX save)
     *
     * @param string $role_name Role slug
     * @param array $capabilities Array of capability => enabled pairs
     * @return bool True if updated successfully
     */
    public function updateRoleCapabilities(string $role_name, array $capabilities): bool {
        // Don't allow modifying administrator role
        if ($role_name === 'administrator') {
            return false;
        }

        $role = get_role($role_name);
        if (!$role) {
            return false;
        }

        // Update capabilities
        foreach ($capabilities as $cap => $enabled) {
            if ($enabled && isset($this->getAllCapabilities()[$cap])) {
                $role->add_cap($cap);
            } else {
                $role->remove_cap($cap);
            }
        }

        return true;
    }

    /**
     * Get role capabilities matrix
     * Returns current state of all roles and their capabilities
     *
     * @return array Array of role_name => [name, capabilities]
     */
    public function getRoleCapabilitiesMatrix(): array {
        $matrix = [];
        $roles = get_editable_roles();

        foreach ($roles as $role_name => $role_info) {
            $role = get_role($role_name);
            if (!$role) continue;

            $matrix[$role_name] = [
                'name' => $role_info['name'],
                'capabilities' => []
            ];

            foreach (array_keys($this->getAllCapabilities()) as $cap) {
                $matrix[$role_name]['capabilities'][$cap] = $role->has_cap($cap);
            }
        }

        return $matrix;
    }

    /**
     * Get plugin prefix from RoleManager class name
     * Helper method to extract plugin prefix for logging and filters
     *
     * @param string $role_manager_class RoleManager class name
     * @return string Plugin prefix (e.g., 'wpapp', 'customer', 'agency')
     */
    private function getPluginPrefixFromRoleManager(string $role_manager_class): string {
        // Extract from class name
        // WP_App_Core_Role_Manager -> wpapp
        // WP_Customer_Role_Manager -> customer
        // WP_Agency_Role_Manager -> agency

        if (strpos($role_manager_class, 'WP_App_Core') !== false) {
            return 'wpapp';
        } else if (strpos($role_manager_class, 'WP_Customer') !== false) {
            return 'customer';
        } else if (strpos($role_manager_class, 'WP_Agency') !== false) {
            return 'agency';
        }

        // Fallback: lowercase first part before _Role_Manager
        $parts = explode('_', $role_manager_class);
        return strtolower($parts[1] ?? 'plugin');
    }

    /**
     * Get default capabilities for a specific role
     * This is a public wrapper that child classes must implement
     * Allows external access to default capability mappings
     *
     * @param string $role_slug Role slug
     * @return array Array of capability => bool pairs
     */
    public function getDefaults(string $role_slug): array {
        return $this->getDefaultCapabilitiesForRole($role_slug);
    }
}
