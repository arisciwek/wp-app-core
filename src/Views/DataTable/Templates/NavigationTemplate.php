<?php
/**
 * Navigation Container Template
 *
 * Container for statistics and filters sections.
 * Always visible for all users (content inside is conditional).
 *
 * @package WPAppCore
 * @subpackage Views\DataTable\Templates
 * @since 1.0.0
 * @author arisciwek
 *
 * Usage:
 * ```php
 * use WPAppCore\Views\DataTable\Templates\NavigationTemplate;
 *
 * NavigationTemplate::render([
 *     'entity' => 'customer',
 *     'has_stats' => true
 * ]);
 * ```
 */

namespace WPAppCore\Views\DataTable\Templates;

defined('ABSPATH') || exit;

class NavigationTemplate {

    /**
     * Render navigation container
     *
     * Container always renders, content inside is conditional based on:
     * - has_stats: Show statistics section
     * - Plugins hook to wpapp_dashboard_filters for filter content
     *
     * @param array $config Configuration array
     * @return void
     */
    public static function render($config) {
        ?>
        <!-- Navigation Container (Global Scope) - Contains Stats & Filters -->
        <div class="wpapp-navigation-container">

            <?php
            /**
             * Action: Before navigation content
             *
             * @param array $config Dashboard configuration
             * @param string $entity Entity name
             */
            do_action('wpapp_navigation_before_content', $config, $config['entity']);
            ?>

            <!-- Split Layout: Filters (Left) | Stats (Right) -->
            <div class="wpapp-navigation-split">

                <!-- LEFT: Filters Section -->
                <div class="wpapp-navigation-left">
                    <div class="wpapp-filters-wrapper">
                        <?php
                        /**
                         * Action: Dashboard filters (search, status filter, etc)
                         *
                         * Plugins should hook here to render filter controls
                         * Each plugin renders their own HTML with their own classes
                         *
                         * @param array $config Dashboard configuration
                         * @param string $entity Entity name
                         *
                         * @example
                         * add_action('wpapp_dashboard_filters', function($config, $entity) {
                         *     if ($entity !== 'agency') return;
                         *     include WP_AGENCY_PATH . 'src/Views/agency/partials/status-filter.php';
                         * }, 10, 2);
                         */
                        do_action('wpapp_dashboard_filters', $config, $config['entity']);
                        ?>
                    </div>
                </div>

                <!-- RIGHT: Statistics Section -->
                <div class="wpapp-navigation-right">
                    <?php if (!empty($config['has_stats'])): ?>
                        <?php self::render_stats_section($config); ?>
                    <?php endif; ?>

                    <?php
                    /**
                     * Action: After statistics section
                     *
                     * @param array $config Dashboard configuration
                     * @param string $entity Entity name
                     */
                    do_action('wpapp_dashboard_after_stats', $config, $config['entity']);
                    ?>
                </div>

            </div>

            <?php
            /**
             * Action: After navigation content
             *
             * @param array $config Dashboard configuration
             * @param string $entity Entity name
             */
            do_action('wpapp_navigation_after_content', $config, $config['entity']);
            ?>

        </div>
        <?php
    }

    /**
     * Render statistics section
     *
     * @param array $config Configuration
     * @return void
     */
    private static function render_stats_section($config) {
        ?>
        <?php
        /**
         * Action: Before stats section
         *
         * @param array $config Dashboard configuration
         * @param string $entity Entity name
         */
        do_action('wpapp_dashboard_before_stats', $config, $config['entity']);
        ?>

        <div class="wpapp-dashboard-stats">
            <?php StatsBoxTemplate::render($config['entity']); ?>
        </div>

        <?php
    }
}
