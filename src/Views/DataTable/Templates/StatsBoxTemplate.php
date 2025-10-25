<?php
/**
 * Stats Box Template - Base
 *
 * Provides reusable statistics cards/boxes for dashboard.
 * Follows existing pattern from customer/company dashboards.
 *
 * @package WPAppCore
 * @subpackage Views\DataTable\Templates
 * @since 1.0.0
 * @author arisciwek
 *
 * Stats Structure:
 * ```php
 * [
 *     [
 *         'id' => 'total-customers',
 *         'label' => 'Total Customers',
 *         'icon' => 'dashicons-groups',  // Optional
 *         'class' => 'primary'           // Optional: primary, success, warning, danger
 *     ]
 * ]
 * ```
 */

namespace WPAppCore\Views\DataTable\Templates;

defined('ABSPATH') || exit;

class StatsBoxTemplate {

    /**
     * Render statistics boxes
     *
     * @param string $entity Entity name
     * @return void
     */
    public static function render($entity) {
        // Get stats from filter
        $stats = self::get_stats($entity);

        if (empty($stats)) {
            // No stats registered
            return;
        }

        ?>
        <div class="wpapp-stats-container">
            <?php foreach ($stats as $stat): ?>
                <?php self::render_stat_box($stat, $entity); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Get stats for entity via filter
     *
     * @param string $entity Entity name
     * @return array Stats array
     */
    private static function get_stats($entity) {
        /**
         * Filter: Register statistics for entity
         *
         * Plugins can register stats boxes for their entities
         *
         * @param array $stats Stats array
         * @param string $entity Entity name
         *
         * @return array Modified stats array
         *
         * @example
         * add_filter('wpapp_datatable_stats', function($stats, $entity) {
         *     if ($entity !== 'customer') return $stats;
         *
         *     return [
         *         [
         *             'id' => 'total-customers',
         *             'label' => 'Total Customers',
         *             'icon' => 'dashicons-groups',
         *             'class' => 'primary'
         *         ],
         *         [
         *             'id' => 'active-customers',
         *             'label' => 'Active',
         *             'icon' => 'dashicons-yes-alt',
         *             'class' => 'success'
         *         ]
         *     ];
         * }, 10, 2);
         */
        $stats = apply_filters('wpapp_datatable_stats', [], $entity);

        return $stats;
    }

    /**
     * Render single stat box
     *
     * @param array $stat Stat configuration
     * @param string $entity Entity name
     * @return void
     */
    private static function render_stat_box($stat, $entity) {
        // Parse stat data
        $id = isset($stat['id']) ? $stat['id'] : '';
        $label = isset($stat['label']) ? $stat['label'] : '';
        $icon = isset($stat['icon']) ? $stat['icon'] : 'dashicons-chart-bar';
        $class = isset($stat['class']) ? $stat['class'] : '';

        if (empty($id) || empty($label)) {
            return; // Invalid stat
        }

        ?>
        <div class="wpapp-stat-box <?php echo esc_attr($class); ?>"
             data-stat-id="<?php echo esc_attr($id); ?>"
             data-entity="<?php echo esc_attr($entity); ?>">

            <!-- Icon -->
            <?php if (!empty($icon)): ?>
                <div class="wpapp-stat-icon">
                    <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                </div>
            <?php endif; ?>

            <!-- Content -->
            <div class="wpapp-stat-content">
                <!-- Number (loaded via JavaScript) -->
                <div class="wpapp-stat-number" id="<?php echo esc_attr($id); ?>">
                    <span class="wpapp-stat-loading">
                        <span class="spinner is-active"></span>
                    </span>
                </div>

                <!-- Label -->
                <div class="wpapp-stat-label">
                    <?php echo esc_html($label); ?>
                </div>
            </div>

        </div>
        <?php
    }
}
