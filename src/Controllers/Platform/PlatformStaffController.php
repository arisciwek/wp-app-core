<?php
/**
 * Platform Staff Controller
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Platform
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Platform/PlatformStaffController.php
 *
 * Description: CRUD controller untuk Platform Staff management.
 *              Extends AbstractCrudController untuk inherit standard CRUD operations.
 *              Handles staff creation with WordPress user integration.
 *              Includes permission validation dan error handling.
 *
 * Changelog:
 * 2.0.0 - 2025-11-04 (TODO-1198: Abstract Controller Implementation)
 * - BREAKING: Refactored to extend AbstractCrudController
 * - RENAMED: createStaff() → store()
 * - RENAMED: updateStaff() → update()
 * - RENAMED: deleteStaff() → delete()
 * - ADDED: show() method for single entity retrieval
 * - REMOVED: renderDashboard() (moved to PlatformStaffDashboardController)
 * - REMOVED: renderDataTableTest() (testing only, not needed in production)
 * - REMOVED: handleDataTableRequest() (handled by DashboardController)
 * - REMOVED: getStatistics() (handled by DashboardController)
 * - REMOVED: getStaffDetails() (replaced by show() + DashboardController)
 * - KEPT: DataTableController::register_ajax_action for test endpoint
 * - KEPT: AJAX action registration for CRUD (create/update/delete)
 * - Code reduction: 446 lines → ~300 lines (33% reduction)
 * - Implements 9 abstract methods from base class
 * - All CRUD operations inherited FREE from AbstractCrudController
 *
 * 1.0.9 - 2025-11-01 (TODO-1191: Separation of Concerns)
 * - REMOVED: Menu registration (moved to MenuManager)
 * - REMOVED: Asset enqueue (moved to class-dependencies.php)
 * - Controller now focuses only on business logic
 *
 * 1.0.8 - 2025-11-01 (TODO-1190: Static ID Hook Pattern)
 * - Added wp_app_core_staff_user_before_insert filter hook
 * - Handle static WordPress user ID injection in createStaff()
 *
 * 1.0.0 - 2025-10-19
 * - Initial implementation
 */

namespace WPAppCore\Controllers\Platform;

use WPAppCore\Controllers\Abstract\AbstractCrudController;
use WPAppCore\Models\Platform\PlatformStaffModel;
use WPAppCore\Validators\Platform\PlatformStaffValidator;

defined('ABSPATH') || exit;

class PlatformStaffController extends AbstractCrudController {

    /**
     * @var PlatformStaffModel
     */
    private $model;

    /**
     * @var PlatformStaffValidator
     */
    private $validator;

    /**
     * Constructor
     */
    public function __construct() {
        $this->model = new PlatformStaffModel();
        $this->validator = new PlatformStaffValidator();

        // Register AJAX endpoints for CRUD operations
        add_action('wp_ajax_create_platform_staff', [$this, 'store']);
        add_action('wp_ajax_update_platform_staff', [$this, 'update']);
        add_action('wp_ajax_delete_platform_staff', [$this, 'delete']);

        // Note: get_platform_staff_details handled by DashboardController
        // Note: get_platform_staff_stats handled by DashboardController
        // Note: get_platform_staff_datatable handled by DashboardController

        // Register NEW DataTable system test endpoint (from old file)
        \WPAppCore\Controllers\DataTable\DataTableController::register_ajax_action(
            'platform_staff_datatable_test',
            'WPAppCore\\Models\\Platform\\PlatformStaffDataTableModel'
        );
    }

    // ========================================
    // IMPLEMENT ABSTRACT METHODS (9 required)
    // ========================================

    /**
     * Get entity name (singular)
     *
     * @return string
     */
    protected function getEntityName(): string {
        return 'platform_staff';
    }

    /**
     * Get entity name (plural)
     *
     * @return string
     */
    protected function getEntityNamePlural(): string {
        return 'platform_staff';
    }

    /**
     * Get nonce action
     *
     * @return string
     */
    protected function getNonceAction(): string {
        return 'wp_app_core_platform_staff_nonce';
    }

    /**
     * Get text domain
     *
     * @return string
     */
    protected function getTextDomain(): string {
        return 'wp-app-core';
    }

    /**
     * Get validator instance
     *
     * @return PlatformStaffValidator
     */
    protected function getValidator() {
        return $this->validator;
    }

    /**
     * Get model instance
     *
     * @return PlatformStaffModel
     */
    protected function getModel() {
        return $this->model;
    }

    /**
     * Get cache group
     *
     * @return string
     */
    protected function getCacheGroup(): string {
        return 'wp-app-core';
    }

