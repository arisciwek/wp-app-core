<?php
/**
 * User Profile Fields Template
 *
 * @package     WP_App_Core
 * @subpackage  Views/Templates/User
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/templates/user/_user_profile_fields.php
 *
 * Description: Generic template untuk menampilkan profile fields.
 *              Dapat digunakan oleh berbagai plugin (customer, agency, dll).
 *              Menggunakan filter system untuk extensibility.
 *
 * Changelog:
 * 1.0.0 - 2025-01-18
 * - Initial release (migrated from wp-customer)
 * - Made generic and reusable
 * - Added filter system for customization
 */

defined('ABSPATH') || exit;

// Ensure required variables are set
$userData = $userData ?? [];
$user_roles = $user_roles ?? [];
$user_capabilities = $user_capabilities ?? [];

// Allow plugins to modify user data before display
$userData = apply_filters('wp_app_core_user_profile_data', $userData, $user->ID ?? 0);

?>

<h2><?php echo esc_html(apply_filters('wp_app_core_profile_additional_info_title', __('Additional User Information', 'wp-app-core'))); ?></h2>
<table class="form-table">
    <?php
    // Allow plugins to add custom fields before standard fields
    do_action('wp_app_core_before_profile_fields', $userData, $user);

    // Display entity info (customer/agency name)
    if (isset($userData['entity_name'])) :
    ?>
    <tr>
        <th><label><?php echo esc_html(apply_filters('wp_app_core_profile_entity_label', __('Entity', 'wp-app-core'))); ?></label></th>
        <td><?php echo esc_html($userData['entity_name']); ?></td>
    </tr>
    <?php endif; ?>

    <?php
    // Display branch/office info
    if (isset($userData['branch_name'])) :
    ?>
    <tr>
        <th><label><?php echo esc_html(apply_filters('wp_app_core_profile_branch_label', __('Branch', 'wp-app-core'))); ?></label></th>
        <td><?php echo esc_html($userData['branch_name']); ?></td>
    </tr>
    <?php endif; ?>

    <?php
    // Display position
    if (isset($userData['position'])) :
    ?>
    <tr>
        <th><label><?php echo esc_html(apply_filters('wp_app_core_profile_position_label', __('Position', 'wp-app-core'))); ?></label></th>
        <td><?php echo esc_html($userData['position']); ?></td>
    </tr>
    <?php endif; ?>

    <?php
    // Display department
    if (isset($userData['department'])) :
    ?>
    <tr>
        <th><label><?php echo esc_html(apply_filters('wp_app_core_profile_department_label', __('Department', 'wp-app-core'))); ?></label></th>
        <td><?php echo esc_html($userData['department']); ?></td>
    </tr>
    <?php endif; ?>

    <?php
    // Allow plugins to add custom fields after standard fields
    do_action('wp_app_core_after_profile_fields', $userData, $user);
    ?>
</table>

<h2><?php echo esc_html(apply_filters('wp_app_core_profile_roles_title', __('Roles & Capabilities', 'wp-app-core'))); ?></h2>
<table class="form-table">
    <tr>
        <th><label><?php esc_html_e('Roles', 'wp-app-core'); ?></label></th>
        <td>
            <?php if (!empty($user_roles)) : ?>
                <?php echo implode(', ', array_map('esc_html', $user_roles)); ?>
            <?php else : ?>
                <?php esc_html_e('No roles assigned', 'wp-app-core'); ?>
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <th><label><?php esc_html_e('Capabilities', 'wp-app-core'); ?></label></th>
        <td>
            <?php if (!empty($user_capabilities)) : ?>
                <ul style="columns: 2;">
                    <?php foreach ($user_capabilities as $cap) : ?>
                        <li><?php echo esc_html($cap); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <?php esc_html_e('No capabilities found', 'wp-app-core'); ?>
            <?php endif; ?>
        </td>
    </tr>
</table>

<?php
// Allow plugins to add additional sections
do_action('wp_app_core_after_profile_section', $userData, $user);
?>
