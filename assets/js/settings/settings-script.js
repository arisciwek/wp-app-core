/**
 * Platform Settings JavaScript
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Settings
 * @version     1.0.2
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/settings/settings-script.js
 *
 * Description: JavaScript for platform settings functionality
 *
 * Changelog:
 * 1.0.2 - 2025-10-30
 * - CRITICAL FIX: Removed duplicate reset permission handler
 * - Fixed double AJAX call issue (was causing 500 errors)
 * - Permissions tab now handled by permissions-tab-script.js only
 * - Deprecated handleResetPermissions and handlePermissionChange
 *
 * 1.0.1 - 2025-10-30
 * - Renamed from settings.js to settings-script.js for consistency
 *
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
            // DEPRECATED: Reset permissions handler moved to permissions-tab-script.js
            // This prevents double AJAX calls on permissions tab
            // Only bind if NOT on permissions section (permissions-tab-script.js handles it)
            // $('.button-reset-permissions').on('click', this.handleResetPermissions.bind(this));

            // DEPRECATED: Permission checkbox handler moved to permissions-tab-script.js
            // Permissions now use form submission instead of individual checkbox AJAX
            // $('.permission-checkbox').on('change', this.handlePermissionChange.bind(this));

            // Handle form submissions with AJAX for general settings
            $('#platform-general-settings-form').on('submit', this.handleFormSubmit.bind(this));
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
