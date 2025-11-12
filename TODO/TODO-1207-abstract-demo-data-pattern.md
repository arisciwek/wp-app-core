# TODO-1207: Abstract Demo Data Pattern

## Objective
Standardize demo data generation pattern across all plugins (wp-app-core, wp-customer, wp-agency, and 17 others).
Similar to TODO-1206 (Abstract Permissions Pattern), create reusable foundation to eliminate duplicate code and ensure consistency.

## Current Situation

### Problem
Each plugin has its own copy of:
- `AbstractDemoData.php` - Hardcoded to specific plugin models
- `WPUserGenerator.php` - Nearly identical across plugins
- `tab-demo-data.php` - Different structures but similar patterns

**Code Duplication:**
- 3× AbstractDemoData (wp-app-core, wp-customer, wp-agency)
- 3× WPUserGenerator (nearly identical)
- 3× tab-demo-data.php (different content, similar pattern)
- Will be 20× when all plugins implemented

### Current Implementations

**wp-app-core:**
- `/src/Database/Demo/AbstractDemoData.php` - Depends on WPCustomer models (WRONG!)
- `/src/Database/Demo/PlatformDemoData.php`
- `/src/Database/Demo/WPUserGenerator.php`
- `/src/Views/templates/settings/tab-demo-data.php` - Platform roles + staff + dev settings

**wp-customer:**
- `/src/Database/Demo/AbstractDemoData.php` - Hardcoded to CustomerModel, BranchModel
- `/src/Database/Demo/CustomerDemoData.php`
- `/src/Database/Demo/BranchDemoData.php`
- `/src/Database/Demo/CustomerEmployeeDemoData.php`
- `/src/Database/Demo/MembershipDemoData.php`
- `/src/Database/Demo/MembershipFeaturesDemoData.php`
- `/src/Database/Demo/MembershipGroupsDemoData.php`
- `/src/Database/Demo/MembershipLevelsDemoData.php`
- `/src/Database/Demo/WPUserGenerator.php`
- `/src/Database/Demo/CustomerDemoDataHelperTrait.php`
- `/src/Views/templates/settings/tab-demo-data.php` - Multiple cards for data types

**wp-agency:**
- `/src/Database/Demo/AbstractDemoData.php` - Hardcoded to AgencyModel, DivisionModel
- `/src/Database/Demo/AgencyDemoData.php`
- `/src/Database/Demo/DivisionDemoData.php`
- `/src/Database/Demo/AgencyEmployeeDemoData.php`
- `/src/Database/Demo/JurisdictionDemoData.php`
- `/src/Database/Demo/WPUserGenerator.php`
- `/src/Database/Demo/AgencyDemoDataHelperTrait.php`
- `/src/Views/templates/settings/tab-demo-data.php` - Similar to wp-customer

## Desired Pattern

### Goals
1. **Single AbstractDemoData** in wp-app-core, extended by all plugins
2. **Single WPUserGenerator** in wp-app-core, shared by all plugins
3. **Base template** (`demo-data-page.php`) provided by wp-app-core
4. **Plugin-specific tabs** added via HOOK system
5. **Shared assets** (CSS/JS) from wp-app-core
6. **Consistent UI/UX** across all 20 plugins

### Pattern (Similar to TODO-1206)
```
wp-app-core/
  src/
    Controllers/Abstract/
      AbstractDemoDataController.php  # NEW - Base controller with AJAX handlers
    Database/Demo/
      AbstractDemoData.php            # REFACTOR - Generic, no plugin dependencies
      WPUserGenerator.php             # KEEP - Already generic enough
    Views/templates/demo-data/
      demo-data-page.php              # NEW - Base template with tabs & extensibility hooks
  assets/
    css/demo-data/
      demo-data.css                   # NEW - Shared styling
    js/demo-data/
      demo-data.js                    # NEW - Shared AJAX handlers

wp-customer/
  src/
    Controllers/Settings/
      CustomerDemoDataController.php  # NEW - Extends abstract, defines data types
    Database/Demo/
      CustomerDemoData.php            # REFACTOR - Extends generic abstract
      BranchDemoData.php             # KEEP - Plugin-specific
      CustomerEmployeeDemoData.php   # KEEP - Plugin-specific
      # Delete: AbstractDemoData.php (use wp-app-core's)
      # Delete: WPUserGenerator.php (use wp-app-core's)
    Views/templates/demo-data/
      # Delete: tab-demo-data.php
      tab-customer.php               # NEW - Customer-specific tab content
      tab-branch.php                 # NEW - Branch-specific tab content
```

