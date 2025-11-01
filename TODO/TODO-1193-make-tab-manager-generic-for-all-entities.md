# TODO-1193: Make wpapp-tab-manager.js Generic for All Entities

**Status**: ✅ COMPLETED
**Priority**: HIGH
**Type**: Bug Fix / Enhancement
**Created**: 2025-11-01
**Completed**: 2025-11-01
**Related**:
- wp-customer TODO-2187 Review-03
- wp-agency TODO-3099-migrate-agency-to-generic-tab-pattern.md

## Problem

wpapp-tab-manager.js is currently hardcoded for 'agency' entity only:
- **Line 219**: `const agencyId = $tab.attr('data-agency-id');` - hardcoded attribute name
- **Line 252**: `agency_id: agencyId` - hardcoded parameter name in AJAX request

This makes the tab system non-generic and forces other plugins (wp-customer, etc.) to use workarounds.

## Current Impact

**wp-customer plugin** had to implement workaround:
1. Add `data-agency-id` attribute alongside `data-customer-id` in tab views
2. Accept both `$_POST['agency_id']` and `$_POST['customer_id']` in AJAX handlers

Files affected:
- `/wp-customer/src/Views/customer/tabs/branches.php:54`
- `/wp-customer/src/Views/customer/tabs/employees.php:54`
- `/wp-customer/src/Controllers/Customer/CustomerDashboardController.php:516-522` (handle_load_branches_tab)
- `/wp-customer/src/Controllers/Customer/CustomerDashboardController.php:564-570` (handle_load_employees_tab)

## Required Changes

### File: `/wp-app-core/assets/js/datatable/wpapp-tab-manager.js`

**Current Code (Lines 219-252)**:
```javascript
// Line 219 - HARDCODED
const agencyId = $tab.attr('data-agency-id');

// Line 231 - HARDCODED validation
if (!loadAction || !agencyId) {
    console.error('[WPApp Tab] Missing required data attributes for auto-load', {
        loadAction: loadAction,
        agencyId: agencyId
    });
    return;
}

// Line 252 - HARDCODED parameter
agency_id: agencyId
```

**Proposed Solution**:
```javascript
// Get entity type from panel or default to 'agency' for backward compatibility
const entityType = $panel.attr('data-entity-type') || 'agency';
const entityIdAttr = 'data-' + entityType + '-id';
const entityId = $tab.attr(entityIdAttr);

// Generic validation
if (!loadAction || !entityId) {
    console.error('[WPApp Tab] Missing required data attributes for auto-load', {
        loadAction: loadAction,
        entityIdAttr: entityIdAttr,
        entityId: entityId
    });
    return;
}

// Generic parameter name
const ajaxData = {
    action: loadAction,
    nonce: nonce
};
ajaxData[entityType + '_id'] = entityId; // Dynamic: agency_id, customer_id, etc.
```

## Implementation Steps

1. **Update wpapp-tab-manager.js**:
   - Make entity ID attribute dynamic based on `data-entity-type`
   - Make AJAX parameter name dynamic
   - Maintain backward compatibility (default to 'agency' if no entity-type specified)

2. **Update TabSystemTemplate.php** (if needed):
   - Ensure panel has `data-entity-type` attribute
   - Example: `<div class="wpapp-panel" data-entity-type="customer">`

3. **Update documentation**:
   - Document the generic pattern in `/wp-app-core/src/Views/DataTable/README.md`
   - Add examples for different entity types

4. **Test with multiple entities**:
   - wp-agency (agency)
   - wp-customer (customer)
   - Any other plugins using the tab system

## Backward Compatibility

**IMPORTANT**: Maintain backward compatibility!
- If `data-entity-type` not specified, default to 'agency'
- This ensures existing wp-agency implementation continues to work
- Other plugins can opt-in to generic pattern by specifying entity-type

## Testing Checklist

- [ ] wp-agency tabs still work (backward compatibility)
- [ ] wp-customer tabs work with generic pattern
- [ ] Console errors are helpful and entity-specific
- [ ] AJAX requests send correct parameter names
- [ ] Multiple entities can coexist on same page

## Related Files

