# TODO-1179: Rename Container ID to wpapp-datatable-container

## Status
✅ **COMPLETED** - 2025-10-25

## Requirement (Review-09)
Di wp-app-core, ID `wpapp-agency-container` adalah ambigu - ini wp-app-core atau wp-agency?

**Instruksi:**
1. Ubah `wpapp-agency-container` menjadi `wpapp-datatable-container`
2. Salin CSS property `#wpapp-datatable-container` dari companies.css ke wpapp-datatable.css

## Problem

**Before (Ambiguous ID):**
```html
<!-- In wp-app-core template -->
<div class="wpapp-row" id="wpapp-<?php echo esc_attr($entity); ?>-container">
```

When `$entity = 'agency'`, this becomes:
```html
<div class="wpapp-row" id="wpapp-agency-container">
```

**Issues:**
1. ❌ **Ambiguous naming** - Looks like wp-agency plugin scope, but file is in wp-app-core
2. ❌ **Dynamic ID** - Different for each entity (agency, company, customer), hard to style
3. ❌ **Inconsistent** - wp-customer uses static ID `wpapp-datatable-container`

## Solution

Change from dynamic entity-based ID to static generic ID.

**After (Clear Global ID):**
```html
<!-- In wp-app-core template -->
<div class="wpapp-row" id="wpapp-datatable-container">
```

