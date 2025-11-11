<?php
/**
 * Email Settings Validator
 *
 * @package     WP_App_Core
 * @subpackage  Validators
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Validators/Settings/EmailSettingsValidator.php
 *
 * Description: Validator for email and notification settings.
 *              Extends AbstractSettingsValidator with email-specific rules.
 *
 * Changelog:
 * 1.0.0 - 2025-01-09 (TODO-1203)
 * - Initial creation
 * - Validation rules for SMTP configuration
 * - Validation rules for email templates
 * - Validation rules for notification preferences
 */

namespace WPAppCore\Validators\Settings;

use WPAppCore\Validators\Abstract\AbstractSettingsValidator;

defined('ABSPATH') || exit;

class EmailSettingsValidator extends AbstractSettingsValidator {

    protected function getTextDomain(): string {
        return 'wp-app-core';
    }

    protected function getRules(): array {
        return [
            // SMTP Configuration
            'smtp_host' => ['max:255'],
            'smtp_port' => ['numeric', 'min:1', 'max:65535'],
            'smtp_encryption' => ['in:tls,ssl,none'],
            'smtp_username' => ['max:255'],
            'smtp_from_email' => ['email'],
            'smtp_from_name' => ['max:255'],

            // Email Templates
            'welcome_email_subject' => ['max:500'],
            'notification_from_email' => ['email'],
            'notification_from_name' => ['max:255'],

            // Notification Preferences
            'digest_frequency' => ['in:daily,weekly'],
            'digest_time' => ['max:10'],
        ];
    }

    protected function getMessages(): array {
        return [
            'smtp_from_email.email' => __('Please enter a valid SMTP from email address.', 'wp-app-core'),
            'notification_from_email.email' => __('Please enter a valid notification email address.', 'wp-app-core'),
            'smtp_port.numeric' => __('SMTP port must be a number.', 'wp-app-core'),
            'smtp_port.min' => __('SMTP port must be at least 1.', 'wp-app-core'),
            'smtp_port.max' => __('SMTP port must not exceed 65535.', 'wp-app-core'),
            'smtp_encryption.in' => __('SMTP encryption must be one of: tls, ssl, none.', 'wp-app-core'),
            'digest_frequency.in' => __('Digest frequency must be daily or weekly.', 'wp-app-core'),
        ];
    }

    // ✅ validate($data) - inherited from AbstractSettingsValidator
    // ✅ getErrors() - inherited from AbstractSettingsValidator
    // ✅ hasError($field) - inherited from AbstractSettingsValidator
    // ✅ getFirstError($field) - inherited from AbstractSettingsValidator
}
