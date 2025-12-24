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
 * @version 1.2.0
 * @author arisciwek
 *
 * Path: /wp-app-core/src/Views/DataTable/Templates/StatsBoxTemplate.php
 *
 * Changelog:
 * 1.2.0 - 2025-10-29 (TODO-3089)
 * - BREAKING: Removed filter-based rendering (over-engineering)
 * - Deleted: get_stats() method (wpapp_datatable_stats filter)
 * - Deleted: render_stat_box() method (wpapp-stats-* HTML rendering)
 * - Deleted: All wpapp-stats-* CSS class usage
 * - Reason: Same issue as deleted TabViewTemplate - dual rendering mechanism
 * - Pattern: Now pure infrastructure (container + hook only)
 * - Plugins render their own HTML with plugin-specific CSS classes
 * - Impact: No active users, acceptable breaking change
 *
 * 1.1.0 - 2025-10-29 (TODO-3089)
 * - BREAKING: Added wpapp- prefix to all CSS classes
 * - Changed: statistics-cards → wpapp-statistics-cards
 * - Changed: stats-card → wpapp-stats-card
 * - Changed: stats-icon → wpapp-stats-icon
 * - Changed: stats-content → wpapp-stats-content
 * - Changed: stats-number → wpapp-stats-number
 * - Changed: stats-label → wpapp-stats-label
 * - Reason: Consistent with global scope naming convention (wpapp- = wp-app-core)
 * - Impact: No active users, acceptable breaking change
 *
 * 1.0.0 - Initial version
 * - Statistics box rendering with hook-based content injection
 * - No wpapp- prefix (violated scope convention)
 *
 * Usage:
 * ```php
 * // In plugin controller:
 * add_action('wpapp_statistics_cards_content', function($entity) {
 *     if ($entity !== 'agency') return;
 *     echo '<div class="agency-statistics-cards">';
 *     echo '<div class="agency-stat-card">Custom Card</div>';
 *     echo '</div>';
 * }, 10);
 * ```
 */

namespace WPAppCore\Views\DataTable\Templates;

defined('ABSPATH') || exit;

class StatsBoxTemplate {

    /**
     * Render statistics container with hook
     *
     * Provides empty container for plugins to inject statistics.
     * Each plugin renders their own HTML with plugin-specific CSS classes.
     *
     * Pattern: Infrastructure (container + hook), not implementation
     *
     * @param string $entity Entity name
     * @return void
     */
    public static function render($entity) {
        ?>
        <!-- Statistics Container (Global Scope) -->
        <div class="wpapp-statistics-container">
            <?php
            /**
             * Action: Statistics cards content
             *
             * Plugins should hook here to render custom statistics cards
             * Each plugin renders their own HTML with their own CSS classes
             *
             * IMPORTANT: Use plugin-specific CSS classes (e.g., agency-, customer-)
             *
             * @param string $entity Entity name
             *
             * @example
             * add_action('wpapp_statistics_cards_content', function($entity) {
             *     if ($entity !== 'agency') return;
             *     echo '<div class="agency-statistics-cards">';
             *     echo '<div class="agency-stat-card">Custom Card</div>';
             *     echo '</div>';
             * }, 10);
             */
            do_action('wpapp_statistics_cards_content', $entity);
            ?>
        </div>
        <?php
    }
}
