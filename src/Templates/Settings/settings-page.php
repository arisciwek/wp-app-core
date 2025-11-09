<?php
/**
 * Settings Page Template (Shared)
 *
 * @package     WP_App_Core
 * @subpackage  Templates/Settings
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Templates/Settings/settings-page.php
 *
 * Description: Shared settings page template with tab navigation.
 *              Used by all plugins via AbstractSettingsController.
 *              Supports plugin-specific hooks for extensibility.
 *
 * Variables available in this template:
 * - $tabs (array) - Tab slug => Tab label
 * - $current_tab (string) - Current active tab slug
 * - $page_slug (string) - Settings page slug
 * - $plugin_prefix (string) - Plugin prefix for hooks (e.g., 'wpc', 'wpa')
 * - $controller (AbstractSettingsController) - Controller instance
 *
 * Changelog:
 * 1.1.0 - 2025-01-09 (TODO-1203)
 * - Updated for AbstractSettingsController
 * - Added plugin-specific hooks
 * - Removed hardcoded tabs (now from controller)
 * 1.0.0 - 2025-10-19
 * - Initial creation
 */

if (!defined('ABSPATH')) {
    die;
}

?>

<div class="wrap wpapp-settings-page">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors(); ?>

    <nav class="nav-tab-wrapper wp-clearfix" style="margin-bottom: 20px;">
        <?php foreach ($tabs as $tab_key => $tab_label): ?>
            <?php $active = $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>
            <a href="<?php echo esc_url(add_query_arg('tab', $tab_key)); ?>"
               class="nav-tab <?php echo esc_attr($active); ?>">
                <?php echo esc_html($tab_label); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="tab-content wpapp-tab-content">
        <?php
        /**
         * Hook: Before tab content
         *
         * Plugin-specific hook that fires before tab content is rendered.
         *
         * @param string $current_tab Current tab slug
         *
         * Examples:
         * - do_action('wpc_settings_before_tab_content', 'general')
         * - do_action('wpa_settings_before_tab_content', 'commission')
         * - do_action('wpapp_settings_before_tab_content', 'permissions')
         */
        do_action($plugin_prefix . '_settings_before_tab_content', $current_tab);

        // Load tab content through controller
        if (isset($controller)) {
            $controller->loadTabView($current_tab);
        }

        /**
         * Hook: After tab content
         *
         * Plugin-specific hook that fires after tab content is rendered.
         *
         * @param string $current_tab Current tab slug
         */
        do_action($plugin_prefix . '_settings_after_tab_content', $current_tab);
        ?>
    </div>
</div>
