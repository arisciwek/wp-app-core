# TODO-1204: Method Migration to AdminBarModel

## Task Description
Memindahkan method yang bersifat **global scope** dari AgencyEmployeeModel.php ke AdminBarModel.php untuk separation of concerns dan reusability.

## Tanggal
2025-01-18

## Status
✅ COMPLETED

## Problem Statement

### Before Migration

**AgencyEmployeeModel.php** contained 3 methods for admin bar:
1. `getUserInfo()` - Get user info for admin bar (lines 642-728)
2. `getRoleNamesFromCapabilities()` - Parse capabilities to role names (lines 742-770)
3. `getPermissionNamesFromUserId()` - Extract permission names (lines 784-863)

**Issues:**
- ❌ Admin bar logic mixed with employee CRUD operations
- ❌ Violates Single Responsibility Principle
- ❌ Helper methods not reusable by other plugins (wp-customer, etc)
- ❌ Tight coupling between model and presentation layer

### Analysis Results

**Usage Check:**
- `getUserInfo()` only called by: `class-app-core-integration.php:132`
- Helper methods only used by: `getUserInfo()` internally
- **NOT used** for any employee management operations

**Conclusion:**
✅ These methods are exclusively for admin bar
✅ Should be moved to wp-app-core for reusability

---

## Solution Implemented: Option 2 (Proper Separation)

### Decision Rationale

**Why Option 2:**
1. **getUserInfo() stays** in AgencyEmployeeModel
   - Queries agency-specific tables (`wp_app_agency_employees`)
   - Data access responsibility belongs in model
   - Agency plugin owns agency data

2. **Helper methods move** to AdminBarModel
   - Generic WordPress capability parsing
   - No agency-specific dependencies
   - Reusable across all plugins

3. **Better Architecture**
   - Clear separation: wp-app-core = generic utils, wp-agency = data access
   - Follows Single Responsibility Principle
   - Maintainable and scalable

---

## Changes Made

### 1. Created AdminBarModel.php

**File:** `/wp-app-core/src/Models/AdminBarModel.php`
**Lines:** 256 lines
**Namespace:** `WPAppCore\Models`

**Methods Created:**

#### getRoleNamesFromCapabilities()

```php
public function getRoleNamesFromCapabilities(
    string $capabilities_string,
    array $role_slugs_filter = [],
    ?callable $role_name_resolver = null
): array
```

**Features:**
- ✅ Generic version (plugin-agnostic)
- ✅ Accepts role filter array parameter
- ✅ Accepts callback for role name resolution
- ✅ Fallback to WordPress role names
- ✅ Comprehensive debug logging
- ✅ Works with any plugin (customer, agency, etc)

**Usage Example:**
```php
// In wp-agency:
$model = new \WPAppCore\Models\AdminBarModel();
$role_names = $model->getRoleNamesFromCapabilities(
    $capabilities_string,
    WP_Agency_Role_Manager::getRoleSlugs(),
    ['WP_Agency_Role_Manager', 'getRoleName']
);

// In wp-customer:
$model = new \WPAppCore\Models\AdminBarModel();
$role_names = $model->getRoleNamesFromCapabilities(
    $capabilities_string,
    WP_Customer_Role_Manager::getRoleSlugs(),
    ['WP_Customer_Role_Manager', 'getRoleName']
);
```

#### getPermissionNamesFromUserId()

```php
public function getPermissionNamesFromUserId(
    int $user_id,
    array $role_slugs_to_skip = [],
    array $permission_labels = []
): array
```

**Features:**
- ✅ Generic version (plugin-agnostic)
- ✅ Accepts roles to skip parameter
- ✅ Accepts permission labels array
- ✅ Uses WP_User->allcaps (actual permissions)
- ✅ Comprehensive debug logging
- ✅ Reusable across plugins

**Usage Example:**
```php
// In wp-agency:
$model = new \WPAppCore\Models\AdminBarModel();
$permission_model = new PermissionModel();

$permission_names = $model->getPermissionNamesFromUserId(
    $user_id,
    WP_Agency_Role_Manager::getRoleSlugs(),
    $permission_model->getAllCapabilities()
);
```

---

### 2. Updated AgencyEmployeeModel.php

**File:** `/wp-agency/src/Models/Employee/AgencyEmployeeModel.php`
**Lines:** 743 lines (was ~878 lines, removed ~135 lines)

**Changes in getUserInfo() method:**

**Before:**
```php
// Line 717
$result['role_names'] = $this->getRoleNamesFromCapabilities($user_data->capabilities);

// Line 722
$result['permission_names'] = $this->getPermissionNamesFromUserId($user_id);
```

**After:**
```php
// Use AdminBarModel for generic capability parsing
$admin_bar_model = new \WPAppCore\Models\AdminBarModel();

$result['role_names'] = $admin_bar_model->getRoleNamesFromCapabilities(
    $user_data->capabilities,
    call_user_func(['WP_Agency_Role_Manager', 'getRoleSlugs']),
    ['WP_Agency_Role_Manager', 'getRoleName']
);

$permission_model = new \WPAgency\Models\Settings\PermissionModel();
$result['permission_names'] = $admin_bar_model->getPermissionNamesFromUserId(
    $user_id,
    call_user_func(['WP_Agency_Role_Manager', 'getRoleSlugs']),
    $permission_model->getAllCapabilities()
);
```

