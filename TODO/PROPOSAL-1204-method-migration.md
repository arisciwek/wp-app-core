# PROPOSAL-1204: Method Migration Analysis

## Task Description
Memindahkan method yang bersifat **global scope** dari AgencyEmployeeModel.php ke AdminBarModel.php.
Method yang bersifat **plugin scope** tetap di AgencyEmployeeModel.php.

---

## Files Involved
- **Source:** `/wp-agency/src/Models/Employee/AgencyEmployeeModel.php`
- **Target:** `/wp-app-core/src/Models/AdminBarModel.php` (currently empty)

---

## Method Analysis

### 🔴 Methods yang TIDAK AKAN dipindah (Plugin Scope)

Methods ini **TETAP** di AgencyEmployeeModel.php karena spesifik untuk wp-agency:

| No | Method | Lines | Reason |
|----|--------|-------|---------|
| 1 | `create()` | 46-92 | CRUD untuk `wp_app_agency_employees` table |
| 2 | `find()` | 94-123 | Query agency employees dengan JOIN agency/division |
| 3 | `update()` | 125-192 | Update agency employee data |
| 4 | `delete()` | 194-230 | Delete agency employee |
| 5 | `existsByEmail()` | 232-265 | Check email di agency employees table |
| 6 | `getDataTableData()` | 267-378 | DataTable untuk agency employees |
| 7 | `getTotalCount()` | 383-417 | Count agency employees |
| 8 | `getByDivision()` | 422-447 | Get employees by division (agency-specific) |
| 9 | `isValidStatus()` | 450-452 | Validate agency employee status |
| 10 | `changeStatus()` | 455-496 | Change agency employee status |
| 11 | `getInBatches()` | 502-531 | Batch process agency employees |
| 12 | `bulkUpdate()` | 537-610 | Bulk update agency employees |
| 13 | `invalidateEmployeeCache()` | 612-627 | Invalidate agency-specific cache |
| 14 | `getUserInfo()` | 642-728 | **BORDERLINE** - Gets agency employee info |

---

### 🟡 BORDERLINE: getUserInfo()

**Location:** Lines 642-728

**Current Implementation:**
```php
public function getUserInfo(int $user_id): ?array
```

**Why Borderline:**
- ✅ Used by admin bar (generic purpose)
- ❌ Query menggunakan agency-specific tables (`wp_app_agency_employees`, `wp_app_agency_divisions`, etc)
- ❌ Returns agency-specific data (division, jurisdiction, agency)

**Recommendation:**
- **TETAP di AgencyEmployeeModel.php**
- Sudah digunakan oleh `class-app-core-integration.php` untuk call agency data
- Ini adalah plugin-specific implementation dari generic interface

---

### ✅ Methods yang AKAN dipindah (Global Scope)

Methods ini **PINDAH** ke AdminBarModel.php karena bersifat generic:

#### 1. `getRoleNamesFromCapabilities()`

**Location:** Lines 742-770 (Private method)

**Current Signature:**
```php
private function getRoleNamesFromCapabilities(string $capabilities_string): array
```

**Purpose:**
- Parse serialized WordPress capabilities string
- Extract role display names
- Generic WordPress capability parsing

**Problem dengan Implementation Sekarang:**
```php
// Line 754: Agency-specific!
$agency_role_slugs = call_user_func(['WP_Agency_Role_Manager', 'getRoleSlugs']);

// Line 761: Agency-specific!
$role_name = call_user_func(['WP_Agency_Role_Manager', 'getRoleName'], $role_slug);
```

**❗ISSUE:** Method ini masih **agency-specific** karena:
- Menggunakan `WP_Agency_Role_Manager`
- Filter hanya agency roles

**Proposed Solution:**
Buat versi **generic** di AdminBarModel.php yang bisa digunakan semua plugin:

```php
/**
 * Extract role names from serialized capabilities string (GENERIC version)
 *
 * @param string $capabilities_string Serialized capabilities from wp_usermeta
 * @param array $role_slugs_filter Optional array of role slugs to filter
 * @param callable $role_name_resolver Optional callback to get role display name
 * @return array Array of role display names
 */
public function getRoleNamesFromCapabilities(
    string $capabilities_string,
    array $role_slugs_filter = [],
    ?callable $role_name_resolver = null
): array
```

**Usage Example (in AgencyEmployeeModel):**
```php
// In wp-agency
$admin_bar_model = new AdminBarModel();
$role_names = $admin_bar_model->getRoleNamesFromCapabilities(
    $capabilities_string,
    WP_Agency_Role_Manager::getRoleSlugs(),  // Filter
    ['WP_Agency_Role_Manager', 'getRoleName'] // Resolver
);
```

---

