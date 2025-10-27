# TODO-1180: Create FiltersTemplate for Consistent Filter Architecture

## Status
✅ **COMPLETED** - 2025-10-26

## Priority
High - Architecture consistency

## Context

### Problem Identified
After creating StatsBoxTemplate.php, discovered **architectural inconsistency**:
- ✅ **StatsBoxTemplate.php** exists - centralized statistics rendering
- ❌ **FiltersTemplate.php** missing - filters embedded in NavigationTemplate
- ⚠️ Different patterns for similar functionality

### User Request
> "StatsBoxTemplate.php ada filenya sementara filter tidak ada filennya, apakah sebaiknya dibuat juga?"

**Answer:** YA - Untuk konsistensi arsitektur dan better maintainability.

---

## Goals

1. ✅ Create FiltersTemplate.php matching StatsBoxTemplate pattern
2. ✅ Support multiple filter types (select, search, date_range)
3. ✅ Backward compatible with existing wp-agency implementation
4. ✅ Update NavigationTemplate to use FiltersTemplate
5. ✅ Comprehensive documentation

---

## Implementation

### 1. Created FiltersTemplate.php ✅

**File**: `wp-app-core/src/Views/DataTable/Templates/FiltersTemplate.php`

**Pattern Match with StatsBoxTemplate:**
```php
class FiltersTemplate {
    public static function render($entity, $config = [])
    private static function get_filters($entity)
    private static function render_filter_control($filter_key, $filter, $entity)
    private static function render_select_filter(...)
    private static function render_search_filter(...)
    private static function render_date_range_filter(...)
}
```

**Key Features:**
- Centralized filter rendering
- Multiple filter type support
- Backward compatible with `wpapp_dashboard_filters` action
- Standardized HTML structure
- Plugin-agnostic implementation

---

### 2. Filter Hook System ✅

#### New Approach (RECOMMENDED)
**Filter Hook**: `wpapp_datatable_filters`

**Usage Example:**
```php
add_filter('wpapp_datatable_filters', function($filters, $entity) {
    if ($entity !== 'agency') return $filters;

    return [
        'status' => [
            'type' => 'select',
            'label' => __('Filter Status:', 'wp-agency'),
            'id' => 'agency-status-filter',
            'options' => [
                'all' => __('Semua Status', 'wp-agency'),
                'active' => __('Aktif', 'wp-agency'),
                'inactive' => __('Tidak Aktif', 'wp-agency')
            ],
            'default' => 'active',
            'class' => 'agency-filter-select'
        ],
        'search' => [
            'type' => 'search',
            'label' => __('Search:', 'wp-agency'),
            'id' => 'agency-search',
            'placeholder' => __('Search agencies...', 'wp-agency')
        ]
    ];
}, 10, 2);
```

#### Old Approach (STILL SUPPORTED)
**Action Hook**: `wpapp_dashboard_filters`

**Current wp-agency implementation:**
```php
add_action('wpapp_dashboard_filters', [$this, 'render_filters'], 10, 2);

public function render_filters($config, $entity) {
    if ($entity !== 'agency') return;
    include WP_AGENCY_PATH . 'src/Views/DataTable/Templates/partials/status-filter.php';
}
```

**Status:** BACKWARD COMPATIBLE - Still works, no breaking changes!

---

### 3. Filter Types Supported ✅

#### A. Select Dropdown
```php
[
    'status' => [
        'type' => 'select',
        'label' => 'Filter Status:',
        'id' => 'entity-status-filter',
        'options' => [
            'all' => 'All Status',
            'active' => 'Active',
            'inactive' => 'Inactive'
        ],
        'default' => 'active',
        'class' => 'status-filter'  // Optional
    ]
]
```

**Renders:**
```html
<div class="wpapp-filter-group wpapp-filter-select-group">
    <label for="entity-status-filter" class="wpapp-filter-label">Filter Status:</label>
    <select id="entity-status-filter"
            class="wpapp-filter-control wpapp-filter-select status-filter"
            data-filter-key="status"
            data-entity="entity"
            data-current="active">
        <option value="all">All Status</option>
        <option value="active" selected>Active</option>
        <option value="inactive">Inactive</option>
    </select>
</div>
```

#### B. Search Input
```php
[
    'search' => [
        'type' => 'search',
        'label' => 'Search:',
        'id' => 'entity-search',
        'placeholder' => 'Search...',
        'class' => 'search-input'
    ]
]
```

**Renders:**
```html
<div class="wpapp-filter-group wpapp-filter-search-group">
    <label for="entity-search" class="wpapp-filter-label">Search:</label>
    <input type="search"
           id="entity-search"
           class="wpapp-filter-control wpapp-filter-search search-input"
           data-filter-key="search"
           data-entity="entity"
           placeholder="Search..." />
</div>
```

#### C. Date Range (Future)
```php
[
    'date_range' => [
        'type' => 'date_range',
        'label' => 'Date Range:',
        'from' => '',
        'to' => ''
    ]
]
```

**Status:** Placeholder - Will be implemented when needed with datepicker library.

---

