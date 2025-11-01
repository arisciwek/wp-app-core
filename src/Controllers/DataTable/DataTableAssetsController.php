<?php
/**
 * DataTable Assets Controller
 *
 * Manages enqueuing of CSS and JS files for DataTable dashboard system.
 * Handles localization and dependency management.
 *
 * @package WPAppCore
 * @subpackage Controllers\DataTable
 * @since 1.0.0
 * @author arisciwek
 *
 * Path: wp-app-core/src/Controllers/DataTable/DataTableAssetsController.php
 *
 * Responsibilities:
 * - Enqueue global DataTable CSS
 * - Enqueue global DataTable JavaScript (panel manager, tab manager)
 * - Localize scripts with AJAX data
 * - Handle conditional loading (only on admin pages with DataTable)
 *
 * Usage:
 * ```php
 * // Initialize in main plugin file
 * $assets_controller = new \WPAppCore\Controllers\DataTable\DataTableAssetsController();
 * $assets_controller->init();
 * ```
 */

namespace WPAppCore\Controllers\DataTable;

defined('ABSPATH') || exit;

class DataTableAssetsController {

    /**
     * Plugin version for cache busting
     *
     * @var string
     */
    private $version;

    /**
     * Constructor
     */
    public function __construct() {
        $this->version = defined('WP_APP_CORE_VERSION') ? WP_APP_CORE_VERSION : '1.0.0';
    }

    /**
     * Initialize controller
     *
     * @return void
     */
    public function init() {
        // Enqueue assets on admin pages
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Enqueue CSS and JS assets
     *
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_assets($hook) {
        /**
         * REFACTORED (TODO-1192): Plug & Play Pattern
         *
         * Assets sekarang di-load secara OTOMATIS oleh DashboardTemplate::render()
         * Plugin TIDAK PERLU register hooks atau modifikasi core
         *
         * Method ini hanya untuk manual override jika diperlukan
         */

        // Allow manual override via filter (untuk kasus edge case)
        $force_load = apply_filters('wpapp_datatable_force_load_assets', false, $hook);

        if ($force_load) {
            $this->load_assets();
        }

        // Note: Normal case - assets loaded by DashboardTemplate::ensure_assets_loaded()
        // Tidak perlu enqueue di sini
    }

    /**
     * Load all DataTable assets
     *
     * Loads CSS, JavaScript, and localizations
     * Called by DashboardTemplate::ensure_assets_loaded() or force_enqueue()
     *
     * @return void
     */
    private function load_assets() {
        $this->enqueue_styles();
        $this->enqueue_scripts();
        $this->localize_scripts();
    }

    /**
     * Enqueue CSS styles
     *
     * @return void
     */
    private function enqueue_styles() {
        // Global DataTable CSS
        wp_enqueue_style(
            'wpapp-datatable-css',
            WP_APP_CORE_PLUGIN_URL . 'assets/css/datatable/wpapp-datatable.css',
            [],
            $this->version
        );

        /**
         * Action: After DataTable CSS enqueued
         *
         * Allows plugins to enqueue additional styles
         *
         * @param string $version Asset version
         */
        do_action('wpapp_datatable_after_enqueue_styles', $this->version);
    }

    /**
     * Enqueue JavaScript files
     *
     * @return void
     */
    private function enqueue_scripts() {
        error_log('Enqueuing DataTable scripts...');

        // Ensure jQuery is loaded
        wp_enqueue_script('jquery');

        // Panel Manager (core functionality)
        wp_enqueue_script(
            'wpapp-panel-manager',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/datatable/wpapp-panel-manager.js',
            ['jquery'],
            $this->version,
            true
        );
        error_log('✅ Panel Manager enqueued: ' . WP_APP_CORE_PLUGIN_URL . 'assets/js/datatable/wpapp-panel-manager.js');

        // Tab Manager (depends on panel manager)
        wp_enqueue_script(
            'wpapp-tab-manager',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/datatable/wpapp-tab-manager.js',
            ['jquery', 'wpapp-panel-manager'],
            $this->version,
            true
        );
        error_log('✅ Tab Manager enqueued');

        /**
         * Action: After DataTable JS enqueued
         *
         * Allows plugins to enqueue additional scripts
         *
         * @param string $version Asset version
         */
        do_action('wpapp_datatable_after_enqueue_scripts', $this->version);
    }

    /**
     * Localize scripts with data
     *
     * @return void
     */
    private function localize_scripts() {
        error_log('Localizing scripts...');

        // Base configuration
        $config = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpapp_panel_nonce'),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'i18n' => $this->get_i18n_strings(),
        ];

        error_log('wpAppConfig: ' . print_r($config, true));

        /**
         * Filter: Modify localized script data
         *
         * Allows plugins to add custom data to wpAppConfig
         *
         * @param array $config Configuration array
         *
         * @return array Modified configuration
         */
        $config = apply_filters('wpapp_datatable_localize_data', $config);

        // Localize to wpapp-panel-manager
        wp_localize_script('wpapp-panel-manager', 'wpAppConfig', $config);
    }

    /**
     * Get internationalization strings
     *
     * @return array i18n strings
     */
    private function get_i18n_strings() {
        $strings = [
            'loading' => __('Loading...', 'wp-app-core'),
            'error' => __('Error', 'wp-app-core'),
            'close' => __('Close', 'wp-app-core'),
            'unknownError' => __('An unknown error occurred', 'wp-app-core'),
            'networkError' => __('Network error. Please check your connection.', 'wp-app-core'),
            'serverError' => __('Server error. Please try again later.', 'wp-app-core'),
        ];

        /**
         * Filter: Modify i18n strings
         *
         * Allows plugins to add custom translations
         *
         * @param array $strings Translation strings
         *
         * @return array Modified strings
         */
        return apply_filters('wpapp_datatable_i18n_strings', $strings);
    }

    /**
     * Public API: Force enqueue assets
     *
     * Called by DashboardTemplate::ensure_assets_loaded() untuk auto-loading.
     * Juga bisa dipanggil manual jika diperlukan (edge cases).
     *
     * @return void
     */
    public static function force_enqueue() {
        $instance = new self();
        $instance->load_assets();
    }

    /**
     * Public API: Check if assets are enqueued
     *
     * @return bool True if assets are enqueued
     */
    public static function is_enqueued() {
        return wp_script_is('wpapp-panel-manager', 'enqueued') &&
               wp_script_is('wpapp-tab-manager', 'enqueued') &&
               wp_style_is('wpapp-datatable-css', 'enqueued');
    }
}
