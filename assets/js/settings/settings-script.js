/**
 * Platform Settings JavaScript - GLOBAL SCOPE
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Settings
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/settings/settings-script.js
 *
 * Description: Global JavaScript for platform settings functionality.
 *              Handles page-level Save button that works across ALL tabs.
 *              REUSABLE: Can be used by wp-customer, wp-agency, etc.
 *
 * Changelog:
 * 2.0.0 - 2025-11-12
 * - BREAKING: Added global save button handler (#wpapp-settings-save)
 * - Button detects current tab and submits correct form
 * - Pattern reusable across all wp-app-* plugins
 * - Removed deprecated permission handlers
 *
 * 1.0.2 - 2025-10-30
 * - Removed duplicate reset permission handler
 * - Fixed double AJAX call issue
 *
 * 1.0.1 - 2025-10-30
 * - Renamed from settings.js to settings-script.js
 *
 * 1.0.0 - 2025-10-19
 * - Initial creation
 */

(function($) {
    'use strict';

    const WPAppSettings = {
        init: function() {
            console.log('[WPApp Settings] 🔄 Initializing global settings handler...');
            console.log('[WPApp Settings] Current URL:', window.location.href);

            const $saveBtn = $('#wpapp-settings-save');
            const $resetBtn = $('#wpapp-settings-reset');

            console.log('[WPApp Settings] Save button found:', $saveBtn.length > 0, {
                exists: $saveBtn.length > 0,
                visible: $saveBtn.is(':visible'),
                formId: $saveBtn.data('form-id'),
                currentTab: $saveBtn.data('current-tab')
            });

            console.log('[WPApp Settings] Reset button found:', $resetBtn.length > 0, {
                exists: $resetBtn.length > 0,
                visible: $resetBtn.is(':visible'),
                action: $resetBtn.data('reset-action'),
                currentTab: $resetBtn.data('current-tab')
            });

            this.bindEvents();
        },

        bindEvents: function() {
            // GLOBAL SCOPE: Page-level Save button
            // Detects current tab and submits the correct form
            const $saveBtn = $('#wpapp-settings-save');

            if ($saveBtn.length === 0) {
                console.warn('[WPApp Settings] ⚠️ Save button not found!');
            } else {
                $saveBtn.on('click', this.handleGlobalSave.bind(this));
                console.log('[WPApp Settings] ✅ Global save button handler registered');
            }
        },

        /**
         * Handle global Save button click
         * Submits the form for the current active tab
         */
        handleGlobalSave: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const formId = $btn.data('form-id');
            const currentTab = $btn.data('current-tab');

            console.log('[WPApp Settings] Global save clicked:', {
                tab: currentTab,
                formId: formId
            });

            // Find and submit the form
            const $form = $('#' + formId);

            if ($form.length === 0) {
                console.error('[WPApp Settings] Form not found:', formId);
                alert('Error: Form not found for tab "' + currentTab + '"');
                return false;
            }

            console.log('[WPApp Settings] Submitting form:', formId);

            // Disable button to prevent double-submit
            $btn.prop('disabled', true).text('Saving...');

            // Add saved_tab as hidden input field (safer than modifying action URL)
            // Remove existing saved_tab input if any
            $form.find('input[name="saved_tab"]').remove();

            // Add new hidden input
            $('<input>')
                .attr('type', 'hidden')
                .attr('name', 'saved_tab')
                .attr('value', currentTab)
                .appendTo($form);

            console.log('[WPApp Settings] 📍 Added saved_tab hidden input:', currentTab);

            // Submit the form (WordPress will handle it)
            $form.submit();

            return false;
        },

        /**
         * Show admin notice
         * @param {string} message Notice message
         * @param {string} type Notice type (success, error, warning, info)
         */
        showNotice: function(message, type) {
            const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
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
        WPAppSettings.init();
    });

    // Export to global scope for other plugins to use
    window.WPAppSettings = WPAppSettings;

})(jQuery);
