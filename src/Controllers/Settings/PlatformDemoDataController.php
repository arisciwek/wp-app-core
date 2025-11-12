<?php
/**
 * Platform Demo Data Controller
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Settings
 * @version     2.1.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Settings/PlatformDemoDataController.php
 *
 * Description: Controller untuk platform demo data & role management.
 *              REFACTORED: Extracted from monolithic PlatformSettingsController.
 *              Does NOT extend AbstractSettingsController (not settings, but demo data).
 *
 * Changelog:
 * 2.1.0 - 2025-01-12 (TODO-1207)
 * - Updated AJAX handlers to use PlatformDemoData instance methods
 * - Changed from static methods to instance methods pattern
 * - Now creates PlatformDemoData instance and calls run()/deleteAll()/getStatistics()
 * - Added AbstractDemoData require_once for proper class loading
 * 2.0.0 - 2025-01-09 (TODO-1203)
 * - BREAKING: Extracted from PlatformSettingsController
 * - Standalone controller for demo data management
 * - Single responsibility: Role creation, staff generation
 * 1.0.0 - 2025-10-19
 * - Was part of PlatformSettingsController
 */

namespace WPAppCore\Controllers\Settings;

use WPAppCore\Models\Settings\PlatformPermissionModel;

class PlatformDemoDataController {

    private PlatformPermissionModel $permission_model;

    public function __construct() {
        $this->permission_model = new PlatformPermissionModel();
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
        // Role Management
        add_action('wp_ajax_wpapp_create_platform_roles', [$this, 'handleCreatePlatformRoles']);
        add_action('wp_ajax_wpapp_delete_platform_roles', [$this, 'handleDeletePlatformRoles']);
        add_action('wp_ajax_wpapp_reset_platform_capabilities', [$this, 'handleResetPlatformCapabilities']);

        // Platform Staff Demo Data
        add_action('wp_ajax_wpapp_generate_platform_staff', [$this, 'handleGeneratePlatformStaff']);
        add_action('wp_ajax_wpapp_delete_platform_staff', [$this, 'handleDeletePlatformStaff']);
        add_action('wp_ajax_wpapp_platform_staff_stats', [$this, 'handlePlatformStaffStats']);
    }

    /**
     * Handle create platform roles
     */
    public function handleCreatePlatformRoles(): void {
        // Verify nonce with the same one used in the button
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'create_platform_roles')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-app-core')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        try {
            require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-role-manager.php';

            // Create roles
            \WP_App_Core_Role_Manager::createRoles();

            // Add capabilities
            $this->permission_model->addCapabilities();

            wp_send_json_success([
                'message' => __('Platform roles created successfully', 'wp-app-core')
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error creating roles: %s', 'wp-app-core'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle delete platform roles
     */
    public function handleDeletePlatformRoles(): void {
        // Verify nonce with the same one used in the button
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delete_platform_roles')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-app-core')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        try {
            require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-role-manager.php';

            $plugin_roles = \WP_App_Core_Role_Manager::getRoleSlugs();
            $deleted_count = 0;

            foreach ($plugin_roles as $role_slug) {
                if (\WP_App_Core_Role_Manager::roleExists($role_slug)) {
                    remove_role($role_slug);
                    $deleted_count++;
                }
            }

            wp_send_json_success([
                'message' => sprintf(
                    __('Successfully deleted %d platform roles', 'wp-app-core'),
                    $deleted_count
                )
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error deleting roles: %s', 'wp-app-core'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle reset platform capabilities
     */
    public function handleResetPlatformCapabilities(): void {
        // Verify nonce with the same one used in the button
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'reset_platform_capabilities')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-app-core')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        try {
            if ($this->permission_model->resetToDefault()) {
                wp_send_json_success([
                    'message' => __('Platform capabilities reset to default values successfully', 'wp-app-core')
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Failed to reset capabilities', 'wp-app-core')
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error resetting capabilities: %s', 'wp-app-core'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle generate platform staff
     */
    public function handleGeneratePlatformStaff(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'generate_platform_staff')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-app-core')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        try {
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/Data/PlatformUsersData.php';
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/AbstractDemoData.php';
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/PlatformDemoData.php';
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/WPUserGenerator.php';

            // Create instance and run generation
            $generator = new \WPAppCore\Database\Demo\PlatformDemoData();
            $success = $generator->run();
            $result = $generator->getLastResults();

            if ($success) {
                wp_send_json_success([
                    'message' => $result['message'],
                    'users_created' => $result['users_created'],
                    'staff_records_created' => $result['staff_records_created'],
                    'errors' => $result['errors']
                ]);
            } else {
                wp_send_json_error([
                    'message' => $result['message'],
                    'errors' => $result['errors']
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error generating platform staff: %s', 'wp-app-core'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle delete platform staff
     */
    public function handleDeletePlatformStaff(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delete_platform_staff')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-app-core')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        try {
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/Data/PlatformUsersData.php';
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/AbstractDemoData.php';
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/PlatformDemoData.php';
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/WPUserGenerator.php';

            // Create instance and delete all
            $generator = new \WPAppCore\Database\Demo\PlatformDemoData();
            $result = $generator->deleteAll();

            if ($result['success']) {
                wp_send_json_success([
                    'message' => $result['message'],
                    'users_deleted' => $result['users_deleted'],
                    'staff_records_deleted' => $result['staff_records_deleted'],
                    'errors' => $result['errors']
                ]);
            } else {
                wp_send_json_error([
                    'message' => $result['message'],
                    'errors' => $result['errors']
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error deleting platform staff: %s', 'wp-app-core'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle platform staff statistics
     */
    public function handlePlatformStaffStats(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'platform_staff_stats')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-app-core')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        try {
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/Data/PlatformUsersData.php';
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/AbstractDemoData.php';
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/PlatformDemoData.php';
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/WPUserGenerator.php';

            // Create instance and get statistics
            $generator = new \WPAppCore\Database\Demo\PlatformDemoData();
            $stats = $generator->getStatistics();

            wp_send_json_success([
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error getting statistics: %s', 'wp-app-core'), $e->getMessage())
            ]);
        }
    }
}
