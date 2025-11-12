<?php
/**
 * Abstract Permissions Validator
 *
 * @package     WP_App_Core
 * @subpackage  Validators/Abstract
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Validators/Abstract/AbstractPermissionsValidator.php
 *
 * Description: Base validator untuk permission management across all plugins.
 *              Provides server-side validation for save and reset operations.
 *              Child plugins can extend with custom validation rules.
 *
 * Usage in Child Plugin:
 * ```php
 * class CustomerPermissionValidator extends AbstractPermissionsValidator {
 *     protected function getRoleManagerClass(): string {
 *         return 'WP_Customer_Role_Manager';
 *     }
 *
 *     // Optional: Add custom validation
 *     public function validateSaveRequest(array $data): array {
 *         $result = parent::validateSaveRequest($data);
 *
 *         // Add custom rules
 *         if ($result['valid']) {
 *             if ($data['role'] === 'customer_admin' && $data['capability'] === 'view_customer_list') {
 *                 if (!$data['enabled']) {
 *                     $result['valid'] = false;
 *                     $result['errors'][] = 'Admin must have view access';
 *                 }
 *             }
 *         }
 *
 *         return $result;
 *     }
 * }
 * ```
 *
 * Changelog:
 * 1.0.0 - 2025-01-12 (TODO-1206)
 * - Initial creation
 * - Server-side validation for save requests
 * - Server-side validation for reset requests
 * - Extensible for custom rules
 */

namespace WPAppCore\Validators\Abstract;

abstract class AbstractPermissionsValidator {

    /**
     * Abstract methods - MUST be implemented by child class
     */
    abstract protected function getRoleManagerClass(): string;

    /**
     * Get capability required to manage permissions
     * Child class returns appropriate capability from their model
     * Example: 'manage_roles_permissions' for platform
     *
     * @return string Capability slug
     */
    abstract protected function getManagePermissionCapability(): string;

    /**
     * Get roles that should be protected from modification
     * Typically returns highest operational role (not administrator)
     * Example: ['platform_super_admin'] for wp-app-core
     *          ['customer_admin'] for wp-customer
     *
     * @return array Array of protected role slugs
     */
    abstract protected function getProtectedRoles(): array;

    /**
     * Check if current user can manage permissions
     * Helper method for controllers to check permission before validation
     *
     * @return bool True if user has permission
     */
    public function userCanManagePermissions(): bool {
        $required_capability = $this->getManagePermissionCapability();
        return current_user_can($required_capability);
    }

    /**
     * Validate save permission request
     * Performs standard validation checks
     * Child classes can override to add custom rules
     *
     * @param array $data POST data containing role, capability, enabled
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateSaveRequest(array $data): array {
        $errors = [];

        // Validate role parameter exists
        if (empty($data['role'])) {
            $errors[] = __('Role parameter is required', 'wp-app-core');
        } else {
            // Validate role exists in WordPress
            $role = get_role($data['role']);
            if (!$role) {
                $errors[] = __('Role does not exist', 'wp-app-core');
            }

            // Prevent modifying administrator role (developer level - never modify)
            if ($data['role'] === 'administrator') {
                $errors[] = __('Cannot modify administrator role', 'wp-app-core');
            }

            // Prevent modifying protected roles (highest operational roles)
            $protected_roles = $this->getProtectedRoles();
            if (in_array($data['role'], $protected_roles)) {
                $errors[] = sprintf(
                    __('Cannot modify protected role: %s', 'wp-app-core'),
                    $data['role']
                );
            }

            // Validate role belongs to this plugin
            if (!empty($data['role'])) {
                $role_manager = $this->getRoleManagerClass();
                if (!$role_manager::isPluginRole($data['role'])) {
                    $errors[] = __('Role does not belong to this plugin', 'wp-app-core');
                }
            }
        }

        // Validate capability parameter
        if (empty($data['capability'])) {
            $errors[] = __('Capability parameter is required', 'wp-app-core');
        } else {
            // Validate capability format (alphanumeric and underscores only)
            if (!preg_match('/^[a-z0-9_]+$/', $data['capability'])) {
                $errors[] = __('Invalid capability format', 'wp-app-core');
            }
        }

        // Validate enabled parameter
        if (!isset($data['enabled'])) {
            $errors[] = __('Enabled parameter is required', 'wp-app-core');
        } else {
            // Ensure it's a boolean value (0 or 1)
            if (!in_array($data['enabled'], [0, 1, '0', '1', true, false], true)) {
                $errors[] = __('Enabled parameter must be boolean', 'wp-app-core');
            }
        }

        // Allow child classes to add custom validation via filter
        $role_manager = $this->getRoleManagerClass();
        $plugin_prefix = $this->getPluginPrefixFromRoleManager($role_manager);

        $errors = apply_filters(
            "{$plugin_prefix}_permission_save_validation_errors",
            $errors,
            $data
        );

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate reset permissions request
     * Performs standard validation checks
     * Child classes can override to add custom rules
     *
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateResetRequest(): array {
        $errors = [];

        // Validate user has permission using plugin-specific capability
        $required_capability = $this->getManagePermissionCapability();
        if (!current_user_can($required_capability)) {
            $errors[] = sprintf(
                __('You do not have permission to reset permissions. Required: %s', 'wp-app-core'),
                $required_capability
            );
        }

        // Allow child classes to add custom validation via filter
        $role_manager = $this->getRoleManagerClass();
        $plugin_prefix = $this->getPluginPrefixFromRoleManager($role_manager);

        $errors = apply_filters(
            "{$plugin_prefix}_permission_reset_validation_errors",
            $errors
        );

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate capability against allowed capabilities
     * Optional method for child classes to implement capability whitelist
     *
     * @param string $capability Capability to validate
     * @param array $allowed_capabilities Array of allowed capabilities
     * @return bool True if capability is allowed
     */
    protected function isCapabilityAllowed(string $capability, array $allowed_capabilities): bool {
        return isset($allowed_capabilities[$capability]);
    }

    /**
     * Validate role against plugin roles
     * Helper method to check if role belongs to plugin
     *
     * @param string $role_slug Role slug to validate
     * @return bool True if role belongs to plugin
     */
    protected function isPluginRole(string $role_slug): bool {
        $role_manager = $this->getRoleManagerClass();
        return $role_manager::isPluginRole($role_slug);
    }

    /**
     * Get plugin prefix from RoleManager class name
     * Helper method to extract plugin prefix for filters
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
     * Sanitize role slug
     * Helper method for data sanitization
     *
     * @param string $role_slug Role slug to sanitize
     * @return string Sanitized role slug
     */
    protected function sanitizeRoleSlug(string $role_slug): string {
        return sanitize_key($role_slug);
    }

    /**
     * Sanitize capability slug
     * Helper method for data sanitization
     *
     * @param string $capability Capability to sanitize
     * @return string Sanitized capability
     */
    protected function sanitizeCapability(string $capability): string {
        return sanitize_key($capability);
    }

    /**
     * Log validation error
     * Helper method for debugging validation issues
     *
     * @param string $message Error message
     * @param array $context Additional context data
     */
    protected function logValidationError(string $message, array $context = []): void {
        $role_manager = $this->getRoleManagerClass();
        $plugin_prefix = $this->getPluginPrefixFromRoleManager($role_manager);

        error_log("[{$plugin_prefix}PermissionValidator] {$message}");

        if (!empty($context)) {
            error_log("[{$plugin_prefix}PermissionValidator] Context: " . json_encode($context));
        }
    }
}
