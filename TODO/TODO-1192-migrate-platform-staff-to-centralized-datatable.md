# TODO-1192: Migrate Platform Staff to Centralized DataTable System

**Status**: In Progress
**Priority**: HIGH
**Created**: 2025-11-01
**Plugin**: wp-app-core
**Category**: Architecture, Refactoring, DataTable System

---

## Problem Statement

Platform Staff dashboard masih menggunakan implementasi custom/manual, belum menggunakan centralized DataTable system dari wp-app-core. Seharusnya mengikuti pola yang sudah established di wp-agency (menu Disnaker).

### Current Implementation (WRONG)

**File**: `/wp-app-core/src/Views/platform-staff/platform-staff-dashboard.php`

- Manual HTML structure
- Custom CSS/JS implementation
- Tidak menggunakan `DashboardTemplate::render()`
- Tidak menggunakan centralized panel system
- Code duplication

---

## Solution: Follow wp-agency Pattern

Implementasi mengikuti pattern dari wp-agency menu Disnaker yang sudah menggunakan centralized DataTable system.

### Reference Files

**wp-agency Implementation:**
- `/wp-agency/src/Controllers/Agency/AgencyDashboardController.php`
- `/wp-agency/src/Models/Agency/AgencyDataTableModel.php`
- `/wp-agency/assets/css/agency/agency-style.css`
- `/wp-agency/assets/css/agency/agency-header-cards.css`
- `/wp-agency/assets/css/agency/agency-filter.css`
- `/wp-agency/assets/js/agency/agency-datatable.js`
- `/wp-agency/src/Views/agency/partials/header-title.php`
- `/wp-agency/src/Views/agency/partials/header-buttons.php`
- `/wp-agency/src/Views/agency/partials/stat-cards.php`
- `/wp-agency/src/Views/agency/tabs/info.php`

**Centralized DataTable System:**
- `/wp-app-core/src/Views/DataTable/README.md`
- `/wp-app-core/src/Views/DataTable/Templates/DashboardTemplate.php`
- `/wp-app-core/src/Views/DataTable/Templates/PanelLayoutTemplate.php`
- `/wp-app-core/src/Views/DataTable/Templates/TabSystemTemplate.php`
- `/wp-app-core/src/Views/DataTable/Templates/StatsBoxTemplate.php`

---

## Implementation Plan

### Step 1: Create PlatformStaffDataTableModel

**File**: `/wp-app-core/src/Models/Platform/PlatformStaffDataTableModel.php`

Following `AgencyDataTableModel.php` pattern:
- Implement DataTable query methods
- Server-side processing
- Search and filtering
- Sorting support

### Step 2: Create PlatformStaffDashboardController

**File**: `/wp-app-core/src/Controllers/Platform/PlatformStaffDashboardController.php`

Following `AgencyDashboardController.php` pattern:
- `renderDashboard()` - Main dashboard using `DashboardTemplate::render()`
- `registerHooks()` - Register filters and actions
- `renderStatCards()` - Statistics cards
- `renderLeftPanelContent()` - DataTable HTML
- `registerTabs()` - Tab registration
- `getStaffStatistics()` - AJAX endpoint for stats
- `getStaffDetails()` - AJAX endpoint for panel data

### Step 3: Create View Structure

**Partials** (`/wp-app-core/src/Views/platform-staff/partials/`):
- `header-title.php` - Page header with title
- `header-buttons.php` - Action buttons (Add New Staff, etc)
- `stat-cards.php` - Statistics cards HTML

**Tabs** (`/wp-app-core/src/Views/platform-staff/tabs/`):
- `info.php` - Staff information tab (main tab)
- `placeholder.php` - Empty second tab (for future expansion)

**Main Dashboard** (`/wp-app-core/src/Views/platform-staff/`):
- `dashboard.php` - Main dashboard using DashboardTemplate

### Step 4: Create Assets

