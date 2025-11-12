/**
 * Settings Reset Helper - WPModal with Form POST
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Settings
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/settings/settings-reset-helper-post.js
 *
 * Description: Reset settings dengan WPModal confirmation + native form POST
 *              NO AJAX - menggunakan WordPress Settings API standard
 *
 * Dependencies:
 * - jQuery
 * - WPModal (wp-modal plugin)
 *
 * Changelog:
 * 2.0.0 - 2025-11-12
 * - BREAKING: Changed from AJAX to native form POST
 * - Still uses WPModal for beautiful confirmation dialog
 * - Much simpler and faster - no AJAX overhead
 */

(function($) {
    'use strict';

    console.log('[Settings Reset Helper POST] Loading...');

    $(document).ready(function() {
        console.log('[Settings Reset Helper POST] Initializing...');

        // Find reset button
        const $resetBtn = $('#wpapp-settings-reset');

        if ($resetBtn.length === 0) {
            console.log('[Settings Reset Helper POST] No reset button found');
            return;
        }

        console.log('[Settings Reset Helper POST] Reset button found');

        // Setup click handler
        $resetBtn.on('click', function(e) {
            e.preventDefault();

            const $btn = $(this);
            const title = $btn.data('reset-title') || 'Reset to Default?';
            const message = $btn.data('reset-message') || 'Are you sure you want to reset settings to default values?\n\nThis action cannot be undone.';
            const formId = $btn.data('form-id');

            console.log('[Settings Reset Helper POST] Reset clicked:', {
                formId: formId,
                title: title
            });

            // Check if WPModal is loaded
            if (typeof WPModal === 'undefined') {
                console.error('[Settings Reset Helper POST] WPModal not loaded!');
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
                    console.log('[Settings Reset Helper POST] Confirmed - submitting form');
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
                console.error('[Settings Reset Helper POST] Form not found:', formId);
                alert('Error: Form not found. Please refresh the page.');
                return;
            }

            // Set reset flag to 1
            $form.find('input[name="reset_to_defaults"]').val('1');

            console.log('[Settings Reset Helper POST] Submitting form via POST to options.php');

            // Submit form - will reload page with success/error message
            $form.submit();
        }

        console.log('[Settings Reset Helper POST] Initialization complete');
    });

})(jQuery);
