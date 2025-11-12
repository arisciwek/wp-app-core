<?php
/**
 * Security Policy Model
 *
 * @package     WP_App_Core
 * @subpackage  Models/Settings
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Models/Settings/SecurityPolicyModel.php
 *
 * Description: Model untuk Security Policies & Audit settings.
 *              REFACTORED: Now extends AbstractSettingsModel.
 *
 * Changelog:
 * 2.0.0 - 2025-01-09 (TODO-1203)
 * - BREAKING: Now extends AbstractSettingsModel
 * - Uses PlatformCacheManager via AbstractCacheManager
 * - ~110 lines eliminated
 * 1.0.0 - 2025-10-19
 * - Initial release
 */

namespace WPAppCore\Models\Settings;

use WPAppCore\Models\Abstract\AbstractSettingsModel;
use WPAppCore\Cache\Abstract\AbstractCacheManager;
use WPAppCore\Cache\PlatformCacheManager;

class SecurityPolicyModel extends AbstractSettingsModel {

    private PlatformCacheManager $cacheManager;

    public function __construct() {
        $this->cacheManager = new PlatformCacheManager();
        parent::__construct(); // Register cache invalidation hooks
    }

    protected function getOptionName(): string {
        return 'platform_security_policy';
    }

    protected function getCacheManager() {
        return $this->cacheManager;
    }

    protected function getDefaultSettings(): array {
        return [
        // Data Security
        'data_encryption_enabled' => false,
        'force_ssl_admin' => true,
        'secure_cookies' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict', // Strict, Lax, None
        'allowed_file_types' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'],
        'max_upload_size' => 5242880, // 5MB in bytes
        'disable_file_editing' => true,

        // Activity Logging
        'activity_logs_enabled' => true,
        'log_retention_days' => 90,
        'log_user_login_logout' => true,
        'log_settings_changes' => true,
        'log_role_permission_changes' => true,
        'log_data_exports' => true,
        'log_failed_logins' => true,
        'log_critical_actions' => true,

        // Audit & Compliance
        'export_logs_enabled' => true,
        'user_access_reports_enabled' => true,
        'security_event_notifications' => true,
        'notify_new_admin_user' => true,
        'notify_role_changes' => true,
        'notify_settings_modified' => true,
        'notify_multiple_failed_logins' => true,
        'compliance_mode' => 'none', // none, gdpr, ccpa

        // Advanced Security
        'disable_xmlrpc' => true,
        'disable_rest_api_anonymous' => false,
        'security_headers_enabled' => true,
        'x_frame_options' => 'SAMEORIGIN',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'database_backup_enabled' => false,
        'auto_security_updates' => true,
        ];
    }

    // ✅ getSettings() - inherited from AbstractSettingsModel
    // ✅ getSetting($key) - inherited from AbstractSettingsModel
    // ✅ saveSettings($settings) - inherited from AbstractSettingsModel
    // ✅ updateSetting($key, $value) - inherited from AbstractSettingsModel
    // ✅ clearCache() - inherited from AbstractSettingsModel