**Benefits:**
1. ✅ Clear this is global scope (wpapp- prefix, generic name)
2. ✅ Consistent across all entities (agency, company, customer use same ID)
3. ✅ Easy to style with ID selector (#wpapp-datatable-container)
4. ✅ Matches wp-customer implementation

## Changes Implemented

### 1. PanelLayoutTemplate.php - Change Dynamic ID to Static ID ✅
**File**: `wp-app-core/src/Views/DataTable/Templates/PanelLayoutTemplate.php`

**Line 45:** Changed from dynamic to static ID
```php
// Before:
<div class="wpapp-row" id="wpapp-<?php echo esc_attr($entity); ?>-container">

// After:
<div class="wpapp-row" id="wpapp-datatable-container">
```

**Changes:**
- ✅ Removed dynamic entity variable from ID
- ✅ Changed to static generic name `wpapp-datatable-container`
- ✅ Same ID for all entities (agency, company, customer)

### 2. wpapp-datatable.css - Add ID Selector (Desktop) ✅
**File**: `wp-app-core/assets/css/datatable/wpapp-datatable.css`

**Lines 172-179:** Added ID selector after existing class selector
```css
/* ID selector for row container - Copied from companies.css */
#wpapp-datatable-container {
    display: flex;
    flex-wrap: nowrap; /* IMPORTANT: Don't wrap to next line */
    margin-right: -15px;
    margin-left: -15px;
    align-items: flex-start;
}
```

**Source:** `wp-customer/assets/css/companies/companies.css:634-640`

**Changes:**
- ✅ Added ID selector for specific styling
- ✅ Same properties as class selector `.wpapp-datatable-layout .wpapp-row`
- ✅ Provides stronger specificity when needed

### 3. wpapp-datatable.css - Add ID Selector (Mobile Responsive) ✅
**File**: `wp-app-core/assets/css/datatable/wpapp-datatable.css`

**Lines 497-500:** Added responsive CSS inside `@media (max-width: 768px)`
```css
/* Sliding panel responsiveness - Copied from companies.css */
#wpapp-datatable-container {
    flex-wrap: wrap; /* Allow wrapping on mobile */
}
```

**Source:** `wp-customer/assets/css/companies/companies.css:972-974`

**Changes:**
- ✅ Added mobile responsive override
- ✅ Changes `flex-wrap: nowrap` to `flex-wrap: wrap` on mobile
- ✅ Allows panels to stack vertically on small screens

## CSS Specificity

Now the container can be styled with both:

1. **Class selector** (lower specificity, for general layout):
   ```css
   .wpapp-datatable-layout .wpapp-row { ... }
   ```

2. **ID selector** (higher specificity, for specific overrides):
   ```css
   #wpapp-datatable-container { ... }
   ```

Both have identical properties, but ID selector provides option for stronger specificity if needed by plugins.

## Impact on Other Files

### Files Using This Template
All entities using `PanelLayoutTemplate::render()` now render:
```html
<div class="wpapp-row" id="wpapp-datatable-container">
```

**Entities affected:**
- wp-agency (was `wpapp-agency-container`, now `wpapp-datatable-container`)
- wp-company (was `wpapp-company-container`, now `wpapp-datatable-container`)
- wp-customer (already using `wpapp-datatable-container` in custom template)

### JavaScript Impact
No JavaScript changes needed - no JS files reference the old dynamic IDs.

**Checked:**
- `wpapp-panel-manager.js` - Uses class selectors, not ID
- `wpapp-tab-manager.js` - Uses class selectors, not ID

## Naming Convention Alignment

### Before (Inconsistent)
- wp-customer: `wpapp-datatable-container` (static, generic)
- wp-agency: `wpapp-agency-container` (dynamic, specific)
- wp-company: `wpapp-company-container` (dynamic, specific)

### After (Consistent)
- wp-customer: `wpapp-datatable-container` ✅
- wp-agency: `wpapp-datatable-container` ✅
- wp-company: `wpapp-datatable-container` ✅

All entities now use the same ID - consistent global scope naming.

## Files Modified
1. ✅ `wp-app-core/src/Views/DataTable/Templates/PanelLayoutTemplate.php` (line 45)
2. ✅ `wp-app-core/assets/css/datatable/wpapp-datatable.css` (lines 172-179, 497-500)

## Visual Impact
- ✅ **No visual changes** - CSS properties remain the same
- ✅ Layout still uses flexbox with nowrap on desktop
- ✅ Layout still wraps on mobile (< 768px)
- ✅ Only the ID name changed (internal change)

## Testing Checklist
- [ ] Clear WordPress cache
- [ ] Clear browser cache
- [ ] Test wp-agency dashboard:
  - [ ] Layout displays correctly
  - [ ] Panel sliding works
  - [ ] Check DevTools: verify ID is `wpapp-datatable-container`
- [ ] Test wp-customer companies:
  - [ ] Layout displays correctly (should match before)
  - [ ] Panel sliding works
  - [ ] Check DevTools: verify ID is `wpapp-datatable-container`
- [ ] Test responsive on mobile:
  - [ ] Resize browser to < 768px width
  - [ ] Verify panels stack vertically
  - [ ] No horizontal overflow
- [ ] No console errors

## Expected HTML Structure

```html
<div class="wpapp-datatable-layout" data-entity="agency">
    <div class="wpapp-row" id="wpapp-datatable-container">  <!-- ✅ Static ID -->

        <!-- Left Panel -->
        <div class="wpapp-col-md-12" id="wpapp-agency-table-container">
            ...DataTable...
        </div>

        <!-- Right Panel -->
        <div class="wpapp-col-md-5 wpapp-detail-panel wpapp-hidden"
             id="wpapp-agency-detail-panel">
            ...Detail View...
        </div>

    </div>
</div>
```

**Note:** Only the row container ID changed. Other child IDs still use entity-specific names:
- `wpapp-agency-table-container` (still dynamic)
- `wpapp-agency-detail-panel` (still dynamic)
- `wpapp-agency-detail-content` (still dynamic)

## Why Keep Other IDs Dynamic?

The row container (`wpapp-datatable-container`) is now static because:
- ✅ Used for layout only (flex container)
- ✅ Same styling across all entities
- ✅ Generic purpose (holds any datatable)

Child containers remain dynamic because:
- ✅ May need entity-specific JavaScript targeting
- ✅ May need entity-specific AJAX actions
- ✅ Helps with debugging (clear which entity's panel)

## Related TODOs
- See: `wp-app-core/TODO/TODO-1179-align-templates-css-with-wp-customer.md`
- Related: `wp-agency/TODO/TODO-3073-remove-double-wrapper-filter.md`
- Related: `wp-agency/TODO/TODO-3074-rename-filter-group-to-status-filter-group.md`

## References
- Review-09 in `wp-app-core/claude-chats/task-1179.md` (lines 293-309)
- Source CSS: `wp-customer/assets/css/companies/companies.css` (lines 634-640, 972-974)

## Code Quality Notes
This change improves:
1. **Clarity** - Clear that this is global scope (wpapp- + generic name)
2. **Consistency** - All entities use same ID
3. **Maintainability** - Single ID to style, not per-entity IDs
4. **Alignment** - Matches wp-customer implementation (reference plugin)
