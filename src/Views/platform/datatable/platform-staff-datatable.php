<?php
/**
 * Platform Staff DataTable View - Left Panel
 *
 * @package     WP_App_Core
 * @subpackage  Views/Platform/DataTable
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/platform/datatable/datatable.php
 *
 * Description: DataTable HTML untuk platform staff listing di left panel.
 *              Rendered via wpdt_left_panel_content hook.
 *              Used by wp-datatable DualPanel layout.
 *              PURE HTML - no JavaScript (separation of concerns).
 *
 * Important Classes:
 * - wpdt-datatable: Required for panel-manager.js to find DataTable instance
 *
 * Changelog:
 * 1.0.0 - 2025-12-25
 * - Created for wp-datatable integration
 * - Standalone DataTable view for left panel
 */

defined('ABSPATH') || exit;
?>

<div class="wpdt-datatable-wrapper">
    <table id="platform-staff-datatable" class="wpdt-datatable display" style="width:100%">
        <thead>
            <tr>
                <th><?php _e('Name', 'wp-app-core'); ?></th>
                <th><?php _e('Email', 'wp-app-core'); ?></th>
                <th><?php _e('Phone', 'wp-app-core'); ?></th>
                <th><?php _e('Status', 'wp-app-core'); ?></th>
                <th><?php _e('Actions', 'wp-app-core'); ?></th>
            </tr>
        </thead>
        <tbody>
            <!-- DataTable will populate via AJAX -->
        </tbody>
    </table>
</div>
