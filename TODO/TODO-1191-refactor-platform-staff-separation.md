# TODO-1191: Refactor PlatformStaffController - Separate Menu & Assets

**Status**: ✅ Completed
**Priority**: MEDIUM
**Created**: 2025-11-01
**Completed**: 2025-11-01
**Plugin**: wp-app-core
**Category**: Code Quality, Architecture, Separation of Concerns

---

## Problem Statement

PlatformStaffController masih menggunakan pola lama dimana menu registration dan asset enqueue dilakukan di dalam controller. Seharusnya mengikuti pola wp-app-core yang sudah ada:
- Menu registration → MenuManager.php
- Asset enqueue → class-dependencies.php

### Current Implementation (WRONG)

**File**: `/wp-app-core/src/Controllers/Platform/PlatformStaffController.php`

**Lines 66-69**: Hook registration di constructor
```php
// Register menu page
add_action('admin_menu', [$this, 'registerMenu'], 20);

// Register assets
add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
```

**Lines 75-95**: Menu registration method
```php
public function registerMenu() {
    add_menu_page(
        __('Platform Staff', 'wp-app-core'),
        __('Platform Staff', 'wp-app-core'),
        'manage_options',
        'wp-app-core-platform-staff',
        [$this, 'renderDashboard'],
        'dashicons-groups',
        25
    );

    add_submenu_page(
        'wp-app-core-platform-staff',
        __('DataTable Test', 'wp-app-core'),
        __('🧪 DataTable Test', 'wp-app-core'),
        'manage_options',
        'wp-app-core-datatable-test',
        [$this, 'renderDataTableTest']
    );
}
```

**Lines 100+**: Asset enqueue method
```php
public function enqueueAssets($hook) {
    if ($hook !== 'toplevel_page_wp-app-core-platform-staff') {
        return;
    }

    // DataTables library from CDN
    wp_enqueue_style('datatables', ...);
    wp_enqueue_script('datatables', ...);

    // CSS
    wp_enqueue_style('wp-app-core-platform-staff', ...);
    wp_enqueue_style('wp-app-core-platform-staff-datatable', ...);

    // JavaScript
    wp_enqueue_script('wp-app-core-platform-staff', ...);
    wp_enqueue_script('wp-app-core-platform-staff-datatable', ...);

    // Localize script
    wp_localize_script(...);
}
```

---

## Why This is Wrong

### Violates Separation of Concerns
1. **Controller** seharusnya hanya handle business logic (CRUD, validation, response)
2. **MenuManager** seharusnya handle semua menu registration (centralized)
3. **Dependencies** seharusnya handle semua asset enqueue (centralized)

### Problems
- ❌ Mixing responsibilities (controller knows about UI/menu structure)
- ❌ Tidak consistent dengan MenuManager pattern yang sudah ada
- ❌ Asset loading logic scattered di berbagai controller
- ❌ Sulit maintain (harus cari di banyak file untuk update asset)
- ❌ Testing complexity (controller tightly coupled dengan WordPress hooks)

---

## Solution: Follow Existing Pattern

### Pattern Reference

**MenuManager.php** (CORRECT PATTERN):
```php
public function registerMenus() {
    add_menu_page(
        __('Platform Settings', 'wp-app-core'),
        __('Platform', 'wp-app-core'),
        'manage_options',
        'wp-app-core-settings',
        [$this->settings_controller, 'renderPage'],
        'dashicons-admin-generic',
        60
    );
}
```

**class-dependencies.php** (CORRECT PATTERN):
```php
public function enqueue_settings_assets($hook) {
    if ($hook !== 'toplevel_page_wp-app-core-settings') {
        return;
    }

    wp_enqueue_style('wp-app-core-settings', ...);
    wp_enqueue_script('wp-app-core-settings', ...);
}
```

---

## Implementation Steps

### Step 1: Update MenuManager.php

**File**: `/wp-app-core/src/Controllers/MenuManager.php`

