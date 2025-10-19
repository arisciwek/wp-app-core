/**
 * Platform Settings JavaScript
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/settings/settings.js
 *
 * Description: JavaScript for platform settings functionality
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial creation
 * - AJAX form submission
 * - Permission matrix handling
 */

(function($) {
    'use strict';

    const PlatformSettings = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Handle reset permissions
            $('.button-reset-permissions').on('click', this.handleResetPermissions.bind(this));

            // Handle permission matrix checkboxes
            $('.permission-checkbox').on('change', this.handlePermissionChange.bind(this));

            // Handle form submissions with AJAX
            $('#platform-general-settings-form').on('submit', this.handleFormSubmit.bind(this));
        },

        handleResetPermissions: function(e) {
            e.preventDefault();

            if (!confirm(wpAppCoreSettings.i18n.confirm)) {
                return;
            }

            $.ajax({
                url: wpAppCoreSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'reset_platform_permissions',
                    nonce: wpAppCoreSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        PlatformSettings.showNotice(response.data.message, 'success');
                        location.reload();
                    } else {
                        PlatformSettings.showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    PlatformSettings.showNotice(wpAppCoreSettings.i18n.error, 'error');
                }
            });
        },

        handlePermissionChange: function(e) {
            const $checkbox = $(e.currentTarget);
            const role = $checkbox.data('role');
            const capability = $checkbox.data('capability');
            const checked = $checkbox.prop('checked');

            // Get all capabilities for this role
            const capabilities = {};
            $(`.permission-checkbox[data-role="${role}"]`).each(function() {
                capabilities[$(this).data('capability')] = $(this).prop('checked');
            });

            $.ajax({
                url: wpAppCoreSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'save_platform_permissions',
                    nonce: wpAppCoreSettings.nonce,
                    role: role,
                    capabilities: capabilities
                },
                success: function(response) {
                    if (response.success) {
                        PlatformSettings.showNotice(response.data.message, 'success');
                    } else {
                        PlatformSettings.showNotice(response.data.message, 'error');
                        // Revert checkbox state
                        $checkbox.prop('checked', !checked);
                    }
                },
                error: function() {
                    PlatformSettings.showNotice(wpAppCoreSettings.i18n.error, 'error');
                    // Revert checkbox state
                    $checkbox.prop('checked', !checked);
                }
            });
        },

        handleFormSubmit: function(e) {
            // Let WordPress handle the form submission normally
            // This is just a placeholder for future AJAX implementation
            return true;
        },

        showNotice: function(message, type) {
            const $notice = $('<div class="settings-notice ' + type + '">' + message + '</div>');
            $('.wrap h1').after($notice);

            // Auto-remove after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        PlatformSettings.init();
    });

})(jQuery);
