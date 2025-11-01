# TODO List for WP App Core Plugin

## TODO-1192: Migrate Platform Staff to Centralized DataTable System ✅ COMPLETED

**Status**: ✅ COMPLETED
**Priority**: HIGH
**Created**: 2025-11-01
**Completed**: 2025-11-01
**Plugin**: wp-app-core
**Category**: Architecture, Refactoring, DataTable System, Plug & Play Pattern

---

### Summary

Platform Staff dashboard berhasil dimigrasikan dari implementasi custom/manual ke centralized DataTable system dengan mengikuti pola wp-agency (menu Disnaker). Implementasi mencakup **major architectural refactoring** untuk menghilangkan anti-pattern hardcoded hooks dan menerapkan true "Plug & Play" pattern.

### Implementation Completed

**✅ Step 1: Create PlatformStaffDataTableModel**
- File: `/wp-app-core/src/Models/Platform/PlatformStaffDataTableModel.php`
- Server-side processing implementation
- Search and filtering support
- Status removed from DataTable columns (moved to filter)

**✅ Step 2: Create PlatformStaffDashboardController**
- File: `/wp-app-core/src/Controllers/Platform/PlatformStaffDashboardController.php`
- Main dashboard using `DashboardTemplate::render()`
- Registered hooks for stats, tabs, filters, panels
- AJAX endpoints for dashboard data
- Fixed: Fatal error calling wrong method (line 323)

**✅ Step 3: Create View Structure**
- Partials: `header-title.php`, `header-buttons.php`, `stat-cards.php`
- Tabs: `info.php`, `placeholder.php`
- Created: Generic `status-filter.php` partial
- Created: Missing `datatable.php` template

**✅ Step 4: Create Assets**
- CSS: `platform-staff-header-cards.css`, `platform-staff-filter.css`
- JavaScript: `platform-staff-datatable.js`
- All assets follow centralized pattern

**✅ Step 5: Update Dependencies**
- File: `/wp-app-core/includes/class-dependencies.php`
- Added `enqueue_platform_staff_assets()` method
- Proper asset loading on platform staff pages

**✅ Step 6: Permission Fix**
- Fixed directory permission: `700` → `755` on partials directory
- Filter dropdown now displays correctly

### Major Architectural Refactoring (Critical Work)

**Problem Identified**: User raised concern about hardcoded `wpapp_datatable_allowed_hooks` array being an anti-pattern - plugins should NOT modify core foundation.

**User Quote**:
> "plugin tidak boleh merubah pondasi, ibaratnya wp-app-core menyediakan container, plugin membuat 'sesuatu' dan otomatis masuk ke container tersebut"

**Solution Implemented**: **Plug & Play Pattern**

**Changes Made**:

1. **DashboardTemplate.php** - Added auto-asset loading
   ```php
   private static function ensure_assets_loaded() {
       if (wp_script_is('wpapp-panel-manager', 'enqueued')) return;
       if (wp_script_is('wpapp-panel-manager', 'done')) return;

       // Force enqueue assets automatically
       \WPAppCore\Controllers\DataTable\DataTableAssetsController::force_enqueue();
   }
   ```

2. **DataTableAssetsController.php** - Removed hardcoded hooks
   ```php
   // REMOVED: 30+ lines of hardcoded plugin hooks
   // NOW: Assets loaded automatically by DashboardTemplate::ensure_assets_loaded()
   // Manual override available via filter if needed
   ```

3. **wp-agency/MenuManager.php** - Removed plugin registration
   ```php
   // REMOVED: add_filter('wpapp_datatable_allowed_hooks', ...)
   // NOW: Just call DashboardTemplate::render() - assets loaded automatically!
   ```

4. **Code Cleanup** - Removed 60+ lines of deprecated code
   - Deleted deprecated panel handler code from `class-dependencies.php`
   - Removed debug logs from 4 files (18 lines total)

### CSS Global Variables Implementation

**Added comprehensive CSS Custom Properties** for consistent theming:

```css
:root {
    /* DataTable Controls */
    --wpapp-dt-control-padding: 8px 12px;
    --wpapp-dt-control-font-size: 13px;
    --wpapp-dt-control-border: 1px solid #ddd;

    /* Filter Controls */
    --wpapp-filter-padding: 10px 15px;
    --wpapp-filter-margin: 0 0 15px 0;
    --wpapp-filter-gap: 10px;
    --wpapp-filter-label-font-weight: 600;
    --wpapp-filter-select-width: 200px;

    /* Pagination Buttons */
    --wpapp-paginate-button-padding: 6px 12px;
    --wpapp-paginate-button-bg: #fff;
    --wpapp-paginate-button-current-bg: #2271b1;
    /* ... and more */
}
```

