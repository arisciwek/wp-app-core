<?php
/**
 * Demo Data Settings Tab Template (Shared)
 *
 * @package     WP_App_Core
 * @subpackage  Templates/Settings
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Templates/Settings/tab-demo-data.php
 *
 * Description: Shared demo data settings tab template.
 *              Base template for demo data management.
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

<div class="wpapp-settings-tab-demo-data">
    <div class="notice notice-warning">
        <p>
            <strong><?php _e('Warning:', 'wp-app-core'); ?></strong>
            <?php _e('Demo data operations are for development purposes only.', 'wp-app-core'); ?>
        </p>
    </div>

    <div class="notice notice-info">
        <p>
            <?php _e('This is the base demo data template from wp-app-core.', 'wp-app-core'); ?>
            <br>
            <?php _e('Override this template to implement plugin-specific demo data generation.', 'wp-app-core'); ?>
        </p>
    </div>

    <h3><?php _e('Demo Data Management', 'wp-app-core'); ?></h3>
    <p><?php _e('Generate or remove demo data for testing purposes.', 'wp-app-core'); ?></p>

    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('Generate Demo Data', 'wp-app-core'); ?></th>
            <td>
                <button type="button" class="button" disabled>
                    <?php _e('Generate', 'wp-app-core'); ?>
                </button>
                <p class="description">
                    <?php _e('Plugin-specific implementation required.', 'wp-app-core'); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Remove Demo Data', 'wp-app-core'); ?></th>
            <td>
                <button type="button" class="button button-secondary" disabled>
                    <?php _e('Remove All Demo Data', 'wp-app-core'); ?>
                </button>
                <p class="description">
                    <?php _e('Plugin-specific implementation required.', 'wp-app-core'); ?>
                </p>
            </td>
        </tr>
    </table>
</div>
