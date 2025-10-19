<?php
/**
 * Platform Settings Model
 *
 * @package     WP_App_Core
 * @subpackage  Models/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Models/Settings/PlatformSettingsModel.php
 *
 * Description: Model untuk mengelola pengaturan platform/company
 *              Menyimpan informasi dasar perusahaan/kantor platform
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial release
 * - Company information settings
 * - Timezone and regional settings
 * - Logo and branding settings
 */

namespace WPAppCore\Models\Settings;

class PlatformSettingsModel {

    private $option_name = 'wp_app_core_platform_settings';

    private $default_settings = [
        // Company Information
        'company_name' => '',
        'company_tagline' => '',
        'company_address' => '',
        'company_city' => '',
        'company_state' => '',
        'company_postal_code' => '',
        'company_country' => 'Indonesia',

        // Contact Information
        'company_phone' => '',
        'company_email' => '',
        'company_website' => '',
        'support_email' => '',
        'support_phone' => '',

        // Branding
        'company_logo_id' => 0,
        'company_logo_url' => '',
        'company_favicon_id' => 0,
        'company_favicon_url' => '',
        'primary_color' => '#0073aa',
        'secondary_color' => '#23282d',

        // Regional Settings
        'timezone' => 'Asia/Jakarta',
        'date_format' => 'd/m/Y',
        'time_format' => 'H:i',
        'first_day_of_week' => 1, // Monday
        'default_language' => 'id_ID',

        // Platform Settings
        'platform_name' => 'Marketplace Platform',
        'platform_version' => '1.0.0',
        'maintenance_mode' => false,
        'maintenance_message' => 'We are currently performing scheduled maintenance.',
    ];

    /**
     * Get platform settings with defaults
     *
     * @return array
     */
    public function getSettings(): array {
        $cache_key = 'wp_app_core_platform_settings';
        $cache_group = 'wp_app_core';

        // Try to get from cache
        $settings = wp_cache_get($cache_key, $cache_group);

        if (false === $settings) {
            // Get from database
            $settings = get_option($this->option_name, []);

            // Parse with defaults
            $settings = wp_parse_args($settings, $this->default_settings);

            // Store in cache
            wp_cache_set($cache_key, $settings, $cache_group);
        }

        // If admin email is not set, use WordPress admin email
        if (empty($settings['company_email'])) {
            $settings['company_email'] = get_option('admin_email');
        }

        if (empty($settings['support_email'])) {
            $settings['support_email'] = get_option('admin_email');
        }

        return $settings;
    }

    /**
     * Save platform settings
     *
     * @param array $input
     * @return bool
     */
    public function saveSettings(array $input): bool {
        if (empty($input)) {
            return false;
        }

        // Clear cache
        wp_cache_delete('wp_app_core_platform_settings', 'wp_app_core');

        // Sanitize input
        $sanitized = $this->sanitizeSettings($input);

        if (!empty($sanitized)) {
            $result = update_option($this->option_name, $sanitized);

            // Re-cache if successful
            if ($result) {
                wp_cache_set(
                    'wp_app_core_platform_settings',
                    $sanitized,
                    'wp_app_core'
                );
            }

            return $result;
        }

        return false;
    }

