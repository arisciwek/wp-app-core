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
use WPAppCore\Controllers\Settings\PlatformEmailSettingsController;
use WPAppCore\Controllers\Settings\PlatformSecurityAuthenticationController;
use WPAppCore\Controllers\Settings\PlatformSecuritySessionController;
use WPAppCore\Controllers\Settings\PlatformSecurityPolicyController;
use WPAppCore\Controllers\Settings\PlatformPermissionsController;
use WPAppCore\Controllers\Settings\PlatformDemoDataController;

class PlatformSettingsPageController {

    private array $controllers = [];

    public function __construct() {
        // Initialize specialized controllers
        $this->controllers = [
            'general' => new PlatformGeneralSettingsController(),
            'email' => new PlatformEmailSettingsController(),
            'security-authentication' => new PlatformSecurityAuthenticationController(),
            'security-session' => new PlatformSecuritySessionController(),
            'security-policy' => new PlatformSecurityPolicyController(),
            'permissions' => new PlatformPermissionsController(),
            'demo-data' => new PlatformDemoDataController(),
        ];
    }

    /**
     * Initialize controller
     */
    public function init(): void {
        // Initialize all specialized controllers FIRST
        // This registers their hooks (wpapp_save_*, wpapp_reset_*)
        foreach ($this->controllers as $tab => $controller) {
            $controller->init();
        }

        // CRITICAL: Central dispatcher - handle save/reset BEFORE WordPress processes form
        add_action('admin_init', [$this, 'handleFormSubmission'], 1); // Priority 1 - very early

        // Register settings
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Central dispatcher for save & reset
     * Priority 1 - runs BEFORE WordPress Settings API processes form
     */
    public function handleFormSubmission(): void {
        // Only handle POST requests with option_page
        if (empty($_POST) || !isset($_POST['option_page'])) {
            return;
        }

        $option_page = $_POST['option_page'] ?? '';

        // Only handle our settings
        $our_settings = [
            'platform_settings',
            'platform_email_settings',
            'platform_security_authentication',
            'platform_security_session',
            'platform_security_policy'
        ];

        if (!in_array($option_page, $our_settings)) {
            return;
        }

        // Verify nonce
        check_admin_referer($option_page . '-options');

        // DISPATCH: Reset request?
        if (isset($_POST['reset_to_defaults']) && $_POST['reset_to_defaults'] === '1') {
            $this->dispatchReset($option_page);
            return; // exit handled in dispatchReset
        }

        // DISPATCH: Save request
        $this->dispatchSave($option_page);
        // Let WordPress continue to handle redirect
    }

    /**
     * Dispatch reset request via hook
     *
     * @param string $option_page Option page being reset
     */
    private function dispatchReset(string $option_page): void {
        // Trigger hook - controller yang match option_page akan respond
        $defaults = apply_filters("wpapp_reset_{$option_page}", [], $option_page);

        if (empty($defaults)) {
            // No controller handled this
            wp_die(__('Invalid reset request - no controller responded.', 'wp-app-core'));
        }

        // Update option with defaults
        update_option($option_page, $defaults);

        // Build redirect URL
        $current_tab = $_POST['current_tab'] ?? '';
        $redirect_url = add_query_arg([
            'page' => 'wp-app-core-settings',
            'tab' => $current_tab,
            'reset' => 'success',
            'reset_tab' => $current_tab
        ], admin_url('admin.php'));

        // CRITICAL: Redirect and exit to prevent WordPress from processing form
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Dispatch save request via hook
     *
     * @param string $option_page Option page being saved
     */
    private function dispatchSave(string $option_page): void {
        // Trigger hook - controller yang match option_page akan respond
        $saved = apply_filters("wpapp_save_{$option_page}", false, $_POST);

        if (!$saved) {
            // Validation failed or no controller handled this
            add_settings_error(
                $option_page,
                'save_failed',
                __('Failed to save settings. Please check your input.', 'wp-app-core')
            );
        }

        // Let WordPress continue to process form and redirect
        // This allows WordPress Settings API to handle redirect with settings-updated parameter
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
     * Get notification messages from all controllers via hook
     *
     * Hook pattern: Each controller registers their messages
     * This creates an abstraction layer for tab notifications
     *
     * @return array ['save_messages' => [...], 'reset_messages' => [...]]
     */
    public function getNotificationMessages(): array {
        $messages = [
            'save_messages' => [],
            'reset_messages' => []
        ];

        /**
         * Hook: wpapp_settings_notification_messages
         *
         * Allows each controller to register their notification messages
         *
         * @param array $messages Array with 'save_messages' and 'reset_messages'
         * @return array Modified messages array
         *
         * @example
         * add_filter('wpapp_settings_notification_messages', function($messages) {
         *     $messages['save_messages']['email'] = __('Email settings saved', 'wp-app-core');
         *     $messages['reset_messages']['email'] = __('Email settings reset', 'wp-app-core');
         *     return $messages;
         * });
         */
        $messages = apply_filters('wpapp_settings_notification_messages', $messages);

        return $messages;
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
                // Permissions tab now handles its own data via AbstractPermissionsController
                // tab-permissions.php instantiates controller and calls getViewModel()
                // No data preparation needed here
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

        // SKIP if this is a RESET request (not a save request)
        if (isset($_POST['reset_to_defaults']) && $_POST['reset_to_defaults'] === '1') {
            // Reset is handled by dispatchReset() - already redirected with reset=success
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
                // IMPORTANT: Remove reset parameters first (prevent duplicate notifications)
                $location = remove_query_arg(['reset', 'reset_tab', 'message'], $location);

                // Add settings-updated parameter if not already present
                if (strpos($location, 'settings-updated=true') === false) {
                    $location = add_query_arg('settings-updated', 'true', $location);
                }

                // Add saved_tab parameter to show tab-specific success message
                if (isset($_POST['saved_tab'])) {
                    $saved_tab = sanitize_key($_POST['saved_tab']);
                    $location = add_query_arg('saved_tab', $saved_tab, $location);
                }
            }
        }

        return $location;
    }
}
