<?php
/**
 * Security Session Validator
 *
 * @package     WP_App_Core
 * @subpackage  Validators
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Validators/Settings/SecuritySessionValidator.php
 *
 * Description: Validator for session & login management settings.
 *              Extends AbstractSettingsValidator with session-specific rules.
 *
 * Changelog:
 * 1.0.0 - 2025-01-09 (TODO-1203)
 * - Initial creation
 * - Validation rules for session settings
 * - Validation rules for login protection
 * - Validation rules for login monitoring
 */

namespace WPAppCore\Validators\Settings;

use WPAppCore\Validators\Abstract\AbstractSettingsValidator;

defined('ABSPATH') || exit;

class SecuritySessionValidator extends AbstractSettingsValidator {

    protected function getTextDomain(): string {
        return 'wp-app-core';
    }

    protected function getRules(): array {
        return [
            // Session Settings
            'session_idle_timeout' => ['numeric', 'min:300'],  // Min 5 minutes
            'session_absolute_timeout' => ['numeric', 'min:600'],  // Min 10 minutes
            'concurrent_sessions_limit' => ['numeric', 'min:1', 'max:10'],
            'remember_me_duration' => ['numeric', 'min:0'],

            // Login Protection
            'max_login_attempts' => ['numeric', 'min:3', 'max:10'],
            'lockout_duration' => ['numeric', 'min:300'],  // Min 5 minutes
            'captcha_after_failed_attempts' => ['numeric', 'min:1'],

            // Login Monitoring
            'login_history_limit' => ['numeric', 'min:10', 'max:1000'],
        ];
    }

    protected function getMessages(): array {
        return [
            'session_idle_timeout.min' => __('Session idle timeout must be at least 5 minutes (300 seconds).', 'wp-app-core'),
            'session_absolute_timeout.min' => __('Session absolute timeout must be at least 10 minutes (600 seconds).', 'wp-app-core'),
            'concurrent_sessions_limit.min' => __('Concurrent sessions limit must be at least 1.', 'wp-app-core'),
            'concurrent_sessions_limit.max' => __('Concurrent sessions limit cannot exceed 10.', 'wp-app-core'),
            'max_login_attempts.min' => __('Maximum login attempts must be at least 3.', 'wp-app-core'),
            'max_login_attempts.max' => __('Maximum login attempts cannot exceed 10.', 'wp-app-core'),
            'lockout_duration.min' => __('Lockout duration must be at least 5 minutes (300 seconds).', 'wp-app-core'),
            'login_history_limit.min' => __('Login history limit must be at least 10.', 'wp-app-core'),
            'login_history_limit.max' => __('Login history limit cannot exceed 1000.', 'wp-app-core'),
        ];
    }

    // ✅ validate($data) - inherited from AbstractSettingsValidator
    // ✅ getErrors() - inherited from AbstractSettingsValidator
    // ✅ hasError($field) - inherited from AbstractSettingsValidator
    // ✅ getFirstError($field) - inherited from AbstractSettingsValidator
}
