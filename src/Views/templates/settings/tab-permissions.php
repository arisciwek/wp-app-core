<?php
/**
 * Platform Permission Management Tab Template
 *
 * @package     WP_App_Core
 * @subpackage  Views/Templates/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/templates/settings/tab-permissions.php
 *
 * Description: Template untuk mengelola platform permissions
 *              Menampilkan nested tab untuk 7 permission groups
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial creation
 * - Nested tab navigation for 7 groups
 * - Permission matrix table
 * - Reset to default button
 * - No inline CSS/JS - uses separate files
 */

if (!defined('ABSPATH')) {
    die;
}

// Data passed from controller via prepareViewData():
// - $capability_groups (capability groups with their caps)
// - $role_matrix (role capabilities matrix)
// - $capability_descriptions (capability descriptions for tooltips)
// - $permission_labels (capability labels for column headers)

// Load RoleManager
require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-role-manager.php';

// Check if platform roles exist
$platform_roles = WP_App_Core_Role_Manager::getRoleSlugs();
$existing_platform_roles = [];
foreach ($platform_roles as $role_slug) {
    if (WP_App_Core_Role_Manager::roleExists($role_slug)) {
        $existing_platform_roles[] = $role_slug;
    }
}
$platform_roles_exist = !empty($existing_platform_roles);

// Handle role creation if requested
if (isset($_POST['create_platform_roles']) && check_admin_referer('wp_app_core_create_roles')) {
    WP_App_Core_Role_Manager::createRoles();

    // Add capabilities after creating roles
    $temp_permission_model = new \WPAppCore\Models\Settings\PlatformPermissionModel();
    $temp_permission_model->addCapabilities();
    add_settings_error(
        'wp_app_core_messages',
        'roles_created',
        __('Platform roles berhasil dibuat dan capabilities telah diterapkan.', 'wp-app-core'),
        'success'
    );
    // Refresh the roles list
    $existing_platform_roles = WP_App_Core_Role_Manager::getRoleSlugs();
    $platform_roles_exist = true;
}

// Get all editable roles
$all_roles = get_editable_roles();

// Display ONLY platform roles (exclude other plugin roles and standard WP roles)
// Platform permissions are specifically for platform staff management
$displayed_roles = [];
if ($platform_roles_exist) {
    // Show only platform roles with the admin-generic icon indicator
    foreach ($existing_platform_roles as $role_slug) {
        if (isset($all_roles[$role_slug])) {
            $displayed_roles[$role_slug] = $all_roles[$role_slug];
        }
    }
}

// Get current active tab with validation
$current_tab = isset($_GET['permission_tab']) ? sanitize_key($_GET['permission_tab']) : 'platform_management';

// Validate that the tab exists in capability_groups
if (!isset($capability_groups[$current_tab])) {
    $current_tab = 'platform_management';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role_permissions') {
    if (!check_admin_referer('wp_app_core_permissions')) {
        wp_die(__('Security check failed.', 'wp-app-core'));
    }

    $current_tab = sanitize_key($_POST['current_tab']);

    // Need to get capability groups for form processing
    $temp_permission_model = new \WPAppCore\Models\Settings\PlatformPermissionModel();
    $temp_capability_groups = $temp_permission_model->getCapabilityGroups();

    // Only get capabilities for current tab
    $current_tab_caps = isset($temp_capability_groups[$current_tab]['caps']) ?
                       $temp_capability_groups[$current_tab]['caps'] :
                       [];

    $updated = false;

    // Only process platform roles (consistent with display filter)
    $temp_platform_roles = \WP_App_Core_Role_Manager::getRoleSlugs();
    foreach ($temp_platform_roles as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            // Only process capabilities from current tab
            foreach ($current_tab_caps as $cap) {
                $has_cap = isset($_POST['permissions'][$role_name][$cap]);
                if ($role->has_cap($cap) !== $has_cap) {
                    if ($has_cap) {
                        $role->add_cap($cap);
                    } else {
                        $role->remove_cap($cap);
                    }
                    $updated = true;
                }
            }
        }
    }

    if ($updated) {
        add_settings_error(
            'wp_app_core_messages',
            'permissions_updated',
            sprintf(
                __('Hak akses %s berhasil diperbarui.', 'wp-app-core'),
                $temp_capability_groups[$current_tab]['title']
            ),
            'success'
        );
    }
}
?>

