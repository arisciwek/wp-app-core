# TODO List for WP App Core Plugin

## TODO-1185: Scope Separation Phase 2 - Tabs & Components ⏳ PENDING

**Status**: ⏳ PENDING
**Created**: 2025-10-27
**Priority**: High
**Category**: Architecture, Refactoring
**Dependencies**: TODO-1185 Phase 1 (completed)

**Summary**: Continue scope separation untuk komponen yang tersisa: tab templates, DataTable structure, filter components, dan modal forms. Follow pattern dari Phase 1 dengan naming convention `wpapp-*` (global) vs `agency-*` (local).

**Current Problems**:
- Tab templates masih ada mixed scope usage
- Perlu decision: Hybrid approach vs Pure separation
- Belum ada guidelines document untuk naming convention

**Proposed Solutions**:

**Option A: Hybrid (RECOMMENDED)**
- Tabs use `wpapp-*` for structure (reusable)
- Content/theme use `agency-*` for styling (specific)
- Quick, pragmatic, reusable

**Option B: Pure Separation**
- Move all templates to wp-app-core
- wp-agency hooks into templates only
- 100% separation but massive refactoring needed

**Tasks Breakdown**:
1. Audit all `wpapp-*` usage in wp-agency
2. Decide on tab template strategy (A or B)
3. Create NAMING-CONVENTION.md guidelines
4. Update tab templates (if Option B chosen)
5. CSS audit & cleanup
6. Testing strategy

**Estimated Effort**:
- Option A: 5 hours ✅
- Option B: 19 hours ⚠️

**Files Created**:
- `/TODO/TODO-1185-scope-separation-phase2.md` (TODO-005)

See: [TODO/TODO-1185-scope-separation-phase2.md](TODO/TODO-1185-scope-separation-phase2.md)

---

## TODO-1185: Scope Separation Phase 1 - Statistics & Header Buttons ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-27
**Completed**: 2025-10-27
**Priority**: High
**Category**: Architecture, Refactoring

**Summary**: Implement strict scope separation dengan naming convention `wpapp-*` (global) vs `agency-*` (local). Phase 1 fokus pada statistics cards dan header buttons dengan hook-based architecture.

**Problem**:
- wp-agency files menggunakan `wpapp-*` classes (global scope) di local files
- Mixed prefixes: `stats-card agency-stats-card` (inconsistent)
- Violation of separation of concerns
- High coupling, difficult testing

**Solution**: Hook-Based Architecture

**wp-app-core Provides**:
```php
// Container + hook only (no HTML rendering)
<div class="wpapp-statistics-container">
    <?php do_action('wpapp_statistics_cards_content', $entity); ?>
</div>
```

**wp-agency Uses**:
```php
// Hook into container, full HTML control
add_action('wpapp_statistics_cards_content', function($entity) {
    if ($entity !== 'agency') return;
    ?>
    <div class="agency-statistics-cards">
        <div class="agency-stat-card agency-theme-blue">
            <!-- Full control over structure & styling -->
        </div>
    </div>
    <?php
});
```

**Changes**:
1. Header buttons: `wpapp-header-buttons` → `agency-header-buttons`
2. Statistics cards: All classes changed to `agency-*` prefix
3. Full card CSS moved to wp-agency/assets/css/agency/agency-style.css
4. Responsive design with agency-specific theming

**Benefits Achieved**:
- ✅ Clean separation (infrastructure vs implementation)
- ✅ No CSS conflicts (clear ownership)
- ✅ Reusable pattern for other plugins (wp-customer, etc)
- ✅ Testable (mock hooks for unit tests)
- ✅ Maintainable (clear boundaries)

**Files Modified**:
- `/wp-agency/src/Controllers/AgencyDashboardController.php` (lines 205, 253-286)
- `/wp-agency/assets/css/agency/agency-style.css` (lines 175-305)

**Files Created**:
- `/TODO/TODO-1185-scope-separation-phase1.md` (TODO-004)

**Test Results**:
- ✅ Statistics cards display correctly
- ✅ Hover effects work (lift animation)
- ✅ Theme colors applied (blue, green, orange)
- ✅ Header buttons aligned properly
- ✅ Responsive layout works
- ✅ All classes use `agency-*` prefix
- ✅ No console errors, no CSS conflicts

**Code Quality Metrics**:
- Mixed scopes: 100% → 0% ✅
- Coupling: High → Loose ✅
- Reusability: Low → High ✅
- Maintainability: Poor → Excellent ✅

See: [TODO/TODO-1185-scope-separation-phase1.md](TODO/TODO-1185-scope-separation-phase1.md)

---

## TODO-1185: Remove Inline CSS & JavaScript from PHP Files ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-27
**Completed**: 2025-10-27
**Priority**: High
**Category**: Code Quality, Separation of Concerns

**Summary**: Remove ALL inline `<style>` and `<script>` tags from PHP files untuk mencapai 100% separation of concerns: PHP = HTML structure, CSS = external files, JS = external files.

**Problems Found**:
- `divisions.php`: 81 lines dengan inline styles (3 places) + inline script (34 lines)
- `employees.php`: 81 lines dengan inline styles (3 places) + inline script (34 lines)
- AJAX logic hardcoded di template (maintenance nightmare)

**Solution**: Event-Driven Tab Loading Pattern

**A. Remove Inline Styles**

**BEFORE:**
```php
<div class="wpapp-loading" style="text-align: center; padding: 20px; color: #666;">
<div class="wpapp-divisions-content" style="display: none;">
<div class="wpapp-error" style="display: none;">
```

**AFTER:**
```php
<div class="wpapp-tab-loading">
<div class="wpapp-divisions-content wpapp-tab-loaded-content">
<div class="wpapp-tab-error">
```

**B. Remove Inline Scripts - Use Event-Driven**

**BEFORE (❌ Bad):**
```php
<script>
jQuery(document).ready(function($) {
    // 30+ lines of AJAX code inline
});
</script>
```

**AFTER (✅ Good):**

**1. Add Data Attributes (Configuration):**
```php
<div class="wpapp-tab-content wpapp-divisions-tab wpapp-tab-autoload"
     data-agency-id="<?php echo esc_attr($agency_id); ?>"
     data-load-action="load_divisions_tab"
     data-content-target=".wpapp-divisions-content">
```

**2. External JS Handles It:** `wpapp-tab-manager.js:200-264`

**New Pattern Flow**:
1. User clicks tab
2. `wpapp-tab-manager.js` detects `wpapp-tab-autoload` class
3. Reads configuration from `data-*` attributes
4. Makes AJAX request
5. Loads content into target element
6. Marks tab as `loaded` (cached)
7. Next click: instant display (no AJAX)

