<?php
/**
 * Security Session Tab Template
 *
 * @package     WP_App_Core
 * @subpackage  Views/Templates/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/templates/settings/tab-security-session.php
 *
 * Description: Security: Session Management settings tab
 *              - Session timeout management
 *              - Login protection (brute force prevention)
 *              - Login monitoring and tracking
 *
 * Dependencies:
 * - SecuritySessionModel (settings storage & sanitization)
 * - WordPress Settings API (form handling)
 * - security-session-tab-style.css (styling)
 * - security-session-tab-script.js (conditional logic & validation)
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial creation
 * - Session settings section
 * - Login protection section
 * - Login monitoring section
 */

if (!defined('ABSPATH')) {
    exit;
}

// Settings are passed from controller via extract()
// $settings variable is already available
?>

<div class="wrap wp-app-core-security-session-wrap">
    <div id="security-session-messages" class="notice" style="display:none;"></div>

    <form method="post" action="options.php" id="wp-app-core-security-session-form" class="wp-app-core-security-session-form">
        <?php
        settings_fields('platform_security_session');
        ?>
        <input type="hidden" name="reset_to_defaults" value="0">
        <input type="hidden" name="current_tab" value="security-session">

        <!-- Section 1: Session Settings -->
        <div class="settings-section">
            <h3><?php _e('Session Settings', 'wp-app-core'); ?></h3>
            <p class="description">
                <?php _e('Configure session timeout and management settings. These settings control how long users can remain logged in.', 'wp-app-core'); ?>
            </p>

            <table class="form-table" role="presentation">
                <tbody>
                    <!-- Session Idle Timeout -->
                    <tr>
                        <th scope="row">
                            <label for="session_idle_timeout"><?php _e('Session Idle Timeout', 'wp-app-core'); ?></label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="session_idle_timeout"
                                name="platform_security_session[session_idle_timeout]"
                                value="<?php echo esc_attr($settings['session_idle_timeout'] ?? 3600); ?>"
                                class="regular-text"
                                min="300"
                                step="60"
                            />
                            <p class="description">
                                <?php _e('Maximum idle time before logout (in seconds). Minimum: 300 seconds (5 minutes). Default: 3600 seconds (1 hour).', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Session Absolute Timeout -->
                    <tr>
                        <th scope="row">
                            <label for="session_absolute_timeout"><?php _e('Session Absolute Timeout', 'wp-app-core'); ?></label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="session_absolute_timeout"
                                name="platform_security_session[session_absolute_timeout]"
                                value="<?php echo esc_attr($settings['session_absolute_timeout'] ?? 43200); ?>"
                                class="regular-text"
                                min="600"
                                step="60"
                            />
                            <p class="description">
                                <?php _e('Maximum total session duration (in seconds). Minimum: 600 seconds (10 minutes). Default: 43200 seconds (12 hours).', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Concurrent Sessions Limit -->
                    <tr>
                        <th scope="row">
                            <label for="concurrent_sessions_limit"><?php _e('Concurrent Sessions Limit', 'wp-app-core'); ?></label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="concurrent_sessions_limit"
                                name="platform_security_session[concurrent_sessions_limit]"
                                value="<?php echo esc_attr($settings['concurrent_sessions_limit'] ?? 3); ?>"
                                class="small-text"
                                min="1"
                                max="10"
                            />
                            <p class="description">
                                <?php _e('Maximum number of concurrent sessions per user. Range: 1-10. Default: 3.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Force Logout Enabled -->
                    <tr>
                        <th scope="row">
                            <?php _e('Force Logout', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="platform_security_session[force_logout_enabled]"
                                    value="1"
                                    class="force-logout-toggle"
                                    <?php checked(!empty($settings['force_logout_enabled'])); ?>
                                />
                                <?php _e('Enable force logout when concurrent session limit is reached', 'wp-app-core'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, oldest session will be terminated when user exceeds concurrent session limit.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Remember Me Duration -->
                    <tr>
                        <th scope="row">
                            <label for="remember_me_duration"><?php _e('Remember Me Duration', 'wp-app-core'); ?></label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="remember_me_duration"
                                name="platform_security_session[remember_me_duration]"
                                value="<?php echo esc_attr($settings['remember_me_duration'] ?? 1209600); ?>"
                                class="regular-text"
                                min="86400"
                                step="86400"
                            />
                            <p class="description">
                                <?php _e('Duration for "Remember Me" feature (in seconds). Minimum: 86400 seconds (1 day). Default: 1209600 seconds (14 days).', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Section 2: Login Protection -->
        <div class="settings-section">
            <h3><?php _e('Login Protection', 'wp-app-core'); ?></h3>
            <p class="description">
                <?php _e('Configure brute force protection and login attempt limits. These settings protect against automated attacks.', 'wp-app-core'); ?>
            </p>

            <table class="form-table" role="presentation">
                <tbody>
                    <!-- Max Login Attempts -->
                    <tr>
                        <th scope="row">
                            <label for="max_login_attempts"><?php _e('Max Login Attempts', 'wp-app-core'); ?></label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="max_login_attempts"
                                name="platform_security_session[max_login_attempts]"
                                value="<?php echo esc_attr($settings['max_login_attempts'] ?? 5); ?>"
                                class="small-text"
                                min="3"
                                max="10"
                            />
                            <p class="description">
                                <?php _e('Maximum failed login attempts before lockout. Range: 3-10. Default: 5.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Lockout Duration -->
                    <tr>
                        <th scope="row">
                            <label for="lockout_duration"><?php _e('Lockout Duration', 'wp-app-core'); ?></label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="lockout_duration"
                                name="platform_security_session[lockout_duration]"
                                value="<?php echo esc_attr($settings['lockout_duration'] ?? 1800); ?>"
                                class="regular-text"
                                min="300"
                                step="60"
                            />
                            <p class="description">
                                <?php _e('Duration of account lockout after max attempts (in seconds). Minimum: 300 seconds (5 minutes). Default: 1800 seconds (30 minutes).', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Progressive Delays Enabled -->
                    <tr>
                        <th scope="row">
                            <?php _e('Progressive Delays', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="platform_security_session[progressive_delays_enabled]"
                                    value="1"
                                    <?php checked(!empty($settings['progressive_delays_enabled'])); ?>
                                />
                                <?php _e('Enable progressive delays between failed login attempts', 'wp-app-core'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Increases delay time between login attempts after each failed attempt to slow down brute force attacks.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- CAPTCHA After Failed Attempts -->
                    <tr>
                        <th scope="row">
                            <label for="captcha_after_failed_attempts"><?php _e('CAPTCHA Trigger', 'wp-app-core'); ?></label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="captcha_after_failed_attempts"
                                name="platform_security_session[captcha_after_failed_attempts]"
                                value="<?php echo esc_attr($settings['captcha_after_failed_attempts'] ?? 3); ?>"
                                class="small-text"
                                min="1"
                            />
                            <p class="description">
                                <?php _e('Show CAPTCHA after this many failed attempts. Minimum: 1. Default: 3.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Email Failed Login Notification -->
                    <tr>
                        <th scope="row">
                            <?php _e('Email Notifications', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="platform_security_session[email_failed_login_notification]"
                                    value="1"
                                    <?php checked(!empty($settings['email_failed_login_notification'])); ?>
                                />
                                <?php _e('Send email notification on repeated failed login attempts', 'wp-app-core'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Administrators will be notified when multiple failed login attempts are detected.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Section 3: Login Monitoring -->
        <div class="settings-section">
            <h3><?php _e('Login Monitoring', 'wp-app-core'); ?></h3>
            <p class="description">
                <?php _e('Configure login history and session monitoring. These settings help track user activity and detect suspicious behavior.', 'wp-app-core'); ?>
            </p>

            <table class="form-table" role="presentation">
                <tbody>
                    <!-- Login History Enabled -->
                    <tr>
                        <th scope="row">
                            <?php _e('Login History', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="platform_security_session[login_history_enabled]"
                                    value="1"
                                    class="login-history-toggle"
                                    <?php checked(!empty($settings['login_history_enabled'])); ?>
                                />
                                <?php _e('Enable login history tracking', 'wp-app-core'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Track all user login attempts (successful and failed) for security auditing.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Login History Limit -->
                    <tr class="login-history-option">
                        <th scope="row">
                            <label for="login_history_limit"><?php _e('Login History Limit', 'wp-app-core'); ?></label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="login_history_limit"
                                name="platform_security_session[login_history_limit]"
                                value="<?php echo esc_attr($settings['login_history_limit'] ?? 100); ?>"
                                class="small-text"
                                min="10"
                                max="1000"
                            />
                            <p class="description">
                                <?php _e('Number of login records to keep per user. Range: 10-1000. Default: 100.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Show Active Sessions -->
                    <tr>
                        <th scope="row">
                            <?php _e('Active Sessions Display', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="platform_security_session[show_active_sessions]"
                                    value="1"
                                    <?php checked(!empty($settings['show_active_sessions'])); ?>
                                />
                                <?php _e('Show active sessions to users in their profile', 'wp-app-core'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Users can view and manage their active sessions from their profile page.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Force Logout Suspicious Sessions -->
                    <tr>
                        <th scope="row">
                            <?php _e('Suspicious Session Handling', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="platform_security_session[force_logout_suspicious_sessions]"
                                    value="1"
                                    <?php checked(!empty($settings['force_logout_suspicious_sessions'])); ?>
                                />
                                <?php _e('Automatically logout suspicious sessions', 'wp-app-core'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Sessions with unusual activity patterns will be automatically terminated for security.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Email New Device Login -->
                    <tr>
                        <th scope="row">
                            <?php _e('New Device Notifications', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="platform_security_session[email_new_device_login]"
                                    value="1"
                                    <?php checked(!empty($settings['email_new_device_login'])); ?>
                                />
                                <?php _e('Send email notification when login from new device is detected', 'wp-app-core'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Users will be notified via email when their account is accessed from a previously unknown device.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Unusual Activity Detection -->
                    <tr>
                        <th scope="row">
                            <?php _e('Activity Monitoring', 'wp-app-core'); ?>
                        </th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="platform_security_session[unusual_activity_detection]"
                                    value="1"
                                    <?php checked(!empty($settings['unusual_activity_detection'])); ?>
                                />
                                <?php _e('Enable unusual activity detection', 'wp-app-core'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Monitor for unusual patterns such as rapid location changes, multiple failed attempts, etc.', 'wp-app-core'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </form>

    <!-- DEPRECATED: Per-tab buttons moved to page level (settings-page.php) -->
    <!-- Global scope pattern: All wp-app-* plugins use page-level buttons -->
    <!--
    <div class="settings-footer">
        <p class="submit">
            <?php // submit_button(__('Save Session Settings', 'wp-app-core'), 'primary', 'submit', false, ['form' => 'wp-app-core-security-session-form']); ?>
        </p>
    </div>
    -->
</div>
