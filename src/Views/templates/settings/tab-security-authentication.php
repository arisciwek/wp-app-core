<?php
/**
 * Security Authentication Settings Tab Template
 *
 * @package     WP_App_Core
 * @subpackage  Views/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/templates/settings/tab-security-authentication.php
 *
 * Description: Template untuk security authentication settings
 *              - Password Policy
 *              - Two-Factor Authentication
 *              - Access Control
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial creation
 * - Password policy settings
 * - 2FA settings
 * - Access control settings
 */

if (!defined('ABSPATH')) {
    die;
}

// Verify nonce and capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Settings are passed from controller via extract()
// $settings variable is already available
?>

<div class="wrap">
    <div id="security-auth-messages"></div>

    <form method="post" action="options.php" class="wp-app-core-security-auth-form">
        <?php settings_fields('wp_app_core_security_authentication'); ?>

        <!-- Password Policy Settings Section -->
        <div class="settings-section">
            <h3><?php _e('Password Policy', 'wp-app-core'); ?></h3>
            <p class="description">
                <?php _e('Configure password requirements and policies for all platform users.', 'wp-app-core'); ?>
            </p>

            <table class="form-table">
                <!-- Force Strong Password -->
                <tr>
                    <th scope="row">
                        <?php _e('Enforce Strong Passwords', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="wp_app_core_security_authentication[force_strong_password]"
                                   value="1"
                                   <?php checked($settings['force_strong_password'], true); ?>>
                            <?php _e('Require strong passwords for all users', 'wp-app-core'); ?>
                        </label>
                        <p class="description">
                            <?php _e('When enabled, users must create passwords that meet the requirements below.', 'wp-app-core'); ?>
                        </p>
                    </td>
                </tr>

                <!-- Minimum Password Length -->
                <tr>
                    <th scope="row">
                        <label for="password_min_length"><?php _e('Minimum Length', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <input type="number"
                               id="password_min_length"
                               name="wp_app_core_security_authentication[password_min_length]"
                               value="<?php echo esc_attr($settings['password_min_length']); ?>"
                               min="8"
                               max="128"
                               class="small-text">
                        <?php _e('characters', 'wp-app-core'); ?>
                        <p class="description">
                            <?php _e('Minimum: 8, Maximum: 128', 'wp-app-core'); ?>
                        </p>
                    </td>
                </tr>

                <!-- Password Requirements -->
                <tr>
                    <th scope="row">
                        <?php _e('Password Requirements', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text">
                                <span><?php _e('Password Requirements', 'wp-app-core'); ?></span>
                            </legend>

                            <label>
                                <input type="checkbox"
                                       name="wp_app_core_security_authentication[password_require_uppercase]"
                                       value="1"
                                       <?php checked($settings['password_require_uppercase'], true); ?>>
                                <?php _e('At least one uppercase letter (A-Z)', 'wp-app-core'); ?>
                            </label><br>

                            <label>
                                <input type="checkbox"
                                       name="wp_app_core_security_authentication[password_require_lowercase]"
                                       value="1"
                                       <?php checked($settings['password_require_lowercase'], true); ?>>
                                <?php _e('At least one lowercase letter (a-z)', 'wp-app-core'); ?>
                            </label><br>

                            <label>
                                <input type="checkbox"
                                       name="wp_app_core_security_authentication[password_require_numbers]"
                                       value="1"
                                       <?php checked($settings['password_require_numbers'], true); ?>>
                                <?php _e('At least one number (0-9)', 'wp-app-core'); ?>
                            </label><br>

                            <label>
                                <input type="checkbox"
                                       name="wp_app_core_security_authentication[password_require_special_chars]"
                                       value="1"
                                       <?php checked($settings['password_require_special_chars'], true); ?>>
                                <?php _e('At least one special character (!@#$%^&*)', 'wp-app-core'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>

                <!-- Password Expiration -->
                <tr>
                    <th scope="row">
                        <label for="password_expiration_days"><?php _e('Password Expiration', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <input type="number"
                               id="password_expiration_days"
                               name="wp_app_core_security_authentication[password_expiration_days]"
                               value="<?php echo esc_attr($settings['password_expiration_days']); ?>"
                               min="0"
                               max="365"
                               class="small-text">
                        <?php _e('days', 'wp-app-core'); ?>
                        <p class="description">
                            <?php _e('Force password change after this many days. Set to 0 to disable.', 'wp-app-core'); ?>
                        </p>
                    </td>
                </tr>

                <!-- Password History -->
                <tr>
                    <th scope="row">
                        <label for="password_history_count"><?php _e('Password History', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <input type="number"
                               id="password_history_count"
                               name="wp_app_core_security_authentication[password_history_count]"
                               value="<?php echo esc_attr($settings['password_history_count']); ?>"
                               min="0"
                               max="24"
                               class="small-text">
                        <?php _e('passwords', 'wp-app-core'); ?>
                        <p class="description">
                            <?php _e('Prevent reusing the last N passwords. Maximum: 24', 'wp-app-core'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Two-Factor Authentication Section -->
        <div class="settings-section">
            <h3><?php _e('Two-Factor Authentication (2FA)', 'wp-app-core'); ?></h3>
            <p class="description">
                <?php _e('Add an extra layer of security by requiring a second form of authentication.', 'wp-app-core'); ?>
            </p>

            <table class="form-table">
                <!-- Enable 2FA -->
                <tr>
                    <th scope="row">
                        <?php _e('Enable 2FA', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="wp_app_core_security_authentication[twofa_enabled]"
                                   value="1"
                                   class="twofa-toggle"
                                   <?php checked($settings['twofa_enabled'], true); ?>>
                            <?php _e('Enable two-factor authentication for platform users', 'wp-app-core'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Users will be prompted to set up 2FA on their next login.', 'wp-app-core'); ?>
                        </p>
                    </td>
                </tr>

                <!-- 2FA Methods -->
                <tr class="twofa-option">
                    <th scope="row">
                        <?php _e('Authentication Methods', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text">
                                <span><?php _e('Authentication Methods', 'wp-app-core'); ?></span>
                            </legend>

                            <label>
                                <input type="checkbox"
                                       name="wp_app_core_security_authentication[twofa_methods][]"
                                       value="authenticator"
                                       <?php checked(in_array('authenticator', $settings['twofa_methods'])); ?>>
                                <?php _e('Authenticator App (Google Authenticator, Authy)', 'wp-app-core'); ?>
                            </label><br>

                            <label>
                                <input type="checkbox"
                                       name="wp_app_core_security_authentication[twofa_methods][]"
                                       value="email"
                                       <?php checked(in_array('email', $settings['twofa_methods'])); ?>>
                                <?php _e('Email Code', 'wp-app-core'); ?>
                            </label><br>

                            <label>
                                <input type="checkbox"
                                       name="wp_app_core_security_authentication[twofa_methods][]"
                                       value="sms"
                                       <?php checked(in_array('sms', $settings['twofa_methods'])); ?>>
                                <?php _e('SMS Code (requires SMS gateway)', 'wp-app-core'); ?>
                            </label>
                        </fieldset>
                        <p class="description">
                            <?php _e('Select which methods users can use for 2FA. At least one method must be selected.', 'wp-app-core'); ?>
                        </p>
                    </td>
                </tr>

                <!-- Force 2FA for Roles -->
                <tr class="twofa-option">
                    <th scope="row">
                        <?php _e('Enforce 2FA for Roles', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <select name="wp_app_core_security_authentication[twofa_force_for_roles][]"
                                multiple
                                class="regular-text"
                                style="height: 150px;">
                            <?php
                            require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-role-manager.php';
                            $platform_roles = WP_App_Core_Role_Manager::getRoles();
                            foreach ($platform_roles as $role_slug => $role_name) {
                                $selected = in_array($role_slug, $settings['twofa_force_for_roles']) ? 'selected' : '';
                                echo '<option value="' . esc_attr($role_slug) . '" ' . $selected . '>' . esc_html($role_name) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description">
                            <?php _e('Hold Ctrl/Cmd to select multiple roles. Selected roles will be required to use 2FA.', 'wp-app-core'); ?>
                        </p>
                    </td>
                </tr>

                <!-- Backup Codes -->
                <tr class="twofa-option">
                    <th scope="row">
                        <?php _e('Backup Codes', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="wp_app_core_security_authentication[twofa_backup_codes]"
                                   value="1"
                                   <?php checked($settings['twofa_backup_codes'], true); ?>>
                            <?php _e('Allow users to generate backup codes', 'wp-app-core'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Backup codes can be used to login when 2FA device is unavailable.', 'wp-app-core'); ?>
                        </p>
                    </td>
                </tr>

                <!-- Grace Period -->
                <tr class="twofa-option">
                    <th scope="row">
                        <label for="twofa_grace_period_days"><?php _e('Setup Grace Period', 'wp-app-core'); ?></label>
                    </th>
                    <td>
                        <input type="number"
                               id="twofa_grace_period_days"
                               name="wp_app_core_security_authentication[twofa_grace_period_days]"
                               value="<?php echo esc_attr($settings['twofa_grace_period_days']); ?>"
                               min="0"
                               max="30"
                               class="small-text">
                        <?php _e('days', 'wp-app-core'); ?>
                        <p class="description">
                            <?php _e('Number of days users have to set up 2FA before being locked out.', 'wp-app-core'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Access Control Section -->
        <div class="settings-section">
            <h3><?php _e('Access Control', 'wp-app-core'); ?></h3>
            <p class="description">
                <?php _e('Restrict access based on IP address, country, time, and device.', 'wp-app-core'); ?>
            </p>

            <table class="form-table">
                <!-- IP Whitelist -->
                <tr>
                    <th scope="row">
                        <?php _e('IP Whitelist', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="wp_app_core_security_authentication[ip_whitelist_enabled]"
                                   value="1"
                                   class="ip-whitelist-toggle"
                                   <?php checked($settings['ip_whitelist_enabled'], true); ?>>
                            <?php _e('Enable IP whitelist (only allow specific IPs)', 'wp-app-core'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Warning: This may lock you out if configured incorrectly!', 'wp-app-core'); ?>
                        </p>
                        <div class="ip-whitelist-options" style="margin-top: 10px;">
                            <textarea name="wp_app_core_security_authentication[ip_whitelist]"
                                      rows="5"
                                      class="large-text code"
                                      placeholder="192.168.1.1&#10;10.0.0.0/8&#10;203.0.113.0/24"><?php
                                echo esc_textarea(is_array($settings['ip_whitelist']) ? implode("\n", $settings['ip_whitelist']) : '');
                            ?></textarea>
                            <p class="description">
                                <?php _e('One IP address or CIDR block per line. Your current IP: ', 'wp-app-core'); ?>
                                <strong><?php echo esc_html($_SERVER['REMOTE_ADDR']); ?></strong>
                            </p>
                        </div>
                    </td>
                </tr>

                <!-- IP Blacklist -->
                <tr>
                    <th scope="row">
                        <?php _e('IP Blacklist', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="wp_app_core_security_authentication[ip_blacklist_enabled]"
                                   value="1"
                                   class="ip-blacklist-toggle"
                                   <?php checked($settings['ip_blacklist_enabled'], true); ?>>
                            <?php _e('Enable IP blacklist (block specific IPs)', 'wp-app-core'); ?>
                        </label>
                        <div class="ip-blacklist-options" style="margin-top: 10px;">
                            <textarea name="wp_app_core_security_authentication[ip_blacklist]"
                                      rows="5"
                                      class="large-text code"
                                      placeholder="192.168.1.100&#10;10.0.0.50"><?php
                                echo esc_textarea(is_array($settings['ip_blacklist']) ? implode("\n", $settings['ip_blacklist']) : '');
                            ?></textarea>
                            <p class="description">
                                <?php _e('One IP address per line. These IPs will be completely blocked.', 'wp-app-core'); ?>
                            </p>
                        </div>
                    </td>
                </tr>

                <!-- Admin Access Hours -->
                <tr>
                    <th scope="row">
                        <?php _e('Admin Access Hours', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="wp_app_core_security_authentication[admin_access_hours_enabled]"
                                   value="1"
                                   class="access-hours-toggle"
                                   <?php checked($settings['admin_access_hours_enabled'], true); ?>>
                            <?php _e('Restrict admin access to specific hours', 'wp-app-core'); ?>
                        </label>
                        <div class="access-hours-options" style="margin-top: 10px;">
                            <label>
                                <?php _e('From:', 'wp-app-core'); ?>
                                <input type="time"
                                       name="wp_app_core_security_authentication[admin_access_hours_start]"
                                       value="<?php echo esc_attr($settings['admin_access_hours_start']); ?>">
                            </label>
                            <label style="margin-left: 15px;">
                                <?php _e('To:', 'wp-app-core'); ?>
                                <input type="time"
                                       name="wp_app_core_security_authentication[admin_access_hours_end]"
                                       value="<?php echo esc_attr($settings['admin_access_hours_end']); ?>">
                            </label>
                            <p class="description">
                                <?php _e('Admin login will only be allowed during these hours (server timezone).', 'wp-app-core'); ?>
                            </p>
                        </div>
                    </td>
                </tr>

                <!-- Maintenance Mode -->
                <tr>
                    <th scope="row">
                        <?php _e('Maintenance Mode', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="wp_app_core_security_authentication[maintenance_mode_enabled]"
                                   value="1"
                                   <?php checked($settings['maintenance_mode_enabled'], true); ?>>
                            <?php _e('Enable maintenance mode', 'wp-app-core'); ?>
                        </label>
                        <p class="description">
                            <?php _e('When enabled, only administrators can access the site. All other users will see a maintenance message.', 'wp-app-core'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <p class="submit">
            <?php submit_button(__('Save Security Settings', 'wp-app-core'), 'primary', 'submit', false); ?>
            <button type="button" id="reset-security-authentication" class="button button-secondary" style="margin-left: 10px;">
                <?php _e('Reset to Default', 'wp-app-core'); ?>
            </button>
        </p>
    </form>
</div>
