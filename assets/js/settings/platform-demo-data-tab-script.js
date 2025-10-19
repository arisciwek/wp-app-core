/**
 * Platform Demo Data Settings Script
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/settings/platform-demo-data-tab-script.js
 *
 * Description: Handles platform role management functionality in the settings page
 *              Menangani:
 *              - Create platform roles AJAX
 *              - Delete platform roles AJAX
 *              - Reset platform capabilities AJAX
 *              - UI feedback and messages
 *
 * Dependencies:
 * - jQuery
 * - WordPress AJAX
 * - wpAppCoreSettings (localized data)
 *
 * Changelog:
 * 1.0.1 - 2025-10-19
 * - Added platform staff generation handlers
 * - Added staff deletion handlers
 * - Added statistics refresh handlers
 *
 * 1.0.0 - 2025-10-19
 * - Initial implementation
 * - Create/delete/reset role operations
 * - UI state management
 */

jQuery(document).ready(function($) {

    /**
     * Show notification message
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
     * Handle Create Platform Roles button
     */
    $('.platform-create-roles').on('click', function(e) {
        e.preventDefault();

        const button = $(this);
        const nonce = button.data('nonce');

        if (button.prop('disabled')) {
            return;
        }

        if (!confirm(wpAppCoreSettings.i18n.confirmCreateRoles || 'Are you sure you want to create platform roles?')) {
            return;
        }

        // Disable button and show loading
        button.prop('disabled', true);
        button.closest('.demo-data-card').addClass('loading');

        $.ajax({
            url: wpAppCoreSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'create_platform_roles',
                nonce: nonce
            },
            success: function(response) {
                button.prop('disabled', false);
                button.closest('.demo-data-card').removeClass('loading');

                if (response.success) {
                    showMessage(response.data.message, 'success');

                    // Reload page after 1.5 seconds to update UI
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    showMessage(response.data.message || 'An error occurred while creating roles.', 'error');
                }
            },
            error: function(xhr, status, error) {
                button.prop('disabled', false);
                button.closest('.demo-data-card').removeClass('loading');

                console.error('AJAX Error:', {xhr, status, error});
                showMessage('An unexpected error occurred. Please try again.', 'error');
            }
        });
    });

    /**
     * Handle Delete Platform Roles button
     */
    $('.platform-delete-roles').on('click', function(e) {
        e.preventDefault();

        const button = $(this);
        const nonce = button.data('nonce');

        if (button.prop('disabled')) {
            return;
        }

        if (!confirm(wpAppCoreSettings.i18n.confirmDeleteRoles || 'WARNING: This will permanently delete all platform roles. Are you sure?')) {
            return;
        }

        // Double confirmation for safety
        if (!confirm(wpAppCoreSettings.i18n.confirmDeleteRolesDouble || 'This action cannot be undone. Continue?')) {
            return;
        }

        // Disable button and show loading
        button.prop('disabled', true);
        button.closest('.demo-data-card').addClass('loading');

        $.ajax({
            url: wpAppCoreSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'delete_platform_roles',
                nonce: nonce
            },
            success: function(response) {
                button.prop('disabled', false);
                button.closest('.demo-data-card').removeClass('loading');

                if (response.success) {
                    showMessage(response.data.message, 'success');

                    // Reload page after 1.5 seconds to update UI
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    showMessage(response.data.message || 'An error occurred while deleting roles.', 'error');
                }
            },
            error: function(xhr, status, error) {
                button.prop('disabled', false);
                button.closest('.demo-data-card').removeClass('loading');

                console.error('AJAX Error:', {xhr, status, error});
                showMessage('An unexpected error occurred. Please try again.', 'error');
            }
        });
    });

    /**
     * Handle Reset Platform Capabilities button
     */
    $('.platform-reset-capabilities').on('click', function(e) {
        e.preventDefault();

        const button = $(this);
        const nonce = button.data('nonce');

        if (button.prop('disabled')) {
            return;
        }

        if (!confirm(wpAppCoreSettings.i18n.confirmResetCapabilities || 'Are you sure you want to reset all platform capabilities to default values?')) {
            return;
        }

        // Disable button and show loading
        button.prop('disabled', true);
        button.closest('.demo-data-card').addClass('loading');

        $.ajax({
            url: wpAppCoreSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'reset_platform_capabilities',
                nonce: nonce
            },
            success: function(response) {
                button.prop('disabled', false);
                button.closest('.demo-data-card').removeClass('loading');

                if (response.success) {
                    showMessage(response.data.message, 'success');

                    // Reload page after 1.5 seconds to update UI
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    showMessage(response.data.message || 'An error occurred while resetting capabilities.', 'error');
                }
            },
            error: function(xhr, status, error) {
                button.prop('disabled', false);
                button.closest('.demo-data-card').removeClass('loading');

                console.error('AJAX Error:', {xhr, status, error});
                showMessage('An unexpected error occurred. Please try again.', 'error');
            }
        });
    });

    /**
     * Handle Generate Platform Staff button
     */
    $('.platform-generate-staff').on('click', function(e) {
        e.preventDefault();

        const button = $(this);
        const nonce = button.data('nonce');

        if (button.prop('disabled')) {
            return;
        }

        if (!confirm('Generate 20 platform staff demo users?\n\nThis will create:\n- 2 Super Admins\n- 3 Admins\n- 3 Managers\n- 4 Support staff\n- 3 Finance staff\n- 3 Analysts\n- 2 Viewers\n\nDefault password: password123')) {
            return;
        }

        // Disable button and show loading
        button.prop('disabled', true);
        button.closest('.demo-data-card').addClass('loading');

        $.ajax({
            url: wpAppCoreSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'generate_platform_staff',
                nonce: nonce
            },
            success: function(response) {
                button.prop('disabled', false);
                button.closest('.demo-data-card').removeClass('loading');

                if (response.success) {
                    let message = response.data.message;
                    if (response.data.errors && response.data.errors.length > 0) {
                        message += '\n\nErrors:\n' + response.data.errors.join('\n');
                    }
                    showMessage(message, 'success');

                    // Refresh statistics
                    $('.platform-refresh-stats').trigger('click');
                } else {
                    showMessage(response.data.message || 'An error occurred while generating platform staff.', 'error');
                }
            },
            error: function(xhr, status, error) {
                button.prop('disabled', false);
                button.closest('.demo-data-card').removeClass('loading');

                console.error('AJAX Error:', {xhr, status, error});
                showMessage('An unexpected error occurred. Please try again.', 'error');
            }
        });
    });

    /**
     * Handle Delete Platform Staff button
     */
    $('.platform-delete-staff').on('click', function(e) {
        e.preventDefault();

        const button = $(this);
        const nonce = button.data('nonce');

        if (button.prop('disabled')) {
            return;
        }

        if (!confirm('WARNING: This will permanently delete all platform staff demo users (ID 230-249).\n\nThis action cannot be undone. Are you sure?')) {
            return;
        }

        // Double confirmation for safety
        if (!confirm('Final confirmation: Delete ALL platform staff demo data?')) {
            return;
        }

        // Disable button and show loading
        button.prop('disabled', true);
        button.closest('.demo-data-card').addClass('loading');

        $.ajax({
            url: wpAppCoreSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'delete_platform_staff',
                nonce: nonce
            },
            success: function(response) {
                button.prop('disabled', false);
                button.closest('.demo-data-card').removeClass('loading');

                if (response.success) {
                    showMessage(response.data.message, 'success');

                    // Refresh statistics
                    setTimeout(function() {
                        $('.platform-refresh-stats').trigger('click');
                    }, 500);
                } else {
                    showMessage(response.data.message || 'An error occurred while deleting platform staff.', 'error');
                }
            },
            error: function(xhr, status, error) {
                button.prop('disabled', false);
                button.closest('.demo-data-card').removeClass('loading');

                console.error('AJAX Error:', {xhr, status, error});
                showMessage('An unexpected error occurred. Please try again.', 'error');
            }
        });
    });

    /**
     * Handle Refresh Statistics button
     */
    $('.platform-refresh-stats').on('click', function(e) {
        e.preventDefault();

        const button = $(this);
        const nonce = button.data('nonce');
        const statsContainer = $('#platform-staff-stats');

        if (button.prop('disabled')) {
            return;
        }

        // Disable button and show loading
        button.prop('disabled', true);
        statsContainer.html('<p>Loading statistics...</p>');

        $.ajax({
            url: wpAppCoreSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'platform_staff_stats',
                nonce: nonce
            },
            success: function(response) {
                button.prop('disabled', false);

                if (response.success) {
                    const stats = response.data.stats;
                    let html = '<ul style="margin: 0; padding-left: 20px;">';
                    html += '<li><strong>Total users defined:</strong> ' + stats.total_users + '</li>';
                    html += '<li><strong>Existing users:</strong> ' + stats.existing_users + '</li>';
                    html += '<li><strong>Staff records:</strong> ' + stats.existing_staff_records + '</li>';
                    html += '<li><strong>Users to create:</strong> ' + stats.users_to_create + '</li>';
                    html += '</ul>';

                    // Add status indicator
                    if (stats.existing_users === stats.total_users) {
                        html = '<p style="color: #46b450;"><span class="dashicons dashicons-yes-alt"></span> All users generated</p>' + html;
                    } else if (stats.existing_users > 0) {
                        html = '<p style="color: #f0b849;"><span class="dashicons dashicons-warning"></span> Partially generated</p>' + html;
                    } else {
                        html = '<p style="color: #646970;"><span class="dashicons dashicons-info"></span> No users generated</p>' + html;
                    }

                    statsContainer.html(html);
                } else {
                    statsContainer.html('<p style="color: #d63638;">Error loading statistics</p>');
                }
            },
            error: function(xhr, status, error) {
                button.prop('disabled', false);

                console.error('AJAX Error:', {xhr, status, error});
                statsContainer.html('<p style="color: #d63638;">Error loading statistics</p>');
            }
        });
    });

    // Auto-load statistics on page load
    if ($('.platform-refresh-stats').length > 0) {
        $('.platform-refresh-stats').trigger('click');
    }
});
