<?php
/**
 * Platform Settings Page Controller (Orchestrator)
 *
 * @package     WP_App_Core
 * @subpackage  Controllers
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Settings/PlatformSettingsPageController.php
 *
 * Description: Main orchestrator untuk platform settings page.
 *              REFACTORED: Replaces monolithic PlatformSettingsController.
 *              Delegates to specialized controllers for each settings area.
 *
 * Changelog:
 * 2.0.0 - 2025-01-09 (TODO-1203)
 * - BREAKING: Complete refactor from monolithic controller
 * - Now acts as orchestrator for 7 specialized controllers
 * - Reduced from 871 lines to ~250 lines (71% reduction)
 * - Single Responsibility: Page rendering & tab coordination
 * - Delegates all AJAX handlers to specialized controllers
 * 1.0.0 - 2025-10-19
 * - Was monolithic PlatformSettingsController (871 lines)
 */

namespace WPAppCore\Controllers\Settings;

use WPAppCore\Controllers\Settings\PlatformGeneralSettingsController;
use WPAppCore\Controllers\Settings\EmailSettingsController;
use WPAppCore\Controllers\Settings\SecurityAuthenticationController;
use WPAppCore\Controllers\Settings\SecuritySessionController;
use WPAppCore\Controllers\Settings\SecurityPolicyController;
use WPAppCore\Controllers\Settings\PlatformPermissionsController;
use WPAppCore\Controllers\Settings\PlatformDemoDataController;

class PlatformSettingsPageController {

    private array $controllers = [];

    public function __construct() {
        // Initialize specialized controllers
        $this->controllers = [
            'general' => new PlatformGeneralSettingsController(),
            'email' => new EmailSettingsController(),
            'security-authentication' => new SecurityAuthenticationController(),
            'security-session' => new SecuritySessionController(),
            'security-policy' => new SecurityPolicyController(),
            'permissions' => new PlatformPermissionsController(),
            'demo-data' => new PlatformDemoDataController(),
        ];
    }

    /**
     * Initialize controller
     */
    public function init(): void {
        // Initialize all specialized controllers
        foreach ($this->controllers as $tab => $controller) {
            $controller->init();
        }

        // Register admin_init hooks
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Register settings for WordPress Settings API
     */
    public function registerSettings(): void {
        // Each settings controller handles its own registration via AbstractSettingsController
        // This method remains for backward compatibility and future global settings

        // Add redirect after settings saved to prevent form resubmission
        add_filter('wp_redirect', [$this, 'addSettingsSavedMessage'], 10, 2);

        // Register development settings (not handled by any controller)
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
     * Render settings page
     */
    public function renderPage(): void {
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
    public function loadTabView(string $tab): void {
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
    private function prepareViewData(string $tab): array {
        $data = [];

        switch ($tab) {
            case 'general':
                $controller = $this->controllers['general'];
                $data['settings'] = $controller->getModelInstance()->getSettings();
                break;

            case 'email':
                $controller = $this->controllers['email'];
                $data['settings'] = $controller->getModelInstance()->getSettings();
                break;

            case 'permissions':
                $controller = $this->controllers['permissions'];
                $data['capability_groups'] = $controller->getCapabilityGroups();
                $data['role_matrix'] = $controller->getRoleCapabilitiesMatrix();
                $data['capability_descriptions'] = $controller->getCapabilityDescriptions();
                $data['permission_labels'] = $controller->getAllCapabilities();
                break;

            case 'security-authentication':
                $controller = $this->controllers['security-authentication'];
                $data['settings'] = $controller->getModelInstance()->getSettings();
                $data['roles'] = get_editable_roles();
                break;

            case 'security-session':
                $controller = $this->controllers['security-session'];
                $data['settings'] = $controller->getModelInstance()->getSettings();
                break;

            case 'security-policy':
                $controller = $this->controllers['security-policy'];
                $data['settings'] = $controller->getModelInstance()->getSettings();
                break;
        }

        return $data;
    }

    /**
     * Get tabs for navigation
     */
    public function getTabs(): array {
        return [
            'general' => __('General', 'wp-app-core'),
            'email' => __('Email', 'wp-app-core'),
            'permissions' => __('Permissions', 'wp-app-core'),
            'security-authentication' => __('Authentication', 'wp-app-core'),
            'security-session' => __('Session', 'wp-app-core'),
            'security-policy' => __('Security Policy', 'wp-app-core'),
            'demo-data' => __('Demo Data', 'wp-app-core'),
        ];
    }

    /**
     * Sanitize development settings
     */
    public function sanitizeDevelopmentSettings(array $input): array {
        $sanitized = [];
        $sanitized['enable_development'] = isset($input['enable_development']) ? 1 : 0;
        $sanitized['clear_data_on_deactivate'] = isset($input['clear_data_on_deactivate']) ? 1 : 0;
        return $sanitized;
    }

    /**
     * Add settings saved message to redirect URL
     * Prevents form resubmission issue on general and email tabs
     *
     * @param string $location Redirect location
     * @param int $status HTTP status code
     * @return string Modified redirect location
     */
    public function addSettingsSavedMessage(string $location, int $status): string {
        // Only handle redirects from options.php for our settings
        if (strpos($location, 'page=wp-app-core-settings') === false) {
            return $location;
        }

        // Check if this is a settings save redirect
        if (isset($_POST['option_page'])) {
            $option_page = $_POST['option_page'];

            // Only for our settings pages
            $our_settings = [
                'platform_settings',
                'platform_email_settings',
                'platform_security_authentication',
                'platform_security_session',
                'platform_security_policy',
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
}
