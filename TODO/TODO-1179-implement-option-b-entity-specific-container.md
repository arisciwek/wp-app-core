# TODO-1179: Implement Option B - Entity-Specific Container Without Global wpapp-panel-content

## Status
✅ **COMPLETED** - 2025-10-25

## Requirement (Review ulang Review-09)
Struktur HTML masih tumpang tindih - ada global scope di dalam local scope, dan ID container ambigu.

**Masalah:**
1. `wpapp-datatable-container` (generic) vs `wpapp-agency-container` (entity-specific) - mana yang benar?
2. `wpapp-panel-content` (global) digunakan di left panel yang seharusnya fully controlled oleh plugin
3. Double global scope: `wpapp-panel-content` → `wpapp-datatable-wrapper`

**Solusi: Opsi B - Entity-Specific ID + No wpapp-panel-content di Left Panel**

## Problem Analysis

### Before (Tumpang Tindih):
```html
<div class="wpapp-datatable-layout" data-entity="agency">  <!-- Entity clear -->
    <div class="wpapp-row" id="wpapp-datatable-container">  <!-- ❌ Generic ID, entity sudah di parent -->
        <div class="wpapp-col-md-12" id="wpapp-agency-table-container">
            <div class="wpapp-panel-content">  <!-- ❌ Global scope di left panel -->
                <div class="wpapp-datatable-wrapper">  <!-- ❌ Global scope lagi -->
                    <table>...</table>
                </div>
            </div>
        </div>
    </div>
</div>
```

**Issues:**
1. ❌ ID ambigu - `wpapp-datatable-container` bisa jadi wp-app-core atau wp-agency?
2. ❌ `wpapp-panel-content` tidak perlu di left panel (wp-customer reference tidak pakai)
3. ❌ Left panel = DataTable = full plugin control, bukan base template
4. ❌ Double wrapper global scope tanpa local scope

### wp-customer Reference (Correct Pattern):
```html
<div class="col-md-12" id="wpapp-companies-table-container">
    <div class="wpapp-companies-list-container">  <!-- ✅ LOCAL scope, NO wpapp-panel-content -->
        <table>...</table>
    </div>
</div>
```

## Solution: Opsi B

### After (Clear Separation):
```html
<div class="wpapp-datatable-layout" data-entity="agency">
    <div class="wpapp-row" id="wpapp-agency-container">  <!-- ✅ Entity-specific, explicit -->

        <!-- Left Panel: NO global wrapper -->
        <div class="wpapp-col-md-12" id="wpapp-agency-table-container">
            <div class="agency-datatable-wrapper">  <!-- ✅ LOCAL scope only -->
                <table>...</table>
            </div>
        </div>

        <!-- Right Panel: WITH global wrapper -->
        <div class="wpapp-col-md-5 wpapp-detail-panel" id="wpapp-agency-detail-panel">
            <div class="wpapp-panel-header">...</div>
            <div class="wpapp-panel-content">  <!-- ✅ Global scope untuk consistency -->
                <!-- Tabs or content -->
            </div>
        </div>

    </div>
</div>
```

**Benefits:**
1. ✅ Entity-specific ID - explicit, tidak ambigu
2. ✅ Left panel = full plugin control (seperti wp-customer)
3. ✅ Right panel = consistent base template (header, content, footer sama across entities)
4. ✅ Clear separation: local (left) vs global (right)

## Changes Implemented

### 1. Revert to Entity-Specific Container ID ✅
**File**: `wp-app-core/src/Views/DataTable/Templates/PanelLayoutTemplate.php`

**Line 47:** Reverted from generic to entity-specific
```php
// Before (Review-09):
<div class="wpapp-row" id="wpapp-datatable-container">

// After (Opsi B):
<div class="wpapp-row" id="wpapp-<?php echo esc_attr($entity); ?>-container">
```

**Result:**
- agency → `wpapp-agency-container`
- company → `wpapp-company-container`
- customer → `wpapp-customer-container`

**Reason:** Entity-specific ID lebih explicit, tidak ambigu

### 2. Remove wpapp-panel-content from Left Panel ✅
**File**: `wp-app-core/src/Views/DataTable/Templates/PanelLayoutTemplate.php`

**Lines 89-110:** Removed wrapper, updated documentation
```php
// Before:
private static function render_left_panel($config) {
    ?>
    <div class="wpapp-panel-content">  <!-- ❌ Global wrapper -->
        <?php
        do_action('wpapp_left_panel_content', $config);
        ?>
    </div>
    <?php
}

// After:
private static function render_left_panel($config) {
    /**
     * NO wrapper - plugins provide their own wrapper
     * Example: <div class="agency-datatable-wrapper">...</div>
     */
    do_action('wpapp_left_panel_content', $config);
}
```

