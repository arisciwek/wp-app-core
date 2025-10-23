# TODO-2172: Fix resetToDefault() Removing Agency Capabilities

**Status**: ✅ COMPLETED
**Date**: 2025-10-22
**Plugin**: wp-app-core
**Priority**: High (Regression Bug)
**Related**: wp-customer Task-2172, wp-agency TODO-2064

## Summary

Fixed critical regression bug in `PlatformPermissionModel::resetToDefault()` that was removing wp-customer capabilities from agency roles. The method was incorrectly removing capabilities from ALL WordPress roles instead of only platform roles.

## Problem

### User Report
Platform and agency users could not access wp-customer menus after plugin reactivation.

### Initial Investigation
```
User asked: "apakah capabilities hilang dari database setelah reaktivasi plugin berulang kali?"

Test Results:
- platform_finance: ✓ Has 12 wp-customer capabilities
- agency_admin_dinas: ✗ Has 0 total capabilities
- agency (base role): ✗ Has 0 wp-customer capabilities
```

### Root Cause Discovery

**Bug Location**: `/wp-app-core/src/Models/Settings/PlatformPermissionModel.php:420-467`

```php
// BUGGY CODE (BEFORE FIX):
public function resetToDefault(): bool {
    foreach (get_editable_roles() as $role_name => $role_info) {
        $role = get_role($role_name);
        if (!$role) continue;

        // ❌ REMOVES FROM ALL ROLES (including agency!)
        foreach (array_keys($this->available_capabilities) as $cap) {
            $role->remove_cap($cap);
        }

        // Only adds back to administrator + platform roles
        if ($role_name === 'administrator') {
            // ... add all caps
        }
        if (\WP_App_Core_Role_Manager::isPluginRole($role_name)) {
            // ... add platform caps
        }
        // ❌ Agency roles DON'T get caps back!
    }
}
```

**Impact**:
1. `available_capabilities` includes wp-customer capabilities (view_customer_list, etc.)
2. Method removes these from **ALL** roles (customer, agency, etc.)
3. Only restores to platform roles + administrator
4. **Agency roles lose wp-customer access** ❌

### Why This Happened

The `resetToDefault()` method was designed to:
- Remove old/stale capabilities from platform roles
- Reapply fresh default capabilities

But it incorrectly assumed it should clean ALL roles, not just platform roles.

### Evidence - Test Sequence

```bash
# Test 1: Restore agency caps
wp eval 'agency_model->addCapabilities()'
# Result: agency has 6 customer caps ✓

# Test 2: Reset platform caps
wp eval 'platform_model->resetToDefault()'
# Result: agency has 0 customer caps ✗ (BUG!)

# Expected: agency should still have 6 caps
```

## Solution

**Fix**: Only remove capabilities from roles that will be re-assigned (platform + admin)

### Code Changes

**File**: `/wp-app-core/src/Models/Settings/PlatformPermissionModel.php`
**Lines**: 420-477
**Method**: `resetToDefault()`

```php
// FIXED CODE (Task-2172):
public function resetToDefault(): bool {
    try {
        require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-role-manager.php';

        foreach (get_editable_roles() as $role_name => $role_info) {
            $role = get_role($role_name);
            if (!$role) continue;

            // ✓ FIX: Only process platform roles + administrator
            $is_platform_role = \WP_App_Core_Role_Manager::isPluginRole($role_name);
            $is_admin = $role_name === 'administrator';

            if (!$is_platform_role && !$is_admin) {
                // ✓ Skip non-platform roles - don't touch their capabilities
                continue;
            }

            // ✓ Now only removes from platform roles + admin
            foreach (array_keys($this->available_capabilities) as $cap) {
                $role->remove_cap($cap);
            }

            // ... rest of the method (unchanged)
        }
    }
}
```

### Changes Made

1. **Added role filtering** (lines 432-438):
   ```php
   $is_platform_role = \WP_App_Core_Role_Manager::isPluginRole($role_name);
   $is_admin = $role_name === 'administrator';

   if (!$is_platform_role && !$is_admin) {
       continue; // Skip agency/customer/other roles
   }
   ```

2. **Updated comment** to clarify intent

3. **No breaking changes** - platform roles still work exactly the same

## Test Results

### Before Fix
```bash
=== TESTING BEFORE FIX ===
1. Restore agency capabilities
   Agency customer caps: 6

2. Reset platform capabilities
   Agency customer caps: 0  ✗ REMOVED!

3. Platform capabilities: 12  ✓ OK
```

