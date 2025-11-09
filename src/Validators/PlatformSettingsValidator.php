<?php
/**
 * Platform Settings Validator
 *
 * @package     WP_App_Core
 * @subpackage  Validators
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Validators/PlatformSettingsValidator.php
 *
 * Description: Validator for platform settings.
 *              Extends AbstractSettingsValidator with platform-specific rules.
 *
 * Changelog:
 * 1.0.0 - 2025-01-09 (TODO-1203)
 * - Initial creation
 * - Validation rules for company information
 * - Validation rules for contact information
 * - Validation rules for regional settings
 */

namespace WPAppCore\Validators;

defined('ABSPATH') || exit;

class PlatformSettingsValidator extends AbstractSettingsValidator {

    protected function getTextDomain(): string {
        return 'wp-app-core';
    }

    protected function getRules(): array {
        return [
            // Company Information
            'company_name' => ['max:255'],
            'company_tagline' => ['max:500'],
            'company_city' => ['max:100'],
            'company_postal_code' => ['max:20'],
            'company_country' => ['max:100'],

            // Contact Information
            'company_phone' => ['max:50'],
            'company_email' => ['email'],
            'company_website' => ['url'],
            'support_email' => ['email'],
            'support_phone' => ['max:50'],

            // Branding
            'company_logo_id' => ['numeric', 'min:0'],
            'company_favicon_id' => ['numeric', 'min:0'],
            'company_logo_url' => ['url'],
            'company_favicon_url' => ['url'],

            // Regional Settings
            'timezone' => ['max:50'],
            'date_format' => ['max:20'],
            'time_format' => ['max:20'],
            'first_day_of_week' => ['numeric', 'min:0', 'max:6'],
            'default_language' => ['max:10'],

            // Platform Settings
            'platform_name' => ['max:255'],
            'platform_version' => ['max:20'],
            'maintenance_mode' => ['boolean'],
        ];
    }

    protected function getMessages(): array {
        return [
            'company_email.email' => __('Please enter a valid company email address.', 'wp-app-core'),
            'support_email.email' => __('Please enter a valid support email address.', 'wp-app-core'),
            'company_website.url' => __('Please enter a valid website URL.', 'wp-app-core'),
            'first_day_of_week.max' => __('First day of week must be between 0 (Sunday) and 6 (Saturday).', 'wp-app-core'),
        ];
    }

    // ✅ validate($data) - inherited from AbstractSettingsValidator
    // ✅ getErrors() - inherited from AbstractSettingsValidator
    // ✅ hasError($field) - inherited from AbstractSettingsValidator
    // ✅ getFirstError($field) - inherited from AbstractSettingsValidator
}
