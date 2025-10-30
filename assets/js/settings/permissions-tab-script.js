/**
 * Permission Matrix Script
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Settings
 * @version     1.2.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/settings/permissions-tab-script.js
 *
 * Description: Handler untuk matrix permission
 *              Menangani update dan reset permission matrix
 *              INCLUDES RACE CONDITION PROTECTION
 *
 * Dependencies:
 * - jQuery
 * - wpAppCoreSettings (localized from controller)
 *
 * Changelog:
 * 1.2.0 - 2025-10-30
 * - CRITICAL HOTFIX: Fixed checkbox disable timing bug (TODO-3091)
 * - Split lockPage() into lockPageForSave() and lockPageForReset()
 * - lockPageForSave(): Disables buttons only (checkboxes must be enabled for form submit)
 * - lockPageForReset(): Disables everything (safe for AJAX operation)
 * - Fixed bug: disabled checkboxes were not being submitted in POST data
 * - Removed 1.5s delay - immediate reload after reset
 * - Now permissions save correctly
 *
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

        /**
         * Lock page for form submission
         * Disables buttons only - checkboxes must remain enabled for form data
         */
        lockPageForSave() {
            // Disable ALL buttons (reset + save)
            $('.button-reset-permissions, button[type="submit"]').prop('disabled', true);

            // DO NOT disable checkboxes - they need to be submitted!
            // Add visual loading indicator to body
            $('body').addClass('permission-operation-in-progress');
        },

        /**
         * Lock page for reset operation
         * Disables everything including checkboxes (AJAX operation, no form submit)
         */
        lockPageForReset() {
            // Disable ALL buttons (reset + save)
            $('.button-reset-permissions, button[type="submit"]').prop('disabled', true);

            // Disable ALL checkboxes (safe for AJAX, not form submit)
            $('.permission-checkbox').prop('disabled', true);

            // Add visual loading indicator to body
            $('body').addClass('permission-operation-in-progress');
        },

        /**
         * Unlock page (for error recovery only)
         */
        unlockPage() {
            $('.button-reset-permissions, button[type="submit"]').prop('disabled', false);
            $('.permission-checkbox').prop('disabled', false);
            $('body').removeClass('permission-operation-in-progress');
        },

        bindEvents() {
            const self = this;

            // Handle form submission with race condition protection
            $('.permissions-section form').on('submit', function(e) {
                // Lock page for save (buttons only, NOT checkboxes)
                // Checkboxes must remain enabled so browser can serialize form data
                self.lockPageForSave();

                // Note: Form will continue submitting, page will be locked until reload
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

            // CRITICAL: Lock entire page to prevent race conditions
            // Use lockPageForReset() - disables checkboxes too (safe for AJAX)
            self.lockPageForReset();

            // Set loading state for reset button
            $button.addClass('loading')
                   .html('<span class="dashicons dashicons-update"></span> Resetting...');

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
                        // Reload page with parameter to clear stale notices
                        // Remove old save notice and mark as reset operation
                        const url = new URL(window.location.href);
                        url.searchParams.delete('settings-updated'); // Remove old save notice
                        url.searchParams.set('permissions-reset', '1'); // Mark as reset operation
                        window.location.href = url.toString();
                    } else {
                        self.showNotice(response.data.message || 'Failed to reset permissions', 'error');
                        // Unlock page on error
                        self.unlockPage();
                        // Reset button state
                        $button.removeClass('loading')
                               .html('<span class="dashicons dashicons-image-rotate"></span> ' + originalText);
                    }
                },
                error: function(xhr, status, error) {
                    self.showNotice('Server error while resetting permissions', 'error');
                    // Unlock page on error
                    self.unlockPage();
                    // Reset button state
                    $button.removeClass('loading')
                           .html('<span class="dashicons dashicons-image-rotate"></span> ' + originalText);
                }
            });
        },

        showNotice(message, type) {
            // IMPORTANT: Remove all existing notices first (both PHP and JS generated)
            // This prevents old "save success" notices from showing after reset errors
            $('.wrap .notice').remove();

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
