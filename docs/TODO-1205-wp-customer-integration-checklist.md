# TODO-1205: WP Customer Integration Checklist

## Status: 🔍 IN REVIEW

## Deskripsi

Analisis dan perbaikan integrasi wp-customer dengan wp-app-core berdasarkan [Plugin Integration Guide](plugin-integration-guide.md).

---

## Checklist Persyaratan Integrasi

### 1. Integration Class ✅ PASS (dengan catatan)

**File**: `/wp-customer/includes/class-app-core-integration.php`

**Version**: 1.1.0

#### ✅ Yang Sudah Benar:

1. ✅ **Dependency Check**: Ada pengecekan `class_exists('WP_App_Core_Admin_Bar_Info')`
   - Line 44: Check di `init()`
   - Line 62: Check di `register_with_app_core()`

2. ✅ **Plugin Registration**: Terdaftar dengan benar
   - Line 66-69: `WP_App_Core_Admin_Bar_Info::register_plugin()`
   - Plugin ID: `'customer'`
   - Roles: `WP_Customer_Role_Manager::getRoleSlugs()`
   - Callback: `get_user_info`

3. ✅ **Role Name Filters**: Semua role terdaftar
   - Line 52-55: Filter untuk 4 customer roles
   - Uses `get_role_name()` method (line 246-249)

4. ✅ **Debug Logging**: Comprehensive logging
   - Line 80-82, 92-98, 135-137, 166-169, 181-198, 224-227, 233-235

5. ✅ **Fallback Logic**: Ada handling untuk users dengan role tapi no entity
   - Line 174-230: Fallback implementation

6. ✅ **Model Delegation**: Menggunakan CustomerEmployeeModel
   - Line 88-89: Delegates to `CustomerEmployeeModel::getUserInfo()`

#### ❌ Issues Yang Ditemukan:

**ISSUE #1: Multiple Queries di Integration Class** 🔴 CRITICAL

**Location**: Lines 101-171

**Problem**:
```php
// Line 88-89: Query 1 - Employee
$employee_model = new \WPCustomer\Models\Employee\CustomerEmployeeModel();
$result = $employee_model->getUserInfo($user_id);

// Line 101-140: Query 2 - Customer Owner
if (!$result) {
    $customer = $wpdb->get_row(...);
    // Get main branch
    $branch = $wpdb->get_row(...);
}

// Line 143-171: Query 3 - Branch Admin
if (!$result) {
    $customer_branch_admin = $wpdb->get_row(...);
}
```

**Why it's a problem**:
- Integration class memiliki business logic (queries)
- Tidak mengikuti separation of concerns
- Berbeda dengan wp-agency yang delegates SEMUA ke Model
- Sulit untuk maintain dan test

**Expected**:
```php
// Integration class should only delegate:
public static function get_user_info($user_id) {
    $employee_model = new \WPCustomer\Models\Employee\CustomerEmployeeModel();
    return $employee_model->getUserInfo($user_id);
}
```

**Impact**: Medium (works tapi tidak optimal dari segi architecture)

---

**ISSUE #2: Model getUserInfo() Hanya Handle Employee** 🟡 MODERATE

**Location**: `CustomerEmployeeModel::getUserInfo()` line 799-954

**Problem**:
```php
// Model hanya query employee table
SELECT ... FROM {$wpdb->prefix}app_customer_employees e
...
WHERE e.user_id = %d AND e.status = 'active'
```

**Why it's a problem**:
- Model tidak handle customer owner
- Model tidak handle branch admin
- Integration class harus melakukan query tambahan
- Tidak consistent dengan pattern wp-agency

**Expected**:
Model should handle ALL user types:
1. Employee (current implementation)
2. Customer owner (belum ada)
3. Branch admin (belum ada)

**Suggested Solution**:
```php
public function getUserInfo(int $user_id): ?array {
    // Try employee first (most common)
    $result = $this->getEmployeeInfo($user_id);
    if ($result) return $result;

    // Try customer owner
    $result = $this->getCustomerOwnerInfo($user_id);
    if ($result) return $result;

    // Try branch admin
    $result = $this->getBranchAdminInfo($user_id);
    if ($result) return $result;

    // Fallback
    return $this->getFallbackInfo($user_id);
}
```

