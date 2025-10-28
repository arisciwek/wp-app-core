<?php
/**
 * Tab View Template - Base
 *
 * Provides generic container for tab content with hook-based injection.
 * Similar pattern to Header Buttons (wpapp_page_header_right).
 *
 * Container:   wpapp-* (global scope, reusable)
 * Content:     plugin-* (local scope, via hook)
 *
 * @package WPAppCore
 * @subpackage Views\DataTable\Templates
 * @since 1.0.0
 * @author arisciwek
 *
 * Path: /wp-app-core/src/Views/DataTable/Templates/TabViewTemplate.php
 *
 * Example Usage:
 * ```php
 * // In tab template file (e.g., info.php, details.php)
 * use WPAppCore\Views\DataTable\Templates\TabViewTemplate;
 *
 * TabViewTemplate::render('agency', 'info', $agency_data);
 * ```
 *
 * Hook Usage (in plugin):
 * ```php
 * add_action('wpapp_tab_view_content', function($entity, $tab_id, $data) {
 *     if ($entity !== 'agency' || $tab_id !== 'info') return;
 *
 *     // Render content with LOCAL scope classes
 *     ?>
 *     <div class="agency-tab-section">
 *         <div class="agency-tab-grid">
 *             ...
 *         </div>
 *     </div>
 *     <?php
 * }, 10, 3);
 * ```
 *
 * Changelog:
 * 1.0.0 - 2025-10-27
 * - Initial creation
 * - Hook-based content injection
 * - Global container (wpapp-*) with local content (plugin-*)
 * - Inspired by wpapp_page_header_right pattern
 */

namespace WPAppCore\Views\DataTable\Templates;

defined('ABSPATH') || exit;

class TabViewTemplate {

    /**
     * Render tab view container with hook for content injection
     *
     * Container uses wpapp-* classes (global scope).
     * Content injected via hook uses plugin-specific classes (local scope).
     *
     * @param string $entity Entity identifier (agency, customer, company, etc)
     * @param string $tab_id Tab identifier (info, details, divisions, etc)
     * @param array  $data   Optional data passed to hook (from AJAX response)
     * @return void
     */
    public static function render($entity, $tab_id, $data = []) {
        error_log('=== TAB VIEW TEMPLATE RENDER ===');
        error_log('Entity: ' . $entity);
        error_log('Tab ID: ' . $tab_id);
        error_log('Data provided: ' . (empty($data) ? 'NO' : 'YES'));

        ?>
        <!-- Tab View Container (Global Scope - wp-app-core) -->
        <div class="wpapp-tab-view-container"
             data-entity="<?php echo esc_attr($entity); ?>"
             data-tab-id="<?php echo esc_attr($tab_id); ?>">

            <?php
            /**
             * Action: wpapp_tab_view_content
             *
             * Allows plugins to inject tab content with their own structure and styling.
             *
             * Plugins should:
             * - Check entity and tab_id match their context
             * - Use plugin-specific class prefixes (agency-*, customer-*, company-*)
             * - Render complete HTML structure within this container
             *
             * IMPORTANT: This is LOCAL SCOPE content.
             * Use plugin prefix (agency-*, customer-*, etc), NOT wpapp-*
             *
             * Container Responsibility (wp-app-core):
             * - Provide outer wrapper (wpapp-tab-view-container)
             * - Provide hook point for content injection
             *
             * Content Responsibility (plugins):
             * - Define HTML structure
             * - Define CSS styling with plugin prefix
             * - Handle data rendering
             *
             * @since 1.0.0
             *
             * @param string $entity Entity identifier
             * @param string $tab_id Tab identifier
             * @param array  $data   Optional data for rendering
             *
             * @example
             * // In wp-agency/src/Controllers/Agency/AgencyDashboardController.php
             * add_action('wpapp_tab_view_content', [$this, 'render_tab_content'], 10, 3);
             *
             * public function render_tab_content($entity, $tab_id, $data) {
             *     if ($entity !== 'agency') return;
             *
             *     if ($tab_id === 'info') {
             *         $this->render_info_content($data);
             *     }
             * }
             *
             * private function render_info_content($data) {
             *     $agency = $data['agency'] ?? null;
             *     ?>
             *     <div class="agency-tab-section">      <!-- LOCAL scope -->
             *         <h3>Informasi Umum</h3>
             *         <div class="agency-tab-grid">      <!-- LOCAL scope -->
             *             <div class="agency-tab-item">  <!-- LOCAL scope -->
             *                 <span class="agency-tab-label">Kode:</span>
             *                 <span class="agency-tab-value"><?php echo $agency->code; ?></span>
             *             </div>
             *         </div>
             *     </div>
             *     <?php
             * }
             */
            do_action('wpapp_tab_view_content', $entity, $tab_id, $data);

            error_log('Hook wpapp_tab_view_content fired');
            ?>

        </div>
        <!-- End Tab View Container -->
        <?php

        error_log('=== END TAB VIEW TEMPLATE ===');
    }

    /**
     * Check if content was rendered for entity/tab
     *
     * Helper method to detect if any plugin hooked into the content.
     * Useful for debugging or showing "no content" message.
     *
     * @param string $entity Entity identifier
     * @param string $tab_id Tab identifier
     * @return bool True if hook has callbacks registered
     */
    public static function has_content($entity, $tab_id) {
        global $wp_filter;

        if (!isset($wp_filter['wpapp_tab_view_content'])) {
            return false;
        }

        return $wp_filter['wpapp_tab_view_content']->has_filters();
    }

    /**
     * Render empty state when no content available
     *
     * @param string $entity Entity identifier
     * @param string $tab_id Tab identifier
     * @return void
     */
    public static function render_empty_state($entity, $tab_id) {
        ?>
        <div class="wpapp-tab-view-container wpapp-tab-view-empty">
            <div class="wpapp-empty-state">
                <span class="dashicons dashicons-info-outline"></span>
                <p>
                    <?php
                    printf(
                        esc_html__('No content available for %s tab.', 'wp-app-core'),
                        '<strong>' . esc_html($tab_id) . '</strong>'
                    );
                    ?>
                </p>
                <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                    <p class="wpapp-debug-info">
                        <small>
                            Debug: No callbacks registered for hook
                            <code>wpapp_tab_view_content</code>
                            with entity=<code><?php echo esc_html($entity); ?></code>
                            and tab_id=<code><?php echo esc_html($tab_id); ?></code>
                        </small>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
