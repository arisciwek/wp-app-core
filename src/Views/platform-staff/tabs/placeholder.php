<?php
/**
 * Platform Staff Additional Tab - Placeholder
 *
 * @package     WP_App_Core
 * @subpackage  Views/Platform-Staff/Tabs
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/platform-staff/tabs/placeholder.php
 *
 * Description: Placeholder tab for future expansion.
 *              Can be used for: Documents, Activity Log, Settings, etc.
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-1192)
 * - Initial creation
 * - Empty placeholder for future features
 */

defined('ABSPATH') || exit;
?>

<div class="platform-staff-placeholder-content">
    <div class="platform-staff-empty-state">
        <span class="dashicons dashicons-info-outline"></span>
        <h3><?php _e('Additional Information', 'wp-app-core'); ?></h3>
        <p><?php _e('This section is reserved for future features.', 'wp-app-core'); ?></p>
        <p class="description">
            <?php _e('Possible features: Activity logs, documents, settings, permissions, etc.', 'wp-app-core'); ?>
        </p>
    </div>
</div>