**Impact**: Medium (architectural improvement)

---

**ISSUE #3: Duplicate Fallback Logic** 🟡 MODERATE

**Location**:
- Integration class: Lines 174-230
- Model: Returns null when no employee found (line 892)

**Problem**:
- Fallback logic ada di Integration class
- Seharusnya di Model seperti wp-agency
- Duplicate code jika perlu digunakan di tempat lain

**Expected**:
- Fallback logic di Model
- Integration class hanya delegate

**Impact**: Low (code duplication)

---

### 2. Role Manager ✅ PASS

**File**: `/wp-customer/includes/class-role-manager.php`

**Version**: 1.0.0

#### ✅ Yang Sudah Benar:

1. ✅ **Required Methods**:
   - `getRoles()`: Returns array of slug => name
   - `getRoleSlugs()`: Returns array of slugs
   - `getRoleName($slug)`: Returns display name

2. ✅ **Role Definitions**:
   ```php
   'customer' => 'Customer'
   'customer_admin' => 'Customer Admin'
   'customer_branch_admin' => 'Customer Branch Admin'
   'customer_employee' => 'Customer Employee'
   ```

3. ✅ **Additional Methods**:
   - `isPluginRole()`: Check if role managed by plugin
   - `roleExists()`: Check if WordPress role exists

4. ✅ **Clean Implementation**: Well-documented, single responsibility

#### ❌ Issues: NONE

---

### 3. User Info Retrieval ✅ PASS (dengan catatan)

**File**: `/wp-customer/src/Models/Employee/CustomerEmployeeModel.php`

**Method**: `getUserInfo()` lines 799-954

#### ✅ Yang Sudah Benar:

1. ✅ **Caching Implementation**:
   - Line 803-808: Check cache first
   - Line 891: Cache null result (5 minutes)
   - Line 951: Cache success result (5 minutes)
   - Cache key: `'customer_user_info'`

2. ✅ **Single Comprehensive Query**:
   - Lines 812-887: One query with multiple JOINs
   - Includes: employees, customers, branches, memberships, users, usermeta
   - Uses GROUP_CONCAT pattern
   - Optimal performance

3. ✅ **Complete Data Structure**:
   - Customer info (code, name, npwp, nib, status)
   - Branch info (code, name, type, nitku, address, phone, email, etc)
   - Membership info (level, status, dates, payment info)
   - User info (email, capabilities)
   - Employee info (position)

4. ✅ **AdminBarModel Integration**:
   - Line 932-938: Uses `getRoleNamesFromCapabilities()`
   - Line 943-948: Uses `getPermissionNamesFromUserId()`
   - Proper delegation to helper methods

5. ✅ **Role Names Array**:
   - Line 934: `result['role_names']` dynamically populated
   - No hardcoded filters needed

6. ✅ **Permission Names Array**:
   - Line 944: `result['permission_names']` dynamically populated
   - Gets actual user permissions (including inherited)

#### ❌ Issues Yang Ditemukan:

**ISSUE #4: Model Hanya Handle Employee Case** 🔴 CRITICAL

**Location**: Line 812 - 887

**Problem**:
```sql
WHERE e.user_id = %d AND e.status = 'active'
```

Only queries `app_customer_employees` table, so:
- ❌ Customer owners tidak terdeteksi
- ❌ Branch admins tidak terdeteksi
- ❌ Integration class harus melakukan query terpisah

**Expected**:
Model should check:
1. Employee table (current)
2. Customer table (user_id = owner)
3. Branch table (user_id = branch admin)

**Impact**: High (forces business logic in integration class)

---

### 4. Query Optimization ⚠️ PARTIAL PASS

#### ✅ Yang Sudah Optimal:

1. ✅ **Employee Query**:
   - Single comprehensive query
   - Multiple JOINs (customers, branches, memberships, users, usermeta)
   - Uses GROUP BY to prevent duplicates
   - Includes MAX() aggregation
   - LIMIT 1 for performance

