<?php
/**
 * Platform Permission Validator
 *
 * @package     WP_App_Core
 * @subpackage  Validators/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Validators/Settings/PlatformPermissionValidator.php
 *
 * Description: Validator untuk platform permission management.
 *              Extends AbstractPermissionsValidator.
 *              Defines platform-specific validation rules.
 *
 * Changelog:
 * 1.0.0 - 2025-01-12 (TODO-1206)
 * - Initial creation
 * - Implements abstract methods from AbstractPermissionsValidator
 * - Platform-specific capability and protected roles
 */

namespace WPAppCore\Validators\Settings;

use WPAppCore\Validators\Abstract\AbstractPermissionsValidator;

class PlatformPermissionValidator extends AbstractPermissionsValidator {

    /**
     * Get role manager class
     */
    protected function getRoleManagerClass(): string {
        return 'WP_App_Core_Role_Manager';
    }

    /**
     * Get capability required to manage permissions
     * Uses platform-specific capability instead of generic manage_options
     */
    protected function getManagePermissionCapability(): string {
        return 'manage_roles_permissions';
    }

    /**
     * Get roles that should be protected from modification
     * Platform Super Admin is the highest operational role
     * (Administrator is always protected by abstract)
     */
    protected function getProtectedRoles(): array {
        return [
            'platform_super_admin'
        ];
    }

    /**
     * Optional: Add custom validation rules
     * This is an example of extending validation logic
     */
    public function validateSaveRequest(array $data): array {
        // Get base validation from parent
        $result = parent::validateSaveRequest($data);

        // Add platform-specific validation rules
        if ($result['valid']) {
            // Example: Prevent removing critical capabilities from certain roles
            if ($data['role'] === 'platform_admin') {
                $critical_capabilities = [
                    'view_platform_dashboard',
                    'access_platform_settings'
                ];

                if (in_array($data['capability'], $critical_capabilities) && !$data['enabled']) {
                    $result['valid'] = false;
                    $result['errors'][] = sprintf(
                        __('Cannot remove critical capability "%s" from Platform Admin role', 'wp-app-core'),
                        $data['capability']
                    );
                }
            }
        }

        return $result;
    }
}
