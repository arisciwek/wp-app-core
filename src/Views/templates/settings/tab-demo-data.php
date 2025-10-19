<?php
/**
 * Demo Data & Development Settings Tab Template
 *
 * @package     WP_App_Core
 * @subpackage  Views/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/templates/settings/tab-demo-data.php
 *
 * Description: Template untuk development settings dan demo data management
 *              - Create/Delete platform roles
 *              - Development mode toggle
 *              - Clear data on deactivation
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial creation
 * - Platform role management
 * - Development settings
 */

if (!defined('ABSPATH')) {
    die;
}

// Verify nonce and capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Load Role Manager
require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-role-manager.php';

// Check current status
$platform_roles = WP_App_Core_Role_Manager::getRoleSlugs();
$roles_exist = false;
$existing_role_count = 0;
foreach ($platform_roles as $role_slug) {
    if (WP_App_Core_Role_Manager::roleExists($role_slug)) {
        $roles_exist = true;
        $existing_role_count++;
    }
}
?>

<div class="wrap">
    <div id="demo-data-messages"></div>

    <!-- Platform Roles Management Section -->
    <div class="demo-data-section">
        <h3><?php _e('Platform Roles Management', 'wp-app-core'); ?></h3>
        <p class="description">
            <?php _e('Manage platform roles for staff management. These roles are used in the permission matrix.', 'wp-app-core'); ?>
        </p>

        <div class="demo-data-grid">
            <!-- Create Platform Roles -->
            <div class="demo-data-card">
                <h4><?php _e('Create Platform Roles', 'wp-app-core'); ?></h4>
                <p><?php _e('Create all 7 platform roles with default capabilities.', 'wp-app-core'); ?></p>
                <p class="role-status">
                    <?php if ($roles_exist): ?>
                        <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                        <?php printf(__('Status: %d of 7 roles exist', 'wp-app-core'), $existing_role_count); ?>
                    <?php else: ?>
                        <span class="dashicons dashicons-warning" style="color: #f0b849;"></span>
                        <?php _e('Status: No platform roles exist', 'wp-app-core'); ?>
                    <?php endif; ?>
                </p>
                <button type="button"
                        class="button button-primary platform-create-roles"
                        data-nonce="<?php echo wp_create_nonce('create_platform_roles'); ?>"
                        <?php echo $existing_role_count === 7 ? 'disabled' : ''; ?>>
                    <?php _e('Create Platform Roles', 'wp-app-core'); ?>
                </button>
                <p class="description">
                    <?php _e('Creates: Super Admin, Admin, Manager, Support, Finance, Analyst, Viewer', 'wp-app-core'); ?>
                </p>
            </div>

            <!-- Delete Platform Roles -->
            <div class="demo-data-card">
                <h4><?php _e('Delete Platform Roles', 'wp-app-core'); ?></h4>
                <p><?php _e('Remove all platform roles and their capabilities.', 'wp-app-core'); ?></p>
                <p class="role-status">
                    <?php if ($roles_exist): ?>
                        <span class="dashicons dashicons-info" style="color: #2271b1;"></span>
                        <?php printf(__('%d roles can be deleted', 'wp-app-core'), $existing_role_count); ?>
                    <?php else: ?>
                        <span class="dashicons dashicons-info" style="color: #646970;"></span>
                        <?php _e('No roles to delete', 'wp-app-core'); ?>
                    <?php endif; ?>
                </p>
                <button type="button"
                        class="button button-secondary platform-delete-roles"
                        data-nonce="<?php echo wp_create_nonce('delete_platform_roles'); ?>"
                        <?php echo !$roles_exist ? 'disabled' : ''; ?>>
                    <?php _e('Delete Platform Roles', 'wp-app-core'); ?>
                </button>
                <p class="description">
                    <?php _e('Warning: This will remove all platform roles and their capabilities.', 'wp-app-core'); ?>
                </p>
            </div>

            <!-- Reset Platform Capabilities -->
            <div class="demo-data-card">
                <h4><?php _e('Reset Platform Capabilities', 'wp-app-core'); ?></h4>
                <p><?php _e('Reset all platform capabilities to default values.', 'wp-app-core'); ?></p>
                <p class="role-status">
                    <?php if ($roles_exist): ?>
                        <span class="dashicons dashicons-admin-tools" style="color: #2271b1;"></span>
                        <?php _e('Ready to reset capabilities', 'wp-app-core'); ?>
                    <?php else: ?>
                        <span class="dashicons dashicons-warning" style="color: #646970;"></span>
                        <?php _e('Create roles first', 'wp-app-core'); ?>
                    <?php endif; ?>
                </p>
                <button type="button"
                        class="button button-secondary platform-reset-capabilities"
                        data-nonce="<?php echo wp_create_nonce('reset_platform_capabilities'); ?>"
                        <?php echo !$roles_exist ? 'disabled' : ''; ?>>
                    <?php _e('Reset Capabilities', 'wp-app-core'); ?>
                </button>
                <p class="description">
                    <?php _e('Resets capabilities to default values defined in Role Manager.', 'wp-app-core'); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Platform Staff Demo Data Section -->
    <div class="demo-data-section" style="margin-top: 30px;">
        <h3><?php _e('Platform Staff Demo Data', 'wp-app-core'); ?></h3>
        <p class="description">
            <?php _e('Generate demo platform staff users for testing. 20 users total across 7 roles (ID 230-249).', 'wp-app-core'); ?>
        </p>

        <div class="demo-data-grid">
            <!-- Generate Platform Staff -->
            <div class="demo-data-card">
                <h4><?php _e('Generate Platform Staff', 'wp-app-core'); ?></h4>
                <p><?php _e('Create 20 demo platform staff users with complete profiles.', 'wp-app-core'); ?></p>
                <p class="role-status">
                    <span class="dashicons dashicons-admin-users" style="color: #2271b1;"></span>
                    <?php _e('20 staff members: Super Admin (2), Admin (3), Manager (3), Support (4), Finance (3), Analyst (3), Viewer (2)', 'wp-app-core'); ?>
                </p>
                <button type="button"
                        class="button button-primary platform-generate-staff"
                        data-nonce="<?php echo wp_create_nonce('generate_platform_staff'); ?>">
                    <?php _e('Generate Platform Staff', 'wp-app-core'); ?>
                </button>
                <p class="description">
                    <?php _e('Default password: password123 | Employee IDs: STAFF-001 to STAFF-020', 'wp-app-core'); ?>
                </p>
            </div>

            <!-- Delete Platform Staff -->
            <div class="demo-data-card">
                <h4><?php _e('Delete Platform Staff', 'wp-app-core'); ?></h4>
                <p><?php _e('Remove all platform staff demo users and their data.', 'wp-app-core'); ?></p>
                <p class="role-status">
                    <span class="dashicons dashicons-trash" style="color: #d63638;"></span>
                    <?php _e('Removes users, staff records, and all associated data', 'wp-app-core'); ?>
                </p>
                <button type="button"
                        class="button button-secondary platform-delete-staff"
                        data-nonce="<?php echo wp_create_nonce('delete_platform_staff'); ?>">
                    <?php _e('Delete Platform Staff', 'wp-app-core'); ?>
                </button>
                <p class="description">
                    <?php _e('Warning: This will permanently delete all demo staff users (ID 230-249).', 'wp-app-core'); ?>
                </p>
            </div>

            <!-- Staff Statistics -->
            <div class="demo-data-card">
                <h4><?php _e('Platform Staff Statistics', 'wp-app-core'); ?></h4>
                <div id="platform-staff-stats">
                    <p><?php _e('Loading statistics...', 'wp-app-core'); ?></p>
                </div>
                <button type="button"
                        class="button button-secondary platform-refresh-stats"
                        data-nonce="<?php echo wp_create_nonce('platform_staff_stats'); ?>">
                    <?php _e('Refresh Statistics', 'wp-app-core'); ?>
                </button>
                <p class="description">
                    <?php _e('View current status of platform staff demo data', 'wp-app-core'); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Development Settings Section -->
    <div class="development-settings-section" style="margin-top: 30px;">
        <h3><?php _e('Development Settings', 'wp-app-core'); ?></h3>
        <form method="post" action="options.php" id="wp-app-core-demo-data-form">
            <?php
            settings_fields('wp_app_core_development_settings');
            $dev_settings = get_option('wp_app_core_development_settings', array(
                'enable_development' => 0,
                'clear_data_on_deactivate' => 0
            ));
            ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php _e('Development Mode', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="wp_app_core_development_settings[enable_development]"
                                   value="1"
                                   <?php checked($dev_settings['enable_development'], 1); ?>>
                            <?php _e('Enable development mode', 'wp-app-core'); ?>
                        </label>
                        <p class="description">
                            <?php _e('When enabled, this overrides WP_APP_CORE_DEVELOPMENT constant.', 'wp-app-core'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php _e('Data Cleanup', 'wp-app-core'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="wp_app_core_development_settings[clear_data_on_deactivate]"
                                   value="1"
                                   <?php checked($dev_settings['clear_data_on_deactivate'], 1); ?>>
                            <?php _e('Clear roles and settings on plugin deactivation', 'wp-app-core'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Warning: When enabled, all platform roles and settings will be permanently deleted when the plugin is deactivated.', 'wp-app-core'); ?>
                        </p>
                        <p class="description">
                            <strong><?php _e('Note:', 'wp-app-core'); ?></strong>
                            <?php _e('Both Development Mode and this option must be checked for data to be cleared on deactivation.', 'wp-app-core'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </form>

        <!-- Sticky Footer with Action Buttons -->
        <div class="settings-footer">
            <p class="submit">
                <?php submit_button(__('Save Development Settings', 'wp-app-core'), 'primary', 'submit', false, ['form' => 'wp-app-core-demo-data-form']); ?>
            </p>
        </div>
    </div>

    <!-- Information Section -->
    <div class="info-section" style="margin-top: 30px; background: #f0f6fc; padding: 20px; border-left: 4px solid #2271b1;">
        <h4><?php _e('About Platform Roles', 'wp-app-core'); ?></h4>
        <p><?php _e('Platform roles are used to manage staff permissions for platform operations. These roles include:', 'wp-app-core'); ?></p>
        <ul style="list-style: disc; margin-left: 20px;">
            <li><strong><?php _e('Platform Super Admin', 'wp-app-core'); ?></strong> - <?php _e('Full access to all platform features', 'wp-app-core'); ?></li>
            <li><strong><?php _e('Platform Admin', 'wp-app-core'); ?></strong> - <?php _e('Manage platform operations and tenants', 'wp-app-core'); ?></li>
            <li><strong><?php _e('Platform Manager', 'wp-app-core'); ?></strong> - <?php _e('Oversee operations and analytics', 'wp-app-core'); ?></li>
            <li><strong><?php _e('Platform Support', 'wp-app-core'); ?></strong> - <?php _e('Handle customer support', 'wp-app-core'); ?></li>
            <li><strong><?php _e('Platform Finance', 'wp-app-core'); ?></strong> - <?php _e('Manage financial operations', 'wp-app-core'); ?></li>
            <li><strong><?php _e('Platform Analyst', 'wp-app-core'); ?></strong> - <?php _e('Access analytics and reports', 'wp-app-core'); ?></li>
            <li><strong><?php _e('Platform Viewer', 'wp-app-core'); ?></strong> - <?php _e('View-only access', 'wp-app-core'); ?></li>
        </ul>
    </div>
</div>