2. ✅ **No N+1 Problems**: All data retrieved in one query

3. ✅ **Proper Indexes**: Assuming tables have proper indexes on:
   - `user_id` columns
   - Foreign key columns

#### ❌ Yang Belum Optimal:

**ISSUE #5: Total 3 Queries untuk Non-Employee Users** 🟡 MODERATE

**Location**: Integration class lines 101-171

**Problem**:
Untuk user yang bukan employee:
1. Query 1: Check employee (Model) - Returns null
2. Query 2: Check customer owner (Integration) - Separate query
3. Query 3: Check branch admin (Integration) - Separate query

**vs. wp-agency approach**:
- wp-agency: 1 query handles ALL user types

**Expected**:
```php
// Single query in Model that checks ALL:
SELECT ... FROM employees
UNION
SELECT ... FROM customers (owners)
UNION
SELECT ... FROM branches (admins)
WHERE user_id = %d
LIMIT 1
```

Or use conditional JOINs in single query.

**Impact**: Medium (3x database calls for non-employees vs 1x in wp-agency)

---

### 5. Caching ✅ PASS

#### ✅ Implementation:

1. ✅ **Cache Key**: `'customer_user_info'` with user_id
2. ✅ **Cache Check**: Line 803-808
3. ✅ **Cache Set**:
   - Line 891: Null result (5 minutes)
   - Line 951: Success result (5 minutes)
4. ✅ **Cache Manager**: Uses `CustomerCacheManager`
5. ✅ **TTL**: 5 minutes (300 seconds) - reasonable

#### ✅ Cache Invalidation:

Model has comprehensive cache invalidation in:
- `create()`: Line 96-101
- `update()`: Line 192-198
- `delete()`: Line 221-228
- `changeStatus()`: Line 588-595

**Note**: `getUserInfo()` cache tidak otomatis di-invalidate saat employee data berubah.

#### ⚠️ Potential Issue:

**ISSUE #6: getUserInfo Cache Not Invalidated on Updates** 🟡 MODERATE

**Problem**:
- `update()` invalidates `'customer_employee'` cache (line 192)
- `update()` does NOT invalidate `'customer_user_info'` cache
- User info bisa outdated selama 5 menit setelah update

**Location**: Lines 192-198 in `update()` method

**Current**:
```php
$this->cache->delete('customer_employee', $id);
$this->cache->delete('customer_employee_count', (string)$employee->customer_id);
$this->cache->delete('active_customer_employee_count', (string)$employee->customer_id);
```

**Expected**:
```php
// Also invalidate getUserInfo cache
if ($employee->user_id) {
    $this->cache->delete('customer_user_info', $employee->user_id);
}
```

**Impact**: Low (only affects recently updated users for max 5 minutes)

---

### 6. Debug Logging ✅ PASS

#### ✅ Implementation di Integration Class:

1. ✅ **Function Start/End**: Lines 80-82, 233-235
2. ✅ **Query Results**: Lines 92-98, 135-137, 166-169
3. ✅ **Fallback Logic**: Lines 181-198, 224-227
4. ✅ **Conditional Logging**: Only when `WP_DEBUG` enabled
5. ✅ **Comprehensive**: Logs all decision points

#### ✅ Logging Pattern:

```php
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log("=== WP_Customer get_user_info START for user_id: {$user_id} ===");
    // ... operations ...
    error_log("=== WP_Customer get_user_info END - Final Result: ... ===");
}
```

**Grade**: Excellent

---

### 7. Best Practices Comparison

#### ✅ Mengikuti Best Practices:

1. ✅ **Dependency Check**: Graceful degradation
2. ✅ **Caching**: Implemented dengan proper TTL
3. ✅ **AdminBarModel Helpers**: Uses shared utilities
4. ✅ **Debug Logging**: Comprehensive dan conditional
5. ✅ **Role Name Array**: Dynamic role_names (no filters needed in v2)
6. ✅ **Permission Names Array**: Actual user permissions
7. ✅ **Single Query** (untuk employee): Optimal dengan JOINs
8. ✅ **Data Validation**: Checks null results
9. ✅ **Error Handling**: Returns null on failure
10. ✅ **Version Documentation**: Changelog present

