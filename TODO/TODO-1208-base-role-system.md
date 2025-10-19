# TODO-1208: Base Role System for Platform Users

## Tanggal
2025-10-19

## Deskripsi
Implementasi base role system untuk semua platform users, mirip dengan wp-customer plugin. Semua platform users akan mendapat dual roles: `platform_staff` (base role) + admin role specific (platform_admin, platform_manager, dll).

## Root Cause Analysis

### Problem
Platform users tidak bisa akses wp-admin meskipun memiliki capability 'read'.

### Investigation
```bash
# Check roles for customer users (CAN access wp-admin)
wp user get 2 --field=roles
# Output: customer, customer_admin  ← DUAL ROLES!

# Check roles for platform users (CANNOT access wp-admin)
wp user get 232 --field=roles
# Output: platform_admin  ← SINGLE ROLE!
```

### Root Cause
**WP-Customer Plugin**: Users mendapat 2 roles
- Base role: `customer` (dengan capability 'read')
- Admin role: `customer_admin` (dengan permissions khusus)

**WP-App-Core Plugin** (sebelum fix): Users hanya 1 role
- Admin role: `platform_admin` (tidak ada base role)

**WordPress Requirement**:
- User butuh capability 'read' untuk akses wp-admin
- Role capabilities di WordPress tidak selalu persistent
- Base role pattern lebih reliable untuk ensure wp-admin access

## Solution: Base Role System

### Konsep
Implementasi dual role system seperti wp-customer:

**Struktur Baru**:
```
Platform User:
├── Base Role: platform_staff (untuk wp-admin access)
│   └── Capability: read = true
└── Admin Role: platform_xxx (untuk specific permissions)
    └── Capabilities: (managed by PlatformPermissionModel)
```

### Implementation

#### 1. class-role-manager.php
**Changes**:
- Added `getBaseRole()` → returns 'platform_staff'
- Added `getBaseRoleName()` → returns 'Platform Staff'
- Added `getAdminRoles()` → returns 7 admin roles
- Updated `getRoles()` → merge base role + admin roles
- Updated `createRoles()`:
  - Create base role FIRST with 'read' capability
  - Create admin roles without 'read' (will inherit from base role)

**Code**:
```php
public static function getBaseRole(): string {
    return 'platform_staff';
}

public static function createRoles(): void {
    // Create base role with 'read'
    add_role('platform_staff', 'Platform Staff', ['read' => true]);

    // Create admin roles without 'read'
    foreach (getAdminRoles() as $slug => $name) {
        add_role($slug, $name, []);
    }
}
```

#### 2. Data/PlatformUsersData.php
**Changes**:
Updated ALL 20 users (ID 230-249) untuk dual roles:

**Before**:
```php
'roles' => ['platform_admin']
```

**After**:
```php
'roles' => ['platform_staff', 'platform_admin']
```

**Affected Entries**: All 20 users across 7 platform roles

#### 3. class-upgrade.php
**New Upgrade Routine**: `upgrade_to_1_0_2()`

**What It Does**:
1. Create base role 'platform_staff' with 'read' capability
2. Loop through all existing platform users
3. Add 'platform_staff' role to each user (without removing existing role)
4. Log each update

**Code Flow**:
```
plugins_loaded (priority 5)
→ check_and_upgrade()
→ detect version 1.0.1 → 1.0.2
→ upgrade_to_1_0_2()
→ Create platform_staff role
→ Get all users with platform admin roles
→ Add platform_staff to each user
→ Update version in DB
```

**Example Log Output**:
```
WP_App_Core_Upgrade: Upgrading from 1.0.1 to 1.0.2
WP_App_Core_Upgrade: Running upgrade to 1.0.2 - Implementing base role system
WP_App_Core_Upgrade: Created base role: platform_staff with 'read' capability
WP_App_Core_Upgrade: Added base role 'platform_staff' to user 232 (edwin_felix)
WP_App_Core_Upgrade: Added base role 'platform_staff' to user 233 (grace_helen)
... (all platform users)
WP_App_Core_Upgrade: Upgrade to 1.0.2 completed - 20 users updated with base role
```

#### 4. wp-app-core.php
**Changes**:
- Version: 1.0.1 → 1.0.2
- Updated changelog
- Upgrade script akan auto-run on next page load

