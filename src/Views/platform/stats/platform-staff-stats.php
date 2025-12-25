<?php
/**
 * Platform Staff Statistics Boxes View
 *
 * @package     WP_App_Core
 * @subpackage  Views/Platform/Stats
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/platform/stats/stats-boxes.php
 *
 * Description: Statistics boxes untuk platform staff dashboard.
 *              Rendered via wpdt_statistics_content hook.
 *              Stats loaded via AJAX (get_platform_staff_stats).
 *
 * Changelog:
 * 1.0.0 - 2025-12-25
 * - Created for wp-datatable integration
 */

defined('ABSPATH') || exit;
?>

<div class="wpdt-stats-container">
    <!-- Stats will be loaded via AJAX -->
    <div class="wpdt-stats-loading">
        <span class="spinner is-active"></span>
        <?php _e('Loading statistics...', 'wp-app-core'); ?>
    </div>
</div>
