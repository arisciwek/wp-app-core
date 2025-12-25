<?php
/**
 * Menu Manager
 *
 * @package     WP_App_Core
 * @subpackage  Controllers
 * @version     2.2.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/MenuManager.php
 *
 * Description: Central menu manager for WP App Core plugin
 *              Manages all admin menus and submenus
 *
 * Changelog:
 * 2.2.0 - 2025-01-09 (TODO-1203)
 * - Changed: Use PlatformSettingsPageController instead of PlatformSettingsController
 * - Integration with refactored settings architecture
 *
 * 2.1.0 - 2025-11-01 (TODO-1191: Separation of Concerns)
 * - Added: Platform Staff menu registration
 * - Added: staff_controller property
 * - Centralized menu management for all controllers
 *
 * 2.0.0 - 2025-10-19
 * - Migrated to WPAppCore namespace
 * - Updated for platform settings
 * - Simplified menu structure for core plugin
 *
 * 1.0.1 - 2024-01-07
 * - Initial version from wp-customer
 */

namespace WPAppCore\Controllers;

use WPAppCore\Controllers\Settings\PlatformSettingsPageController;
use WPAppCore\Controllers\Platform\PlatformStaffDashboardController;

class MenuManager {
    private $plugin_name;
    private $version;
    private $settings_controller;
    private $staff_dashboard_controller;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->settings_controller = new PlatformSettingsPageController();
        $this->staff_dashboard_controller = new PlatformStaffDashboardController();
    }

    public function init() {
        add_action('admin_menu', [$this, 'registerMenus']);
        $this->settings_controller->init();
    }

    public function registerMenus() {
        // Main Platform Settings Menu
        add_menu_page(
            __('Platform Settings', 'wp-app-core'),
            __('Platform', 'wp-app-core'),
            'manage_options',
            'wp-app-core-settings',
            [$this->settings_controller, 'renderPage'],
            'dashicons-admin-generic',
            60
        );

        // Platform Staff Menu (TODO-2205: wp-datatable Integration)
        add_menu_page(
            __('Platform Staff', 'wp-app-core'),
            __('Platform Staff', 'wp-app-core'),
            'view_platform_users',
            'wp-app-core-platform-staff',
            [$this->staff_dashboard_controller, 'render'],
            'dashicons-groups',
            25
        );
    }
}
