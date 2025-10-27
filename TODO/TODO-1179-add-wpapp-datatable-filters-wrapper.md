# TODO-1179: Add wpapp-datatable-filters Wrapper

## Status
✅ **COMPLETED** - 2025-10-25

## Requirement (Review-07)
Di wp-app-core:
1. Buat wrapper `wpapp-datatable-filters` di dalam `wpapp-filters-container`
2. Salin property `wpapp-datatable-filters` dari companies.css ke wpapp-datatable.css
3. Sehingga: `agency-filter-wrapper` ada di dalam `wpapp-datatable-filters`

## Changes Implemented

### 1. NavigationTemplate.php - Add Wrapper ✅
**File**: `wp-app-core/src/Views/DataTable/Templates/NavigationTemplate.php`

**Before:**
```html
<div class="wpapp-filters-container">
    <?php do_action('wpapp_dashboard_filters', $config, $config['entity']); ?>
</div>
```

**After:**
```html
<div class="wpapp-filters-container">
    <div class="wpapp-datatable-filters">
        <?php do_action('wpapp_dashboard_filters', $config, $config['entity']); ?>
    </div>
</div>
```

**Changes:**
- ✅ Added `wpapp-datatable-filters` wrapper inside `wpapp-filters-container`
- ✅ Plugin content now renders inside the wrapper via `wpapp_dashboard_filters` action

### 2. wpapp-datatable.css - CSS Properties ✅
**File**: `wp-app-core/assets/css/datatable/wpapp-datatable.css`

**Before:**
```css
.wpapp-filters-container .datatable-filters {
    /* properties */
}
```

**After:**
```css
.wpapp-filters-container .wpapp-datatable-filters {
    padding: 12px 20px;
    background: #fff;
    border-radius: 5px;
    border: 1px solid #e0e0e0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    display: flex;
    align-items: center;
    gap: 10px;
}

.wpapp-filters-container .wpapp-datatable-filters label {
    margin: 0;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.wpapp-filters-container .wpapp-datatable-filters select.status-filter {
    padding: 7px 12px;
    min-width: 150px;
    border-radius: 4px;
    border: 1px solid #ddd;
    background: #fff;
    font-size: 14px;
}
```

**Changes:**
- ✅ Renamed selector: `.datatable-filters` → `.wpapp-datatable-filters`
- ✅ Properties copied from `companies.css` (consistent with wp-customer)

## Final HTML Structure

### wp-app-core Structure (Global Scope):
```html
<div class="wpapp-filters-container">  <!-- Container -->
    <div class="wpapp-datatable-filters">  <!-- Filters wrapper with styling -->
        <!-- Plugin content via wpapp_dashboard_filters hook -->
    </div>
</div>
```

### wp-agency Structure (Complete):
```html
<div class="wpapp-filters-container">  <!-- wp-app-core -->
    <div class="wpapp-datatable-filters">  <!-- wp-app-core (new wrapper) -->
        <div class="agency-filter-wrapper">  <!-- wp-agency local scope -->
            <div class="agency-filter-group">
                <label class="agency-filter-label">Filter Status:</label>
                <select class="agency-filter-select">...</select>
            </div>
        </div>
    </div>
</div>
```

### wp-customer Structure (Reference):
```html
<div class="wpapp-filters-container">  <!-- wp-app-core -->
    <div class="wpapp-datatable-filters">  <!-- wp-app-core -->
        <label>Filter Status:</label>
        <select class="status-filter">...</select>
    </div>
</div>
```

## Class Naming Convention

### Global Scope (wp-app-core) - Prefix: `wpapp-`
- `wpapp-filters-container` - Outer container
- `wpapp-datatable-filters` - Inner wrapper with styling (white box, padding, border)

### Local Scope (wp-agency) - Prefix: `agency-`
- `agency-filter-wrapper` - Agency-specific wrapper
- `agency-filter-group` - Filter group container
- `agency-filter-label` - Label styling
- `agency-filter-select` - Select dropdown styling

### Local Scope (wp-customer) - Prefix: none/generic
- Direct label and select elements (simple structure)

## Benefits
1. ✅ Consistent structure across all plugins (wp-customer, wp-agency)
2. ✅ Global styling from wp-app-core (white box, padding, border, shadow)
3. ✅ Plugins can add local customization inside wrapper
4. ✅ Clean separation: global scope (wpapp-) vs local scope (plugin-)
5. ✅ Proper visual hierarchy and containment

## Files Modified
1. ✅ `wp-app-core/src/Views/DataTable/Templates/NavigationTemplate.php`
2. ✅ `wp-app-core/assets/css/datatable/wpapp-datatable.css`

## No Changes Required
- ✅ `wp-agency/src/Views/agency/partials/status-filter.php` - Already has proper structure
- ✅ wp-agency filter CSS - Already styled correctly

## Visual Improvements
- ✅ Filters now have consistent white box container
- ✅ Proper padding and spacing (12px 20px)
- ✅ Border and shadow for depth (matches wp-customer)
- ✅ Flex layout for proper alignment

## Testing Checklist
- [ ] Clear WordPress cache
- [ ] Clear browser cache
- [ ] Open wp-agency dashboard
- [ ] Verify filter has white box container with border
- [ ] Check DevTools - structure should show:
  - `wpapp-filters-container`
    - `wpapp-datatable-filters` (white box)
      - `agency-filter-wrapper`
- [ ] Compare with wp-customer - styling should match
- [ ] Verify filter still works (select changes update DataTable)

## Related TODOs
- See: `wp-agency/TODO/TODO-3071-fix-stats-cards-container-position.md`
- See: `wp-agency/TODO/TODO-3072-rename-agency-card-to-agency-stats-card.md`

## References
- Review-07 in `wp-app-core/claude-chats/task-1179.md`
- Source CSS: `wp-customer/assets/css/companies/companies.css` (`.wpapp-datatable-filters`)
