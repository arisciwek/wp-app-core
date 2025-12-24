<?php
/**
 * Filters Template - Base
 *
 * Provides reusable filter controls for dashboard DataTable.
 * Follows same pattern as StatsBoxTemplate for consistency.
 *
 * @package WPAppCore
 * @subpackage Views\DataTable\Templates
 * @since 1.0.0
 * @author arisciwek
 *
 * Path: /wp-app-core/src/Views/DataTable/Templates/FiltersTemplate.php
 *
 * Changelog:
 * 1.0.0 - 2025-10-26
 * - Initial implementation
 * - Match pattern with StatsBoxTemplate
 * - Support multiple filter types: select, search, date_range
 * - Backward compatible with wpapp_dashboard_filters action
 * - Centralized filter rendering
 *
 * Filter Structure:
 * ```php
 * [
 *     'status' => [
 *         'type' => 'select',
 *         'label' => 'Filter Status:',
 *         'id' => 'entity-status-filter',
 *         'options' => [
 *             'all' => 'All Status',
 *             'active' => 'Active',
 *             'inactive' => 'Inactive'
 *         ],
 *         'default' => 'active',
 *         'class' => 'status-filter'  // Optional additional CSS class
 *     ],
 *     'search' => [
 *         'type' => 'search',
 *         'label' => 'Search:',
 *         'id' => 'entity-search',
 *         'placeholder' => 'Search...',
 *         'class' => 'search-input'
 *     ]
 * ]
 * ```
 *
 * Supported Filter Types:
 * - select: Dropdown select
 * - search: Search input
 * - date_range: Date range picker (future)
 */

namespace WPAppCore\Views\DataTable\Templates;

defined('ABSPATH') || exit;

class FiltersTemplate {

