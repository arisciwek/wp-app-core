/**
 * Security Session Settings Tab Script
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Settings
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/settings/security-session-tab-script.js
 *
 * Description: Handles security session settings tab functionality
 *              - Reset to default functionality using WPModal
 *              - Session timeout validation
 *
 * Dependencies:
 * - jQuery
 * - WPModal (wp-modal plugin)
 * - settings-reset-helper.js
 *
 * Changelog:
 * 1.1.0 - 2025-11-11
 * - Migrated to WPModal confirmation
 * - Using settings-reset-helper
 *
 * 1.0.0 - 2025-10-19
 * - Initial implementation
 */

jQuery(document).ready(function($) {
    console.log('[Security Session Tab] 🔄 Initializing...');

    /**
     * Toggle Login History Options
     * Adds/removes class to body for CSS pointer-events control
     */
    function toggleLoginHistoryOptions() {
        const isEnabled = $('.login-history-toggle').is(':checked');
        console.log('[Security Session Tab] Login history enabled:', isEnabled);

        if (isEnabled) {
            $('body').addClass('login-history-enabled');
        } else {
            $('body').removeClass('login-history-enabled');
        }
    }

    $('.login-history-toggle').on('change', toggleLoginHistoryOptions);
    toggleLoginHistoryOptions(); // Initial state

    /**
     * CUSTOM LOGIC: Validate session timeout values
     */
    $('form[id^="wp-app-core-security-session"]').on('submit', function(e) {
        const idleTimeout = parseInt($('input[name="platform_security_session[session_idle_timeout]"]').val());
        const absoluteTimeout = parseInt($('input[name="platform_security_session[session_absolute_timeout]"]').val());

        if (idleTimeout >= absoluteTimeout) {
            e.preventDefault();

            if (typeof WPModal !== 'undefined') {
                WPModal.confirm({
                    title: 'Session Timeout Warning',
                    message: 'Session idle timeout is greater than or equal to absolute timeout.\n\nThis means users may be logged out by absolute timeout before idle timeout takes effect.\n\nContinue anyway?',
                    danger: false,
                    confirmLabel: 'Continue',
                    onConfirm: function() {
                        // Submit form
                        $('form[id^="wp-app-core-security-session"]').off('submit').submit();
                    }
                });
            } else {
                if (!confirm('Session idle timeout is greater than or equal to absolute timeout. Continue anyway?')) {
                    return false;
                }
                // Continue with submit
                $(this).off('submit').submit();
            }

            return false;
        }
    });

    console.log('[Security Session Tab] ✅ Initialization complete');
});
