# TODO-1190: Implement Static ID Hook Pattern in wp-app-core

**Status**: Pending
**Priority**: High
**Created**: 2025-11-01
**Plugin**: wp-app-core
**Related**: wp-customer (TODO-2185), wp-agency (TODO-3098)

## Problem Statement

The wp-app-core plugin DOES NOT implement the static ID hook pattern that is now standard in wp-customer and wp-agency plugins. This creates inconsistency across the platform and prevents reliable demo data generation with predictable IDs.

### Current Implementation (Outdated)

**WPUserGenerator** (`/src/Database/Demo/WPUserGenerator.php`):
- Uses direct database UPDATE to change WordPress user IDs (lines 81-100)
- No hook pattern for static ID injection
- Old approach that bypasses WordPress standards

**PlatformStaffModel** (`/src/Models/Platform/PlatformStaffModel.php`):
- No `wp_app_core_platform_staff_before_insert` filter hook
- Direct wpdb->insert without allowing ID override (lines 202-234)
- Cannot inject static entity IDs

**PlatformStaffController** (`/src/Controllers/Platform/PlatformStaffController.php`):
- No hook before wp_create_user() for static WordPress user IDs (line 351)
- Production code cannot create users with specific IDs
- No `wp_app_core_staff_user_before_insert` filter

**PlatformDemoData** (`/src/Database/Demo/PlatformDemoData.php`):
- No filter hook application for static ID injection
- Relies on old WPUserGenerator method

## Required Implementation

Follow the exact pattern used in wp-customer and wp-agency plugins:

### 1. WordPress User Static IDs (Controller Layer)

**File**: `src/Controllers/Platform/PlatformStaffController.php`

Add hook in `createStaff()` method before `wp_create_user()`:

```php
// Prepare user data
$user_data = [
    'user_login' => $user_login,
    'user_email' => $user_email,
    'user_pass' => wp_generate_password(12, true, true),
    'display_name' => $full_name
];

// Apply filter hook BEFORE wp_create_user()
$user_data = apply_filters(
    'wp_app_core_staff_user_before_insert',
    $user_data,
    $data,
    'platform_staff'
);

// Handle static ID if requested
$static_user_id = null;
if (isset($user_data['ID'])) {
    $static_user_id = $user_data['ID'];
    unset($user_data['ID']); // Remove before wp_create_user
}

$user_id = wp_create_user(
    $user_data['user_login'],
    $user_data['user_pass'],
    $user_data['user_email']
);

// Update to static ID if requested
if ($static_user_id !== null && $static_user_id != $user_id) {
    global $wpdb;
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->users} WHERE ID = %d",
        $static_user_id
    ));

    if (!$existing) {
        $wpdb->query('SET FOREIGN_KEY_CHECKS=0');
        $wpdb->update($wpdb->users, ['ID' => $static_user_id], ['ID' => $user_id], ['%d'], ['%d']);
        $wpdb->update($wpdb->usermeta, ['user_id' => $static_user_id], ['user_id' => $user_id], ['%d'], ['%d']);
        $wpdb->query('SET FOREIGN_KEY_CHECKS=1');
        $user_id = $static_user_id;
    }
}
```

### 2. Entity Static IDs (Model Layer)

**File**: `src/Models/Platform/PlatformStaffModel.php`

Update `create()` method to support static ID injection:

```php
public function create(array $data) {
    global $wpdb;

    // Generate employee_id if not provided
    if (empty($data['employee_id'])) {
        $data['employee_id'] = $this->generateEmployeeId();
    }

    $insert_data = [
        'user_id' => $data['user_id'],
        'employee_id' => $data['employee_id'],
        'full_name' => $data['full_name'],
        'department' => !empty($data['department']) ? $data['department'] : null,
        'hire_date' => !empty($data['hire_date']) ? $data['hire_date'] : null,
        'phone' => !empty($data['phone']) ? $data['phone'] : null,
        'status' => !empty($data['status']) ? $data['status'] : 'aktif',
    ];

    // HOOK: Allow static ID injection via filter
    $insert_data = apply_filters('wp_app_core_platform_staff_before_insert', $insert_data, $data);

    // If 'id' field was injected via filter, reorder to put it first
    if (isset($insert_data['id'])) {
        $static_id = $insert_data['id'];
        unset($insert_data['id']);
        $insert_data = array_merge(['id' => $static_id], $insert_data);
    }

    // Prepare format array (must match key order)
    $format = [];
    if (isset($insert_data['id'])) {
        $format[] = '%d';  // id first
    }
    $format = array_merge($format, [
        '%d',  // user_id
        '%s',  // employee_id
        '%s',  // full_name
        '%s',  // department
        '%s',  // hire_date
        '%s',  // phone
        '%s',  // status
    ]);

    $result = $wpdb->insert($this->table, $insert_data, $format);

    if ($result) {
        $staff_id = $wpdb->insert_id;

        // Clear caches
        $this->cache->clearStaffCache();

        return $staff_id;
    }

    return false;
}
```

