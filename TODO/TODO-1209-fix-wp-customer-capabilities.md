# TODO-1209: Fix WP Customer Plugin Capabilities Registration

**Status**: ✅ COMPLETED
**Date**: 2025-10-19
**Author**: arisciwek
**Related Files**:
- `/wp-app-core/src/Models/Settings/PlatformPermissionModel.php`

## Problem

WP Customer plugin capabilities were added to the role defaults in `getDefaultCapabilitiesForRole()` method (lines 442-577 in v1.0.2), but platform users still couldn't access WP Customer menus even after running `wp cache flush`.

## Root Cause

The WP Customer capabilities were NOT registered in two critical arrays:
1. `$available_capabilities` array (lines 41-97)
2. `$capability_groups` array (lines 102-180)

This caused the capabilities to be **skipped** during role assignment because of this validation check on line 246:
```php
if ($enabled && isset($this->available_capabilities[$cap])) {
    $role->add_cap($cap);
}
```

Since the WP Customer capabilities were not in `$available_capabilities`, the `isset()` check failed and they were never added to the roles.

## Solution Implemented

### 1. Added WP Customer Capabilities to `$available_capabilities`
Added all 32 WP Customer capabilities organized by category:
- **Customer Management** (7 capabilities)
  - view_customer_list, view_customer_detail, view_own_customer
  - add_customer, edit_all_customers, edit_own_customer, delete_customer

- **Branch Management** (7 capabilities)
  - view_customer_branch_list, view_customer_branch_detail, view_own_customer_branch
  - add_customer_branch, edit_all_customer_branches, edit_own_customer_branch, delete_customer_branch

- **Employee Management** (7 capabilities)
  - view_customer_employee_list, view_customer_employee_detail, view_own_customer_employee
  - add_customer_employee, edit_all_customer_employees, edit_own_customer_employee, delete_customer_employee

- **Membership Invoice** (8 capabilities)
  - view_customer_membership_invoice_list, view_customer_membership_invoice_detail
  - view_own_customer_membership_invoice, create_customer_membership_invoice
  - edit_all_customer_membership_invoices, edit_own_customer_membership_invoice
  - delete_customer_membership_invoice, approve_customer_membership_invoice

- **Invoice Payment** (3 capabilities)
  - pay_all_customer_membership_invoices
  - pay_own_customer_membership_invoices
  - pay_own_branch_membership_invoices

### 2. Added WP Customer Capabilities to `$capability_groups`
Created 5 new capability groups for organized UI display:
- `wp_customer_management`
- `wp_customer_branch`
- `wp_customer_employee`
- `wp_customer_invoice`
- `wp_customer_invoice_payment`

### 3. Added Capability Descriptions
Added Indonesian descriptions for all 32 WP Customer capabilities in `getCapabilityDescriptions()` method.

### 4. Updated Version and Changelog
- Updated version from 1.0.2 to 1.0.3
- Added comprehensive changelog entry

## Testing Results

### Before Fix
```
platform_finance role:
   ✗ No WP Customer capabilities (except those already manually added)
```

### After Fix
```
platform_finance role:
   ✓ view_customer_membership_invoice_list
   ✓ view_customer_membership_invoice_detail
   ✓ create_customer_membership_invoice
   ✓ edit_all_customer_membership_invoices
   ✓ approve_customer_membership_invoice
   ✓ pay_all_customer_membership_invoices
   ✓ view_customer_list
   ✓ view_customer_branch_list

platform_admin role:
   ✓ All 15 required WP Customer capabilities

Menu Access Verification:
   ✓ platform_finance users can access all 3 WP Customer menus
   ✓ platform_admin users can access all 3 WP Customer menus
```

## Files Modified

1. **PlatformPermissionModel.php** (v1.0.2 → v1.0.3)
   - Lines 98-138: Added WP Customer capabilities to `$available_capabilities`
   - Lines 222-278: Added WP Customer capability groups to `$capability_groups`
   - Lines 772-812: Added WP Customer capability descriptions
   - Lines 15-21: Added changelog entry
   - Line 7: Updated version number

## How to Apply

If you need to reapply capabilities to existing roles after this fix:

```bash
# Option 1: Use WordPress admin
Go to Settings > Platform Settings > Permissions tab
Click "Reset to Default" button

# Option 2: Use WP-CLI
wp eval-file update-capabilities.php
```

## Role Capability Matrix

| Capability | platform_super_admin | platform_admin | platform_manager | platform_finance |
|-----------|---------------------|----------------|------------------|------------------|
| **Customer Management** |
| view_customer_list | ✓ | ✓ | ✓ | ✓ |
| add_customer | ✓ | ✓ | - | - |
| edit_all_customers | ✓ | ✓ | - | - |
| delete_customer | ✓ | - | - | - |
| **Branch Management** |
| view_customer_branch_list | ✓ | ✓ | ✓ | ✓ |
| add_customer_branch | ✓ | ✓ | - | - |
| edit_all_customer_branches | ✓ | ✓ | - | - |
| delete_customer_branch | ✓ | - | - | - |
| **Employee Management** |
| view_customer_employee_list | ✓ | ✓ | ✓ | - |
| view_customer_employee_detail | ✓ | ✓ | ✓ | - |
| **Membership Invoice** |
| view_customer_membership_invoice_list | ✓ | ✓ | ✓ | ✓ |
| create_customer_membership_invoice | ✓ | ✓ | - | ✓ |
| edit_all_customer_membership_invoices | ✓ | ✓ | - | ✓ |
| approve_customer_membership_invoice | ✓ | ✓ | - | ✓ |
| **Invoice Payment** |
| pay_all_customer_membership_invoices | ✓ | - | - | ✓ |

## Next Steps

- [x] Test menu access with actual user login
- [x] Verify permission matrix in admin settings UI
- [x] Update main TODO.md with this task

## Notes

- This fix ensures that all future plugin integrations follow the same pattern:
  1. Add capabilities to `$available_capabilities`
  2. Add capability groups to `$capability_groups`
  3. Add capability descriptions to `getCapabilityDescriptions()`
  4. Add role defaults to `getDefaultCapabilitiesForRole()`

- The validation on line 246 (`isset($this->available_capabilities[$cap])`) is intentional - it ensures only registered capabilities can be added to roles.
