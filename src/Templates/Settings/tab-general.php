<?php
/**
 * General Settings Tab Template (Shared)
 *
 * @package     WP_App_Core
 * @subpackage  Templates/Settings
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Templates/Settings/tab-general.php
 *
 * Description: Shared general settings tab template.
 *              This is a base template that can be overridden by plugins.
 *              Use hook '{prefix}_settings_tab_path' to provide custom template.
 *
 * Changelog:
 * 1.1.0 - 2025-01-09 (TODO-1203)
 * - Created as shared template
 * - Placeholder for plugin-specific implementation
 */

if (!defined('ABSPATH')) {
    die;
}

?>

<div class="wpapp-settings-tab-general">
    <div class="notice notice-info">
        <p>
            <?php _e('This is the base general settings template from wp-app-core.', 'wp-app-core'); ?>
            <br>
            <?php _e('Override this template by providing custom path via hook or implement tab content via action hook.', 'wp-app-core'); ?>
        </p>
    </div>

    <h3><?php _e('General Settings', 'wp-app-core'); ?></h3>
    <p><?php _e('Configure general settings for your plugin.', 'wp-app-core'); ?></p>

    <form method="post" action="options.php">
        <?php
        // Plugins should implement their own settings fields here
        // via settings_fields() and do_settings_sections()
        ?>

        <p class="submit">
            <button type="submit" class="button button-primary">
                <?php _e('Save Changes', 'wp-app-core'); ?>
            </button>
        </p>
    </form>
</div>
