<?php
/**
 * Abstract Dashboard Controller
 *
 * Base controller class for entity dashboard pages with DataTable and panel system.
 * Eliminates code duplication across dashboard controllers by providing
 * shared implementation for common dashboard patterns.
 *
 * @package     WPAppCore
 * @subpackage  Controllers/Abstract
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Abstract/AbstractDashboardController.php
 *
 * Description: Abstract base class for all entity dashboard controllers.
 *              Provides standardized dashboard rendering with DataTable,
 *              statistics, filters, and tabbed panel system. Implements
 *              hook-based architecture for flexible content injection.
 *              Child classes only need to implement entity-specific logic.
 *
 * Dependencies:
 * - WordPress hooks system (add_action, add_filter, do_action, apply_filters)
 * - WordPress AJAX functions (check_ajax_referer, wp_send_json_*)
 * - WordPress i18n functions (__)
 * - DashboardTemplate (wp-app-core)
 * - Entity-specific DataTable Model (via getDataTableModel())
 * - Entity-specific CRUD Model (via getModel())
 *
 * Usage:
 * ```php
 * class CustomerDashboardController extends AbstractDashboardController {
 *     protected function getEntityName(): string {
 *         return 'customer';
 *     }
 *
 *     protected function getEntityPath(): string {
 *         return WP_CUSTOMER_PATH;
 *     }
 *
 *     // Implement other abstract methods...
 *
 *     // ✅ All dashboard rendering methods inherited FREE!
 *     // ✅ All AJAX handlers inherited FREE!
 *     // ✅ All hooks registered automatically!
 * }
 * ```
 *
 * Benefits:
 * - 60-70% code reduction in dashboard controllers
 * - Consistent UI/UX across all entities
 * - Standardized hook-based architecture
 * - Automatic DataTable integration
 * - Built-in statistics and filters
 * - Tabbed panel system with lazy loading
 * - Type-safe method signatures
 * - Easier testing and maintenance
 *
 * Changelog:
 * 1.0.0 - 2025-01-02
 * - Initial implementation
 * - Dashboard rendering: renderDashboard()
 * - DataTable rendering: render_datatable()
 * - Header rendering: render_header_title(), render_header_buttons()
 * - Statistics: render_header_cards(), register_stats(), handle_get_stats()
 * - Filters: render_filters()
 * - Tabs: register_tabs(), render_tab_contents()
 * - AJAX handlers: handle_datatable_ajax(), handle_get_details()
 * - Utility methods: render_partial()
 * - Abstract methods for entity-specific configuration
 */

namespace WPAppCore\Controllers\Abstract;

use WPAppCore\Views\DataTable\Templates\DashboardTemplate;

defined('ABSPATH') || exit;

abstract class AbstractDashboardController {

    /**
     * DataTable model instance
     *
     * @var object
     */
    protected $datatable_model;

    /**
     * CRUD model instance
     *
     * @var object
     */
    protected $model;

    /**
     * Constructor
     * Initializes models and registers hooks
     */
    public function __construct() {
        $this->datatable_model = $this->getDataTableModel();
        $this->model = $this->getModel();
        $this->register_hooks();
    }

    // ========================================
    // ABSTRACT METHODS (Must be implemented by child classes)
    // ========================================

    /**
     * Get entity name (singular, lowercase)
     *
     * Used for hook names, cache keys, and UI messages.
     * Must use underscore for multi-word entities (e.g., 'platform_staff').
     *
     * @return string Entity name, e.g., 'customer', 'agency', 'platform_staff'
     *
     * @example
     * ```php
     * protected function getEntityName(): string {
     *     return 'customer';
     * }
     * ```
     */
    abstract protected function getEntityName(): string;

    /**
     * Get entity display name (singular)
     *
     * Used for page titles and UI labels.
     * Can include spaces and proper capitalization.
     *
     * @return string Display name, e.g., 'Customer', 'Agency', 'Platform Staff'
     *
     * @example
     * ```php
     * protected function getEntityDisplayName(): string {
     *     return 'Customer';
     * }
     * ```
     */
    abstract protected function getEntityDisplayName(): string;

    /**
     * Get entity display name (plural)
     *
     * Used for page titles and list labels.
     *
     * @return string Plural display name, e.g., 'Customers', 'Agencies', 'Platform Staff'
     *
     * @example
     * ```php
     * protected function getEntityDisplayNamePlural(): string {
     *     return 'Customers';
     * }
     * ```
     */
    abstract protected function getEntityDisplayNamePlural(): string;

