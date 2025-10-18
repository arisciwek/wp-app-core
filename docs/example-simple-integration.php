<?php
/**
 * Example: Simplified Plugin Integration with WP App Core
 *
 * This file shows how SIMPLE it is for plugins to integrate with wp-app-core
 * using the NEW simplified approach (v2.0+).
 *
 * NO integration class needed!
 * NO role manager needed!
 * Just ONE filter in your main plugin file!
 *
 * ============================================================================
 * EXAMPLE 1: wp-customer Integration (SIMPLIFIED)
 * ============================================================================
 */

// In your main plugin file (wp-customer.php), just add this:

add_filter('wp_app_core_user_entity_data', 'wp_customer_provide_entity_data', 10, 3);

/**
 * Provide customer entity data for admin bar
 *
 * wp-app-core handles ALL WordPress stuff (users, roles, permissions).
 * You ONLY provide entity-specific data from your tables!
 *
 * @param mixed $entity_data Current entity data (null if no other plugin provided data yet)
 * @param int $user_id WordPress user ID
 * @param WP_User $user WordPress user object
 * @return array|null Entity data array or null if user is not a customer
 */
function wp_customer_provide_entity_data($entity_data, $user_id, $user) {
    // If another plugin already provided entity data, skip
    // This allows multiple plugins to coexist peacefully
    if ($entity_data) {
        return $entity_data;
    }

    global $wpdb;

    // Try employee first (most common case)
    $employee = $wpdb->get_row($wpdb->prepare(
        "SELECT e.*,
                c.name as customer_name,
                c.code as customer_code,
                b.name as branch_name,
                b.type as branch_type
         FROM {$wpdb->prefix}app_customer_employees e
         INNER JOIN {$wpdb->prefix}app_customers c ON e.customer_id = c.id
         INNER JOIN {$wpdb->prefix}app_customer_branches b ON e.branch_id = b.id
         WHERE e.user_id = %d AND e.status = 'active'
         LIMIT 1",
        $user_id
    ));

    if ($employee) {
        return [
            'entity_name' => $employee->customer_name,
            'entity_code' => $employee->customer_code,
            'branch_name' => $employee->branch_name,
            'branch_type' => $employee->branch_type,
            'position' => $employee->position,
            'icon' => '🏢',
            'relation_type' => 'employee'
        ];
    }

    // Try customer owner
    $customer = $wpdb->get_row($wpdb->prepare(
        "SELECT c.id, c.name, c.code
         FROM {$wpdb->prefix}app_customers c
         WHERE c.user_id = %d",
        $user_id
    ));

    if ($customer) {
        // Get main branch
        $branch = $wpdb->get_row($wpdb->prepare(
            "SELECT name, type
             FROM {$wpdb->prefix}app_customer_branches
             WHERE customer_id = %d AND type = 'pusat'
             ORDER BY id ASC LIMIT 1",
            $customer->id
        ));

        return [
            'entity_name' => $customer->name,
            'entity_code' => $customer->code,
            'branch_name' => $branch ? $branch->name : '',
            'branch_type' => $branch ? $branch->type : '',
            'icon' => '🏢',
            'relation_type' => 'owner'
        ];
    }

    // Try branch admin
    $branch_admin = $wpdb->get_row($wpdb->prepare(
        "SELECT b.name as branch_name,
                b.type as branch_type,
                c.name as customer_name,
                c.code as customer_code
         FROM {$wpdb->prefix}app_customer_branches b
         LEFT JOIN {$wpdb->prefix}app_customers c ON b.customer_id = c.id
         WHERE b.user_id = %d",
        $user_id
    ));

    if ($branch_admin) {
        return [
            'entity_name' => $branch_admin->customer_name,
            'entity_code' => $branch_admin->customer_code,
            'branch_name' => $branch_admin->branch_name,
            'branch_type' => $branch_admin->branch_type,
            'icon' => '🏢',
            'relation_type' => 'branch_admin'
        ];
    }

    // Not a customer user
    return null;
}

/**
 * ============================================================================
 * EXAMPLE 2: wp-agency Integration (SIMPLIFIED)
 * ============================================================================
 */

// In your main plugin file (wp-agency.php), just add this:

add_filter('wp_app_core_user_entity_data', 'wp_agency_provide_entity_data', 10, 3);

