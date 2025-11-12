<?php
/**
 * Platform Email Settings Controller
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Settings
 * @version     3.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Settings/PlatformEmailSettingsController.php
 *
 * Description: Controller untuk email dan notification settings.
 *              REFACTORED: Now extends AbstractSettingsController.
 *              Extracted from monolithic PlatformSettingsController.
 *
 * Changelog:
 * 3.0.0 - 2025-11-12 (TODO-1205)
 * - BREAKING: Renamed from EmailSettingsController
 * - Implemented doSave() and doReset() abstract methods
 * - Removed registerAjaxHandlers() - no longer in parent
 * - Part of standardized settings architecture for 20 plugins
 * 2.0.0 - 2025-01-09 (TODO-1203)
 * - BREAKING: Extracted from PlatformSettingsController
 * - Now extends AbstractSettingsController
 * - Reduced from ~870 lines (shared) to ~100 lines
 * - Single responsibility: Email settings only
 * 1.0.0 - 2025-10-19
 * - Was part of PlatformSettingsController
 */

namespace WPAppCore\Controllers\Settings;

use WPAppCore\Controllers\Abstract\AbstractSettingsController;
use WPAppCore\Models\Abstract\AbstractSettingsModel;
use WPAppCore\Models\Settings\EmailSettingsModel;
use WPAppCore\Validators\Abstract\AbstractSettingsValidator;
use WPAppCore\Validators\Settings\EmailSettingsValidator;

class PlatformEmailSettingsController extends AbstractSettingsController {

    protected function getPluginSlug(): string {
        return 'wp-app-core';
    }

    protected function getPluginPrefix(): string {
        return 'wpapp';
    }

    protected function getSettingsPageSlug(): string {
        return 'wp-app-core-settings';
    }

    protected function getSettingsCapability(): string {
        return 'manage_options';
    }

    protected function getDefaultTabs(): array {
        // No tabs - this controller handles single tab content
        // Tab navigation is handled by parent PlatformSettingsPageController
        return [];
    }

    protected function getModel(): AbstractSettingsModel {
        return new EmailSettingsModel();
    }

    protected function getValidator(): AbstractSettingsValidator {
        return new EmailSettingsValidator();
    }

    protected function getControllerSlug(): string {
        return 'email';
    }

    /**
     * Register notification messages and custom AJAX handlers
     *
     * ABSTRACT PATTERN: Each controller registers their own messages
     * This is called during init() to register with the page controller
     */
    public function init(): void {
        parent::init();

        // Register notification messages
        add_filter('wpapp_settings_notification_messages', [$this, 'registerNotificationMessages']);

        // Register custom AJAX handlers for email-specific features
        add_action('wp_ajax_wpapp_test_smtp_connection', [$this, 'handleTestSMTPConnection']);
        add_action('wp_ajax_wpapp_send_test_email', [$this, 'handleSendTestEmail']);
    }

    /**
     * Register notification messages for this controller
     *
     * @param array $messages Existing messages from other controllers
     * @return array Modified messages with this controller's messages added
     */
    public function registerNotificationMessages(array $messages): array {
        // Save message
        $messages['save_messages']['email'] = __('Email settings have been saved successfully.', 'wp-app-core');

        // Reset message
        $messages['reset_messages']['email'] = __('Email settings have been reset to default values successfully.', 'wp-app-core');

        return $messages;
    }

    /**
     * Save settings (implementation of abstract method)
     * Called by central dispatcher via hook
     *
     * @param array $data POST data
     * @return bool True if saved successfully
     */
    protected function doSave(array $data): bool {
        // Extract settings from POST data
        $settings = $data['platform_email_settings'] ?? [];

        // Save via model
        return $this->model->saveSettings($settings);
    }

    /**
     * Reset settings to defaults (implementation of abstract method)
     * Called by central dispatcher via hook
     *
     * @return array Default settings
     */
    protected function doReset(): array {
        return $this->model->getDefaults();
    }

    /**
     * Test SMTP connection
     */
    public function handleTestSMTPConnection(): void {
        check_ajax_referer($this->getPluginPrefix() . '_nonce', 'nonce');

        if (!current_user_can($this->getSettingsCapability())) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        $result = $this->getModel()->testSMTPConnection();

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Send test email
     */
    public function handleSendTestEmail(): void {
        check_ajax_referer($this->getPluginPrefix() . '_nonce', 'nonce');

        if (!current_user_can($this->getSettingsCapability())) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        $to_email = sanitize_email($_POST['to_email'] ?? '');

        if (!is_email($to_email)) {
            wp_send_json_error(['message' => __('Invalid email address', 'wp-app-core')]);
        }

        $subject = __('Test Email from Platform', 'wp-app-core');
        $message = __('This is a test email sent from the platform settings.', 'wp-app-core');

        $sent = wp_mail($to_email, $subject, $message);

        if ($sent) {
            wp_send_json_success(['message' => __('Test email sent successfully', 'wp-app-core')]);
        } else {
            wp_send_json_error(['message' => __('Failed to send test email', 'wp-app-core')]);
        }
    }
}
