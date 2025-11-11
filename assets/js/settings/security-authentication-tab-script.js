/**
 * Security Authentication Settings Script
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/settings/security-authentication-tab-script.js
 *
 * Description: Handles security authentication settings functionality
 *              - Conditional field display
 *              - Form validation
 *              - User feedback
 *
 * Dependencies:
 * - jQuery
 * - WordPress Settings API
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial implementation
 * - Conditional fields (2FA, IP lists, access hours)
 * - Field validation
 */

jQuery(document).ready(function($) {

    /**
     * Toggle 2FA Options
     */
    function toggle2FAOptions() {
        const isEnabled = $('.twofa-toggle').is(':checked');
        if (isEnabled) {
            $('body').addClass('twofa-enabled');
        } else {
            $('body').removeClass('twofa-enabled');
        }
    }

    $('.twofa-toggle').on('change', toggle2FAOptions);
    toggle2FAOptions(); // Initial state

    /**
     * Toggle IP Whitelist Options
     */
    function toggleIPWhitelistOptions() {
        const isEnabled = $('.ip-whitelist-toggle').is(':checked');
        if (isEnabled) {
            $('body').addClass('ip-whitelist-enabled');
        } else {
            $('body').removeClass('ip-whitelist-enabled');
        }
    }

    $('.ip-whitelist-toggle').on('change', toggleIPWhitelistOptions);
    toggleIPWhitelistOptions(); // Initial state

    /**
     * Toggle IP Blacklist Options
     */
    function toggleIPBlacklistOptions() {
        const isEnabled = $('.ip-blacklist-toggle').is(':checked');
        if (isEnabled) {
            $('body').addClass('ip-blacklist-enabled');
        } else {
            $('body').removeClass('ip-blacklist-enabled');
        }
    }

    $('.ip-blacklist-toggle').on('change', toggleIPBlacklistOptions);
    toggleIPBlacklistOptions(); // Initial state

    /**
     * Toggle Access Hours Options
     */
    function toggleAccessHoursOptions() {
        const isEnabled = $('.access-hours-toggle').is(':checked');
        if (isEnabled) {
            $('body').addClass('access-hours-enabled');
        } else {
            $('body').removeClass('access-hours-enabled');
        }
    }

    $('.access-hours-toggle').on('change', toggleAccessHoursOptions);
    toggleAccessHoursOptions(); // Initial state

    /**
     * Validate Password Min Length
     */
    $('#password_min_length').on('change', function() {
        let value = parseInt($(this).val());
        if (value < 8) {
            $(this).val(8);
            showMessage('Minimum password length cannot be less than 8 characters.', 'warning');
        } else if (value > 128) {
            $(this).val(128);
            showMessage('Maximum password length is 128 characters.', 'warning');
        }
    });

    /**
     * Validate Password History
     */
    $('#password_history_count').on('change', function() {
        let value = parseInt($(this).val());
        if (value > 24) {
            $(this).val(24);
            showMessage('Maximum password history is 24 passwords.', 'warning');
        }
    });

    /**
     * Validate 2FA Methods - At least one must be selected
     */
    $('input[name="platform_security_authentication[twofa_methods][]"]').on('change', function() {
        const checked = $('input[name="platform_security_authentication[twofa_methods][]"]:checked').length;
        if (checked === 0) {
            // Prevent unchecking the last one
            $(this).prop('checked', true);
            showMessage('At least one authentication method must be selected.', 'error');
        }
    });

    /**
     * IP Whitelist Warning
     */
    $('.ip-whitelist-toggle').on('change', function() {
        if ($(this).is(':checked')) {
            if (!confirm('WARNING: Enabling IP whitelist may lock you out if not configured correctly.\n\nMake sure to add your current IP address to the whitelist.\n\nYour current IP: ' + $('strong:contains("' + $('.ip-whitelist-options').find('.description strong').text() + '")').text() + '\n\nContinue?')) {
                $(this).prop('checked', false);
                toggleIPWhitelistOptions();
            }
        }
    });

    /**
     * Show notification message
     */
    function showMessage(message, type) {
        const messageDiv = $('#security-auth-messages');
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
    $('.wp-app-core-security-auth-form').on('submit', function(e) {
        const form = $(this);

        // Validate 2FA
        if ($('.twofa-toggle').is(':checked')) {
            const methodsChecked = $('input[name="platform_security_authentication[twofa_methods][]"]:checked').length;
            if (methodsChecked === 0) {
                e.preventDefault();
                showMessage('Please select at least one 2FA authentication method.', 'error');
                return false;
            }
        }

        // Validate IP Whitelist
        if ($('.ip-whitelist-toggle').is(':checked')) {
            const whitelist = $('textarea[name="platform_security_authentication[ip_whitelist]"]').val().trim();
            if (whitelist === '') {
                if (!confirm('IP Whitelist is enabled but empty. This will block ALL access including yours!\n\nAre you sure you want to continue?')) {
                    e.preventDefault();
                    return false;
                }
            }
        }

        // Validate Access Hours
        if ($('.access-hours-toggle').is(':checked')) {
            const start = $('input[name="platform_security_authentication[admin_access_hours_start]"]').val();
            const end = $('input[name="platform_security_authentication[admin_access_hours_end]"]').val();

            if (!start || !end) {
                e.preventDefault();
                showMessage('Please specify both start and end times for admin access hours.', 'error');
                return false;
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
            showMessage('Security authentication settings saved successfully.', 'success');
        }
    });

    /**
     * Parse IP addresses from textarea (one per line)
     */
    function parseIPList(textarea) {
        const value = $(textarea).val();
        return value.split('\n')
                    .map(ip => ip.trim())
                    .filter(ip => ip.length > 0);
    }

    /**
     * Basic IP validation
     */
    function isValidIP(ip) {
        // Simple regex for IPv4 and CIDR notation
        const ipv4Pattern = /^(\d{1,3}\.){3}\d{1,3}(\/\d{1,2})?$/;
        return ipv4Pattern.test(ip);
    }

    /**
     * Validate IP lists on blur
     */
    $('textarea[name="platform_security_authentication[ip_whitelist]"], textarea[name="platform_security_authentication[ip_blacklist]"]').on('blur', function() {
        const ips = parseIPList(this);
        const invalid = [];

        ips.forEach(function(ip) {
            if (!isValidIP(ip)) {
                invalid.push(ip);
            }
        });

        if (invalid.length > 0) {
            showMessage('Warning: The following entries may not be valid IP addresses: ' + invalid.join(', '), 'warning');
        }
    });

    /**
     * Handle Reset to Default button
     */
    $('#reset-security-authentication').on('click', function(e) {
        e.preventDefault();

        if (!confirm('Are you sure you want to reset all security authentication settings to their default values?\n\nThis action cannot be undone.')) {
            return;
        }

        const $resetBtn = $(this);
        const $submitBtn = $('#submit');
        const form = $('.wp-app-core-security-auth-form');

        // Disable both buttons
        $resetBtn.prop('disabled', true).text('Resetting...');
        $submitBtn.prop('disabled', true);

        // Send AJAX request
        $.ajax({
            url: wpAppCoreSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'reset_security_authentication',
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