**Add**:
```php
use WPAppCore\Controllers\Platform\PlatformStaffController;

class MenuManager {
    private $settings_controller;
    private $staff_controller; // Add this

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->settings_controller = new PlatformSettingsController();
        $this->staff_controller = new PlatformStaffController(); // Add this
    }

    public function registerMenus() {
        // Existing Platform Settings menu...

        // Platform Staff menu
        add_menu_page(
            __('Platform Staff', 'wp-app-core'),
            __('Platform Staff', 'wp-app-core'),
            'manage_options',
            'wp-app-core-platform-staff',
            [$this->staff_controller, 'renderDashboard'],
            'dashicons-groups',
            25
        );

        // DataTable Test submenu
        add_submenu_page(
            'wp-app-core-platform-staff',
            __('DataTable Test', 'wp-app-core'),
            __('🧪 DataTable Test', 'wp-app-core'),
            'manage_options',
            'wp-app-core-datatable-test',
            [$this->staff_controller, 'renderDataTableTest']
        );
    }
}
```

---

### Step 2: Update class-dependencies.php

**File**: `/wp-app-core/includes/class-dependencies.php`

**Add method**:
```php
/**
 * Enqueue Platform Staff assets
 *
 * @param string $hook Current admin page hook
 */
public function enqueue_platform_staff_assets($hook) {
    // Only load on platform staff pages
    if ($hook !== 'toplevel_page_wp-app-core-platform-staff' &&
        $hook !== 'platform-staff_page_wp-app-core-datatable-test') {
        return;
    }

    // DataTables library from CDN
    wp_enqueue_style(
        'datatables',
        'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css',
        [],
        '1.13.7'
    );

    wp_enqueue_script(
        'datatables',
        'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
        ['jquery'],
        '1.13.7',
        true
    );

    // Platform Staff CSS
    wp_enqueue_style(
        'wp-app-core-platform-staff',
        WP_APP_CORE_PLUGIN_URL . 'assets/css/platform/platform-staff-style.css',
        ['datatables'],
        $this->version
    );

    wp_enqueue_style(
        'wp-app-core-platform-staff-datatable',
        WP_APP_CORE_PLUGIN_URL . 'assets/css/platform/platform-staff-datatable-style.css',
        ['wp-app-core-platform-staff'],
        $this->version
    );

    // Platform Staff JavaScript
    wp_enqueue_script(
        'wp-app-core-platform-staff',
        WP_APP_CORE_PLUGIN_URL . 'assets/js/platform/platform-staff-script.js',
        ['jquery', 'datatables'],
        $this->version,
        true
    );

    wp_enqueue_script(
        'wp-app-core-platform-staff-datatable',
        WP_APP_CORE_PLUGIN_URL . 'assets/js/platform/platform-staff-datatable-script.js',
        ['jquery', 'datatables', 'wp-app-core-platform-staff'],
        $this->version,
        true
    );

    // Localize script
    wp_localize_script(
        'wp-app-core-platform-staff-datatable',
        'wpAppCoreStaff',
        [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_app_core_staff_nonce'),
            'strings' => [
                'confirm_delete' => __('Are you sure you want to delete this staff?', 'wp-app-core'),
                'error_generic' => __('An error occurred. Please try again.', 'wp-app-core'),
                'loading' => __('Loading...', 'wp-app-core')
            ]
        ]
    );
}
```

**Update constructor**:
```php
public function __construct($plugin_name, $version) {
    $this->plugin_name = $plugin_name;
    $this->version = $version;

    // Existing hooks...

    // Platform Staff assets
    add_action('admin_enqueue_scripts', [$this, 'enqueue_platform_staff_assets']);
}
```

---

### Step 3: Clean Up PlatformStaffController.php

**File**: `/wp-app-core/src/Controllers/Platform/PlatformStaffController.php`

**REMOVE from constructor** (lines 66-69):
```php
// REMOVE THESE LINES:
// Register menu page
add_action('admin_menu', [$this, 'registerMenu'], 20);

// Register assets
add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
```

**DELETE methods**:
- Delete `registerMenu()` method (lines 75-95)
- Delete `enqueueAssets()` method (lines 100+)

