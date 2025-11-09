<?php
/**
 * Platform Permissions Controller
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Settings
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Settings/PlatformPermissionsController.php
 *
 * Description: Controller untuk platform permission management.
 *              REFACTORED: Extracted from monolithic PlatformSettingsController.
 *              Does NOT extend AbstractSettingsController (not settings, but permissions).
 *
 * Changelog:
 * 2.0.0 - 2025-01-09 (TODO-1203)
 * - BREAKING: Extracted from PlatformSettingsController
 * - Standalone controller for permission management
 * - Single responsibility: Permission matrix only
 * 1.0.0 - 2025-10-19
 * - Was part of PlatformSettingsController
 */

namespace WPAppCore\Controllers\Settings;

use WPAppCore\Models\Settings\PlatformPermissionModel;

class PlatformPermissionsController {

    private PlatformPermissionModel $model;

    public function __construct() {
        $this->model = new PlatformPermissionModel();
    }

    /**
     * Initialize controller
     */
    public function init(): void {
        $this->registerAjaxHandlers();
    }

    /**
     * Register AJAX handlers
     */
    private function registerAjaxHandlers(): void {
        add_action('wp_ajax_wpapp_save_platform_permissions', [$this, 'handleSavePlatformPermissions']);
        add_action('wp_ajax_wpapp_reset_platform_permissions', [$this, 'handleResetPlatformPermissions']);
    }

    /**
     * Get capability groups for view
     */
    public function getCapabilityGroups(): array {
        return $this->model->getCapabilityGroups();
    }

    /**
     * Get role capabilities matrix for view
     */
    public function getRoleCapabilitiesMatrix(): array {
        return $this->model->getRoleCapabilitiesMatrix();
    }

    /**
     * Get capability descriptions for view
     */
    public function getCapabilityDescriptions(): array {
        return $this->model->getCapabilityDescriptions();
    }

    /**
     * Get all capabilities for view
     */
    public function getAllCapabilities(): array {
        return $this->model->getAllCapabilities();
    }

    /**
     * Handle save platform permissions
     */
    public function handleSavePlatformPermissions(): void {
        check_ajax_referer('wpapp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        $role = sanitize_key($_POST['role'] ?? '');
        $capabilities = $_POST['capabilities'] ?? [];

        if (empty($role)) {
            wp_send_json_error(['message' => __('Invalid role', 'wp-app-core')]);
        }

        if ($this->model->updateRoleCapabilities($role, $capabilities)) {
            wp_send_json_success(['message' => __('Permissions updated successfully', 'wp-app-core')]);
        } else {
            wp_send_json_error(['message' => __('Failed to update permissions', 'wp-app-core')]);
        }
    }

    /**
     * Handle reset platform permissions
     */
    public function handleResetPlatformPermissions(): void {
        // CRITICAL: Register shutdown function to catch fatal errors
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                error_log('=== FATAL ERROR DETECTED IN RESET PERMISSIONS ===');
                error_log('Type: ' . $error['type']);
                error_log('Message: ' . $error['message']);
                error_log('File: ' . $error['file']);
                error_log('Line: ' . $error['line']);
                error_log('=== END FATAL ERROR ===');
            }
        });

        // CRITICAL: Start output buffering to prevent contamination from plugin hooks
        ob_start();

        error_log('=== WP-APP-CORE RESET PERMISSIONS START ===');

        try {
            check_ajax_referer('wpapp_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                error_log('ERROR: User does not have manage_options capability');
                ob_end_clean();
                wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
                die();
            }

            error_log('Calling resetToDefault()...');
            $result = $this->model->resetToDefault();
            error_log('resetToDefault() returned: ' . ($result ? 'TRUE' : 'FALSE'));

            // CRITICAL: Clean output buffer before sending JSON response
            ob_end_clean();

            if ($result) {
                error_log('SUCCESS: Sending success response');
                wp_send_json_success(['message' => __('Permissions reset to default', 'wp-app-core')]);
                die();
            } else {
                error_log('ERROR: resetToDefault() returned false');
                wp_send_json_error(['message' => __('Failed to reset permissions', 'wp-app-core')]);
                die();
            }
        } catch (\Exception $e) {
            error_log('EXCEPTION: ' . $e->getMessage());
            ob_end_clean();
            wp_send_json_error(['message' => $e->getMessage()]);
            die();
        }
    }
}