**CSS** (`/wp-app-core/assets/css/platform/`):
- `platform-staff-style.css` - Main styles
- `platform-staff-header-cards.css` - Header and stat cards styles
- `platform-staff-filter.css` - Filter styles

**JavaScript** (`/wp-app-core/assets/js/platform/`):
- `platform-staff-datatable.js` - DataTable initialization and panel handling

### Step 5: Update Dependencies

**File**: `/wp-app-core/includes/class-dependencies.php`

Update `enqueue_platform_staff_assets()`:
- Add new CSS files
- Add new JS file
- Update dependencies

### Step 6: Update MenuManager

**File**: `/wp-app-core/src/Controllers/MenuManager.php`

- Change from `PlatformStaffController` to `PlatformStaffDashboardController`
- Update renderDashboard callback

---

## Features to Implement

### Statistics Cards

Following wp-agency pattern:
1. **Total Staff** - Total number of platform staff
2. **Active Staff** - Staff with status 'aktif'
3. **Inactive Staff** - Staff with status 'tidak aktif'
4. **Recent Additions** - Staff added in last 30 days (optional)

### Tabs

1. **Info Tab** (priority 10):
   - Staff details display
   - Employee ID, Full Name, Department
   - Hire Date, Phone, Status
   - Associated WordPress user info

2. **Placeholder Tab** (priority 20):
   - Empty content for future expansion
   - Could be for: Documents, Activity Log, Settings, etc.

### DataTable Features

- Server-side processing
- Search functionality
- Status filtering
- Column sorting
- Row click to open detail panel
- Responsive design

---

## File Structure

```
wp-app-core/
├── src/
│   ├── Controllers/
│   │   └── Platform/
│   │       ├── PlatformStaffController.php (KEEP - for AJAX/CRUD only)
│   │       └── PlatformStaffDashboardController.php (NEW)
│   ├── Models/
│   │   └── Platform/
│   │       └── PlatformStaffDataTableModel.php (NEW)
│   └── Views/
│       └── platform-staff/
│           ├── dashboard.php (REPLACE)
│           ├── partials/
│           │   ├── header-title.php (NEW)
│           │   ├── header-buttons.php (NEW)
│           │   └── stat-cards.php (NEW)
│           └── tabs/
│               ├── info.php (NEW)
│               └── placeholder.php (NEW)
├── assets/
│   ├── css/
│   │   └── platform/
│   │       ├── platform-staff-style.css (NEW)
│   │       ├── platform-staff-header-cards.css (NEW)
│   │       └── platform-staff-filter.css (NEW)
│   └── js/
│       └── platform/
│           └── platform-staff-datatable.js (NEW)
└── includes/
    └── class-dependencies.php (UPDATE)
```

---

## Controller Separation

**PlatformStaffController.php** (Existing - KEEP):
- Focus: Business logic ONLY
- CRUD operations (create, update, delete staff)
- AJAX endpoints for data manipulation
- Validation and permission checks
- No view rendering (except old dashboard for backward compat)

**PlatformStaffDashboardController.php** (NEW):
- Focus: Dashboard view ONLY
- Render dashboard using DashboardTemplate
- Register hooks for stats, tabs, panels
- AJAX endpoints for dashboard data (stats, details)
- No CRUD operations

**Separation Benefits**:
- Clear separation of concerns
- Dashboard logic separated from business logic
- Easier to maintain and test
- Follows wp-agency pattern

---

## Configuration

### DashboardTemplate Config

```php
DashboardTemplate::render([
    'entity' => 'platform_staff',
    'title' => __('Platform Staff', 'wp-app-core'),
    'ajax_action' => 'get_platform_staff_details',
    'has_stats' => true,
    'has_tabs' => true,
]);
```

### Statistics Registration

