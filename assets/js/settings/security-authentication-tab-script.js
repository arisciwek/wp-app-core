/**
 * Security Authentication Settings Tab Script
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Settings
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/settings/security-authentication-tab-script.js
 *
 * Description: Handles security authentication settings tab functionality
 *              - Reset to default functionality using WPModal
 *              - IP whitelist warnings
 *
 * Dependencies:
 * - jQuery
 * - WPModal (wp-modal plugin)
 * - settings-reset-helper.js
 *
 * Changelog:
 * 1.1.0 - 2025-11-11
 * - Migrated to WPModal confirmation
 * - Using settings-reset-helper
 *
 * 1.0.0 - 2025-10-19
 * - Initial implementation
 */

jQuery(document).ready(function($) {
    // DEPRECATED: Reset handler moved to page-level
    // Using global settings-reset-helper.js (auto-initialize)

    console.log('[Security Authentication Tab] Using global handlers');

    // Add any tab-specific logic here if needed in future
    // Example: conditional field display, validation, etc.
});
