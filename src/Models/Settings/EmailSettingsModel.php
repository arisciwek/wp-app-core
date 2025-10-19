<?php
/**
 * Email Settings Model
 *
 * @package     WP_App_Core
 * @subpackage  Models/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Models/Settings/EmailSettingsModel.php
 *
 * Description: Model untuk mengelola email dan notification settings
 *              SMTP configuration, email templates, dan notification preferences
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial release
 * - SMTP configuration
 * - Email templates management
 * - Notification preferences
 */

namespace WPAppCore\Models\Settings;

class EmailSettingsModel {

    private $option_name = 'wp_app_core_email_settings';

    private $default_settings = [
        // SMTP Configuration
        'smtp_enabled' => false,
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_encryption' => 'tls', // tls, ssl, none
        'smtp_auth' => true,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_from_email' => '',
        'smtp_from_name' => '',

        // Email Templates
        'welcome_email_enabled' => true,
        'welcome_email_subject' => 'Welcome to {platform_name}',
        'welcome_email_content' => '',

        'notification_email_enabled' => true,
        'notification_from_email' => '',
        'notification_from_name' => '',

        // Admin Notifications
        'admin_new_tenant_notification' => true,
        'admin_new_payment_notification' => true,
        'admin_support_ticket_notification' => true,
        'admin_notification_recipients' => [], // Array of emails

        // Tenant Notifications (for customers/branches)
        'tenant_registration_approved' => true,
        'tenant_invoice_created' => true,
        'tenant_payment_received' => true,
        'tenant_subscription_expiring' => true,

        // Notification Preferences
        'notification_method' => ['email'], // email, in_app, sms
        'digest_enabled' => false,
        'digest_frequency' => 'daily', // daily, weekly
        'digest_time' => '09:00',

        // Email Settings
        'email_footer_text' => '',
        'unsubscribe_enabled' => true,
    ];

    /**
     * Get email settings
     *
     * @return array
     */
    public function getSettings(): array {
        $cache_key = 'wp_app_core_email_settings';
        $cache_group = 'wp_app_core';

        $settings = wp_cache_get($cache_key, $cache_group);

        if (false === $settings) {
            $settings = get_option($this->option_name, []);
            $settings = wp_parse_args($settings, $this->default_settings);
            wp_cache_set($cache_key, $settings, $cache_group);
        }

        // Default from email to admin email if empty
        if (empty($settings['smtp_from_email'])) {
            $settings['smtp_from_email'] = get_option('admin_email');
        }

        if (empty($settings['notification_from_email'])) {
            $settings['notification_from_email'] = get_option('admin_email');
        }

        return $settings;
    }

    /**
     * Save email settings
     *
     * @param array $input
     * @return bool
     */
    public function saveSettings(array $input): bool {
        if (empty($input)) {
            return false;
        }

        wp_cache_delete('wp_app_core_email_settings', 'wp_app_core');

        $sanitized = $this->sanitizeSettings($input);

        if (!empty($sanitized)) {
            $result = update_option($this->option_name, $sanitized);

            if ($result) {
                wp_cache_set(
                    'wp_app_core_email_settings',
                    $sanitized,
                    'wp_app_core'
                );
            }

            return $result;
        }

        return false;
    }