function wp_agency_provide_entity_data($entity_data, $user_id, $user) {
    // If another plugin already provided data, skip
    if ($entity_data) {
        return $entity_data;
    }

    global $wpdb;

    // Single query handles all cases
    $agency_data = $wpdb->get_row($wpdb->prepare(
        "SELECT e.*,
                MAX(a.name) AS agency_name,
                MAX(a.code) AS agency_code,
                MAX(d.name) AS division_name,
                MAX(d.type) AS division_type,
                GROUP_CONCAT(j.jurisdiction_code) AS jurisdiction_codes
         FROM {$wpdb->prefix}app_agency_employees e
         INNER JOIN {$wpdb->prefix}app_agencies a ON e.agency_id = a.id
         INNER JOIN {$wpdb->prefix}app_agency_divisions d ON e.division_id = d.id
         INNER JOIN {$wpdb->prefix}app_agency_jurisdictions j ON d.id = j.division_id
         WHERE e.user_id = %d
         GROUP BY e.id
         LIMIT 1",
        $user_id
    ));

    if ($agency_data) {
        return [
            'entity_name' => $agency_data->agency_name,
            'entity_code' => $agency_data->agency_code,
            'division_name' => $agency_data->division_name,
            'division_type' => $agency_data->division_type,
            'jurisdiction_codes' => $agency_data->jurisdiction_codes,
            'position' => $agency_data->position,
            'icon' => '🏛️',
            'relation_type' => 'agency_employee'
        ];
    }

    // Not an agency user
    return null;
}

/**
 * ============================================================================
 * OPTIONAL: Custom Role Names (if you want friendly display names)
 * ============================================================================
 */

// If you want to customize how role names are displayed:
add_filter('wp_app_core_role_display_name', 'wp_customer_role_display_name', 10, 2);

function wp_customer_role_display_name($name, $slug) {
    $custom_names = [
        'customer' => 'Customer',
        'customer_admin' => 'Customer Admin',
        'customer_branch_admin' => 'Branch Admin',
        'customer_employee' => 'Employee',
    ];

    return isset($custom_names[$slug]) ? $custom_names[$slug] : $name;
}

/**
 * ============================================================================
 * OPTIONAL: Custom Permission Names (if you want friendly display names)
 * ============================================================================
 */

// If you want to customize how permission names are displayed:
add_filter('wp_app_core_permission_display_name', 'wp_customer_permission_display_name', 10, 2);

function wp_customer_permission_display_name($name, $capability) {
    $custom_names = [
        'manage_customer_branches' => 'Manage Branches',
        'view_customer_reports' => 'View Reports',
        'edit_customer_employees' => 'Edit Employees',
        'delete_customer_data' => 'Delete Customer Data',
    ];

    return isset($custom_names[$capability]) ? $custom_names[$capability] : $name;
}

/**
 * ============================================================================
 * CACHE INVALIDATION: Tell wp-app-core when your data changes
 * ============================================================================
 */

// When you update employee data, invalidate the cache:

function wp_customer_after_update_employee($employee_id) {
    $employee = get_employee_by_id($employee_id); // Your function

    if ($employee && $employee->user_id) {
        // Tell wp-app-core to refresh cache for this user
        do_action('wp_app_core_invalidate_user_cache', $employee->user_id);
    }
}

// Hook into your update functions:
add_action('wp_customer_employee_updated', 'wp_customer_after_update_employee');
add_action('wp_customer_employee_deleted', 'wp_customer_after_update_employee');
add_action('wp_customer_employee_status_changed', 'wp_customer_after_update_employee');

/**
 * ============================================================================
 * THAT'S IT!
 * ============================================================================
 *
 * Total code needed: ~100 lines (vs 1300 lines in old approach!)
 *
 * Files needed: 1 (main plugin file) vs 3 files (Integration, RoleManager, Model)
 *
 * Benefits:
 * - ✅ 97% less code
 * - ✅ No integration class
 * - ✅ No role manager class
 * - ✅ No complex callbacks
 * - ✅ No dependencies on wp-app-core classes
 * - ✅ 15 minutes to implement (vs 4 hours)
 * - ✅ wp-app-core handles all WordPress stuff
 * - ✅ You only handle your entity data
 * - ✅ Simple, clean, maintainable
 *
 * wp-app-core handles:
 * - ✅ Querying WordPress users
 * - ✅ Getting user roles
 * - ✅ Getting user permissions
 * - ✅ Parsing capabilities
 * - ✅ Displaying role names
 * - ✅ Displaying permission names
 * - ✅ Rendering admin bar
 * - ✅ Caching
 * - ✅ Debug logging
 *
 * Your plugin only does:
 * - ✅ Query YOUR database tables
 * - ✅ Return simple entity data array
 *
 * Perfect separation of concerns!
 */