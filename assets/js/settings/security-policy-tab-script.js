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
    // DEPRECATED: Reset handler moved to page-level
    // Using global settings-reset-helper.js (auto-initialize)

    console.log('[Security Policy Tab] Using global handlers + custom warnings');

    /**
     * CUSTOM LOGIC: XML-RPC toggle warning
     * This is tab-specific behavior that stays here
     */
    $('input[name="platform_security_policy[disable_xmlrpc]"]').on('change', function() {
        if ($(this).is(':checked')) {
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
    });

});
