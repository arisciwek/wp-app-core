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

        // Platform Staff CSS
        wp_enqueue_style(
            'wp-app-core-platform-staff',
            WP_APP_CORE_PLUGIN_URL . 'assets/css/platform/platform-staff-style.css',
            ['datatables'],
            $this->version
        );

        wp_enqueue_style(
            'wp-app-core-platform-staff-datatable',
            WP_APP_CORE_PLUGIN_URL . 'assets/css/platform/platform-staff-datatable-style.css',
            ['wp-app-core-platform-staff'],
            $this->version
        );

        // Platform Staff JavaScript
        wp_enqueue_script(
            'wp-app-core-platform-staff',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/platform/platform-staff-script.js',
            ['jquery', 'datatables'],
            $this->version,
            true
        );

        wp_enqueue_script(
            'wp-app-core-platform-staff-datatable',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/platform/platform-staff-datatable-script.js',
            ['jquery', 'datatables', 'wp-app-core-platform-staff'],
            $this->version,
            true
        );

        // Localize script
        wp_localize_script(
            'wp-app-core-platform-staff',
            'wpAppCoreStaffData',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_app_core_platform_staff_nonce'),
                'i18n' => [
                    'confirmDelete' => __('Apakah Anda yakin ingin menghapus staff ini?', 'wp-app-core'),
                    'deleteSuccess' => __('Staff berhasil dihapus', 'wp-app-core'),
                    'deleteError' => __('Gagal menghapus staff', 'wp-app-core'),
                    'saveSuccess' => __('Data staff berhasil disimpan', 'wp-app-core'),
                    'saveError' => __('Gagal menyimpan data staff', 'wp-app-core'),
                    'loadError' => __('Gagal memuat data', 'wp-app-core'),
                ]
            ]
        );
    }
}
