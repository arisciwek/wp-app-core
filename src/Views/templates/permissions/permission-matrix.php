<?php
/**
 * Shared Permission Matrix Template
 *
 * @package     WP_App_Core
 * @subpackage  Views/Templates/Permissions
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/templates/permissions/permission-matrix.php
 *
 * Description: Reusable template untuk permission management UI.
 *              Provides nested tab navigation and permission matrix table.
 *              Used by all plugins that extend AbstractPermissionsController.
 *
 * Required Variables (from controller's getViewModel()):
 * @var string $plugin_prefix          Plugin prefix (wpapp, customer, agency)
 * @var string $plugin_slug           Plugin slug (wp-app-core, wp-customer, etc)
 * @var string $text_domain           Text domain for translations
 * @var string $current_tab           Current capability group tab
 * @var array  $capability_groups     All capability groups with tabs
 * @var array  $current_group         Current group data (title, description, caps)
 * @var array  $all_capabilities      All available capabilities
 * @var array  $capability_labels     Capability labels for display
 * @var array  $capability_descriptions Capability descriptions for tooltips
 * @var array  $displayed_roles       Roles to display in matrix
 * @var array  $role_matrix           Current role capabilities matrix
 * @var string $page_title            Page title (optional)
 * @var string $page_description      Page description (optional)
 *
 * Hooks Provided:
 * - {prefix}_before_permission_matrix
 * - {prefix}_after_permission_matrix
 * - {prefix}_permission_matrix_tab_content
 * - {prefix}_permission_matrix_actions
 *
 * Changelog:
 * 1.0.0 - 2025-01-12 (TODO-1206)
 * - Initial creation
 * - Nested tab navigation
 * - Permission matrix table
 * - Reset button with WPModal
 * - Extensibility hooks
 */

// Ensure required variables exist
if (!isset($plugin_prefix) || !isset($capability_groups) || !isset($displayed_roles)) {
    wp_die(__('Required template variables missing. Please check controller getViewModel().', 'wp-app-core'));
}

// Set defaults for optional variables
$page_title = $page_title ?? __('Permission Management', 'wp-app-core');
$page_description = $page_description ?? __('Configure role capabilities for this plugin', 'wp-app-core');
?>

