<?php
/**
 * Header Buttons - Platform Staff Action Buttons
 *
 * @package     WP_App_Core
 * @subpackage  Views/Platform-Staff/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/platform-staff/partials/header-buttons.php
 *
 * Description: Displays action buttons in dashboard header (Print, Export, Add).
 *              Uses LOCAL SCOPE (platform-staff-*) classes only.
 *              Called by: PlatformStaffDashboardController::render_header_buttons()
 *
 * Context: Header component
 * Scope: LOCAL (platform-staff-* prefix only)
 *
 * Variables available:
 * - None (uses current_user_can checks)
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-1192)
 * - Initial creation
 * - Follows wp-agency pattern
 * - Permission-based button display
 */

defined('ABSPATH') || exit;
?>

<div class="platform-staff-header-buttons">
    <?php if (current_user_can('view_platform_users')): ?>
        <button type="button" class="button platform-staff-print-btn" id="platform-staff-print-btn">
            <span class="dashicons dashicons-printer"></span>
            <?php _e('Print', 'wp-app-core'); ?>
        </button>

        <button type="button" class="button platform-staff-export-btn" id="platform-staff-export-btn">
            <span class="dashicons dashicons-download"></span>
            <?php _e('Export', 'wp-app-core'); ?>
        </button>
    <?php endif; ?>

    <?php if (current_user_can('create_platform_users')): ?>
        <a href="#" class="button button-primary platform-staff-add-btn">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php _e('Add Staff', 'wp-app-core'); ?>
        </a>
    <?php endif; ?>
</div>
