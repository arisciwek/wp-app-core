<?php
/**
 * Stat Cards - Platform Staff Statistics Display
 *
 * @package     WP_App_Core
 * @subpackage  Views/Platform-Staff/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/platform-staff/partials/stat-cards.php
 *
 * Description: Displays platform staff statistics cards in dashboard header.
 *              Uses LOCAL SCOPE (platform-staff-*) classes only.
 *              Called by: PlatformStaffDashboardController::render_header_cards()
 *
 * Context: Statistics cards fragment
 * Scope: LOCAL (platform-staff-* prefix only)
 *
 * Variables available:
 * @var int $total Total staff count
 * @var int $active Active staff count
 * @var int $inactive Inactive staff count
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-1192)
 * - Initial creation
 * - Follows wp-agency pattern
 * - Pure presentation layer
 */

defined('ABSPATH') || exit;

// Ensure variables exist
if (!isset($total, $active, $inactive)) {
    echo '<p>' . __('Statistics data not available', 'wp-app-core') . '</p>';
    return;
}
?>

<div class="platform-staff-statistics-cards" id="platform-staff-statistics">
    <!-- Total Card -->
    <div class="platform-staff-stat-card platform-staff-theme-blue" data-card-id="total-platform-staff">
        <div class="platform-staff-stat-icon">
            <span class="dashicons dashicons-groups"></span>
        </div>
        <div class="platform-staff-stat-content">
            <div class="platform-staff-stat-number"><?php echo esc_html($total ?: '0'); ?></div>
            <div class="platform-staff-stat-label"><?php _e('Total Staff', 'wp-app-core'); ?></div>
        </div>
    </div>

    <!-- Active Card -->
    <div class="platform-staff-stat-card platform-staff-theme-green" data-card-id="active-platform-staff">
        <div class="platform-staff-stat-icon">
            <span class="dashicons dashicons-yes-alt"></span>
        </div>
        <div class="platform-staff-stat-content">
            <div class="platform-staff-stat-number"><?php echo esc_html($active ?: '0'); ?></div>
            <div class="platform-staff-stat-label"><?php _e('Active', 'wp-app-core'); ?></div>
        </div>
    </div>

    <!-- Inactive Card -->
    <div class="platform-staff-stat-card platform-staff-theme-orange" data-card-id="inactive-platform-staff">
        <div class="platform-staff-stat-icon">
            <span class="dashicons dashicons-dismiss"></span>
        </div>
        <div class="platform-staff-stat-content">
            <div class="platform-staff-stat-number"><?php echo esc_html($inactive ?: '0'); ?></div>
            <div class="platform-staff-stat-label"><?php _e('Inactive', 'wp-app-core'); ?></div>
        </div>
    </div>
</div>
