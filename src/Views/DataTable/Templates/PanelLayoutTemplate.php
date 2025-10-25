<?php
/**
 * Panel Layout Template - Base
 *
 * Provides left/right panel system with smooth transitions (Perfex CRM pattern).
 * Left panel typically contains DataTable listing.
 * Right panel typically contains detail view with optional tabs.
 *
 * @package WPAppCore
 * @subpackage Views\DataTable\Templates
 * @since 1.0.0
 * @author arisciwek
 */

namespace WPAppCore\Views\DataTable\Templates;

defined('ABSPATH') || exit;

class PanelLayoutTemplate {

    /**
     * Render panel layout
     *
     * @param array $config Configuration array
     * @return void
     */
    public static function render($config) {
        error_log('=== PANEL LAYOUT TEMPLATE RENDER ===');
        error_log('Config: ' . print_r($config, true));
        error_log('Entity: ' . ($config['entity'] ?? 'NONE'));
        error_log('AJAX Action: ' . ($config['ajax_action'] ?? 'NONE'));
        error_log('Has Tabs: ' . ($config['has_tabs'] ? 'YES' : 'NO'));
        ?>
        <div class="wpapp-datatable-layout"
             data-entity="<?php echo esc_attr($config['entity']); ?>"
             data-ajax-action="<?php echo esc_attr($config['ajax_action']); ?>"
             data-has-tabs="<?php echo $config['has_tabs'] ? 'true' : 'false'; ?>">

            <!-- Left Panel -->
            <div class="wpapp-left-panel">
                <?php
                error_log('Rendering left panel...');
                self::render_left_panel($config);
                error_log('Left panel rendered');
                ?>
            </div>

            <!-- Right Panel -->
            <div class="wpapp-right-panel hidden">
                <?php
                error_log('Rendering right panel...');
                self::render_right_panel($config);
                error_log('Right panel rendered');
                ?>
            </div>

        </div>
        <?php
        error_log('=== END PANEL LAYOUT TEMPLATE ===');
    }

    /**
     * Render left panel content
     *
     * @param array $config Configuration
     * @return void
     */
    private static function render_left_panel($config) {
        ?>
        <?php
        /**
         * Action: Left panel header
         *
         * Typically contains title and action buttons
         *
         * @param array $config Panel configuration
         */
        do_action('wpapp_left_panel_header', $config);
        ?>

        <div class="wpapp-panel-content">
            <?php
            /**
             * Action: Left panel content
             *
             * Main content area - typically DataTable HTML
             * Plugins should hook here to render their DataTable
             *
             * @param array $config Panel configuration
             *
             * @example
             * add_action('wpapp_left_panel_content', function($config) {
             *     if ($config['entity'] !== 'customer') return;
             *     include WP_CUSTOMER_PATH . 'src/Views/customers/datatable.php';
             * });
             */
            do_action('wpapp_left_panel_content', $config);
            ?>
        </div>

        <?php
        /**
         * Action: Left panel footer
         *
         * Footer area for additional controls
         *
         * @param array $config Panel configuration
         */
        do_action('wpapp_left_panel_footer', $config);
        ?>
        <?php
    }

    /**
     * Render right panel content
     *
     * @param array $config Configuration
     * @return void
     */
    private static function render_right_panel($config) {
        ?>
        <!-- Panel Header -->
        <div class="wpapp-panel-header">
            <h2 class="wpapp-panel-title">
                <span class="wpapp-entity-name"></span>
            </h2>
            <button type="button" class="wpapp-panel-close" aria-label="<?php esc_attr_e('Close', 'wp-app-core'); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>

            <?php
            /**
             * Action: Right panel header
             *
             * Additional header content (after title and close button)
             *
             * @param array $config Panel configuration
             */
            do_action('wpapp_right_panel_header', $config);
            ?>
        </div>

        <!-- Panel Content -->
        <div class="wpapp-panel-content">
            <?php if ($config['has_tabs']): ?>
                <!-- Tab System -->
                <?php
                error_log('Has tabs enabled, rendering TabSystemTemplate for entity: ' . $config['entity']);
                TabSystemTemplate::render($config['entity']);
                error_log('TabSystemTemplate rendered');
                ?>
            <?php else: ?>
                <!-- Simple Content (No Tabs) -->
                <?php
                error_log('No tabs, using simple content hook');
                /**
                 * Action: Right panel content (no tabs)
                 *
                 * Content area when tabs are disabled
                 *
                 * @param array $config Panel configuration
                 */
                do_action('wpapp_right_panel_content', $config);
                ?>
            <?php endif; ?>
        </div>

        <!-- Panel Footer -->
        <?php
        /**
         * Action: Right panel footer
         *
         * Footer area for action buttons
         *
         * @param array $config Panel configuration
         */
        do_action('wpapp_right_panel_footer', $config);
        ?>
        <?php
    }
}