- `/wp-app-core/assets/js/datatable/wpapp-tab-manager.js` (main fix)
- `/wp-app-core/src/Views/DataTable/TabSystemTemplate.php` (may need data-entity-type)
- `/wp-app-core/src/Views/DataTable/README.md` (documentation)

## After This Fix

wp-customer can REMOVE workarounds:
- Remove `data-agency-id` from tab views (use only `data-customer-id`)
- Remove dual parameter check in AJAX handlers (accept only `customer_id`)

wp-agency may need update:
- Add `data-entity-type="agency"` to panel for explicitness (optional if defaulting)
- See wp-agency TODO-3099-migrate-agency-to-generic-tab-pattern.md

---

## ✅ IMPLEMENTATION COMPLETED (2025-11-01)

### Changes Made:

**File Modified**: `/wp-app-core/assets/js/datatable/wpapp-tab-manager.js`
- **Version**: 1.0.0 → 1.1.0
- **Lines Changed**: 218-257

### Implementation Details:

**1. Dynamic Entity Type Detection (Line 218-221)**
```javascript
// Get entity type from panel (default to 'agency' for backward compatibility)
const $panel = $('.wpapp-panel');
const entityType = $panel.attr('data-entity-type') || this.currentEntity || 'agency';
const entityIdAttr = 'data-' + entityType + '-id';
```

**2. Dynamic Entity ID Retrieval (Line 224)**
```javascript
const entityId = $tab.attr(entityIdAttr);
```

**3. Generic Validation (Line 238-242)**
```javascript
if (!loadAction || !entityId) {
    console.error('[WPApp Tab] Missing required data attributes for auto-load');
    console.error('[WPApp Tab] loadAction:', loadAction);
    console.error('[WPApp Tab] ' + entityIdAttr + ':', entityId);
    return;
}
```

**4. Dynamic AJAX Data Building (Line 252-257)**
```javascript
// Build AJAX data with dynamic entity ID parameter
const ajaxData = {
    action: loadAction,
    nonce: wpAppConfig.nonce
};
ajaxData[entityType + '_id'] = entityId; // Dynamic: agency_id, customer_id, etc.
```

### Entity Detection Flow:

**Priority Order:**
1. `data-entity-type` attribute on `.wpapp-panel` element (explicit)
2. `this.currentEntity` from `.wpapp-datatable-layout` data-entity (line 58)
3. Default to `'agency'` (backward compatibility)

**Result:**
- wp-customer: `entityType = 'customer'` → uses `data-customer-id`, sends `customer_id`
- wp-agency: `entityType = 'agency'` → uses `data-agency-id`, sends `agency_id`
- Future entities: Automatically supported

### Backward Compatibility:

✅ **100% Backward Compatible**
- wp-agency works without any code changes
- Default entity type is 'agency' if not specified
- Existing `data-agency-id` attributes continue to work

### Workarounds Removed from wp-customer:

**Files Updated:**
1. `/wp-customer/src/Views/customer/tabs/branches.php`
   - Removed: `data-agency-id="<?php echo esc_attr($customer_id); ?>"`
   - Kept: `data-customer-id="<?php echo esc_attr($customer_id); ?>"`

2. `/wp-customer/src/Views/customer/tabs/employees.php`
   - Removed: `data-agency-id="<?php echo esc_attr($customer_id); ?>"`
   - Kept: `data-customer-id="<?php echo esc_attr($customer_id); ?>"`

3. `/wp-customer/src/Controllers/Customer/CustomerDashboardController.php`
   - `handle_load_branches_tab()`: Removed agency_id fallback
   - `handle_load_employees_tab()`: Removed agency_id fallback
   - Now accepts only `customer_id` parameter

### Testing:

**Tested Entities:**
- ✅ customer (wp-customer plugin)
- ✅ agency (wp-agency plugin - backward compatibility)

**Test Results:**
- ✅ wp-customer tabs load correctly with `customer_id`
- ✅ wp-agency tabs still work with `agency_id`
- ✅ No console errors
- ✅ Entity detection automatic and correct

### Documentation:

✅ Updated wpapp-tab-manager.js header with:
- Version 1.1.0 changelog
- Entity configuration instructions
- Usage examples
- Backward compatibility notes
