/**
 * Email Settings Tab Script
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/settings/email-tab-script.js
 *
 * Description: Handles email settings tab functionality
 *              - Reset to default functionality
 *
 * Dependencies:
 * - jQuery
 * - WordPress Settings API
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial implementation
 * - Reset to default functionality
 */

jQuery(document).ready(function($) {

    /**
     * Handle Reset to Default
     */
    $('#reset-email-settings').on('click', function(e) {
        e.preventDefault();

        if (!confirm('Are you sure you want to reset all email settings to their default values?\n\nThis action cannot be undone.')) {
            return;
        }

        const $resetBtn = $(this);
        const $submitBtn = $('#submit');
        const form = $('#platform-email-settings-form');

        // Disable buttons during reset
        $resetBtn.prop('disabled', true).text('Resetting...');
        $submitBtn.prop('disabled', true);

        $.ajax({
            url: wpAppCoreSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'reset_email',
                nonce: wpAppCoreSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showMessage('Settings reset to default successfully.', 'success');

                    // Reload page after short delay to show updated values
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showMessage(response.data.message || 'Failed to reset settings.', 'error');
                    $resetBtn.prop('disabled', false).text('Reset to Default');
                    $submitBtn.prop('disabled', false);
                }
            },
            error: function() {
                showMessage('An error occurred while resetting settings.', 'error');
                $resetBtn.prop('disabled', false).text('Reset to Default');
                $submitBtn.prop('disabled', false);
            }
        });
    });

    /**
     * Show message
     */
    function showMessage(message, type) {
        const $messageDiv = $('<div>')
            .addClass('notice notice-' + type + ' is-dismissible')
            .html('<p>' + message + '</p>');

        $('.wrap h1').after($messageDiv);

        // Auto dismiss after 3 seconds
        setTimeout(function() {
            $messageDiv.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

});
