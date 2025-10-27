<?php
/**
 * Dependencies Handler Class
 *
 * @package     WP_App_Core
 * @subpackage  Includes
 * @version     1.1.1
 * @author      arisciwek
 *
 * Path: /wp-app-core/includes/class-dependencies.php
 *
 * Description: Menangani dependencies plugin seperti CSS dan JavaScript
 *              untuk admin bar dan komponen core lainnya
 *
 * Changelog:
 * 1.1.1 - 2025-10-26 (TODO-1180)
 * - Added: Panel handler enqueue (panel-handler.js)
 * - Added: enqueue_panel_handler() private method
 * - Added: wpAppCore localized object for panel handler
 * - Uses wpapp_datatable_allowed_hooks filter for page registration
 * - Centralized detail panel handling for all plugins
 *
 * 1.1.0 - 2025-01-18 (Review-04 Fix)
 * - FIXED: Assets now load on BOTH frontend and admin
 * - Reason: Admin bar appears on both, but only admin hooks were registered
 * - Added: wp_enqueue_scripts hooks (for frontend)
 * - Added: is_admin_bar_showing() check to prevent unnecessary loading
 * - Renamed: Methods to enqueue_styles() and enqueue_scripts() (more accurate)
 * - Benefits: CSS/JS now load correctly everywhere admin bar appears
 *
 * 1.0.0 - 2025-01-18
 * - Initial creation
 * - Added admin bar CSS enqueue
 * - Organized assets following wp-agency pattern
 * - Support for feature-based folder structure
 */

defined('ABSPATH') || exit;

class WP_App_Core_Dependencies {

    /**
     * Plugin name
     * @var string
     */
    private $plugin_name;

    /**
     * Plugin version
     * @var string
     */
    private $version;

    /**
     * Constructor
     *
     * @param string $plugin_name Plugin name
     * @param string $version Plugin version
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Register hooks with priority 20 to load after WordPress core (default 10)
        // IMPORTANT: Admin bar appears on BOTH admin AND frontend, so we need both hooks!
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles'], 20);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts'], 20);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles'], 20);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts'], 20);
    }

    /**
     * Enqueue styles (both admin and frontend)
     *
     * Admin bar appears on both admin and frontend, so we need to load on both.
     */
    public function enqueue_styles() {
        // Only load if admin bar is showing
        if (!is_admin_bar_showing()) {
            return;
        }

        // Admin Bar styles - load with dependency on WP core admin-bar
        // This ensures our styles override core styles properly
        wp_enqueue_style(
            'wp-app-core-admin-bar',
            WP_APP_CORE_PLUGIN_URL . 'assets/css/admin-bar/admin-bar-style.css',
            ['admin-bar'], // Depend on WordPress core admin-bar CSS
            $this->version
        );
    }

    /**
     * Enqueue scripts (both admin and frontend)
     *
     * Admin bar appears on both admin and frontend, so we need to load on both.
     */
    public function enqueue_scripts() {
        // Only load if admin bar is showing
        if (!is_admin_bar_showing()) {
            return;
        }

        // Admin Bar scripts - basic interactive functionality
        wp_enqueue_script(
            'wp-app-core-admin-bar',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/admin-bar/admin-bar-script.js',
            ['jquery'],
            $this->version,
            true
        );

        // Localize script for future AJAX use
        wp_localize_script(
            'wp-app-core-admin-bar',
            'wpAppCoreData',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_app_core_nonce'),
                'debug' => (defined('WP_DEBUG') && WP_DEBUG)
            ]
        );

        // Panel Handler - DEPRECATED (TODO-1182)
        // Replaced by DataTableAssetsController->enqueue_panel_manager()
        // OLD panel-handler.js conflicts with NEW wpapp-panel-manager.js
        // Keeping code commented for reference
        /*
        if (is_admin()) {
            $this->enqueue_panel_handler();
        }
        */
    }

    /**
     * Enqueue panel handler script
     *
     * @deprecated 1.1.2 (TODO-1182) Replaced by DataTableAssetsController->enqueue_panel_manager()
     * @since 1.1.1
     *
     * OLD panel-handler.js has been replaced by wpapp-panel-manager.js
     * This method is kept for reference but no longer called
     */
    private function enqueue_panel_handler() {
        // Get current screen
        $screen = get_current_screen();
        if (!$screen) {
            return;
        }

        // Get allowed page hooks from filter
        // Plugins can register their pages via wpapp_datatable_allowed_hooks filter
        $allowed_hooks = apply_filters('wpapp_datatable_allowed_hooks', []);

        // Check if current screen is in allowed hooks
        if (!in_array($screen->id, $allowed_hooks)) {
            return;
        }

        // Enqueue panel handler script
        wp_enqueue_script(
            'wpapp-panel-handler',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/datatable/panel-handler.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // Localize with core configuration
        wp_localize_script('wpapp-panel-handler', 'wpAppCore', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpapp_panel_nonce'),
            'debug' => (defined('WP_DEBUG') && WP_DEBUG)
        ]);

        // Log for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[WP_App_Core] Panel handler enqueued on: ' . $screen->id);
        }
    }
}
