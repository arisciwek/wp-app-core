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
 * Path: /wp-app-core/src/Views/DataTable/Templates/StatsBoxTemplate.php
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
     * All classes use wpapp- prefix (from wp-app-core)
     *
     * @param string $entity Entity name
     * @return void
     */
    public static function render($entity) {
        // Get stats from filter
        $stats = self::get_stats($entity);

        // Always render container even if no stats (for plugin hooks)
        ?>
        <!-- Statistics Container -->
        <div class="wpapp-statistics-container">
            <?php
            /**
             * Action: Statistics cards content
             *
             * Plugins can hook here to render custom statistics cards
             * Cards should be rendered inside this container
             *
             * @param string $entity Entity name
             *
             * @example
             * add_action('wpapp_statistics_cards_content', function($entity) {
             *     if ($entity !== 'agency') return;
             *     echo '<div class="statistics-cards">';
             *     echo '<div class="stats-card">Custom Card</div>';
             *     echo '</div>';
             * });
             */
            do_action('wpapp_statistics_cards_content', $entity);
            ?>

            <?php if (!empty($stats)): ?>
            <div class="statistics-cards hidden" id="<?php echo esc_attr($entity); ?>-statistics">
                <?php foreach ($stats as $stat): ?>
                    <?php self::render_stat_box($stat, $entity); ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
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
         *     if ($entity !== 'agency') return $stats;
         *
         *     return [
         *         [
         *             'id' => 'agency-stat-total',
         *             'label' => 'Total Disnaker',
         *             'icon' => 'dashicons-building',
         *             'class' => 'primary'
         *         ],
         *         [
         *             'id' => 'agency-stat-active',
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
     * All classes use wpapp- prefix (from wp-app-core)
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
        <div class="stats-card <?php echo esc_attr($class); ?>"
             data-stat-id="<?php echo esc_attr($id); ?>"
             data-entity="<?php echo esc_attr($entity); ?>">

            <!-- Icon -->
            <?php if (!empty($icon)): ?>
                <div class="stats-icon">
                    <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                </div>
            <?php endif; ?>

            <!-- Content -->
            <div class="stats-content">
                <!-- Number (loaded via JavaScript) -->
                <h3 class="stats-number" id="<?php echo esc_attr($id); ?>">0</h3>

                <!-- Label -->
                <p class="stats-label">
                    <?php echo esc_html($label); ?>
                </p>
            </div>

        </div>
        <?php
    }
}