**Changes:**
- ✅ Removed `wpapp-panel-content` wrapper
- ✅ Updated docblock: plugins MUST provide their own wrapper
- ✅ Example updated to show entity-specific wrapper pattern

**Reason:** Left panel = DataTable = full plugin control (like wp-customer)

### 3. Remove #wpapp-datatable-container CSS ✅
**File**: `wp-app-core/assets/css/datatable/wpapp-datatable.css`

**Removed Lines 172-179** (Desktop):
```css
/* ❌ Removed - No longer using generic ID */
#wpapp-datatable-container {
    display: flex;
    flex-wrap: nowrap;
    margin-right: -15px;
    margin-left: -15px;
    align-items: flex-start;
}
```

**Removed Lines 488-491** (Mobile):
```css
/* ❌ Removed - No longer needed */
#wpapp-datatable-container {
    flex-wrap: wrap;
}
```

**Kept:**
```css
/* ✅ Still used - Class selector for layout */
.wpapp-datatable-layout .wpapp-row {
    display: flex;
    flex-wrap: nowrap;
    margin-right: -15px;
    margin-left: -15px;
    align-items: flex-start;
}
```

**Reason:** Using entity-specific IDs now, generic CSS not needed

### 4. Add Local Wrapper in wp-agency ✅
**File**: `wp-agency/src/Views/DataTable/Templates/datatable.php`

**Line 35:** Changed to local scope wrapper
```php
// Before:
<div class="wpapp-datatable-wrapper">  <!-- ❌ Global scope -->

// After:
<div class="agency-datatable-wrapper">  <!-- ✅ Local scope -->
```

**Version:** Updated to 1.0.2

**Changelog Added:**
```
* 1.0.2 - 2025-10-25
* - Changed wpapp-datatable-wrapper to agency-datatable-wrapper (local scope)
* - Following wp-customer pattern (wpapp-companies-list-container)
* - Provides own wrapper styling instead of relying on global wpapp-panel-content
```

### 5. Create agency-datatable-wrapper CSS ✅
**File**: `wp-agency/assets/css/agency/agency-style.css`

**Lines 188-200:** Added wrapper styling (copied from wp-customer pattern)
```css
/* DataTable Wrapper - Local Scope (replaces global wpapp-panel-content) */
.agency-datatable-wrapper {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 5px;
    padding: 0; /* No padding - table fills container */
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

/* DataTables wrapper padding */
.agency-datatable-wrapper .dataTables_wrapper {
    padding: 20px;
}
```

**Lines 232-238:** Added responsive styling
```css
@media screen and (max-width: 782px) {
    /* DataTable wrapper responsive padding */
    .agency-datatable-wrapper {
        padding: 10px;
    }

    .agency-datatable-wrapper .dataTables_wrapper {
        padding: 10px;
    }
}
```

**Source:** Pattern copied from `wp-customer/assets/css/companies/companies.css` (.wpapp-companies-list-container)

## Comparison: Global vs Local Scope

### Left Panel (DataTable):

**Before (Tumpang Tindih):**
- `wpapp-col-md-12` (global container)
  - `wpapp-panel-content` (global wrapper) ❌
    - `wpapp-datatable-wrapper` (global wrapper) ❌
      - `table` (plugin content)

**After (Clear Local):**
- `wpapp-col-md-12` (global container)
  - `agency-datatable-wrapper` (local wrapper) ✅
    - `table` (plugin content)

### Right Panel (Detail):

**Consistent (Before & After):**
- `wpapp-col-md-5` (global container)
  - `wpapp-panel-header` (global header)
  - `wpapp-panel-content` (global wrapper) ✅
    - Tabs or content (plugin content)
  - `wpapp-panel-footer` (global footer)

## Why Different Treatment?

### Left Panel = DataTable:
- ✅ Content varies greatly between entities (different columns, filters, etc.)
- ✅ Each plugin needs full control over styling
- ✅ wp-customer uses local wrapper (`wpapp-companies-list-container`)
- ✅ No consistency needed - each entity is different

### Right Panel = Detail:
- ✅ Structure consistent across entities (header, content area, footer)
- ✅ Base template handles common elements (close button, tabs, etc.)
- ✅ `wpapp-panel-content` provides consistent padding for all detail panels
- ✅ Consistency is valuable - similar user experience

## Files Modified Summary

### wp-app-core (2 files):
1. ✅ `src/Views/DataTable/Templates/PanelLayoutTemplate.php`
   - Line 47: Revert to `wpapp-<?php echo esc_attr($entity); ?>-container`
   - Lines 89-110: Removed `wpapp-panel-content` wrapper
2. ✅ `assets/css/datatable/wpapp-datatable.css`
   - Removed lines 172-179: `#wpapp-datatable-container` desktop CSS
   - Removed lines 488-491: `#wpapp-datatable-container` mobile CSS

