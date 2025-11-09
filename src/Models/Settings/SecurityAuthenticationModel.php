<?php
/**
 * Security Authentication Model
 *
 * @package     WP_App_Core
 * @subpackage  Models/Settings
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Models/Settings/SecurityAuthenticationModel.php
 *
 * Description: Model untuk Authentication & Access Control settings.
 *              Password policies, 2FA, IP restrictions.
 *              REFACTORED: Now extends AbstractSettingsModel.
 *
 * Changelog:
 * 2.0.0 - 2025-01-09 (TODO-1203)
 * - BREAKING: Now extends AbstractSettingsModel
 * - CHANGED: Uses AbstractCacheManager via PlatformCacheManager
 * - REMOVED: Duplicate methods (getSettings, saveSettings inherited)
 * - REDUCED: ~110 lines eliminated
 * 1.0.0 - 2025-10-19
 * - Initial release
 * - Password policy settings
 * - Two-factor authentication settings
 * - Access control settings
 */

namespace WPAppCore\Models\Settings;

use WPAppCore\Models\AbstractSettingsModel;
use WPAppCore\Cache\Abstract\AbstractCacheManager;
use WPAppCore\Cache\PlatformCacheManager;

class SecurityAuthenticationModel extends AbstractSettingsModel {

    private PlatformCacheManager $cacheManager;

    public function __construct() {
        $this->cacheManager = new PlatformCacheManager();
    }

    protected function getOptionName(): string {
        return 'wpapp_security_authentication';
    }

    protected function getCacheManager() {
        return $this->cacheManager;
    }