### After Fix
```bash
=== TESTING AFTER FIX ===
1. Restore agency capabilities
   Agency customer caps BEFORE platform reset: 6

2. Reset platform capabilities
   Agency customer caps AFTER platform reset: 6  ✓ PRESERVED!
   Caps: view_customer_list, view_customer_detail,
         view_customer_branch_list, view_customer_branch_detail,
         view_customer_employee_list, view_customer_employee_detail

3. Platform capabilities: 12  ✓ OK
```

**Result**: ✅ Both platform AND agency capabilities work correctly!

## Affected Roles

### Now Protected (not touched by resetToDefault):
- **Agency roles**: agency, agency_admin_dinas, agency_pengawas, etc.
- **Customer roles**: customer, customer_admin, customer_branch_admin, customer_employee
- **Any other plugin roles**

### Still Managed by resetToDefault:
- **Platform roles**: platform_finance, platform_admin, platform_super_admin, etc.
- **WordPress admin**: administrator

## Integration Points

This fix ensures compatibility with:

1. **wp-agency**:
   - `/wp-agency/src/Models/Settings/PermissionModel.php` - addCapabilities()
   - `/wp-agency/includes/class-wp-customer-integration.php` - agency access filters

2. **wp-customer**:
   - Customer roles and capabilities remain intact
   - No interference with customer permission management

3. **wp-app-core**:
   - Platform roles still get proper capability management
   - No changes to platform role behavior

## Why Cache Flush Didn't Help

User asked: "apakah wp_cache_flush() tidak berfungsi?"

**Answer**: Cache flush WAS working correctly! The issue was NOT cache-related:
- `wp_cache_flush()` clears runtime cache ✓
- But capabilities were **actually removed from database** ✗
- `resetToDefault()` was actively deleting caps from `wp_user_roles` option

**Evidence**:
```bash
# Direct database query (bypasses cache)
SELECT option_value FROM wp_options WHERE option_name = 'wp_user_roles'

# Result: agency role has 0 customer caps in database
# Proof: Not a cache issue, caps were actually deleted!
```

## Prevention

### For wp-app-core Developers

**Rule**: `resetToDefault()` should ONLY manage roles owned by the plugin.

**Pattern**:
```php
// ✓ CORRECT: Filter roles first
if (!$is_my_plugin_role && !$is_admin) {
    continue; // Don't touch other plugins' roles
}

// ✗ WRONG: Process all roles
foreach (get_editable_roles() as $role) {
    // This affects ALL plugins!
}
```

### For Other Plugins

If you implement similar `resetToDefault()` logic, follow this pattern:
1. Check if role belongs to your plugin
2. Skip roles from other plugins
3. Only administrator should get capabilities from all plugins

## Related Files

**Modified**:
- `/wp-app-core/src/Models/Settings/PlatformPermissionModel.php` (lines 420-477)

**Verified Working**:
- `/wp-agency/src/Models/Settings/PermissionModel.php` (addCapabilities)
- `/wp-agency/includes/class-wp-customer-integration.php` (access filters)
- `/wp-app-core/includes/class-wp-customer-integration.php` (platform filters)

**Documentation**:
- `/wp-app-core/TODO/TODO-1210-implement-platform-role-access-filters.md`
- `/wp-agency/TODO/TODO-1201-wp-app-core-integration.md`
- `/wp-agency/TODO/TODO-2064-wp-customer-capabilities.md`

## Testing Checklist

- [x] Agency base role retains wp-customer caps after platform reset
- [x] Platform roles still get correct caps from resetToDefault()
- [x] Customer roles not affected
- [x] Administrator still gets all caps
- [x] Cache flush works (was never the issue)
- [x] Database verification - caps actually persisted

## Lessons Learned

1. **Scope Isolation**: Each plugin's permission manager should only manage its own roles
2. **Database vs Cache**: Always verify in database when debugging missing capabilities
3. **Defensive Coding**: Check role ownership before modifying capabilities
4. **Testing Cross-Plugin**: Test interactions between multiple permission systems

## Notes

- **No migration needed** - fix is forward-compatible
- **One-time restore**: Admin may need to restore agency caps once: `wp eval '$model = new \WPAgency\Models\Settings\PermissionModel(); $model->addCapabilities();'`
- **Future-proof**: Works with any number of plugins adding capabilities

---

**Related TODOs**:
- wp-customer Task-2172 (original issue report)
- wp-app-core TODO-1210 (platform access filters)
- wp-agency TODO-2064 (wp-customer capabilities)
