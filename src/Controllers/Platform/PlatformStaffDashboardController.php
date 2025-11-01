<?php
/**
 * Platform Staff Dashboard Controller
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Platform
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Platform/PlatformStaffDashboardController.php
 *
 * Description: Controller untuk Platform Staff dashboard dengan base panel system.
 *              Registers hooks untuk DataTable, stats, dan tabs.
 *              Handles AJAX requests untuk panel content.
 *              Follows wp-agency AgencyDashboardController pattern.
 *
 * Dependencies:
 * - wp-app-core base panel system (DashboardTemplate)
 * - PlatformStaffDataTableModel untuk DataTable processing
 * - PlatformStaffModel untuk CRUD operations
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-1192)
 * - Initial implementation following AgencyDashboardController pattern
 * - Integrated with centralized DataTable system
 * - Register hooks for DataTable, stats, tabs
 * - Implement AJAX handlers
 * - Support 2 tabs: Info + Placeholder
 */

namespace WPAppCore\Controllers\Platform;

use WPAppCore\Models\Platform\PlatformStaffDataTableModel;
use WPAppCore\Models\Platform\PlatformStaffModel;
use WPAppCore\Controllers\DataTable\DataTableController;
use WPAppCore\Views\DataTable\Templates\DashboardTemplate;

class PlatformStaffDashboardController {

    /**
     * @var PlatformStaffDataTableModel DataTable model instance
     */
    private $datatable_model;

    /**
     * @var PlatformStaffModel CRUD model instance
     */
    private $model;

    /**
     * @var DataTableController DataTable controller instance
     */
    private $datatable_controller;

    /**
     * Constructor
     * Register all hooks for dashboard components
     */
    public function __construct() {
        $this->datatable_model = new PlatformStaffDataTableModel();
        $this->model = new PlatformStaffModel();
        $this->datatable_controller = new DataTableController();

        // Register hooks for dashboard components
        $this->register_hooks();
    }

    /**
     * Register all WordPress hooks
     */
    private function register_hooks(): void {
        // Panel content hook
        add_action('wpapp_left_panel_content', [$this, 'render_datatable'], 10, 1);

        // Page header hooks
        add_action('wpapp_page_header_left', [$this, 'render_header_title'], 10, 2);
        add_action('wpapp_page_header_right', [$this, 'render_header_buttons'], 10, 2);

        // Stats cards hook
        add_action('wpapp_statistics_cards_content', [$this, 'render_header_cards'], 10, 1);

        // Filter hooks
        add_action('wpapp_dashboard_filters', [$this, 'render_filters'], 10, 2);

        // Statistics hook
        add_filter('wpapp_datatable_stats', [$this, 'register_stats'], 10, 2);

        // Tabs hook
        add_filter('wpapp_datatable_tabs', [$this, 'register_tabs'], 10, 2);

        // Tab content injection hooks
        add_action('wpapp_tab_view_content', [$this, 'render_info_tab'], 10, 3);
        add_action('wpapp_tab_view_content', [$this, 'render_placeholder_tab'], 10, 3);

        // AJAX handlers
        add_action('wp_ajax_get_platform_staff_datatable', [$this, 'handle_datatable_ajax']);
        add_action('wp_ajax_get_platform_staff_details', [$this, 'handle_get_details']);
        add_action('wp_ajax_get_platform_staff_stats', [$this, 'handle_get_stats']);
    }

    /**
     * Render main dashboard page
     */
    public function renderDashboard(): void {
        // Render using centralized DashboardTemplate
        DashboardTemplate::render([
            'entity' => 'platform_staff',
            'title' => __('Platform Staff', 'wp-app-core'),
            'ajax_action' => 'get_platform_staff_details',
            'has_stats' => true,
            'has_tabs' => true,
        ]);
    }

    /**
     * Render DataTable HTML
     *
     * Hooked to: wpapp_left_panel_content
     *
     * @param array $config Configuration array
     */
    public function render_datatable($config): void {
        if (!is_array($config)) {
            return;
        }

        $entity = $config['entity'] ?? '';

        if ($entity !== 'platform_staff') {
            return;
        }

        // Include DataTable view file
        $datatable_file = WP_APP_CORE_PATH . 'src/Views/DataTable/Templates/datatable.php';

        if (file_exists($datatable_file)) {
            include $datatable_file;
        }
    }

    /**
     * Render page header title
     *
     * @param array $config Dashboard configuration
     * @param string $entity Entity name
     */
    public function render_header_title($config, $entity): void {
        if ($entity !== 'platform_staff') {
            return;
        }

        $this->render_partial('header-title', [], 'platform-staff');
    }

    /**
     * Render page header buttons
     *
     * @param array $config Dashboard configuration
     * @param string $entity Entity name
     */
    public function render_header_buttons($config, $entity): void {
        if ($entity !== 'platform_staff') {
            return;
        }

        $this->render_partial('header-buttons', [], 'platform-staff');
    }

    /**
     * Render statistics header cards
     *
     * @param string $entity Entity name
     */
    public function render_header_cards($entity): void {
        if ($entity !== 'platform_staff') {
            return;
        }

        // Get stats directly using DataTable model
        $total = $this->datatable_model->get_total_count('all');
        $active = $this->datatable_model->get_total_count('aktif');
        $inactive = $this->datatable_model->get_total_count('tidak aktif');

        // Render using partial template
        $this->render_partial('stat-cards', compact('total', 'active', 'inactive'), 'platform-staff');
    }

    /**
     * Render filter controls
     *
     * @param array $config Dashboard configuration
     * @param string $entity Entity name
     */
    public function render_filters($config, $entity): void {
        if ($entity !== 'platform_staff') {
            return;
        }

        // Include status filter partial
        $filter_file = WP_APP_CORE_PATH . 'src/Views/DataTable/Templates/partials/status-filter.php';

        if (file_exists($filter_file)) {
            include $filter_file;
        }
    }

