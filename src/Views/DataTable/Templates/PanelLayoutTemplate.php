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
 *
 * Path: /wp-app-core/src/Views/DataTable/Templates/PanelLayoutTemplate.php
 */

namespace WPAppCore\Views\DataTable\Templates;

defined('ABSPATH') || exit;

class PanelLayoutTemplate {

    /**
     * Render panel layout
     *
     * All classes use wpapp- prefix (from wp-app-core)
     *
     * @param array $config Configuration array
     * @return void
     */
    public static function render($config) {
        $entity = $config['entity'];

        error_log('=== PANEL LAYOUT TEMPLATE RENDER ===');
        error_log('Config: ' . print_r($config, true));
        error_log('Entity: ' . ($config['entity'] ?? 'NONE'));
        error_log('AJAX Action: ' . ($config['ajax_action'] ?? 'NONE'));
        error_log('Has Tabs: ' . ($config['has_tabs'] ? 'YES' : 'NO'));
        ?>
        <!-- DataTable Container (TODO-1187) -->
        <div class="wpapp-datatable-container">
            <!-- DataTable Layout Container -->
            <div class="wpapp-datatable-layout"
                 data-entity="<?php echo esc_attr($entity); ?>"
                 data-ajax-action="<?php echo esc_attr($config['ajax_action']); ?>"
                 data-has-tabs="<?php echo $config['has_tabs'] ? 'true' : 'false'; ?>">

            <!-- Sliding Panel Row Container -->
            <div class="wpapp-row" id="wpapp-<?php echo esc_attr($entity); ?>-container">

                <!-- Left Panel: DataTable (45% when right panel open) -->
                <div class="wpapp-col-md-12 wpapp-left-panel" id="wpapp-<?php echo esc_attr($entity); ?>-table-container">
                    <?php
                    error_log('Rendering left panel...');
                    self::render_left_panel($config);
                    error_log('Left panel rendered');
                    ?>
                </div>
                <!-- End Left Panel -->

                <!-- Right Panel: Detail (sliding panel, 55% width) -->
                <div class="wpapp-col-md-5 wpapp-right-panel wpapp-detail-panel hidden"
                     id="wpapp-<?php echo esc_attr($entity); ?>-detail-panel">
                    <div id="wpapp-<?php echo esc_attr($entity); ?>-detail-content">
                        <?php
                        error_log('Rendering right panel...');
                        self::render_right_panel($config);
                        error_log('Right panel rendered');
                        ?>
                    </div>
                </div>
                <!-- End Right Panel -->

            </div>
            <!-- End Row Container -->

            </div>
            <!-- End DataTable Layout -->
        </div>
        <!-- End DataTable Container -->
        <?php
        error_log('=== END PANEL LAYOUT TEMPLATE ===');
    }

    /**
     * Render left panel content
     *
     * NO wrapper - plugins provide their own wrapper
     * (e.g., agency-datatable-wrapper, companies-list-container)
     *
     * @param array $config Configuration
     * @return void
     */
    private static function render_left_panel($config) {
        /**
         * Action: Left panel content
         *
         * Main content area - typically DataTable HTML
         * Plugins should hook here to render their DataTable
         *
         * IMPORTANT: Plugins MUST provide their own wrapper with entity-specific classes
         * Example: <div class="agency-datatable-wrapper">...</div>
         *
         * @param array $config Panel configuration
         *
         * @example
         * add_action('wpapp_left_panel_content', function($config) {
         *     if ($config['entity'] !== 'agency') return;
         *     // Include template with agency-specific wrapper
         *     include WP_AGENCY_PATH . 'src/Views/DataTable/Templates/datatable.php';
         * });
         */
        do_action('wpapp_left_panel_content', $config);
    }

    /**
     * Render right panel content
     *
     * Uses wpapp- prefix (from wp-app-core)
     *
     * @param array $config Configuration
     * @return void
     */
    private static function render_right_panel($config) {
        $entity = $config['entity'];
        ?>
        <!-- Panel Header -->
        <div class="wpapp-panel-header">
            <h2 class="wpapp-panel-title">
                <span class="wpapp-entity-name"></span>
            </h2>
            <button type="button" class="wpapp-panel-close" aria-label="<?php esc_attr_e('Close', 'wp-app-core'); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>

        <!-- Panel Content -->
        <div class="wpapp-panel-content">
            <!-- Loading Placeholder -->
            <div class="wpapp-loading-placeholder">
                <span class="spinner is-active"></span>
                <p><?php echo esc_html(sprintf(__('Loading %s details...', 'wp-app-core'), $entity)); ?></p>
            </div>

            <?php if ($config['has_tabs']): ?>
                <!-- Tab System -->
                <?php
                error_log('Has tabs enabled, rendering TabSystemTemplate for entity: ' . $entity);
                TabSystemTemplate::render($entity);
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
