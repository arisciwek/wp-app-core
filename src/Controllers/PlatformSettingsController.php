<?php
/**
 * Platform Settings Controller
 *
 * @package     WP_App_Core
 * @subpackage  Controllers
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/PlatformSettingsController.php
 *
 * Description: Controller untuk mengelola halaman settings platform
 *              Handles tab navigation, AJAX, dan form submission
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial release
 * - Multi-tab settings management
 * - AJAX handlers for settings save
 * - Permission checks
 */

namespace WPAppCore\Controllers;

use WPAppCore\Models\Settings\PlatformSettingsModel;
use WPAppCore\Models\Settings\PlatformPermissionModel;
use WPAppCore\Models\Settings\EmailSettingsModel;
use WPAppCore\Models\Settings\SecurityAuthenticationModel;
use WPAppCore\Models\Settings\SecuritySessionModel;
use WPAppCore\Models\Settings\SecurityPolicyModel;

class PlatformSettingsController {

    private $platform_model;
    private $permission_model;
    private $email_model;
    private $auth_model;
    private $session_model;
    private $policy_model;

    public function __construct() {
        $this->platform_model = new PlatformSettingsModel();
        $this->permission_model = new PlatformPermissionModel();
        $this->email_model = new EmailSettingsModel();
        $this->auth_model = new SecurityAuthenticationModel();
        $this->session_model = new SecuritySessionModel();
        $this->policy_model = new SecurityPolicyModel();
    }

    /**
     * Initialize controller
     */
    public function init() {
        // Menu registration is handled by MenuManager
        add_action('admin_init', [$this, 'registerSettings']);
        $this->registerAjaxHandlers();
        $this->registerAssets();
    }

    /**
     * Register settings
     */
    public function registerSettings() {
        // Platform Settings
        register_setting(
            'wp_app_core_platform_settings',
            'wp_app_core_platform_settings',
            [
                'sanitize_callback' => [$this->platform_model, 'sanitizeSettings'],
                'default' => $this->platform_model->getDefaultSettings()
            ]
        );

        // Email Settings
        register_setting(
            'wp_app_core_email_settings',
            'wp_app_core_email_settings',
            [
                'sanitize_callback' => [$this->email_model, 'sanitizeSettings'],
                'default' => $this->email_model->getDefaultSettings()
            ]
        );

        // Security Authentication Settings
        register_setting(
            'wp_app_core_security_authentication',
            'wp_app_core_security_authentication',
            [
                'sanitize_callback' => [$this->auth_model, 'sanitizeSettings'],
                'default' => $this->auth_model->getDefaultSettings()
            ]
        );

        // Security Session Settings
        register_setting(
            'wp_app_core_security_session',
            'wp_app_core_security_session',
            [
                'sanitize_callback' => [$this->session_model, 'sanitizeSettings'],
                'default' => $this->session_model->getDefaultSettings()
            ]
        );

        // Security Policy Settings
        register_setting(
            'wp_app_core_security_policy',
            'wp_app_core_security_policy',
            [
                'sanitize_callback' => [$this->policy_model, 'sanitizeSettings'],
                'default' => $this->policy_model->getDefaultSettings()
            ]
        );

        // Development Settings
        register_setting(
            'wp_app_core_development_settings',
            'wp_app_core_development_settings',
            [
                'sanitize_callback' => [$this, 'sanitizeDevelopmentSettings'],
                'default' => [
                    'enable_development' => 0,
                    'clear_data_on_deactivate' => 0
                ]
            ]
        );
    }

