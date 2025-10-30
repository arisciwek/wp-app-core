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
        // Asset registration moved to class-dependencies.php
    }

    /**
     * Register settings
     */
    public function registerSettings() {
        // Add redirect after settings saved to prevent form resubmission
        add_filter('wp_redirect', [$this, 'addSettingsSavedMessage'], 10, 2);

        // Clear cache after settings updated
        add_action('update_option_wp_app_core_platform_settings', [$this, 'clearPlatformSettingsCache']);
        add_action('update_option_wp_app_core_email_settings', [$this, 'clearEmailSettingsCache']);

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

        // General & Email Settings Reset AJAX handlers
        add_action('wp_ajax_reset_general_settings', [$this, 'handleResetGeneralSettings']);
        add_action('wp_ajax_reset_email_settings', [$this, 'handleResetEmailSettings']);

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
        // CRITICAL: Register shutdown function to catch fatal errors
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                error_log('=== FATAL ERROR DETECTED IN RESET PERMISSIONS ===');
                error_log('Type: ' . $error['type']);
                error_log('Message: ' . $error['message']);
                error_log('File: ' . $error['file']);
                error_log('Line: ' . $error['line']);
                error_log('=== END FATAL ERROR ===');
            }
        });

        // CRITICAL: Start output buffering to prevent contamination from plugin hooks
        // resetToDefault() triggers WordPress hooks that may init other plugins
        // which can output to buffer and cause 500 errors
        ob_start();

        error_log('=== WP-APP-CORE RESET PERMISSIONS START ===');
        error_log('Received nonce: ' . ($_POST['nonce'] ?? 'NOT SET'));
        error_log('Current user ID: ' . get_current_user_id());
        error_log('User can manage_options: ' . (current_user_can('manage_options') ? 'YES' : 'NO'));

        try {
            check_ajax_referer('wp_app_core_settings_nonce', 'nonce');
            error_log('Nonce verified successfully');

            if (!current_user_can('manage_options')) {
                error_log('ERROR: User does not have manage_options capability');
                error_log('=== WP-APP-CORE RESET PERMISSIONS END (Permission Denied) ===');
                ob_end_clean(); // Clean buffer before sending JSON
                wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
                die(); // Ensure no code runs after wp_send_json
            }

            error_log('Calling resetToDefault()...');
            $result = $this->permission_model->resetToDefault();
            error_log('resetToDefault() returned: ' . ($result ? 'TRUE' : 'FALSE'));

            // CRITICAL: Clean output buffer before sending JSON response
            // This removes any output from plugin hooks triggered during reset
            ob_end_clean();

            if ($result) {
                $success_message = __('Permissions reset to default', 'wp-app-core');
                error_log('SUCCESS: Sending success response');
                error_log('SUCCESS: Message being sent: ' . $success_message);
                error_log('=== WP-APP-CORE RESET PERMISSIONS END (Success) ===');
                wp_send_json_success(['message' => $success_message]);
                die(); // Ensure no code runs after wp_send_json
            } else {
                error_log('ERROR: resetToDefault() returned false');
                error_log('=== WP-APP-CORE RESET PERMISSIONS END (Failed) ===');
                wp_send_json_error(['message' => __('Failed to reset permissions', 'wp-app-core')]);
                die(); // Ensure no code runs after wp_send_json
            }
        } catch (\Exception $e) {
            error_log('EXCEPTION: ' . $e->getMessage());
            error_log('Exception trace: ' . $e->getTraceAsString());
            error_log('=== WP-APP-CORE RESET PERMISSIONS END (Exception) ===');
            ob_end_clean(); // Clean buffer before sending JSON
            wp_send_json_error(['message' => $e->getMessage()]);
            die(); // Ensure no code runs after wp_send_json
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
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/PlatformDemoData.php';
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/WPUserGenerator.php';

            $result = \WPAppCore\Database\Demo\PlatformDemoData::generate();

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
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/PlatformDemoData.php';
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/WPUserGenerator.php';

            $result = \WPAppCore\Database\Demo\PlatformDemoData::deleteAll();

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
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/PlatformDemoData.php';
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Database/Demo/WPUserGenerator.php';

            $stats = \WPAppCore\Database\Demo\PlatformDemoData::getStatistics();

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

    /**
     * Add settings saved message to redirect URL
     * Prevents form resubmission issue on general and email tabs
     *
     * @param string $location Redirect location
     * @param int $status HTTP status code
     * @return string Modified redirect location
     */
    public function addSettingsSavedMessage($location, $status) {
        // Only handle redirects from options.php for our settings
        if (strpos($location, 'page=wp-app-core-settings') === false) {
            return $location;
        }

        // Check if this is a settings save redirect
        if (isset($_POST['option_page'])) {
            $option_page = $_POST['option_page'];

            // Only for our settings pages
            $our_settings = [
                'wp_app_core_platform_settings',
                'wp_app_core_email_settings',
                'wp_app_core_development_settings'
            ];

            if (in_array($option_page, $our_settings)) {
                // Add settings-updated parameter if not already present
                if (strpos($location, 'settings-updated=true') === false) {
                    $location = add_query_arg('settings-updated', 'true', $location);
                }
            }
        }

        return $location;
    }

    /**
     * Handle reset general settings to default
     */
    public function handleResetGeneralSettings() {
        check_ajax_referer('wp_app_core_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        try {
            // Delete the option - this will force getSettings() to return defaults
            delete_option('wp_app_core_platform_settings');
            wp_cache_delete('wp_app_core_platform_settings', 'wp_app_core');

            // Get default settings to return to client
            $defaults = $this->platform_model->getDefaultSettings();

            wp_send_json_success([
                'message' => __('General settings reset to default successfully.', 'wp-app-core'),
                'settings' => $defaults
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error resetting settings: %s', 'wp-app-core'), $e->getMessage())
            ]);
        }
    }

    /**
     * Handle reset email settings to default
     */
    public function handleResetEmailSettings() {
        check_ajax_referer('wp_app_core_settings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        try {
            // Delete the option - this will force getSettings() to return defaults
            delete_option('wp_app_core_email_settings');
            wp_cache_delete('wp_app_core_email_settings', 'wp_app_core');

            // Get default settings to return to client
            $defaults = $this->email_model->getDefaultSettings();

            wp_send_json_success([
                'message' => __('Email settings reset to default successfully.', 'wp-app-core'),
                'settings' => $defaults
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(__('Error resetting settings: %s', 'wp-app-core'), $e->getMessage())
            ]);
        }
    }

    /**
     * Clear platform settings cache after update
     */
    public function clearPlatformSettingsCache() {
        wp_cache_delete('wp_app_core_platform_settings', 'wp_app_core');
        // Also clear any object cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }

    /**
     * Clear email settings cache after update
     */
    public function clearEmailSettingsCache() {
        wp_cache_delete('wp_app_core_email_settings', 'wp_app_core');
        // Also clear any object cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
}
