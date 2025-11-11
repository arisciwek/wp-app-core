/**
 * Email Settings Tab Script - DEPRECATED
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Settings
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/settings/email-tab-script.js
 *
 * Description: DEPRECATED - Tab-specific logic no longer needed.
 *              Save & Reset functionality moved to page-level (settings-page.php).
 *              This file kept for backward compatibility but does nothing.
 *
 * Dependencies: None (no longer needed)
 *
 * Changelog:
 * 2.0.0 - 2025-11-12
 * - DEPRECATED: Moved to page-level global scope
 * - Reset handler now in settings-reset-helper.js (auto-initialize)
 * - Save handler now in settings-script.js
 * - File kept empty for backward compatibility
 *
 * 1.1.0 - 2025-11-11
 * - Migrated to WPModal confirmation
 *
 * 1.0.0 - 2025-10-19
 * - Initial implementation
 */

jQuery(document).ready(function($) {
    // DEPRECATED: No tab-specific logic needed
    // All functionality handled by global scripts:
    // - settings-script.js (save button)
    // - settings-reset-helper.js (reset button)

    console.log('[Email Tab] Using global handlers (no tab-specific code needed)');
});
