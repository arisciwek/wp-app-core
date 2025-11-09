<?php
/**
 * Permissions Settings Tab Template (Shared)
 *
 * @package     WP_App_Core
 * @subpackage  Templates/Settings
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Templates/Settings/tab-permissions.php
 *
 * Description: Shared permissions settings tab template.
 *              Base template for managing role capabilities.
 *              Override via hook for plugin-specific implementation.
 *
 * Changelog:
 * 1.1.0 - 2025-01-09 (TODO-1203)
 * - Created as shared template
 */

if (!defined('ABSPATH')) {
    die;
}

?>

<div class="wpapp-settings-tab-permissions">
    <div class="notice notice-info">
        <p>
            <?php _e('This is the base permissions settings template from wp-app-core.', 'wp-app-core'); ?>
            <br>
            <?php _e('Override this template to implement plugin-specific permission management.', 'wp-app-core'); ?>
        </p>
    </div>

    <h3><?php _e('Permission Settings', 'wp-app-core'); ?></h3>
    <p><?php _e('Configure role capabilities for your plugin.', 'wp-app-core'); ?></p>

    <form method="post" action="options.php">
        <?php
        // Plugins should implement their own permission management here
        ?>

        <p class="submit">
            <button type="submit" class="button button-primary">
                <?php _e('Save Permissions', 'wp-app-core'); ?>
            </button>
        </p>
    </form>
</div>