    /**
     * Prepare data for create operation
     *
     * Creates WordPress user first, then prepares staff data.
     *
     * @return array Sanitized data ready for model->create()
     * @throws \Exception If user creation fails
     */
    protected function prepareCreateData(): array {
        // Get and validate WordPress user data
        $user_email = isset($_POST['user_email']) ? sanitize_email($_POST['user_email']) : '';
        $user_login = isset($_POST['user_login']) ? sanitize_user($_POST['user_login']) : '';
        $full_name = isset($_POST['full_name']) ? sanitize_text_field($_POST['full_name']) : '';

        if (empty($user_email)) {
            throw new \Exception(__('Email harus diisi', 'wp-app-core'));
        }
        if (empty($user_login)) {
            throw new \Exception(__('Username harus diisi', 'wp-app-core'));
        }
        if (empty($full_name)) {
            throw new \Exception(__('Nama lengkap harus diisi', 'wp-app-core'));
        }

        // Check if email already exists
        if (email_exists($user_email)) {
            throw new \Exception(__('Email sudah terdaftar', 'wp-app-core'));
        }

        // Check if username already exists
        if (username_exists($user_login)) {
            throw new \Exception(__('Username sudah terdaftar', 'wp-app-core'));
        }

        // Create WordPress user
        $user_data = [
            'user_login' => $user_login,
            'user_email' => $user_email,
            'user_pass' => wp_generate_password(12, true, true),
            'display_name' => $full_name
        ];

        // HOOK: Apply filter before user creation (allows static ID injection)
        $user_data = apply_filters(
            'wp_app_core_staff_user_before_insert',
            $user_data,
            $_POST,
            'platform_staff'
        );

        // Handle static ID if requested
        $static_user_id = null;
        if (isset($user_data['ID'])) {
            $static_user_id = $user_data['ID'];
            unset($user_data['ID']);
        }

        $user_id = wp_create_user(
            $user_data['user_login'],
            $user_data['user_pass'],
            $user_data['user_email']
        );

        if (is_wp_error($user_id)) {
            throw new \Exception($user_id->get_error_message());
        }

        // Update to static ID if requested (demo data support)
        if ($static_user_id !== null && $static_user_id != $user_id) {
            global $wpdb;
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->users} WHERE ID = %d",
                $static_user_id
            ));

            if (!$existing) {
                $wpdb->query('SET FOREIGN_KEY_CHECKS=0');
                $wpdb->update($wpdb->users, ['ID' => $static_user_id], ['ID' => $user_id], ['%d'], ['%d']);
                $wpdb->update($wpdb->usermeta, ['user_id' => $static_user_id], ['user_id' => $user_id], ['%d'], ['%d']);
                $wpdb->query('SET FOREIGN_KEY_CHECKS=1');
                $user_id = $static_user_id;

                clean_user_cache($user_id);
            }
        }

        // Update user display name
        wp_update_user([
            'ID' => $user_id,
            'display_name' => $full_name
        ]);

        // Prepare staff data
        return [
            'user_id' => $user_id,
            'full_name' => $full_name,
            'department' => isset($_POST['department']) ? sanitize_text_field($_POST['department']) : '',
            'hire_date' => isset($_POST['hire_date']) ? sanitize_text_field($_POST['hire_date']) : '',
            'phone' => isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '',
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'aktif',
        ];
    }

    /**
     * Prepare data for update operation
     *
     * @param int $id Staff ID
     * @return array Sanitized data ready for model->update()
     */
    protected function prepareUpdateData(int $id): array {
        $data = [];

        if (isset($_POST['full_name'])) {
            $data['full_name'] = sanitize_text_field($_POST['full_name']);
        }
        if (isset($_POST['department'])) {
            $data['department'] = sanitize_text_field($_POST['department']);
        }
        if (isset($_POST['hire_date'])) {
            $data['hire_date'] = sanitize_text_field($_POST['hire_date']);
        }
        if (isset($_POST['phone'])) {
            $data['phone'] = sanitize_text_field($_POST['phone']);
        }

        return $data;
    }

    // ========================================
    // CUSTOM VALIDATION OVERRIDE
    // ========================================

    /**
     * Override validate method to handle staff-specific validation
     *
     * @param array $data Data to validate
     * @param int|null $id Entity ID (for update)
     * @return void
     * @throws \Exception If validation fails
     */
    protected function validate(array $data, ?int $id = null): void {
        if ($id === null) {
            // Create validation
            $validation = $this->validator->validateStaffCreation($data);
        } else {
            // Update validation
            $validation = $this->validator->validateStaffUpdate($id, $data);
        }

        if (is_wp_error($validation)) {
            throw new \Exception($validation->get_error_message());
        }
    }

    /**
     * Override store method to handle WordPress user creation rollback
     *
     * @return void
     * @throws \Exception On validation or creation failure
     */
    public function store(): void {
        $user_id = null;

        try {
            // Verify nonce
            $this->verifyNonce();

            // Check permission
            $this->checkPermission('create');

            // Prepare data (creates WordPress user)
            $data = $this->prepareCreateData();
            $user_id = $data['user_id'];

            // Validate
            $this->validate($data);

            // Create via model
            $result = $this->getModel()->create($data);

            // Send success response
            $this->sendSuccess(
                __('Data staff berhasil dibuat', $this->getTextDomain()),
                $result
            );

        } catch (\Exception $e) {
            // Rollback: Delete WordPress user if staff creation fails
            if ($user_id) {
                wp_delete_user($user_id);
            }

            $this->handleError($e, 'create');
        }
    }
}