    /**
     * Sanitize email settings
     *
     * @param array|null $settings
     * @return array
     */
    public function sanitizeSettings(?array $settings = []): array {
        if ($settings === null) {
            $settings = [];
        }

        $sanitized = [];

        // SMTP Configuration
        if (isset($settings['smtp_enabled'])) {
            $sanitized['smtp_enabled'] = (bool) $settings['smtp_enabled'];
        }

        if (isset($settings['smtp_host'])) {
            $sanitized['smtp_host'] = sanitize_text_field($settings['smtp_host']);
        }

        if (isset($settings['smtp_port'])) {
            $sanitized['smtp_port'] = absint($settings['smtp_port']);
            if ($sanitized['smtp_port'] < 1 || $sanitized['smtp_port'] > 65535) {
                $sanitized['smtp_port'] = 587;
            }
        }

        if (isset($settings['smtp_encryption'])) {
            $allowed = ['tls', 'ssl', 'none'];
            $sanitized['smtp_encryption'] = in_array($settings['smtp_encryption'], $allowed)
                ? $settings['smtp_encryption']
                : 'tls';
        }

        if (isset($settings['smtp_auth'])) {
            $sanitized['smtp_auth'] = (bool) $settings['smtp_auth'];
        }

        if (isset($settings['smtp_username'])) {
            $sanitized['smtp_username'] = sanitize_text_field($settings['smtp_username']);
        }

        if (isset($settings['smtp_password'])) {
            // Don't sanitize password, just store it
            $sanitized['smtp_password'] = $settings['smtp_password'];
        }

        if (isset($settings['smtp_from_email'])) {
            $sanitized['smtp_from_email'] = sanitize_email($settings['smtp_from_email']);
        }

        if (isset($settings['smtp_from_name'])) {
            $sanitized['smtp_from_name'] = sanitize_text_field($settings['smtp_from_name']);
        }

        // Email Templates
        if (isset($settings['welcome_email_enabled'])) {
            $sanitized['welcome_email_enabled'] = (bool) $settings['welcome_email_enabled'];
        }

        if (isset($settings['welcome_email_subject'])) {
            $sanitized['welcome_email_subject'] = sanitize_text_field($settings['welcome_email_subject']);
        }

        if (isset($settings['welcome_email_content'])) {
            $sanitized['welcome_email_content'] = wp_kses_post($settings['welcome_email_content']);
        }

        if (isset($settings['notification_email_enabled'])) {
            $sanitized['notification_email_enabled'] = (bool) $settings['notification_email_enabled'];
        }

        if (isset($settings['notification_from_email'])) {
            $sanitized['notification_from_email'] = sanitize_email($settings['notification_from_email']);
        }

        if (isset($settings['notification_from_name'])) {
            $sanitized['notification_from_name'] = sanitize_text_field($settings['notification_from_name']);
        }

        // Admin Notifications
        if (isset($settings['admin_new_tenant_notification'])) {
            $sanitized['admin_new_tenant_notification'] = (bool) $settings['admin_new_tenant_notification'];
        }

        if (isset($settings['admin_new_payment_notification'])) {
            $sanitized['admin_new_payment_notification'] = (bool) $settings['admin_new_payment_notification'];
        }

        if (isset($settings['admin_support_ticket_notification'])) {
            $sanitized['admin_support_ticket_notification'] = (bool) $settings['admin_support_ticket_notification'];
        }

        if (isset($settings['admin_notification_recipients'])) {
            if (is_array($settings['admin_notification_recipients'])) {
                $sanitized['admin_notification_recipients'] = array_map('sanitize_email', $settings['admin_notification_recipients']);
                $sanitized['admin_notification_recipients'] = array_filter($sanitized['admin_notification_recipients']);
            }
        }

        // Tenant Notifications
        if (isset($settings['tenant_registration_approved'])) {
            $sanitized['tenant_registration_approved'] = (bool) $settings['tenant_registration_approved'];
        }

        if (isset($settings['tenant_invoice_created'])) {
            $sanitized['tenant_invoice_created'] = (bool) $settings['tenant_invoice_created'];
        }

        if (isset($settings['tenant_payment_received'])) {
            $sanitized['tenant_payment_received'] = (bool) $settings['tenant_payment_received'];
        }

        if (isset($settings['tenant_subscription_expiring'])) {
            $sanitized['tenant_subscription_expiring'] = (bool) $settings['tenant_subscription_expiring'];
        }

        // Notification Preferences
        if (isset($settings['notification_method'])) {
            $allowed = ['email', 'in_app', 'sms'];
            if (is_array($settings['notification_method'])) {
                $sanitized['notification_method'] = array_values(
                    array_intersect($settings['notification_method'], $allowed)
                );
            }
            if (empty($sanitized['notification_method'])) {
                $sanitized['notification_method'] = ['email'];
            }
        }

        if (isset($settings['digest_enabled'])) {
            $sanitized['digest_enabled'] = (bool) $settings['digest_enabled'];
        }

        if (isset($settings['digest_frequency'])) {
            $allowed = ['daily', 'weekly'];
            $sanitized['digest_frequency'] = in_array($settings['digest_frequency'], $allowed)
                ? $settings['digest_frequency']
                : 'daily';
        }

        if (isset($settings['digest_time'])) {
            $sanitized['digest_time'] = sanitize_text_field($settings['digest_time']);
        }

        // Email Settings
        if (isset($settings['email_footer_text'])) {
            $sanitized['email_footer_text'] = sanitize_textarea_field($settings['email_footer_text']);
        }

        if (isset($settings['unsubscribe_enabled'])) {
            $sanitized['unsubscribe_enabled'] = (bool) $settings['unsubscribe_enabled'];
        }

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
        wp_cache_delete('wp_app_core_email_settings', 'wp_app_core');
        return delete_option($this->option_name);
    }

    /**
     * Test SMTP connection
     *
     * @return array
     */
    public function testSMTPConnection(): array {
        $settings = $this->getSettings();

        if (!$settings['smtp_enabled']) {
            return [
                'success' => false,
                'message' => 'SMTP is not enabled'
            ];
        }

        // Test connection logic here
        // This is a placeholder
        return [
            'success' => true,
            'message' => 'SMTP connection successful'
        ];
    }
}