    /**
     * Get text domain for translations
     *
     * Used for __() translation functions.
     *
     * @return string Text domain, e.g., 'wp-customer', 'wp-agency', 'wp-app-core'
     *
     * @example
     * ```php
     * protected function getTextDomain(): string {
     *     return 'wp-customer';
     * }
     * ```
     */
    abstract protected function getTextDomain(): string;

    /**
     * Get entity plugin path
     *
     * Used for locating view templates and partials.
     * Must be absolute path with trailing slash.
     *
     * @return string Plugin path, e.g., WP_CUSTOMER_PATH
     *
     * @example
     * ```php
     * protected function getEntityPath(): string {
     *     return WP_CUSTOMER_PATH;
     * }
     * ```
     */
    abstract protected function getEntityPath(): string;

    /**
     * Get DataTable model instance
     *
     * Must return an instance of the entity's DataTable model class.
     * Model should have get_datatable_data() and get_total_count() methods.
     *
     * @return object DataTable model instance
     *
     * @example
     * ```php
     * protected function getDataTableModel() {
     *     return new CustomerDataTableModel();
     * }
     * ```
     */
    abstract protected function getDataTableModel();

    /**
     * Get CRUD model instance
     *
     * Must return an instance of the entity's CRUD model class.
     * Model should have find() method.
     *
     * @return object CRUD model instance
     *
     * @example
     * ```php
     * protected function getModel() {
     *     return new CustomerModel();
     * }
     * ```
     */
    abstract protected function getModel();

    /**
     * Get AJAX action for DataTable
     *
     * Action name for DataTable AJAX requests.
     *
     * @return string AJAX action, e.g., 'get_customer_datatable'
     *
     * @example
     * ```php
     * protected function getDataTableAjaxAction(): string {
     *     return 'get_customer_datatable';
     * }
     * ```
     */
    abstract protected function getDataTableAjaxAction(): string;

    /**
     * Get AJAX action for entity details
     *
     * Action name for panel content AJAX requests.
     *
     * @return string AJAX action, e.g., 'get_customer_details'
     *
     * @example
     * ```php
     * protected function getDetailsAjaxAction(): string {
     *     return 'get_customer_details';
     * }
     * ```
     */
    abstract protected function getDetailsAjaxAction(): string;

    /**
     * Get AJAX action for statistics
     *
     * Action name for statistics AJAX requests.
     *
     * @return string AJAX action, e.g., 'get_customer_stats'
     *
     * @example
     * ```php
     * protected function getStatsAjaxAction(): string {
     *     return 'get_customer_stats_v2';
     * }
     * ```
     */
    abstract protected function getStatsAjaxAction(): string;

    /**
     * Get view permission capability
     *
     * WordPress capability required to view the dashboard.
     *
     * @return string Capability, e.g., 'view_customer_list'
     *
     * @example
     * ```php
     * protected function getViewCapability(): string {
     *     return 'view_customer_list';
     * }
     * ```
     */
    abstract protected function getViewCapability(): string;

    /**
     * Register statistics boxes
     *
     * Define statistics boxes to display in header.
     * Return array with stat configuration.
     *
     * @return array Statistics configuration
     *
     * @example
     * ```php
     * protected function getStatsConfig(): array {
     *     return [
     *         'total' => [
     *             'label' => __('Total Customers', 'wp-customer'),
     *             'value' => 0,
     *             'icon' => 'dashicons-businessperson',
     *             'color' => 'blue'
     *         ],
     *         'active' => [
     *             'label' => __('Active', 'wp-customer'),
     *             'value' => 0,
     *             'icon' => 'dashicons-yes-alt',
     *             'color' => 'green'
     *         ],
     *         'inactive' => [
     *             'label' => __('Inactive', 'wp-customer'),
     *             'value' => 0,
     *             'icon' => 'dashicons-dismiss',
     *             'color' => 'red'
     *         ]
     *     ];
     * }
     * ```
     */
    abstract protected function getStatsConfig(): array;

    /**
     * Register tabs for right panel
     *
     * Define tabs to display in entity detail panel.
     * Return array with tab configuration.
     *
     * @return array Tabs configuration
     *
     * @example
     * ```php
     * protected function getTabsConfig(): array {
     *     return [
     *         'info' => [
     *             'title' => __('Customer Information', 'wp-customer'),
     *             'priority' => 10
     *         ],
     *         'branches' => [
     *             'title' => __('Branches', 'wp-customer'),
     *             'priority' => 20
     *         ]
     *     ];
     * }
     * ```
     */
    abstract protected function getTabsConfig(): array;

