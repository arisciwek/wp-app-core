/**
 * Shared Demo Data Tab Script
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/DemoData
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/demo-data/wpapp-demo-data.js
 *
 * Description: Shared JavaScript for demo data tab across all plugins.
 *              REFACTORED: From plugin-specific to shared asset (TODO-1207).
 *              RENAMED: For global scope identification (TODO-1208).
 *              Used by: wp-app-core, wp-customer, wp-agency, and 17 other plugins.
 *
 * Features:
 * - Generic AJAX handlers using data attributes
 * - Configurable actions via data-action attribute
 * - Loading states management
 * - Error handling
 * - Success/error notifications
 * - Statistics refresh
 *
 * Dependencies:
 * - jQuery
 * - WordPress AJAX
 * - WPModal (wp-modal plugin) for confirmation dialogs
 * - wpAppCoreSettings (or plugin-specific equivalent for localized data)
 *
 * Button Data Attributes:
 * - data-action: AJAX action name (e.g., 'wpapp_generate_platform_staff')
 * - data-nonce: Security nonce
 * - data-confirm: Confirmation message (optional)
 * - data-double-confirm: Double confirmation message (optional, for dangerous actions)
 * - data-success-reload: Reload page after success (optional, default: false)
 * - data-stats-refresh: Trigger stats refresh after success (optional)
 *
 * Changelog:
 * 2.0.1 - 2025-01-12 (TODO-1207)
 * - Added WPModal integration for confirmation dialogs
 * - Replaced native confirm() with WPModal.confirm()
 * - Extracted executeAjaxRequest() function for reusability
 * - Fallback to native confirm() if WPModal not loaded
 * 2.0.0 - 2025-01-12 (TODO-1207)
 * - BREAKING: Made generic using data attributes for configuration
 * - Moved from settings/ to demo-data/ (shared asset)
 * - Now reusable across all 20 plugins
 * - Button actions configured via data-action attribute
 * - Support for plugin-specific localized objects
 * 1.0.1 - 2025-10-19
 * - Added platform staff generation handlers
 * - Added staff deletion handlers
 * - Added statistics refresh handlers
 * 1.0.0 - 2025-10-19
 * - Initial creation (platform-specific)
 */