    protected function getDefaultSettings(): array {
        return [
        // Login Settings
        'force_strong_password' => true,
        'password_min_length' => 12,
        'password_require_uppercase' => true,
        'password_require_lowercase' => true,
        'password_require_numbers' => true,
        'password_require_special_chars' => true,
        'password_expiration_days' => 90,
        'password_history_count' => 5, // Prevent reusing last N passwords

        // Two-Factor Authentication
        'twofa_enabled' => false,
        'twofa_force_for_roles' => [], // Array of role names
        'twofa_methods' => ['authenticator'], // authenticator, email, sms
        'twofa_backup_codes' => true,
        'twofa_grace_period_days' => 7,

        // Access Control
        'ip_whitelist_enabled' => false,
        'ip_whitelist' => [], // Array of IPs
        'ip_blacklist_enabled' => false,
        'ip_blacklist' => [], // Array of IPs
        'country_blocking_enabled' => false,
        'allowed_countries' => [], // Array of country codes
        'blocked_countries' => [], // Array of country codes
        'device_management_enabled' => false,
        'admin_access_hours_enabled' => false,
        'admin_access_hours_start' => '00:00',
        'admin_access_hours_end' => '23:59',
        'maintenance_mode_enabled' => false,
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

        // Login Settings
        $sanitized['force_strong_password'] = isset($settings['force_strong_password']) ? (bool) $settings['force_strong_password'] : false;

        if (isset($settings['password_min_length'])) {
            $sanitized['password_min_length'] = absint($settings['password_min_length']);
            if ($sanitized['password_min_length'] < 8) {
                $sanitized['password_min_length'] = 8;
            }
            if ($sanitized['password_min_length'] > 128) {
                $sanitized['password_min_length'] = 128;
            }
        }

        $sanitized['password_require_uppercase'] = isset($settings['password_require_uppercase']) ? (bool) $settings['password_require_uppercase'] : false;
        $sanitized['password_require_lowercase'] = isset($settings['password_require_lowercase']) ? (bool) $settings['password_require_lowercase'] : false;
        $sanitized['password_require_numbers'] = isset($settings['password_require_numbers']) ? (bool) $settings['password_require_numbers'] : false;
        $sanitized['password_require_special_chars'] = isset($settings['password_require_special_chars']) ? (bool) $settings['password_require_special_chars'] : false;

        if (isset($settings['password_expiration_days'])) {
            $sanitized['password_expiration_days'] = absint($settings['password_expiration_days']);
        }

        if (isset($settings['password_history_count'])) {
            $sanitized['password_history_count'] = absint($settings['password_history_count']);
            if ($sanitized['password_history_count'] > 24) {
                $sanitized['password_history_count'] = 24;
            }
        }

        // Two-Factor Authentication
        $sanitized['twofa_enabled'] = isset($settings['twofa_enabled']) ? (bool) $settings['twofa_enabled'] : false;

        if (isset($settings['twofa_force_for_roles'])) {
            if (is_array($settings['twofa_force_for_roles'])) {
                $sanitized['twofa_force_for_roles'] = array_map('sanitize_key', $settings['twofa_force_for_roles']);
            }
        }

        if (isset($settings['twofa_methods'])) {
            $allowed = ['authenticator', 'email', 'sms'];
            if (is_array($settings['twofa_methods'])) {
                $sanitized['twofa_methods'] = array_values(
                    array_intersect($settings['twofa_methods'], $allowed)
                );
            }
            if (empty($sanitized['twofa_methods'])) {
                $sanitized['twofa_methods'] = ['authenticator'];
            }
        }

        $sanitized['twofa_backup_codes'] = isset($settings['twofa_backup_codes']) ? (bool) $settings['twofa_backup_codes'] : false;

        if (isset($settings['twofa_grace_period_days'])) {
            $sanitized['twofa_grace_period_days'] = absint($settings['twofa_grace_period_days']);
        }

        // Access Control
        $sanitized['ip_whitelist_enabled'] = isset($settings['ip_whitelist_enabled']) ? (bool) $settings['ip_whitelist_enabled'] : false;

        if (isset($settings['ip_whitelist'])) {
            if (is_array($settings['ip_whitelist'])) {
                $sanitized['ip_whitelist'] = array_map('sanitize_text_field', $settings['ip_whitelist']);
            }
        }

        $sanitized['ip_blacklist_enabled'] = isset($settings['ip_blacklist_enabled']) ? (bool) $settings['ip_blacklist_enabled'] : false;

        if (isset($settings['ip_blacklist'])) {
            if (is_array($settings['ip_blacklist'])) {
                $sanitized['ip_blacklist'] = array_map('sanitize_text_field', $settings['ip_blacklist']);
            }
        }

        $sanitized['country_blocking_enabled'] = isset($settings['country_blocking_enabled']) ? (bool) $settings['country_blocking_enabled'] : false;

        if (isset($settings['allowed_countries'])) {
            if (is_array($settings['allowed_countries'])) {
                $sanitized['allowed_countries'] = array_map('sanitize_text_field', $settings['allowed_countries']);
            }
        }

        if (isset($settings['blocked_countries'])) {
            if (is_array($settings['blocked_countries'])) {
                $sanitized['blocked_countries'] = array_map('sanitize_text_field', $settings['blocked_countries']);
            }
        }

        $sanitized['device_management_enabled'] = isset($settings['device_management_enabled']) ? (bool) $settings['device_management_enabled'] : false;
        $sanitized['admin_access_hours_enabled'] = isset($settings['admin_access_hours_enabled']) ? (bool) $settings['admin_access_hours_enabled'] : false;

        if (isset($settings['admin_access_hours_start'])) {
            $sanitized['admin_access_hours_start'] = sanitize_text_field($settings['admin_access_hours_start']);
        }

        if (isset($settings['admin_access_hours_end'])) {
            $sanitized['admin_access_hours_end'] = sanitize_text_field($settings['admin_access_hours_end']);
        }

        $sanitized['maintenance_mode_enabled'] = isset($settings['maintenance_mode_enabled']) ? (bool) $settings['maintenance_mode_enabled'] : false;

        return wp_parse_args($sanitized, $this->getDefaultSettings());
    }
}
