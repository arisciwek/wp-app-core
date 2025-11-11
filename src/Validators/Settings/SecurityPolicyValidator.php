<?php
/**
 * Security Policy Validator
 *
 * @package     WP_App_Core
 * @subpackage  Validators
 * @version     1.0.0
 * @author      arisciwek
 * Path: /wp-app-core/src/Validators/Settings/SecurityPolicyValidator.php
 * Description: Validator for security policies & audit settings.
 *              Extends AbstractSettingsValidator with policy-specific rules.
 * Changelog:
 * 1.0.0 - 2025-01-09 (TODO-1203)
 * - Initial creation
 * - Validation rules for data security
 * - Validation rules for activity logging
 * - Validation rules for audit & compliance
 */

namespace WPAppCore\Validators\Settings;
use WPAppCore\Validators\Abstract\AbstractSettingsValidator;
defined('ABSPATH') || exit;
class SecurityPolicyValidator extends AbstractSettingsValidator {
    protected function getTextDomain(): string {
        return 'wp-app-core';
    }
    protected function getRules(): array {
        return [
            // Data Security
            'cookie_samesite' => ['in:Strict,Lax,None'],
            'max_upload_size' => ['numeric', 'min:1024'],  // Min 1KB
            // Activity Logging
            'log_retention_days' => ['numeric', 'min:7', 'max:365'],
            // Audit & Compliance
            'compliance_mode' => ['in:none,gdpr,ccpa'],
            // Advanced Security
            'x_frame_options' => ['in:DENY,SAMEORIGIN'],
        ];
    protected function getMessages(): array {
            'cookie_samesite.in' => __('Cookie SameSite must be one of: Strict, Lax, None.', 'wp-app-core'),
            'max_upload_size.min' => __('Maximum upload size must be at least 1KB (1024 bytes).', 'wp-app-core'),
            'log_retention_days.min' => __('Log retention days must be at least 7 days.', 'wp-app-core'),
            'log_retention_days.max' => __('Log retention days cannot exceed 365 days.', 'wp-app-core'),
            'compliance_mode.in' => __('Compliance mode must be one of: none, gdpr, ccpa.', 'wp-app-core'),
            'x_frame_options.in' => __('X-Frame-Options must be DENY or SAMEORIGIN.', 'wp-app-core'),
    // ✅ validate($data) - inherited from AbstractSettingsValidator
    // ✅ getErrors() - inherited from AbstractSettingsValidator
    // ✅ hasError($field) - inherited from AbstractSettingsValidator
    // ✅ getFirstError($field) - inherited from AbstractSettingsValidator
}
