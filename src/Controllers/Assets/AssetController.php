<?php
/**
 * Asset Controller Class
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Assets
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Assets/AssetController.php
 *
 * Description: Mengelola asset loading untuk plugin wp-app-core.
 *              REFACTORED: Simplified from Strategy Pattern to Single File.
 *              Inspired by wp-customer AssetController (proven pattern).
 *
 * Changelog:
 * 2.0.0 - 2025-12-25
 * - MAJOR REFACTOR: Removed Strategy Pattern
 * - Consolidated all asset loading into single file
 * - Removed AssetStrategyInterface and Strategies folder
 * - Simplified architecture like wp-customer
 * - Method-based approach instead of class-based strategies
 *
 * 1.0.0 - 2025-01-09
 * - Initial implementation with Strategy Pattern (deprecated)
 */

namespace WPAppCore\Controllers\Assets;

defined('ABSPATH') || exit;

class AssetController {
    /**
     * Singleton instance
     *
     * @var AssetController|null
     */
    private static $instance = null;

    /**
     * Plugin name
     *
     * @var string
     */
    private $plugin_name;

    /**
     * Plugin version
     *
     * @var string
     */
    private $version;

    /**
     * Get singleton instance
     *
     * @return AssetController
     */
    public static function get_instance(): AssetController {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor (Singleton pattern)
     */
    private function __construct() {
        $this->plugin_name = 'wp-app-core';
        $this->version = defined('WP_APP_CORE_VERSION') ? WP_APP_CORE_VERSION : '1.0.0';

        // Register hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Initialize AssetController
     * Called from main plugin file
     *
     * @return void
     */
    public function init(): void {
        // Hook for extensions to register additional assets
        do_action('wpapp_asset_controller_init', $this);
    }

    /**
     * Enqueue frontend assets
     *
     * @return void
     */
    public function enqueue_frontend_assets(): void {
        // Frontend assets can be added here if needed
    }

    /**
     * Enqueue admin assets (styles and scripts)
     *
     * @return void
     */
    public function enqueue_admin_assets(): void {
        $screen = get_current_screen();
        if (!$screen) {
            return;
        }

        // Platform Settings page
        if (strpos($screen->id, 'wp-app-core-settings') !== false) {
            $this->enqueue_settings_assets();
        }

        // Platform Staff Dashboard
        // Check both screen ID and page parameter for compatibility
        $is_platform_staff = strpos($screen->id, 'platform-staff') !== false
                          || (isset($_GET['page']) && $_GET['page'] === 'wp-app-core-platform-staff');

        if ($is_platform_staff) {
            $this->enqueue_platform_staff_assets();
        }
    }


    /**
     * Enqueue platform staff dashboard assets
     *
     * @return void
     */
    private function enqueue_platform_staff_assets(): void {
        // Enqueue CSS for forms and info display
        wp_enqueue_style(
            'platform-staff-forms',
            WP_APP_CORE_PLUGIN_URL . 'assets/css/platform/platform-staff-forms.css',
            [],
            $this->version
        );

        wp_enqueue_style(
            'platform-staff-info',
            WP_APP_CORE_PLUGIN_URL . 'assets/css/platform/platform-staff-info.css',
            [],
            $this->version
        );

        // Enqueue minimal JS for DataTable initialization
        // Dependency: jquery only (datatables will be loaded by wp-datatable BaseAssets)
        // Nonce and config will be provided by wp-datatable DualPanelAssets (wpdtConfig)
        wp_enqueue_script(
            'platform-staff-datatable',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/platform/platform-staff-datatable.js',
            ['jquery'],
            $this->version,
            false  // Load in header instead of footer
        );

        // Enqueue modal handler for edit/delete operations
        wp_enqueue_script(
            'platform-staff-modal-handler',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/platform/platform-staff-modal-handler.js',
            ['jquery', 'wp-modal', 'platform-staff-datatable'],
            $this->version,
            true  // Load in footer
        );

        // Localize script with config
        wp_localize_script('platform-staff-modal-handler', 'wpPlatformStaffConfig', [
            'nonce' => wp_create_nonce('wpdt_nonce'),
            'ajaxUrl' => admin_url('admin-ajax.php')
        ]);
    }

    /**
     * Enqueue settings page assets (styles and scripts)
     *
     * @return void
     */
    private function enqueue_settings_assets(): void {
        // Base settings styles
        wp_enqueue_style(
            'wpapp-settings-base',
            WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/wpapp-settings-style.css',
            [],
            $this->version
        );

        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

        // Tab-specific styles
        $this->enqueue_settings_tab_styles($current_tab);

        // Base settings script
        wp_enqueue_script(
            'wpapp-settings-base',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/wpapp-settings-script.js',
            ['jquery'],
            $this->version,
            true
        );

        // Error logger for debugging (load in head)
        wp_enqueue_script(
            'platform-error-logger',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/platform-error-logger.js',
            [],
            filemtime(WP_APP_CORE_PLUGIN_DIR . 'assets/js/settings/platform-error-logger.js'),
            false
        );

        // Localize script with wpAppCoreSettings
        wp_localize_script('wpapp-settings-base', 'wpAppCoreSettings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpapp_nonce'),
            'currentTab' => $current_tab,
            'i18n' => [
                'saving' => __('Saving...', 'wp-app-core'),
                'saved' => __('Settings saved successfully.', 'wp-app-core'),
                'error' => __('Error saving settings.', 'wp-app-core'),
                'confirmReset' => __('Are you sure you want to reset settings to defaults?', 'wp-app-core'),
            ]
        ]);

        // Settings Reset Script (global for all plugins)
        wp_enqueue_script(
            'wpapp-settings-reset-script',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/wpapp-settings-reset-script.js',
            ['jquery', 'wp-modal'],
            filemtime(WP_APP_CORE_PLUGIN_DIR . 'assets/js/settings/wpapp-settings-reset-script.js'),
            true
        );

        // Tab-specific scripts
        $this->enqueue_settings_tab_scripts($current_tab);
    }

