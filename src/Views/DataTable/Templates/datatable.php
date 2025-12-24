<?php
/**
 * Generic DataTable View Template
 *
 * @package     WP_App_Core
 * @subpackage  Views/DataTable/Templates
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/DataTable/Templates/datatable.php
 *
 * Description: Generic DataTable HTML structure.
 *              Rendered in left panel via wpapp_left_panel_content hook.
 *              Entity-agnostic, driven by $config.
 *
 * Variables:
 * @var array $config Configuration array with 'entity' key
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-1192)
 * - Initial creation
 * - Generic template for all entities
 * - Uses entity-specific table ID
 */

defined('ABSPATH') || exit;

// Get entity from config
$entity = $config['entity'] ?? 'generic';
$entity_slug = str_replace('_', '-', $entity);
$table_id = $entity_slug . '-list-table';
?>

<div class="<?php echo esc_attr($entity_slug); ?>-datatable-wrapper">
    <table id="<?php echo esc_attr($table_id); ?>" class="wpapp-datatable display" style="width:100%">
        <thead>
            <!-- Headers will be defined by DataTable columns config in JavaScript -->
        </thead>
        <tbody>
            <!-- DataTables will populate via AJAX -->
        </tbody>
    </table>
</div>
<!-- JavaScript handles DataTable initialization -->
