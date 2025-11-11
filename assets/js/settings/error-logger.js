/**
 * Error Logger - Catch and persist JavaScript errors
 *
 * Temporary debug tool to catch fast-disappearing errors
 */
(function() {
    'use strict';

    // Store errors in localStorage
    const ERROR_KEY = 'wpapp_debug_errors';

    // Get existing errors
    let errors = [];
    try {
        errors = JSON.parse(localStorage.getItem(ERROR_KEY) || '[]');
    } catch (e) {
        errors = [];
    }

    // Global error handler
    window.addEventListener('error', function(event) {
        const errorInfo = {
            timestamp: new Date().toISOString(),
            message: event.message,
            filename: event.filename,
            lineno: event.lineno,
            colno: event.colno,
            stack: event.error ? event.error.stack : 'No stack trace',
            url: window.location.href
        };

        console.error('🔴 [Error Logger] Caught error:', errorInfo);

        // Save to localStorage
        errors.push(errorInfo);
        if (errors.length > 10) errors.shift(); // Keep last 10
        localStorage.setItem(ERROR_KEY, JSON.stringify(errors));

        // Also show in alert for immediate visibility
        alert('JavaScript Error Detected!\n\n' +
              'Message: ' + event.message + '\n' +
              'File: ' + event.filename + '\n' +
              'Line: ' + event.lineno + ':' + event.colno + '\n\n' +
              'Check console for details.');
    });

    // Log unhandled promise rejections
    window.addEventListener('unhandledrejection', function(event) {
        const errorInfo = {
            timestamp: new Date().toISOString(),
            type: 'unhandledrejection',
            reason: event.reason ? event.reason.toString() : 'Unknown',
            url: window.location.href
        };

        console.error('🔴 [Error Logger] Unhandled rejection:', errorInfo);

        errors.push(errorInfo);
        if (errors.length > 10) errors.shift();
        localStorage.setItem(ERROR_KEY, JSON.stringify(errors));
    });

    // Add helper to view errors in console
    window.wpAppShowErrors = function() {
        const stored = JSON.parse(localStorage.getItem(ERROR_KEY) || '[]');
        console.log('===== STORED ERRORS =====');
        if (stored.length === 0) {
            console.log('No errors logged');
        } else {
            stored.forEach(function(err, idx) {
                console.log('\n--- Error #' + (idx + 1) + ' ---');
                console.log('Time:', err.timestamp);
                console.log('Message:', err.message || err.reason);
                console.log('File:', err.filename);
                console.log('Line:', err.lineno + ':' + err.colno);
                console.log('URL:', err.url);
                if (err.stack) console.log('Stack:', err.stack);
            });
        }
        console.log('=========================');
        console.log('To clear: wpAppClearErrors()');
    };

    // Clear errors
    window.wpAppClearErrors = function() {
        localStorage.removeItem(ERROR_KEY);
        console.log('✅ Errors cleared');
    };

    console.log('🔍 [Error Logger] Active - errors will be caught and stored');
    console.log('📝 Type wpAppShowErrors() to view logged errors');
    console.log('🗑️ Type wpAppClearErrors() to clear error log');

    // Show existing errors on load
    if (errors.length > 0) {
        console.warn('⚠️ Found ' + errors.length + ' previous error(s). Type wpAppShowErrors() to view.');
    }
})();