    /**
     * Register AJAX handlers
     */
    private function registerAjaxHandlers() {
        add_action('wp_ajax_save_platform_settings', [$this, 'handleSavePlatformSettings']);
        add_action('wp_ajax_save_email_settings', [$this, 'handleSaveEmailSettings']);
        add_action('wp_ajax_save_security_authentication', [$this, 'handleSaveSecurityAuthentication']);
        add_action('wp_ajax_save_security_session', [$this, 'handleSaveSecuritySession']);
        add_action('wp_ajax_save_security_policy', [$this, 'handleSaveSecurityPolicy']);
        add_action('wp_ajax_save_platform_permissions', [$this, 'handleSavePlatformPermissions']);
        add_action('wp_ajax_reset_platform_permissions', [$this, 'handleResetPlatformPermissions']);

        // Security Settings Reset AJAX handlers
        add_action('wp_ajax_reset_security_authentication', [$this, 'handleResetSecurityAuthentication']);
        add_action('wp_ajax_reset_security_session', [$this, 'handleResetSecuritySession']);
        add_action('wp_ajax_reset_security_policy', [$this, 'handleResetSecurityPolicy']);

        // Demo Data / Role Management AJAX handlers
        add_action('wp_ajax_create_platform_roles', [$this, 'handleCreatePlatformRoles']);
        add_action('wp_ajax_delete_platform_roles', [$this, 'handleDeletePlatformRoles']);
        add_action('wp_ajax_reset_platform_capabilities', [$this, 'handleResetPlatformCapabilities']);

        // Platform Staff Demo Data AJAX handlers
        add_action('wp_ajax_generate_platform_staff', [$this, 'handleGeneratePlatformStaff']);
        add_action('wp_ajax_delete_platform_staff', [$this, 'handleDeletePlatformStaff']);
        add_action('wp_ajax_platform_staff_stats', [$this, 'handlePlatformStaffStats']);
    }

    /**
     * Register assets
     */
    private function registerAssets() {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Enqueue assets
     */
    public function enqueueAssets($hook) {
        if ($hook !== 'toplevel_page_wp-app-core-settings') {
            return;
        }

        // Common settings CSS
        wp_enqueue_style(
            'wp-app-core-settings',
            WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/settings.css',
            [],
            WP_APP_CORE_VERSION
        );

        // Common settings JS
        wp_enqueue_script(
            'wp-app-core-settings',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/settings.js',
            ['jquery'],
            WP_APP_CORE_VERSION,
            true
        );

        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

        // Load permissions-specific assets
        if ($current_tab === 'permissions') {
            wp_enqueue_style(
                'wp-app-core-permissions-tab',
                WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/permissions-tab-style.css',
                ['wp-app-core-settings'],
                WP_APP_CORE_VERSION
            );

            wp_enqueue_script(
                'wp-app-core-permissions-tab',
                WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/permissions-tab-script.js',
                ['jquery', 'wp-app-core-settings'],
                WP_APP_CORE_VERSION,
                true
            );
        }

        // Load demo-data specific assets
        if ($current_tab === 'demo-data') {
            wp_enqueue_style(
                'wp-app-core-demo-data-tab',
                WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/demo-data-tab-style.css',
                ['wp-app-core-settings'],
                WP_APP_CORE_VERSION
            );

            wp_enqueue_script(
                'wp-app-core-demo-data-tab',
                WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/platform-demo-data-tab-script.js',
                ['jquery', 'wp-app-core-settings'],
                WP_APP_CORE_VERSION,
                true
            );
        }

        // Load security-authentication specific assets
        if ($current_tab === 'security-authentication') {
            wp_enqueue_style(
                'wp-app-core-security-authentication-tab',
                WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/security-authentication-tab-style.css',
                ['wp-app-core-settings'],
                WP_APP_CORE_VERSION
            );

            wp_enqueue_script(
                'wp-app-core-security-authentication-tab',
                WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/security-authentication-tab-script.js',
                ['jquery', 'wp-app-core-settings'],
                WP_APP_CORE_VERSION,
                true
            );
        }

        // Load security-session specific assets
        if ($current_tab === 'security-session') {
            wp_enqueue_style(
                'wp-app-core-security-session-tab',
                WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/security-session-tab-style.css',
                ['wp-app-core-settings'],
                WP_APP_CORE_VERSION
            );

            wp_enqueue_script(
                'wp-app-core-security-session-tab',
                WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/security-session-tab-script.js',
                ['jquery', 'wp-app-core-settings'],
                WP_APP_CORE_VERSION,
                true
            );
        }

        // Load security-policy specific assets
        if ($current_tab === 'security-policy') {
            wp_enqueue_style(
                'wp-app-core-security-policy-tab',
                WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/security-policy-tab-style.css',
                ['wp-app-core-settings'],
                WP_APP_CORE_VERSION
            );

            wp_enqueue_script(
                'wp-app-core-security-policy-tab',
                WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/security-policy-tab-script.js',
                ['jquery', 'wp-app-core-settings'],
                WP_APP_CORE_VERSION,
                true
            );
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
     * Render settings page
     */
    public function renderPage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'wp-app-core'));
        }

        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