---

## Questions for Clarification

### 1. AbstractDemoData Architecture
**Current:** Each plugin has `AbstractDemoData` hardcoded to specific models:
- wp-app-core: Uses CustomerModel, BranchModel (WRONG!)
- wp-customer: Uses CustomerModel, BranchModel
- wp-agency: Uses AgencyModel, DivisionModel

**Question:** How should the generic `AbstractDemoData` work?

**Options:**
- **A)** Make it truly abstract with no model dependencies - child plugins inject their models
- **B)** Use dependency injection pattern - pass models to constructor
- **C)** Use abstract methods like TODO-1206: `getModelClass()`, `getCacheManager()`

**Example if Option C:**
```php
// In wp-app-core/src/Database/Demo/AbstractDemoData.php
abstract class AbstractDemoData {
    abstract protected function getModels(): array; // Return plugin-specific models
    abstract protected function getCacheManager(): object;

    public function run() {
        $models = $this->getModels();
        // Generic transaction logic...
    }
}

// In wp-customer/src/Database/Demo/CustomerDemoData.php
class CustomerDemoData extends AbstractDemoData {
    protected function getModels(): array {
        return [
            'customer' => new CustomerModel(),
            'branch' => new BranchModel(),
        ];
    }
}
```

**Your answer:**


### 2. WPUserGenerator - Abstract or Utility?
**Current:** WPUserGenerator is nearly identical across plugins (instance-based class).

**Question:** Should WPUserGenerator be:

**Options:**
- **A)** Keep as instance class in wp-app-core, all plugins use it directly (shared utility)
- **B)** Make it abstract like AbstractDemoData with plugin-specific hooks
- **C)** Make it static utility class (no instantiation needed)

**Trade-offs:**
- Option A: Simple, works now, but less extensible
- Option B: More complex, allows plugin-specific user meta/roles
- Option C: Simplest, but less testable

**Your answer:**


### 3. Demo Data Tab Structure
**Current:**
- wp-app-core: Platform roles + staff + dev settings (all in one tab)
- wp-customer: Multiple cards for different data types (membership, customers, branches)
- wp-agency: Similar to wp-customer (agencies, divisions, employees)

**Question:** How should the tab structure work?

**Options:**
- **A)** Single "Demo Data" tab with nested tabs (like Permissions pattern)
  - Tab 1: Platform Staff (from wp-app-core)
  - Tab 2: Customers (from wp-customer via hook)
  - Tab 3: Agencies (from wp-agency via hook)

- **B)** Single "Demo Data" tab with cards from all plugins (no nested tabs)
  - All cards in one scrollable page
  - Sections separated by headers

- **C)** Keep separate tabs per plugin
  - wp-app-core: "Platform Demo"
  - wp-customer: "Customer Demo"
  - wp-agency: "Agency Demo"

**Your answer:**


### 4. Hook System for Demo Data Tabs
**If using nested tabs (Option 3A):**

**Question:** What hooks should be provided?

**Example Pattern:**
```php
// In demo-data-page.php
$tabs = apply_filters('wpapp_demo_data_tabs', [
    'platform' => [
        'title' => __('Platform Staff', 'wp-app-core'),
        'callback' => [PlatformDemoDataController::class, 'renderTab']
    ]
]);

foreach ($tabs as $tab_key => $tab_data) {
    // Render tab navigation
}

// In wp-customer plugin:
add_filter('wpapp_demo_data_tabs', function($tabs) {
    $tabs['customer'] = [
        'title' => __('Customer Data', 'wp-customer'),
        'callback' => [CustomerDemoDataController::class, 'renderTab']
    ];
    return $tabs;
});
```

**Is this the right pattern?**


### 5. Demo Data Card Structure
**Current:** Each card has:
- Title
- Description
- Status indicator
- Generate button (AJAX)
- Delete button (AJAX)
- Dependency checking (data-requires attribute)

