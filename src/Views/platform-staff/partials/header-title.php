<?php
/**
 * Header Title - Platform Staff Page Title and Subtitle
 *
 * @package     WP_App_Core
 * @subpackage  Views/Platform-Staff/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/platform-staff/partials/header-title.php
 *
 * Description: Displays platform staff page title and subtitle in dashboard header.
 *              Uses LOCAL SCOPE (platform-staff-*) classes only.
 *              Called by: PlatformStaffDashboardController::render_header_title()
 *
 * Context: Header component
 * Scope: LOCAL (platform-staff-* prefix only)
 *
 * Variables available:
 * - None (static content)
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-1192)
 * - Initial creation
 * - Follows wp-agency pattern
 * - Pure presentation layer
 */

defined('ABSPATH') || exit;
?>

<h1 class="platform-staff-page-title"><?php _e('Platform Staff', 'wp-app-core'); ?></h1>
<p class="platform-staff-page-subtitle"><?php _e('Manage platform staff members', 'wp-app-core'); ?></p>