<div class="wrap">
    <div>
        <?php settings_errors('wp_app_core_messages'); ?>
    </div>

    <?php if (!$platform_roles_exist): ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php _e('Platform roles belum dibuat.', 'wp-app-core'); ?></strong>
            <?php _e('Untuk menggunakan permission matrix dengan platform roles, Anda perlu membuat platform roles terlebih dahulu.', 'wp-app-core'); ?>
        </p>
        <form method="post" style="margin: 10px 0;">
            <?php wp_nonce_field('wp_app_core_create_roles'); ?>
            <button type="submit" name="create_platform_roles" class="button button-primary">
                <?php _e('Buat Platform Roles', 'wp-app-core'); ?>
            </button>
            <p class="description">
                <?php _e('Ini akan membuat 7 platform roles: Super Admin, Admin, Manager, Support, Finance, Analyst, dan Viewer.', 'wp-app-core'); ?>
            </p>
        </form>
    </div>
    <?php endif; ?>

    <h2 class="nav-tab-wrapper wp-clearfix">
        <?php foreach ($capability_groups as $tab_key => $group): ?>
            <a href="<?php echo add_query_arg(['tab' => 'permissions', 'permission_tab' => $tab_key]); ?>"
               class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($group['title']); ?>
            </a>
        <?php endforeach; ?>
    </h2>

    <!-- Header Section -->
    <div class="settings-header-section" style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 15px 20px; margin-top: 20px;">
        <h3 style="margin: 0; color: #1d2327;">
            <?php
            printf(
                __('Managing %s Permissions', 'wp-app-core'),
                esc_html($capability_groups[$current_tab]['title'])
            );
            ?>
        </h3>
        <p style="margin: 5px 0 0 0; color: #646970; font-size: 13px;">
            <?php _e('Configure which platform roles <span class="dashicons dashicons-admin-generic" style="font-size: 14px; vertical-align: middle; color: #0073aa;"></span> have access to these capabilities. Only platform staff roles are shown here.', 'wp-app-core'); ?>
        </p>
    </div>

    <!-- Reset Section -->
    <div class="settings-section" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px;">
        <button type="button" class="button button-secondary button-reset-permissions">
            <span class="dashicons dashicons-image-rotate"></span>
            <?php _e('Reset to Default', 'wp-app-core'); ?>
        </button>
        <p class="description">
            <?php
            printf(
                __('Reset <strong>%s</strong> permissions to plugin defaults. This will restore the original capability settings for all roles in this group.', 'wp-app-core'),
                esc_html($capability_groups[$current_tab]['title'])
            );
            ?>
        </p>
    </div>

    <!-- Permission Matrix Section -->
    <div class="permissions-section" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px;">
        <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #dcdcde;">
            <?php
            printf(
                __('Platform Settings - %s', 'wp-app-core'),
                esc_html($capability_groups[$current_tab]['title'])
            );
            ?>
        </h2>

        <form method="post" action="<?php echo add_query_arg(['tab' => 'permissions', 'permission_tab' => $current_tab]); ?>">
            <?php wp_nonce_field('wp_app_core_permissions'); ?>
            <input type="hidden" name="current_tab" value="<?php echo esc_attr($current_tab); ?>">
            <input type="hidden" name="action" value="update_role_permissions">

            <p class="description" style="margin-bottom: 15px;">
                <?php _e('Check capabilities for each platform role. WordPress Administrators automatically have full access to all platform capabilities.', 'wp-app-core'); ?>
            </p>

            <table class="widefat fixed striped permission-matrix-table">
                <thead>
                    <tr>
                        <th class="column-role"><?php _e('Role', 'wp-app-core'); ?></th>
                        <?php foreach ($capability_groups[$current_tab]['caps'] as $cap): ?>
                            <th class="column-permission">
                                <?php echo esc_html($permission_labels[$cap]); ?>
                                <span class="dashicons dashicons-info"
                                      title="<?php echo esc_attr($capability_descriptions[$cap] ?? ''); ?>">
                                </span>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (empty($displayed_roles)) {
                        echo '<tr><td colspan="' . (count($capability_groups[$current_tab]['caps']) + 1) . '" style="text-align:center;">';
                        _e('Tidak ada roles yang tersedia. Silakan buat platform roles terlebih dahulu.', 'wp-app-core');
                        echo '</td></tr>';
                    } else {
                        foreach ($displayed_roles as $role_name => $role_info):
                            $role = get_role($role_name);
                            if (!$role) continue;
                    ?>
                        <tr>
                            <td class="column-role">
                                <strong><?php echo translate_user_role($role_info['name']); ?></strong>
                                <span class="dashicons dashicons-admin-generic" style="color: #0073aa; font-size: 14px; vertical-align: middle;" title="<?php _e('Platform Role', 'wp-app-core'); ?>"></span>
                            </td>
                            <?php foreach ($capability_groups[$current_tab]['caps'] as $cap): ?>
                                <td class="column-permission">
                                    <input type="checkbox"
                                           class="permission-checkbox"
                                           name="permissions[<?php echo esc_attr($role_name); ?>][<?php echo esc_attr($cap); ?>]"
                                           value="1"
                                           data-role="<?php echo esc_attr($role_name); ?>"
                                           data-capability="<?php echo esc_attr($cap); ?>"
                                           <?php checked($role->has_cap($cap)); ?>>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php
                        endforeach;
                    }
                    ?>
                </tbody>
            </table>

            <?php submit_button(__('Save Changes', 'wp-app-core')); ?>
        </form>
    </div><!-- .permissions-section -->
</div><!-- .wrap -->