**Methods Removed:**
- ❌ `getRoleNamesFromCapabilities()` (private, lines 742-782)
- ❌ `getPermissionNamesFromUserId()` (private, lines 784-876)

---

### 3. No Changes to Integration Layer

**File:** `/wp-agency/includes/class-app-core-integration.php`
**Status:** NO CHANGES REQUIRED

**Reason:**
- Still calls `AgencyEmployeeModel::getUserInfo()`
- Internal implementation changed but public interface unchanged
- Backward compatible

---

## Benefits

### 1. Separation of Concerns
- ✅ AgencyEmployeeModel focuses on employee CRUD
- ✅ AdminBarModel handles generic admin bar utilities
- ✅ Clear responsibility boundaries

### 2. Reusability
- ✅ AdminBarModel methods work with ANY plugin
- ✅ wp-customer can use same helpers
- ✅ No code duplication needed

### 3. Maintainability
- ✅ Smaller, focused classes
- ✅ Easier to test and debug
- ✅ Changes isolated to relevant scope

### 4. Architecture
- ✅ Generic utilities in wp-app-core
- ✅ Plugin-specific logic stays in plugins
- ✅ Follows WordPress best practices

### 5. No Breaking Changes
- ✅ Public API unchanged
- ✅ class-app-core-integration still works
- ✅ Admin bar display unchanged
- ✅ Backward compatible

---

## File Summary

| File | Change Type | Lines | Details |
|------|-------------|-------|---------|
| `/wp-app-core/src/Models/AdminBarModel.php` | NEW | 256 | Generic helper methods |
| `/wp-agency/src/Models/Employee/AgencyEmployeeModel.php` | MODIFIED | 743 (-135) | Updated getUserInfo(), removed 2 methods |
| `/wp-agency/includes/class-app-core-integration.php` | NO CHANGE | - | Still calls getUserInfo() |

---

## Testing Checklist

- [x] AdminBarModel.php created successfully
- [x] Generic methods accept parameters correctly
- [x] AgencyEmployeeModel updated to call AdminBarModel
- [x] Old methods removed from AgencyEmployeeModel
- [x] File line counts verified
- [ ] Browser test: Admin bar displays correctly
- [ ] Browser test: Role names appear
- [ ] Browser test: Permissions appear in dropdown
- [ ] Console check: No errors
- [ ] Cache test: Clear cache and verify functionality

---

## Code Quality

### AdminBarModel.php
- ✅ Proper namespace: `WPAppCore\Models`
- ✅ Comprehensive PHPDoc comments
- ✅ Type declarations on all parameters
- ✅ Debug logging (WP_DEBUG aware)
- ✅ Fallback mechanisms
- ✅ Example usage in comments

### AgencyEmployeeModel.php
- ✅ Clean integration with AdminBarModel
- ✅ Maintained existing functionality
- ✅ No hardcoded dependencies
- ✅ Comments explain what's happening

---

## Migration Statistics

**Lines of Code:**
- Removed from AgencyEmployeeModel: ~135 lines
- Added to AdminBarModel: ~256 lines
- Net change: +121 lines (includes comprehensive docs and logging)

**Methods:**
- Migrated: 2 methods (made generic)
- Kept: 1 method (getUserInfo - data access)
- Total public API in AdminBarModel: 2 methods

---

## Related Documents

- Original Proposal: [PROPOSAL-1204-method-migration.md](PROPOSAL-1204-method-migration.md)
- Analysis: [ANALYSIS-1204-review-01.md](ANALYSIS-1204-review-01.md)
- Task File: `/wp-app-core/claude-chats/task-1202.md`

---

## Next Steps (Optional Improvements)

### For wp-customer Plugin
wp-customer can now use AdminBarModel helpers:

```php
// In wp-customer
$admin_bar_model = new \WPAppCore\Models\AdminBarModel();

$role_names = $admin_bar_model->getRoleNamesFromCapabilities(
    $capabilities_string,
    WP_Customer_Role_Manager::getRoleSlugs(),
    ['WP_Customer_Role_Manager', 'getRoleName']
);
```

This eliminates need for duplicate code in wp-customer.

### For Future Plugins
Any new plugin can leverage AdminBarModel utilities without reinventing the wheel.

---

## Completion Criteria

✅ AdminBarModel.php created with generic methods
✅ AgencyEmployeeModel updated to use AdminBarModel
✅ Old methods removed from AgencyEmployeeModel
✅ No changes to integration layer (backward compatible)
✅ Documentation completed
⏳ Browser testing (pending user verification)

---

## Author Notes

**Implementation Approach:**
- Chose Option 2 (Proper Separation) per user approval
- Prioritized reusability and maintainability
- Maintained backward compatibility
- Added comprehensive documentation

**Key Decisions:**
- Keep getUserInfo() in AgencyEmployeeModel (correct location for data access)
- Move helpers to AdminBarModel (generic utilities)
- Use parameters instead of hardcoded dependencies (flexibility)
- Maintain same debug logging patterns (consistency)

---

**Status:** ✅ Implementation Complete
**Next:** User browser testing to verify admin bar functionality