    /**
     * Sanitize settings
     *
     * @param array|null $settings
     * @return array
     */
    public function sanitizeSettings(?array $settings = []): array {
        if ($settings === null) {
            $settings = [];
        }

        $sanitized = [];

        // Data Security
        $sanitized['data_encryption_enabled'] = !empty($settings['data_encryption_enabled']);
        $sanitized['force_ssl_admin'] = !empty($settings['force_ssl_admin']);
        $sanitized['secure_cookies'] = !empty($settings['secure_cookies']);
        $sanitized['cookie_httponly'] = !empty($settings['cookie_httponly']);

        if (isset($settings['cookie_samesite'])) {
            $allowed = ['Strict', 'Lax', 'None'];
            $sanitized['cookie_samesite'] = in_array($settings['cookie_samesite'], $allowed)
                ? $settings['cookie_samesite']
                : 'Strict';
        }

        if (isset($settings['allowed_file_types'])) {
            if (is_array($settings['allowed_file_types'])) {
                $sanitized['allowed_file_types'] = array_map('sanitize_text_field', $settings['allowed_file_types']);
            }
        }

        if (isset($settings['max_upload_size'])) {
            $sanitized['max_upload_size'] = absint($settings['max_upload_size']);
            if ($sanitized['max_upload_size'] < 1024) { // Min 1KB
                $sanitized['max_upload_size'] = 1024;
            }
        }

        $sanitized['disable_file_editing'] = !empty($settings['disable_file_editing']);

        // Activity Logging
        $sanitized['activity_logs_enabled'] = !empty($settings['activity_logs_enabled']);

        if (isset($settings['log_retention_days'])) {
            $sanitized['log_retention_days'] = absint($settings['log_retention_days']);
            if ($sanitized['log_retention_days'] < 7) {
                $sanitized['log_retention_days'] = 7;
            }
            if ($sanitized['log_retention_days'] > 365) {
                $sanitized['log_retention_days'] = 365;
            }
        }

        $sanitized['log_user_login_logout'] = !empty($settings['log_user_login_logout']);
        $sanitized['log_settings_changes'] = !empty($settings['log_settings_changes']);
        $sanitized['log_role_permission_changes'] = !empty($settings['log_role_permission_changes']);
        $sanitized['log_data_exports'] = !empty($settings['log_data_exports']);
        $sanitized['log_failed_logins'] = !empty($settings['log_failed_logins']);
        $sanitized['log_critical_actions'] = !empty($settings['log_critical_actions']);

        // Audit & Compliance
        $sanitized['export_logs_enabled'] = !empty($settings['export_logs_enabled']);
        $sanitized['user_access_reports_enabled'] = !empty($settings['user_access_reports_enabled']);
        $sanitized['security_event_notifications'] = !empty($settings['security_event_notifications']);
        $sanitized['notify_new_admin_user'] = !empty($settings['notify_new_admin_user']);
        $sanitized['notify_role_changes'] = !empty($settings['notify_role_changes']);
        $sanitized['notify_settings_modified'] = !empty($settings['notify_settings_modified']);
        $sanitized['notify_multiple_failed_logins'] = !empty($settings['notify_multiple_failed_logins']);

        if (isset($settings['compliance_mode'])) {
            $allowed = ['none', 'gdpr', 'ccpa'];
            $sanitized['compliance_mode'] = in_array($settings['compliance_mode'], $allowed)
                ? $settings['compliance_mode']
                : 'none';
        }

        // Advanced Security
        $sanitized['disable_xmlrpc'] = !empty($settings['disable_xmlrpc']);
        $sanitized['disable_rest_api_anonymous'] = !empty($settings['disable_rest_api_anonymous']);
        $sanitized['security_headers_enabled'] = !empty($settings['security_headers_enabled']);

        if (isset($settings['x_frame_options'])) {
            $allowed = ['DENY', 'SAMEORIGIN'];
            $sanitized['x_frame_options'] = in_array($settings['x_frame_options'], $allowed)
                ? $settings['x_frame_options']
                : 'SAMEORIGIN';
        }

        if (isset($settings['x_content_type_options'])) {
            $sanitized['x_content_type_options'] = sanitize_text_field($settings['x_content_type_options']);
        }

        if (isset($settings['x_xss_protection'])) {
            $sanitized['x_xss_protection'] = sanitize_text_field($settings['x_xss_protection']);
        }

        if (isset($settings['referrer_policy'])) {
            $sanitized['referrer_policy'] = sanitize_text_field($settings['referrer_policy']);
        }

        $sanitized['database_backup_enabled'] = !empty($settings['database_backup_enabled']);
        $sanitized['auto_security_updates'] = !empty($settings['auto_security_updates']);

        return wp_parse_args($sanitized, $this->getDefaultSettings());
    }
}
