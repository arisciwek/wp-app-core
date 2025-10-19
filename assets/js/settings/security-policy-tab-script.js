/**
 * Security Policy & Audit Settings Script
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/settings/security-policy-tab-script.js
 *
 * Description: Handles security policy & audit settings functionality
 *              - Conditional field display
 *              - Form validation
 *              - User feedback
 *              - File size conversion helpers
 *
 * Dependencies:
 * - jQuery
 * - WordPress Settings API
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial implementation
 * - Conditional fields (activity logs, security notifications, security headers)
 * - Field validation (upload size, retention days)
 * - File size conversion helpers
 */

jQuery(document).ready(function($) {

    /**
     * Toggle Activity Logs Options
     */
    function toggleActivityLogsOptions() {
        const isEnabled = $('.activity-logs-toggle').is(':checked');
        if (isEnabled) {
            $('body').addClass('activity-logs-enabled');
        } else {
            $('body').removeClass('activity-logs-enabled');
        }
    }

    $('.activity-logs-toggle').on('change', toggleActivityLogsOptions);
    toggleActivityLogsOptions(); // Initial state

    /**
     * Toggle Security Notifications Options
     */
    function toggleSecurityNotificationsOptions() {
        const isEnabled = $('.security-notifications-toggle').is(':checked');
        if (isEnabled) {
            $('body').addClass('security-notifications-enabled');
        } else {
            $('body').removeClass('security-notifications-enabled');
        }
    }

    $('.security-notifications-toggle').on('change', toggleSecurityNotificationsOptions);
    toggleSecurityNotificationsOptions(); // Initial state

    /**
     * Toggle Security Headers Options
     */
    function toggleSecurityHeadersOptions() {
        const isEnabled = $('.security-headers-toggle').is(':checked');
        if (isEnabled) {
            $('body').addClass('security-headers-enabled');
        } else {
            $('body').removeClass('security-headers-enabled');
        }
    }

    $('.security-headers-toggle').on('change', toggleSecurityHeadersOptions);
    toggleSecurityHeadersOptions(); // Initial state

    /**
     * Validate Max Upload Size
     */
    $('#max_upload_size').on('change', function() {
        let value = parseInt($(this).val());
        if (value < 1024) {
            $(this).val(1024);
            showMessage('Maximum upload size cannot be less than 1024 bytes (1KB).', 'warning');
        }
    });

    /**
     * Validate Log Retention Days
     */
    $('#log_retention_days').on('change', function() {
        let value = parseInt($(this).val());
        if (value < 7) {
            $(this).val(7);
            showMessage('Log retention period cannot be less than 7 days.', 'warning');
        } else if (value > 365) {
            $(this).val(365);
            showMessage('Maximum log retention period is 365 days.', 'warning');
        }
    });

    /**
     * Show notification message
     */
    function showMessage(message, type) {
        const messageDiv = $('#security-policy-messages');
        messageDiv.removeClass('notice-error notice-success notice-warning notice-info')
                  .addClass('notice notice-' + type)
                  .html('<p>' + message + '</p>')
                  .show();

        // Scroll to message
        $('html, body').animate({
            scrollTop: messageDiv.offset().top - 50
        }, 300);

        // Auto-hide info/success messages after 5 seconds
        if (type === 'info' || type === 'success') {
            setTimeout(function() {
                messageDiv.fadeOut();
            }, 5000);
        }
    }

    /**
     * Form Submission Validation
     */
    $('.wp-app-core-security-policy-form').on('submit', function(e) {
        const form = $(this);

        // Validate max upload size
        const maxUploadSize = parseInt($('#max_upload_size').val());
        if (maxUploadSize < 1024) {
            e.preventDefault();
            showMessage('Maximum upload size must be at least 1024 bytes (1KB).', 'error');
            return false;
        }

        // Validate log retention if activity logs enabled
        if ($('.activity-logs-toggle').is(':checked')) {
            const retentionDays = parseInt($('#log_retention_days').val());
            if (retentionDays < 7 || retentionDays > 365) {
                e.preventDefault();
                showMessage('Log retention period must be between 7 and 365 days.', 'error');
                return false;
            }
        }

        // Warning for disabling XML-RPC
        if ($('input[name="wp_app_core_security_policy[disable_xmlrpc]"]').is(':checked')) {
            const xmlrpcWarningShown = sessionStorage.getItem('xmlrpc_warning_shown');
            if (!xmlrpcWarningShown) {
                if (!confirm('WARNING: Disabling XML-RPC will prevent remote publishing and some mobile apps from working.\n\nAre you sure you want to disable XML-RPC?')) {
                    e.preventDefault();
                    return false;
                }
                sessionStorage.setItem('xmlrpc_warning_shown', 'true');
            }
        }

        // Warning for disabling REST API for anonymous users
        if ($('input[name="wp_app_core_security_policy[disable_rest_api_anonymous]"]').is(':checked')) {
            const restApiWarningShown = sessionStorage.getItem('rest_api_warning_shown');
            if (!restApiWarningShown) {
                if (!confirm('WARNING: Disabling REST API for anonymous users may break some plugins and themes that rely on the API.\n\nContinue?')) {
                    e.preventDefault();
                    return false;
                }
                sessionStorage.setItem('rest_api_warning_shown', 'true');
            }
        }

        // Show loading state
        form.addClass('loading');
        $('input[type="submit"]', form).prop('disabled', true);
    });

    /**
     * Handle WordPress Settings Update Success/Error
     * This fires after WordPress saves settings via options.php
     */
    $(window).on('load', function() {
        // Check for WordPress settings-updated parameter
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('settings-updated') === 'true') {
            showMessage('Security policy settings saved successfully.', 'success');

            // Clear session storage warnings after successful save
            sessionStorage.removeItem('xmlrpc_warning_shown');
            sessionStorage.removeItem('rest_api_warning_shown');
        }
    });

    /**
     * Helper: Convert bytes to human-readable format
     */
    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    /**
     * Add tooltip helper for upload size field
     */
    $('#max_upload_size').on('input', function() {
        const value = parseInt($(this).val());
        if (!isNaN(value) && value > 0) {
            const formatted = formatBytes(value);
            let description = $(this).siblings('.description');

            // Update description to show human-readable format
            const originalText = description.text().split('(')[0];
            description.html(originalText + '<strong>(' + formatted + ')</strong>');
        }
    });

    /**
     * Initialize tooltips on page load
     */
    $('#max_upload_size').trigger('input');

    /**
     * Cookie SameSite Info
     */
    $('#cookie_samesite').on('change', function() {
        const value = $(this).val();
        let message = '';

        switch(value) {
            case 'Strict':
                message = 'Strict mode provides the highest security but may affect user experience on some external login flows.';
                break;
            case 'Lax':
                message = 'Lax mode provides a balance between security and usability for most applications.';
                break;
            case 'None':
                message = 'None mode allows cross-site cookies. Only use this if you need cross-site functionality and have HTTPS enabled.';
                break;
        }

        if (message && value !== 'Strict') {
            showMessage(message, 'info');
        }
    });

    /**
     * Compliance Mode Info
     */
    $('#compliance_mode').on('change', function() {
        const value = $(this).val();
        let message = '';

        switch(value) {
            case 'gdpr':
                message = 'GDPR mode adds data protection features required for EU compliance, including consent management and data export capabilities.';
                break;
            case 'ccpa':
                message = 'CCPA mode adds privacy features required for California compliance, including opt-out rights and data disclosure.';
                break;
        }

        if (message) {
            showMessage(message, 'info');
        }
    });

    /**
     * Disable File Editing Warning
     */
    $('input[name="wp_app_core_security_policy[disable_file_editing]"]').on('change', function() {
        if ($(this).is(':checked')) {
            showMessage('File editing will be disabled. You will need to edit theme and plugin files via FTP or file manager.', 'info');
        }
    });

    /**
     * Force SSL Admin Info
     */
    $('input[name="wp_app_core_security_policy[force_ssl_admin]"]').on('change', function() {
        if ($(this).is(':checked')) {
            // Check if HTTPS is enabled
            if (window.location.protocol !== 'https:') {
                showMessage('WARNING: You are not currently using HTTPS. Enabling this option may lock you out of the admin area. Please configure SSL certificate first.', 'warning');
            }
        }
    });

    /**
     * Data Encryption Warning
     */
    $('input[name="wp_app_core_security_policy[data_encryption_enabled]"]').on('change', function() {
        if ($(this).is(':checked')) {
            if (!confirm('Enabling data encryption will encrypt sensitive data in the database.\n\nThis is a one-way operation. Once enabled, disabling it later may cause data issues.\n\nContinue?')) {
                $(this).prop('checked', false);
            }
        }
    });

    /**
     * X-Frame-Options Info
     */
    $('#x_frame_options').on('change', function() {
        const value = $(this).val();
        if (value === 'DENY') {
            showMessage('DENY mode provides maximum protection but will prevent your site from being embedded in any iframe, including your own pages.', 'info');
        }
    });

    /**
     * Handle Reset to Default button
     */
    $('#reset-security-policy').on('click', function(e) {
        e.preventDefault();

        if (!confirm('Are you sure you want to reset all security policy settings to their default values?\n\nThis action cannot be undone.')) {
            return;
        }

        const $resetBtn = $(this);
        const $submitBtn = $('#submit');

        // Disable both buttons
        $resetBtn.prop('disabled', true).text('Resetting...');
        $submitBtn.prop('disabled', true);

        // Send AJAX request
        $.ajax({
            url: wpAppCoreSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'reset_security_policy',
                nonce: wpAppCoreSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    // Reload page after 1 second to show fresh default values
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showMessage(response.data.message || 'Error resetting settings.', 'error');
                    // Re-enable buttons on error
                    $resetBtn.prop('disabled', false).text('Reset to Default');
                    $submitBtn.prop('disabled', false);
                }
            },
            error: function() {
                showMessage('AJAX error occurred while resetting settings.', 'error');
                // Re-enable buttons on error
                $resetBtn.prop('disabled', false).text('Reset to Default');
                $submitBtn.prop('disabled', false);
            }
        });
    });
});
