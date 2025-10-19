<?php
/**
 * Email & Notification Settings Tab
 *
 * @package     WP_App_Core
 * @subpackage  Views/Templates/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/templates/settings/tab-email.php
 *
 * Description: Email and notification settings tab template
 *              SMTP configuration, email templates, notification preferences
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial creation
 */

if (!defined('ABSPATH')) {
    die;
}

// $settings is passed from controller
?>

<div class="platform-settings-email">
    <form method="post" action="options.php" id="platform-email-settings-form">
        <?php settings_fields('wp_app_core_email_settings'); ?>

        <!-- SMTP Configuration Section -->
        <div class="settings-section">
            <h2><?php _e('SMTP Configuration', 'wp-app-core'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php _e('Enable SMTP', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="wp_app_core_email_settings[smtp_enabled]"
                                   value="1" <?php checked($settings['smtp_enabled'] ?? false, true); ?>>
                            <?php _e('Use SMTP for sending emails', 'wp-app-core'); ?>
                        </label>
                        <p class="description"><?php _e('Enable this to use custom SMTP server instead of PHP mail()', 'wp-app-core'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="smtp_host"><?php _e('SMTP Host', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="wp_app_core_email_settings[smtp_host]"
                               id="smtp_host" value="<?php echo esc_attr($settings['smtp_host'] ?? ''); ?>"
                               class="regular-text" placeholder="smtp.example.com">
                        <p class="description"><?php _e('SMTP server hostname', 'wp-app-core'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="smtp_port"><?php _e('SMTP Port', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="wp_app_core_email_settings[smtp_port]"
                               id="smtp_port" value="<?php echo esc_attr($settings['smtp_port'] ?? 587); ?>"
                               class="small-text" min="1" max="65535">
                        <p class="description"><?php _e('Common ports: 25, 465 (SSL), 587 (TLS)', 'wp-app-core'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="smtp_encryption"><?php _e('Encryption', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <select name="wp_app_core_email_settings[smtp_encryption]" id="smtp_encryption">
                            <option value="tls" <?php selected($settings['smtp_encryption'] ?? 'tls', 'tls'); ?>>TLS (Recommended)</option>
                            <option value="ssl" <?php selected($settings['smtp_encryption'] ?? 'tls', 'ssl'); ?>>SSL</option>
                            <option value="none" <?php selected($settings['smtp_encryption'] ?? 'tls', 'none'); ?>>None</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('SMTP Authentication', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="wp_app_core_email_settings[smtp_auth]"
                                   value="1" <?php checked($settings['smtp_auth'] ?? true, true); ?>>
                            <?php _e('Use SMTP authentication', 'wp-app-core'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="smtp_username"><?php _e('SMTP Username', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="wp_app_core_email_settings[smtp_username]"
                               id="smtp_username" value="<?php echo esc_attr($settings['smtp_username'] ?? ''); ?>"
                               class="regular-text" autocomplete="off">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="smtp_password"><?php _e('SMTP Password', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <input type="password" name="wp_app_core_email_settings[smtp_password]"
                               id="smtp_password" value="<?php echo esc_attr($settings['smtp_password'] ?? ''); ?>"
                               class="regular-text" autocomplete="new-password">
                        <p class="description"><?php _e('Password is stored encrypted', 'wp-app-core'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="smtp_from_email"><?php _e('From Email', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <input type="email" name="wp_app_core_email_settings[smtp_from_email]"
                               id="smtp_from_email" value="<?php echo esc_attr($settings['smtp_from_email'] ?? ''); ?>"
                               class="regular-text" placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                        <p class="description"><?php _e('Email address to send from', 'wp-app-core'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="smtp_from_name"><?php _e('From Name', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="wp_app_core_email_settings[smtp_from_name]"
                               id="smtp_from_name" value="<?php echo esc_attr($settings['smtp_from_name'] ?? ''); ?>"
                               class="regular-text" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
                        <p class="description"><?php _e('Name to appear in From field', 'wp-app-core'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Admin Notifications Section -->
        <div class="settings-section">
            <h2><?php _e('Admin Notifications', 'wp-app-core'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php _e('Notify on New Tenant', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="wp_app_core_email_settings[admin_new_tenant_notification]"
                                   value="1" <?php checked($settings['admin_new_tenant_notification'] ?? true, true); ?>>
                            <?php _e('Send notification when new tenant registers', 'wp-app-core'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('Notify on New Payment', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="wp_app_core_email_settings[admin_new_payment_notification]"
                                   value="1" <?php checked($settings['admin_new_payment_notification'] ?? true, true); ?>>
                            <?php _e('Send notification when payment is received', 'wp-app-core'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('Notify on Support Ticket', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="wp_app_core_email_settings[admin_support_ticket_notification]"
                                   value="1" <?php checked($settings['admin_support_ticket_notification'] ?? true, true); ?>>
                            <?php _e('Send notification when new support ticket is created', 'wp-app-core'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="admin_notification_recipients"><?php _e('Notification Recipients', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <textarea name="wp_app_core_email_settings[admin_notification_recipients]"
                                  id="admin_notification_recipients" rows="3" class="large-text"
                                  placeholder="admin@example.com&#10;manager@example.com"><?php
                            if (!empty($settings['admin_notification_recipients'])) {
                                echo esc_textarea(implode("\n", $settings['admin_notification_recipients']));
                            }
                        ?></textarea>
                        <p class="description"><?php _e('One email address per line', 'wp-app-core'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Tenant Notifications Section -->
        <div class="settings-section">
            <h2><?php _e('Tenant Notifications', 'wp-app-core'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php _e('Registration Approved', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="wp_app_core_email_settings[tenant_registration_approved]"
                                   value="1" <?php checked($settings['tenant_registration_approved'] ?? true, true); ?>>
                            <?php _e('Notify tenant when registration is approved', 'wp-app-core'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('Invoice Created', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="wp_app_core_email_settings[tenant_invoice_created]"
                                   value="1" <?php checked($settings['tenant_invoice_created'] ?? true, true); ?>>
                            <?php _e('Notify tenant when invoice is created', 'wp-app-core'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('Payment Received', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="wp_app_core_email_settings[tenant_payment_received]"
                                   value="1" <?php checked($settings['tenant_payment_received'] ?? true, true); ?>>
                            <?php _e('Notify tenant when payment is confirmed', 'wp-app-core'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('Subscription Expiring', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="wp_app_core_email_settings[tenant_subscription_expiring]"
                                   value="1" <?php checked($settings['tenant_subscription_expiring'] ?? true, true); ?>>
                            <?php _e('Notify tenant when subscription is about to expire', 'wp-app-core'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Notification Preferences Section -->
        <div class="settings-section">
            <h2><?php _e('Notification Preferences', 'wp-app-core'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php _e('Notification Methods', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <?php
                        $methods = $settings['notification_method'] ?? ['email'];
                        ?>
                        <label>
                            <input type="checkbox" name="wp_app_core_email_settings[notification_method][]"
                                   value="email" <?php checked(in_array('email', $methods), true); ?>>
                            <?php _e('Email', 'wp-app-core'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="wp_app_core_email_settings[notification_method][]"
                                   value="in_app" <?php checked(in_array('in_app', $methods), true); ?>>
                            <?php _e('In-App Notification', 'wp-app-core'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="wp_app_core_email_settings[notification_method][]"
                                   value="sms" <?php checked(in_array('sms', $methods), true); ?>>
                            <?php _e('SMS (if configured)', 'wp-app-core'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('Email Digest', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="wp_app_core_email_settings[digest_enabled]"
                                   value="1" <?php checked($settings['digest_enabled'] ?? false, true); ?>>
                            <?php _e('Enable email digest (combine notifications)', 'wp-app-core'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="digest_frequency"><?php _e('Digest Frequency', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <select name="wp_app_core_email_settings[digest_frequency]" id="digest_frequency">
                            <option value="daily" <?php selected($settings['digest_frequency'] ?? 'daily', 'daily'); ?>><?php _e('Daily', 'wp-app-core'); ?></option>
                            <option value="weekly" <?php selected($settings['digest_frequency'] ?? 'daily', 'weekly'); ?>><?php _e('Weekly', 'wp-app-core'); ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="digest_time"><?php _e('Digest Time', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <input type="time" name="wp_app_core_email_settings[digest_time]"
                               id="digest_time" value="<?php echo esc_attr($settings['digest_time'] ?? '09:00'); ?>">
                        <p class="description"><?php _e('Time of day to send digest', 'wp-app-core'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Email Footer Section -->
        <div class="settings-section">
            <h2><?php _e('Email Settings', 'wp-app-core'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="email_footer_text"><?php _e('Email Footer Text', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <textarea name="wp_app_core_email_settings[email_footer_text]"
                                  id="email_footer_text" rows="4" class="large-text"><?php
                            echo esc_textarea($settings['email_footer_text'] ?? '');
                        ?></textarea>
                        <p class="description"><?php _e('Text to appear in email footer', 'wp-app-core'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('Unsubscribe Option', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="wp_app_core_email_settings[unsubscribe_enabled]"
                                   value="1" <?php checked($settings['unsubscribe_enabled'] ?? true, true); ?>>
                            <?php _e('Allow users to unsubscribe from emails', 'wp-app-core'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
    </form>

    <!-- Sticky Footer with Action Buttons -->
    <div class="settings-footer">
        <p class="submit">
            <?php submit_button(__('Save Email Settings', 'wp-app-core'), 'primary', 'submit', false, ['form' => 'platform-email-settings-form']); ?>
            <button type="button" id="reset-email-settings" class="button button-secondary">
                <?php _e('Reset to Default', 'wp-app-core'); ?>
            </button>
        </p>
    </div>
</div>
