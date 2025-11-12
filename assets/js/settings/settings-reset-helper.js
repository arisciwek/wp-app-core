/**
 * Settings Reset Helper - WPModal Integration
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/settings/settings-reset-helper.js
 *
 * Description: Reusable helper untuk reset settings dengan WPModal confirmation
 *
 * Dependencies:
 * - jQuery
 * - WPModal (wp-modal plugin)
 *
 * Usage:
 * wpAppCoreSettingsHelper.resetSettings({
 *     buttonId: '#reset-general-settings',
 *     action: 'reset_general',
 *     title: 'Reset General Settings?',
 *     message: 'Are you sure...',
 *     successMessage: 'Settings reset successfully'
 * });
 *
 * Changelog:
 * 1.0.0 - 2025-11-11
 * - Initial implementation
 * - WPModal integration
 * - Reusable pattern for all settings tabs
 */

(function($) {
    'use strict';

    console.log('[Settings Helper] Loading...');

    /**
     * Auto-initialize all reset buttons with data-reset-action attribute
     * Supports both:
     * - Global page-level button: #wpapp-settings-reset (wp-app-core global scope)
     * - Per-tab buttons: [data-reset-action] (backward compatibility)
     */
    $(document).ready(function() {
        console.log('[Settings Helper] 🔄 Page loaded - starting auto-initialization...');
        console.log('[Settings Helper] Current URL:', window.location.href);
        console.log('[Settings Helper] Document ready state:', document.readyState);

        // Find all buttons with data-reset-action (includes both global and per-tab)
        const resetButtons = $('[data-reset-action]');
        console.log('[Settings Helper] Found ' + resetButtons.length + ' reset button(s)');

        resetButtons.each(function(index) {
            try {
                const $btn = $(this);
                const action = $btn.data('reset-action');
                const currentTab = $btn.data('current-tab');

                console.log('[Settings Helper] Initializing button #' + (index + 1) + ':', {
                    id: $btn.attr('id'),
                    action: action,
                    currentTab: currentTab,
                    visible: $btn.is(':visible'),
                    disabled: $btn.is(':disabled')
                });

                // Validate action exists
                if (!action) {
                    console.warn('[Settings Helper] ⚠️ Button has no reset-action data attribute:', $btn.attr('id'));
                    return;
                }

                const title = $btn.data('reset-title') || 'Reset to Default?';
                const message = $btn.data('reset-message') || 'Are you sure you want to reset settings to default values?\n\nThis action cannot be undone.';

                console.log('[Settings Helper] ✅ Button configured:', {
                    id: $btn.attr('id'),
                    action: action,
                    title: title
                });

            // Setup click handler
            $btn.on('click', function(e) {
                e.preventDefault();

                console.log('[Settings Helper] Reset button clicked:', action);

                // Check if WPModal is loaded
                if (typeof WPModal === 'undefined') {
                    console.error('[Settings Helper] WPModal is not loaded!');
                    alert('Error: Modal library not loaded. Please refresh the page.');
                    return;
                }

                const $resetBtn = $(this);
                const $submitBtn = $('#submit');
                const originalText = $resetBtn.text();

                // Show confirmation modal
                WPModal.confirm({
                    title: title,
                    message: message,
                    danger: true,
                    confirmLabel: 'Reset Settings',
                    onConfirm: function() {
                        console.log('[Settings Helper] Confirmed - starting AJAX for action:', action);

                        // Disable buttons
                        $resetBtn.prop('disabled', true).text('Resetting...');
                        $submitBtn.prop('disabled', true);

                        $.ajax({
                            url: wpAppCoreSettings.ajaxUrl,
                            type: 'POST',
                            data: {
                                action: action,
                                nonce: wpAppCoreSettings.nonce
                            },
                            success: function(response) {
                                console.log('[Settings Helper] AJAX Success:', response);

                                if (response.success) {
                                    console.log('[Settings Helper] Reset successful!');
                                    console.log('[Settings Helper] ✅ SUCCESS - Settings reset data:', response.data);
                                    console.log('[Settings Helper] 🔄 Reloading page with success notice...');

                                    // Build redirect URL parameters
                                    const currentTab = $resetBtn.data('current-tab');
                                    const redirectParams = {
                                        page: new URLSearchParams(window.location.search).get('page'),
                                        tab: currentTab,
                                        'settings-updated': 'true',
                                        reset: 'success',
                                        reset_tab: currentTab
                                    };

                                    console.log('[Settings Helper] 📋 Redirect parameters:', redirectParams);
                                    console.log('[Settings Helper] 📍 Current tab value:', currentTab);

                                    // NORMAL MODE: Reload page with success parameter
                                    const redirectUrl = window.location.href.split('?')[0] + '?' + $.param(redirectParams);
                                    console.log('[Settings Helper] 🔗 Redirect URL:', redirectUrl);

                                    window.location.href = redirectUrl;
                                } else {
                                    console.log('[Settings Helper] Reset failed:', response.data.message);
                                    console.error('[Settings Helper] ❌ ERROR - Full response:', response);
                                    console.log('[Settings Helper] 🔄 Reloading page with error notice...');

                                    // NORMAL MODE: Reload page with error parameter
                                    window.location.href = window.location.href.split('?')[0] + '?' +
                                        $.param({
                                            page: new URLSearchParams(window.location.search).get('page'),
                                            tab: $resetBtn.data('current-tab'),
                                            reset: 'error',
                                            message: response.data.message || 'Failed to reset settings.'
                                        });
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('[Settings Helper] AJAX Error:', xhr, status, error);
                                // Reload with error parameter for WordPress notice
                                window.location.href = window.location.href.split('?')[0] + '?' +
                                    $.param({
                                        page: new URLSearchParams(window.location.search).get('page'),
                                        tab: $resetBtn.data('current-tab'),
                                        reset: 'error',
                                        message: 'An error occurred while resetting settings.'
                                    });
                            }
                        });
                    }
                });
            });
            } catch (error) {
                console.error('[Settings Helper] Error initializing reset button:', error);
            }
        });

        console.log('[Settings Helper] Auto-initialization complete');
    });

    /**
     * Legacy API - kept for backward compatibility
     * @deprecated Use data-reset-action attribute instead
     */
    window.wpAppCoreSettingsHelper = {
        resetSettings: function(config) {
            console.warn('[Settings Helper] wpAppCoreSettingsHelper.resetSettings() is deprecated. Use data-reset-action attribute instead.');
        }
    };

})(jQuery);
