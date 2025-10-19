<?php
/**
 * Platform Staff Controller
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Platform
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Platform/PlatformStaffController.php
 *
 * Description: Controller untuk mengelola operasi terkait platform staff:
 *              - View staff list dan details
 *              - Create, update, delete staff
 *              - Staff management
 *              - Statistics dan reporting
 *              Includes permission validation dan error handling.
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial implementation
 * - CRUD operations
 * - DataTable support
 * - Statistics
 * - Permission validation
 */

namespace WPAppCore\Controllers\Platform;

use WPAppCore\Models\Platform\PlatformStaffModel;
use WPAppCore\Cache\PlatformCacheManager;
use WPAppCore\Validators\Platform\PlatformStaffValidator;

defined('ABSPATH') || exit;

class PlatformStaffController {
    private $staff_model;
    private $cache;
    private $validator;

    public function __construct() {
        $this->staff_model = new PlatformStaffModel();
        $this->cache = new PlatformCacheManager();
        $this->validator = new PlatformStaffValidator();

        // Register AJAX endpoints
        add_action('wp_ajax_handle_platform_staff_datatable', [$this, 'handleDataTableRequest']);
        add_action('wp_ajax_get_platform_staff_stats', [$this, 'getStatistics']);
        add_action('wp_ajax_get_platform_staff_details', [$this, 'getStaffDetails']);
        add_action('wp_ajax_create_platform_staff', [$this, 'createStaff']);
        add_action('wp_ajax_update_platform_staff', [$this, 'updateStaff']);
        add_action('wp_ajax_delete_platform_staff', [$this, 'deleteStaff']);

        // Register menu page
        add_action('admin_menu', [$this, 'registerMenu'], 20);

        // Register assets
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Register admin menu
     */
    public function registerMenu() {
        add_menu_page(
            __('Platform Staff', 'wp-app-core'),
            __('Platform Staff', 'wp-app-core'),
            'manage_options', // Administrator can access
            'wp-app-core-platform-staff',
            [$this, 'renderDashboard'],
            'dashicons-groups',
            25
        );
    }

    /**
     * Enqueue assets
     */
    public function enqueueAssets($hook) {
        if ($hook !== 'toplevel_page_wp-app-core-platform-staff') {
            return;
        }

        // DataTables library from CDN
        wp_enqueue_style(
            'datatables',
            'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css',
            [],
            '1.13.7'
        );

        wp_enqueue_script(
            'datatables',
            'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
            ['jquery'],
            '1.13.7',
            true
        );

        // CSS
        wp_enqueue_style(
            'wp-app-core-platform-staff',
            WP_APP_CORE_PLUGIN_URL . 'assets/css/platform/platform-staff-style.css',
            ['datatables'],
            WP_APP_CORE_VERSION
        );

        wp_enqueue_style(
            'wp-app-core-platform-staff-datatable',
            WP_APP_CORE_PLUGIN_URL . 'assets/css/platform/platform-staff-datatable-style.css',
            ['wp-app-core-platform-staff'],
            WP_APP_CORE_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'wp-app-core-platform-staff',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/platform/platform-staff-script.js',
            ['jquery', 'datatables'],
            WP_APP_CORE_VERSION,
            true
        );

        wp_enqueue_script(
            'wp-app-core-platform-staff-datatable',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/platform/platform-staff-datatable-script.js',
            ['jquery', 'datatables', 'wp-app-core-platform-staff'],
            WP_APP_CORE_VERSION,
            true
        );

        // Localize script
        wp_localize_script(
            'wp-app-core-platform-staff',
            'wpAppCoreStaffData',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_app_core_platform_staff_nonce'),
                'i18n' => [
                    'confirmDelete' => __('Apakah Anda yakin ingin menghapus staff ini?', 'wp-app-core'),
                    'deleteSuccess' => __('Staff berhasil dihapus', 'wp-app-core'),
                    'deleteError' => __('Gagal menghapus staff', 'wp-app-core'),
                    'saveSuccess' => __('Data staff berhasil disimpan', 'wp-app-core'),
                    'saveError' => __('Gagal menyimpan data staff', 'wp-app-core'),
                    'loadError' => __('Gagal memuat data', 'wp-app-core'),
                ]
            ]
        );
    }