**Applied to** (lines 242-315 in wpapp-datatable.css):
- `.wpapp-filters-container` - Filter container styling
- `.wpapp-filter-label` - Label styling
- `.wpapp-filter-select` - Dropdown styling
- `.wpapp-filter-control.wpapp-filter-search` - Search input styling
- All pagination button states (normal, hover, current, disabled)

### Features Implemented

**Statistics Cards**:
- Total Staff
- Active Staff
- Inactive Staff
- AJAX loading with smooth transitions

**Tabs**:
- Info Tab - Staff details display
- Placeholder Tab - Empty for future expansion

**DataTable Features**:
- Server-side processing
- Search functionality
- **Status filter dropdown** (removed from column)
- Column sorting
- Row click to open detail panel
- Responsive design

### Testing Results

**Dashboard**: ✅ All components render correctly
**DataTable**: ✅ Server-side processing works
**Status Filter**: ✅ Filter dropdown displays and functions
**Detail Panel**: ✅ Opens with staff details
**Tabs**: ✅ Both tabs render correctly
**Assets**: ✅ Auto-loaded by container (Plug & Play)
**Permissions**: ✅ Directory permissions fixed
**Integration**: ✅ All AJAX endpoints work

### Architecture Benefits Achieved

**Before (Anti-Pattern)**:
- ❌ Plugins register hooks to core
- ❌ Core maintains hardcoded array
- ❌ Tight coupling
- ❌ Core needs updates for every plugin

**After (Plug & Play)**:
- ✅ Container auto-detects usage
- ✅ Zero plugin registration needed
- ✅ Zero coupling
- ✅ Plugin just calls DashboardTemplate::render()

**Pattern Flow**:
```
Plugin calls DashboardTemplate::render()
    ↓
Template detects first usage
    ↓
Auto-enqueues assets via ensure_assets_loaded()
    ↓
Assets available immediately
    ↓
Zero configuration from plugin!
```

### Files Modified

**Models**:
- `PlatformStaffDataTableModel.php` - Removed status column

**Controllers**:
- `PlatformStaffDashboardController.php` - Fixed AJAX handler, cleaned debug logs
- `DataTableAssetsController.php` - Removed hardcoded hooks, added Plug & Play pattern

**Templates**:
- `DashboardTemplate.php` - Added auto-asset loading
- `datatable.php` - Created missing template
- `status-filter.php` - Created generic partial

**Assets**:
- `wpapp-datatable.css` - Added 200+ lines CSS variables and filter styling
- `platform-staff-datatable.js` - Removed status column

**Core**:
- `class-dependencies.php` - Cleaned 60 lines deprecated code, added platform staff assets

**Plugins**:
- `wp-agency/MenuManager.php` - Removed hook registration (13 lines)

### Code Quality Metrics

- **Code Removed**: 90+ lines of anti-pattern code deleted
- **Code Added**: 280+ lines of proper implementation
- **Architecture**: Anti-pattern → Plug & Play ✅
- **Coupling**: Tight → Zero ✅
- **Maintainability**: Poor → Excellent ✅
- **Consistency**: Manual → Automated ✅

### Related TODOs

- **TODO-1191**: PlatformStaffController separation (completed)
- **TODO-1187**: Container structure simplification (completed)

### Key Lessons

1. ✅ **"Container provides slot, plugin fills slot"** - Correct architecture
2. ✅ **Auto-detection > Manual registration** - Plug & Play is superior
3. ✅ **Zero configuration** - Best developer experience
4. ✅ **Plugins should NOT modify core** - Open/Closed Principle

See: [TODO/TODO-1192-migrate-platform-staff-to-centralized-datatable.md](TODO/TODO-1192-migrate-platform-staff-to-centralized-datatable.md)

---

## TODO-3089: Simplify Architecture - Remove TabViewTemplate & NavigationTemplate ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-29
**Completed**: 2025-10-29
**Priority**: HIGH
**Category**: Architecture Simplification, Code Cleanup
**Related**: Task-3086 (Pembahasan-05), TODO-1188, TODO-1186