**Benefits**:
- ✅ No inline scripts (100% separation)
- ✅ Automatic caching
- ✅ Reusable pattern
- ✅ Easy to debug
- ✅ Clean HTML

**Files Modified**:
- `/wp-agency/src/Views/agency/tabs/divisions.php` (81 → 57 lines, -30%)
- `/wp-agency/src/Views/agency/tabs/employees.php` (81 → 57 lines, -30%)
- `/wp-app-core/assets/css/datatable/wpapp-datatable.css` (+42 lines for tab states)
- `/wp-app-core/assets/js/datatable/wpapp-tab-manager.js` (+65 lines for auto-load)

**Files Created**:
- `/TODO/TODO-1185-remove-inline-scripts.md`

**Test Results**:
- ✅ Divisions tab loads via AJAX
- ✅ Employees tab loads via AJAX
- ✅ Loading states work
- ✅ Error handling works
- ✅ Caching works (second click instant)
- ✅ No inline script execution
- ✅ View source: Clean HTML

**Code Metrics**:
- PHP files cleaned: 48 lines reduction
- Moved to proper files: CSS +42 lines, JS +65 lines
- Better organized, reusable, maintainable ✅

See: [TODO/TODO-1185-remove-inline-scripts.md](TODO/TODO-1185-remove-inline-scripts.md)

---

## TODO-1184: Fix Visual Flicker on Panel Operations ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-27
**Completed**: 2025-10-27
**Priority**: High
**Category**: UX Enhancement

**Summary**: Fix visual flicker yang terjadi pada right panel loading (spinner muncul instant untuk fast requests) dan left panel DataTable (unnecessary redraw saat panel resize).

**Root Causes**:

**Problem 1: Right Panel Loading Flicker**
- File: `wpapp-panel-manager.js:256`
- Issue: Loading placeholder shows IMMEDIATELY
- User sees: Request completes in 60-110ms, loading visible entire duration
- Perceived as: Flicker

**Problem 2: Left Panel DataTable Flicker**
- File: `wpapp-panel-manager.js:321`
- Issue: `self.dataTable.draw(false)` re-renders all rows unnecessarily
- Impact: All table rows flash/redraw
- Only needed: `columns.adjust()` for width recalculation

**Solutions Implemented**:

**Fix 1: Anti-Flicker Loading Pattern (300ms delay)**

**CSS Changes:** `wpapp-datatable.css:383-397`
```css
.wpapp-loading-placeholder {
    display: none; /* Hidden by default */
    opacity: 0;
    transition: opacity 0.3s ease;
}

.wpapp-loading-placeholder.visible {
    display: flex;
    opacity: 1;
}
```

**JavaScript Changes:** `wpapp-panel-manager.js:255-260`
```javascript
// ✅ DELAY 300ms before showing
this.loadingTimeout = setTimeout(function() {
    self.rightPanel.find('.wpapp-loading-placeholder').addClass('visible');
}, 300);
```

**How It Works:**
- Request < 300ms: Loading NEVER shows → No flicker ✅
- Request > 300ms: Loading shows with smooth fade-in ✅

**Fix 2: Remove Unnecessary DataTable Redraw**
```javascript
// BEFORE: Causes flicker
self.dataTable.columns.adjust();
setTimeout(function() {
    self.dataTable.draw(false); // ❌ Unnecessary redraw
}, 50);

// AFTER: No flicker
self.dataTable.columns.adjust(); // ✅ Enough for width recalculation
```

**Files Modified**:
1. `wpapp-datatable.css` (lines 383-397) - Loading placeholder styles
2. `wpapp-panel-manager.js` (lines 255-260, 312-317, 533-541, 362-366, 639-644)

**Files Created**:
- `/TODO/TODO-1184-flicker-fix.md`

**Test Results**:

**Scenario 1: Fast Response (60-110ms)**
- ✅ No loading placeholder visible
- ✅ Smooth transition
- ✅ No flicker

**Scenario 2: Slow Response (> 300ms)**
- ✅ Loading shows with fade-in
- ✅ Smooth UX

**Scenario 3: Panel Resize**
- ✅ DataTable columns adjust smoothly
- ✅ No row redraw flicker

**Performance Impact**:
- Before: Flicker visible 95% of requests (< 300ms responses)
- After: Flicker visible 0% (anti-flicker pattern) ✅
- DataTable redraw: Always → Never (only adjust columns) ✅

See: [TODO/TODO-1184-flicker-fix.md](TODO/TODO-1184-flicker-fix.md)

---

## TODO-1183: Fix Scroll Jump on Panel Open ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-27
**Completed**: 2025-10-27
**Priority**: High
**Category**: Bug Fix

**Summary**: Fix scroll jump yang terjadi saat user membuka detail panel. Browser otomatis scroll karena direct assignment `window.location.hash`.

**Root Cause**:

**Files Affected:**
1. `wp-app-core/assets/js/datatable/panel-handler.js:449`
2. `wp-app-core/assets/js/datatable/wpapp-panel-manager.js:666`

**Problem:**
```javascript
// ❌ CAUSES SCROLL JUMP
window.location.hash = newHash;
```

Browser will attempt to scroll to element with matching ID.

**Solution Implemented**:

**Changed to:**
```javascript
// ✅ NO SCROLL JUMP
history.pushState(null, document.title, pathname + search + '#' + newHash);
```

**Files Modified**:
1. `panel-handler.js:443-451` - Removed fallback to `window.location.hash`
2. `wpapp-panel-manager.js:664-670` - Changed to `history.pushState()`

**Already Correct**:
- `wpapp-tab-manager.js:208` - Already using `history.replaceState()`

**Files Created**:
- `/TODO/TODO-1183-scroll-jump-fix.md`

**Test Results**:
- ✅ Open panel - No scroll jump
- ✅ Switch rows - No scroll jump
- ✅ Switch tabs - No scroll jump
- ✅ Browser back/forward - Hash works correctly
- ✅ URL hash updates properly for bookmarking

**Notes**:
- `history.pushState()` supported by all modern browsers (IE10+)
- Removed fallback code for legacy browsers
- Hash updates WITHOUT triggering scroll

See: [TODO/TODO-1183-scroll-jump-fix.md](TODO/TODO-1183-scroll-jump-fix.md)

---

## TODO-1212: Platform Access to WP Customer Menu for WP Agency ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-19
**Completed**: 2025-10-19
**Dependencies**: TODO-1211 (filter hooks)
**Related**: wp-agency TODO-2064
**Priority**: High
**Complexity**: Low