    // ========================================
    // CONCRETE METHODS (Shared implementation)
    // ========================================

    /**
     * Register all WordPress hooks
     *
     * Registers action and filter hooks for dashboard components.
     * Called automatically in constructor.
     *
     * @return void
     */
    protected function register_hooks(): void {
        $entity = $this->getEntityName();

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

        // AJAX handlers - Main DataTable
        add_action('wp_ajax_' . $this->getDataTableAjaxAction(), [$this, 'handle_datatable_ajax']);
        add_action('wp_ajax_' . $this->getDetailsAjaxAction(), [$this, 'handle_get_details']);
        add_action('wp_ajax_' . $this->getStatsAjaxAction(), [$this, 'handle_get_stats']);
    }

    /**
     * Render main dashboard page
     *
     * Main entry point for rendering the dashboard.
     * Uses DashboardTemplate from wp-app-core.
     *
     * @return void
     */
    public function renderDashboard(): void {
        DashboardTemplate::render([
            'entity' => $this->getEntityName(),
            'title' => $this->getEntityDisplayNamePlural(),
            'ajax_action' => $this->getDetailsAjaxAction(),
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
     * @return void
     */
    public function render_datatable($config): void {
        if (!is_array($config)) {
            return;
        }

        $entity = $config['entity'] ?? '';

        if ($entity !== $this->getEntityName()) {
            return;
        }

        // Include DataTable view file from wp-app-core
        $datatable_file = WP_APP_CORE_PATH . 'src/Views/DataTable/Templates/datatable.php';

        if (file_exists($datatable_file)) {
            include $datatable_file;
        }
    }

    /**
     * Render page header title
     *
     * Hooked to: wpapp_page_header_left
     *
     * @param array $config Dashboard configuration
     * @param string $entity Entity name
     * @return void
     */
    public function render_header_title($config, $entity): void {
        if ($entity !== $this->getEntityName()) {
            return;
        }

        $this->render_partial('header-title', [], $this->getEntityName());
    }

    /**
     * Render page header buttons
     *
     * Hooked to: wpapp_page_header_right
     *
     * @param array $config Dashboard configuration
     * @param string $entity Entity name
     * @return void
     */
    public function render_header_buttons($config, $entity): void {
        if ($entity !== $this->getEntityName()) {
            return;
        }

        $this->render_partial('header-buttons', [], $this->getEntityName());
    }

    /**
     * Render statistics header cards
     *
     * Hooked to: wpapp_statistics_cards_content
     *
     * @param string $entity Entity name
     * @return void
     */
    public function render_header_cards($entity): void {
        if ($entity !== $this->getEntityName()) {
            return;
        }

        // Get stats using DataTable model
        $stats = $this->getStatsData();

        // Render using partial template
        $this->render_partial('stat-cards', $stats, $this->getEntityName());
    }

    /**
     * Get statistics data
     *
     * Retrieves statistics from DataTable model.
     * Can be overridden by child classes for custom logic.
     *
     * @return array Statistics data
     */
    protected function getStatsData(): array {
        return [
            'total' => $this->datatable_model->get_total_count('all'),
            'active' => $this->datatable_model->get_total_count('active'),
            'inactive' => $this->datatable_model->get_total_count('inactive')
        ];
    }

    /**
     * Render filter controls
     *
     * Hooked to: wpapp_dashboard_filters
     *
     * @param array $config Dashboard configuration
     * @param string $entity Entity name
     * @return void
     */
    public function render_filters($config, $entity): void {
        if ($entity !== $this->getEntityName()) {
            return;
        }

        // Include status filter partial from wp-app-core
        $filter_file = WP_APP_CORE_PATH . 'src/Views/DataTable/Templates/partials/status-filter.php';

        if (file_exists($filter_file)) {
            include $filter_file;
        }
    }

    /**
     * Register statistics boxes
     *
     * Hooked to: wpapp_datatable_stats
     *
     * @param array $stats Existing stats
     * @param string $entity Entity type
     * @return array Modified stats array
     */
    public function register_stats($stats, $entity) {
        if ($entity !== $this->getEntityName()) {
            return $stats;
        }

        return $this->getStatsConfig();
    }

    /**
     * Register tabs for right panel
     *
     * Hooked to: wpapp_datatable_tabs
     *
     * @param array $tabs Existing tabs
     * @param string $entity Entity type
     * @return array Modified tabs array
     */
    public function register_tabs($tabs, $entity) {
        if ($entity !== $this->getEntityName()) {
            return $tabs;
        }

        return $this->getTabsConfig();
    }

    /**
     * Handle DataTable AJAX request
     *
     * Processes server-side DataTable requests.
     * Verifies nonce and permissions before processing.
     *
     * @return void Sends JSON response and dies
     */
    public function handle_datatable_ajax(): void {
        // Verify nonce
        if (!check_ajax_referer('wpapp_panel_nonce', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Security check failed', $this->getTextDomain())
            ]);
            return;
        }

        // Check permission
        if (!current_user_can($this->getViewCapability())) {
            wp_send_json_error([
                'message' => __('Permission denied', $this->getTextDomain())
            ]);
            return;
        }

        try {
            // Get DataTable data using model
            $response = $this->datatable_model->get_datatable_data($_POST);
            wp_send_json($response);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => __('Error loading data', $this->getTextDomain())
            ]);
        }
    }