    /**
     * Enqueue settings tab-specific styles
     *
     * @param string $current_tab Current tab ID
     * @return void
     */
    private function enqueue_settings_tab_styles(string $current_tab): void {
        $tab_styles = [
            'general' => 'general-tab-style.css',
            'email' => 'email-tab-style.css',
            'security-authentication' => 'security-authentication-tab-style.css',
            'security-session' => 'security-session-tab-style.css',
            'security-policy' => 'security-policy-tab-style.css',
            'permissions' => 'permissions-tab-style.css',
            'demo-data' => '../demo-data/wpapp-demo-data.css',
        ];

        if (isset($tab_styles[$current_tab])) {
            $file_path = WP_APP_CORE_PLUGIN_DIR . 'assets/css/settings/' . $tab_styles[$current_tab];

            if (file_exists($file_path)) {
                wp_enqueue_style(
                    'wpapp-settings-' . $current_tab,
                    WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/' . $tab_styles[$current_tab],
                    ['wpapp-settings-base'],
                    $this->version
                );

                do_action('wpapp_after_shared_tab_style', $current_tab, 'wpapp-settings-' . $current_tab);
            }
        }
    }

    /**
     * Enqueue settings tab-specific scripts
     *
     * @param string $current_tab Current tab ID
     * @return void
     */
    private function enqueue_settings_tab_scripts(string $current_tab): void {
        // Platform-specific tab scripts
        $platform_tab_scripts = [
            'general' => 'platform-general-tab-script.js',
            'email' => 'platform-email-tab-script.js',
            'security-authentication' => 'platform-security-authentication-tab-script.js',
            'security-session' => 'platform-security-session-tab-script.js',
            'security-policy' => 'platform-security-policy-tab-script.js',
            'permissions' => 'platform-permissions-tab-script.js',
        ];

        // Shared tab scripts (reusable by other plugins)
        $shared_tab_scripts = [
            'demo-data' => '../demo-data/wpapp-demo-data.js',
        ];

        // Check platform-specific scripts first
        if (isset($platform_tab_scripts[$current_tab])) {
            $file_path = WP_APP_CORE_PLUGIN_DIR . 'assets/js/settings/' . $platform_tab_scripts[$current_tab];

            if (file_exists($file_path)) {
                $dependencies = ['jquery', 'wpapp-settings-base', 'wpapp-settings-reset-script'];

                wp_enqueue_script(
                    'platform-settings-' . $current_tab,
                    WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/' . $platform_tab_scripts[$current_tab],
                    $dependencies,
                    filemtime($file_path),
                    true
                );

                do_action('wpapp_after_platform_tab_script', $current_tab, 'platform-settings-' . $current_tab);
            }
        }
        // Check shared scripts
        elseif (isset($shared_tab_scripts[$current_tab])) {
            $file_path = WP_APP_CORE_PLUGIN_DIR . 'assets/js/settings/' . $shared_tab_scripts[$current_tab];

            if (file_exists($file_path)) {
                $dependencies = ['jquery', 'wpapp-settings-base', 'wpapp-settings-reset-script'];

                // Add wp-modal dependency for demo-data tab
                if ($current_tab === 'demo-data') {
                    $dependencies[] = 'wp-modal';
                }

                wp_enqueue_script(
                    'wpapp-settings-' . $current_tab,
                    WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/' . $shared_tab_scripts[$current_tab],
                    $dependencies,
                    filemtime($file_path),
                    true
                );

                do_action('wpapp_after_shared_tab_script', $current_tab, 'wpapp-settings-' . $current_tab);
            }
        }
    }
}