<div class="wrap permission-matrix-wrapper">
    <!-- Header Section -->
    <div class="permission-header">
        <h2><?php echo esc_html($page_title); ?></h2>
        <p class="description"><?php echo esc_html($page_description); ?></p>
    </div>

    <?php
    /**
     * Hook: {prefix}_before_permission_matrix
     *
     * Allows plugins to add content before permission matrix
     *
     * @param array $view_data All view data from controller
     */
    do_action("{$plugin_prefix}_before_permission_matrix", compact(
        'plugin_prefix',
        'plugin_slug',
        'current_tab',
        'capability_groups',
        'displayed_roles'
    ));
    ?>

    <!-- Nested Tab Navigation for Capability Groups -->
    <?php if (count($capability_groups) > 1): ?>
    <h2 class="nav-tab-wrapper permission-nav-tab-wrapper">
        <?php foreach ($capability_groups as $group_key => $group): ?>
            <?php
            $tab_url = add_query_arg([
                'page' => $_GET['page'] ?? '',
                'tab' => $_GET['tab'] ?? '',
                'permission_tab' => $group_key
            ], admin_url('admin.php'));

            // Remove any reset/success parameters
            $tab_url = remove_query_arg(['reset', 'settings-updated', 'message'], $tab_url);

            $is_active = ($current_tab === $group_key);
            ?>
            <a href="<?php echo esc_url($tab_url); ?>"
               class="nav-tab <?php echo $is_active ? 'nav-tab-active' : ''; ?>"
               data-group="<?php echo esc_attr($group_key); ?>">
                <?php echo esc_html($group['title']); ?>
            </a>
        <?php endforeach; ?>
    </h2>
    <?php endif; ?>

    <!-- Current Group Description -->
    <?php if (!empty($current_group['description'])): ?>
    <div class="permission-group-description">
        <p><?php echo esc_html($current_group['description']); ?></p>
    </div>
    <?php endif; ?>

    <!-- Reset Section -->
    <div class="permission-reset-section">
        <button type="button"
                class="button button-secondary btn-reset-permissions"
                data-plugin-prefix="<?php echo esc_attr($plugin_prefix); ?>"
                data-nonce="<?php echo wp_create_nonce($plugin_prefix . '_reset_permissions'); ?>">
            <span class="dashicons dashicons-image-rotate"></span>
            <?php _e('Reset All Permissions to Default', $text_domain); ?>
        </button>
        <p class="description reset-warning">
            <?php _e('Warning: This will reset ALL permissions across all tabs to their default values. This action cannot be undone.', $text_domain); ?>
        </p>

        <?php
        /**
         * Hook: {prefix}_permission_matrix_actions
         *
         * Allows plugins to add custom action buttons
         *
         * @param string $plugin_prefix Plugin prefix
         * @param string $current_tab Current capability group tab
         */
        do_action("{$plugin_prefix}_permission_matrix_actions", $plugin_prefix, $current_tab);
        ?>
    </div>

    <!-- Permission Matrix Table -->
    <div class="permission-matrix-container">
        <?php
        /**
         * Hook: {prefix}_permission_matrix_tab_content
         *
         * Allows plugins to completely override tab content
         * If this hook outputs content, default table won't render
         */
        ob_start();
        do_action("{$plugin_prefix}_permission_matrix_tab_content", $current_tab, $current_group);
        $custom_content = ob_get_clean();

        if (!empty($custom_content)):
            echo $custom_content;
        else:
        ?>
        <table class="widefat fixed striped permission-matrix-table">
            <thead>
                <tr>
                    <th class="column-role"><?php _e('Role', $text_domain); ?></th>
                    <?php foreach ($current_group['caps'] as $cap): ?>
                        <th class="column-permission" title="<?php echo esc_attr($capability_descriptions[$cap] ?? ''); ?>">
                            <?php echo esc_html($capability_labels[$cap] ?? $cap); ?>
                            <?php if (!empty($capability_descriptions[$cap])): ?>
                            <span class="dashicons dashicons-info-outline permission-info-icon"
                                  title="<?php echo esc_attr($capability_descriptions[$cap]); ?>"></span>
                            <?php endif; ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($displayed_roles as $role_name => $role_info):
                    $role = get_role($role_name);
                    if (!$role) continue;
                ?>
                    <tr data-role="<?php echo esc_attr($role_name); ?>">
                        <td class="column-role">
                            <strong><?php echo translate_user_role($role_info['name']); ?></strong>
                            <span class="role-slug"><?php echo esc_html($role_name); ?></span>
                        </td>
                        <?php foreach ($current_group['caps'] as $cap): ?>
                            <td class="column-permission">
                                <?php
                                $has_cap = $role->has_cap($cap);
                                $is_disabled = ($role_name === 'administrator');
                                ?>
                                <label class="permission-checkbox-wrapper">
                                    <input type="checkbox"
                                           class="permission-checkbox"
                                           data-role="<?php echo esc_attr($role_name); ?>"
                                           data-capability="<?php echo esc_attr($cap); ?>"
                                           data-plugin-prefix="<?php echo esc_attr($plugin_prefix); ?>"
                                           data-nonce="<?php echo wp_create_nonce($plugin_prefix . '_nonce'); ?>"
                                           <?php checked($has_cap); ?>
                                           <?php disabled($is_disabled); ?>>
                                    <span class="permission-status">
                                        <?php if ($has_cap): ?>
                                            <span class="dashicons dashicons-yes-alt permission-enabled"></span>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-minus permission-disabled"></span>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Legend -->
        <div class="permission-legend">
            <p class="description">
                <span class="dashicons dashicons-yes-alt permission-enabled"></span> <?php _e('Permission granted', $text_domain); ?>
                &nbsp;&nbsp;
                <span class="dashicons dashicons-minus permission-disabled"></span> <?php _e('Permission denied', $text_domain); ?>
            </p>
        </div>
        <?php endif; // End custom content check ?>
    </div>

    <?php
    /**
     * Hook: {prefix}_after_permission_matrix
     *
     * Allows plugins to add content after permission matrix
     *
     * @param array $view_data All view data from controller
     */
    do_action("{$plugin_prefix}_after_permission_matrix", compact(
        'plugin_prefix',
        'plugin_slug',
        'current_tab',
        'capability_groups',
        'displayed_roles'
    ));
    ?>
</div>