#### ❌ Tidak Mengikuti Best Practices:

1. ❌ **Separation of Concerns**: Business logic di Integration class
2. ❌ **Single Responsibility**: Integration class melakukan queries
3. ❌ **DRY Principle**: Fallback logic duplicate
4. ❌ **Consistency**: Berbeda dengan wp-agency pattern
5. ❌ **Query Optimization**: 3 queries untuk non-employee vs 1 di wp-agency

---

## Perbandingan dengan wp-agency

### wp-agency Implementation (OPTIMAL):

**Integration Class**:
```php
public static function get_user_info($user_id) {
    // HANYA delegate ke Model
    $employee_model = new \WPAgency\Models\Employee\AgencyEmployeeModel();
    return $employee_model->getUserInfo($user_id);
}
```

**Model**:
```php
public function getUserInfo($user_id) {
    // 1 query handles ALL:
    // - Employee
    // - Division admin
    // - Agency owner
    // - Fallback

    // Single comprehensive query dengan INNER JOINs
    // Returns complete data atau null
}
```

**Total Queries**: 1

---

### wp-customer Implementation (CURRENT):

**Integration Class**:
```php
public static function get_user_info($user_id) {
    // Query 1: Employee (via Model)
    $result = $employee_model->getUserInfo($user_id);

    // Query 2: Customer owner (di Integration!)
    if (!$result) {
        $customer = $wpdb->get_row(...);
        $branch = $wpdb->get_row(...);
    }

    // Query 3: Branch admin (di Integration!)
    if (!$result) {
        $customer_branch_admin = $wpdb->get_row(...);
    }

    // Fallback (di Integration!)
    if (!$result) {
        // ... fallback logic ...
    }
}
```

**Model**:
```php
public function getUserInfo($user_id) {
    // Hanya handle employee
    // Returns null jika bukan employee
}
```

**Total Queries**: Up to 3 (employee check + owner check + branch admin check)

---

## Summary Issues

| Issue # | Severity | Component | Description | Impact |
|---------|----------|-----------|-------------|---------|
| #1 | 🔴 CRITICAL | Integration Class | Multiple queries in integration layer | Architecture |
| #2 | 🟡 MODERATE | Model | Only handles employee case | Architecture |
| #3 | 🟡 MODERATE | Both | Duplicate fallback logic | Code quality |
| #4 | 🔴 CRITICAL | Model | Doesn't check customer/branch tables | Functionality |
| #5 | 🟡 MODERATE | Integration | 3 queries vs 1 in wp-agency | Performance |
| #6 | 🟡 MODERATE | Model | getUserInfo cache not invalidated | Data freshness |

---

## Rekomendasi Perbaikan

### Priority 1: Architectural Refactoring (CRITICAL)

**Goal**: Move ALL business logic dari Integration class ke Model

**Changes Needed**:

1. **Update `CustomerEmployeeModel::getUserInfo()`**:
   - Add `getCustomerOwnerInfo()` private method
   - Add `getBranchAdminInfo()` private method
   - Add `getFallbackInfo()` private method
   - Update `getUserInfo()` to try all methods sequentially

2. **Simplify Integration Class**:
   - Remove queries (lines 101-171)
   - Remove fallback logic (lines 174-230)
   - Keep only delegation to Model

**Files to Modify**:
- `/wp-customer/src/Models/Employee/CustomerEmployeeModel.php`
- `/wp-customer/includes/class-app-core-integration.php`

**Expected Result**:
- Integration class: ~30 lines (down from 250)
- Model: +150 lines (adds new methods)
- Total queries: 1 (down from 3)

---

### Priority 2: Query Optimization (MODERATE)

**Goal**: Single query untuk semua user types

**Option A: UNION approach**:
```sql
(SELECT ... FROM employees WHERE user_id = %d)
UNION ALL
(SELECT ... FROM customers WHERE user_id = %d)
UNION ALL
(SELECT ... FROM branches WHERE user_id = %d)
LIMIT 1
```

