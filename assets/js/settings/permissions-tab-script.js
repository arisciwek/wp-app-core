/**
 * Permission Matrix Script
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Settings
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/settings/permissions-tab-script.js
 *
 * Description: Handler untuk matrix permission
 *              Menangani update dan reset permission matrix
 *
 * Dependencies:
 * - jQuery
 * - wpAppCoreSettings (localized from controller)
 *
 * Changelog:
 * 1.1.0 - 2025-10-19
 * - Added: Disable save button during reset to prevent conflicts
 * - Enhanced: Re-enable save button on reset error
 * - Improved: Better UX with button state management
 *
 * 1.0.0 - 2025-10-19
 * - Initial implementation for wp-app-core
 * - Adapted from wp-customer pattern
 * - Uses native confirm dialog
 * - WordPress admin notices for feedback
 */
(function($) {
    'use strict';

    const PermissionMatrix = {
        init() {
            this.bindEvents();
            this.initTooltips();
            this.initResetButton();
        },

        bindEvents() {
            // Disable submit button on form submission
            $('.permissions-section form').on('submit', function() {
                $(this).find('button[type="submit"]').prop('disabled', true);
            });
        },

        initTooltips() {
            if ($.fn.tooltip) {
                $('.tooltip-icon').tooltip({
                    position: { my: "center bottom", at: "center top-10" }
                });
            }
        },

        initResetButton() {
            const self = this;
            $('.button-reset-permissions').on('click', function(e) {
                e.preventDefault();

                // Show native confirmation dialog
                if (confirm(wpAppCoreSettings.i18n.confirmReset || 'Are you sure you want to reset all permissions to default? This action cannot be undone.')) {
                    self.performReset($(this));
                }
            });
        },

        performReset($button) {
            const self = this;
            const originalText = $button.text();
            const $submitButton = $('.permissions-section form #submit, .permissions-section form [type="submit"]');

            // Set loading state for reset button
            $button.addClass('loading')
                   .prop('disabled', true)
                   .html('<span class="dashicons dashicons-update"></span> Resetting...');

            // Disable save button to prevent conflicts
            $submitButton.prop('disabled', true).addClass('disabled');

            // Perform AJAX reset
            $.ajax({
                url: wpAppCoreSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'reset_platform_permissions',
                    nonce: wpAppCoreSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show success notice
                        self.showNotice(response.data.message || 'Permissions reset successfully', 'success');
                        // Reload page after short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        self.showNotice(response.data.message || 'Failed to reset permissions', 'error');
                        // Reset button states
                        $button.removeClass('loading')
                               .prop('disabled', false)
                               .html('<span class="dashicons dashicons-image-rotate"></span> ' + originalText);
                        $submitButton.prop('disabled', false).removeClass('disabled');
                    }
                },
                error: function(xhr, status, error) {
                    self.showNotice('Server error while resetting permissions', 'error');
                    // Reset button states
                    $button.removeClass('loading')
                           .prop('disabled', false)
                           .html('<span class="dashicons dashicons-image-rotate"></span> ' + originalText);
                    $submitButton.prop('disabled', false).removeClass('disabled');
                }
            });
        },

        showNotice(message, type) {
            const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            const $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');

            // Insert after h1 or at the top of wrap
            if ($('.wrap h1').length) {
                $('.wrap h1').after($notice);
            } else {
                $('.wrap').prepend($notice);
            }

            // Auto-remove after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        if ($('.permissions-section').length) {
            PermissionMatrix.init();
        }
    });

})(jQuery);
