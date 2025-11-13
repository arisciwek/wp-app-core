/**
 * Permission Matrix Shared JavaScript
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Permissions
 * @version     1.0.2
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/permissions/wpapp-permission-matrix.js
 *
 * Description: Shared JavaScript for permission management UI.
 *              Handles AJAX save/reset operations with consistent patterns.
 *              Used by all plugins that extend AbstractPermissionsController.
 *
 * Dependencies:
 * - jQuery
 * - WPModal (from wp-modal plugin)
 *
 * Localized Data (wpappPermissions):
 * - pluginSlug: Plugin slug
 * - pluginPrefix: Plugin prefix for AJAX actions
 * - ajaxUrl: WordPress AJAX URL
 * - nonce: Nonce for save operations
 * - resetNonce: Nonce for reset operations
 * - strings: Translated strings
 *
 * Changelog:
 * 1.0.2 - 2025-01-12 (TODO-1206)
 * - Removed hidePageLevelButtons() (moved to PHP hook for cleaner approach)
 * - Footer now controlled by wpapp_show_settings_footer hook in settings-page.php
 * 1.0.1 - 2025-01-12 (TODO-1206)
 * - [REVERTED] Added hidePageLevelButtons() to hide save/reset buttons
 * 1.0.0 - 2025-01-12 (TODO-1206)
 * - Initial creation
 * - AJAX checkbox save (instant save on change)
 * - AJAX reset with WPModal confirmation
 * - Loading states and notifications
 * - Error handling and recovery
 */