### 3. Update WPUserGenerator (Demo Layer)

**File**: `src/Database/Demo/WPUserGenerator.php`

Replace the static `createUser()` method to use an instance-based approach with the new hook pattern like wp-agency's WPUserGenerator v1.0.8.

Reference: `/wp-agency/src/Database/Demo/WPUserGenerator.php` lines 44-177

Key changes:
- Add `validate()` method with CLI detection
- Add `generateUser()` method using filter hooks
- Remove direct database UPDATE approach
- Add CLI/script detection: `(defined('WP_CLI') && WP_CLI) || (PHP_SAPI === 'cli')`

### 4. Update PlatformDemoData

**File**: `src/Database/Demo/PlatformDemoData.php`

Add filter hook to inject static IDs when generating demo data:

```php
public static function generate() {
    global $wpdb;

    // Add filter hook to inject static entity IDs for demo data
    add_filter('wp_app_core_platform_staff_before_insert', function($insert_data, $data) {
        if (isset($data['_demo_entity_id'])) {
            $insert_data['id'] = $data['_demo_entity_id'];
            error_log("PlatformDemoData: Injecting static staff ID: {$insert_data['id']}");
        }
        return $insert_data;
    }, 10, 2);

    // ... rest of generation logic

    foreach ($user_data as $data) {
        // Create WordPress user
        $user_result = WPUserGenerator::createUser(
            $data['id'],
            $data['username'],
            $data['display_name'],
            $data['email'],
            $data['roles'],
            'password123'
        );

        // Create platform staff record
        $staff_data = [
            'user_id' => $data['id'],
            'employee_id' => $data['employee_id'],
            'full_name' => $data['display_name'],
            'department' => $data['department'],
            'hire_date' => $data['hire_date'],
            'phone' => $data['phone'],
            '_demo_entity_id' => $data['id'], // Pass for hook injection
        ];

        $staff_id = $model->create($staff_data);
    }
}
```

## Cross-Plugin Consistency

After implementation, all three plugins will have:

### Hook Pattern Summary

**wp-customer**:
- `wp_customer_customer_user_before_insert` (controller)
- `wp_customer_before_insert` (CustomerModel)
- `wp_customer_employee_user_before_insert` (EmployeeController)
- `wp_customer_employee_before_insert` (CustomerEmployeeModel)

**wp-agency**:
- `wp_agency_agency_user_before_insert` (AgencyController)
- `wp_agency_before_insert` (AgencyModel)
- `wp_agency_division_before_insert` (DivisionModel)
- `wp_agency_employee_user_before_insert` (AgencyEmployeeController)
- `wp_agency_employee_before_insert` (AgencyEmployeeModel)

**wp-app-core** (TO BE IMPLEMENTED):
- `wp_app_core_staff_user_before_insert` (PlatformStaffController)
- `wp_app_core_platform_staff_before_insert` (PlatformStaffModel)

## Benefits

1. **Consistency**: All plugins use the same pattern for static ID handling
2. **Testability**: Demo data generates with predictable IDs across fresh installations
3. **Production Ready**: Production code can handle static IDs when needed
4. **Cache Safety**: Proper handling of WordPress cache after user operations
5. **WordPress Standards**: Uses filter hooks instead of direct database manipulation

## Files to Modify

1. `/src/Controllers/Platform/PlatformStaffController.php` - Add user hook in createStaff()
2. `/src/Models/Platform/PlatformStaffModel.php` - Add entity hook in create()
3. `/src/Database/Demo/WPUserGenerator.php` - Replace with new pattern
4. `/src/Database/Demo/PlatformDemoData.php` - Add filter hook application

## Testing Required

1. Create test script: `TEST/test-staff-static-id.php`
2. Verify WordPress user creation with static IDs
3. Verify platform_staff entity creation with static IDs
4. Test full demo data generation
5. Verify cache flushing after cleanup

## References

- wp-customer TODO-2185: Static ID implementation
- wp-agency TODO-3098: Static ID implementation (completed 2025-11-01)
- wp-agency WPUserGenerator v1.0.8: New pattern with CLI detection
- wp-agency AgencyDemoData v1.0.8: Hook injection pattern
- wp-agency Models v1.0.11+: Array reordering for static ID

## Priority Justification

HIGH priority because:
- User entities are fundamental (as user emphasized: "user ini hal paling mendasar")
- Creates inconsistency across plugins
- Blocks reliable demo data generation
- Already implemented in other two plugins
- Pattern is proven and tested

## Implementation Order

1. Update WPUserGenerator first (foundation)
2. Add hook to PlatformStaffModel (entity layer)
3. Add hook to PlatformStaffController (user layer)
4. Update PlatformDemoData to use hooks
5. Create test scripts
6. Verify full generation flow

---

**Next Steps**: Implement changes following the exact pattern from wp-agency plugin (most recent implementation, 2025-11-01).