### wp-agency (2 files):
3. ✅ `src/Views/DataTable/Templates/datatable.php`
   - Line 35: Changed to `agency-datatable-wrapper`
   - Version updated to 1.0.2
   - Changelog updated
4. ✅ `assets/css/agency/agency-style.css`
   - Lines 188-200: Added `.agency-datatable-wrapper` styling
   - Lines 232-238: Added responsive CSS

## HTML Structure Comparison

### Before (Tumpang Tindih):
```html
<div class="wpapp-datatable-layout" data-entity="agency">
    <div class="wpapp-row" id="wpapp-datatable-container">  <!-- Generic -->
        <div class="wpapp-col-md-12" id="wpapp-agency-table-container">
            <div class="wpapp-panel-content">  <!-- Global di local context -->
                <div class="wpapp-datatable-wrapper">  <!-- Global lagi -->
                    <table id="agency-list-table">...</table>
                </div>
            </div>
        </div>
        <div class="wpapp-col-md-5 wpapp-detail-panel" id="wpapp-agency-detail-panel">
            <div class="wpapp-panel-content">  <!-- Global -->
                ...
            </div>
        </div>
    </div>
</div>
```

### After (Clear Separation):
```html
<div class="wpapp-datatable-layout" data-entity="agency">
    <div class="wpapp-row" id="wpapp-agency-container">  <!-- ✅ Explicit -->

        <!-- Left: LOCAL control -->
        <div class="wpapp-col-md-12" id="wpapp-agency-table-container">
            <div class="agency-datatable-wrapper">  <!-- ✅ LOCAL only -->
                <table id="agency-list-table">...</table>
            </div>
        </div>

        <!-- Right: GLOBAL consistency -->
        <div class="wpapp-col-md-5 wpapp-detail-panel" id="wpapp-agency-detail-panel">
            <div class="wpapp-panel-header">...</div>
            <div class="wpapp-panel-content">  <!-- ✅ Global untuk consistency -->
                ...
            </div>
        </div>

    </div>
</div>
```

## Testing Checklist
- [ ] Clear WordPress cache
- [ ] Clear PHP opcache
- [ ] Clear browser cache
- [ ] Visit wp-agency Disnaker menu:
  - [ ] DataTable displays correctly
  - [ ] White box container visible around table
  - [ ] No broken layout
  - [ ] Check DevTools: verify ID is `wpapp-agency-container`
  - [ ] Check DevTools: verify wrapper is `agency-datatable-wrapper`
  - [ ] Verify NO `wpapp-panel-content` in left panel
  - [ ] Verify YES `wpapp-panel-content` in right panel (detail)
- [ ] Test right panel (click row):
  - [ ] Panel slides in correctly
  - [ ] Header displays
  - [ ] Content area has correct padding (from wpapp-panel-content)
  - [ ] Tabs work (if enabled)
  - [ ] Close button works
- [ ] Test responsive (< 782px):
  - [ ] DataTable wrapper padding reduces to 10px
  - [ ] Layout doesn't break
  - [ ] No horizontal overflow
- [ ] No console errors
- [ ] No PHP errors in error log

## Related TODOs
- Supersedes: `wp-app-core/TODO/TODO-1179-rename-container-to-datatable-container.md`
- Related: `wp-agency/TODO/TODO-3075-restructure-datatable-templates-directory.md`
- Related: `wp-agency/TODO/TODO-3076-move-partials-to-datatable-templates.md`
- See: `wp-app-core/claude-chats/task-1179.md` (Review ulang Review-09)

## References
- Review-09: Lines 293-309
- Review ulang Review-09: Lines 310-354
- wp-customer reference: `src/Views/companies/list.php` (line 137)
- wp-customer CSS: `assets/css/companies/companies.css` (lines 248-253)

## Migration Notes for Other Plugins

**If wp-company or other plugins use this base template:**

1. Change wrapper class from `wpapp-datatable-wrapper` to `{plugin}-datatable-wrapper`
2. Create CSS for the wrapper (copy from wp-customer pattern)
3. No changes needed to wp-app-core templates

**Example for wp-company:**
```html
<div class="company-datatable-wrapper">
    <table id="company-list-table">...</table>
</div>
```

```css
.company-datatable-wrapper {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 5px;
    padding: 0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.company-datatable-wrapper .dataTables_wrapper {
    padding: 20px;
}
```

## Code Quality Notes
This change improves:
1. **Clarity** - Entity-specific IDs are explicit, not ambiguous
2. **Separation of Concerns** - Left panel = plugin control, Right panel = base template
3. **Consistency** - Follows wp-customer reference implementation
4. **Maintainability** - Clear which scope (global vs local) owns what
5. **Flexibility** - Plugins have full control over DataTable styling
6. **Simplicity** - Removed unnecessary global wrappers from left panel
