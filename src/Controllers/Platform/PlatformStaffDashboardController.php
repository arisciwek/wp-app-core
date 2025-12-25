<?php
/**
 * Platform Staff Dashboard Controller
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Platform
 * @version     3.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Platform/PlatformStaffDashboardController.php
 *
 * Description: Dashboard controller untuk Platform Staff management.
 *              Refactored dari AbstractDashboardController ke wp-datatable framework.
 *              Uses hook-based architecture untuk extensibility.
 *
 * Changelog:
 * 3.0.0 - 2025-12-25 (TODO-2205: wp-datatable Integration)
 * - BREAKING: Removed dependency on AbstractDashboardController
 * - Migrated to wp-datatable DualPanel layout system
 * - Hook changes: wpapp_* → wpdt_*
 * - Nonce changes: wpapp_panel_nonce → wpdt_nonce
 * - Simplified structure: No abstract methods, pure hook-based
 * - Following CustomerDashboardController pattern
 *
 * 2.0.0 - 2025-11-04
 * - Extended AbstractDashboardController
 *
 * 1.0.0 - 2025-11-01
 * - Initial implementation
 */

namespace WPAppCore\Controllers\Platform;

use WPDataTable\Templates\DualPanel\DashboardTemplate;
use WPAppCore\Models\Platform\PlatformStaffDataTableModel;
use WPAppCore\Models\Platform\PlatformStaffModel;

defined('ABSPATH') || exit;

class PlatformStaffDashboardController {

    /**
     * @var PlatformStaffModel
     */
    private $model;

    /**
     * @var PlatformStaffDataTableModel
     */
    private $datatable_model;

    /**
     * Constructor
     */
    public function __construct() {
        $this->model = new PlatformStaffModel();
        $this->datatable_model = new PlatformStaffDataTableModel();

        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        // Signal wp-datatable to load dual panel assets
        add_filter('wpdt_use_dual_panel', [$this, 'signal_dual_panel'], 10, 1);

        // Register tabs
        add_filter('wpdt_datatable_tabs', [$this, 'register_tabs'], 10, 2);

        // Register content hooks
        add_action('wpdt_left_panel_content', [$this, 'render_datatable'], 10, 1);
        add_action('wpdt_statistics_content', [$this, 'render_statistics'], 10, 1);

        // AJAX handlers - Dashboard
        add_action('wp_ajax_get_platform_staff_datatable', [$this, 'handle_datatable']);
        add_action('wp_ajax_get_platform_staff_details', [$this, 'handle_get_details']);
        add_action('wp_ajax_get_platform_staff_stats', [$this, 'handle_get_stats']);

        // AJAX handlers - Modal CRUD
        add_action('wp_ajax_get_platform_staff_form', [$this, 'handle_get_staff_form']);
        add_action('wp_ajax_save_platform_staff', [$this, 'handle_save_staff']);
        add_action('wp_ajax_delete_platform_staff', [$this, 'handle_delete_staff']);

        // Backward compatibility
        add_action('wp_ajax_handle_platform_staff_datatable', [$this, 'handle_datatable']);
    }

    /**
     * Render dashboard page
     * Called from MenuManager
     */
    public function render(): void {
        // Check permission (aligned with menu capability in MenuManager)
        if (!current_user_can('view_platform_users')) {
            wp_die(__('You do not have permission to access this page.', 'wp-app-core'));
        }

        // Render wp-datatable dual panel dashboard
        DashboardTemplate::render([
            'entity' => 'platform_staff',
            'title' => __('Platform Staff', 'wp-app-core'),
            'description' => __('Manage platform staff members', 'wp-app-core'),
            'has_stats' => true,
            'has_tabs' => true,
            'has_filters' => false,
            'ajax_action' => 'get_platform_staff_details',
        ]);
    }

    // ========================================
    // DUAL PANEL SIGNAL
    // ========================================

    /**
     * Signal wp-datatable to use dual panel layout
     */
    public function signal_dual_panel($use): bool {
        error_log('[PlatformStaffDashboard] signal_dual_panel called, page=' . ($_GET['page'] ?? 'none'));
        if (isset($_GET['page']) && $_GET['page'] === 'wp-app-core-platform-staff') {
            error_log('[PlatformStaffDashboard] Returning true for dual panel');
            return true;
        }
        error_log('[PlatformStaffDashboard] Returning false for dual panel');
        return $use;
    }

    // ========================================
    // TAB REGISTRATION
    // ========================================

    /**
     * Register tabs for platform staff dashboard
     */
    public function register_tabs($tabs, $entity): array {
        if ($entity !== 'platform_staff') {
            return $tabs;
        }

        return [
            'info' => [
                'title' => __('Staff Information', 'wp-app-core'),
                'template' => WP_APP_CORE_PATH . 'src/Views/platform/tabs/platform-staff-info.php',
                'priority' => 10
            ]
        ];
    }