    /**
     * Handle get entity details AJAX request
     *
     * Retrieves entity data and renders tabs for right panel.
     * Uses hook-based pattern for tab content injection.
     *
     * @return void Sends JSON response and dies
     */
    public function handle_get_details(): void {
        try {
            check_ajax_referer('wpapp_panel_nonce', 'nonce');

            $entity_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

            if (!$entity_id) {
                throw new \Exception(
                    sprintf(
                        __('Invalid %s ID', $this->getTextDomain()),
                        $this->getEntityDisplayName()
                    )
                );
            }

            $entity_data = $this->model->find($entity_id);

            if (!$entity_data) {
                throw new \Exception(
                    sprintf(
                        __('%s not found', $this->getTextDomain()),
                        $this->getEntityDisplayName()
                    )
                );
            }

            // Render tabs using hook-based pattern
            $tabs_content = $this->render_tab_contents($entity_data);

            // Get title (try 'name' property first, fallback to entity name + ID)
            $title = $entity_data->name ?? ($this->getEntityDisplayName() . ' #' . $entity_id);

            wp_send_json_success([
                'title' => $title,
                'tabs' => $tabs_content,
                'data' => $entity_data
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle get statistics AJAX request
     *
     * Retrieves and returns statistics data.
     *
     * @return void Sends JSON response and dies
     */
    public function handle_get_stats(): void {
        try {
            // Verify nonce
            $nonce = $_POST['nonce'] ?? '';
            if (!wp_verify_nonce($nonce, 'wpapp_panel_nonce')) {
                wp_send_json_error([
                    'message' => __('Security check failed', $this->getTextDomain())
                ]);
                return;
            }

            // Check permission
            if (!current_user_can($this->getViewCapability())) {
                wp_send_json_error([
                    'message' => __('Permission denied', $this->getTextDomain())
                ]);
                return;
            }

            // Get statistics data
            $stats = $this->getStatsData();

            wp_send_json_success($stats);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Render tab contents using hook-based pattern
     *
     * Iterates through registered tabs and triggers wpapp_tab_view_content action.
     * Child classes can hook into this action to inject tab content.
     *
     * @param object $entity_data Entity data
     * @return array Tab contents (tab_id => html)
     */
    protected function render_tab_contents($entity_data): array {
        $tabs = [];

        // Get registered tabs
        $registered_tabs = apply_filters('wpapp_datatable_tabs', [], $this->getEntityName());

        foreach ($registered_tabs as $tab_id => $tab_config) {
            // Start output buffering
            ob_start();

            // Trigger hook - allows content injection via wpapp_tab_view_content
            // Child classes should hook: add_action('wpapp_tab_view_content', [$this, 'render_info_tab'], 10, 3);
            do_action('wpapp_tab_view_content', $tab_id, $this->getEntityName(), $entity_data);

            // Capture output
            $content = ob_get_clean();

            $tabs[$tab_id] = $content;
        }

        return $tabs;
    }

    /**
     * Render a partial template
     *
     * Utility method for rendering partial view files.
     * Extracts data array as variables for template.
     *
     * @param string $partial Partial name (without .php extension)
     * @param array $data Data to pass to partial
     * @param string $context Context (entity) directory
     * @return void
     */
    protected function render_partial(string $partial, array $data = [], string $context = ''): void {
        extract($data);

        $partial_path = $this->getEntityPath() . 'src/Views/' . $context . '/partials/' . $partial . '.php';

        if (file_exists($partial_path)) {
            include $partial_path;
        }
    }
}