#### 2. `getPermissionNamesFromUserId()`

**Location:** Lines 784-863 (Private method)

**Current Signature:**
```php
private function getPermissionNamesFromUserId(int $user_id): array
```

**Purpose:**
- Extract permission names from WP_User capabilities
- Filter out roles and generic WordPress caps
- Return display names for permissions

**Problem dengan Implementation Sekarang:**
```php
// Line 812: Agency-specific!
$agency_role_slugs = call_user_func(['WP_Agency_Role_Manager', 'getRoleSlugs']);

// Line 815: Agency-specific!
$permission_model = new \WPAgency\Models\Settings\PermissionModel();
$permission_labels = $permission_model->getAllCapabilities();
```

**❗ISSUE:** Method ini masih **agency-specific** karena:
- Menggunakan `WP_Agency_Role_Manager`
- Menggunakan `PermissionModel` dari wp-agency
- Hardcoded agency-specific logic

**Proposed Solution:**
Buat versi **generic** di AdminBarModel.php:

```php
/**
 * Extract permission names from user's actual capabilities (GENERIC version)
 *
 * @param int $user_id WordPress user ID
 * @param array $role_slugs_to_skip Array of role slugs to skip (not permissions)
 * @param array $permission_labels Array of cap_slug => display_name mapping
 * @return array Array of permission display names
 */
public function getPermissionNamesFromUserId(
    int $user_id,
    array $role_slugs_to_skip = [],
    array $permission_labels = []
): array
```

**Usage Example (in AgencyEmployeeModel):**
```php
// In wp-agency
$admin_bar_model = new AdminBarModel();
$permission_model = new PermissionModel();

$permission_names = $admin_bar_model->getPermissionNamesFromUserId(
    $user_id,
    WP_Agency_Role_Manager::getRoleSlugs(),           // Skip these
    $permission_model->getAllCapabilities()           // Labels
);
```

---

## Summary of Methods to Move

| Method | From | To | Change Type |
|--------|------|----|----|
| `getRoleNamesFromCapabilities()` | AgencyEmployeeModel.php:742-770 | AdminBarModel.php | Refactor to generic + keep wrapper in agency |
| `getPermissionNamesFromUserId()` | AgencyEmployeeModel.php:784-863 | AdminBarModel.php | Refactor to generic + keep wrapper in agency |

---

## Proposed Approach

### Option A: Full Migration (Recommended)

1. **Create generic versions** in AdminBarModel.php
2. **Keep thin wrappers** in AgencyEmployeeModel.php that call AdminBarModel
3. **No breaking changes** - agency code still works

**Pros:**
- ✅ Truly reusable across plugins (customer, agency, future plugins)
- ✅ Single source of truth in wp-app-core
- ✅ No code duplication
- ✅ Agency methods still work (backward compatible)

**Cons:**
- ⚠️ Need to refactor method signatures (add parameters)
- ⚠️ Need to update calls in AgencyEmployeeModel

### Option B: Copy As-Is

1. **Copy methods exactly** to AdminBarModel.php
2. **Keep original** in AgencyEmployeeModel.php
3. **Duplicate code** in both places

**Pros:**
- ✅ Quick and easy
- ✅ No changes to existing code

**Cons:**
- ❌ Code duplication
- ❌ Methods in AdminBarModel still agency-specific (not truly global)
- ❌ Defeats purpose of "global scope"

---

## Recommendation

**✅ Go with Option A: Full Migration**

Reasons:
1. Methods labeled "global scope" should truly be GENERIC
2. Current methods are NOT global - they're agency-specific
3. Proper refactoring makes them reusable for wp-customer, wp-agency, future plugins
4. Follows separation of concerns: wp-app-core = generic, wp-agency = specific

---

## Questions for User

Before proceeding, please clarify:

1. **Do you want truly GENERIC methods in AdminBarModel?**
   - Generic = works with any plugin (customer, agency, etc)
   - Current methods use WP_Agency_Role_Manager and PermissionModel

2. **Should we keep wrapper methods in AgencyEmployeeModel?**
   - Wrapper = thin method that calls AdminBarModel with agency-specific params
   - Ensures backward compatibility

3. **Which Option do you prefer?**
   - Option A: Refactor to generic (recommended)
   - Option B: Copy as-is (quick but not truly global)

4. **Should getUserInfo() also be moved?**
   - Currently it's agency-specific (queries agency tables)
   - If moved, it would need to be abstracted too

---

## Next Steps (After Approval)

1. Create AdminBarModel.php with generic methods
2. Update AgencyEmployeeModel.php to use AdminBarModel
3. Test admin bar functionality
4. Create TODO-1204 documentation
5. Sync to TODO.md

---

**Please review and let me know which approach to take.**
