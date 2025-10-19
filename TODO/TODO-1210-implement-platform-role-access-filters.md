# TODO-1210: Implement Platform Role Access Filters for WP Customer

**Status**: 🔄 IN PROGRESS
**Date**: 2025-10-19
**Author**: arisciwek
**Priority**: High
**Dependencies**: wp-customer TODO-2165 (hook refactoring)

## Overview

Implement filter hooks yang memungkinkan platform roles (platform_finance, platform_admin, platform_super_admin, dll) untuk mengakses WP Customer entities (customer, branch, employee, invoice) meskipun mereka tidak memiliki relasi langsung di database.

## Problem

Platform users (seperti platform_finance) sudah memiliki capabilities untuk mengakses WP Customer menu:
- ✅ `view_customer_list` - Can see menu
- ✅ `view_customer_detail` - Can view details
- ✅ Menu sudah terlihat di wp-admin

Tetapi **DataTable kosong** karena `access_type = 'none'`:

```php
[19-Oct-2025 12:23:24 UTC] Access Result: Array
(
    [has_access] =>                          // ← FALSE
    [access_type] => none                    // ← PROBLEM!
    [relation] => Array
        (
            [is_admin] =>                    // Not WordPress admin
            [is_customer_admin] =>           // Not customer owner
            [is_customer_branch_admin] =>    // Not branch admin
            [is_customer_employee] =>        // Not employee
            [access_type] => none            // ← NO ACCESS!
        )
)
```

**Root Cause**:
- CustomerModel::getUserRelation() hanya cek 4 tipe: admin, customer_admin, branch_admin, employee
- Platform roles tidak ter-detect karena tidak ada relasi di database
- Validator return `has_access = false` → DataTable filter WHERE 1=0 → No records

## Solution

Gunakan **hook filters** yang sudah ada di wp-customer untuk inject platform role access:

```php
// wp-customer/src/Validators/CustomerValidator.php (after TODO-2165)
return apply_filters('wp_customer_user_can_view_customer', false, $relation);
return apply_filters('wp_customer_user_can_edit_customer', false, $relation);
return apply_filters('wp_customer_user_can_delete_customer', false, $relation);
```

**Strategi**: Hook + Database + Cache (Hybrid Approach)

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│  User: platform_finance (ID: 243)                           │
│  Roles: platform_staff, platform_finance                    │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│  WP Customer: CustomerValidator::canView($relation)         │
│  - Check is_admin: FALSE                                    │
│  - Check is_customer_admin: FALSE                           │
│  - Apply filter: wp_customer_user_can_view_customer         │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│  WP App Core: Filter Implementation                         │
│  add_filter('wp_customer_user_can_view_customer', ...)      │
│                                                              │
│  1. Check cache: platform_access_{user_id}                  │
│  2. If no cache: Query database for platform roles          │
│  3. Match capabilities with platform permissions            │
│  4. Cache result for 5 minutes                              │
│  5. Return TRUE/FALSE                                        │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│  Result: has_access = TRUE                                  │
│  DataTable query: No WHERE 1=0 restriction                  │
│  User dapat melihat data customer                           │
└─────────────────────────────────────────────────────────────┘
```

## Implementation Plan

### Phase 1: Create Integration Class

**File**: `/wp-app-core/includes/class-wp-customer-integration.php`

```php
<?php
/**
 * WP Customer Integration Class
 *
 * @package     WP_App_Core
 * @version     1.0.4
 * @author      arisciwek
 *
 * Description: Extends WP Customer plugin functionality to support platform roles.
 *              Uses hook filters to grant access to platform staff.
 *
 * Changelog:
 * 1.0.4 - 2025-10-19 (TODO-1210)
 * - Initial implementation
 * - Added platform role access filters
 * - Added caching for performance
 */

namespace WPAppCore\Integrations;

class WPCustomerIntegration {

    private const CACHE_GROUP = 'wp_app_core_platform_access';
    private const CACHE_EXPIRY = 300; // 5 minutes

    public function __construct() {
        // Register filters after wp-customer is loaded
        add_action('plugins_loaded', [$this, 'registerFilters'], 20);
    }