**Summary**: Architectural decision to remove TabViewTemplate and NavigationTemplate classes from wp-app-core. Based on "no active users + still development" principle, allowing breaking changes for simpler boilerplate with proper scope separation.

**Problem Analysis (Pembahasan-05)**:
1. ❌ TabViewTemplate class was NOT USED by wp-agency
2. ❌ NavigationTemplate was just orchestrator without visual container
3. ❌ StatsBoxTemplate had dual rendering (hook + filter) - only hook used
4. ❌ Over-engineering: wrapper classes without value
5. ❌ CSS prefix violations: statistics classes lacked wpapp- prefix

**Changes Made**:

**Phase 1: Cleanup wp-agency**
- Removed 'template' key from register_tabs() (not used)
- Updated docblocks to "entity-owned hook pattern"
- No TabViewTemplate references

**Phase 2: Cleanup wp-app-core**
- ❌ DELETED: TabViewTemplate.php (202 lines)
- ❌ DELETED: NavigationTemplate.php
- ❌ DELETED: docs/datatable/TabViewTemplate.md (683 lines)
- ✅ DashboardTemplate: Direct calls to StatsBoxTemplate + FiltersTemplate

**Hotfix: TabSystemTemplate**
- Added support for 2 patterns:
  1. Direct Inclusion (with 'template' key)
  2. Hook-Based AJAX (without 'template' key)
- Version: 1.0.0 → 1.1.0

**CSS Prefix Fix**:
- Added wpapp- prefix to ALL statistics classes
- StatsBoxTemplate: statistics-cards → wpapp-statistics-cards
- wpapp-datatable.css: All .stats-* → .wpapp-stats-*
- Version: 1.0.0 → 1.1.0

**StatsBoxTemplate Simplification**:
- ❌ DELETED: get_stats() method (wpapp_datatable_stats filter)
- ❌ DELETED: render_stat_box() method (HTML rendering)
- ❌ DELETED: All wpapp-stats-* CSS selectors (76 lines)
- ✅ NOW: Pure infrastructure (container + hook only)
- Plugins render with local scope classes (agency-*, customer-*)
- Version: 1.1.0 → 1.2.0
- Code reduction: 187 → 90 lines (-52%)

**Final Architecture**:

**Entity-Owned Hook Pattern** (Simple):
```php
// AgencyDashboardController provides hooks directly
do_action('wpapp_tab_view_content', 'agency', $tab_id, $data);
do_action('wpapp_tab_view_after_content', 'agency', $tab_id, $data);

// StatsBoxTemplate provides container + hook only
<div class="wpapp-statistics-container">
    <?php do_action('wpapp_statistics_cards_content', $entity); ?>
</div>
```

**Benefits Achieved**:
- ✅ No dead code (TabViewTemplate, NavigationTemplate deleted)
- ✅ Simpler architecture (no wrapper classes)
- ✅ Clear ownership (entities own hooks)
- ✅ Proper scope separation (wpapp- = global, plugin- = local)
- ✅ Infrastructure vs Implementation pattern enforced
- ✅ Total code reduction: 1,000+ lines removed

**Files Modified/Deleted**:
- wp-agency: AgencyDashboardController.php (docblocks updated)
- wp-app-core: TabViewTemplate.php (DELETED)
- wp-app-core: NavigationTemplate.php (DELETED)
- wp-app-core: DashboardTemplate.php (v1.1.0)
- wp-app-core: TabSystemTemplate.php (v1.1.0)
- wp-app-core: StatsBoxTemplate.php (v1.2.0, simplified)
- wp-app-core: wpapp-datatable.css (v1.2.0, removed stats selectors)

**Design Philosophy**:
> **"No active users + still development"** → Simplify aggressively
> - Remove dead code
> - Entities own their hooks
> - No forced patterns
> - Simple > Abstraction

**Related TODOs**:
- TODO-1188: ❌ OBSOLETE (TabViewTemplate deleted)
- TODO-1186: ❌ OBSOLETE (TabViewTemplate deleted)

See: [wp-agency/TODO/TODO-3089-simplify-remove-tabviewtemplate.md](/home/mkt01/Public/wppm/public_html/wp-content/plugins/wp-agency/TODO/TODO-3089-simplify-remove-tabviewtemplate.md)

---