    /**
     * Sanitize platform settings
     *
     * @param array|null $settings
     * @return array
     */
    public function sanitizeSettings(?array $settings = []): array {
        if ($settings === null) {
            $settings = [];
        }

        $sanitized = [];

        // Sanitize company information
        if (isset($settings['company_name'])) {
            $sanitized['company_name'] = sanitize_text_field($settings['company_name']);
        }

        if (isset($settings['company_tagline'])) {
            $sanitized['company_tagline'] = sanitize_text_field($settings['company_tagline']);
        }

        if (isset($settings['company_address'])) {
            $sanitized['company_address'] = sanitize_textarea_field($settings['company_address']);
        }

        if (isset($settings['company_city'])) {
            $sanitized['company_city'] = sanitize_text_field($settings['company_city']);
        }

        if (isset($settings['company_state'])) {
            $sanitized['company_state'] = sanitize_text_field($settings['company_state']);
        }

        if (isset($settings['company_postal_code'])) {
            $sanitized['company_postal_code'] = sanitize_text_field($settings['company_postal_code']);
        }

        if (isset($settings['company_country'])) {
            $sanitized['company_country'] = sanitize_text_field($settings['company_country']);
        }

        // Sanitize contact information
        if (isset($settings['company_phone'])) {
            $sanitized['company_phone'] = sanitize_text_field($settings['company_phone']);
        }

        if (isset($settings['company_email'])) {
            $sanitized['company_email'] = sanitize_email($settings['company_email']);
        }

        if (isset($settings['company_website'])) {
            $sanitized['company_website'] = esc_url_raw($settings['company_website']);
        }

        if (isset($settings['support_email'])) {
            $sanitized['support_email'] = sanitize_email($settings['support_email']);
        }

        if (isset($settings['support_phone'])) {
            $sanitized['support_phone'] = sanitize_text_field($settings['support_phone']);
        }

        // Sanitize branding
        if (isset($settings['company_logo_id'])) {
            $sanitized['company_logo_id'] = absint($settings['company_logo_id']);
        }

        if (isset($settings['company_logo_url'])) {
            $sanitized['company_logo_url'] = esc_url_raw($settings['company_logo_url']);
        }

        if (isset($settings['company_favicon_id'])) {
            $sanitized['company_favicon_id'] = absint($settings['company_favicon_id']);
        }

        if (isset($settings['company_favicon_url'])) {
            $sanitized['company_favicon_url'] = esc_url_raw($settings['company_favicon_url']);
        }

        if (isset($settings['primary_color'])) {
            $sanitized['primary_color'] = sanitize_hex_color($settings['primary_color']);
        }

        if (isset($settings['secondary_color'])) {
            $sanitized['secondary_color'] = sanitize_hex_color($settings['secondary_color']);
        }

        // Sanitize regional settings
        if (isset($settings['timezone'])) {
            $sanitized['timezone'] = sanitize_text_field($settings['timezone']);
        }

        if (isset($settings['date_format'])) {
            $sanitized['date_format'] = sanitize_text_field($settings['date_format']);
        }

        if (isset($settings['time_format'])) {
            $sanitized['time_format'] = sanitize_text_field($settings['time_format']);
        }

        if (isset($settings['first_day_of_week'])) {
            $sanitized['first_day_of_week'] = absint($settings['first_day_of_week']);
            if ($sanitized['first_day_of_week'] > 6) {
                $sanitized['first_day_of_week'] = 1;
            }
        }

        if (isset($settings['default_language'])) {
            $sanitized['default_language'] = sanitize_text_field($settings['default_language']);
        }

        // Sanitize platform settings
        if (isset($settings['platform_name'])) {
            $sanitized['platform_name'] = sanitize_text_field($settings['platform_name']);
        }

        if (isset($settings['platform_version'])) {
            $sanitized['platform_version'] = sanitize_text_field($settings['platform_version']);
        }

        if (isset($settings['maintenance_mode'])) {
            $sanitized['maintenance_mode'] = (bool) $settings['maintenance_mode'];
        }

        if (isset($settings['maintenance_message'])) {
            $sanitized['maintenance_message'] = sanitize_textarea_field($settings['maintenance_message']);
        }

        // Merge with defaults
        return wp_parse_args($sanitized, $this->default_settings);
    }

    /**
     * Get default settings
     *
     * @return array
     */
    public function getDefaultSettings(): array {
        return $this->default_settings;
    }

    /**
     * Delete settings
     *
     * @return bool
     */
    public function deleteSettings(): bool {
        wp_cache_delete('wp_app_core_platform_settings', 'wp_app_core');
        return delete_option($this->option_name);
    }
}
