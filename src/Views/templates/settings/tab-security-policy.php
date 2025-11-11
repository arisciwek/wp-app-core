<?php
/**
 * Security Policy & Audit Tab Template
 *
 * @package     WP_App_Core
 * @subpackage  Views/Templates/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/templates/settings/tab-security-policy.php
 *
 * Description: Security: Policy & Audit settings tab
 *              - Data security policies
 *              - Activity logging configuration
 *              - Audit & compliance settings
 *              - Advanced security features
 *
 * Dependencies:
 * - SecurityPolicyModel (settings storage & sanitization)
 * - WordPress Settings API (form handling)
 * - security-policy-tab-style.css (styling)
 * - security-policy-tab-script.js (conditional logic & validation)
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial creation
 * - Data security section
 * - Activity logging section
 * - Audit & compliance section
 * - Advanced security section
 */

if (!defined('ABSPATH')) {
    exit;
}

// Settings are passed from controller via extract()
// $settings variable is already available
?>

<div class="wrap wp-app-core-security-policy-wrap">
    <div id="security-policy-messages" class="notice" style="display:none;"></div>

    <form method="post" action="options.php" id="wp-app-core-security-policy-form" class="wp-app-core-security-policy-form">
        <?php
        settings_fields('platform_security_policy');
        ?>

        <!-- Section 1: Data Security -->
        <div class="settings-section">
            <h3><?php _e('Data Security', 'wp-app-core'); ?></h3>
            <p class="description">
                <?php _e('Configure data protection and security policies. These settings control how sensitive data is handled and protected.', 'wp-app-core'); ?>
            </p>

            <table class="form-table" role="presentation">
                <tbody>
                    <!-- Data Encryption -->
                    <tr>
                        <th scope="row">
                            <?php _e('Data Encryption', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="platform_security_policy[data_encryption_enabled]"
                                    value="1"
                                    <?php checked(!empty($settings['data_encryption_enabled'])); ?>
                                />
                                <?php _e('Enable data encryption for sensitive information', 'wp-app-core'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Encrypt sensitive data in database (user credentials, personal information, etc.).', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Force SSL Admin -->
                    <tr>
                        <th scope="row">
                            <?php _e('SSL/HTTPS', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="platform_security_policy[force_ssl_admin]"
                                    value="1"
                                    <?php checked(!empty($settings['force_ssl_admin'])); ?>
                                />
                                <?php _e('Force SSL for admin area', 'wp-app-core'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Require HTTPS connection for all admin pages to prevent data interception.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Secure Cookies -->
                    <tr>
                        <th scope="row">
                            <?php _e('Cookie Security', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <fieldset>
                                <label>
                                    <input
                                        type="checkbox"
                                        name="platform_security_policy[secure_cookies]"
                                        value="1"
                                        <?php checked(!empty($settings['secure_cookies'])); ?>
                                    />
                                    <?php _e('Use secure cookies (HTTPS only)', 'wp-app-core'); ?>
                                </label>
                                <br>
                                <label>
                                    <input
                                        type="checkbox"
                                        name="platform_security_policy[cookie_httponly]"
                                        value="1"
                                        <?php checked(!empty($settings['cookie_httponly'])); ?>
                                    />
                                    <?php _e('Enable HttpOnly flag (prevent JavaScript access)', 'wp-app-core'); ?>
                                </label>
                            </fieldset>
                            <p class="description">
                                <?php _e('Enhanced cookie security to prevent XSS and session hijacking attacks.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Cookie SameSite -->
                    <tr>
                        <th scope="row">
                            <label for="cookie_samesite"><?php _e('Cookie SameSite Policy', 'wp-app-core'); ?></label>
                        </th>
                        <td>
                            <select
                                id="cookie_samesite"
                                name="platform_security_policy[cookie_samesite]"
                                class="regular-text"
                            >
                                <option value="Strict" <?php selected($settings['cookie_samesite'] ?? 'Strict', 'Strict'); ?>>
                                    <?php _e('Strict (Most secure)', 'wp-app-core'); ?>
                                </option>
                                <option value="Lax" <?php selected($settings['cookie_samesite'] ?? 'Strict', 'Lax'); ?>>
                                    <?php _e('Lax (Balanced)', 'wp-app-core'); ?>
                                </option>
                                <option value="None" <?php selected($settings['cookie_samesite'] ?? 'Strict', 'None'); ?>>
                                    <?php _e('None (Allow cross-site)', 'wp-app-core'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('Controls whether cookies are sent with cross-site requests. Strict is recommended for security.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Max Upload Size -->
                    <tr>
                        <th scope="row">
                            <label for="max_upload_size"><?php _e('Max Upload Size', 'wp-app-core'); ?></label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="max_upload_size"
                                name="platform_security_policy[max_upload_size]"
                                value="<?php echo esc_attr($settings['max_upload_size'] ?? 5242880); ?>"
                                class="regular-text"
                                min="1024"
                                step="1024"
                            />
                            <p class="description">
                                <?php _e('Maximum file upload size in bytes. Minimum: 1024 bytes (1KB). Default: 5242880 bytes (5MB).', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Disable File Editing -->
                    <tr>
                        <th scope="row">
                            <?php _e('File Editing', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="platform_security_policy[disable_file_editing]"
                                    value="1"
                                    <?php checked(!empty($settings['disable_file_editing'])); ?>
                                />
                                <?php _e('Disable theme and plugin file editor', 'wp-app-core'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Prevent editing theme and plugin files from WordPress admin for security.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Section 2: Activity Logging -->
        <div class="settings-section">
            <h3><?php _e('Activity Logging', 'wp-app-core'); ?></h3>
            <p class="description">
                <?php _e('Configure activity logging for audit trails and security monitoring. Track user actions and system events.', 'wp-app-core'); ?>
            </p>

            <table class="form-table" role="presentation">
                <tbody>
                    <!-- Activity Logs Enabled -->
                    <tr>
                        <th scope="row">
                            <?php _e('Activity Logging', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="platform_security_policy[activity_logs_enabled]"
                                    value="1"
                                    class="activity-logs-toggle"
                                    <?php checked(!empty($settings['activity_logs_enabled'])); ?>
                                />
                                <?php _e('Enable activity logging', 'wp-app-core'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Track all user activities and system events for security auditing and compliance.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Log Retention Days -->
                    <tr class="activity-logs-option">
                        <th scope="row">
                            <label for="log_retention_days"><?php _e('Log Retention Period', 'wp-app-core'); ?></label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="log_retention_days"
                                name="platform_security_policy[log_retention_days]"
                                value="<?php echo esc_attr($settings['log_retention_days'] ?? 90); ?>"
                                class="small-text"
                                min="7"
                                max="365"
                            />
                            <?php _e('days', 'wp-app-core'); ?>
                            <p class="description">
                                <?php _e('Number of days to keep activity logs. Range: 7-365 days. Default: 90 days.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Log Events -->
                    <tr class="activity-logs-option">
                        <th scope="row">
                            <?php _e('Events to Log', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <fieldset>
                                <label>
                                    <input
                                        type="checkbox"
                                        name="platform_security_policy[log_user_login_logout]"
                                        value="1"
                                        <?php checked(!empty($settings['log_user_login_logout'])); ?>
                                    />
                                    <?php _e('User login and logout', 'wp-app-core'); ?>
                                </label>
                                <br>
                                <label>
                                    <input
                                        type="checkbox"
                                        name="platform_security_policy[log_settings_changes]"
                                        value="1"
                                        <?php checked(!empty($settings['log_settings_changes'])); ?>
                                    />
                                    <?php _e('Settings changes', 'wp-app-core'); ?>
                                </label>
                                <br>
                                <label>
                                    <input
                                        type="checkbox"
                                        name="platform_security_policy[log_role_permission_changes]"
                                        value="1"
                                        <?php checked(!empty($settings['log_role_permission_changes'])); ?>
                                    />
                                    <?php _e('Role and permission changes', 'wp-app-core'); ?>
                                </label>
                                <br>
                                <label>
                                    <input
                                        type="checkbox"
                                        name="platform_security_policy[log_data_exports]"
                                        value="1"
                                        <?php checked(!empty($settings['log_data_exports'])); ?>
                                    />
                                    <?php _e('Data exports', 'wp-app-core'); ?>
                                </label>
                                <br>
                                <label>
                                    <input
                                        type="checkbox"
                                        name="platform_security_policy[log_failed_logins]"
                                        value="1"
                                        <?php checked(!empty($settings['log_failed_logins'])); ?>
                                    />
                                    <?php _e('Failed login attempts', 'wp-app-core'); ?>
                                </label>
                                <br>
                                <label>
                                    <input
                                        type="checkbox"
                                        name="platform_security_policy[log_critical_actions]"
                                        value="1"
                                        <?php checked(!empty($settings['log_critical_actions'])); ?>
                                    />
                                    <?php _e('Critical actions (user deletion, data purge, etc.)', 'wp-app-core'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Section 3: Audit & Compliance -->
        <div class="settings-section">
            <h3><?php _e('Audit & Compliance', 'wp-app-core'); ?></h3>
            <p class="description">
                <?php _e('Configure audit trails and compliance features. Monitor security events and generate compliance reports.', 'wp-app-core'); ?>
            </p>

            <table class="form-table" role="presentation">
                <tbody>
                    <!-- Export Logs -->
                    <tr>
                        <th scope="row">
                            <?php _e('Log Export', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="platform_security_policy[export_logs_enabled]"
                                    value="1"
                                    <?php checked(!empty($settings['export_logs_enabled'])); ?>
                                />
                                <?php _e('Enable log export functionality', 'wp-app-core'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Allow administrators to export activity logs for external analysis and archival.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- User Access Reports -->
                    <tr>
                        <th scope="row">
                            <?php _e('Access Reports', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="platform_security_policy[user_access_reports_enabled]"
                                    value="1"
                                    <?php checked(!empty($settings['user_access_reports_enabled'])); ?>
                                />
                                <?php _e('Enable user access reports', 'wp-app-core'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Generate reports showing user access patterns and permission usage.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Security Event Notifications -->
                    <tr>
                        <th scope="row">
                            <?php _e('Event Notifications', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="platform_security_policy[security_event_notifications]"
                                    value="1"
                                    class="security-notifications-toggle"
                                    <?php checked(!empty($settings['security_event_notifications'])); ?>
                                />
                                <?php _e('Enable security event notifications', 'wp-app-core'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Send email notifications for critical security events.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Notification Events -->
                    <tr class="security-notifications-option">
                        <th scope="row">
                            <?php _e('Notify On', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <fieldset>
                                <label>
                                    <input
                                        type="checkbox"
                                        name="platform_security_policy[notify_new_admin_user]"
                                        value="1"
                                        <?php checked(!empty($settings['notify_new_admin_user'])); ?>
                                    />
                                    <?php _e('New admin user created', 'wp-app-core'); ?>
                                </label>
                                <br>
                                <label>
                                    <input
                                        type="checkbox"
                                        name="platform_security_policy[notify_role_changes]"
                                        value="1"
                                        <?php checked(!empty($settings['notify_role_changes'])); ?>
                                    />
                                    <?php _e('User role changes', 'wp-app-core'); ?>
                                </label>
                                <br>
                                <label>
                                    <input
                                        type="checkbox"
                                        name="platform_security_policy[notify_settings_modified]"
                                        value="1"
                                        <?php checked(!empty($settings['notify_settings_modified'])); ?>
                                    />
                                    <?php _e('Critical settings modified', 'wp-app-core'); ?>
                                </label>
                                <br>
                                <label>
                                    <input
                                        type="checkbox"
                                        name="platform_security_policy[notify_multiple_failed_logins]"
                                        value="1"
                                        <?php checked(!empty($settings['notify_multiple_failed_logins'])); ?>
                                    />
                                    <?php _e('Multiple failed login attempts', 'wp-app-core'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <!-- Compliance Mode -->
                    <tr>
                        <th scope="row">
                            <label for="compliance_mode"><?php _e('Compliance Mode', 'wp-app-core'); ?></label>
                        </th>
                        <td>
                            <select
                                id="compliance_mode"
                                name="platform_security_policy[compliance_mode]"
                                class="regular-text"
                            >
                                <option value="none" <?php selected($settings['compliance_mode'] ?? 'none', 'none'); ?>>
                                    <?php _e('None', 'wp-app-core'); ?>
                                </option>
                                <option value="gdpr" <?php selected($settings['compliance_mode'] ?? 'none', 'gdpr'); ?>>
                                    <?php _e('GDPR (EU)', 'wp-app-core'); ?>
                                </option>
                                <option value="ccpa" <?php selected($settings['compliance_mode'] ?? 'none', 'ccpa'); ?>>
                                    <?php _e('CCPA (California)', 'wp-app-core'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('Select compliance framework for data protection regulations.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Section 4: Advanced Security -->
        <div class="settings-section">
            <h3><?php _e('Advanced Security', 'wp-app-core'); ?></h3>
            <p class="description">
                <?php _e('Advanced security features and hardening options. Configure security headers and API access controls.', 'wp-app-core'); ?>
            </p>

            <table class="form-table" role="presentation">
                <tbody>
                    <!-- Disable XML-RPC -->
                    <tr>
                        <th scope="row">
                            <?php _e('XML-RPC', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="platform_security_policy[disable_xmlrpc]"
                                    value="1"
                                    <?php checked(!empty($settings['disable_xmlrpc'])); ?>
                                />
                                <?php _e('Disable XML-RPC protocol', 'wp-app-core'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Disable XML-RPC to prevent DDoS and brute force attacks. Only disable if not using remote publishing.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Disable REST API Anonymous -->
                    <tr>
                        <th scope="row">
                            <?php _e('REST API', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="platform_security_policy[disable_rest_api_anonymous]"
                                    value="1"
                                    <?php checked(!empty($settings['disable_rest_api_anonymous'])); ?>
                                />
                                <?php _e('Disable REST API for anonymous users', 'wp-app-core'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Require authentication for REST API access to prevent data exposure.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Security Headers -->
                    <tr>
                        <th scope="row">
                            <?php _e('Security Headers', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="platform_security_policy[security_headers_enabled]"
                                    value="1"
                                    class="security-headers-toggle"
                                    <?php checked(!empty($settings['security_headers_enabled'])); ?>
                                />
                                <?php _e('Enable security headers', 'wp-app-core'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Add HTTP security headers to protect against common web vulnerabilities.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- X-Frame-Options -->
                    <tr class="security-headers-option">
                        <th scope="row">
                            <label for="x_frame_options"><?php _e('X-Frame-Options', 'wp-app-core'); ?></label>
                        </th>
                        <td>
                            <select
                                id="x_frame_options"
                                name="platform_security_policy[x_frame_options]"
                                class="regular-text"
                            >
                                <option value="DENY" <?php selected($settings['x_frame_options'] ?? 'SAMEORIGIN', 'DENY'); ?>>
                                    <?php _e('DENY (No frames allowed)', 'wp-app-core'); ?>
                                </option>
                                <option value="SAMEORIGIN" <?php selected($settings['x_frame_options'] ?? 'SAMEORIGIN', 'SAMEORIGIN'); ?>>
                                    <?php _e('SAMEORIGIN (Same site only)', 'wp-app-core'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('Prevent clickjacking attacks by controlling iframe embedding.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Database Backup -->
                    <tr>
                        <th scope="row">
                            <?php _e('Database Backup', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="platform_security_policy[database_backup_enabled]"
                                    value="1"
                                    <?php checked(!empty($settings['database_backup_enabled'])); ?>
                                />
                                <?php _e('Enable automated database backups', 'wp-app-core'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Automatically backup database for disaster recovery.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Auto Security Updates -->
                    <tr>
                        <th scope="row">
                            <?php _e('Security Updates', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="platform_security_policy[auto_security_updates]"
                                    value="1"
                                    <?php checked(!empty($settings['auto_security_updates'])); ?>
                                />
                                <?php _e('Enable automatic security updates', 'wp-app-core'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Automatically install WordPress security updates to stay protected.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </form>

    <!-- Sticky Footer with Action Buttons -->
    <div class="settings-footer">
        <p class="submit">
            <?php submit_button(__('Save Policy Settings', 'wp-app-core'), 'primary', 'submit', false, ['form' => 'wp-app-core-security-policy-form']); ?>
            <button type="button" id="reset-security-policy" class="button button-secondary">
                <?php _e('Reset to Default', 'wp-app-core'); ?>
            </button>
        </p>
    </div>
</div>