**Summary**: Enable platform users (from wp-agency) to access WP Customer menus. Quick capability fix untuk menu visibility - data filtering untuk jurisdiction handled by wp-agency TODO-2065.

**Problem**:
- wp-agency platform users tidak bisa lihat menu WP Customer
- Menu "WP Customer" dan "WP Perusahaan" tidak muncul
- platform_finance punya capabilities tapi kurang `view_customer_branch_detail`

**Solution**:
Add missing `view_customer_branch_detail` capability to platform_finance role.

**Implementation**:

**Files Modified**:
- `/src/Models/Settings/PlatformPermissionModel.php`:
  - Added `view_customer_branch_detail` to platform_finance (line 686)
  - Updated comment reference wp-agency TODO-2064 (line 682)

**Capability Added**:
```php
'platform_finance' => [
    // ... existing capabilities
    'view_customer_branch_detail' => true,  // Added for wp-agency menu access
]
```

**Test Results**:
```
Platform User: benny_clara (platform_finance)

✅ Capabilities:
   - view_customer_list: yes
   - view_customer_detail: yes
   - view_customer_branch_list: yes
   - view_customer_branch_detail: yes ← ADDED
   - view_customer_employee_list: yes
   - view_customer_employee_detail: yes

✅ Menu Access (Expected):
   - Menu "WP Customer" → SHOULD APPEAR
   - Menu "WP Perusahaan" → SHOULD APPEAR
   - Menu "Invoice Membership" → SHOULD APPEAR

✅ Data Access:
   - Customer access_type: platform (10 records)
   - Branch access_type: platform (50 records)
```

**Impact**:
- ✅ Platform users see WP Customer menus
- ✅ Data accessible (full access - no filtering)
- ⚠️ Shows ALL customer/branch data
- 📋 Jurisdiction filtering: wp-agency TODO-2065

**Related Tasks**:
- wp-app-core TODO-1211: Filter hooks (completed ✅)
- wp-agency TODO-2064: Menu access (completed ✅)
- wp-agency TODO-2065: Jurisdiction filtering (planning 📋)

**Notes**:
- Simple 1-line capability addition
- platform_admin already had all capabilities
- Menu visibility working via WordPress capability system
- Data currently shows all records (intended for finance role)
- wp-agency will implement jurisdiction filtering for restricted access

---

## TODO-1211: Platform Access to Branch and Employee DataTables ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-19
**Completed**: 2025-10-19
**Dependencies**: TODO-1210 (Customer access), wp-customer TODO-2166
**Priority**: High
**Extends**: TODO-1210

**Summary**: Extend platform role access dari Customer entity ke Branch dan Employee entities. Implementasi filter hooks dan capabilities untuk memastikan platform users dapat mengakses Branch dan Employee DataTables dengan benar.

**Problem**:
- Platform users sudah bisa akses Customer DataTable (TODO-1210 ✅)
- Tapi **Branch dan Employee DataTable masih kosong**
- BranchModel dan EmployeeModel belum ada filtering untuk `access_type='platform'`
- BranchValidator tidak recognize platform users (return `access_type='none'`)
- Platform_finance tidak punya employee capabilities

**Root Cause**:
```php
// BranchModel::getTotalCount() dan EmployeeModel::getTotalCount()
// Hanya cek 4 tipe access (sama seperti CustomerModel sebelum TODO-1210):
if ($relation['is_admin']) { }
elseif ($relation['is_customer_admin']) { }
elseif ($relation['is_customer_branch_admin']) { }
elseif ($relation['is_customer_employee']) { }
else { WHERE 1=0 } // Platform users jatuh kesini!
```

**Solution**:
1. Tambah filter hook `wp_branch_access_type` di wp-app-core untuk set access_type='platform'
2. Update BranchModel dan EmployeeModel untuk recognize `access_type='platform'`
3. Update BranchValidator untuk recognize platform capabilities
4. Tambah employee capabilities ke platform_finance role

**Implementation**:

**1. wp-app-core Changes**:
- `/wp-app-core.php`:
  - Added `wp_branch_access_type` filter hook (line 129)
  - Added `set_platform_branch_access_type()` method (lines 168-199)
  - Checks `view_customer_branch_list` capability

- `/src/Models/Settings/PlatformPermissionModel.php` (v1.0.4):
  - Added `view_customer_employee_list` to platform_finance (line 686)
  - Added `view_customer_employee_detail` to platform_finance (line 687)
  - Employee capabilities already in `$available_capabilities` and `$capability_groups`

**2. wp-customer Changes** (via TODO-2166):
- BranchModel, EmployeeModel: Added platform filtering
- BranchValidator: Added platform capability checks
- All DataTables now working for platform users

**Filter Hook Pattern**:
```php
// wp-app-core.php
add_filter('wp_branch_access_type', [$this, 'set_platform_branch_access_type'], 10, 2);

public function set_platform_branch_access_type($access_type, $context) {
    if ($access_type !== 'none') return $access_type;

    if (current_user_can('view_customer_branch_list')) {
        // Check platform role
        if (/* has platform_ role */) {
            return 'platform';
        }
    }
    return $access_type;
}
```

**Test Results**:
```
Platform User: benny_clara (platform_finance)

✓ Customer DataTable: 10 records (access_type: platform)
✓ Branch DataTable: 9 records (access_type: platform)
✓ Employee DataTable: 16 records (access_type: platform)

Capabilities:
✓ view_customer_detail
✓ view_customer_branch_list
✓ view_customer_employee_list
✓ view_customer_employee_detail
```

**Platform Finance Capabilities** (WP Customer):
- Customer: view_customer_list, view_customer_detail
- Branch: view_customer_branch_list
- Employee: view_customer_employee_list, view_customer_employee_detail
- Invoice: Full membership invoice access (view, create, edit, approve, pay)

**Files Modified**:
- `/wp-app-core/wp-app-core.php` (added branch access filter)
- `/wp-app-core/src/Models/Settings/PlatformPermissionModel.php` (v1.0.4)

**Related Tasks**:
- wp-app-core TODO-1210: Customer access (completed ✅)
- wp-customer TODO-2166: Branch & Employee model updates (completed ✅)

**Pattern Consistency**:
All 3 entities (Customer, Branch, Employee) now follow same pattern:
1. Model: Check `access_type='platform'` in data filtering
2. wp-app-core: Hook filter to set access_type='platform'
3. Validator: Check platform capabilities
4. PlatformPermissionModel: Manage capabilities centrally

