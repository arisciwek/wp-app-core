<?php
/**
 * Admin Bar Model Class
 *
 * @package     WP_App_Core
 * @subpackage  Models
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Models/AdminBarModel.php
 *
 * Description: Generic utilities untuk admin bar functionality.
 *              Menyediakan helper methods yang dapat digunakan oleh
 *              berbagai plugin (wp-customer, wp-agency, dll) untuk
 *              parsing WordPress capabilities dan permissions.
 *
 * Changelog:
 * 1.0.0 - 2025-01-18
 * - Initial creation (migrated from wp-agency)
 * - Added getRoleNamesFromCapabilities() - generic version
 * - Added getPermissionNamesFromUserId() - generic version
 * - Methods are plugin-agnostic (accept callbacks/parameters)
 * - Reusable across all plugins in the ecosystem
 */

namespace WPAppCore\Models;

defined('ABSPATH') || exit;

class AdminBarModel {

    /**
     * Extract role names from serialized capabilities string
     *
     * Generic version that works with any plugin's role system.
     * Accepts parameters instead of hardcoding dependencies.
     *
     * Example capabilities string:
     * a:2:{s:6:"agency";b:1;s:17:"agency_admin_unit";b:1;}
     *
     * Example output:
     * ['Agency', 'Admin Unit']
     *
     * @param string $capabilities_string Serialized capabilities from wp_usermeta
     * @param array $role_slugs_filter Array of role slugs to filter (only process these)
     * @param callable|null $role_name_resolver Callback function to get role display name
     *                                           Expected signature: function(string $role_slug): ?string
     * @return array Array of role display names
     *
     * @example
     * // In wp-agency:
     * $model = new AdminBarModel();
     * $role_names = $model->getRoleNamesFromCapabilities(
     *     $capabilities_string,
     *     WP_Agency_Role_Manager::getRoleSlugs(),
     *     ['WP_Agency_Role_Manager', 'getRoleName']
     * );
     *
     * // In wp-customer:
     * $model = new AdminBarModel();
     * $role_names = $model->getRoleNamesFromCapabilities(
     *     $capabilities_string,
     *     WP_Customer_Role_Manager::getRoleSlugs(),
     *     ['WP_Customer_Role_Manager', 'getRoleName']
     * );
     */
    public function getRoleNamesFromCapabilities(
        string $capabilities_string,
        array $role_slugs_filter = [],
        ?callable $role_name_resolver = null
    ): array {
        // DEBUG: Log start
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("=== AdminBarModel::getRoleNamesFromCapabilities START ===");
            error_log("Raw capabilities string: " . substr($capabilities_string, 0, 100) . "...");
        }

        // Unserialize the capabilities string
        // Format: a:2:{s:6:"agency";b:1;s:17:"agency_admin_unit";b:1;}
        $capabilities = @unserialize($capabilities_string);

