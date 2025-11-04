<?php
/**
 * Platform Staff Dashboard Controller
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Platform
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Platform/PlatformStaffDashboardController.php
 *
 * Description: Dashboard controller untuk Platform Staff management.
 *              Extends AbstractDashboardController untuk inherit dashboard system.
 *              Handles DataTable rendering, statistics, filters, and tabs.
 *              Integrates dengan centralized base panel system.
 *
 * Changelog:
 * 2.0.0 - 2025-11-04 (TODO-1198: Abstract Controller Implementation)
 * - BREAKING: Refactored to extend AbstractDashboardController
 * - Code reduction: 448 lines → ~260 lines (42% reduction)
 * - Implements 13 abstract methods from base class
 * - All dashboard rendering inherited FREE from AbstractDashboardController
 * - All AJAX handlers inherited FREE (datatable, details, stats)
 * - All hook registration inherited FREE (6 actions + 2 filters)
 * - KEPT: Tab content injection hooks (render_info_tab, render_placeholder_tab)
 * - ADDED: Backward compatibility for old AJAX action (handle_platform_staff_datatable)
 * - Maintains 2 tabs: Info + Placeholder
 * - Full integration with wp-app-core DataTable system
 * - Custom override: getStatsData() for Indonesian status (aktif/tidak aktif)
 *
 * 1.0.0 - 2025-11-01 (TODO-1192)
 * - Initial implementation following AgencyDashboardController pattern
 * - Integrated with centralized DataTable system
 * - Register hooks for DataTable, stats, tabs
 * - Implement AJAX handlers
 * - Support 2 tabs: Info + Placeholder
 */

namespace WPAppCore\Controllers\Platform;

use WPAppCore\Controllers\Abstract\AbstractDashboardController;
use WPAppCore\Models\Platform\PlatformStaffDataTableModel;
use WPAppCore\Models\Platform\PlatformStaffModel;

defined('ABSPATH') || exit;

class PlatformStaffDashboardController extends AbstractDashboardController {

    /**
     * Constructor
     *
     * Initializes models and registers hooks via parent
     */
    public function __construct() {
        parent::__construct();

        // Register tab content hooks
        add_action('wpapp_tab_view_content', [$this, 'render_info_tab'], 10, 3);
        add_action('wpapp_tab_view_content', [$this, 'render_placeholder_tab'], 10, 3);

        // Backward compatibility: Register old AJAX action names
        // Old file used: handle_platform_staff_datatable
        // Parent class uses: get_platform_staff_datatable
        add_action('wp_ajax_handle_platform_staff_datatable', [$this, 'handle_datatable_ajax']);
    }

    // ========================================
    // IMPLEMENT ABSTRACT METHODS (13 required)
    // ========================================

    /**
     * Get entity name (singular, lowercase)
     *
     * @return string
     */
    protected function getEntityName(): string {
        return 'platform_staff';
    }

    /**
     * Get entity display name (singular)
     *
     * @return string
     */
    protected function getEntityDisplayName(): string {
        return 'Platform Staff';
    }

    /**
     * Get entity display name (plural)
     *
     * @return string
     */
    protected function getEntityDisplayNamePlural(): string {
        return 'Platform Staff';
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
     * Get entity plugin path
     *
     * @return string
     */
    protected function getEntityPath(): string {
        return WP_APP_CORE_PATH;
    }

    /**
     * Get DataTable model instance
     *
     * @return PlatformStaffDataTableModel
     */
    protected function getDataTableModel() {
        return new PlatformStaffDataTableModel();
    }

    /**
     * Get CRUD model instance
     *
     * @return PlatformStaffModel
     */
    protected function getModel() {
        return new PlatformStaffModel();
    }

    /**
     * Get AJAX action for DataTable
     *
     * @return string
     */
    protected function getDataTableAjaxAction(): string {
        return 'get_platform_staff_datatable';
    }

    /**
     * Get AJAX action for entity details
     *
     * @return string
     */
    protected function getDetailsAjaxAction(): string {
        return 'get_platform_staff_details';
    }

    /**
     * Get AJAX action for statistics
     *
     * @return string
     */
    protected function getStatsAjaxAction(): string {
        return 'get_platform_staff_stats';
    }

    /**
     * Get view permission capability
     *
     * @return string
     */
    protected function getViewCapability(): string {
        return 'view_platform_users';
    }

    /**
     * Get statistics configuration
     *
     * @return array
     */
    protected function getStatsConfig(): array {
        return [
            'total' => [
                'label' => __('Total Staff', 'wp-app-core'),
                'value' => 0,
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
     * Get tabs configuration
     *
     * @return array
     */
    protected function getTabsConfig(): array {
        return [
            'info' => [
                'title' => __('Staff Information', 'wp-app-core'),
                'priority' => 10
            ],
            'placeholder' => [
                'title' => __('Additional', 'wp-app-core'),
                'priority' => 20
            ]
        ];
    }

    // ========================================
    // TAB CONTENT RENDERING
    // ========================================

    /**
     * Render info tab content
     *
     * Hooked to: wpapp_tab_view_content
     *
     * @param string $tab_id Current tab ID
     * @param string $entity Entity name
     * @param mixed $data Entity data
     */
    public function render_info_tab($tab_id, $entity, $data): void {
        if ($entity !== 'platform_staff' || $tab_id !== 'info') {
            return;
        }

        // Convert object to variable for template
        $staff = $data;
        $user = get_userdata($staff->user_id);

        $tab_file = WP_APP_CORE_PATH . 'src/Views/platform-staff/tabs/info.php';

        if (file_exists($tab_file)) {
            include $tab_file;
        }
    }

    /**
     * Render placeholder tab content
     *
     * Hooked to: wpapp_tab_view_content
     *
     * @param string $tab_id Current tab ID
     * @param string $entity Entity name
     * @param mixed $data Entity data
     */
    public function render_placeholder_tab($tab_id, $entity, $data): void {
        if ($entity !== 'platform_staff' || $tab_id !== 'placeholder') {
            return;
        }

        $staff = $data;

        $tab_file = WP_APP_CORE_PATH . 'src/Views/platform-staff/tabs/placeholder.php';

        if (file_exists($tab_file)) {
            include $tab_file;
        }
    }

    // ========================================
    // CUSTOM STATISTICS OVERRIDE
    // ========================================

    /**
     * Override getStatsData to handle aktif/tidak aktif status
     *
     * Platform Staff uses 'aktif'/'tidak aktif' instead of 'active'/'inactive'
     *
     * @return array Statistics data
     */
    protected function getStatsData(): array {
        return [
            'total' => $this->datatable_model->get_total_count('all'),
            'active' => $this->datatable_model->get_total_count('aktif'),
            'inactive' => $this->datatable_model->get_total_count('tidak aktif')
        ];
    }
}