    /**
     * Render dashboard page
     */
    public function renderDashboard() {
        // Check permission
        if (!$this->validator->canViewStaff()) {
            require_once WP_APP_CORE_PATH . 'src/Views/templates/platform-staff/platform-staff-no-access.php';
            return;
        }

        require_once WP_APP_CORE_PATH . 'src/Views/templates/platform-staff/platform-staff-dashboard.php';
    }

    /**
     * Handle DataTable AJAX request
     */
    public function handleDataTableRequest() {
        try {
            // Verify nonce
            check_ajax_referer('wp_app_core_platform_staff_nonce', 'nonce');

            // Check permission
            if (!$this->validator->canViewStaff()) {
                throw new \Exception(__('Anda tidak memiliki izin untuk melihat data staff', 'wp-app-core'));
            }

            // Get DataTable parameters
            $params = $_POST;

            // Get data from model
            $response = $this->staff_model->getDataTableData($params);

            wp_send_json($response);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get statistics
     */
    public function getStatistics() {
        try {
            // Verify nonce
            check_ajax_referer('wp_app_core_platform_staff_nonce', 'nonce');

            // Check permission
            if (!$this->validator->canViewStaffStats()) {
                throw new \Exception(__('Anda tidak memiliki izin untuk melihat statistik', 'wp-app-core'));
            }

            $stats = $this->staff_model->getStatistics();

            wp_send_json_success($stats);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get staff details
     */
    public function getStaffDetails() {
        try {
            // Verify nonce
            check_ajax_referer('wp_app_core_platform_staff_nonce', 'nonce');

            // Get staff ID
            $staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
            if (!$staff_id) {
                throw new \Exception(__('ID Staff tidak valid', 'wp-app-core'));
            }

            // Check permission
            if (!$this->validator->canViewStaff()) {
                throw new \Exception(__('Anda tidak memiliki izin untuk melihat data staff', 'wp-app-core'));
            }

            // Get staff data
            $staff = $this->staff_model->find($staff_id);
            if (!$staff) {
                throw new \Exception(__('Staff tidak ditemukan', 'wp-app-core'));
            }

            // Get user data
            $user = get_userdata($staff->user_id);

            // Format response
            $response = [
                'id' => $staff->id,
                'employee_id' => $staff->employee_id,
                'full_name' => $staff->full_name,
                'department' => $staff->department ?: '-',
                'hire_date' => $staff->hire_date ? date('d/m/Y', strtotime($staff->hire_date)) : '-',
                'hire_date_raw' => $staff->hire_date,
                'phone' => $staff->phone ?: '-',
                'status' => $staff->status,
                'status_label' => $staff->status === 'aktif' ? 'Aktif' : 'Tidak Aktif',
                'user_id' => $staff->user_id,
                'user_email' => $user ? $user->user_email : '-',
                'user_login' => $user ? $user->user_login : '-',
                'created_at' => date('d/m/Y H:i', strtotime($staff->created_at)),
                'updated_at' => date('d/m/Y H:i', strtotime($staff->updated_at)),
                'can_edit' => $this->validator->canEditStaff($staff_id),
                'can_delete' => $this->validator->canDeleteStaff($staff_id),
            ];

            wp_send_json_success($response);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create new staff
     */
    public function createStaff() {
        try {
            // Verify nonce
            check_ajax_referer('wp_app_core_platform_staff_nonce', 'nonce');

            // Check permission
            if (!$this->validator->canCreateStaff()) {
                throw new \Exception(__('Anda tidak memiliki izin untuk membuat staff', 'wp-app-core'));
            }

            // Get and sanitize data
            $user_email = isset($_POST['user_email']) ? sanitize_email($_POST['user_email']) : '';
            $user_login = isset($_POST['user_login']) ? sanitize_user($_POST['user_login']) : '';
            $full_name = isset($_POST['full_name']) ? sanitize_text_field($_POST['full_name']) : '';

            $data = [
                'full_name' => $full_name,
                'department' => isset($_POST['department']) ? sanitize_text_field($_POST['department']) : '',
                'hire_date' => isset($_POST['hire_date']) ? sanitize_text_field($_POST['hire_date']) : '',
                'phone' => isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '',
                'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'aktif',
            ];

            // Validate WordPress user data
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

            // Step 1: Create WordPress User
            $user_id = wp_create_user(
                $user_login,
                wp_generate_password(12, true, true), // Auto-generate secure password
                $user_email
            );

            if (is_wp_error($user_id)) {
                throw new \Exception($user_id->get_error_message());
            }

            // Update user display name
            wp_update_user([
                'ID' => $user_id,
                'display_name' => $full_name
            ]);

            // Step 2: Create Staff Profile with the new user_id
            $data['user_id'] = $user_id;

            // Validate staff data
            $validation = $this->validator->validateStaffCreation($data);
            if (is_wp_error($validation)) {
                // Rollback: Delete WordPress user if staff creation fails validation
                wp_delete_user($user_id);
                throw new \Exception($validation->get_error_message());
            }

            // Create staff profile
            $staff_id = $this->staff_model->create($data);
            if (!$staff_id) {
                // Rollback: Delete WordPress user if staff creation fails
                wp_delete_user($user_id);
                throw new \Exception(__('Gagal membuat data staff', 'wp-app-core'));
            }

            // Get created staff
            $staff = $this->staff_model->find($staff_id);

            wp_send_json_success([
                'message' => __('Data staff berhasil dibuat', 'wp-app-core'),
                'staff' => $staff
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update staff
     */
    public function updateStaff() {
        try {
            // Verify nonce
            check_ajax_referer('wp_app_core_platform_staff_nonce', 'nonce');

            // Get staff ID
            $staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
            if (!$staff_id) {
                throw new \Exception(__('ID Staff tidak valid', 'wp-app-core'));
            }

            // Check permission
            if (!$this->validator->canEditStaff($staff_id)) {
                throw new \Exception(__('Anda tidak memiliki izin untuk mengubah staff ini', 'wp-app-core'));
            }

            // Get and sanitize data
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

            // Validate data
            $validation = $this->validator->validateStaffUpdate($staff_id, $data);
            if (is_wp_error($validation)) {
                throw new \Exception($validation->get_error_message());
            }

            // Update staff
            $result = $this->staff_model->update($staff_id, $data);
            if (!$result) {
                throw new \Exception(__('Gagal mengubah data staff', 'wp-app-core'));
            }

            // Get updated staff
            $staff = $this->staff_model->find($staff_id);

            wp_send_json_success([
                'message' => __('Data staff berhasil diubah', 'wp-app-core'),
                'staff' => $staff
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Delete staff
     */
    public function deleteStaff() {
        try {
            // Verify nonce
            check_ajax_referer('wp_app_core_platform_staff_nonce', 'nonce');

            // Get staff ID
            $staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
            if (!$staff_id) {
                throw new \Exception(__('ID Staff tidak valid', 'wp-app-core'));
            }

            // Check permission
            if (!$this->validator->canDeleteStaff($staff_id)) {
                throw new \Exception(__('Anda tidak memiliki izin untuk menghapus staff ini', 'wp-app-core'));
            }

            // Delete staff
            $result = $this->staff_model->delete($staff_id);
            if (!$result) {
                throw new \Exception(__('Gagal menghapus staff', 'wp-app-core'));
            }

            wp_send_json_success([
                'message' => __('Staff berhasil dihapus', 'wp-app-core')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}