    public function registerFilters(): void {
        // Only register if wp-customer is active
        if (!$this->isWPCustomerActive()) {
            return;
        }

        // Customer entity filters
        add_filter('wp_customer_user_can_view_customer', [$this, 'checkPlatformAccess'], 10, 2);
        add_filter('wp_customer_user_can_edit_customer', [$this, 'checkPlatformEditAccess'], 10, 2);
        add_filter('wp_customer_user_can_delete_customer', [$this, 'checkPlatformDeleteAccess'], 10, 2);

        // Future: Branch, Employee, Invoice filters
        // add_filter('wp_customer_user_can_view_branch', [$this, 'checkPlatformAccess'], 10, 2);
        // add_filter('wp_customer_user_can_view_employee', [$this, 'checkPlatformAccess'], 10, 2);
    }

    /**
     * Check if platform user has view access
     */
    public function checkPlatformAccess(bool $has_access, array $relation): bool {
        // If already has access, return true
        if ($has_access) {
            return true;
        }

        // Check if user has platform role with required capability
        return $this->hasPlatformCapability('view_customer_detail');
    }

    /**
     * Check if platform user has edit access
     */
    public function checkPlatformEditAccess(bool $has_access, array $relation): bool {
        if ($has_access) {
            return true;
        }

        return $this->hasPlatformCapability('edit_all_customers');
    }

    /**
     * Check if platform user has delete access
     */
    public function checkPlatformDeleteAccess(bool $has_access, array $relation): bool {
        if ($has_access) {
            return true;
        }

        return $this->hasPlatformCapability('delete_customer');
    }

    /**
     * Check if current user has platform capability (with caching)
     */
    private function hasPlatformCapability(string $capability): bool {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return false;
        }

        // Check cache first
        $cache_key = "user_{$user_id}_cap_{$capability}";
        $cached = wp_cache_get($cache_key, self::CACHE_GROUP);

        if ($cached !== false) {
            return (bool) $cached;
        }

        // Query: Does user have the capability?
        $has_capability = current_user_can($capability);

        // Additional check: Is user a platform role?
        if ($has_capability) {
            $has_capability = $this->isPlatformUser($user_id);
        }

        // Cache result
        wp_cache_set($cache_key, $has_capability, self::CACHE_GROUP, self::CACHE_EXPIRY);

        return $has_capability;
    }

    /**
     * Check if user has platform role
     */
    private function isPlatformUser(int $user_id): bool {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        // Platform roles pattern: platform_*
        $platform_roles = array_filter($user->roles, function($role) {
            return strpos($role, 'platform_') === 0;
        });

        return !empty($platform_roles);
    }

    /**
     * Check if WP Customer plugin is active
     */
    private function isWPCustomerActive(): bool {
        return class_exists('WPCustomer\\Models\\Customer\\CustomerModel');
    }

    /**
     * Clear cache for specific user
     */
    public static function clearUserCache(int $user_id): void {
        // Clear all capability caches for this user
        $capabilities = [
            'view_customer_detail',
            'edit_all_customers',
            'delete_customer',
            'view_customer_list',
            // Add more as needed
        ];

        foreach ($capabilities as $cap) {
            wp_cache_delete("user_{$user_id}_cap_{$cap}", self::CACHE_GROUP);
        }
    }
}
```

### Phase 2: Register Integration in wp-app-core.php

**File**: `/wp-app-core/wp-app-core.php`

```php
// Add to load_dependencies() method
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-customer-integration.php';

// Add to define_hooks() or similar
$wp_customer_integration = new \WPAppCore\Integrations\WPCustomerIntegration();
```

### Phase 3: Testing

**Test Script**: `/wp-app-core/test-platform-customer-access.php`

```php
<?php
/**
 * Test platform role access to WP Customer
 */

require_once __DIR__ . '/../../../wp-load.php';

echo "=== Platform Role Customer Access Test ===\n\n";

// Test users
$test_users = [
    243 => 'benny_clara (platform_finance)',
    232 => 'edwin_felix (platform_admin)'
];