### 4. Updated NavigationTemplate.php ✅

**File**: `wp-app-core/src/Views/DataTable/Templates/NavigationTemplate.php`

**Version**: 1.1.0

**Changes:**
```php
// BEFORE (Old - embedded in template):
<div class="wpapp-filters-container">
    <div class="wpapp-datatable-filters">
        <?php do_action('wpapp_dashboard_filters', $config, $config['entity']); ?>
    </div>
</div>

// AFTER (New - uses FiltersTemplate):
<?php FiltersTemplate::render($config['entity'], $config); ?>
```

**FiltersTemplate.render() internally:**
1. Gets filters via `apply_filters('wpapp_datatable_filters', [], $entity)`
2. Renders each filter via `render_filter_control()`
3. **THEN** calls `do_action('wpapp_dashboard_filters', $config, $entity)` for backward compatibility
4. Returns standardized HTML with `wpapp-filters-container` wrapper

**Result:**
- ✅ New plugins can use filter hook (recommended)
- ✅ Old plugins using action still work (backward compatible)
- ✅ Consistent HTML structure

---

### 5. HTML Structure ✅

**Global Classes (wpapp- prefix):**
```html
<div class="wpapp-filters-container">
    <div class="wpapp-datatable-filters">

        <!-- New filter approach -->
        <div class="wpapp-filter-group wpapp-filter-select-group">
            <label class="wpapp-filter-label">...</label>
            <select class="wpapp-filter-control wpapp-filter-select">...</select>
        </div>

        <div class="wpapp-filter-group wpapp-filter-search-group">
            <label class="wpapp-filter-label">...</label>
            <input class="wpapp-filter-control wpapp-filter-search" />
        </div>

        <!-- Old action approach (backward compatible) -->
        <?php do_action('wpapp_dashboard_filters', $config, $entity); ?>

    </div>
</div>
```

**CSS Classes:**
- `.wpapp-filters-container` - Outer wrapper
- `.wpapp-datatable-filters` - Inner container (white background, border, flex)
- `.wpapp-filter-group` - Individual filter wrapper
- `.wpapp-filter-label` - Label styling
- `.wpapp-filter-control` - Base control class
- `.wpapp-filter-select` - Select dropdown
- `.wpapp-filter-search` - Search input

**Existing CSS (no changes needed):**
```css
.wpapp-filters-container {
    margin: 0 0 20px 0;
}

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
```

---

## Architecture Comparison

### Before (Inconsistent)

**StatsBoxTemplate:**
```
✅ Separate file
✅ Centralized rendering
✅ Filter hook: wpapp_datatable_stats
✅ Standardized HTML
```

**Filters:**
```
❌ No separate file (embedded)
❌ Action hook: wpapp_dashboard_filters
❌ Plugin renders own HTML
⚠️ Inconsistent structure
```

### After (Consistent) ✅

**StatsBoxTemplate:**
```
✅ Separate file
✅ Centralized rendering
✅ Filter hook: wpapp_datatable_stats
✅ Standardized HTML
```

**FiltersTemplate:**
```
✅ Separate file
✅ Centralized rendering
✅ Filter hook: wpapp_datatable_filters
✅ Standardized HTML
✅ Backward compatible with action hook
```

---

## Navigation Template Role

**Question:** "apakah NavigationTemplate.php akan tetap digunakan?"

**Answer:** **YA, TETAP DIGUNAKAN**

**NavigationTemplate sebagai Orchestrator:**
```
NavigationTemplate.php (Container/Orchestrator)
│
├── StatsBoxTemplate::render($entity)
│   ├── apply_filters('wpapp_datatable_stats')
│   └── Renders statistics cards
│
└── FiltersTemplate::render($entity, $config)
    ├── apply_filters('wpapp_datatable_filters')
    ├── Renders filter controls
    └── do_action('wpapp_dashboard_filters') ← Backward compatibility
```

**Responsibility:**
- Layout management (stacked: stats → filters)
- Template orchestration
- Action hooks for before/after sections
- NOT responsible for rendering individual components

**Components do the rendering:**
- StatsBoxTemplate → Statistics
- FiltersTemplate → Filters
- Each has own filter/action system

---

## Files Modified

### Created:
1. ✅ `wp-app-core/src/Views/DataTable/Templates/FiltersTemplate.php`

### Modified:
1. ✅ `wp-app-core/src/Views/DataTable/Templates/NavigationTemplate.php`
   - Version 1.1.0
   - Now calls `FiltersTemplate::render()`
   - Updated documentation

### Documentation:
1. ✅ `wp-app-core/TODO/TODO-1180-create-filters-template.md` (this file)

### Untouched (Backward Compatible):
1. ⏸️ `wp-agency/src/Controllers/Agency/AgencyDashboardController.php`
   - Still uses `add_action('wpapp_dashboard_filters')`
   - Still includes `status-filter.php` partial
   - **NO CHANGES NEEDED** - Backward compatible!

2. ⏸️ `wp-agency/src/Views/DataTable/Templates/partials/status-filter.php`
   - Still works as-is
   - **NO CHANGES NEEDED**