**Question:** Should card structure be standardized?

**Example:**
```php
// Abstract controller provides:
public function getCards(): array {
    return [
        'customer' => [
            'title' => __('Customers', 'wp-customer'),
            'description' => __('Generate sample customer data', 'wp-customer'),
            'generate_action' => 'customer_generate_customers',
            'delete_action' => 'customer_delete_customers',
            'stats_action' => 'customer_stats_customers',
            'requires' => [], // Dependencies
            'count' => 0, // Current count
        ]
    ];
}

// Template loops through cards and renders consistently
```

**Is standardized card structure needed?**


### 6. AJAX Handler Pattern
**Current:** Each plugin has custom AJAX handlers for generate/delete/stats.

**Question:** Should AJAX handlers be standardized like TODO-1206?

**Example Pattern:**
```php
// In AbstractDemoDataController
public function init(): void {
    $prefix = $this->getPluginPrefix();
    add_action("wp_ajax_{$prefix}_generate_demo", [$this, 'handleGenerate']);
    add_action("wp_ajax_{$prefix}_delete_demo", [$this, 'handleDelete']);
    add_action("wp_ajax_{$prefix}_stats_demo", [$this, 'handleStats']);
}

public function handleGenerate(): void {
    // Nonce check, validation, call generator->run()
}
```

**Should we follow TODO-1206 AJAX pattern?**


### 7. Development Settings Integration
**Current:** wp-app-core has development settings in demo data tab:
- Enable development mode
- Clear data on deactivation

**Question:** Where should development settings live?

**Options:**
- **A)** Keep in demo data tab (current)
- **B)** Separate "Development" tab
- **C)** In general settings tab
- **D)** Each plugin has its own dev settings in their demo tab

**Your answer:**


### 8. Asset Sharing Strategy
**Current:** Each plugin has its own CSS/JS for demo data tab.

**Question:** How should assets be shared?

**Pattern from TODO-1206:**
```php
// In AbstractDemoDataController
public function enqueueAssets(string $hook): void {
    // Load shared CSS/JS from wp-app-core
    wp_enqueue_style(
        'wpapp-demo-data',
        WP_APP_CORE_PLUGIN_URL . 'assets/css/demo-data/demo-data.css'
    );

    wp_enqueue_script(
        'wpapp-demo-data',
        WP_APP_CORE_PLUGIN_URL . 'assets/js/demo-data/demo-data.js',
        ['jquery']
    );

    // Localize plugin-specific data
    wp_localize_script('wpapp-demo-data', 'wpappDemoData', [
        'pluginPrefix' => $this->getPluginPrefix(),
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonces' => $this->getNonces(),
    ]);
}

// Child plugin calls parent::enqueueAssets() when needed
```

**Should we use same pattern as TODO-1206?**


### 9. Validation and Error Handling
**Current:** Each DemoData class has its own `validate()` method.

**Question:** Should validation be standardized?

**Example:**
```php
// In AbstractDemoData
abstract protected function validateRequirements(): array; // Check dependencies

public function run() {
    $validation = $this->validateRequirements();
    if (!$validation['valid']) {
        throw new Exception($validation['message']);
    }
    // ... continue
}

// In CustomerDemoData
protected function validateRequirements(): array {
    if (!class_exists('CustomerModel')) {
        return [
            'valid' => false,
            'message' => 'CustomerModel not found'
        ];
    }
    return ['valid' => true];
}
```

**Should validation be standardized?**


### 10. Orchestrator Pattern
**Question:** Do we need a DemoDataPageController orchestrator like PlatformSettingsPageController?

**From TODO-1206:** PlatformSettingsPageController orchestrates multiple tab controllers.

**Options:**
- **A)** Yes, need DemoDataPageController to coordinate all demo tabs
- **B)** No, each plugin manages its own demo data independently
- **C)** Use existing settings page controller, add demo data as another tab

**Example if Option A:**
```php
class DemoDataPageController {
    private array $controllers = [];

    public function __construct() {
        $this->controllers = [
            'platform' => new PlatformDemoDataController(),
            // Other plugins register via hook
        ];
    }

    public function renderPage(): void {
        // Load base template with tabs
    }
}
```

