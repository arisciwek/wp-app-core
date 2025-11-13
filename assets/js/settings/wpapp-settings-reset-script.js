/**
 * Settings Reset Script - GLOBAL for ALL Plugins
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Settings
 * @version     2.1.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/settings/wpapp-settings-reset-script.js
 *
 * Description: SHARED reset script untuk semua plugin yang memiliki fitur reset di settings.
 *              Menggunakan WPModal confirmation + native form POST (NO AJAX).
 *              WordPress Settings API standard.
 *
 * Used By:
 * - wp-customer (Customer Settings > General, Permissions, etc.)
 * - wp-agency (Agency Settings > General, Permissions, etc.)
 * - wp-disnaker (Disnaker Settings > General, Permissions, etc.)
 * - All future wp-app-* plugins with reset functionality
 *
 * Dependencies:
 * - jQuery
 * - WPModal (wp-modal plugin)
 * - wpapp-settings-base (for save button)
 *
 * Button Requirements:
 * - ID: #wpapp-settings-reset
 * - Attributes: data-form-id, data-reset-title, data-reset-message
 *
 * Changelog:
 * 2.1.0 - 2025-11-14
 * - RENAMED: settings-reset-helper-post.js → wpapp-settings-reset-script.js
 * - Updated to follow wpapp-* naming convention
 * - Documented as GLOBAL shared script for all plugins
 * - Added comprehensive usage documentation
 *
 * 2.0.0 - 2025-11-12
 * - BREAKING: Changed from AJAX to native form POST
 * - Uses WPModal for beautiful confirmation dialog
 * - Much simpler and faster - no AJAX overhead
 */

(function($) {
    'use strict';

    console.log('[WPApp Settings Reset] Loading...');

    $(document).ready(function() {
        console.log('[WPApp Settings Reset] Initializing...');

        // Find reset button
        const $resetBtn = $('#wpapp-settings-reset');

        if ($resetBtn.length === 0) {
            console.log('[WPApp Settings Reset] No reset button found');
            return;
        }

        console.log('[WPApp Settings Reset] Reset button found');

        // Setup click handler
        $resetBtn.on('click', function(e) {
            e.preventDefault();

            const $btn = $(this);
            const title = $btn.data('reset-title') || 'Reset to Default?';
            const message = $btn.data('reset-message') || 'Are you sure you want to reset settings to default values?\n\nThis action cannot be undone.';
            const formId = $btn.data('form-id');

            console.log('[WPApp Settings Reset] Reset clicked:', {
                formId: formId,
                title: title
            });

            // Check if WPModal is loaded
            if (typeof WPModal === 'undefined') {
                console.error('[WPApp Settings Reset] WPModal not loaded!');
                // Fallback to native confirm
                if (confirm(message)) {
                    submitResetForm(formId);
                }
                return;
            }

            // Show WPModal confirmation
            WPModal.confirm({
                title: title,
                message: message,
                danger: true,
                confirmLabel: 'Reset Settings',
                onConfirm: function() {
                    console.log('[WPApp Settings Reset] Confirmed - submitting form');
                    submitResetForm(formId);
                }
            });
        });

        /**
         * Submit form with reset flag
         */
        function submitResetForm(formId) {
            const $form = $('#' + formId);

            if ($form.length === 0) {
                console.error('[WPApp Settings Reset] Form not found:', formId);
                alert('Error: Form not found. Please refresh the page.');
                return;
            }

            // Set reset flag to 1
            $form.find('input[name="reset_to_defaults"]').val('1');

            console.log('[WPApp Settings Reset] Submitting form via POST to options.php');

            // Submit form - will reload page with success/error message
            $form.submit();
        }

        console.log('[WPApp Settings Reset] Initialization complete');
    });

})(jQuery);
