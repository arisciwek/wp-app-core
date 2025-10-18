# ANALYSIS-1204 Review-01: Remove Admin Bar Methods from AgencyEmployeeModel

## Review-01 Instruction
> "query disini sudah bisa mendapatkan role dari user"
> "hapus dulu method method yang tidak digunakan lagi untuk keperluan admin bar dari AgencyEmployeeModel.php"

---

## Current Situation

### Methods in AgencyEmployeeModel.php (Admin Bar Related)

| Method | Lines | Visibility | Used By | Purpose |
|--------|-------|------------|---------|---------|
| `getUserInfo()` | 642-728 | public | class-app-core-integration.php | Get user info for admin bar |
| `getRoleNamesFromCapabilities()` | 742-770 | private | getUserInfo() only | Extract role names from capabilities |
| `getPermissionNamesFromUserId()` | 784-863 | private | getUserInfo() only | Extract permission names from user |

### Usage Analysis

**getUserInfo() is ONLY used in:**
- `/wp-agency/includes/class-app-core-integration.php:132`
  ```php
  $employee_model = new \WPAgency\Models\Employee\AgencyEmployeeModel();
  $result = $employee_model->getUserInfo($user_id);
  ```

**Helper methods are ONLY used by getUserInfo():**
- Line 717: `$result['role_names'] = $this->getRoleNamesFromCapabilities(...)`
- Line 722: `$result['permission_names'] = $this->getPermissionNamesFromUserId(...)`

**Conclusion:**
✅ These 3 methods are **ONLY for admin bar**
✅ **NOT used** for any employee management operations
✅ Should be **moved** to AdminBarModel.php (wp-app-core)

---

## Problem

### Why These Methods Don't Belong in AgencyEmployeeModel?

1. **Wrong Responsibility**
   - AgencyEmployeeModel should manage employee CRUD operations
   - Admin bar display logic is NOT employee management
   - Violates Single Responsibility Principle

2. **Not Reusable**
   - getUserInfo() is agency-specific (queries agency tables)
   - getRoleNamesFromCapabilities() uses WP_Agency_Role_Manager
   - getPermissionNamesFromUserId() uses agency PermissionModel
   - Cannot be used by wp-customer or other plugins

3. **Tight Coupling**
   - AgencyEmployeeModel tightly coupled to admin bar needs
   - Makes model harder to maintain
   - Mixes presentation layer with data layer

---

## Solution: Move to AdminBarModel.php

### Approach

**Move ALL 3 methods to wp-app-core:**

1. **Create AdminBarModel.php** in wp-app-core
   - Location: `/wp-app-core/src/Models/AdminBarModel.php`

2. **Move methods with refactoring:**
   - `getUserInfo()` → Make generic or keep agency-specific wrapper
   - `getRoleNamesFromCapabilities()` → Refactor to generic
   - `getPermissionNamesFromUserId()` → Refactor to generic

3. **Update class-app-core-integration.php:**
   - Change from AgencyEmployeeModel to AdminBarModel
   - Or keep calling pattern but delegate internally

---

## Proposed Implementation

### Option 1: Keep Agency-Specific in AdminBarModel (Quick)

**AdminBarModel.php:**
```php
<?php
namespace WPAppCore\Models;

class AdminBarModel {

    /**
     * Get agency user info for admin bar
     * NOTE: This is agency-specific, customer plugin has its own implementation
     */
    public function getAgencyUserInfo(int $user_id): ?array {
        // Exact code from AgencyEmployeeModel::getUserInfo()
        // ...
    }

    private function getRoleNamesFromCapabilities(string $capabilities_string): array {
        // Exact code from AgencyEmployeeModel
        // Still uses WP_Agency_Role_Manager
    }

    private function getPermissionNamesFromUserId(int $user_id): array {
        // Exact code from AgencyEmployeeModel
        // Still uses agency PermissionModel
    }
}
```

**Update class-app-core-integration.php:**
```php
public static function get_user_info($user_id) {
    $admin_bar_model = new \WPAppCore\Models\AdminBarModel();
    $result = $admin_bar_model->getAgencyUserInfo($user_id);
    return $result;
}
```

**Pros:**
- ✅ Quick and simple
- ✅ No changes to method logic
- ✅ Removes methods from AgencyEmployeeModel (cleaner)

**Cons:**
- ❌ Still agency-specific
- ❌ Not reusable by wp-customer
- ❌ AdminBarModel has agency coupling

---

### Option 2: Generic Helper Methods (Better Separation)