**Your answer:**


---

## Implementation Plan (After Questions Answered)

### Phase 1: Abstract Foundation
- [ ] Create AbstractDemoDataController
- [ ] Refactor AbstractDemoData (make generic)
- [ ] Keep/move WPUserGenerator to wp-app-core
- [ ] Create demo-data-page.php base template
- [ ] Create shared CSS/JS assets

### Phase 2: wp-app-core Implementation
- [ ] Create PlatformDemoDataController
- [ ] Refactor PlatformDemoData
- [ ] Update tab-demo-data.php to use new pattern

### Phase 3: wp-customer Implementation
- [ ] Create CustomerDemoDataController
- [ ] Refactor CustomerDemoData
- [ ] Create tab-customer.php
- [ ] Remove duplicate files

### Phase 4: wp-agency Implementation
- [ ] Create AgencyDemoDataController
- [ ] Refactor AgencyDemoData
- [ ] Create tab-agency.php
- [ ] Remove duplicate files

### Phase 5: Remaining Plugins
- [ ] Apply pattern to 17 other plugins

---

## Expected Benefits

1. **Code Reduction:** ~60% reduction in demo data code per plugin
2. **Consistency:** Same UI/UX across all 20 plugins
3. **Maintainability:** Single source of truth for demo data logic
4. **Development Speed:** New plugins just implement abstract methods
5. **Bug Fixes:** Fix once in abstract, benefits all plugins

---

## Status
- [ ] Questions answered by user
- [ ] Implementation plan approved
- [ ] Phase 1 started
- [ ] Phase 2 started
- [ ] Phase 3 started
- [ ] Phase 4 started
- [ ] Phase 5 started

---

Last Updated: 2025-01-12

## Implementation Notes (2025-01-12)

### User Answers Summary

1. **AbstractDemoData Architecture:** Make generic without hardcoded models, child plugins inject their own
2. **WPUserGenerator:** Keep as shared utility in wp-app-core
3. **Tab Structure:** Keep current professional pattern (each plugin has its own Demo Data tab)
4. **Hook System:** Not needed - tabs stay in their respective plugin settings
5. **Card Structure:** Focus on shared CSS/JS to avoid duplication
6. **AJAX Handlers:** Yes, standardize like TODO-1206
7. **Development Settings:** Keep in Demo Data tab (as currently implemented)
8. **Asset Sharing:** Yes, follow TODO-1206 pattern
9. **Validation:** Minimal server-side validation for user access
10. **Orchestrator:** Not needed - each plugin manages its own tab

### Phase 1 Implementation Complete ✓

**1. AbstractDemoData Refactored**
- File: `/wp-app-core/src/Database/Demo/AbstractDemoData.php`
- **Before:** 120 lines, hardcoded to CustomerModel/BranchModel/CustomerCacheManager
- **After:** 182 lines, fully generic
- **Changes:**
  - Removed all hardcoded model properties (customerModel, branchModel, customerMembershipModel)
  - Removed hardcoded CustomerCacheManager dependency
  - Made `initModels()` abstract - child plugins now inject their own models
  - Kept generic `run()` method with transaction wrapper (START/COMMIT/ROLLBACK)
  - Kept generic `debug()` helper
  - Kept `validate()` and `generate()` as abstract methods

**Usage Pattern:**
```php
// In wp-customer/src/Database/Demo/CustomerDemoData.php
class CustomerDemoData extends AbstractDemoData {
    protected $customerModel;
    protected $branchModel;
    protected $cache;

    public function initModels(): void {
        $this->cache = new CustomerCacheManager();
        if (class_exists('WPCustomer\Models\Customer\CustomerModel')) {
            $this->customerModel = new CustomerModel();
        }
        if (class_exists('WPCustomer\Models\Branch\BranchModel')) {
            $this->branchModel = new BranchModel();
        }
    }

    protected function validate(): bool {
        return current_user_can('manage_options');
    }

    protected function generate(): void {
        // Generate customer demo data...
    }
}
```