    /**
     * Register statistics boxes
     *
     * @param array $stats Existing stats
     * @param string $entity Entity type
     * @return array Modified stats array
     */
    public function register_stats($stats, $entity) {
        if ($entity !== 'platform_staff') {
            return $stats;
        }

        return [
            'total' => [
                'label' => __('Total Staff', 'wp-app-core'),
                'value' => 0,  // Will be filled by AJAX
                'icon' => 'dashicons-groups',
                'color' => 'blue'
            ],
            'active' => [
                'label' => __('Active', 'wp-app-core'),
                'value' => 0,
                'icon' => 'dashicons-yes-alt',
                'color' => 'green'
            ],
            'inactive' => [
                'label' => __('Inactive', 'wp-app-core'),
                'value' => 0,
                'icon' => 'dashicons-dismiss',
                'color' => 'red'
            ]
        ];
    }

    /**
     * Register tabs for right panel
     *
     * @param array $tabs Existing tabs
     * @param string $entity Entity type
     * @return array Modified tabs array
     */
    public function register_tabs($tabs, $entity) {
        if ($entity !== 'platform_staff') {
            return $tabs;
        }

        return [
            'info' => [
                'title' => __('Staff Information', 'wp-app-core'),
                'priority' => 10,
                'lazy_load' => false
            ],
            'placeholder' => [
                'title' => __('Additional', 'wp-app-core'),
                'priority' => 20,
                'lazy_load' => false
            ]
        ];
    }

    /**
     * Render info tab content
     *
     * @param string $tab_id Current tab ID
     * @param string $entity Entity name
     * @param mixed $data Entity data
     */
    public function render_info_tab($tab_id, $entity, $data): void {
        if ($entity !== 'platform_staff' || $tab_id !== 'info') {
            return;
        }

        $tab_file = WP_APP_CORE_PATH . 'src/Views/platform-staff/tabs/info.php';

        if (file_exists($tab_file)) {
            include $tab_file;
        }
    }

    /**
     * Render placeholder tab content
     *
     * @param string $tab_id Current tab ID
     * @param string $entity Entity name
     * @param mixed $data Entity data
     */
    public function render_placeholder_tab($tab_id, $entity, $data): void {
        if ($entity !== 'platform_staff' || $tab_id !== 'placeholder') {
            return;
        }

        $tab_file = WP_APP_CORE_PATH . 'src/Views/platform-staff/tabs/placeholder.php';

        if (file_exists($tab_file)) {
            include $tab_file;
        }
    }

    /**
     * Handle DataTable AJAX request
     */
    public function handle_datatable_ajax(): void {
        // Verify nonce
        if (!check_ajax_referer('wpapp_panel_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-app-core')]);
            return;
        }

        // Check permission
        if (!current_user_can('view_platform_users')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
            return;
        }

        try {
            // Get DataTable data using model
            $response = $this->datatable_model->get_datatable_data($_POST);
            wp_send_json($response);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => __('Error loading data', 'wp-app-core')
            ]);
        }
    }

    /**
     * Handle get staff details AJAX request
     */
    public function handle_get_details(): void {
        try {
            check_ajax_referer('wpapp_panel_nonce', 'nonce');

            $staff_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

            if (!$staff_id) {
                throw new \Exception(__('Invalid staff ID', 'wp-app-core'));
            }

            $staff = $this->model->find($staff_id);

            if (!$staff) {
                throw new \Exception(__('Staff not found', 'wp-app-core'));
            }

            // Get user data
            $user = get_userdata($staff->user_id);

            // Prepare tab contents
            $tabs = [];

            // Info tab
            ob_start();
            include WP_APP_CORE_PATH . 'src/Views/platform-staff/tabs/info.php';
            $tabs['info'] = ob_get_clean();

            // Placeholder tab
            ob_start();
            include WP_APP_CORE_PATH . 'src/Views/platform-staff/tabs/placeholder.php';
            $tabs['placeholder'] = ob_get_clean();

            wp_send_json_success([
                'title' => $staff->full_name,
                'tabs' => $tabs,
                'data' => $staff
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle get statistics AJAX request
     */
    public function handle_get_stats(): void {
        try {
            check_ajax_referer('wpapp_panel_nonce', 'nonce');

            $total = $this->datatable_model->get_total_count('all');
            $active = $this->datatable_model->get_total_count('aktif');
            $inactive = $this->datatable_model->get_total_count('tidak aktif');

            wp_send_json_success([
                'total' => $total,
                'active' => $active,
                'inactive' => $inactive
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Render a partial template
     *
     * @param string $partial Partial name
     * @param array $data Data to pass to partial
     * @param string $context Context (entity) directory
     */
    private function render_partial(string $partial, array $data = [], string $context = ''): void {
        extract($data);

        $partial_path = WP_APP_CORE_PATH . 'src/Views/' . $context . '/partials/' . $partial . '.php';

        if (file_exists($partial_path)) {
            include $partial_path;
        }
    }

    /**
     * Build WHERE clause from array
     *
     * @param array $conditions WHERE conditions
     * @return string WHERE clause SQL
     */
    private function build_where_clause(array $conditions): string {
        if (empty($conditions)) {
            return '';
        }

        global $wpdb;
        $where_parts = [];

        foreach ($conditions as $key => $value) {
            if (is_numeric($key)) {
                // Raw SQL condition
                $where_parts[] = $value;
            } else {
                // Key-value pair
                $where_parts[] = $wpdb->prepare("{$key} = %s", $value);
            }
        }

        return 'WHERE ' . implode(' AND ', $where_parts);
    }
}