    // ========================================
    // CONTENT RENDERING
    // ========================================

    /**
     * Render DataTable in left panel
     */
    public function render_datatable($config): void {
        error_log('[PlatformStaffDashboard] render_datatable called with entity: ' . ($config['entity'] ?? 'none'));

        if ($config['entity'] !== 'platform_staff') {
            error_log('[PlatformStaffDashboard] Entity mismatch, skipping render');
            return;
        }

        $view_file = WP_APP_CORE_PATH . 'src/Views/platform/datatable/platform-staff-datatable.php';
        error_log('[PlatformStaffDashboard] View file path: ' . $view_file);
        error_log('[PlatformStaffDashboard] File exists: ' . (file_exists($view_file) ? 'YES' : 'NO'));

        if (file_exists($view_file)) {
            error_log('[PlatformStaffDashboard] Including DataTable view');
            include $view_file;
        } else {
            error_log('[PlatformStaffDashboard] DataTable view file not found: ' . $view_file);
            echo '<p>DataTable view not found</p>';
        }
    }

    /**
     * Render statistics boxes
     */
    public function render_statistics($config): void {
        if ($config['entity'] !== 'platform_staff') {
            return;
        }

        $view_file = WP_APP_CORE_PATH . 'src/Views/platform/stats/platform-staff-stats.php';

        if (file_exists($view_file)) {
            include $view_file;
        } else {
            error_log('[PlatformStaffDashboard] Stats view file not found: ' . $view_file);
        }
    }

    // ========================================
    // AJAX HANDLERS - Dashboard
    // ========================================