```php
add_filter('wpapp_datatable_stats', function($stats, $entity) {
    if ($entity !== 'platform_staff') return $stats;

    return [
        [
            'id' => 'total-platform-staff',
            'label' => __('Total Staff', 'wp-app-core'),
            'icon' => 'dashicons-groups',
            'class' => 'primary'
        ],
        [
            'id' => 'active-platform-staff',
            'label' => __('Active', 'wp-app-core'),
            'icon' => 'dashicons-yes-alt',
            'class' => 'success'
        ],
        [
            'id' => 'inactive-platform-staff',
            'label' => __('Inactive', 'wp-app-core'),
            'icon' => 'dashicons-dismiss',
            'class' => 'warning'
        ]
    ];
}, 10, 2);
```

### Tabs Registration

```php
add_filter('wpapp_datatable_tabs', function($tabs, $entity) {
    if ($entity !== 'platform_staff') return $tabs;

    return [
        'info' => [
            'title' => __('Staff Information', 'wp-app-core'),
            'template' => WP_APP_CORE_PATH . 'src/Views/platform-staff/tabs/info.php',
            'priority' => 10
        ],
        'placeholder' => [
            'title' => __('Additional', 'wp-app-core'),
            'template' => WP_APP_CORE_PATH . 'src/Views/platform-staff/tabs/placeholder.php',
            'priority' => 20
        ]
    ];
}, 10, 2);
```

---

## Benefits

### Before (Current)

❌ Manual HTML structure (300+ lines)
❌ Custom CSS (300+ lines)
❌ Custom JavaScript (200+ lines)
❌ Code duplication
❌ Inconsistent with other dashboards
❌ Hard to maintain

### After (Centralized)

✅ Use DashboardTemplate (7 lines)
✅ Reuse centralized CSS
✅ Reuse centralized JavaScript
✅ Consistent UX across all dashboards
✅ Hook-based extensibility
✅ Easy to maintain
✅ Follows wp-app-core pattern
✅ Consistent with wp-agency implementation

---

## Testing Checklist

After implementation:

### Dashboard
- [ ] Page loads without errors
- [ ] Header displays correctly
- [ ] Statistics cards show loading spinner
- [ ] Statistics load via AJAX
- [ ] DataTable renders properly

### DataTable
- [ ] Server-side processing works
- [ ] Search functionality works
- [ ] Status filter works
- [ ] Column sorting works
- [ ] Pagination works

### Detail Panel
- [ ] Row click opens panel
- [ ] Panel slides in smoothly
- [ ] Staff details display correctly
- [ ] Tabs render properly
- [ ] Tab switching works
- [ ] Close button works

### Tabs
- [ ] Info tab shows staff details
- [ ] Placeholder tab shows empty state
- [ ] Tab navigation works (click, keyboard)
- [ ] Tab content loads correctly

### Assets
- [ ] CSS files load on platform staff page
- [ ] JavaScript files load correctly
- [ ] No console errors
- [ ] Responsive design works
- [ ] Panel animations smooth

### Integration
- [ ] Statistics AJAX endpoint works
- [ ] Details AJAX endpoint works
- [ ] Nonce validation works
- [ ] Permission checks work
- [ ] Cache flush works

---

## Migration Notes

### Breaking Changes

None - backward compatible

### New Features

1. Centralized DataTable system
2. Statistics cards
3. Tab system
4. Detail panel with smooth animations
5. Consistent UX with other dashboards

### Deprecated

None - old implementation will be replaced

---

## Related TODOs

- **TODO-1191**: PlatformStaffController separation (menu/assets)
- **TODO-1187**: Container structure simplification
- **wp-agency TODO-3086+**: Similar pattern implementation

---

## Implementation Order

1. ✅ Create TODO-1192 documentation
2. ⏳ Create PlatformStaffDataTableModel
3. ⏳ Create PlatformStaffDashboardController
4. ⏳ Create view partials (header, stats, buttons)
5. ⏳ Create tab templates (info, placeholder)
6. ⏳ Create CSS files (style, header-cards, filter)
7. ⏳ Create JavaScript datatable file
8. ⏳ Update class-dependencies.php
9. ⏳ Update MenuManager.php
10. ⏳ Test complete implementation

---

**Next Steps**: Implement PlatformStaffDataTableModel following AgencyDataTableModel pattern.