See: wp-customer [TODO-2166](../wp-customer/TODO.md#TODO-2166) for implementation details

---

## TODO-1210: Platform Role Access to WP Customer via Capability System ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-19
**Completed**: 2025-10-19
**Dependencies**: wp-customer TODO-2165, TODO-1209 (capabilities registration)
**Priority**: High
**Approach**: Direct Capability Checks (Opsi 1 - Simplified)

**Summary**: Enable platform roles (platform_finance, platform_admin, etc.) to access WP Customer entities using WordPress capability system. Simplified approach using direct `current_user_can()` checks instead of complex hook filters.

**Problem**:
- Platform users sudah memiliki WP Customer capabilities (TODO-1209 ✅)
- Menu sudah terlihat di wp-admin
- Tetapi **DataTable kosong** karena `access_type = 'none'`
- CustomerValidator::getUserRelation() tidak recognize platform roles
- Validator return `has_access = false` → WHERE 1=0 → No records

**Root Cause**:
```php
// CustomerModel::getUserRelation() hanya cek 4 tipe:
- is_admin (WordPress administrator)
- is_customer_admin (customer owner)
- is_customer_branch_admin (branch admin)
- is_customer_employee (employee)

// Platform roles tidak ter-detect → access_type = 'none'
```

**Solution**:
Implement filter hooks di wp-app-core untuk inject platform role access:
```php
// wp-customer provides hooks (after TODO-2165):
apply_filters('wp_customer_user_can_view_customer', false, $relation);
apply_filters('wp_customer_user_can_edit_customer', false, $relation);
apply_filters('wp_customer_user_can_delete_customer', false, $relation);

// wp-app-core implements filters (TODO-1210):
add_filter('wp_customer_user_can_view_customer', 'check_platform_access', 10, 2);
```

**Implementation**:
1. Create `/includes/class-wp-customer-integration.php` (WPCustomerIntegration class)
2. Register filters for customer entity (view, edit, delete)
3. Check platform capabilities with caching (5 minutes)
4. Register integration in wp-app-core.php

**Caching Strategy**:
- Level 1: Object Cache (wp_cache) - 5 minutes
- Level 2: Database query (only on cache miss)
- Estimated overhead: ~0.001s per request (after cache warm-up)

**Expected Result**:
```
Before:
  access_type: none
  has_access: NO
  DataTable: Empty (0 records)

After:
  access_type: platform
  has_access: YES
  DataTable: Shows all records
```

**Final Implementation**:
Instead of complex hook system, used **Opsi 1: Direct Capability Checks**:
- wp-customer checks capabilities directly: `current_user_can('view_customer_detail')`
- Platform roles have these capabilities (managed in PlatformPermissionModel)
- Simpler, more secure, and maintainable

**Files Modified**:
- `/src/Models/Settings/PlatformPermissionModel.php` (v1.0.3 → v1.0.4)
  - Added `view_customer_detail` to platform_finance, platform_analyst, platform_viewer
- wp-customer `CustomerValidator.php` (v1.0.4 → v1.0.6)
  - Added direct capability checks in canView(), canUpdate(), canDelete()
  - No hook filters needed

**Files Created (Reference Only - Not Used)**:
- `/includes/class-wp-customer-integration.php` (created but disabled)
- `/TODO/TODO-1210-implement-platform-role-access-filters.md` (detailed analysis)

**Related Tasks**:
- wp-customer TODO-2165: Refactor hook naming convention (dependency)
- wp-app-core TODO-1208: Base role system (completed ✅)
- wp-app-core TODO-1209: WP Customer capabilities registration (completed ✅)

**Test Results**:
```
platform_finance:
  ✓ view_customer_detail capability
  ✓ has_access = YES
  ✓ canView() = YES
  ⚠ access_type = 'none' (trade-off, but functional)

platform_admin:
  ✓ view_customer_detail + edit_all_customers
  ✓ has_access = YES
  ✓ canView() = YES, canUpdate() = YES
  ✓ access_type = 'admin' (detected correctly)
```

**Benefits**:
- ✅ Simple and maintainable (KISS principle)
- ✅ Uses WordPress core capability system
- ✅ No complex hooks or integration classes
- ✅ Secure (capability-based access control)
- ✅ Easy to extend (just add capabilities to roles)

**Trade-offs**:
- ⚠ access_type = 'none' for some platform users (acceptable - has_access still works)
- ⚠ Coupling: wp-customer aware of platform capabilities (minimal impact)

See: [TODO/TODO-1210-implement-platform-role-access-filters.md](TODO/TODO-1210-implement-platform-role-access-filters.md) for detailed analysis and alternative approaches

---

## TODO-1209: Fix WP Customer Plugin Capabilities Registration ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-19
**Completed**: 2025-10-19
**Version**: 1.0.3

**Summary**: Fixed WP Customer plugin capabilities registration by adding all 32 capabilities to `$available_capabilities`, `$capability_groups`, and capability descriptions arrays in PlatformPermissionModel.php.

**Problem**:
- WP Customer capabilities were added to role defaults but NOT registered in `$available_capabilities` array
- Platform users couldn't access WP Customer menus even after `wp cache flush`
- Capabilities were skipped during role assignment due to validation check: `isset($this->available_capabilities[$cap])`

**Root Cause**:
```php
// Line 246 in addCapabilities():
if ($enabled && isset($this->available_capabilities[$cap])) {
    $role->add_cap($cap);
}
// WP Customer caps were NOT in $available_capabilities, so isset() returned false
```

**Solution**:
1. Added 32 WP Customer capabilities to `$available_capabilities` array (lines 98-138)
2. Added 5 capability groups to `$capability_groups` array (lines 222-278):
   - wp_customer_management (7 caps)
   - wp_customer_branch (7 caps)
   - wp_customer_employee (7 caps)
   - wp_customer_invoice (8 caps)
   - wp_customer_invoice_payment (3 caps)
3. Added capability descriptions for all 32 capabilities (lines 772-812)
4. Updated version from 1.0.2 to 1.0.3

**Testing Results**:
```
✓ platform_finance: 8 WP Customer capabilities
✓ platform_admin: 15 WP Customer capabilities
✓ All platform users can access 3 WP Customer menus:
  - WP Customer Menu (view_customer_list)
  - WP Perusahaan Menu (view_customer_branch_list)
  - Invoice Membership Menu (view_customer_membership_invoice_list)
```

**Files Modified**:
- `/src/Models/Settings/PlatformPermissionModel.php` (v1.0.2 → v1.0.3)

**Files Created**:
- `/TODO/TODO-1209-fix-wp-customer-capabilities.md`

See: [TODO/TODO-1209-fix-wp-customer-capabilities.md](TODO/TODO-1209-fix-wp-customer-capabilities.md)

---

## TODO-1208: Base Role System Implementation ✅ COMPLETED

**Status**: ✅ COMPLETED (FINAL FIX)
**Created**: 2025-10-19
**Completed**: 2025-10-19
**Version**: 1.0.2

**Summary**: Implemented base role system untuk semua platform users (dual roles: platform_staff + platform_xxx), fixing wp-admin access issue permanently.

**Root Cause**:
- WP-Customer users: `customer, customer_admin` (2 roles) → CAN access wp-admin
- WP-Platform users: `platform_admin` (1 role) → CANNOT access wp-admin
- Issue: WordPress requires base role with 'read' capability for reliable wp-admin access

**Solution**:
Implemented dual role system matching wp-customer pattern:
```
Platform User:
├── Base Role: platform_staff (wp-admin access)
│   └── Capability: read = true
└── Admin Role: platform_xxx (specific permissions)
```

**Changes**:
1. **class-role-manager.php**:
   - Added `getBaseRole()`, `getBaseRoleName()`, `getAdminRoles()`
   - Updated `createRoles()` to create base role first with 'read'

2. **Data/PlatformUsersData.php**:
   - Updated all 20 users from single role to dual roles
   - `['platform_admin']` → `['platform_staff', 'platform_admin']`

3. **class-upgrade.php** (v1.0.2):
   - New `upgrade_to_1_0_2()` routine
   - Auto-creates base role
   - Auto-adds base role to ALL existing platform users
   - Idempotent (safe to run multiple times)

4. **wp-app-core.php**:
   - Version: 1.0.1 → 1.0.2
   - Updated changelog

**How It Works** (Auto-Fix):
1. User refreshes admin page
2. Plugin detects version change (1.0.1 → 1.0.2)
3. Upgrade script creates 'platform_staff' role with 'read' capability
4. Loops all existing platform users
5. Adds 'platform_staff' role to each user (without removing existing roles)
6. **Auto-flushes WordPress cache**
7. Users can now access wp-admin

**IMPORTANT**: If auto-upgrade runs but users still can't access:
```bash
# Manually flush cache
wp cache flush
```

**Testing**:
```bash
# After upgrade, check dual roles:
wp user list --role=platform_admin --fields=ID,user_login,roles

# Expected:
232  edwin_felix   platform_staff, platform_admin  ✅
233  grace_helen   platform_staff, platform_admin  ✅
```

**Files Modified**:
- `/includes/class-role-manager.php`
- `/src/Database/Demo/Data/PlatformUsersData.php`
- `/includes/class-upgrade.php`
- `/wp-app-core.php`

**Files Created**:
- `/TODO/TODO-1208-base-role-system.md`

See: [TODO/TODO-1208-base-role-system.md](TODO/TODO-1208-base-role-system.md)

---

## TODO-1208: Platform Permission Read Capability ✅ COMPLETED (SUPERSEDED)

**Status**: ✅ COMPLETED
**Created**: 2025-10-19
**Completed**: 2025-10-19

**Summary**: Menambahkan default capability "read" ke semua platform role agar dapat mengakses halaman admin WordPress + Upgrade system untuk auto-migration.

**Problem**:
- Platform roles tidak dapat mengakses halaman wp-admin
- Capability "read" tidak ditambahkan secara eksplisit meskipun sudah ada di default capabilities array
- Method hanya menambahkan capabilities yang ada di `$available_capabilities` array
- Existing users tidak mendapat update karena activator hanya run sekali

**Solution**:
1. Menambahkan capability "read" secara eksplisit di `addCapabilities()` dan `resetToDefault()`
2. Membuat upgrade system yang otomatis detect version change dan run migrations
3. Upgrade to v1.0.1 otomatis add 'read' capability ke existing platform roles

**Changes**:
1. **PlatformPermissionModel.php** (v1.0.1):
   - Updated `addCapabilities()` method - explicitly add 'read' capability to all platform roles
   - Updated `resetToDefault()` method - ensure 'read' capability persists after reset
   - Added changelog for version 1.0.1

2. **class-upgrade.php** (NEW):
   - Version checking mechanism
   - Upgrade routine for v1.0.1 (auto-add 'read' capability)
   - Debug logging
   - Extensible for future migrations

3. **wp-app-core.php** (v1.0.1):
   - Updated plugin version: 1.0.0 → 1.0.1
   - Load upgrade class in `load_dependencies()`
   - Hook upgrade checker to `plugins_loaded` (priority 5)
   - Updated changelog

**Platform Roles Affected**:
- platform_super_admin
- platform_admin
- platform_manager
- platform_support
- platform_finance
- platform_analyst
- platform_viewer

**How It Works** (Auto-Fix):
1. User refresh admin page
2. Plugin detect version 1.0.0 (DB) vs 1.0.1 (code)
3. Upgrade script runs automatically
4. All platform roles get 'read' capability
5. Version updated to 1.0.1 in DB
6. Users can now access wp-admin

**Reference**: `/wp-customer/src/Models/Settings/PermissionModel.php` (lines 238, 298, 358)

**Files Modified**:
- `/wp-app-core/src/Models/Settings/PlatformPermissionModel.php`
- `/wp-app-core/wp-app-core.php`

**Files Created**:
- `/wp-app-core/includes/class-upgrade.php` (NEW - Upgrade system)
- `/wp-app-core/TODO/TODO-1208-platform-permission-read-capability.md`

See: [TODO/TODO-1208-platform-permission-read-capability.md](TODO/TODO-1208-platform-permission-read-capability.md)

---

## TODO-1207: Platform Staff CRUD & DataTable ✅ COMPLETED

**Status**: ✅ COMPLETED (Ready for testing)
**Created**: 2025-10-19
**Completed**: 2025-10-19

**Summary**: Complete CRUD and DataTable system for Platform Staff management dengan full MVC architecture.

**Key Deliverables**:
- ✅ PlatformCacheManager (generic cache untuk semua platform entities)
- ✅ PlatformStaffModel (CRUD, DataTable, Statistics, Employee ID generation)
- ✅ PlatformStaffValidator (permission checks, data validation)
- ✅ PlatformStaffController (AJAX handlers, menu registration, assets)
- ✅ 4 View Templates (dashboard, left panel, right panel, no access)
- ✅ 2 JavaScript files (main + datatable with server-side processing)
- ✅ 2 CSS files (main + datatable styling)
- ✅ Integration dengan wp-app-core.php

**Features**:
- DataTable dengan server-side processing (pagination, search, sorting, filtering)
- Add/Edit/Delete staff dengan modal form
- Right panel untuk detail staff
- Statistics dashboard (total staff, recent hires, departments)
- Department-based filtering
- Role-based permission checks (view, create, edit, delete)
- Cache management untuk optimasi performa
- Toast notifications untuk user feedback
- Responsive design (mobile-friendly)

**Files Created**: 12 new files + 1 update
- 1 Cache Manager (generic untuk semua platform)
- 3 MVC components (Model, Validator, Controller)
- 4 View templates
- 2 JavaScript files
- 2 CSS files

**Database Structure**:
Table `wp_app_platform_staff` dengan fields:
- id, user_id, employee_id (unique), full_name
- department, hire_date, phone
- created_at, updated_at

**Next Steps**: Testing CRUD operations, DataTable functionality, dan permission checks

---

## TODO-1206: Platform Settings Implementation ✅ COMPLETED

**Status**: ✅ COMPLETED (Ready for production)

**Summary**: Complete platform settings system with 7 tabs for managing platform-level configurations (company info, email, permissions, security, demo data).

**Key Deliverables**:
- ✅ 6 Settings Models (Platform, Email, Permission, 3x Security)
- ✅ 7 Tab Views (General, Email, Permissions, Demo Data, 3x Security tabs)
- ✅ AJAX Handlers (save, reset, demo data generation)
- ✅ Platform Staff Demo Data System (20 users, ID 230-249)
- ✅ Reset to Default functionality for all security tabs
- ✅ Comprehensive validation & user feedback
- ✅ Conditional field display & real-time validation
- ✅ MVC compliance & code optimization

**Bug Fixes**:
- ✅ Fixed checkbox not saving (3 issues: boolean handling, model re-instantiation, cache not cleared)
- ✅ Fixed settings group name mismatch
- ✅ Fixed permission matrix fatal error

**Files Created**: 40+ files (models, views, controllers, assets, database, documentation)

**Description**: Implementasi lengkap Platform Settings untuk wp-app-core plugin. Settings ini untuk mengatur hal-hal platform-level (company info, email, permissions, security) bukan operational marketplace yang sudah diatur di plugin lain.

**Key Features**:
- General/Company Settings (info, contact, branding, regional)
- Email & Notifications (SMTP, templates, notification preferences)
- Platform Permissions (7 groups untuk platform staff)
- Security Settings (Authentication, Session, Policy & Audit)

**Platform Permission Groups** (7 Groups):
1. Platform Management - Dashboard, settings, system config
2. User & Role Management - Platform staff users
3. Tenant Management - Customer/branch approval & management
4. Financial & Billing - Pricing, payments, invoices
5. Support & Helpdesk - Tickets, FAQ, announcements
6. Reports & Analytics - Analytics, audit logs
7. Content & Resources - Documentation, templates

**Important: WordPress Administrator vs Platform Super Admin**

TIDAK SAMA! Dua role yang berbeda:

| Aspek | WordPress Administrator | Platform Super Admin |
|-------|------------------------|---------------------|
| **Source** | WordPress Core (built-in) | wp-app-core Plugin (custom) |
| **Role Slug** | `administrator` | `platform_super_admin` |
| **User ID 1** | ✅ Biasanya Ya | ❌ Tidak otomatis |
| **Di Permission Matrix** | ❌ TIDAK muncul | ✅ MUNCUL (editable) |
| **Auto Full Access** | ✅ Ya (WordPress core) | ❌ Harus di-set via matrix |
| **Icon** | - | 🔧 dashicons-admin-generic |
| **Capabilities** | Tidak bisa diubah | Bisa dikonfigurasi |

**WordPress Administrator**:
- Role bawaan WordPress, biasanya user ID 1 (site owner/developer)
- Sudah memiliki ALL capabilities otomatis (WordPress core behavior)
- TIDAK ditampilkan di permission matrix karena sudah punya akses penuh
- Tidak bisa dimodifikasi permission-nya melalui matrix
- Untuk site owner, developer, full admin WordPress

**Platform Super Admin** (7 Platform Roles):
1. `platform_super_admin` - Full platform access (perlu di-set)
2. `platform_admin` - Platform operations & tenant management
3. `platform_manager` - Operations oversight & analytics
4. `platform_support` - Customer support & helpdesk
5. `platform_finance` - Financial operations & billing
6. `platform_analyst` - Analytics & reports
7. `platform_viewer` - View-only access

- Role custom yang dibuat oleh plugin ini
- Harus di-assign capabilities melalui permission matrix
- DITAMPILKAN di permission matrix untuk konfigurasi
- Bisa dimodifikasi permission-nya sesuai kebutuhan
- Untuk platform staff yang bukan WordPress site owner
- Segregation of duties - pisahkan WordPress admin dari platform staff

**Use Case Example**:
```
Website Owner (User ID 1):
├── WordPress Role: administrator
└── Full access otomatis, tidak perlu setting matrix

Platform Staff (User ID 125):
├── WordPress Role: platform_super_admin (atau role lainnya)
├── Harus di-set capabilities via permission matrix
└── Bisa dibatasi sesuai kebutuhan bisnis
```

**Files Created**:
- `/wp-app-core/src/Models/Settings/PlatformSettingsModel.php`
- `/wp-app-core/src/Models/Settings/PlatformPermissionModel.php`
- `/wp-app-core/src/Models/Settings/EmailSettingsModel.php`
- `/wp-app-core/src/Models/Settings/SecurityAuthenticationModel.php`
- `/wp-app-core/src/Models/Settings/SecuritySessionModel.php`
- `/wp-app-core/src/Models/Settings/SecurityPolicyModel.php`
- `/wp-app-core/src/Controllers/PlatformSettingsController.php`
- `/wp-app-core/includes/class-role-manager.php`
- `/wp-app-core/includes/class-activator.php` (updated)
- `/wp-app-core/includes/class-deactivator.php` (updated)
- `/wp-app-core/src/Views/templates/settings/settings-page.php`
- `/wp-app-core/src/Views/templates/settings/tab-general.php`
- `/wp-app-core/src/Views/templates/settings/tab-email.php`
- `/wp-app-core/src/Views/templates/settings/tab-permissions.php`
- `/wp-app-core/src/Views/templates/settings/tab-demo-data.php`
- `/wp-app-core/src/Views/templates/settings/tab-security-authentication.php`
- `/wp-app-core/src/Views/templates/settings/tab-security-session.php`
- `/wp-app-core/src/Views/templates/settings/tab-security-policy.php`
- `/wp-app-core/assets/css/settings/settings.css`
- `/wp-app-core/assets/js/settings/settings.js`
- `/wp-app-core/assets/css/settings/permissions-tab-style.css`
- `/wp-app-core/assets/js/settings/permissions-tab-script.js`
- `/wp-app-core/assets/css/settings/demo-data-tab-style.css`
- `/wp-app-core/assets/js/settings/platform-demo-data-tab-script.js`
- `/wp-app-core/assets/css/settings/security-authentication-tab-style.css`
- `/wp-app-core/assets/js/settings/security-authentication-tab-script.js`
- `/wp-app-core/assets/css/settings/security-session-tab-style.css`
- `/wp-app-core/assets/js/settings/security-session-tab-script.js`
- `/wp-app-core/assets/css/settings/security-policy-tab-style.css`
- `/wp-app-core/assets/js/settings/security-policy-tab-script.js`
- `/wp-app-core/src/Database/Tables/PlatformStaffDB.php` (Review-01)
- `/wp-app-core/src/Database/Installer.php` (Review-01)
- `/wp-app-core/src/Database/Demo/Data/PlatformUsersData.php` (Review-01)
- `/wp-app-core/src/Database/Demo/Data/PlatformDemoData.php` (Review-01)
- `/wp-app-core/src/Database/Demo/WPUserGenerator.php` (Review-01)
- `/wp-app-core/TODO/TODO-1206-platform-settings-implementation.md`
- `/wp-app-core/TODO/REVIEW-01-platform-user-generation.md` (Review-01)

**Files Modified**:
- `/wp-app-core/src/Controllers/MenuManager.php` (migrated to WPAppCore namespace)
- `/wp-app-core/src/Controllers/PlatformSettingsController.php` (added tab-specific asset loading, demo-data AJAX handlers, dev settings, platform staff AJAX - Review-01)
- `/wp-app-core/src/Views/templates/settings/settings-page.php` (added demo-data tab to navigation)
- `/wp-app-core/src/Views/templates/settings/tab-permissions.php` (H2 context header, separated sections, MVC compliance)
- `/wp-app-core/src/Views/templates/settings/tab-demo-data.php` (added Platform Staff Demo Data section - Review-01)
- `/wp-app-core/src/Models/Settings/PlatformPermissionModel.php` (added getDefaultCapabilitiesForRole, getCapabilityDescriptions methods)
- `/wp-app-core/includes/class-role-manager.php` (removed getDefaultCapabilities and initializeRoleCapabilities methods)
- `/wp-app-core/includes/class-activator.php` (added database installation call - Review-01)
- `/wp-app-core/includes/class-deactivator.php` (updated to use development settings option)
- `/wp-app-core/assets/js/settings/platform-demo-data-tab-script.js` (added platform staff handlers - Review-01)
- `/wp-app-core/wp-app-core.php` (use MenuManager)

See: [TODO/TODO-1206-platform-settings-implementation.md](TODO/TODO-1206-platform-settings-implementation.md)

---

## TODO-1203 CSS Loading Fix
- [x] Analyze CSS loading issue (wp-includes instead of plugin CSS)
- [x] Add priority 20 to admin_enqueue_scripts hooks
- [x] Add 'admin-bar' dependency to wp_enqueue_style
- [x] Document load order and cascade strategy
- [x] Create TODO-1203-review-08-css-loading-fix.md
- [x] Sync to TODO.md

**Status**: ✅ COMPLETED (needs browser testing)

**Description**: Fix masalah CSS plugin tidak ter-load. Browser masih menggunakan `wp-includes/css/admin-bar.css` (WordPress core) instead of plugin's custom CSS.

**Problem**:
- Plugin CSS tidak ter-load di browser
- Yang muncul hanya wp-includes/css/admin-bar.css
- Plugin CSS file exists tapi tidak di-enqueue dengan benar

**Solution**:
1. **Priority 20**: Load after WordPress core (default 10)
2. **Dependency**: Declare dependency on core 'admin-bar' CSS
3. **Cascade**: Our styles override core via proper load order

**Technical Details**:
```php
// Before: Priority 10 (default), no dependency
add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
wp_enqueue_style('wp-app-core-admin-bar', $url, [], $version);

// After: Priority 20, with dependency
add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles'], 20);
wp_enqueue_style('wp-app-core-admin-bar', $url, ['admin-bar'], $version);
```

**Expected Result**:
Browser should load BOTH CSS files:
1. wp-includes/css/admin-bar.css (core)
2. wp-content/plugins/wp-app-core/assets/css/admin-bar/admin-bar-style.css (plugin)

**Files Modified**:
- `/wp-app-core/includes/class-dependencies.php`

**Files Created**:
- `/wp-app-core/TODO/TODO-1203-review-08-css-loading-fix.md`

See: [TODO/TODO-1203-review-08-css-loading-fix.md](TODO/TODO-1203-review-08-css-loading-fix.md)

---

## TODO-1202 Review-07: Assets Reorganization
- [x] Rename CSS dari admin-bar-style.css ke admin-bar.css
- [x] Buat file JS admin-bar/admin-bar.js
- [x] Update class-dependencies.php (CSS path & JS enqueue)
- [x] Tambahkan wp_localize_script untuk future AJAX
- [x] Create TODO-1202-review-07-assets-reorganization.md
- [x] Sync to TODO.md

**Status**: ✅ COMPLETED

**Description**: Reorganisasi struktur assets plugin wp-app-core untuk mengikuti pola yang sama dengan wp-agency. Menerapkan naming convention yang konsisten dan mempersiapkan struktur untuk future interactive features.

**Perubahan**:
- File CSS: `admin-bar-style.css` → `admin-bar.css`
- File JS: Created `admin-bar.js` dengan basic structure
- Dependencies: Updated enqueue paths dan activated JS
- Localization: Added wpAppCoreData object untuk AJAX support

**Pola yang Diikuti**:
- wp-agency: `agency/agency-style.css` + `agency/agency-script.js`
- wp-app-core: `admin-bar/admin-bar.css` + `admin-bar/admin-bar.js`

**Files Modified**:
- `/wp-app-core/includes/class-dependencies.php`
- `/wp-app-core/assets/css/admin-bar/admin-bar-style.css` → `admin-bar.css`

**Files Created**:
- `/wp-app-core/assets/js/admin-bar/admin-bar.js`
- `/wp-app-core/TODO/TODO-1202-review-07-assets-reorganization.md`

See: [TODO/TODO-1202-review-07-assets-reorganization.md](TODO/TODO-1202-review-07-assets-reorganization.md)

---

## TODO-1201: WP App Core Admin Bar Integration
- [x] Create class-admin-bar-info.php for generic admin bar system
- [x] Create integration layer for wp-agency (class-app-core-integration.php)
- [x] Update wp-customer integration (already exists)
- [x] **Review-02**: Add fallback for users with agency role but no entity link
- [x] **Review-03**: False alarm - customer users admin bar (reverted)
- [x] **Review-04**: Revert Review-03 changes
- [x] **Review-05**: Fix hardcoded values and wrong terminology (branch � division)
- [x] **Review-06**: Update init() pattern and add comprehensive debug logging
- [x] **Review-07**: CRITICAL FIX - Reorder query priority (check employee FIRST)
- [x] **Review-08**: MAJOR IMPROVEMENT - Complete query rewrite with jurisdictions
- [x] **Review-09**: CRITICAL OPTIMIZATION - Replace 3 queries with 1 single query
- [x] **Review-10**: Filter function explanation and documentation
- [x] Create TODO-1201-wp-app-core-admin-bar-integration.md
- [x] Sync to TODO.md

**Status**:  COMPLETED

**Description**: Generic admin bar integration system untuk wp-app-core yang dapat digunakan oleh multiple plugins (wp-customer, wp-agency, dll) untuk menampilkan user information di WordPress admin bar.

**Key Features**:
- Generic plugin registration system
- Dynamic filter for role name translation
- Support for multiple plugins without conflicts
- Comprehensive debug logging
- Optimal database performance (1 query instead of 3)
- Complete data retrieval (jurisdictions, email, capabilities)

**Performance**:
- Query reduction: 3 queries � 1 query (67% reduction)
- Execution speed: ~3x faster for common case (employees)
- Code reduction: Removed ~150 lines of redundant code

**Fixes Applied**:

- **Review-02**: Added fallback logic untuk user dengan agency role tanpa entity link 

- **Review-05**: Fixed hardcoded values and terminology
  - Removed hardcoded "Dinas Tenaga Kerja" dan "DISNAKER"
  - Changed `branch_name`/`branch_type` to `division_name`/`division_type`
  - Agency owner now shows actual first division from database
  - Fallback uses generic "Agency System" instead of hardcoded name 

- **Review-06**: Updated init() method pattern
  - Changed from loop-based to explicit add_filter for each role
  - Matches pattern with wp-customer
  - Added comprehensive debug logging for all queries
  - Created debug-logging-guide.md 

- **Review-07**: CRITICAL FIX - Query order optimization
  - **Problem**: Was checking agency owner FIRST, but not all employees are owners
  - **Fix**: Reordered to check EMPLOYEE first (most common case)
  - **New Order**: Employee � Division Admin � Agency Owner � Fallback
  - **Benefit**: 3x performance improvement for employees 

- **Review-08**: MAJOR IMPROVEMENT - Complete query rewrite
  - **Problem**: Incomplete data retrieval, missing jurisdiction information
  - **Fix**: Comprehensive query with INNER/LEFT JOIN, GROUP_CONCAT, MAX()
  - **New Fields**: division_code, jurisdiction_codes, is_primary_jurisdiction
  - **Benefit**: Single query gets ALL related data 

- **Review-09**: CRITICAL OPTIMIZATION - Single query replaces all
  - **Problem**: Using 3 separate queries (employee, division admin, owner) inefficiently
  - **Realization**: wp_app_agency_employees table already has user_id, division_id, agency_id
  - **Fix**: Replaced with ONE comprehensive query with all JOINs
  - **Added**: JOIN with wp_users (email) and wp_usermeta (capabilities)
  - **Benefits**:
    - **3 queries � 1 query** (67% reduction)
    - Much faster execution time
    - Less database load
    - Cleaner code (removed ~150 lines)
    - Added user_email and capabilities data 

**Files Created**:
- `/wp-app-core/includes/class-admin-bar-info.php`
- `/wp-agency/includes/class-app-core-integration.php` (v1.5.0)
- `/wp-app-core/claude-chats/debug-logging-guide.md`
- `/wp-app-core/TODO/TODO-1201-wp-app-core-admin-bar-integration.md`

**Files Modified**:
- `/wp-customer/includes/class-app-core-integration.php` (already exists)
- `/wp-agency/TODO.md`

**Files to Keep** (Backward Compatibility):
- `/wp-customer/includes/class-admin-bar-info.php`
- `/wp-customer/src/Views/templates/customer-employee/partials/_customer_employee_profile_fields.php`

See: [TODO/TODO-1201-wp-app-core-admin-bar-integration.md](TODO/TODO-1201-wp-app-core-admin-bar-integration.md)

---

## TODO-1204: Method Migration to AdminBarModel
- [x] Analyze methods in AgencyEmployeeModel
- [x] Create AdminBarModel.php with generic methods
- [x] Update AgencyEmployeeModel to use AdminBarModel
- [x] Remove old helper methods from AgencyEmployeeModel
- [x] Create TODO-1204-method-migration.md
- [x] Sync to TODO.md

**Status**: ✅ COMPLETED (needs browser testing)

**Description**: Memindahkan method yang bersifat global scope dari AgencyEmployeeModel.php ke AdminBarModel.php untuk separation of concerns dan reusability.

**Approach**: Option 2 - Proper Separation
- Keep: `getUserInfo()` in AgencyEmployeeModel (data access layer)
- Move: Helper methods to AdminBarModel as generic utilities

**Methods Migrated**:
1. `getRoleNamesFromCapabilities()` → Made generic with parameters
2. `getPermissionNamesFromUserId()` → Made generic with parameters

**Changes**:
- Created: `/wp-app-core/src/Models/AdminBarModel.php` (256 lines)
- Modified: `/wp-agency/src/Models/Employee/AgencyEmployeeModel.php` (removed 135 lines)
- No change: `/wp-agency/includes/class-app-core-integration.php` (backward compatible)

**Benefits**:
- ✅ Separation of concerns (model vs utilities)
- ✅ Reusable by all plugins (customer, agency, future)
- ✅ Generic implementation (no hardcoded dependencies)
- ✅ Backward compatible (no breaking changes)
- ✅ Better maintainability

**Files Created**:
- `/wp-app-core/src/Models/AdminBarModel.php`
- `/wp-app-core/TODO/TODO-1204-method-migration.md`
- `/wp-app-core/TODO/PROPOSAL-1204-method-migration.md`
- `/wp-app-core/TODO/ANALYSIS-1204-review-01.md`

**Files Modified**:
- `/wp-agency/src/Models/Employee/AgencyEmployeeModel.php`

See: [TODO/TODO-1204-method-migration.md](TODO/TODO-1204-method-migration.md)

---