jQuery(document).ready(function($) {
    'use strict';

    // Get localized settings (wpAppCoreSettings or plugin-specific)
    const settings = window.wpAppCoreSettings || window.wpCustomerSettings || window.wpAgencySettings || {};
    const ajaxUrl = settings.ajaxUrl || ajaxurl; // Fallback to global ajaxurl

    /**
     * Show notification message
     *
     * @param {string} message Message text
     * @param {string} type    Message type: success, error, warning, info
     */
    function showMessage(message, type) {
        const messageDiv = $('#demo-data-messages');

        messageDiv.removeClass('notice-error notice-success notice-warning notice-info')
                  .addClass('notice notice-' + type)
                  .html('<p>' + message + '</p>')
                  .show();

        // Scroll to message
        $('html, body').animate({
            scrollTop: messageDiv.offset().top - 50
        }, 300);
    }

    /**
     * Generic AJAX handler for demo data buttons
     * Buttons must have:
     * - data-action: AJAX action name
     * - data-nonce: Security nonce
     * - data-confirm: Optional confirmation message
     * - data-double-confirm: Optional second confirmation (for dangerous actions)
     * - data-success-reload: Optional reload after success
     * - data-stats-refresh: Optional trigger stats refresh
     */
    $(document).on('click', '.demo-data-button', function(e) {
        e.preventDefault();

        const $button = $(this);
        const action = $button.data('action');
        const nonce = $button.data('nonce');
        const confirmMsg = $button.data('confirm');
        const doubleConfirmMsg = $button.data('double-confirm');
        const successReload = $button.data('success-reload');
        const statsRefresh = $button.data('stats-refresh');

        // Validate required attributes
        if (!action || !nonce) {
            console.error('[DemoData] Missing required data attributes (data-action or data-nonce)');
            showMessage('Configuration error: missing action or nonce', 'error');
            return;
        }

        // Check if button is already disabled
        if ($button.prop('disabled')) {
            return;
        }

        // Show confirmation if configured (using WPModal)
        if (confirmMsg) {
            // Check if WPModal is available
            if (typeof WPModal === 'undefined') {
                console.warn('[DemoData] WPModal not loaded, using fallback confirm()');
                if (!confirm(confirmMsg)) {
                    return;
                }
                // Process double confirm for fallback
                if (doubleConfirmMsg && !confirm(doubleConfirmMsg)) {
                    return;
                }
                // Continue with AJAX if fallback confirmed
            } else {
                // Use WPModal for confirmation
                WPModal.confirm({
                    title: doubleConfirmMsg ? 'Warning: Destructive Action' : 'Confirm Action',
                    message: confirmMsg + (doubleConfirmMsg ? '\n\n' + doubleConfirmMsg : ''),
                    danger: !!doubleConfirmMsg,
                    confirmLabel: 'Proceed',
                    onConfirm: function() {
                        // Execute AJAX request after confirmation
                        executeAjaxRequest($button, action, nonce, successReload, statsRefresh);
                    }
                });
                return; // Exit here, AJAX will be called from onConfirm
            }
        }

        // No confirmation needed, execute immediately
        executeAjaxRequest($button, action, nonce, successReload, statsRefresh);
    });

    /**
     * Execute AJAX request for demo data operations
     * Extracted to support both direct calls and WPModal callbacks
     */
    function executeAjaxRequest($button, action, nonce, successReload, statsRefresh) {
        // Disable button and show loading
        $button.prop('disabled', true);
        $button.closest('.demo-data-card').addClass('loading');

        // Send AJAX request
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: action,
                nonce: nonce
            },
            success: function(response) {
                $button.prop('disabled', false);
                $button.closest('.demo-data-card').removeClass('loading');

                if (response.success) {
                    // Build success message
                    let message = response.data.message || 'Operation completed successfully';

                    // Add error details if present (partial success)
                    if (response.data.errors && response.data.errors.length > 0) {
                        message += '\n\nErrors:\n' + response.data.errors.join('\n');
                    }

                    showMessage(message, 'success');

                    // Refresh statistics if configured
                    if (statsRefresh) {
                        const $statsButton = $(statsRefresh);
                        if ($statsButton.length) {
                            setTimeout(function() {
                                $statsButton.trigger('click');
                            }, 500);
                        }
                    }

                    // Reload page after success if configured
                    if (successReload) {
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    const errorMsg = response.data.message || 'An error occurred during operation';
                    showMessage(errorMsg, 'error');
                }
            },
            error: function(xhr, status, error) {
                $button.prop('disabled', false);
                $button.closest('.demo-data-card').removeClass('loading');

                console.error('[DemoData] AJAX Error:', {xhr, status, error});
                showMessage('An unexpected error occurred. Please try again.', 'error');
            }
        });
    }

    /**
     * Statistics refresh handler
     * Button must have:
     * - data-action: AJAX action name for stats
     * - data-nonce: Security nonce
     * - data-stats-container: Selector for stats container
     */
    $(document).on('click', '.demo-data-stats-refresh', function(e) {
        e.preventDefault();

        const $button = $(this);
        const action = $button.data('action');
        const nonce = $button.data('nonce');
        const containerSelector = $button.data('stats-container') || '#demo-data-stats';
        const $statsContainer = $(containerSelector);

        // Validate required attributes
        if (!action || !nonce) {
            console.error('[DemoData] Missing required data attributes (data-action or data-nonce)');
            return;
        }

        // Check if button is already disabled
        if ($button.prop('disabled')) {
            return;
        }

        // Disable button and show loading
        $button.prop('disabled', true);
        $statsContainer.html('<p>Loading statistics...</p>');

        // Send AJAX request
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: action,
                nonce: nonce
            },
            success: function(response) {
                $button.prop('disabled', false);

                if (response.success && response.data.stats) {
                    const stats = response.data.stats;

                    // Build stats HTML
                    let html = '<ul style="margin: 0; padding-left: 20px;">';

                    // Generic stats display (customize per plugin if needed)
                    if (stats.total_users !== undefined) {
                        html += '<li><strong>Total users defined:</strong> ' + stats.total_users + '</li>';
                    }
                    if (stats.existing_users !== undefined) {
                        html += '<li><strong>Existing users:</strong> ' + stats.existing_users + '</li>';
                    }
                    if (stats.existing_staff_records !== undefined) {
                        html += '<li><strong>Staff records:</strong> ' + stats.existing_staff_records + '</li>';
                    }
                    if (stats.existing_records !== undefined) {
                        html += '<li><strong>Existing records:</strong> ' + stats.existing_records + '</li>';
                    }
                    if (stats.users_to_create !== undefined) {
                        html += '<li><strong>Users to create:</strong> ' + stats.users_to_create + '</li>';
                    }

                    html += '</ul>';

                    // Add status indicator
                    if (stats.existing_users === stats.total_users) {
                        html = '<p style="color: #46b450;"><span class="dashicons dashicons-yes-alt"></span> All users generated</p>' + html;
                    } else if (stats.existing_users > 0) {
                        html = '<p style="color: #f0b849;"><span class="dashicons dashicons-warning"></span> Partially generated</p>' + html;
                    } else {
                        html = '<p style="color: #646970;"><span class="dashicons dashicons-info"></span> No users generated</p>' + html;
                    }

                    $statsContainer.html(html);
                } else {
                    $statsContainer.html('<p style="color: #d63638;">Error loading statistics</p>');
                }
            },
            error: function(xhr, status, error) {
                $button.prop('disabled', false);

                console.error('[DemoData] AJAX Error:', {xhr, status, error});
                $statsContainer.html('<p style="color: #d63638;">Error loading statistics</p>');
            }
        });
    });

    /**
     * Auto-load statistics on page load
     * Any stats refresh button will auto-trigger on page load
     */
    $('.demo-data-stats-refresh').each(function() {
        $(this).trigger('click');
    });

    /**
     * Legacy support for old class names (backward compatibility)
     * Map old platform-specific classes to new generic class
     */
    const legacyButtonMap = {
        '.platform-create-roles': 'wpapp_create_platform_roles',
        '.platform-delete-roles': 'wpapp_delete_platform_roles',
        '.platform-reset-capabilities': 'wpapp_reset_platform_capabilities',
        '.platform-generate-staff': 'wpapp_generate_platform_staff',
        '.platform-delete-staff': 'wpapp_delete_platform_staff'
    };

    // Attach legacy handlers
    $.each(legacyButtonMap, function(selector, action) {
        $(selector).on('click', function(e) {
            // If not already handled by .demo-data-button, manually trigger
            if (!$(this).hasClass('demo-data-button')) {
                const $button = $(this);
                $button.addClass('demo-data-button');

                // Set action if not already set
                if (!$button.data('action')) {
                    $button.attr('data-action', action);
                }

                // Retrigger click
                $button.trigger('click');
            }
        });
    });

    // Legacy stats refresh
    $('.platform-refresh-stats').on('click', function(e) {
        if (!$(this).hasClass('demo-data-stats-refresh')) {
            const $button = $(this);
            $button.addClass('demo-data-stats-refresh');

            if (!$button.data('action')) {
                $button.attr('data-action', 'wpapp_platform_staff_stats');
            }
            if (!$button.data('stats-container')) {
                $button.attr('data-stats-container', '#platform-staff-stats');
            }

            $button.trigger('click');
        }
    });

    console.log('[DemoData] Shared demo data script initialized');
});
