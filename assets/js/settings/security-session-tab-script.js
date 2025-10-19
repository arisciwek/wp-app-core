/**
 * Security Session Settings Script
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/settings/security-session-tab-script.js
 *
 * Description: Handles security session management settings functionality
 *              - Conditional field display
 *              - Form validation
 *              - User feedback
 *              - Time conversion helpers
 *
 * Dependencies:
 * - jQuery
 * - WordPress Settings API
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial implementation
 * - Conditional fields (login history)
 * - Field validation (timeouts, limits)
 * - Time conversion helpers
 */

jQuery(document).ready(function($) {

    /**
     * Toggle Login History Options
     */
    function toggleLoginHistoryOptions() {
        const isEnabled = $('.login-history-toggle').is(':checked');
        if (isEnabled) {
            $('body').addClass('login-history-enabled');
        } else {
            $('body').removeClass('login-history-enabled');
        }
    }

    $('.login-history-toggle').on('change', toggleLoginHistoryOptions);
    toggleLoginHistoryOptions(); // Initial state

    /**
     * Validate Session Idle Timeout
     */
    $('#session_idle_timeout').on('change', function() {
        let value = parseInt($(this).val());
        if (value < 300) {
            $(this).val(300);
            showMessage('Session idle timeout cannot be less than 300 seconds (5 minutes).', 'warning');
        }
    });

    /**
     * Validate Session Absolute Timeout
     */
    $('#session_absolute_timeout').on('change', function() {
        let value = parseInt($(this).val());
        if (value < 600) {
            $(this).val(600);
            showMessage('Session absolute timeout cannot be less than 600 seconds (10 minutes).', 'warning');
        }
    });

    /**
     * Validate Concurrent Sessions Limit
     */
    $('#concurrent_sessions_limit').on('change', function() {
        let value = parseInt($(this).val());
        if (value < 1) {
            $(this).val(1);
            showMessage('Concurrent sessions limit must be at least 1.', 'warning');
        } else if (value > 10) {
            $(this).val(10);
            showMessage('Maximum concurrent sessions limit is 10.', 'warning');
        }
    });

    /**
     * Validate Remember Me Duration
     */
    $('#remember_me_duration').on('change', function() {
        let value = parseInt($(this).val());
        if (value < 86400) {
            $(this).val(86400);
            showMessage('Remember me duration cannot be less than 86400 seconds (1 day).', 'warning');
        }
    });

    /**
     * Validate Max Login Attempts
     */
    $('#max_login_attempts').on('change', function() {
        let value = parseInt($(this).val());
        if (value < 3) {
            $(this).val(3);
            showMessage('Maximum login attempts cannot be less than 3.', 'warning');
        } else if (value > 10) {
            $(this).val(10);
            showMessage('Maximum login attempts cannot exceed 10.', 'warning');
        }
    });

    /**
     * Validate Lockout Duration
     */
    $('#lockout_duration').on('change', function() {
        let value = parseInt($(this).val());
        if (value < 300) {
            $(this).val(300);
            showMessage('Lockout duration cannot be less than 300 seconds (5 minutes).', 'warning');
        }
    });

    /**
     * Validate CAPTCHA Trigger
     */
    $('#captcha_after_failed_attempts').on('change', function() {
        let value = parseInt($(this).val());
        if (value < 1) {
            $(this).val(1);
            showMessage('CAPTCHA trigger must be at least 1 failed attempt.', 'warning');
        }

        // Warning if CAPTCHA trigger is higher than max attempts
        const maxAttempts = parseInt($('#max_login_attempts').val());
        if (value >= maxAttempts) {
            showMessage('Note: CAPTCHA trigger (' + value + ') should be less than max login attempts (' + maxAttempts + ') for effectiveness.', 'info');
        }
    });

    /**
     * Validate Login History Limit
     */
    $('#login_history_limit').on('change', function() {
        let value = parseInt($(this).val());
        if (value < 10) {
            $(this).val(10);
            showMessage('Login history limit cannot be less than 10.', 'warning');
        } else if (value > 1000) {
            $(this).val(1000);
            showMessage('Maximum login history limit is 1000.', 'warning');
        }
    });

    /**
     * Cross-validation: Idle timeout vs Absolute timeout
     */
    $('#session_idle_timeout, #session_absolute_timeout').on('blur', function() {
        const idleTimeout = parseInt($('#session_idle_timeout').val());
        const absoluteTimeout = parseInt($('#session_absolute_timeout').val());

        if (idleTimeout >= absoluteTimeout) {
            showMessage('Warning: Session idle timeout should be less than absolute timeout for best security practice.', 'warning');
        }
    });

    /**
     * Show notification message
     */
    function showMessage(message, type) {
        const messageDiv = $('#security-session-messages');
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
    $('.wp-app-core-security-session-form').on('submit', function(e) {
        const form = $(this);

        // Validate timeouts
        const idleTimeout = parseInt($('#session_idle_timeout').val());
        const absoluteTimeout = parseInt($('#session_absolute_timeout').val());

        if (idleTimeout < 300) {
            e.preventDefault();
            showMessage('Session idle timeout must be at least 300 seconds (5 minutes).', 'error');
            return false;
        }

        if (absoluteTimeout < 600) {
            e.preventDefault();
            showMessage('Session absolute timeout must be at least 600 seconds (10 minutes).', 'error');
            return false;
        }

        if (idleTimeout >= absoluteTimeout) {
            if (!confirm('Session idle timeout is greater than or equal to absolute timeout.\n\nThis means users may be logged out by absolute timeout before idle timeout takes effect.\n\nContinue anyway?')) {
                e.preventDefault();
                return false;
            }
        }

        // Validate concurrent sessions
        const concurrentLimit = parseInt($('#concurrent_sessions_limit').val());
        if (concurrentLimit < 1 || concurrentLimit > 10) {
            e.preventDefault();
            showMessage('Concurrent sessions limit must be between 1 and 10.', 'error');
            return false;
        }

        // Validate login attempts
        const maxAttempts = parseInt($('#max_login_attempts').val());
        if (maxAttempts < 3 || maxAttempts > 10) {
            e.preventDefault();
            showMessage('Max login attempts must be between 3 and 10.', 'error');
            return false;
        }

        // Validate lockout duration
        const lockoutDuration = parseInt($('#lockout_duration').val());
        if (lockoutDuration < 300) {
            e.preventDefault();
            showMessage('Lockout duration must be at least 300 seconds (5 minutes).', 'error');
            return false;
        }

        // Validate login history limit if enabled
        if ($('.login-history-toggle').is(':checked')) {
            const historyLimit = parseInt($('#login_history_limit').val());
            if (historyLimit < 10 || historyLimit > 1000) {
                e.preventDefault();
                showMessage('Login history limit must be between 10 and 1000.', 'error');
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
            showMessage('Security session settings saved successfully.', 'success');
        }
    });

    /**
     * Helper: Convert seconds to human-readable format
     */
    function formatSeconds(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;

        const parts = [];
        if (hours > 0) parts.push(hours + ' hour' + (hours !== 1 ? 's' : ''));
        if (minutes > 0) parts.push(minutes + ' minute' + (minutes !== 1 ? 's' : ''));
        if (secs > 0 || parts.length === 0) parts.push(secs + ' second' + (secs !== 1 ? 's' : ''));

        return parts.join(', ');
    }

    /**
     * Add tooltip helper for timeout fields
     */
    $('#session_idle_timeout, #session_absolute_timeout, #lockout_duration, #remember_me_duration').on('input', function() {
        const value = parseInt($(this).val());
        if (!isNaN(value) && value > 0) {
            const formatted = formatSeconds(value);
            let description = $(this).siblings('.description');

            // Update description to show human-readable format
            const originalText = description.text().split('(')[0];
            description.html(originalText + '<strong>(' + formatted + ')</strong>');
        }
    });

    /**
     * Initialize tooltips on page load
     */
    $('#session_idle_timeout, #session_absolute_timeout, #lockout_duration, #remember_me_duration').each(function() {
        $(this).trigger('input');
    });

    /**
     * Warning for Force Logout Setting
     */
    $('.force-logout-toggle').on('change', function() {
        if ($(this).is(':checked')) {
            showMessage('Force logout is enabled. Oldest sessions will be terminated when users exceed the concurrent session limit.', 'info');
        }
    });

    /**
     * Handle Reset to Default button
     */
    $('#reset-security-session').on('click', function(e) {
        e.preventDefault();

        if (!confirm('Are you sure you want to reset all security session settings to their default values?\n\nThis action cannot be undone.')) {
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
                action: 'reset_security_session',
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
