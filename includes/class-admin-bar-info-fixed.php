<?php
// Temporary fixed version of get_detailed_info_html
// Copy this function body to replace the broken one

private static function get_detailed_info_html($user_id, $user_info) {
    $user = get_user_by('ID', $user_id);

    // DEBUG: Log detailed info generation
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("=== Generating Detailed Info HTML ===");
        error_log("All available fields: " . print_r(array_keys($user_info ?? []), true));
    }

    // Build lines array to avoid HTML escaping issues
    $sections = [];

    // User Info Section
    $user_lines = [];
    $user_lines[] = '<strong>User Information:</strong>';
    $user_lines[] = 'ID: ' . $user_id;
    $user_lines[] = 'Username: ' . esc_html($user->user_login);
    $user_lines[] = 'Email: ' . esc_html($user->user_email);
    $sections[] = implode("\n", $user_lines);

    // Entity Info Section
    if ($user_info) {
        $entity_lines = [];
        $entity_lines[] = '<strong>Entity Information:</strong>';

        if (isset($user_info['entity_name'])) {
            $entity_lines[] = 'Entity: ' . esc_html($user_info['entity_name']);
        }
        if (isset($user_info['entity_code'])) {
            $entity_lines[] = 'Code: ' . esc_html($user_info['entity_code']);
        }
        if (isset($user_info['branch_name'])) {
            $entity_lines[] = 'Branch: ' . esc_html($user_info['branch_name']);
        }
        if (isset($user_info['branch_type'])) {
            $entity_lines[] = 'Type: ' . ucfirst($user_info['branch_type']);
        }
        if (isset($user_info['division_name'])) {
            $entity_lines[] = 'Division: ' . esc_html($user_info['division_name']);
        }
        if (isset($user_info['division_type'])) {
            $entity_lines[] = 'Division Type: ' . ucfirst($user_info['division_type']);
        }
        if (isset($user_info['relation_type'])) {
            $entity_lines[] = 'Relation: ' . ucfirst(str_replace('_', ' ', $user_info['relation_type']));
        }
        if (isset($user_info['position'])) {
            $entity_lines[] = 'Position: ' . esc_html($user_info['position']);
        }
        if (isset($user_info['department'])) {
            $entity_lines[] = 'Department: ' . esc_html($user_info['department']);
        }

        if (count($entity_lines) > 1) {
            $sections[] = implode("\n", $entity_lines);
        }
    }

    // Roles Section
    $role_lines = [];
    $role_lines[] = '<strong>Roles:</strong>';

    // DEBUG: Log roles section data
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("=== Detailed Info - Roles Section ===");
        error_log("user_info['role_names']: " . print_r($user_info['role_names'] ?? 'NOT SET', true));
        error_log("user->roles (slugs): " . print_r($user->roles, true));
    }

    if (isset($user_info['role_names']) && is_array($user_info['role_names']) && !empty($user_info['role_names'])) {
        foreach ($user_info['role_names'] as $role_name) {
            $role_lines[] = '• ' . esc_html($role_name);
        }
    } else {
        foreach ((array) $user->roles as $role) {
            $role_lines[] = '• ' . esc_html($role);
        }
    }
    $sections[] = implode("\n", $role_lines);

    // Capabilities Section
    $cap_lines = [];
    $cap_lines[] = '<strong>Key Capabilities:</strong>';

    // DEBUG: Log permissions section data
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("=== Detailed Info - Permissions Section ===");
        error_log("user_info['permission_names']: " . print_r($user_info['permission_names'] ?? 'NOT SET', true));
    }

    if (isset($user_info['permission_names']) && is_array($user_info['permission_names']) && !empty($user_info['permission_names'])) {
        foreach ($user_info['permission_names'] as $permission) {
            $cap_lines[] = '✓ ' . esc_html($permission);
        }
    } else {
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
        foreach ($key_caps as $cap) {
            if (user_can($user_id, $cap)) {
                $cap_lines[] = '✓ ' . $cap;
                $has_caps = true;
            }
        }

        if (!$has_caps) {
            $cap_lines[] = 'No key capabilities found';
        }
    }
    $sections[] = implode("\n", $cap_lines);

    // Join all sections with double newline and wrap in pre tag for proper formatting
    $content = implode("\n\n", $sections);

    return '<div style="white-space: pre-line; font-family: monospace; font-size: 12px; padding: 10px;">' . $content . '</div>';
}