    /**
     * Render filter controls
     *
     * All classes use wpapp- prefix (from wp-app-core)
     * Backward compatible with wpapp_dashboard_filters action
     *
     * @param string $entity Entity name
     * @param array $config Configuration array (for backward compatibility)
     * @return void
     */
    public static function render($entity, $config = []) {
        // Get filters from filter hook
        $filters = self::get_filters($entity);

        ?>
        <!-- Filters Container -->
        <div class="wpapp-filters-container">
            <div class="wpapp-datatable-filters">
                <?php if (!empty($filters)): ?>
                    <?php foreach ($filters as $filter_key => $filter): ?>
                        <?php self::render_filter_control($filter_key, $filter, $entity); ?>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php
                /**
                 * Action: Dashboard filters (BACKWARD COMPATIBILITY)
                 *
                 * Old hook - still supported for plugins using action approach
                 * Plugins should migrate to 'wpapp_datatable_filters' filter for consistency
                 *
                 * @param array $config Dashboard configuration
                 * @param string $entity Entity name
                 *
                 * @deprecated Use 'wpapp_datatable_filters' filter instead
                 *
                 * @example OLD (still works):
                 * add_action('wpapp_dashboard_filters', function($config, $entity) {
                 *     if ($entity !== 'agency') return;
                 *     echo '<select>...</select>';
                 * }, 10, 2);
                 */
                do_action('wpapp_dashboard_filters', $config, $entity);
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Get filters for entity via filter hook
     *
     * @param string $entity Entity name
     * @return array Filters array
     */
    private static function get_filters($entity) {
        /**
         * Filter: Register filters for entity
         *
         * Plugins can register filter controls for their entities
         * This is the RECOMMENDED approach (matches StatsBoxTemplate pattern)
         *
         * @param array $filters Filters array
         * @param string $entity Entity name
         *
         * @return array Modified filters array
         *
         * @example
         * add_filter('wpapp_datatable_filters', function($filters, $entity) {
         *     if ($entity !== 'agency') return $filters;
         *
         *     return [
         *         'status' => [
         *             'type' => 'select',
         *             'label' => __('Filter Status:', 'wp-agency'),
         *             'id' => 'agency-status-filter',
         *             'options' => [
         *                 'all' => __('Semua Status', 'wp-agency'),
         *                 'active' => __('Aktif', 'wp-agency'),
         *                 'inactive' => __('Tidak Aktif', 'wp-agency')
         *             ],
         *             'default' => 'active',
         *             'class' => 'agency-filter-select'
         *         ],
         *         'search' => [
         *             'type' => 'search',
         *             'label' => __('Search:', 'wp-agency'),
         *             'id' => 'agency-search',
         *             'placeholder' => __('Search agencies...', 'wp-agency')
         *         ]
         *     ];
         * }, 10, 2);
         */
        $filters = apply_filters('wpapp_datatable_filters', [], $entity);

        return $filters;
    }

    /**
     * Render single filter control
     *
     * Supports multiple filter types: select, search, date_range
     *
     * @param string $filter_key Filter key/identifier
     * @param array $filter Filter configuration
     * @param string $entity Entity name
     * @return void
     */
    private static function render_filter_control($filter_key, $filter, $entity) {
        // Validate filter structure
        if (empty($filter['type'])) {
            return; // Invalid filter
        }

        $type = $filter['type'];

        // Render based on type
        switch ($type) {
            case 'select':
                self::render_select_filter($filter_key, $filter, $entity);
                break;

            case 'search':
                self::render_search_filter($filter_key, $filter, $entity);
                break;

            case 'date_range':
                self::render_date_range_filter($filter_key, $filter, $entity);
                break;

            default:
                // Unknown filter type
                break;
        }
    }

    /**
     * Render select dropdown filter
     *
     * @param string $filter_key Filter key
     * @param array $filter Filter config
     * @param string $entity Entity name
     * @return void
     */
    private static function render_select_filter($filter_key, $filter, $entity) {
        $id = isset($filter['id']) ? $filter['id'] : $entity . '-' . $filter_key . '-filter';
        $label = isset($filter['label']) ? $filter['label'] : '';
        $options = isset($filter['options']) ? $filter['options'] : [];
        $default = isset($filter['default']) ? $filter['default'] : '';
        $class = isset($filter['class']) ? $filter['class'] : '';

        // Get current value from GET parameter
        $param_name = $filter_key . '_filter';
        $current_value = isset($_GET[$param_name]) ? sanitize_text_field($_GET[$param_name]) : $default;

        if (empty($options)) {
            return; // No options
        }

        ?>
        <div class="wpapp-filter-group wpapp-filter-select-group">
            <?php if (!empty($label)): ?>
                <label for="<?php echo esc_attr($id); ?>" class="wpapp-filter-label">
                    <?php echo esc_html($label); ?>
                </label>
            <?php endif; ?>

            <select id="<?php echo esc_attr($id); ?>"
                    class="wpapp-filter-control wpapp-filter-select <?php echo esc_attr($class); ?>"
                    data-filter-key="<?php echo esc_attr($filter_key); ?>"
                    data-entity="<?php echo esc_attr($entity); ?>"
                    data-current="<?php echo esc_attr($current_value); ?>">
                <?php foreach ($options as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>"
                            <?php selected($current_value, $value); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    /**
     * Render search input filter
     *
     * @param string $filter_key Filter key
     * @param array $filter Filter config
     * @param string $entity Entity name
     * @return void
     */
    private static function render_search_filter($filter_key, $filter, $entity) {
        $id = isset($filter['id']) ? $filter['id'] : $entity . '-' . $filter_key;
        $label = isset($filter['label']) ? $filter['label'] : '';
        $placeholder = isset($filter['placeholder']) ? $filter['placeholder'] : '';
        $class = isset($filter['class']) ? $filter['class'] : '';

        // Get current value from GET parameter
        $param_name = $filter_key . '_filter';
        $current_value = isset($_GET[$param_name]) ? sanitize_text_field($_GET[$param_name]) : '';

        ?>
        <div class="wpapp-filter-group wpapp-filter-search-group">
            <?php if (!empty($label)): ?>
                <label for="<?php echo esc_attr($id); ?>" class="wpapp-filter-label">
                    <?php echo esc_html($label); ?>
                </label>
            <?php endif; ?>

            <input type="search"
                   id="<?php echo esc_attr($id); ?>"
                   class="wpapp-filter-control wpapp-filter-search <?php echo esc_attr($class); ?>"
                   data-filter-key="<?php echo esc_attr($filter_key); ?>"
                   data-entity="<?php echo esc_attr($entity); ?>"
                   placeholder="<?php echo esc_attr($placeholder); ?>"
                   value="<?php echo esc_attr($current_value); ?>" />
        </div>
        <?php
    }

    /**
     * Render date range filter
     *
     * TODO: Implement date range picker
     * Currently placeholder for future implementation
     *
     * @param string $filter_key Filter key
     * @param array $filter Filter config
     * @param string $entity Entity name
     * @return void
     */
    private static function render_date_range_filter($filter_key, $filter, $entity) {
        // Future implementation
        // Will use datepicker or similar library
        ?>
        <!-- Date range filter - TODO: Implement -->
        <div class="wpapp-filter-group wpapp-filter-date-range-group">
            <span class="wpapp-filter-placeholder">Date Range Filter (Coming Soon)</span>
        </div>
        <?php
    }
}