    /**
     * Handle DataTable AJAX request
     */
    public function handle_datatable(): void {
        error_log('[PlatformStaffDashboard] handle_datatable AJAX called');
        error_log('[PlatformStaffDashboard] $_POST: ' . print_r($_POST, true));

        try {
            // Verify nonce
            if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
                error_log('[PlatformStaffDashboard] Nonce check FAILED');
                throw new \Exception(__('Security check failed', 'wp-app-core'));
            }

            error_log('[PlatformStaffDashboard] Nonce check PASSED');

            // Get DataTable data from POST (not GET)
            $params = $_POST;
            error_log('[PlatformStaffDashboard] Calling get_datatable_data with params');
            $response = $this->datatable_model->get_datatable_data($params);

            error_log('[PlatformStaffDashboard] Sending JSON response');
            wp_send_json($response);

        } catch (\Exception $e) {
            error_log('[PlatformStaffDashboard] DataTable error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle get details AJAX request
     */
    public function handle_get_details(): void {
        try {
            // Verify nonce
            if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
                throw new \Exception(__('Security check failed', 'wp-app-core'));
            }

            // Panel-manager.js sends data via POST
            $staff_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

            if (!$staff_id) {
                throw new \Exception(__('Invalid staff ID', 'wp-app-core'));
            }

            // Get staff data
            $staff = $this->model->find($staff_id);

            if (!$staff) {
                throw new \Exception(__('Staff not found', 'wp-app-core'));
            }

            // Prepare tabs content
            $tabs_content = $this->render_tabs_content($staff);

            wp_send_json_success([
                'title' => sprintf(__('Platform Staff: %s', 'wp-app-core'), $staff->name),
                'tabs' => $tabs_content
            ]);

        } catch (\Exception $e) {
            error_log('[PlatformStaffDashboard] Get details error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Render all tabs content and return as array
     *
     * @param object $staff Staff data
     * @return array Associative array [tab_id => html_content]
     */
    private function render_tabs_content($staff): array {
        error_log('[PlatformStaffDashboard] render_tabs_content called');
        error_log('[PlatformStaffDashboard] Staff ID: ' . ($staff->id ?? 'NULL'));

        $tabs = [];

        // Get registered tabs
        $registered_tabs = $this->register_tabs([], 'platform_staff');
        error_log('[PlatformStaffDashboard] Registered tabs: ' . print_r(array_keys($registered_tabs), true));

        // Render each tab
        foreach ($registered_tabs as $tab_id => $tab_config) {
            if (isset($tab_config['template']) && file_exists($tab_config['template'])) {
                error_log("[PlatformStaffDashboard] Rendering tab: {$tab_id}");
                ob_start();
                $data = $staff; // Make $data available to template
                include $tab_config['template'];
                $content = ob_get_clean();
                $tabs[$tab_id] = $content;
                error_log("[PlatformStaffDashboard] Tab {$tab_id} rendered, length: " . strlen($content));
            }
        }

        error_log('[PlatformStaffDashboard] Total tabs rendered: ' . count($tabs));
        return $tabs;
    }

    /**
     * Handle get stats AJAX request
     */
    public function handle_get_stats(): void {
        try {
            // Verify nonce
            if (!check_ajax_referer('wpdt_nonce', 'nonce', false)) {
                throw new \Exception(__('Security check failed', 'wp-app-core'));
            }

            // Get stats data
            $stats = $this->get_stats_data();

            wp_send_json_success([
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            error_log('[PlatformStaffDashboard] Get stats error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics data
     */
    private function get_stats_data(): array {
        global $wpdb;

        $table = $wpdb->prefix . 'app_platform_staff';

        return [
            [
                'label' => __('Total Staff', 'wp-app-core'),
                'value' => $wpdb->get_var("SELECT COUNT(*) FROM {$table}"),
                'icon' => 'dashicons-groups'
            ],
            [
                'label' => __('Active', 'wp-app-core'),
                'value' => $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'active'"),
                'icon' => 'dashicons-yes'
            ],
            [
                'label' => __('Inactive', 'wp-app-core'),
                'value' => $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'inactive'"),
                'icon' => 'dashicons-no'
            ]
        ];
    }

    // ========================================
    // AJAX HANDLERS - MODAL CRUD
    // ========================================

    /**
     * Handle get staff form (edit)
     */
    public function handle_get_staff_form(): void {
        $nonce = $_REQUEST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'wpdt_nonce')) {
            echo '<p class="error">' . __('Security check failed', 'wp-app-core') . '</p>';
            wp_die();
        }

        $mode = $_GET['mode'] ?? 'edit';
        $staff_id = isset($_GET['staff_id']) ? (int) $_GET['staff_id'] : 0;

        // Check permissions
        if (!current_user_can('manage_options') && !current_user_can('edit_platform_users')) {
            echo '<p class="error">' . __('Permission denied', 'wp-app-core') . '</p>';
            wp_die();
        }

        try {
            if ($mode === 'edit' && $staff_id) {
                $staff = $this->model->find($staff_id);

                if (!$staff) {
                    echo '<p class="error">' . __('Staff not found', 'wp-app-core') . '</p>';
                    wp_die();
                }

                include WP_APP_CORE_PATH . 'src/Views/platform/forms/edit-staff-form.php';
            }
        } catch (\Exception $e) {
            echo '<p class="error">' . esc_html($e->getMessage()) . '</p>';
        }

        wp_die();
    }

    /**
     * Handle save staff (update)
     */
    public function handle_save_staff(): void {
        @ini_set('display_errors', '0');
        ob_start();

        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'wpdt_nonce')) {
            ob_end_clean();
            wp_send_json_error(['message' => __('Security check failed', 'wp-app-core')]);
            wp_die();
        }

        // Check permissions
        if (!current_user_can('manage_options') && !current_user_can('edit_platform_users')) {
            ob_end_clean();
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
            wp_die();
        }

        $staff_id = isset($_POST['staff_id']) ? (int) $_POST['staff_id'] : 0;

        if (!$staff_id) {
            ob_end_clean();
            wp_send_json_error(['message' => __('Invalid staff ID', 'wp-app-core')]);
            wp_die();
        }

        // Prepare data
        $data = [
            'full_name' => sanitize_text_field($_POST['full_name'] ?? ''),
            'department' => sanitize_text_field($_POST['department'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'hire_date' => sanitize_text_field($_POST['hire_date'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? 'aktif'),
        ];

        // Basic validation
        if (empty($data['full_name'])) {
            ob_end_clean();
            wp_send_json_error(['message' => __('Full name is required', 'wp-app-core')]);
            wp_die();
        }

        if (empty($data['department'])) {
            ob_end_clean();
            wp_send_json_error(['message' => __('Department is required', 'wp-app-core')]);
            wp_die();
        }

        try {
            // Update staff
            $result = $this->model->update($staff_id, $data);

            if ($result) {
                // Clear cache
                wp_cache_delete('platform_staff_' . $staff_id, 'wp-app-core');

                // Get updated staff data
                $staff = $this->model->find($staff_id);

                ob_end_clean();
                wp_send_json_success([
                    'message' => __('Staff updated successfully', 'wp-app-core'),
                    'staff' => $staff
                ]);
            } else {
                ob_end_clean();
                wp_send_json_error(['message' => __('Failed to update staff', 'wp-app-core')]);
            }

        } catch (\Exception $e) {
            ob_end_clean();
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle delete staff
     */
    public function handle_delete_staff(): void {
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'wpdt_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-app-core')]);
            wp_die();
        }

        if (!current_user_can('manage_options') && !current_user_can('delete_platform_users')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
            wp_die();
        }

        $staff_id = isset($_POST['staff_id']) ? (int) $_POST['staff_id'] : 0;

        if (!$staff_id) {
            wp_send_json_error(['message' => __('Invalid staff ID', 'wp-app-core')]);
            wp_die();
        }

        try {
            // Use model delete() method
            $result = $this->model->delete($staff_id);

            if ($result) {
                wp_send_json_success(['message' => __('Staff deleted successfully', 'wp-app-core')]);
            } else {
                wp_send_json_error(['message' => __('Staff not found or failed to delete', 'wp-app-core')]);
            }

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