        if (!is_array($capabilities)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("ERROR: Failed to unserialize capabilities");
                error_log("=== AdminBarModel::getRoleNamesFromCapabilities END ===");
            }
            return [];
        }

        // DEBUG: Log unserialized capabilities
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Unserialized capabilities (" . count($capabilities) . " items):");
            error_log(print_r($capabilities, true));
            error_log("Role slugs filter (" . count($role_slugs_filter) . " items):");
            error_log(print_r($role_slugs_filter, true));
        }

        $role_names = [];

        // Loop through capabilities and extract role names
        foreach ($capabilities as $role_slug => $has_cap) {
            // Skip if capability not granted
            if (!$has_cap) {
                continue;
            }

            // If filter provided, only process roles in the filter
            if (!empty($role_slugs_filter) && !in_array($role_slug, $role_slugs_filter)) {
                continue;
            }

            // Get role display name
            $role_name = null;

            // If resolver callback provided, use it
            if (is_callable($role_name_resolver)) {
                $role_name = call_user_func($role_name_resolver, $role_slug);
            }

            // Fallback: Try to get from WordPress role names
            if (!$role_name) {
                $wp_roles = wp_roles();
                $role_name = isset($wp_roles->role_names[$role_slug])
                    ? translate_user_role($wp_roles->role_names[$role_slug])
                    : null;
            }

            // Last resort: Use slug as display name (capitalized)
            if (!$role_name) {
                $role_name = ucwords(str_replace('_', ' ', $role_slug));
            }

            $role_names[] = $role_name;
        }

        // DEBUG: Log results
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Role names extracted (" . count($role_names) . " roles):");
            error_log(print_r($role_names, true));
            error_log("=== AdminBarModel::getRoleNamesFromCapabilities END ===");
        }

        return $role_names;
    }

    /**
     * Extract permission names from user's actual capabilities
     *
     * Generic version that works with any plugin's permission system.
     * Gets ALL capabilities user has (including inherited from roles)
     * and returns display names for permissions.
     *
     * This method uses WP_User object to get ACTUAL capabilities (roles + inherited permissions),
     * not just raw wp_usermeta which only contains role assignments.
     *
     * @param int $user_id WordPress user ID
     * @param array $role_slugs_to_skip Array of role slugs to exclude (these are roles, not permissions)
     * @param array $permission_labels Array of capability_slug => display_name mapping
     * @return array Array of permission display names
     *
     * @example
     * // In wp-agency:
     * $model = new AdminBarModel();
     * $permission_model = new PermissionModel();
     * $permission_names = $model->getPermissionNamesFromUserId(
     *     $user_id,
     *     WP_Agency_Role_Manager::getRoleSlugs(),
     *     $permission_model->getAllCapabilities()
     * );
     *
     * // In wp-customer:
     * $model = new AdminBarModel();
     * $permission_model = new CustomerPermissionModel();
     * $permission_names = $model->getPermissionNamesFromUserId(
     *     $user_id,
     *     WP_Customer_Role_Manager::getRoleSlugs(),
     *     $permission_model->getAllCapabilities()
     * );
     */
    public function getPermissionNamesFromUserId(
        int $user_id,
        array $role_slugs_to_skip = [],
        array $permission_labels = []
    ): array {
        // DEBUG: Log start
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("=== AdminBarModel::getPermissionNamesFromUserId START for user_id: {$user_id} ===");
            error_log("Role slugs to skip (" . count($role_slugs_to_skip) . " items):");
            error_log(print_r($role_slugs_to_skip, true));
            error_log("Permission labels provided (" . count($permission_labels) . " items):");
            error_log(print_r(array_keys($permission_labels), true));
        }

        // Get WP_User object to access ALL capabilities (roles + inherited permissions)
        $user = get_userdata($user_id);

        if (!$user) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("ERROR: User not found for user_id: {$user_id}");
                error_log("=== AdminBarModel::getPermissionNamesFromUserId END ===");
            }
            return [];
        }

        // Get ALL user capabilities (includes inherited from roles)
        // This is the CORRECT way to get actual permissions
        $all_caps = $user->allcaps;

        // DEBUG: Log all capabilities
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("ALL user capabilities (including inherited from roles) (" . count($all_caps) . " items):");
            error_log(print_r($all_caps, true));
        }

        $permission_names = [];
        $skipped_caps = [];

        // Loop through ALL user's actual capabilities (including inherited from roles)
        foreach ($all_caps as $cap_slug => $has_cap) {
            if (!$has_cap) {
                continue; // Skip if not granted
            }

            // Skip if it's a role (not a permission)
            if (in_array($cap_slug, $role_slugs_to_skip)) {
                $skipped_caps[] = $cap_slug . ' (role)';
                continue;
            }

            // Skip generic WordPress capabilities
            if ($cap_slug === 'read' || $cap_slug === 'level_0') {
                $skipped_caps[] = $cap_slug . ' (generic WP cap)';
                continue;
            }

            // This is a permission capability
            // Use label from permission_labels if available, otherwise use the slug
            $display_name = isset($permission_labels[$cap_slug])
                ? $permission_labels[$cap_slug]
                : ucwords(str_replace('_', ' ', $cap_slug));

            $permission_names[] = $display_name;
        }

        // DEBUG: Log results
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Permission names extracted (" . count($permission_names) . " permissions):");
            error_log(print_r($permission_names, true));
            error_log("Skipped capabilities (roles/non-permissions, " . count($skipped_caps) . " items):");
            error_log(print_r($skipped_caps, true));
            error_log("=== AdminBarModel::getPermissionNamesFromUserId END ===");
        }

        return $permission_names;
    }
}