**Option B: Conditional JOIN approach**:
```sql
SELECT
    CASE
        WHEN e.user_id IS NOT NULL THEN 'employee'
        WHEN c.user_id IS NOT NULL THEN 'owner'
        WHEN b.user_id IS NOT NULL THEN 'branch_admin'
    END as relation_type,
    ...
FROM wp_users u
LEFT JOIN employees e ON u.ID = e.user_id
LEFT JOIN customers c ON u.ID = c.user_id
LEFT JOIN branches b ON u.ID = b.user_id
WHERE u.ID = %d
```

**Recommended**: Option A (cleaner, follows wp-agency pattern)

---

### Priority 3: Cache Invalidation (LOW)

**Goal**: Invalidate `customer_user_info` cache when employee data changes

**Changes**:

Add to `update()` method (after line 198):
```php
// Invalidate getUserInfo cache
if ($employee->user_id) {
    $this->cache->delete('customer_user_info', $employee->user_id);
}
```

Add to `delete()` method (after line 227):
```php
// Invalidate getUserInfo cache
if ($employee->user_id) {
    $this->cache->delete('customer_user_info', $employee->user_id);
}
```

Add to `changeStatus()` method (after line 594):
```php
// Invalidate getUserInfo cache
if ($employee->user_id) {
    $this->cache->delete('customer_user_info', $employee->user_id);
}
```

---

## Testing Checklist

### Functionality Tests

- [ ] Admin bar muncul untuk employee users
- [ ] Admin bar muncul untuk customer owner
- [ ] Admin bar muncul untuk branch admin
- [ ] Admin bar muncul untuk users dengan role tapi no entity (fallback)
- [ ] Entity name dan code ditampilkan dengan benar
- [ ] Branch name dan type ditampilkan dengan benar
- [ ] Role names ditampilkan sebagai display names (bukan slugs)
- [ ] Permission names ditampilkan dengan benar
- [ ] Dropdown menampilkan detail lengkap
- [ ] Tidak ada konflik dengan wp-agency

### Performance Tests

- [ ] Query count optimal (ideally 1 query)
- [ ] Caching berfungsi (check debug log)
- [ ] Page load time tidak terpengaruh
- [ ] No N+1 query problems

### Edge Cases

- [ ] User dengan multiple roles
- [ ] User dengan role tapi tanpa entity
- [ ] User tanpa customer roles
- [ ] wp-app-core tidak aktif (graceful degradation)
- [ ] Cache invalidation saat update employee

---

## Files Involved

### Current Files:
1. `/wp-customer/includes/class-app-core-integration.php` (v1.1.0)
2. `/wp-customer/includes/class-role-manager.php` (v1.0.0)
3. `/wp-customer/src/Models/Employee/CustomerEmployeeModel.php` (v1.0.0)
4. `/wp-customer/src/Models/Settings/PermissionModel.php`

### Files to Create:
- None (refactoring only)

### Files to Modify:
1. `CustomerEmployeeModel.php` - Add methods for owner/branch admin
2. `class-app-core-integration.php` - Simplify to delegation only

---

## Implementation Plan

### Phase 1: Model Refactoring (Est: 2 hours)

1. **Extract Methods** (from Integration to Model):
   - `getEmployeeInfo($user_id)` - current implementation
   - `getCustomerOwnerInfo($user_id)` - from integration line 104-138
   - `getBranchAdminInfo($user_id)` - from integration line 146-170
   - `getFallbackInfo($user_id)` - from integration line 200-222

2. **Update `getUserInfo()`**:
   ```php
   public function getUserInfo(int $user_id): ?array {
       // Check cache
       $cached = $this->cache->get('customer_user_info', $user_id);
       if ($cached !== null) return $cached;

       // Try employee first (most common)
       $result = $this->getEmployeeInfo($user_id);
       if ($result) {
           $this->cache->set('customer_user_info', $result, 300, $user_id);
           return $result;
       }

       // Try customer owner
       $result = $this->getCustomerOwnerInfo($user_id);
       if ($result) {
           $this->cache->set('customer_user_info', $result, 300, $user_id);
           return $result;
       }

       // Try branch admin
       $result = $this->getBranchAdminInfo($user_id);
       if ($result) {
           $this->cache->set('customer_user_info', $result, 300, $user_id);
           return $result;
       }

       // Fallback
       $result = $this->getFallbackInfo($user_id);
       if ($result) {
           $this->cache->set('customer_user_info', $result, 300, $user_id);
       }

       return $result;
   }
   ```

