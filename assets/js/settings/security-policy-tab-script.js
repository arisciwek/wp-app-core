/**
 * Security Policy Settings Tab Script
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Settings
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/settings/security-policy-tab-script.js
 *
 * Description: Handles security policy settings tab functionality
 *              - Reset to default functionality using WPModal
 *              - XML-RPC toggle warnings
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
    console.log('[Security Policy Tab] 🔄 Initializing...');

    /**
     * Toggle Activity Logs Options
     * Adds/removes class to body for CSS pointer-events control
     */
    function toggleActivityLogsOptions() {
        const isEnabled = $('.activity-logs-toggle').is(':checked');
        console.log('[Security Policy Tab] Activity logs enabled:', isEnabled);

        if (isEnabled) {
            $('body').addClass('activity-logs-enabled');
        } else {
            $('body').removeClass('activity-logs-enabled');
        }
    }

    $('.activity-logs-toggle').on('change', toggleActivityLogsOptions);
    toggleActivityLogsOptions(); // Initial state

    /**
     * Toggle Security Notifications Options
     */
    function toggleSecurityNotificationsOptions() {
        const isEnabled = $('.security-notifications-toggle').is(':checked');
        console.log('[Security Policy Tab] Security notifications enabled:', isEnabled);

        if (isEnabled) {
            $('body').addClass('security-notifications-enabled');
        } else {
            $('body').removeClass('security-notifications-enabled');
        }
    }

    $('.security-notifications-toggle').on('change', toggleSecurityNotificationsOptions);
    toggleSecurityNotificationsOptions(); // Initial state

    /**
     * Toggle Security Headers Options
     */
    function toggleSecurityHeadersOptions() {
        const isEnabled = $('.security-headers-toggle').is(':checked');
        console.log('[Security Policy Tab] Security headers enabled:', isEnabled);

        if (isEnabled) {
            $('body').addClass('security-headers-enabled');
        } else {
            $('body').removeClass('security-headers-enabled');
        }
    }

    $('.security-headers-toggle').on('change', toggleSecurityHeadersOptions);
    toggleSecurityHeadersOptions(); // Initial state

    /**
     * CUSTOM LOGIC: XML-RPC toggle warning
     */
    $('input[name="platform_security_policy[disable_xmlrpc]"]').on('change', function() {
        if ($(this).is(':checked')) {
            if (typeof WPModal !== 'undefined') {
                WPModal.confirm({
                    title: 'Disable XML-RPC?',
                    message: 'WARNING: Disabling XML-RPC will prevent remote publishing and some mobile apps from working.\n\nAre you sure you want to disable XML-RPC?',
                    danger: true,
                    confirmLabel: 'Disable XML-RPC',
                    onConfirm: function() {
                        // Keep checkbox checked
                    },
                    onCancel: function() {
                        // Uncheck if cancelled
                        $('input[name="platform_security_policy[disable_xmlrpc]"]').prop('checked', false);
                    }
                });
            }
        }
    });

    console.log('[Security Policy Tab] ✅ Initialization complete');
});
