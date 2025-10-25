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
 * @author arisciwek
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
        <div class="wrap wpapp-dashboard-wrap" data-entity="<?php echo esc_attr($config['entity']); ?>">

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

            <!-- Navigation Container (Delegated to NavigationTemplate) -->
            <?php NavigationTemplate::render($config); ?>

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
        <?php
    }

    /**
     * Render page header with hook system
     *
     * @param array $config Configuration
     * @return void
     */
    private static function render_page_header($config) {
        ?>
        <div class="wpapp-page-header">
            <div class="wpapp-page-header-container">
                <!-- Header Left: Title & Subtitle (Scope Local) -->
                <div class="wpapp-header-left">
                    <?php
                    /**
                     * Action: Page header left content
                     *
                     * Plugins should hook here to render title and subtitle
                     * Each plugin renders their own HTML with their own classes
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

                <!-- Header Center: Cards, Stats, etc (Scope Local) -->
                <div class="wpapp-header-center">
                    <?php
                    /**
                     * Action: Page header center content
                     *
                     * Plugins should hook here to render cards, badges, stats, etc
                     * Each plugin renders their own HTML + CSS + JS
                     *
                     * @param array $config Dashboard configuration
                     * @param string $entity Entity name
                     *
                     * @example
                     * add_action('wpapp_page_header_center', function($config, $entity) {
                     *     if ($entity !== 'agency') return;
                     *     ?>
                     *     <div class="agency-header-cards">
                     *         <div class="agency-card agency-card-blue">
                     *             <span class="agency-card-value">10</span>
                     *             <span class="agency-card-label">Total</span>
                     *         </div>
                     *     </div>
                     *     <?php
                     * }, 10, 2);
                     */
                    do_action('wpapp_page_header_center', $config, $config['entity']);
                    ?>
                </div>

                <!-- Header Right: Action Buttons (Scope Local) -->
                <div class="wpapp-header-right">
                    <?php
                    /**
                     * Action: Page header right content
                     *
                     * Plugins should hook here to render action buttons
                     * Each plugin renders their own HTML with their own classes
                     *
                     * @param array $config Dashboard configuration
                     * @param string $entity Entity name
                     *
                     * @example
                     * add_action('wpapp_page_header_right', function($config, $entity) {
                     *     if ($entity !== 'agency') return;
                     *     echo '<a href="#" class="button button-primary">Add New Agency</a>';
                     * }, 10, 2);
                     */
                    do_action('wpapp_page_header_right', $config, $config['entity']);
                    ?>
                </div>
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