**Update version**:
```php
/**
 * @version     1.0.9
 *
 * Changelog:
 * 1.0.9 - 2025-11-01 (TODO-1191: Separation of Concerns)
 * - REMOVED: Menu registration (moved to MenuManager)
 * - REMOVED: Asset enqueue (moved to class-dependencies.php)
 * - Controller now focuses only on business logic
 * - Follows wp-app-core architectural pattern
 */
```

**Final constructor**:
```php
public function __construct() {
    $this->staff_model = new PlatformStaffModel();
    $this->cache = new PlatformCacheManager();
    $this->validator = new PlatformStaffValidator();

    // Register AJAX endpoints ONLY
    add_action('wp_ajax_handle_platform_staff_datatable', [$this, 'handleDataTableRequest']);
    add_action('wp_ajax_get_platform_staff_stats', [$this, 'getStatistics']);
    add_action('wp_ajax_get_platform_staff_details', [$this, 'getStaffDetails']);
    add_action('wp_ajax_create_platform_staff', [$this, 'createStaff']);
    add_action('wp_ajax_update_platform_staff', [$this, 'updateStaff']);
    add_action('wp_ajax_delete_platform_staff', [$this, 'deleteStaff']);

    // Register NEW DataTable system test endpoint
    \WPAppCore\Controllers\DataTable\DataTableController::register_ajax_action(
        'platform_staff_datatable_test',
        'WPAppCore\\Models\\Platform\\PlatformStaffDataTableModel'
    );
}
```

---

## Benefits

### After Refactoring

✅ **Separation of Concerns**:
- Controller = Business logic only
- MenuManager = All menu definitions
- Dependencies = All asset loading

✅ **Centralization**:
- All menus in ONE place (MenuManager)
- All assets in ONE place (Dependencies)

✅ **Maintainability**:
- Easy to find where menu is registered
- Easy to update asset versions
- Clear responsibility boundaries

✅ **Testability**:
- Controller can be tested without WordPress hooks
- Asset loading can be tested separately
- Menu structure can be verified independently

✅ **Consistency**:
- Follows existing wp-app-core pattern
- Same pattern as PlatformSettingsController
- Scalable for future controllers

---

## Files to Modify

1. `/wp-app-core/src/Controllers/MenuManager.php`
   - Add staff_controller property
   - Add staff menu registration
   - Version: 2.0.0 → 2.1.0

2. `/wp-app-core/includes/class-dependencies.php`
   - Add enqueue_platform_staff_assets() method
   - Register hook in constructor
   - Version: 1.1.1 → 1.2.0

3. `/wp-app-core/src/Controllers/Platform/PlatformStaffController.php`
   - Remove registerMenu() method
   - Remove enqueueAssets() method
   - Remove menu/asset hooks from constructor
   - Version: 1.0.8 → 1.0.9

---

## Testing Checklist

After implementation:

- [ ] Platform Staff menu appears in admin
- [ ] DataTable Test submenu appears
- [ ] CSS loads on platform staff page
- [ ] JavaScript loads on platform staff page
- [ ] DataTables library loads correctly
- [ ] Localized strings work
- [ ] AJAX endpoints still work
- [ ] No JavaScript errors in console
- [ ] No PHP errors in debug.log
- [ ] Page renders correctly
- [ ] Stats cards display
- [ ] DataTable functions properly

---

## Priority Justification

**MEDIUM Priority** because:
- Current code works (not broken)
- But violates architectural pattern
- Creates technical debt
- Makes future maintenance harder
- Good time to fix before more controllers added

---

## Implementation Order

1. Update MenuManager.php (add staff menu)
2. Update class-dependencies.php (add asset enqueue)
3. Test menu appears and assets load
4. Update PlatformStaffController.php (remove methods)
5. Final testing

---

## Related

- **MenuManager.php**: Existing pattern for PlatformSettingsController
- **class-dependencies.php**: Existing pattern for admin bar, settings assets
- **wp-agency**: Similar refactoring done in TODO-3XXX series
- **wp-customer**: Similar pattern already implemented

---

**Next Steps**: Implement refactoring following the steps above, focusing on separation of concerns and consistency with existing patterns.
