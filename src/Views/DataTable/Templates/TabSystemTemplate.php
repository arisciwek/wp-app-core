<?php
/**
 * Tab System Template - Base
 *
 * Provides WordPress-style tab navigation for detail panels.
 * Supports two rendering patterns:
 *
 * 1. Direct Inclusion Pattern: 'template' key provided, file included directly
 * 2. Hook-Based AJAX Pattern: No 'template' key, content loaded via AJAX + hooks
 *
 * @package WPAppCore
 * @subpackage Views\DataTable\Templates
 * @since 1.0.0
 * @version 1.1.0
 * @author arisciwek
 *
 * Path: /wp-app-core/src/Views/DataTable/Templates/TabSystemTemplate.php
 *
 * Changelog:
 * 1.1.0 - 2025-10-29 (TODO-3089)
 * - Support hook-based pattern (no 'template' key required)
 * - 'template' key is now OPTIONAL (was required)
 * - No error if 'template' key missing (entity uses AJAX pattern)
 * - Added wpapp_tab_empty_container hook for empty containers
 *
 * 1.0.0 - Initial version
 * - Direct inclusion pattern only
 * - 'template' key required
 *
 * Tab Structure:
 *
 * Pattern 1 - Direct Inclusion (Legacy):
 * ```php
 * [
 *     'tab-id' => [
 *         'title' => 'Tab Title',
 *         'template' => '/path/to/template.php',  // ← File included directly
 *         'priority' => 10
 *     ]
 * ]
 * ```
 *
 * Pattern 2 - Hook-Based AJAX (Modern):
 * ```php
 * [
 *     'tab-id' => [
 *         'title' => 'Tab Title',
 *         'priority' => 10
 *         // No 'template' key - content loaded via AJAX + hooks
 *     ]
 * ]
 * ```
 */

namespace WPAppCore\Views\DataTable\Templates;

defined('ABSPATH') || exit;

class TabSystemTemplate {

    /**
     * Render tab system
     *
     * @param string $entity Entity name
     * @return void
     */
    public static function render($entity) {
        error_log('=== TAB SYSTEM TEMPLATE RENDER START ===');
        error_log('Entity: ' . $entity);

        // Get tabs from filter
        $tabs = self::get_tabs($entity);

        error_log('Tabs received from filter: ' . print_r(array_keys($tabs), true));
        error_log('Total tabs: ' . count($tabs));

        if (empty($tabs)) {
            error_log('WARNING: No tabs registered for entity: ' . $entity);
            // No tabs registered, show empty state
            do_action('wpapp_no_tabs_content', $entity);
            return;
        }

        // Sort tabs by priority
        uasort($tabs, function($a, $b) {
            $priority_a = isset($a['priority']) ? $a['priority'] : 10;
            $priority_b = isset($b['priority']) ? $b['priority'] : 10;
            return $priority_a - $priority_b;
        });

        error_log('Tabs after sorting: ' . print_r(array_keys($tabs), true));

        // Render tab navigation
        error_log('Rendering tab navigation...');
        self::render_tab_navigation($tabs, $entity);

        // Render tab content
        error_log('Rendering tab content containers...');
        self::render_tab_content($tabs, $entity);

        error_log('=== TAB SYSTEM TEMPLATE RENDER END ===');
    }

    /**
     * Get tabs for entity via filter
     *
     * @param string $entity Entity name
     * @return array Tabs array
     */
    private static function get_tabs($entity) {
        /**
         * Filter: Register tabs for entity
         *
         * Plugins can register tabs for their entities
         *
         * @param array $tabs Tabs array
         * @param string $entity Entity name
         *
         * @return array Modified tabs array
         *
         * @example
         * add_filter('wpapp_datatable_tabs', function($tabs, $entity) {
         *     if ($entity !== 'agency') return $tabs;
         *
         *     return [
         *         'details' => [
         *             'title' => 'Details',
         *             'template' => WP_AGENCY_PATH . 'src/Views/agency/tabs/tab-details.php',
         *             'priority' => 10
         *         ],
         *         'divisions' => [
         *             'title' => 'Divisions',
         *             'template' => WP_AGENCY_PATH . 'src/Views/agency/tabs/tab-divisions.php',
         *             'priority' => 20
         *         ]
         *     ];
         * }, 10, 2);
         */
        $tabs = apply_filters('wpapp_datatable_tabs', [], $entity);

        return $tabs;
    }