        // Make controller accessible in template
        $controller = $this;

        require_once WP_APP_CORE_PLUGIN_DIR . 'src/Views/templates/settings/settings-page.php';
    }

    /**
     * Load tab view
     */
    public function loadTabView($tab) {
        $allowed_tabs = [
            'general' => 'tab-general.php',
            'email' => 'tab-email.php',
            'permissions' => 'tab-permissions.php',
            'demo-data' => 'tab-demo-data.php',
            'security-authentication' => 'tab-security-authentication.php',
            'security-session' => 'tab-security-session.php',
            'security-policy' => 'tab-security-policy.php',
        ];

        $tab = isset($allowed_tabs[$tab]) ? $tab : 'general';

        // Prepare view data based on tab
        $view_data = $this->prepareViewData($tab);

        $tab_file = WP_APP_CORE_PLUGIN_DIR . 'src/Views/templates/settings/' . $allowed_tabs[$tab];

        if (file_exists($tab_file)) {
            if (!empty($view_data)) {
                extract($view_data);
            }
            require_once $tab_file;
        } else {
            echo sprintf(
                __('Tab file not found: %s', 'wp-app-core'),
                esc_html($tab_file)
            );
        }
    }

    /**
     * Prepare view data for tabs
     */
    private function prepareViewData($tab) {
        $data = [];

        switch ($tab) {
            case 'general':
                $data['settings'] = $this->platform_model->getSettings();
                break;

            case 'email':
                $data['settings'] = $this->email_model->getSettings();
                break;

            case 'permissions':
                $data['capability_groups'] = $this->permission_model->getCapabilityGroups();
                $data['role_matrix'] = $this->permission_model->getRoleCapabilitiesMatrix();
                $data['capability_descriptions'] = $this->permission_model->getCapabilityDescriptions();
                $data['permission_labels'] = $this->permission_model->getAllCapabilities();
                break;

            case 'security-authentication':
                $data['settings'] = $this->auth_model->getSettings();
                $data['roles'] = get_editable_roles();
                break;

            case 'security-session':
                $data['settings'] = $this->session_model->getSettings();
                break;

            case 'security-policy':
                $data['settings'] = $this->policy_model->getSettings();
                break;
        }

        return $data;
    }

    /**
     * Handle save platform settings
     */
    public function handleSavePlatformSettings() {
        check_ajax_referer('wp_app_core_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        $settings = $_POST['settings'] ?? [];

        if ($this->platform_model->saveSettings($settings)) {
            wp_send_json_success(['message' => __('Settings saved successfully', 'wp-app-core')]);
        } else {
            wp_send_json_error(['message' => __('Failed to save settings', 'wp-app-core')]);
        }
    }

    /**
     * Handle save email settings
     */
    public function handleSaveEmailSettings() {
        check_ajax_referer('wp_app_core_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        $settings = $_POST['settings'] ?? [];

        if ($this->email_model->saveSettings($settings)) {
            wp_send_json_success(['message' => __('Settings saved successfully', 'wp-app-core')]);
        } else {
            wp_send_json_error(['message' => __('Failed to save settings', 'wp-app-core')]);
        }
    }

    /**
     * Handle save security authentication
     */
    public function handleSaveSecurityAuthentication() {
        check_ajax_referer('wp_app_core_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        $settings = $_POST['settings'] ?? [];

        if ($this->auth_model->saveSettings($settings)) {
            wp_send_json_success(['message' => __('Settings saved successfully', 'wp-app-core')]);
        } else {
            wp_send_json_error(['message' => __('Failed to save settings', 'wp-app-core')]);
        }
    }

    /**
     * Handle save security session
     */
    public function handleSaveSecuritySession() {
        check_ajax_referer('wp_app_core_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        $settings = $_POST['settings'] ?? [];

        if ($this->session_model->saveSettings($settings)) {
            wp_send_json_success(['message' => __('Settings saved successfully', 'wp-app-core')]);
        } else {
            wp_send_json_error(['message' => __('Failed to save settings', 'wp-app-core')]);
        }
    }

    /**
     * Handle save security policy
     */
    public function handleSaveSecurityPolicy() {
        check_ajax_referer('wp_app_core_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        $settings = $_POST['settings'] ?? [];

        if ($this->policy_model->saveSettings($settings)) {
            wp_send_json_success(['message' => __('Settings saved successfully', 'wp-app-core')]);
        } else {
            wp_send_json_error(['message' => __('Failed to save settings', 'wp-app-core')]);
        }
    }

    /**
     * Handle save platform permissions
     */
    public function handleSavePlatformPermissions() {
        check_ajax_referer('wp_app_core_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        $role = sanitize_key($_POST['role'] ?? '');
        $capabilities = $_POST['capabilities'] ?? [];

        if (empty($role)) {
            wp_send_json_error(['message' => __('Invalid role', 'wp-app-core')]);
        }

        if ($this->permission_model->updateRoleCapabilities($role, $capabilities)) {
            wp_send_json_success(['message' => __('Permissions updated successfully', 'wp-app-core')]);
        } else {
            wp_send_json_error(['message' => __('Failed to update permissions', 'wp-app-core')]);
        }
    }

    /**
     * Handle reset platform permissions
     */
    public function handleResetPlatformPermissions() {
        check_ajax_referer('wp_app_core_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        if ($this->permission_model->resetToDefault()) {
            wp_send_json_success(['message' => __('Permissions reset to default', 'wp-app-core')]);
        } else {
            wp_send_json_error(['message' => __('Failed to reset permissions', 'wp-app-core')]);
        }
    }

    /**
     * Handle create platform roles
     */
    public function handleCreatePlatformRoles() {
        // Verify nonce with the same one used in the button
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'create_platform_roles')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-app-core')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        try {
            require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-role-manager.php';

            // Create roles
            \WP_App_Core_Role_Manager::createRoles();

            // Add capabilities
            $this->permission_model->addCapabilities();

            wp_send_json_success([
                'message' => __('Platform roles created successfully', 'wp-app-core')
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error creating roles: %s', 'wp-app-core'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle delete platform roles
     */
    public function handleDeletePlatformRoles() {
        // Verify nonce with the same one used in the button
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delete_platform_roles')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-app-core')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        try {
            require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-role-manager.php';

            $plugin_roles = \WP_App_Core_Role_Manager::getRoleSlugs();
            $deleted_count = 0;

            foreach ($plugin_roles as $role_slug) {
                if (\WP_App_Core_Role_Manager::roleExists($role_slug)) {
                    remove_role($role_slug);
                    $deleted_count++;
                }
            }

            wp_send_json_success([
                'message' => sprintf(
                    __('Successfully deleted %d platform roles', 'wp-app-core'),
                    $deleted_count
                )
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error deleting roles: %s', 'wp-app-core'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle reset platform capabilities
     */
    public function handleResetPlatformCapabilities() {
        // Verify nonce with the same one used in the button
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'reset_platform_capabilities')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-app-core')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        try {
            if ($this->permission_model->resetToDefault()) {
                wp_send_json_success([
                    'message' => __('Platform capabilities reset to default values successfully', 'wp-app-core')
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Failed to reset capabilities', 'wp-app-core')
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error resetting capabilities: %s', 'wp-app-core'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle generate platform staff
     */
    public function handleGeneratePlatformStaff() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'generate_platform_staff')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-app-core')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        try {
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/Data/PlatformUsersData.php';
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/Data/PlatformDemoData.php';
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/WPUserGenerator.php';

            $result = \WPAppCore\Database\Demo\Data\PlatformDemoData::generate();

            if ($result['success']) {
                wp_send_json_success([
                    'message' => $result['message'],
                    'users_created' => $result['users_created'],
                    'staff_records_created' => $result['staff_records_created'],
                    'errors' => $result['errors']
                ]);
            } else {
                wp_send_json_error([
                    'message' => $result['message'],
                    'errors' => $result['errors']
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error generating platform staff: %s', 'wp-app-core'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle delete platform staff
     */
    public function handleDeletePlatformStaff() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delete_platform_staff')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-app-core')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        try {
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/Data/PlatformUsersData.php';
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/Data/PlatformDemoData.php';
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/WPUserGenerator.php';

            $result = \WPAppCore\Database\Demo\Data\PlatformDemoData::deleteAll();

            if ($result['success']) {
                wp_send_json_success([
                    'message' => $result['message'],
                    'users_deleted' => $result['users_deleted'],
                    'staff_records_deleted' => $result['staff_records_deleted'],
                    'errors' => $result['errors']
                ]);
            } else {
                wp_send_json_error([
                    'message' => $result['message'],
                    'errors' => $result['errors']
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error deleting platform staff: %s', 'wp-app-core'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle platform staff statistics
     */
    public function handlePlatformStaffStats() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'platform_staff_stats')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-app-core')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        try {
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/Data/PlatformUsersData.php';
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/Data/PlatformDemoData.php';
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/WPUserGenerator.php';

            $stats = \WPAppCore\Database\Demo\Data\PlatformDemoData::getStatistics();

            wp_send_json_success([
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error getting statistics: %s', 'wp-app-core'), $e->getMessage())
            ]);
        }
    }

    /**
     * Sanitize development settings
     */
    public function sanitizeDevelopmentSettings($input) {
        $sanitized = [];

        $sanitized['enable_development'] = isset($input['enable_development']) ? 1 : 0;
        $sanitized['clear_data_on_deactivate'] = isset($input['clear_data_on_deactivate']) ? 1 : 0;

        return $sanitized;
    }

    /**
     * Handle reset security authentication settings to default
     */
    public function handleResetSecurityAuthentication() {
        check_ajax_referer('wp_app_core_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        try {
            // Delete the option - this will force getSettings() to return defaults
            delete_option('wp_app_core_security_authentication');
            wp_cache_delete('wp_app_core_security_authentication', 'wp_app_core');

            // Get default settings to return to client
            $defaults = $this->auth_model->getDefaultSettings();

            wp_send_json_success([
                'message' => __('Security authentication settings reset to default successfully.', 'wp-app-core'),
                'settings' => $defaults
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error resetting settings: %s', 'wp-app-core'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle reset security session settings to default
     */
    public function handleResetSecuritySession() {
        check_ajax_referer('wp_app_core_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        try {
            // Delete the option - this will force getSettings() to return defaults
            delete_option('wp_app_core_security_session');
            wp_cache_delete('wp_app_core_security_session', 'wp_app_core');

            // Get default settings to return to client
            $defaults = $this->session_model->getDefaultSettings();

            wp_send_json_success([
                'message' => __('Security session settings reset to default successfully.', 'wp-app-core'),
                'settings' => $defaults
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error resetting settings: %s', 'wp-app-core'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle reset security policy settings to default
     */
    public function handleResetSecurityPolicy() {
        check_ajax_referer('wp_app_core_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        try {
            // Delete the option - this will force getSettings() to return defaults
            delete_option('wp_app_core_security_policy');
            wp_cache_delete('wp_app_core_security_policy', 'wp_app_core');

            // Get default settings to return to client
            $defaults = $this->policy_model->getDefaultSettings();

            wp_send_json_success([
                'message' => __('Security policy settings reset to default successfully.', 'wp-app-core'),
                'settings' => $defaults
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error resetting settings: %s', 'wp-app-core'), $e->getMessage())
            ]);
        }
    }
}
