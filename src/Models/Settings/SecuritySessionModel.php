<?php
/**
 * Security Session Model
 *
 * @package     WP_App_Core
 * @subpackage  Models/Settings
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Models/Settings/SecuritySessionModel.php
 *
 * Description: Model untuk Session & Login Management settings.
 *              REFACTORED: Now extends AbstractSettingsModel.
 *
 * Changelog:
 * 2.0.0 - 2025-01-09 (TODO-1203)
 * - BREAKING: Now extends AbstractSettingsModel
 * - Uses PlatformCacheManager via AbstractCacheManager
 * - ~100 lines eliminated
 * 1.0.0 - 2025-10-19
 * - Initial release
 */

namespace WPAppCore\Models\Settings;

use WPAppCore\Models\AbstractSettingsModel;
use WPAppCore\Cache\Abstract\AbstractCacheManager;
use WPAppCore\Cache\PlatformCacheManager;

class SecuritySessionModel extends AbstractSettingsModel {

    private PlatformCacheManager $cacheManager;

    public function __construct() {
        $this->cacheManager = new PlatformCacheManager();
    }

    protected function getOptionName(): string {
        return 'wpapp_security_session';
    }

    protected function getCacheManager() {
        return $this->cacheManager;
    }

    protected function getDefaultSettings(): array {
        return [
        // Session Settings
        'session_idle_timeout' => 3600, // 1 hour in seconds
        'session_absolute_timeout' => 43200, // 12 hours in seconds
        'concurrent_sessions_limit' => 3,
        'force_logout_enabled' => false,
        'remember_me_duration' => 1209600, // 14 days in seconds

        // Login Protection
        'max_login_attempts' => 5,
        'lockout_duration' => 1800, // 30 minutes in seconds
        'progressive_delays_enabled' => true,
        'captcha_after_failed_attempts' => 3,
        'email_failed_login_notification' => true,

        // Login Monitoring
        'login_history_enabled' => true,
        'login_history_limit' => 100,
        'show_active_sessions' => true,
        'force_logout_suspicious_sessions' => true,
        'email_new_device_login' => true,
        'unusual_activity_detection' => true,
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

        // Session Settings
        if (isset($settings['session_idle_timeout'])) {
            $sanitized['session_idle_timeout'] = absint($settings['session_idle_timeout']);
            if ($sanitized['session_idle_timeout'] < 300) { // Min 5 minutes
                $sanitized['session_idle_timeout'] = 300;
            }
        }

        if (isset($settings['session_absolute_timeout'])) {
            $sanitized['session_absolute_timeout'] = absint($settings['session_absolute_timeout']);
            if ($sanitized['session_absolute_timeout'] < 600) { // Min 10 minutes
                $sanitized['session_absolute_timeout'] = 600;
            }
        }

        if (isset($settings['concurrent_sessions_limit'])) {
            $sanitized['concurrent_sessions_limit'] = absint($settings['concurrent_sessions_limit']);
            if ($sanitized['concurrent_sessions_limit'] < 1) {
                $sanitized['concurrent_sessions_limit'] = 1;
            }
            if ($sanitized['concurrent_sessions_limit'] > 10) {
                $sanitized['concurrent_sessions_limit'] = 10;
            }
        }

        $sanitized['force_logout_enabled'] = isset($settings['force_logout_enabled']) ? (bool) $settings['force_logout_enabled'] : false;

        if (isset($settings['remember_me_duration'])) {
            $sanitized['remember_me_duration'] = absint($settings['remember_me_duration']);
        }

        // Login Protection
        if (isset($settings['max_login_attempts'])) {
            $sanitized['max_login_attempts'] = absint($settings['max_login_attempts']);
            if ($sanitized['max_login_attempts'] < 3) {
                $sanitized['max_login_attempts'] = 3;
            }
            if ($sanitized['max_login_attempts'] > 10) {
                $sanitized['max_login_attempts'] = 10;
            }
        }

        if (isset($settings['lockout_duration'])) {
            $sanitized['lockout_duration'] = absint($settings['lockout_duration']);
            if ($sanitized['lockout_duration'] < 300) { // Min 5 minutes
                $sanitized['lockout_duration'] = 300;
            }
        }

        $sanitized['progressive_delays_enabled'] = isset($settings['progressive_delays_enabled']) ? (bool) $settings['progressive_delays_enabled'] : false;

        if (isset($settings['captcha_after_failed_attempts'])) {
            $sanitized['captcha_after_failed_attempts'] = absint($settings['captcha_after_failed_attempts']);
            if ($sanitized['captcha_after_failed_attempts'] < 1) {
                $sanitized['captcha_after_failed_attempts'] = 1;
            }
        }

        $sanitized['email_failed_login_notification'] = isset($settings['email_failed_login_notification']) ? (bool) $settings['email_failed_login_notification'] : false;

        // Login Monitoring
        $sanitized['login_history_enabled'] = isset($settings['login_history_enabled']) ? (bool) $settings['login_history_enabled'] : false;

        if (isset($settings['login_history_limit'])) {
            $sanitized['login_history_limit'] = absint($settings['login_history_limit']);
            if ($sanitized['login_history_limit'] < 10) {
                $sanitized['login_history_limit'] = 10;
            }
            if ($sanitized['login_history_limit'] > 1000) {
                $sanitized['login_history_limit'] = 1000;
            }
        }

        $sanitized['show_active_sessions'] = isset($settings['show_active_sessions']) ? (bool) $settings['show_active_sessions'] : false;
        $sanitized['force_logout_suspicious_sessions'] = isset($settings['force_logout_suspicious_sessions']) ? (bool) $settings['force_logout_suspicious_sessions'] : false;
        $sanitized['email_new_device_login'] = isset($settings['email_new_device_login']) ? (bool) $settings['email_new_device_login'] : false;
        $sanitized['unusual_activity_detection'] = isset($settings['unusual_activity_detection']) ? (bool) $settings['unusual_activity_detection'] : false;

        return wp_parse_args($sanitized, $this->getDefaultSettings());
    }
}