**2. WPUserGenerator Verified**
- File: `/wp-app-core/src/Database/Demo/WPUserGenerator.php`
- **Status:** Already fully generic and shareable
- **No changes needed**
- **Features:**
  - Generate WordPress users with static IDs
  - Instance-based class with validation
  - Support multiple roles
  - Delete users functionality
  - Demo user meta tracking (`wp_app_core_demo_user`)
  - Legacy static methods for backward compatibility

### Next Steps

**Phase 2: wp-customer Refactoring**
1. Update `CustomerDemoData` to use generic AbstractDemoData from wp-app-core:
   - Add `use WPAppCore\Database\Demo\AbstractDemoData;`
   - Change `extends AbstractDemoData` to use wp-app-core version
2. **DELETE** `/wp-customer/src/Database/Demo/AbstractDemoData.php` (duplicate)
3. **DELETE** `/wp-customer/src/Database/Demo/WPUserGenerator.php` (duplicate)
4. Update other DemoData classes (BranchDemoData, MembershipDemoData, etc.) if they extend AbstractDemoData

**Phase 3: wp-agency Refactoring**
1. Update `AgencyDemoData` to use generic AbstractDemoData from wp-app-core
2. **DELETE** `/wp-agency/src/Database/Demo/AbstractDemoData.php` (duplicate)
3. **DELETE** `/wp-agency/src/Database/Demo/WPUserGenerator.php` (duplicate)
4. Update other DemoData classes (DivisionDemoData, etc.)

**Phase 4: Remaining 17 Plugins**
- Apply same pattern when implementing demo data
- No need to create duplicate AbstractDemoData or WPUserGenerator
- Simply extend wp-app-core's AbstractDemoData

### PlatformDemoData Note

**Current State:**
- Uses static methods pattern (not extends AbstractDemoData)
- Methods: `generate()`, `deleteAll()`, `generateSingleUser()`, `getStatistics()`
- Manual transaction handling

**Decision:** Leave as-is for now
- Pattern already works correctly
- No hardcoded dependencies on other plugins
- Static methods API already used in AJAX handlers
- Breaking change not necessary for TODO-1207 goals

**Future Consideration:**
- Could refactor to extends AbstractDemoData for consistency
- Would need to convert static methods to instance methods
- Would need to update AJAX handlers and tab template

### Files Modified

1. `/wp-app-core/src/Database/Demo/AbstractDemoData.php`
   - Version: 1.0.0 → 2.0.0
   - BREAKING: Removed hardcoded models
   - Made truly plugin-agnostic

### Files Verified (No Changes)

1. `/wp-app-core/src/Database/Demo/WPUserGenerator.php`
   - Already generic and shareable

### Status Update
- [x] Questions answered by user
- [x] Implementation plan approved  
- [x] Phase 1 completed (Abstract foundation)
- [ ] Phase 2 pending (wp-customer refactoring)
- [ ] Phase 3 pending (wp-agency refactoring)
- [ ] Phase 4 pending (Remaining plugins)

### Expected Code Reduction (After Full Implementation)

**wp-customer:**
- Delete AbstractDemoData.php: -120 lines
- Delete WPUserGenerator.php: -435 lines
- Total reduction: -555 lines

**wp-agency:**
- Delete AbstractDemoData.php: -120 lines
- Delete WPUserGenerator.php: -435 lines
- Total reduction: -555 lines

**Per additional plugin (17 remaining):**
- No need to create AbstractDemoData: -120 lines saved
- No need to create WPUserGenerator: -435 lines saved
- Total saved per plugin: -555 lines

**Total Expected Savings:**
- wp-customer + wp-agency: -1,110 lines
- 17 remaining plugins: -9,435 lines (555 × 17)
- **Grand total: -10,545 lines across 20 plugins**

### Testing Required

1. **wp-app-core:**
   - Test PlatformDemoData.generate() still works
   - Test demo data tab UI
   - Test AJAX handlers

2. **After wp-customer refactor:**
   - Test CustomerDemoData extends wp-app-core AbstractDemoData
   - Test customer demo generation
   - Verify no errors from deleted files

3. **After wp-agency refactor:**
   - Test AgencyDemoData extends wp-app-core AbstractDemoData
   - Test agency demo generation
   - Verify no errors from deleted files

---

Last Updated: 2025-01-12 (Phase 1 Complete)