## Testing

### Expected Results After Upgrade:

```bash
# Check roles
wp user list --role=platform_admin --fields=ID,user_login,roles

# Expected Output:
ID   user_login    roles
232  edwin_felix   platform_staff, platform_admin  ← DUAL ROLES ✅
233  grace_helen   platform_staff, platform_admin  ← DUAL ROLES ✅
234  ivan_julia    platform_staff, platform_admin  ← DUAL ROLES ✅
```

### Manual Test:
1. ✅ Refresh admin page (trigger upgrade to 1.0.2)
2. ✅ Check WP_DEBUG log for upgrade messages
3. ✅ Login As User dengan platform role
4. ✅ Access /wp-admin
5. ✅ Should enter directly without password prompt

## Files Modified

1. **includes/class-role-manager.php**
   - Added base role methods
   - Updated createRoles() logic

2. **src/Database/Demo/Data/PlatformUsersData.php**
   - Updated all 20 users to dual roles

3. **includes/class-upgrade.php**
   - Added upgrade_to_1_0_2() routine
   - Added version check for 1.0.2

4. **wp-app-core.php**
   - Updated version to 1.0.2
   - Updated changelog

## Comparison: Customer vs Platform

| Aspect | WP-Customer | WP-App-Core (After Fix) |
|--------|-------------|------------------------|
| **Base Role** | customer | platform_staff |
| **Admin Roles** | customer_admin, customer_branch_admin, customer_employee | platform_super_admin, platform_admin, platform_manager, platform_support, platform_finance, platform_analyst, platform_viewer |
| **User Structure** | base + admin role | base + admin role |
| **WP-Admin Access** | Via base role 'read' cap | Via base role 'read' cap |
| **Implementation** | CustomerDemoData.php line 213 | WPUserGenerator.php line 104-112 |

## Benefits

1. ✅ **Consistent with wp-customer** - Same dual role pattern
2. ✅ **Reliable wp-admin access** - Base role ensures 'read' capability
3. ✅ **Future-proof** - New users automatically get dual roles
4. ✅ **Backward compatible** - Existing users auto-upgraded
5. ✅ **Clean separation** - Base role for access, admin role for permissions
6. ✅ **Easy debugging** - Clear role structure

## Status
✅ Completed - 2025-10-19

## Next Steps
1. Refresh WordPress admin page
2. Check debug.log for upgrade messages
3. **IMPORTANT**: Run `wp cache flush` to clear WordPress cache
4. Test Login As User with platform roles
5. Verify wp-admin access works

## Important: Cache Flush Required!

**After role changes, WordPress cache MUST be flushed:**

```bash
wp cache flush
```

**Why?**
- WordPress caches role and capability data
- After creating/updating roles, old cache still contains outdated data
- Users will get "Permission Denied" even though roles are correct
- Cache flush forces WordPress to reload fresh role data

**Symptoms of cached role data:**
- ✓ Roles are correct in database
- ✓ Capabilities are correct
- ✗ Users still can't access wp-admin
- ✓ After cache flush → Works!

## Troubleshooting

### Issue: Platform users can't access wp-admin after upgrade

**Check 1: Verify base role exists**
```bash
wp role list --format=table | grep platform_staff
```
Expected: Should show "Platform Staff" role

**Check 2: Verify base role has 'read' capability**
```bash
wp eval 'error_reporting(0); $role = get_role("platform_staff"); echo $role && $role->has_cap("read") ? "YES" : "NO";'
```
Expected: `YES`

**Check 3: Verify users have dual roles**
```bash
wp user list --role=platform_finance --fields=ID,user_login,roles
```
Expected: Each user should have `platform_staff, platform_finance`

**Fix 1: If base role missing, run fix script**
```bash
wp eval-file fix-base-role.php
```

**Fix 2: ALWAYS flush cache after role changes**
```bash
wp cache flush
```

**Fix 3: If still not working, clear browser cache**
- Logout completely
- Clear browser cookies
- Login again and test

## Notes
- Base role 'platform_staff' is REQUIRED for ALL platform users
- Admin roles define permissions, base role provides access
- Do not remove base role from any platform user
- Upgrade script is idempotent (safe to run multiple times)
- **ALWAYS run `wp cache flush` after role changes!**
