/**
 * Security Authentication Settings Tab Script
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Settings
 * @version     1.2.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/settings/security-authentication-tab-script.js
 *
 * Description: Handles security authentication settings tab functionality
 *              - Conditional field display (2FA, IP lists, access hours)
 *              - Reset to default functionality using WPModal
 *
 * Dependencies:
 * - jQuery
 * - WPModal (wp-modal plugin)
 * - settings-reset-helper.js
 *
 * Changelog:
 * 1.2.0 - 2025-01-12
 * - RESTORED: All conditional toggles (2FA, IP whitelist, IP blacklist, access hours)
 * - FIXED: pointer-events CSS pattern now working correctly
 * - Classes added to body for CSS control
 *
 * 1.1.0 - 2025-11-11
 * - Migrated to WPModal confirmation
 * - Using settings-reset-helper
 * - BROKEN: Accidentally removed conditional toggle logic
 *
 * 1.0.0 - 2025-10-19
 * - Initial implementation
 */

jQuery(document).ready(function($) {
    console.log('[Security Authentication Tab] 🔄 Initializing...');

    /**
     * Toggle 2FA Options
     * Adds/removes class to body for CSS pointer-events control
     */
    function toggle2FAOptions() {
        const isEnabled = $('.twofa-toggle').is(':checked');
        console.log('[Security Authentication Tab] 2FA enabled:', isEnabled);

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
        console.log('[Security Authentication Tab] IP Whitelist enabled:', isEnabled);

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
        console.log('[Security Authentication Tab] IP Blacklist enabled:', isEnabled);

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
        console.log('[Security Authentication Tab] Access Hours enabled:', isEnabled);

        if (isEnabled) {
            $('body').addClass('access-hours-enabled');
        } else {
            $('body').removeClass('access-hours-enabled');
        }
    }

    $('.access-hours-toggle').on('change', toggleAccessHoursOptions);
    toggleAccessHoursOptions(); // Initial state

    console.log('[Security Authentication Tab] ✅ Initialization complete');
});
