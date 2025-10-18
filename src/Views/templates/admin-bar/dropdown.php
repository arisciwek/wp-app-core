<?php
/**
 * Admin Bar Dropdown Template
 *
 * @package     WP_App_Core
 * @subpackage  Templates
 * @version     1.0.0
 *
 * Available variables:
 * @var int $user_id
 * @var WP_User $user
 * @var array $user_info
 */

defined('ABSPATH') || exit;
?>

<div class="wp-app-core-detailed-info">
    <!-- User Information Section -->
    <div class="info-section">
        <strong>User Information:</strong><br>
        ID: <?php echo esc_html($user_id); ?><br>
        Username: <?php echo esc_html($user->user_login); ?><br>
        Email: <?php echo esc_html($user->user_email); ?><br>
    </div>

    <?php if ($user_info): ?>
    <!-- Entity Information Section -->
    <div class="info-section">
        <strong>Entity Information:</strong><br>
        <?php if (isset($user_info['entity_name'])): ?>
            Entity: <?php echo esc_html($user_info['entity_name']); ?><br>
        <?php endif; ?>
        <?php if (isset($user_info['entity_code'])): ?>
            Code: <?php echo esc_html($user_info['entity_code']); ?><br>
        <?php endif; ?>
        <?php if (isset($user_info['branch_name'])): ?>
            Branch: <?php echo esc_html($user_info['branch_name']); ?><br>
        <?php endif; ?>
        <?php if (isset($user_info['branch_type'])): ?>
            Type: <?php echo esc_html(ucfirst($user_info['branch_type'])); ?><br>
        <?php endif; ?>
        <?php if (isset($user_info['division_name'])): ?>
            Division: <?php echo esc_html($user_info['division_name']); ?><br>
        <?php endif; ?>
        <?php if (isset($user_info['division_type'])): ?>
            Division Type: <?php echo esc_html(ucfirst($user_info['division_type'])); ?><br>
        <?php endif; ?>
        <?php if (isset($user_info['relation_type'])): ?>
            Relation: <?php echo esc_html(ucfirst(str_replace('_', ' ', $user_info['relation_type']))); ?><br>
        <?php endif; ?>
        <?php if (isset($user_info['position'])): ?>
            Position: <?php echo esc_html($user_info['position']); ?><br>
        <?php endif; ?>
        <?php if (isset($user_info['department'])): ?>
            Department: <?php echo esc_html($user_info['department']); ?><br>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Roles Section -->
    <div class="info-section">
        <strong>Roles:</strong><br>
        <?php
        if (isset($user_info['role_names']) && is_array($user_info['role_names']) && !empty($user_info['role_names'])):
            foreach ($user_info['role_names'] as $role_name):
        ?>
            • <?php echo esc_html($role_name); ?><br>
        <?php
            endforeach;
        else:
            foreach ((array) $user->roles as $role):
        ?>
            • <?php echo esc_html($role); ?><br>
        <?php
            endforeach;
        endif;
        ?>
    </div>

    <!-- Key Capabilities Section -->
    <div class="info-section">
        <strong>Key Capabilities:</strong><br>
        <?php
        if (isset($user_info['permission_names']) && is_array($user_info['permission_names']) && !empty($user_info['permission_names'])):
            foreach ($user_info['permission_names'] as $permission):
        ?>
            ✓ <?php echo esc_html($permission); ?><br>
        <?php
            endforeach;
        else:
            // Fallback
            $key_caps = apply_filters('wp_app_core_admin_bar_key_capabilities', [
                'view_customer_list',
                'view_customer_branch_list',
                'view_customer_employee_list',
                'edit_all_customers',
                'edit_own_customer',
                'manage_options'
            ]);

            $has_caps = false;
            foreach ($key_caps as $cap):
                if (user_can($user_id, $cap)):
                    $has_caps = true;
        ?>
            ✓ <?php echo esc_html($cap); ?><br>
        <?php
                endif;
            endforeach;

            if (!$has_caps):
        ?>
            No key capabilities found<br>
        <?php
            endif;
        endif;
        ?>
    </div>
</div>
