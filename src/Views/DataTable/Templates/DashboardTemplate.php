<?php
/**
 * Dashboard Template - Base
 *
 * Main dashboard container template for DataTable pages.
 * Provides structure and hook points for plugin customization.
 *
 * @package WPAppCore
 * @subpackage Views\DataTable\Templates
 * @since 1.0.0
 * @version 1.1.0
 * @author arisciwek
 *
 * Path: /wp-app-core/src/Views/DataTable/Templates/DashboardTemplate.php
 *
 * Changelog:
 * 1.1.0 - 2025-10-29 (TODO-3089)
 * - Removed NavigationTemplate wrapper (over-engineering)
 * - Direct calls to StatsBoxTemplate and FiltersTemplate
 * - Simpler, clearer code flow
 * - No functionality change, just simplified architecture
 *
 * 1.0.0 - Initial version
 * - Used NavigationTemplate as orchestrator
 *
 * Usage:
 * ```php
 * use WPAppCore\Views\DataTable\Templates\DashboardTemplate;
 *
 * DashboardTemplate::render([
 *     'entity' => 'customer',
 *     'title' => 'Customers',
 *     'ajax_action' => 'get_customer_details',
 *     'has_stats' => true,
 *     'has_tabs' => true,
 *     'nonce' => wp_create_nonce('customer_nonce')
 * ]);
 * ```
 */

namespace WPAppCore\Views\DataTable\Templates;

defined('ABSPATH') || exit;

class DashboardTemplate {

    /**
     * Render dashboard template
     *
     * Main method to render complete dashboard with all components
     *
     * @param array $config Configuration array
     * @return void
     */
    public static function render($config) {
        // Validate config
        $config = self::validate_config($config);

        // Note: Assets are enqueued by DataTableAssetsController
        // See: wp-app-core/src/Controllers/DataTable/DataTableAssetsController.php

        // Start rendering
        ?>
        <div class="wrap wpapp-dashboard-wrap">
        <div class="wrap wpapp-datatable-page" data-entity="<?php echo esc_attr($config['entity']); ?>">

            <!-- Page Header Container (Global Scope) -->
            <?php self::render_page_header($config); ?>

            <?php
            /**
             * Action: Before dashboard content
             *
             * @param array $config Dashboard configuration
             * @param string $entity Entity name
             */
            do_action('wpapp_dashboard_before_content', $config, $config['entity']);
            ?>

            <!-- Statistics Section (if enabled) -->
            <?php if (!empty($config['has_stats'])): ?>
                <?php StatsBoxTemplate::render($config['entity']); ?>
            <?php endif; ?>

            <!-- Filters Section -->
            <?php FiltersTemplate::render($config['entity'], $config); ?>

            <!-- Main Panel Layout -->
            <?php
            PanelLayoutTemplate::render($config);
            ?>

            <?php
            /**
             * Action: After dashboard content
             *
             * @param array $config Dashboard configuration
             * @param string $entity Entity name
             */
            do_action('wpapp_dashboard_after_content', $config, $config['entity']);
            ?>

        </div>
        </div>
        <?php
    }

    /**
     * Render page header with hook system
     *
     * All classes use wpapp- prefix (from wp-app-core)
     *
     * Simplified structure (TODO-1187):
     * - Removed outer wpapp-page-header wrapper
     * - Now consistent with wpapp-statistics-container, wpapp-filters-container
     *
     * @param array $config Configuration
     * @return void
     */
    private static function render_page_header($config) {
        ?>
        <!-- Page Header Container (Global Scope) -->
        <div class="wpapp-page-header-container">
            <!-- Header Left: Title & Subtitle -->
            <div class="wpapp-header-left">
                <?php
                /**
                 * Action: Page header left content
                 *
                 * Plugins should hook here to render title and subtitle
                 * Each plugin renders their own HTML with their own CSS classes
                 *
                 * IMPORTANT: Use plugin-specific CSS classes (e.g., agency-, customer-)
                 *
                 * @param array $config Dashboard configuration
                 * @param string $entity Entity name
                 *
                 * @example
                 * add_action('wpapp_page_header_left', function($config, $entity) {
                 *     if ($entity !== 'agency') return;
                 *     echo '<h1 class="agency-title">' . esc_html($config['title']) . '</h1>';
                 *     echo '<div class="agency-subtitle">Manage agencies</div>';
                 * }, 10, 2);
                 */
                do_action('wpapp_page_header_left', $config, $config['entity']);
                ?>

                <?php if (!did_action('wpapp_page_header_left')): ?>
                    <!-- Default title if no hook registered -->
                    <h1 class="wp-heading-inline"><?php echo esc_html($config['title']); ?></h1>
                <?php endif; ?>
            </div>

            <!-- Header Right: Action Buttons -->
            <div class="wpapp-header-right">
                <?php
                /**
                 * Action: Page header right content
                 *
                 * Plugins should hook here to render action buttons
                 * Each plugin renders their own HTML with their own CSS classes
                 *
                 * IMPORTANT: Use plugin-specific CSS classes (e.g., agency-, customer-)
                 *
                 * @param array $config Dashboard configuration
                 * @param string $entity Entity name
                 *
                 * @example
                 * add_action('wpapp_page_header_right', function($config, $entity) {
                 *     if ($entity !== 'agency') return;
                 *     echo '<a href="#" class="button button-primary agency-add-btn">Add New Agency</a>';
                 * }, 10, 2);
                 */
                do_action('wpapp_page_header_right', $config, $config['entity']);
                ?>
            </div>
        </div>

        <hr class="wp-header-end">
        <?php
    }

    /**
     * Validate configuration
     *
     * @param array $config Raw configuration
     * @return array Validated configuration with defaults
     */
    private static function validate_config($config) {
        $defaults = [
            'entity' => '',
            'title' => '',
            'ajax_action' => '',
            'has_stats' => false,
            'has_tabs' => false,
            'nonce' => '',
        ];

        $config = wp_parse_args($config, $defaults);

        // Validate required fields
        if (empty($config['entity'])) {
            wp_die(__('Dashboard entity is required', 'wp-app-core'));
        }

        if (empty($config['title'])) {
            $config['title'] = ucfirst($config['entity']);
        }

        return $config;
    }
}