**AdminBarModel.php:**
```php
<?php
namespace WPAppCore\Models;

class AdminBarModel {

    /**
     * Generic: Extract role names from capabilities string
     */
    public function getRoleNamesFromCapabilities(
        string $capabilities_string,
        array $role_slugs_filter = [],
        ?callable $role_name_resolver = null
    ): array {
        // Generic implementation
    }

    /**
     * Generic: Extract permission names from user capabilities
     */
    public function getPermissionNamesFromUserId(
        int $user_id,
        array $role_slugs_to_skip = [],
        array $permission_labels = []
    ): array {
        // Generic implementation
    }
}
```

**Keep getUserInfo() in AgencyEmployeeModel:**
- getUserInfo() queries agency-specific tables
- It uses AdminBarModel helpers for role/permission parsing
- Stays in AgencyEmployeeModel since it's agency data access

**Update AgencyEmployeeModel:**
```php
public function getUserInfo(int $user_id): ?array {
    // ... query agency tables ...

    // Use AdminBarModel for generic parsing
    $admin_bar_model = new \WPAppCore\Models\AdminBarModel();

    $result['role_names'] = $admin_bar_model->getRoleNamesFromCapabilities(
        $user_data->capabilities,
        WP_Agency_Role_Manager::getRoleSlugs(),
        ['WP_Agency_Role_Manager', 'getRoleName']
    );

    $result['permission_names'] = $admin_bar_model->getPermissionNamesFromUserId(
        $user_id,
        WP_Agency_Role_Manager::getRoleSlugs(),
        $permission_model->getAllCapabilities()
    );

    return $result;
}
```

**Pros:**
- ✅ Generic helpers in AdminBarModel
- ✅ Reusable by wp-customer
- ✅ getUserInfo() stays where it belongs (data access layer)
- ✅ Better separation of concerns

**Cons:**
- ⚠️ More work (refactor method signatures)
- ⚠️ getUserInfo() still in AgencyEmployeeModel (but that's OK, it's data access)

---

## Recommendation

**I recommend Option 2:**

### Reasoning:

1. **getUserInfo() SHOULD stay in AgencyEmployeeModel**
   - It queries `wp_app_agency_employees` table
   - It's data access, that's what models do
   - Agency-specific query, belongs in agency plugin

2. **Helper methods SHOULD move to AdminBarModel**
   - getRoleNamesFromCapabilities() is generic WordPress capability parsing
   - getPermissionNamesFromUserId() is generic permission extraction
   - Both can be made plugin-agnostic

3. **Better Architecture**
   - AdminBarModel = generic utilities for admin bar
   - AgencyEmployeeModel = agency data access
   - Clear separation of concerns

---

## Implementation Plan

### Step 1: Create AdminBarModel.php

File: `/wp-app-core/src/Models/AdminBarModel.php`

```php
<?php
namespace WPAppCore\Models;

class AdminBarModel {

    public function getRoleNamesFromCapabilities(...) { }
    public function getPermissionNamesFromUserId(...) { }
}
```

### Step 2: Update AgencyEmployeeModel.php

- Keep getUserInfo() method
- Remove getRoleNamesFromCapabilities() method
- Remove getPermissionNamesFromUserId() method
- Update getUserInfo() to call AdminBarModel helpers

### Step 3: No Changes to class-app-core-integration.php

- Still calls AgencyEmployeeModel::getUserInfo()
- Internal implementation changed but interface same

### Step 4: Test

- Verify admin bar still displays correctly
- Check role names appear
- Check permissions appear in dropdown

---

## Files to Modify

| File | Action |
|------|--------|
| `/wp-app-core/src/Models/AdminBarModel.php` | CREATE with 2 generic methods |
| `/wp-agency/src/Models/Employee/AgencyEmployeeModel.php` | REMOVE 2 private methods, UPDATE getUserInfo() |
| `/wp-agency/includes/class-app-core-integration.php` | NO CHANGE (keeps calling getUserInfo()) |

---

## Summary

✅ **Keep:** getUserInfo() in AgencyEmployeeModel (data access)
🚚 **Move:** Helper methods to AdminBarModel (generic utilities)
♻️ **Refactor:** Make helpers accept parameters (no hardcoded dependencies)

This achieves:
- Clean separation of concerns
- Reusable utilities in wp-app-core
- Agency data access stays in wp-agency
- No breaking changes to integration layer

---

## Next Steps

Waiting for your approval on **Option 2** approach before implementing.

**Questions:**
1. Option 1 (quick, agency-specific) or Option 2 (proper separation)?
2. Should I proceed with implementation?

