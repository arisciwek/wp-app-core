<?php
/**
 * Email Settings Controller
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Settings
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Settings/EmailSettingsController.php
 *
 * Description: Controller untuk email dan notification settings.
 *              REFACTORED: Now extends AbstractSettingsController.
 *              Extracted from monolithic PlatformSettingsController.
 *
 * Changelog:
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

class EmailSettingsController extends AbstractSettingsController {

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
     * Register custom AJAX handlers for email-specific features
     */
    protected function registerAjaxHandlers(): void {
        parent::registerAjaxHandlers();

        // Custom email AJAX handlers
        add_action('wp_ajax_wpapp_test_smtp_connection', [$this, 'handleTestSMTPConnection']);
        add_action('wp_ajax_wpapp_send_test_email', [$this, 'handleSendTestEmail']);
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
