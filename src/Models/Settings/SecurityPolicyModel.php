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

use WPAppCore\Models\AbstractSettingsModel;
use WPAppCore\Cache\Abstract\AbstractCacheManager;
use WPAppCore\Cache\PlatformCacheManager;

class SecurityPolicyModel extends AbstractSettingsModel {

    private PlatformCacheManager $cacheManager;

    public function __construct() {
        $this->cacheManager = new PlatformCacheManager();
    }

    protected function getOptionName(): string {
        return 'wpapp_security_policy';
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
        $sanitized['data_encryption_enabled'] = isset($settings['data_encryption_enabled']) ? (bool) $settings['data_encryption_enabled'] : false;
        $sanitized['force_ssl_admin'] = isset($settings['force_ssl_admin']) ? (bool) $settings['force_ssl_admin'] : false;
        $sanitized['secure_cookies'] = isset($settings['secure_cookies']) ? (bool) $settings['secure_cookies'] : false;
        $sanitized['cookie_httponly'] = isset($settings['cookie_httponly']) ? (bool) $settings['cookie_httponly'] : false;

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

        $sanitized['disable_file_editing'] = isset($settings['disable_file_editing']) ? (bool) $settings['disable_file_editing'] : false;

        // Activity Logging
        $sanitized['activity_logs_enabled'] = isset($settings['activity_logs_enabled']) ? (bool) $settings['activity_logs_enabled'] : false;

        if (isset($settings['log_retention_days'])) {
            $sanitized['log_retention_days'] = absint($settings['log_retention_days']);
            if ($sanitized['log_retention_days'] < 7) {
                $sanitized['log_retention_days'] = 7;
            }
            if ($sanitized['log_retention_days'] > 365) {
                $sanitized['log_retention_days'] = 365;
            }
        }

        $sanitized['log_user_login_logout'] = isset($settings['log_user_login_logout']) ? (bool) $settings['log_user_login_logout'] : false;
        $sanitized['log_settings_changes'] = isset($settings['log_settings_changes']) ? (bool) $settings['log_settings_changes'] : false;
        $sanitized['log_role_permission_changes'] = isset($settings['log_role_permission_changes']) ? (bool) $settings['log_role_permission_changes'] : false;
        $sanitized['log_data_exports'] = isset($settings['log_data_exports']) ? (bool) $settings['log_data_exports'] : false;
        $sanitized['log_failed_logins'] = isset($settings['log_failed_logins']) ? (bool) $settings['log_failed_logins'] : false;
        $sanitized['log_critical_actions'] = isset($settings['log_critical_actions']) ? (bool) $settings['log_critical_actions'] : false;

        // Audit & Compliance
        $sanitized['export_logs_enabled'] = isset($settings['export_logs_enabled']) ? (bool) $settings['export_logs_enabled'] : false;
        $sanitized['user_access_reports_enabled'] = isset($settings['user_access_reports_enabled']) ? (bool) $settings['user_access_reports_enabled'] : false;
        $sanitized['security_event_notifications'] = isset($settings['security_event_notifications']) ? (bool) $settings['security_event_notifications'] : false;
        $sanitized['notify_new_admin_user'] = isset($settings['notify_new_admin_user']) ? (bool) $settings['notify_new_admin_user'] : false;
        $sanitized['notify_role_changes'] = isset($settings['notify_role_changes']) ? (bool) $settings['notify_role_changes'] : false;
        $sanitized['notify_settings_modified'] = isset($settings['notify_settings_modified']) ? (bool) $settings['notify_settings_modified'] : false;
        $sanitized['notify_multiple_failed_logins'] = isset($settings['notify_multiple_failed_logins']) ? (bool) $settings['notify_multiple_failed_logins'] : false;

        if (isset($settings['compliance_mode'])) {
            $allowed = ['none', 'gdpr', 'ccpa'];
            $sanitized['compliance_mode'] = in_array($settings['compliance_mode'], $allowed)
                ? $settings['compliance_mode']
                : 'none';
        }

        // Advanced Security
        $sanitized['disable_xmlrpc'] = isset($settings['disable_xmlrpc']) ? (bool) $settings['disable_xmlrpc'] : false;
        $sanitized['disable_rest_api_anonymous'] = isset($settings['disable_rest_api_anonymous']) ? (bool) $settings['disable_rest_api_anonymous'] : false;
        $sanitized['security_headers_enabled'] = isset($settings['security_headers_enabled']) ? (bool) $settings['security_headers_enabled'] : false;

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

        $sanitized['database_backup_enabled'] = isset($settings['database_backup_enabled']) ? (bool) $settings['database_backup_enabled'] : false;
        $sanitized['auto_security_updates'] = isset($settings['auto_security_updates']) ? (bool) $settings['auto_security_updates'] : false;

        return wp_parse_args($sanitized, $this->getDefaultSettings());
    }
}