3. **Add Cache Invalidation** to update/delete/changeStatus methods

---

### Phase 2: Integration Class Simplification (Est: 30 minutes)

1. **Remove Queries**: Delete lines 101-171
2. **Remove Fallback**: Delete lines 174-230
3. **Simplify `get_user_info()`**:
   ```php
   public static function get_user_info($user_id) {
       if (defined('WP_DEBUG') && WP_DEBUG) {
           error_log("=== WP_Customer get_user_info START for user_id: {$user_id} ===");
       }

       $employee_model = new \WPCustomer\Models\Employee\CustomerEmployeeModel();
       $result = $employee_model->getUserInfo($user_id);

       if (defined('WP_DEBUG') && WP_DEBUG) {
           error_log("=== WP_Customer get_user_info END - Result: " . print_r($result, true) . " ===");
       }

       return $result;
   }
   ```

---

### Phase 3: Testing (Est: 1 hour)

1. Test dengan berbagai user types
2. Verify query count (should be 1)
3. Test caching behavior
4. Test cache invalidation
5. Test fallback logic

---

### Phase 4: Documentation (Est: 30 minutes)

1. Update changelog in CustomerEmployeeModel.php
2. Update changelog in class-app-core-integration.php
3. Update version numbers
4. Document new private methods

---

## Estimated Total Effort

- Phase 1: 2 hours
- Phase 2: 30 minutes
- Phase 3: 1 hour
- Phase 4: 30 minutes

**Total**: ~4 hours

---

## Success Criteria

✅ Integration class hanya berisi delegation (no business logic)
✅ All business logic di Model layer
✅ Single query untuk semua user types (1 vs 3)
✅ Caching works untuk semua cases
✅ Cache invalidation works
✅ All tests pass
✅ Consistent dengan wp-agency pattern
✅ Code more maintainable dan testable

---

## Risk Assessment

### Low Risk:
- Model refactoring (additive changes)
- Cache invalidation (improves data freshness)

### Medium Risk:
- Integration class simplification (removes code)
- Query structure changes (could affect edge cases)

### Mitigation:
- Keep backup of original files
- Test thoroughly dengan berbagai user types
- Enable debug logging during testing
- Rollback plan ready

---

## Kesimpulan

### Current State:

wp-customer **SUDAH** terintegrasi dengan wp-app-core dan **BERFUNGSI**, tetapi:
- ❌ Tidak optimal dari segi architecture
- ❌ Business logic di Integration layer
- ❌ Tidak consistent dengan wp-agency pattern
- ❌ 3 queries vs 1 query (performance)

### Recommended State:

Setelah refactoring:
- ✅ Clean architecture (separation of concerns)
- ✅ All business logic di Model
- ✅ Consistent dengan best practices
- ✅ Optimal performance (1 query)
- ✅ Easier to maintain dan test

### Grade:

**Current**: C+ (works but not optimal)
**After Refactoring**: A (follows best practices)

---

## Approval

- [ ] User review dan approval
- [ ] Proceed dengan refactoring?
- [ ] Skip refactoring (keep as is)?

---

## Tanggal Review

- **Created**: 2025-01-18 15:30
- **Reviewer**: Claude Code
- **Status**: Awaiting user decision

---

## Related Documentation

- [Plugin Integration Guide](plugin-integration-guide.md) - Best practices reference
- `/wp-app-core/TODO/TODO-1201-wp-app-core-admin-bar-integration.md` - Development history
- `/wp-customer/includes/class-app-core-integration.php` - Current implementation
- `/wp-customer/src/Models/Employee/CustomerEmployeeModel.php` - Model to refactor