(function($) {
    'use strict';

    // Check dependencies
    if (typeof wpappPermissions === 'undefined') {
        console.error('Permission Matrix: wpappPermissions not found. Make sure controller enqueues assets properly.');
        return;
    }

    if (typeof WPModal === 'undefined') {
        console.error('Permission Matrix: WPModal not found. Make sure wp-modal plugin is active.');
        return;
    }

    // Local reference to settings
    const settings = wpappPermissions;

    /**
     * Permission Matrix Handler
     */
    const PermissionMatrix = {

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.cleanupOldNotifications();
            console.log('[PermissionMatrix] Initialized for:', settings.pluginPrefix);
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Checkbox change - instant AJAX save
            $(document).on('change', '.permission-checkbox', this.handleCheckboxChange.bind(this));

            // Reset button - AJAX with WPModal confirmation
            $(document).on('click', '.btn-reset-permissions', this.handleResetClick.bind(this));

            // Cleanup notifications on dismiss
            $(document).on('click', '.permission-notification .notice-dismiss', this.dismissNotification.bind(this));
        },

        /**
         * Handle checkbox change
         * Performs AJAX save for single permission
         */
        handleCheckboxChange: function(event) {
            const $checkbox = $(event.target);
            const $wrapper = $checkbox.closest('.permission-checkbox-wrapper');
            const role = $checkbox.data('role');
            const capability = $checkbox.data('capability');
            const pluginPrefix = $checkbox.data('plugin-prefix');
            const nonce = $checkbox.data('nonce');
            const enabled = $checkbox.is(':checked') ? 1 : 0;

            // Validate data
            if (!role || !capability || !pluginPrefix || !nonce) {
                console.error('[PermissionMatrix] Missing required data attributes');
                this.showNotification('error', 'Invalid configuration. Check console for details.');
                return;
            }

            // Set loading state
            $wrapper.addClass('loading');
            $checkbox.prop('disabled', true);

            // AJAX save
            $.ajax({
                url: settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: pluginPrefix + '_save_permissions',
                    nonce: nonce,
                    role: role,
                    capability: capability,
                    enabled: enabled
                },
                success: (response) => {
                    if (response.success) {
                        // Success - update UI
                        this.updateCheckboxUI($checkbox, enabled);
                        this.showNotification('success', response.data.message || settings.strings.saved);
                    } else {
                        // Error - revert checkbox
                        $checkbox.prop('checked', !enabled);
                        this.showNotification('error', response.data.message || settings.strings.error);
                    }
                },
                error: (xhr, status, error) => {
                    // Network error - revert checkbox
                    $checkbox.prop('checked', !enabled);
                    console.error('[PermissionMatrix] AJAX error:', error);
                    this.showNotification('error', 'Network error. Please try again.');
                },
                complete: () => {
                    // Remove loading state
                    $wrapper.removeClass('loading');
                    $checkbox.prop('disabled', false);
                }
            });
        },

        /**
         * Update checkbox UI after save
         */
        updateCheckboxUI: function($checkbox, enabled) {
            const $status = $checkbox.siblings('.permission-status');
            const $icon = $status.find('.dashicons');

            if (enabled) {
                $icon.removeClass('dashicons-minus permission-disabled')
                     .addClass('dashicons-yes-alt permission-enabled');
            } else {
                $icon.removeClass('dashicons-yes-alt permission-enabled')
                     .addClass('dashicons-minus permission-disabled');
            }
        },

        /**
         * Handle reset button click
         * Shows WPModal confirmation before resetting
         */
        handleResetClick: function(event) {
            event.preventDefault();

            const $button = $(event.currentTarget);
            const pluginPrefix = $button.data('plugin-prefix');
            const nonce = $button.data('nonce');

            // Validate data
            if (!pluginPrefix || !nonce) {
                console.error('[PermissionMatrix] Missing required data attributes on reset button');
                this.showNotification('error', 'Invalid configuration. Check console for details.');
                return;
            }

            // Show WPModal confirmation
            WPModal.confirm({
                title: 'Reset All Permissions?',
                message: 'This will reset ALL permissions across all tabs to their default values.\n\nThis action cannot be undone.\n\nAre you sure you want to continue?',
                danger: true,
                confirmLabel: 'Reset to Default',
                cancelLabel: 'Cancel',
                onConfirm: () => {
                    this.performReset($button, pluginPrefix, nonce);
                }
            });
        },

        /**
         * Perform AJAX reset
         */
        performReset: function($button, pluginPrefix, nonce) {
            // Disable button and show loading state
            const originalText = $button.text();
            $button.prop('disabled', true)
                   .html('<span class="dashicons dashicons-update spin"></span> ' + settings.strings.resetting);

            // Add spinner animation CSS if not exists
            if (!$('#permission-spinner-css').length) {
                $('<style id="permission-spinner-css">')
                    .text('.dashicons.spin { animation: rotation 1s infinite linear; } @keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(359deg); } }')
                    .appendTo('head');
            }

            // AJAX reset
            $.ajax({
                url: settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: pluginPrefix + '_reset_permissions',
                    nonce: nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Success - reload page to show updated permissions
                        this.showNotification('success', response.data.message || 'Permissions reset successfully');

                        // Reload after short delay to show notification
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        // Error - restore button
                        $button.prop('disabled', false).text(originalText);
                        this.showNotification('error', response.data.message || 'Failed to reset permissions');
                    }
                },
                error: (xhr, status, error) => {
                    // Network error - restore button
                    $button.prop('disabled', false).text(originalText);
                    console.error('[PermissionMatrix] Reset AJAX error:', error);
                    this.showNotification('error', 'Network error. Please try again.');
                }
            });
        },

        /**
         * Show notification message
         */
        showNotification: function(type, message) {
            // Remove existing notifications
            $('.permission-notification').remove();

            // Create notification element
            const $notification = $('<div>')
                .addClass('permission-notification notice notice-' + type + ' is-dismissible')
                .append($('<p>').text(message))
                .append($('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss</span></button>'));

            // Add to page
            $('body').append($notification);

            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                this.dismissNotification($notification);
            }, 3000);
        },

        /**
         * Dismiss notification with animation
         */
        dismissNotification: function(elementOrEvent) {
            let $notification;

            if (elementOrEvent instanceof jQuery) {
                $notification = elementOrEvent;
            } else {
                $notification = $(elementOrEvent.target).closest('.permission-notification');
            }

            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        },

        /**
         * Cleanup old notifications on page load
         */
        cleanupOldNotifications: function() {
            $('.permission-notification').remove();
        }
    };

    /**
     * Initialize on DOM ready
     */
    $(document).ready(function() {
        PermissionMatrix.init();
    });

})(jQuery);
