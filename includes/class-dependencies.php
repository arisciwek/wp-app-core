<?php
/**
 * Dependencies Handler Class
 *
 * @package     WP_App_Core
 * @subpackage  Includes
 * @version     1.2.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/includes/class-dependencies.php
 *
 * Description: Menangani dependencies plugin seperti CSS dan JavaScript
 *              untuk admin bar dan komponen core lainnya
 *
 * Changelog:
 * 1.2.0 - 2025-11-01 (TODO-1191: Separation of Concerns)
 * - Added: Platform Staff assets enqueue
 * - Added: enqueue_platform_staff_assets() method
 * - Centralized all asset loading (admin bar, settings, platform staff)
 * - Follows separation of concerns pattern
 *
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

        // Settings page assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_settings_assets']);

        // Platform Staff assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_platform_staff_assets']);
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

        // TODO-2192: Removed wpapp-datatable-auto-refresh.js
        // This is now handled by wp-datatable framework directly

        if (is_admin()) {
            // Leaflet.js for Map Picker (Global)
            wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
            wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);

            // WPApp Map Picker (Global Shared Component)
            wp_enqueue_script(
                'wpapp-map-picker',
                WP_APP_CORE_PLUGIN_URL . 'assets/js/map/wpapp-map-picker.js',
                ['jquery', 'leaflet'],
                $this->version,
                true
            );

            // WPApp Map Adapter (Global Integration Adapter)
            wp_enqueue_script(
                'wpapp-map-adapter',
                WP_APP_CORE_PLUGIN_URL . 'assets/js/map/wpapp-map-adapter.js',
                ['jquery', 'leaflet', 'wpapp-map-picker'],
                $this->version,
                true
            );
        }

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
    }

    /**
     * Enqueue settings page assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_settings_assets($hook) {
        // Only load on settings page
        if ($hook !== 'toplevel_page_wp-app-core-settings') {
            return;
        }

        // Main settings styles
        wp_enqueue_style(
            'wp-app-core-settings',
            WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/settings-style.css',
            [],
            $this->version
        );

        // Common settings JS
        wp_enqueue_script(
            'wp-app-core-settings',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/settings-script.js',
            ['jquery'],
            $this->version,
            true
        );

        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

        // Tab-specific assets
        switch ($current_tab) {
            case 'general':
                wp_enqueue_script(
                    'wp-app-core-general-tab',
                    WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/general-tab-script.js',
                    ['jquery', 'wp-app-core-settings'],
                    $this->version,
                    true
                );
                break;

            case 'email':
                wp_enqueue_script(
                    'wp-app-core-email-tab',
                    WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/email-tab-script.js',
                    ['jquery', 'wp-app-core-settings'],
                    $this->version,
                    true
                );
                break;

            case 'permissions':
                wp_enqueue_style(
                    'wp-app-core-permissions-tab',
                    WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/permissions-tab-style.css',
                    ['wp-app-core-settings'],
                    $this->version
                );

                wp_enqueue_script(
                    'wp-app-core-permissions-tab',
                    WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/permissions-tab-script.js',
                    ['jquery', 'wp-app-core-settings'],
                    $this->version,
                    true
                );
                break;

            case 'demo-data':
                wp_enqueue_style(
                    'wp-app-core-demo-data-tab',
                    WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/demo-data-tab-style.css',
                    ['wp-app-core-settings'],
                    $this->version
                );

                wp_enqueue_script(
                    'wp-app-core-demo-data-tab',
                    WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/platform-demo-data-tab-script.js',
                    ['jquery', 'wp-app-core-settings'],
                    $this->version,
                    true
                );
                break;

            case 'security-authentication':
                wp_enqueue_style(
                    'wp-app-core-security-authentication-tab',
                    WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/security-authentication-tab-style.css',
                    ['wp-app-core-settings'],
                    $this->version
                );

                wp_enqueue_script(
                    'wp-app-core-security-authentication-tab',
                    WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/security-authentication-tab-script.js',
                    ['jquery', 'wp-app-core-settings'],
                    $this->version,
                    true
                );
                break;

            case 'security-session':
                wp_enqueue_style(
                    'wp-app-core-security-session-tab',
                    WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/security-session-tab-style.css',
                    ['wp-app-core-settings'],
                    $this->version
                );

                wp_enqueue_script(
                    'wp-app-core-security-session-tab',
                    WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/security-session-tab-script.js',
                    ['jquery', 'wp-app-core-settings'],
                    $this->version,
                    true
                );
                break;

            case 'security-policy':
                wp_enqueue_style(
                    'wp-app-core-security-policy-tab',
                    WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/security-policy-tab-style.css',
                    ['wp-app-core-settings'],
                    $this->version
                );

                wp_enqueue_script(
                    'wp-app-core-security-policy-tab',
                    WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/security-policy-tab-script.js',
                    ['jquery', 'wp-app-core-settings'],
                    $this->version,
                    true
                );
                break;
        }

        // Localize script with common data
        wp_localize_script(
            'wp-app-core-settings',
            'wpAppCoreSettings',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_app_core_settings_nonce'),
                'i18n' => [
                    'saved' => __('Settings saved successfully', 'wp-app-core'),
                    'error' => __('Error saving settings', 'wp-app-core'),
                    'confirm' => __('Are you sure?', 'wp-app-core'),
                    'confirmReset' => __('Are you sure you want to reset all permissions to default? This action cannot be undone.', 'wp-app-core'),
                    'confirmCreateRoles' => __('Are you sure you want to create platform roles?', 'wp-app-core'),
                    'confirmDeleteRoles' => __('WARNING: This will permanently delete all platform roles. Are you sure?', 'wp-app-core'),
                    'confirmDeleteRolesDouble' => __('This action cannot be undone. Continue?', 'wp-app-core'),
                    'confirmResetCapabilities' => __('Are you sure you want to reset all platform capabilities to default values?', 'wp-app-core'),
                ]
            ]
        );
    }

    /**
     * Enqueue Platform Staff assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_platform_staff_assets($hook) {
        // Only load on platform staff pages
        if ($hook !== 'toplevel_page_wp-app-core-platform-staff' &&
            $hook !== 'platform-staff_page_wp-app-core-datatable-test') {
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

        // Platform Staff CSS (TODO-1192)
        wp_enqueue_style(
            'wp-app-core-platform-staff-header-cards',
            WP_APP_CORE_PLUGIN_URL . 'assets/css/platform/platform-staff-header-cards.css',
            ['datatables'],
            $this->version
        );

        wp_enqueue_style(
            'wp-app-core-platform-staff-filter',
            WP_APP_CORE_PLUGIN_URL . 'assets/css/platform/platform-staff-filter.css',
            ['wp-app-core-platform-staff-header-cards'],
            $this->version
        );

        // Platform Staff JavaScript (TODO-1192)
        wp_enqueue_script(
            'wp-app-core-platform-staff-datatable',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/platform/platform-staff-datatable.js',
            ['jquery', 'datatables'],
            $this->version,
            true
        );

        // Localize script for DataTable
        wp_localize_script(
            'wp-app-core-platform-staff-datatable',
            'wpAppCorePlatformStaff',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpapp_panel_nonce'),
                'i18n' => [
                    'employee_id' => __('Employee ID', 'wp-app-core'),
                    'full_name' => __('Full Name', 'wp-app-core'),
                    'department' => __('Department', 'wp-app-core'),
                    'hire_date' => __('Hire Date', 'wp-app-core'),
                    'actions' => __('Actions', 'wp-app-core'),
                    'processing' => __('Processing...', 'wp-app-core'),
                    'search' => __('Search:', 'wp-app-core'),
                    'lengthMenu' => __('Show _MENU_ entries', 'wp-app-core'),
                    'info' => __('Showing _START_ to _END_ of _TOTAL_ entries', 'wp-app-core'),
                    'infoEmpty' => __('Showing 0 to 0 of 0 entries', 'wp-app-core'),
                    'infoFiltered' => __('(filtered from _MAX_ total entries)', 'wp-app-core'),
                    'zeroRecords' => __('No matching records found', 'wp-app-core'),
                    'emptyTable' => __('No data available in table', 'wp-app-core'),
                    'first' => __('First', 'wp-app-core'),
                    'previous' => __('Previous', 'wp-app-core'),
                    'next' => __('Next', 'wp-app-core'),
                    'last' => __('Last', 'wp-app-core'),
                ]
            ]
        );
    }
}