foreach ($test_users as $user_id => $user_desc) {
    echo "Testing: $user_desc\n";

    wp_set_current_user($user_id);

    // Test capabilities
    $capabilities = [
        'view_customer_list',
        'view_customer_detail',
        'edit_all_customers',
        'delete_customer'
    ];

    foreach ($capabilities as $cap) {
        $has_cap = current_user_can($cap);
        echo "  " . ($has_cap ? '✓' : '✗') . " $cap\n";
    }

    // Test validator access (requires wp-customer)
    if (class_exists('WPCustomer\\Validators\\CustomerValidator')) {
        $validator = new \WPCustomer\Validators\CustomerValidator();
        $access = $validator->validateAccess(0);

        echo "  Access Type: " . $access['access_type'] . "\n";
        echo "  Has Access: " . ($access['has_access'] ? 'YES' : 'NO') . "\n";
    }

    echo "\n";
}

echo "=== Test Complete ===\n";
```

## Expected Results

### Before Implementation:
```
platform_finance user:
  ✓ view_customer_list (capability exists)
  ✓ view_customer_detail (capability exists)
  Access Type: none
  Has Access: NO              ← PROBLEM
  DataTable: Empty (0 records)
```

### After Implementation:
```
platform_finance user:
  ✓ view_customer_list (capability exists)
  ✓ view_customer_detail (capability exists)
  Access Type: platform       ← NEW!
  Has Access: YES             ← FIXED!
  DataTable: Shows all records
```

## Files to Create/Modify

### Create:
1. `/wp-app-core/includes/class-wp-customer-integration.php` (NEW)
2. `/wp-app-core/test-platform-customer-access.php` (NEW - temporary)
3. `/wp-app-core/TODO/TODO-1210-implement-platform-role-access-filters.md` (this file)

### Modify:
1. `/wp-app-core/wp-app-core.php` - Add integration class loading
2. `/wp-app-core/includes/class-dependencies.php` - Register integration (if using loader pattern)
3. `/wp-app-core/TODO.md` - Add TODO-1210 entry

## Performance Considerations

**Caching Strategy**:
```
Level 1: Object Cache (wp_cache)
- Key: user_{id}_cap_{capability}
- Duration: 5 minutes
- Scope: Per-request + persistent (if object cache enabled)

Level 2: Database Query
- Only executed on cache miss
- Simple current_user_can() check (already optimized by WordPress)
- User role check (in-memory, no DB query)
```

**Estimated Performance**:
- Cache hit: ~0.0001s (instant)
- Cache miss: ~0.001s (1ms for capability check)
- Impact: Minimal (<1ms per request after cache warm-up)

## Security Considerations

1. ✅ **Capability-based**: Uses WordPress capability system
2. ✅ **Role verification**: Double-checks user has platform_* role
3. ✅ **No bypass**: Capabilities must exist (set in PlatformPermissionModel)
4. ✅ **Cache invalidation**: Cache cleared when user roles change

## Migration Path

1. **Phase 1** (TODO-1210): Customer entity only
2. **Phase 2** (Future): Branch entity
3. **Phase 3** (Future): Employee entity
4. **Phase 4** (Future): Invoice entity

## Dependencies

**Requires**:
- ✅ wp-customer plugin active
- ✅ wp-customer TODO-2165 completed (new hook names)
- ✅ Platform roles created (wp-app-core TODO-1208)
- ✅ Platform capabilities set (wp-app-core TODO-1209)

**Blocks**:
- Platform users cannot access customer data until this is implemented

## Related TODOs

- **wp-customer TODO-2165**: Refactor hook naming convention
- **wp-app-core TODO-1208**: Base role system (completed)
- **wp-app-core TODO-1209**: WP Customer capabilities registration (completed)

## Testing Checklist

- [ ] platform_finance can view customer list
- [ ] platform_finance can view customer detail
- [ ] platform_admin can edit customers
- [ ] platform_super_admin can delete customers
- [ ] platform_viewer has read-only access
- [ ] Cache works correctly (check cache hit/miss logs)
- [ ] Non-platform users not affected
- [ ] Customer/branch/employee users still work as before

## Implementation Steps

1. [x] Create TODO documentation
2. [ ] Create WPCustomerIntegration class
3. [ ] Register integration in wp-app-core.php
4. [ ] Test with platform_finance user
5. [ ] Verify DataTable shows records
6. [ ] Verify cache is working
7. [ ] Create test script
8. [ ] Update TODO.md
9. [ ] Clean up test script

## Notes

- This is a **clean extension** via hooks - no modification to wp-customer
- Future: Consider creating base class for all plugin integrations
- Future: Add admin UI to configure which platform roles have access
- This pattern can be reused for other plugins (wp-agency, wp-service, etc.)