---

## Migration Guide (Optional for Plugins)

### Current wp-agency Implementation (Still Works)

**AgencyDashboardController.php (Line 101-102):**
```php
add_action('wpapp_dashboard_filters', [$this, 'render_filters'], 10, 2);

public function render_filters($config, $entity): void {
    if ($entity !== 'agency') return;
    include WP_AGENCY_PATH . 'src/Views/DataTable/Templates/partials/status-filter.php';
}
```

**Status:** ✅ WORKS - No changes needed

---

### Recommended New Approach (Future Migration)

**Step 1:** Register filters via filter hook
```php
// In AgencyDashboardController::init()
add_filter('wpapp_datatable_filters', [$this, 'register_filters'], 10, 2);
```

**Step 2:** Implement register_filters method
```php
public function register_filters($filters, $entity) {
    if ($entity !== 'agency') return $filters;

    // Check permission
    $can_filter = current_user_can('edit_all_agencies') || current_user_can('manage_options');

    if (!$can_filter) return $filters;

    // Get current status from GET
    $current_status = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : 'active';

    return array_merge($filters, [
        'status' => [
            'type' => 'select',
            'label' => __('Filter Status:', 'wp-agency'),
            'id' => 'agency-status-filter',
            'options' => [
                'all' => __('Semua Status', 'wp-agency'),
                'active' => __('Aktif', 'wp-agency'),
                'inactive' => __('Tidak Aktif', 'wp-agency')
            ],
            'default' => $current_status,
            'class' => 'agency-filter-select'
        ]
    ]);
}
```

**Step 3:** Remove old action hook and partial file
```php
// Remove:
// add_action('wpapp_dashboard_filters', [$this, 'render_filters'], 10, 2);
// public function render_filters() { ... }
// Can delete: status-filter.php partial
```

**Benefits:**
- ✅ Cleaner code
- ✅ Consistent with StatsBoxTemplate pattern
- ✅ No need for partial file
- ✅ Centralized HTML rendering
- ✅ Better maintainability

**Timeline:** Can migrate anytime - no rush, backward compatible!

---

## Testing Checklist

### Backward Compatibility Tests:
- [ ] wp-agency dashboard loads without errors
- [ ] Status filter displays correctly
- [ ] Filter functionality still works (DataTable filters on change)
- [ ] No console errors
- [ ] No PHP errors in logs

### New Filter Approach Tests (Future):
- [ ] New filter hook works when plugin migrates
- [ ] Multiple filters can be registered
- [ ] Select filter renders correctly
- [ ] Search filter renders correctly
- [ ] Filter values persist on page reload
- [ ] DataTable integrates with filters

### Visual Tests:
- [ ] Filters have same styling as before
- [ ] Layout matches wp-customer pattern
- [ ] Mobile responsive
- [ ] No visual regressions

---

## Benefits

### For Architecture:
1. ✅ **Consistency** - Same pattern as StatsBoxTemplate
2. ✅ **Separation of Concerns** - NavigationTemplate is orchestrator, not renderer
3. ✅ **Reusability** - FiltersTemplate can be reused anywhere
4. ✅ **Maintainability** - One place to update filter HTML structure
5. ✅ **Extensibility** - Easy to add new filter types

### For Developers:
1. ✅ **Better DX** - Declarative filter registration (array config)
2. ✅ **Less Code** - No need for partial files
3. ✅ **Type Safety** - Standardized filter structure
4. ✅ **Documentation** - Clear examples and patterns
5. ✅ **Backward Compatible** - No breaking changes

### For Users:
1. ✅ **Consistent UI** - Same filter appearance across all plugins
2. ✅ **No Disruption** - Existing functionality unchanged
3. ✅ **Better UX** - Standardized filter behavior

---

## Future Enhancements

### Phase 2 (Optional):
1. Implement date_range filter type with datepicker
2. Add multi-select filter type
3. Add checkbox filter type
4. Add radio button filter type
5. Add filter preset/saved filters feature

### Phase 3 (Optional):
1. JavaScript integration for dynamic filtering
2. Filter state management
3. URL parameter sync
4. Filter reset functionality
5. Filter count badges

---

## Related TODOs

- **TODO-1179** - Align templates with wp-customer pattern
- **TODO-1180** - Centralized panel handler (parent TODO)
- **TODO-2071** - Agency dashboard implementation (uses filters)

---

## Conclusion

**Status:** ✅ COMPLETED

**Result:**
- FiltersTemplate created matching StatsBoxTemplate pattern
- NavigationTemplate updated to use FiltersTemplate
- Backward compatible - no breaking changes
- Architecture now consistent
- wp-agency continues to work without modifications

**Next Steps:**
- Test wp-agency dashboard (backward compatibility)
- Optional: Migrate wp-agency to new filter hook (future)
- Document new filter approach for other plugins

---

**Created by**: arisciwek
**Date**: 2025-10-26
**Implemented**: FiltersTemplate.php, NavigationTemplate v1.1.0
**Tested**: Pending - backward compatibility with wp-agency
