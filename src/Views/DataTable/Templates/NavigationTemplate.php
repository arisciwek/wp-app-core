<?php
/**
 * Navigation Container Template
 *
 * Orchestrator template that manages layout and renders:
 * - Statistics section (via StatsBoxTemplate)
 * - Filters section (via FiltersTemplate)
 *
 * Always visible for all users (content inside is conditional).
 *
 * @package WPAppCore
 * @subpackage Views\DataTable\Templates
 * @since 1.0.0
 * @author arisciwek
 *
 * Path: /wp-app-core/src/Views/DataTable/Templates/NavigationTemplate.php
 *
 * Changelog:
 * 1.1.0 - 2025-10-26
 * - Added: FiltersTemplate::render() for centralized filter rendering
 * - Changed: Filters now use template pattern (consistent with Stats)
 * - Backward compatible: wpapp_dashboard_filters action still supported
 * - NavigationTemplate acts as orchestrator/container
 *
 * 1.0.0 - 2025-10-25
 * - Initial implementation
 * - Stacked layout: Statistics → Filters
 * - Direct action hooks for filters
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
 *
 * Components:
 * - StatsBoxTemplate: Statistics cards (if has_stats = true)
 * - FiltersTemplate: Filter controls (always rendered)
 */

namespace WPAppCore\Views\DataTable\Templates;

defined('ABSPATH') || exit;

class NavigationTemplate {

    /**
     * Render navigation container
     *
     * Acts as orchestrator that calls sub-templates:
     * - StatsBoxTemplate::render() if has_stats = true
     * - FiltersTemplate::render() always
     *
     * Container always renders, content inside is conditional based on:
     * - has_stats: Show statistics section (via StatsBoxTemplate)
     * - Filters: Always shown (via FiltersTemplate)
     *
     * @param array $config Configuration array
     * @return void
     */
    public static function render($config) {
        ?>
        <?php
        /**
         * Action: Before navigation content
         *
         * @param array $config Dashboard configuration
         * @param string $entity Entity name
         */
        do_action('wpapp_navigation_before_content', $config, $config['entity']);
        ?>

        <!-- FIRST: Statistics Section (matches wp-customer layout) -->
        <?php if (!empty($config['has_stats'])): ?>
            <?php
            /**
             * Action: Before stats section
             *
             * @param array $config Dashboard configuration
             * @param string $entity Entity name
             */
            do_action('wpapp_dashboard_before_stats', $config, $config['entity']);
            ?>

            <?php StatsBoxTemplate::render($config['entity']); ?>

            <?php
            /**
             * Action: After statistics section
             *
             * @param array $config Dashboard configuration
             * @param string $entity Entity name
             */
            do_action('wpapp_dashboard_after_stats', $config, $config['entity']);
            ?>
        <?php endif; ?>

        <!-- SECOND: Filters Section (matches wp-customer layout) -->
        <?php
        /**
         * Filters are now rendered via FiltersTemplate (v1.0.0 - 2025-10-26)
         *
         * New approach (RECOMMENDED):
         * - Plugins register filters via 'wpapp_datatable_filters' filter
         * - Consistent with StatsBoxTemplate pattern
         * - Centralized rendering with standardized HTML structure
         *
         * Old approach (STILL SUPPORTED):
         * - Plugins hook to 'wpapp_dashboard_filters' action
         * - For backward compatibility
         * - Will be deprecated in future version
         */
        FiltersTemplate::render($config['entity'], $config);
        ?>

        <?php
        /**
         * Action: After navigation content
         *
         * @param array $config Dashboard configuration
         * @param string $entity Entity name
         */
        do_action('wpapp_navigation_after_content', $config, $config['entity']);
        ?>
        <?php
    }
}
