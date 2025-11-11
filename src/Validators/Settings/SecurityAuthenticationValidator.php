<?php
/**
 * Security Authentication Validator
 *
 * @package     WP_App_Core
 * @subpackage  Validators
 * @version     1.0.0
 * @author      arisciwek
 * Path: /wp-app-core/src/Validators/Settings/SecurityAuthenticationValidator.php
 * Description: Validator for authentication & access control settings.
 *              Extends AbstractSettingsValidator with security-specific rules.
 * Changelog:
 * 1.0.0 - 2025-01-09 (TODO-1203)
 * - Initial creation
 * - Validation rules for password policies
 * - Validation rules for 2FA settings
 * - Validation rules for access control
 */

namespace WPAppCore\Validators\Settings;
use WPAppCore\Validators\Abstract\AbstractSettingsValidator;
defined('ABSPATH') || exit;
class SecurityAuthenticationValidator extends AbstractSettingsValidator {
    protected function getTextDomain(): string {
        return 'wp-app-core';
    }
    protected function getRules(): array {
        return [
            // Password Policy
            'password_min_length' => ['numeric', 'min:8', 'max:128'],
            'password_expiration_days' => ['numeric', 'min:0'],
            'password_history_count' => ['numeric', 'min:0', 'max:24'],
            // Two-Factor Authentication
            'twofa_grace_period_days' => ['numeric', 'min:0'],
            // Access Control
            'admin_access_hours_start' => ['max:10'],
            'admin_access_hours_end' => ['max:10'],
        ];
    protected function getMessages(): array {
            'password_min_length.min' => __('Password minimum length must be at least 8 characters.', 'wp-app-core'),
            'password_min_length.max' => __('Password minimum length cannot exceed 128 characters.', 'wp-app-core'),
            'password_history_count.max' => __('Password history count cannot exceed 24.', 'wp-app-core'),
    // ✅ validate($data) - inherited from AbstractSettingsValidator
    // ✅ getErrors() - inherited from AbstractSettingsValidator
    // ✅ hasError($field) - inherited from AbstractSettingsValidator
    // ✅ getFirstError($field) - inherited from AbstractSettingsValidator
}
