<?php
/**
 * Generic Status Filter Partial
 *
 * @package     WP_App_Core
 * @subpackage  Views/DataTable/Templates/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/DataTable/Templates/partials/status-filter.php
 *
 * Description: Generic partial template untuk status filter.
 *              Works with any entity using DataTable system.
 *              Entity-agnostic, driven by $config.
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-1192)
 * - Initial creation for platform staff
 * - Generic implementation for all entities
 * - Uses entity slug for CSS classes and IDs
 */

defined('ABSPATH') || exit;

// Get entity - either from parameter or config
if (!isset($entity)) {
    $entity = $config['entity'] ?? 'generic';
}
$entity_slug = str_replace('_', '-', $entity);

// Get current status filter from GET parameter
$current_status = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : 'aktif';

// Permission check - allow filtering by default, can be overridden
$can_filter = apply_filters("wpapp_{$entity}_can_filter", true);
?>

<?php if ($can_filter): ?>
<div class="<?php echo esc_attr($entity_slug); ?>-status-filter-group wpapp-status-filter-group">
    <label for="<?php echo esc_attr($entity_slug); ?>-status-filter" class="wpapp-filter-label">
        <?php esc_html_e('Filter Status:', 'wp-app-core'); ?>
    </label>
    <select id="<?php echo esc_attr($entity_slug); ?>-status-filter" class="wpapp-filter-select" data-current="<?php echo esc_attr($current_status); ?>">
        <option value="all" <?php selected($current_status, 'all'); ?>>
            <?php esc_html_e('Semua Status', 'wp-app-core'); ?>
        </option>
        <option value="aktif" <?php selected($current_status, 'aktif'); ?>>
            <?php esc_html_e('Aktif', 'wp-app-core'); ?>
        </option>
        <option value="tidak aktif" <?php selected($current_status, 'tidak aktif'); ?>>
            <?php esc_html_e('Tidak Aktif', 'wp-app-core'); ?>
        </option>
    </select>
</div>
<?php endif; ?>
