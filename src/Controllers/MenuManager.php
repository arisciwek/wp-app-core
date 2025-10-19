<?php
/**
 * Menu Manager
 *
 * @package     WP_App_Core
 * @subpackage  Controllers
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/MenuManager.php
 *
 * Description: Central menu manager for WP App Core plugin
 *              Manages all admin menus and submenus
 *
 * Changelog:
 * 2.0.0 - 2025-10-19
 * - Migrated to WPAppCore namespace
 * - Updated for platform settings
 * - Simplified menu structure for core plugin
 *
 * 1.0.1 - 2024-01-07
 * - Initial version from wp-customer
 */

namespace WPAppCore\Controllers;

use WPAppCore\Controllers\PlatformSettingsController;

class MenuManager {
    private $plugin_name;
    private $version;
    private $settings_controller;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->settings_controller = new PlatformSettingsController();
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

        // You can add more submenus here in the future
        // Example:
        // add_submenu_page(
        //     'wp-app-core-settings',
        //     __('Users', 'wp-app-core'),
        //     __('Users', 'wp-app-core'),
        //     'manage_platform_users',
        //     'wp-app-core-users',
        //     [$this->user_controller, 'renderPage']
        // );
    }
}