    /**
     * Render tab navigation
     *
     * All classes use wpapp- prefix (from wp-app-core)
     *
     * @param array $tabs Tabs array
     * @param string $entity Entity name
     * @return void
     */
    private static function render_tab_navigation($tabs, $entity) {
        ?>
        <div class="nav-tab-wrapper wpapp-tab-wrapper">
            <?php
            $is_first = true;
            foreach ($tabs as $tab_id => $tab):
                $active_class = $is_first ? 'nav-tab-active' : '';
                $title = isset($tab['title']) ? $tab['title'] : ucfirst($tab_id);
                ?>
                <a href="#"
                   class="nav-tab <?php echo esc_attr($active_class); ?>"
                   data-tab="<?php echo esc_attr($tab_id); ?>"
                   data-entity="<?php echo esc_attr($entity); ?>">
                    <?php echo esc_html($title); ?>
                </a>
                <?php
                $is_first = false;
            endforeach;
            ?>
        </div>
        <?php
    }

    /**
     * Render tab content containers
     *
     * All classes use wpapp- prefix (from wp-app-core)
     *
     * @param array $tabs Tabs array
     * @param string $entity Entity name
     * @return void
     */
    private static function render_tab_content($tabs, $entity) {
        error_log('=== RENDER TAB CONTENT CONTAINERS ===');
        $is_first = true;
        $container_count = 0;

        foreach ($tabs as $tab_id => $tab):
            $active_class = $is_first ? 'active' : '';
            $container_count++;

            error_log("Creating container #{$container_count}: #{$tab_id} (active: " . ($is_first ? 'yes' : 'no') . ")");
            ?>
            <div id="<?php echo esc_attr($tab_id); ?>"
                 class="wpapp-tab-content <?php echo esc_attr($active_class); ?>"
                 data-entity="<?php echo esc_attr($entity); ?>"
                 data-tab-id="<?php echo esc_attr($tab_id); ?>">

                <?php
                error_log("Container #{$tab_id} HTML created");

                // Get template path
                $template_path = isset($tab['template']) ? $tab['template'] : '';

                /**
                 * Filter: Override tab template path
                 *
                 * Allows plugins to override template paths
                 *
                 * @param string $template_path Template file path
                 * @param string $tab_id Tab identifier
                 * @param string $entity Entity name
                 *
                 * @return string Modified template path
                 */
                $template_path = apply_filters(
                    'wpapp_datatable_tab_template',
                    $template_path,
                    $tab_id,
                    $entity
                );

                error_log("Template path for #{$tab_id}: " . ($template_path ?: 'NONE'));
                error_log("Template exists: " . (file_exists($template_path) ? 'YES' : 'NO'));

                // Include template if exists (direct inclusion pattern)
                if (!empty($template_path) && file_exists($template_path)) {
                    error_log("Including template for #{$tab_id}");

                    /**
                     * Action: Before tab template
                     *
                     * @param string $tab_id Tab identifier
                     * @param string $entity Entity name
                     */
                    do_action('wpapp_before_tab_template', $tab_id, $entity);

                    include $template_path;

                    /**
                     * Action: After tab template
                     *
                     * @param string $tab_id Tab identifier
                     * @param string $entity Entity name
                     */
                    do_action('wpapp_after_tab_template', $tab_id, $entity);

                    error_log("Template included for #{$tab_id}");
                } elseif (!empty($template_path)) {
                    // Template path provided but file not found - ERROR
                    error_log("ERROR: Template file not found for #{$tab_id}: {$template_path}");
                    ?>
                    <div class="notice notice-error">
                        <p>
                            <?php
                            printf(
                                esc_html__('Tab template not found: %s', 'wp-app-core'),
                                '<code>' . esc_html($template_path) . '</code>'
                            );
                            ?>
                        </p>
                    </div>
                    <?php
                } else {
                    // No template path - Entity uses hook-based pattern (content loaded via AJAX)
                    error_log("No template path for #{$tab_id} - Entity uses hook-based pattern (AJAX content)");

                    /**
                     * Hook for entities using hook-based pattern
                     * Content will be loaded via AJAX and injected dynamically
                     */
                    do_action('wpapp_tab_empty_container', $tab_id, $entity);
                }
                ?>

            </div>
            <?php
            error_log("Container #{$tab_id} closed");
            $is_first = false;
        endforeach;

        error_log("Total tab containers created: {$container_count}");
        error_log('=== END RENDER TAB CONTENT CONTAINERS ===');
    }
}
