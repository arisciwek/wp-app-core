# TODO-2179: Implement Base Panel/Dashboard System (Frontend Layer)

**Status**: 🟢 Phase 1-7 Complete (Phase 8-9 Pending - Ready for Implementation Testing)
**Priority**: High
**Plugin**: wp-app-core
**Created**: 2025-10-23
**Last Updated**: 2025-10-23

**Progress**: 77% Complete (7/9 phases done)
- ✅ Phase 1-6: Foundation (Templates, CSS, JS, Controller, Documentation)
- ✅ Phase 7: Migration Examples
- ⏳ Phase 8: Integration Testing (task-2071: wp-agency implementation)
- ⏳ Phase 9: Cleanup
**Dependencies**:
- ✅ TODO-2178 (Backend DataTable System - **COMPLETED**)
- ✅ TODO-2174 (Companies Implementation - **COMPLETED** as proof of concept)

---

## 📋 Overview

Implementasi **base frontend/view layer** untuk DataTable dashboard dengan panel system di wp-app-core.

Saat ini setiap plugin (wp-customer, wp-company, wp-agency) memiliki CSS/JS yang hampir identik untuk:
- Left/Right panel layout
- Statistics cards
- Tab system (optional)
- Panel open/close animation (Perfex CRM style)
- Hash-based navigation

Goal: Standardisasi frontend layer supaya plugin hanya provide **content** via hooks, bukan **structure**.

---

## 🔗 Context & References

### Related TODOs:

1. **TODO-2178** (wp-app-core) - Backend layer ✅ **COMPLETED**
   - Path: `/wp-app-core/TODO/TODO-2178-implement-base-datatable-system.md`
   - Delivered: DataTableModel, QueryBuilder, Controller
   - Result: Backend hook system working

2. **TODO-2174** (wp-customer) - Implementation ✅ **COMPLETED**
   - Path: `/wp-customer/TODO/TODO-2174-implement-companies-datatable.md`
   - Delivered: Companies DataTable with hook-based permissions
   - Result: Proof that hook system works in practice

### What's Missing:

**Frontend/View layer** belum ter-standardize:
- ❌ Each plugin has own CSS (company-style.css, customer-style.css, etc.)
- ❌ Each plugin has own JS (company-script.js, customer-script.js, etc.)
- ❌ Duplication of panel layout code
- ❌ Duplication of stats cards HTML
- ❌ Duplication of tab system code

**This TODO will solve:**
- ✅ Create base templates in wp-app-core
- ✅ Global CSS for panel layouts
- ✅ Global JS for panel management
- ✅ Plugin hanya provide content via hooks

---

## 🎯 Goals

1. **Create base view templates** (`/wp-app-core/src/Views/DataTable/`)
2. **Standardize panel system** (left/right panel with smooth animation)
3. **Standardize tab system** (optional, WordPress-style tabs)
4. **Global CSS** untuk reusable components
5. **Global JS** untuk panel management (Perfex CRM pattern - no flicker)
6. **Migration path** untuk existing plugins

---

## 📊 Current State Analysis

### Existing Patterns (Duplication):

| Component | wp-customer | wp-company | wp-app-core (platform-staff) |
|-----------|-------------|------------|------------------------------|
| Container class | `.wp-customer-container` | `.wp-company-container` | `.wp-platform-staff-container` |
| Left panel | `.wp-customer-left-panel` | `.wp-company-left-panel` | `.wp-platform-staff-left-panel` |
| Right panel | `.wp-customer-right-panel` | `.wp-company-right-panel` | `.wp-platform-staff-right-panel` |
| Stats container | `.wi-stats-container` | `.wi-stats-container` | `.staff-stats-container` |
| Tab system | ✅ With filters | ✅ With filters | ❌ No tabs |

**Files with duplication:**
- `/wp-customer/assets/css/customer/customer-style.css` (235 lines)
- `/wp-customer/assets/css/company/company-style.css` (240 lines)
- `/wp-customer/assets/js/customer/customer-script.js` (450+ lines)
- `/wp-customer/assets/js/company/company-script.js` (450+ lines)

**Common functionality:**
- Panel open/close with smooth transition
- Left panel width transition (100% → 45% when right panel opens)
- Right panel fade in/out
- Hash-based navigation (#entity-123)
- Tab switching without page reload
- State management (currentId tracking)

---

## ✅ Progress Update (2025-10-23)

### Completed Phases:

**Phase 1: Directory Structure** ✅
- Created `/wp-app-core/src/Views/DataTable/Templates/`
- Created `/wp-app-core/assets/css/datatable/`
- Created `/wp-app-core/assets/js/datatable/`

**Phase 2: Base Templates** ✅
- ✅ `DashboardTemplate.php` (154 lines)
- ✅ `PanelLayoutTemplate.php` (166 lines)
- ✅ `TabSystemTemplate.php` (213 lines)
- ✅ `StatsBoxTemplate.php` (147 lines)

**Phase 3: Global CSS** ✅
- ✅ `wpapp-datatable.css` (412 lines)
  - Panel layout styles (Perfex CRM pattern)
  - Statistics cards with color variants
  - Tab system (WordPress style)
  - Responsive design (3 breakpoints)
  - Accessibility features
  - Print styles

**Phase 4: Global JavaScript** ✅
- ✅ `wpapp-panel-manager.js` (590+ lines)
  - Panel open/close with smooth animations
  - AJAX data loading
  - Hash-based navigation
  - Event system (8 events)
  - Error handling
- ✅ `wpapp-tab-manager.js` (280+ lines)
  - Tab switching without reload
  - Keyboard navigation (arrow keys)
  - Hash-based tab state
  - Event system (2 events)

**Phase 5: Asset Management** ✅
- ✅ `DataTableAssetsController.php` (200+ lines)
  - Conditional asset loading (per page hook)
  - Localization with i18n support
  - Hook system for extensibility
  - Force enqueue API

**Phase 6: Documentation** ✅
- ✅ `README.md` (1100+ lines comprehensive guide)
  - Overview & Architecture
  - Template Reference
  - Hook System (25+ hooks documented)
  - JavaScript API
  - Migration Guide (Before/After)
  - Best Practices
  - Troubleshooting
- ✅ `QUICK-REFERENCE.md` (400+ lines cheatsheet)
- ✅ Updated `/docs/INDEX.md` with frontend documentation links

### Files Created: 11 files

**PHP Templates (4):**
1. `DashboardTemplate.php` (154 lines)
2. `PanelLayoutTemplate.php` (166 lines)
3. `TabSystemTemplate.php` (213 lines)
4. `StatsBoxTemplate.php` (147 lines)

**Controllers (1):**
5. `DataTableAssetsController.php` (200 lines)

**CSS (1):**
6. `wpapp-datatable.css` (412 lines)

**JavaScript (2):**
7. `wpapp-panel-manager.js` (590 lines)
8. `wpapp-tab-manager.js` (280 lines)

**Documentation (3):**
9. `README.md` (1100+ lines)
10. `QUICK-REFERENCE.md` (400+ lines)
11. `MIGRATION-EXAMPLE.md` (900+ lines) ⭐ NEW

### Testing:
- ✅ PHP syntax verification (all files passed)
- ✅ Cache flushed
- ✅ Assets controller integrated in main plugin file

### Remaining Tasks:

**Phase 7: Migration Examples** ✅ COMPLETED (2025-10-23)
- ✅ Created comprehensive migration example (MIGRATION-EXAMPLE.md)
- ✅ Real-world wp-customer Companies context
- ✅ Before/After code comparison (1154 lines → 427 lines, 63% reduction)
- ✅ Step-by-step migration guide (10 steps)
- ✅ Testing checklist (30+ items)
- ✅ Common issues & solutions documented

**Phase 8: Testing** (Pending - Ready to Start)
- Linked to task-2071.md (wp-agency implementation)
- Will test with new wp-agency dashboard
- Integration testing with base panel system
- Browser compatibility testing
- Mobile responsive testing

**Phase 9: Cleanup** (Pending)
- Remove duplicated CSS/JS from wp-customer after migration
- Code review
- Performance optimization

---

## 📦 Tasks Breakdown

### Phase 1: Directory Structure

#### Task 1.1: Create Directory Structure

**Checklist**:
- [ ] Create `/wp-app-core/src/Views/DataTable/`
- [ ] Create `/wp-app-core/src/Views/DataTable/Templates/`
- [ ] Create `/wp-app-core/assets/css/datatable/`
- [ ] Create `/wp-app-core/assets/js/datatable/`

**Structure**:
```
wp-app-core/
├── src/
│   └── Views/
│       └── DataTable/
│           ├── Templates/
│           │   ├── DashboardTemplate.php       # Main dashboard container
│           │   ├── PanelLayoutTemplate.php     # Left/Right panel system
│           │   ├── TabSystemTemplate.php       # Optional tab system
│           │   └── StatsBoxTemplate.php        # Statistics cards
│           └── README.md
│
└── assets/
    ├── css/
    │   └── datatable/
    │       ├── wpapp-datatable.css            # Main styles
    │       └── wpapp-datatable-rtl.css        # RTL support (future)
    └── js/
        └── datatable/
            ├── wpapp-panel-manager.js         # Panel management
            └── wpapp-tab-manager.js           # Tab system (optional)
```

---

### Phase 2: Base Templates Implementation

#### Task 2.1: Create DashboardTemplate.php

**File**: `/wp-app-core/src/Views/DataTable/Templates/DashboardTemplate.php`

**Purpose**: Main dashboard container dengan hook points

**Checklist**:
- [ ] Create class `DashboardTemplate`
- [ ] Implement static `render($config)` method
- [ ] Add hook points:
  - `wpapp_dashboard_before_stats` action
  - `wpapp_dashboard_stats` filter (untuk stats boxes)
  - `wpapp_dashboard_after_stats` action
  - `wpapp_dashboard_before_content` action
  - `wpapp_dashboard_after_content` action
- [ ] Support optional stats display (`has_stats: true/false`)
- [ ] Support optional tab system (`has_tabs: true/false`)
- [ ] Add PHPDoc comments
- [ ] Enqueue CSS/JS automatically

**API Design**:
```php
use WPAppCore\Views\DataTable\Templates\DashboardTemplate;

DashboardTemplate::render([
    'entity' => 'customer',              // Entity name (for CSS classes)
    'title' => 'Customers',              // Page title
    'ajax_action' => 'get_customer_details', // AJAX action for panel
    'has_stats' => true,                 // Show statistics?
    'has_tabs' => true,                  // Enable tab system?
    'nonce' => wp_create_nonce('...'),   // Security nonce
]);
```

---

#### Task 2.2: Create PanelLayoutTemplate.php

**File**: `/wp-app-core/src/Views/DataTable/Templates/PanelLayoutTemplate.php`

**Purpose**: Left/Right panel system dengan smooth transitions

**Checklist**:
- [ ] Create class `PanelLayoutTemplate`
- [ ] Implement `render_layout($config)`
- [ ] Add data attributes untuk JS:
  - `data-entity="customer"`
  - `data-ajax-action="get_customer_details"`
  - `data-has-tabs="true"`
- [ ] Add hook points:
  - `wpapp_left_panel_header` action
  - `wpapp_left_panel_content` action (DataTable HTML)
  - `wpapp_left_panel_footer` action
  - `wpapp_right_panel_header` action
  - `wpapp_right_panel_content` action (Details/tabs)
  - `wpapp_right_panel_footer` action
- [ ] Standard CSS classes: `.wpapp-datatable-layout`, `.wpapp-left-panel`, `.wpapp-right-panel`

---

#### Task 2.3: Create TabSystemTemplate.php

**File**: `/wp-app-core/src/Views/DataTable/Templates/TabSystemTemplate.php`

**Purpose**: Optional tab system (WordPress style)

**Checklist**:
- [ ] Create class `TabSystemTemplate`
- [ ] Implement `render_tabs($entity)`
- [ ] Get tabs via filter:
  ```php
  $tabs = apply_filters('wpapp_datatable_tabs', [], $entity);
  ```
- [ ] Sort tabs by priority
- [ ] Render tab navigation (`.nav-tab-wrapper`)
- [ ] Render tab content containers (`.wpapp-tab-content`)
- [ ] Support tab template paths via filter
- [ ] First tab active by default

**Tab Structure**:
```php
$tabs = [
    'details' => [
        'title' => 'Details',
        'template' => '/path/to/template.php',
        'priority' => 10
    ],
    'membership' => [
        'title' => 'Membership',
        'template' => '/path/to/template.php',
        'priority' => 20
    ]
];
```

---

#### Task 2.4: Create StatsBoxTemplate.php

**File**: `/wp-app-core/src/Views/DataTable/Templates/StatsBoxTemplate.php`

**Purpose**: Reusable statistics cards

**Checklist**:
- [ ] Create class `StatsBoxTemplate`
- [ ] Implement `render_stats($entity)`
- [ ] Get stats via filter:
  ```php
  $stats = apply_filters('wpapp_datatable_stats', [], $entity);
  ```
- [ ] Render stats grid (flexbox)
- [ ] Each stat box: icon, number, label
- [ ] Auto-hide if no stats defined

**Stats Structure**:
```php
$stats = [
    [
        'id' => 'total-customers',
        'label' => 'Total Customers',
        'icon' => 'dashicons-groups',  // Optional
        'class' => 'primary'           // Optional: primary, success, warning, danger
    ]
];
```

---

### Phase 3: Global CSS Implementation

#### Task 3.1: Create wpapp-datatable.css

**File**: `/wp-app-core/assets/css/datatable/wpapp-datatable.css`

**Checklist**:
- [ ] **Panel Layout Styles**:
  ```css
  .wpapp-datatable-layout {
      display: flex;
      gap: 20px;
      transition: all 0.3s ease;
  }

  .wpapp-left-panel {
      width: 100%;
      transition: width 0.3s ease;
      background: #fff;
      border: 1px solid #ccd0d4;
      border-radius: 4px;
  }

  .wpapp-datatable-layout.with-right-panel .wpapp-left-panel {
      width: 45%;
  }

  .wpapp-right-panel {
      width: 55%;
      background: #fff;
      border: 1px solid #ccd0d4;
      border-radius: 4px;
      opacity: 0;
      transition: opacity 0.3s ease;
  }

  .wpapp-right-panel.hidden {
      display: none;
  }

  .wpapp-right-panel.visible {
      display: block;
      opacity: 1;
  }
  ```

- [ ] **Statistics Cards**:
  ```css
  .wpapp-stats-container {
      display: flex;
      gap: 20px;
      margin: 15px 0;
      flex-wrap: wrap;
  }

  .wpapp-stat-box {
      background: #fff;
      border: 1px solid #ccd0d4;
      padding: 15px 20px;
      border-radius: 4px;
      flex: 1;
      min-width: 200px;
      text-align: center;
  }

  .wpapp-stat-number {
      font-size: 24px;
      font-weight: bold;
      color: #2271b1;
      margin: 10px 0 0;
  }
  ```

- [ ] **Tab System**:
  ```css
  .wpapp-tab-content {
      display: none;
      padding: 20px;
  }

  .wpapp-tab-content.active {
      display: block;
  }
  ```

- [ ] **Panel Headers**:
  ```css
  .wpapp-panel-header {
      padding: 15px 20px;
      border-bottom: 1px solid #ccd0d4;
      display: flex;
      justify-content: space-between;
      align-items: center;
  }

  .wpapp-panel-close {
      background: none;
      border: none;
      color: #666;
      font-size: 24px;
      cursor: pointer;
  }

  .wpapp-panel-close:hover {
      color: #d63638;
  }
  ```

- [ ] **Responsive Design** (mobile):
  ```css
  @media (max-width: 768px) {
      .wpapp-datatable-layout {
          flex-direction: column;
      }

      .wpapp-datatable-layout.with-right-panel .wpapp-left-panel,
      .wpapp-right-panel {
          width: 100%;
      }
  }
  ```

---

### Phase 4: Global JavaScript Implementation

#### Task 4.1: Create wpapp-panel-manager.js

**File**: `/wp-app-core/assets/js/datatable/wpapp-panel-manager.js`

**Purpose**: Panel management (Perfex CRM pattern - no flicker)

**Checklist**:
- [ ] Create `WPAppPanelManager` object
- [ ] Implement methods:
  - `init(config)` - Initialize with entity config
  - `openPanel(id, data)` - Open right panel with smooth animation
  - `closePanel()` - Close right panel
  - `loadData(id)` - Load data via AJAX
  - `populatePanel(data)` - Trigger hook untuk populate data
  - `handleHash()` - Handle URL hash on page load
  - `updateHash(id)` - Update URL hash without reload
- [ ] State management:
  - `currentId` - Track current open item
  - `isLoading` - Prevent duplicate loads
- [ ] jQuery hooks/events:
  - `wpapp:panel-opened` - Fire after panel opened
  - `wpapp:panel-closed` - Fire after panel closed
  - `wpapp:panel-data-loaded` - Fire after data loaded
- [ ] Auto-init on document ready:
  ```javascript
  $(document).ready(function() {
      const $layout = $('.wpapp-datatable-layout');
      if ($layout.length) {
          const entity = $layout.data('entity');
          const ajaxAction = $layout.data('ajax-action');
          WPAppPanelManager.init({ entity, ajaxAction });
      }
  });
  ```

**Key Features**:
```javascript
const WPAppPanelManager = {
    config: {},
    currentId: null,
    isLoading: false,

    openPanel: function(id) {
        if (this.isLoading) return;

        const $layout = $('.wpapp-datatable-layout');
        const $rightPanel = $('.wpapp-right-panel');

        this.loadData(id).then(data => {
            // Trigger event untuk plugin populate data
            $(document).trigger('wpapp:panel-data-loaded', [data, this.config.entity]);

            // Show panel dengan smooth animation
            $layout.addClass('with-right-panel');
            $rightPanel.removeClass('hidden').addClass('visible');

            // Update hash
            this.updateHash(id);
            this.currentId = id;

            $(document).trigger('wpapp:panel-opened', [id, data]);
        });
    },

    closePanel: function() {
        const $layout = $('.wpapp-datatable-layout');
        const $rightPanel = $('.wpapp-right-panel');

        $layout.removeClass('with-right-panel');
        $rightPanel.removeClass('visible').addClass('hidden');

        window.location.hash = '';
        this.currentId = null;

        $(document).trigger('wpapp:panel-closed');
    }
};
```

---

#### Task 4.2: Create wpapp-tab-manager.js

**File**: `/wp-app-core/assets/js/datatable/wpapp-tab-manager.js`

**Purpose**: Tab switching without reload

**Checklist**:
- [ ] Create `WPAppTabManager` object
- [ ] Implement methods:
  - `init()` - Initialize tab system
  - `switchTab(tabId)` - Switch to tab
  - `handleHashTab()` - Handle ?tab=xxx in hash
  - `updateTabHash(tabId)` - Update hash with tab
- [ ] Auto-init on document ready
- [ ] Tab click event binding
- [ ] Hash-based tab on page load

**Implementation**:
```javascript
const WPAppTabManager = {
    init: function() {
        this.bindEvents();
        this.handleHashTab();
    },

    switchTab: function(tabId) {
        // Hide all tabs
        $('.wpapp-tab-content').removeClass('active');
        $('.nav-tab').removeClass('nav-tab-active');

        // Show selected tab
        $(`#${tabId}`).addClass('active');
        $(`.nav-tab[data-tab="${tabId}"]`).addClass('nav-tab-active');

        // Update hash
        this.updateTabHash(tabId);
    },

    bindEvents: function() {
        $(document).on('click', '.nav-tab', function(e) {
            e.preventDefault();
            const tabId = $(this).data('tab');
            WPAppTabManager.switchTab(tabId);
        });
    }
};
```

---

### Phase 5: Asset Management

#### Task 5.1: Enqueue CSS/JS Globally

**File**: `/wp-app-core/src/Controllers/DataTable/DataTableAssetsController.php`

**Checklist**:
- [ ] Create `DataTableAssetsController` class
- [ ] Register CSS:
  - `wpapp-datatable-css` → `assets/css/datatable/wpapp-datatable.css`
- [ ] Register JS:
  - `wpapp-panel-manager` → `assets/js/datatable/wpapp-panel-manager.js`
  - `wpapp-tab-manager` → `assets/js/datatable/wpapp-tab-manager.js`
- [ ] Enqueue on admin pages only
- [ ] Localize script dengan config:
  ```php
  wp_localize_script('wpapp-panel-manager', 'wpAppConfig', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('wpapp_panel_nonce'),
      'debug' => WP_DEBUG
  ]);
  ```
- [ ] Hook to `admin_enqueue_scripts`

---

### Phase 6: Documentation

#### Task 6.1: Create Usage Documentation

**File**: `/wp-app-core/src/Views/DataTable/README.md`

**Checklist**:
- [ ] Overview of system
- [ ] Quick start guide
- [ ] API reference:
  - `DashboardTemplate::render()`
  - Available hooks/filters
  - JavaScript events
- [ ] Code examples for each template
- [ ] Migration guide from old pattern
- [ ] Troubleshooting section

---

#### Task 6.2: Update Main DataTable Docs

**File**: `/wp-app-core/docs/datatable/ARCHITECTURE.md`

**Checklist**:
- [ ] Add "Frontend/View Layer" section
- [ ] Document template system
- [ ] Document CSS classes
- [ ] Document JS API
- [ ] Add diagrams showing full stack

---

### Phase 7: Migration Examples

#### Task 7.1: Create Migration Guide

**File**: `/wp-app-core/docs/datatable/MIGRATION-TO-BASE-TEMPLATES.md`

**Checklist**:
- [ ] **Before/After comparison**:
  - Old pattern (custom CSS/JS per plugin)
  - New pattern (base templates + hooks)
- [ ] **Step-by-step migration**:
  1. Remove old CSS/JS files
  2. Use `DashboardTemplate::render()`
  3. Register hooks for content
  4. Test panel functionality
- [ ] **Code examples** for:
  - Customer migration
  - Company migration
  - Agency migration
- [ ] **Breaking changes** (if any)
- [ ] **Checklist** untuk verify migration

---

#### Task 7.2: Create wp-customer Migration Example

**File**: `/wp-customer/docs/migration/migrate-to-base-templates.md`

**Purpose**: Show migration untuk wp-customer sebagai contoh

**Checklist**:
- [ ] Document current state (customer-style.css, customer-script.js)
- [ ] Show new implementation using base templates
- [ ] List files to remove
- [ ] List hooks to implement
- [ ] Test checklist

**Before**:
```php
// OLD: Custom view file
include 'customer-dashboard.php'; // 200+ lines of HTML
```

**After**:
```php
// NEW: Use base template
DashboardTemplate::render([
    'entity' => 'customer',
    'title' => 'Customers',
    'ajax_action' => 'get_customer_details',
    'has_stats' => true,
    'has_tabs' => true
]);

// Register stats
add_filter('wpapp_datatable_stats', function($stats, $entity) {
    if ($entity !== 'customer') return $stats;
    return [...];
}, 10, 2);

// Register left panel (DataTable)
add_action('wpapp_left_panel_content', function($config) {
    if ($config['entity'] !== 'customer') return;
    include WP_CUSTOMER_PATH . 'src/Views/customers/datatable.php';
});
```

---

### Phase 8: Testing

#### Task 8.1: Unit Testing

**Checklist**:
- [ ] Test DashboardTemplate::render() generates correct HTML
- [ ] Test hooks fire in correct order
- [ ] Test CSS classes applied correctly
- [ ] Test data attributes set correctly
- [ ] Test optional features (stats, tabs) work

---

#### Task 8.2: Integration Testing

**Scenarios**:
- [ ] **Test with wp-customer**:
  - Migrate customer dashboard
  - Panel opens smoothly
  - Tabs switch correctly
  - Hash navigation works
  - No console errors

- [ ] **Test with wp-company**:
  - Migrate company dashboard
  - Same functionality as customer

- [ ] **Test with platform-staff**:
  - Already in wp-app-core
  - Should work without changes

- [ ] **Test without tabs**:
  - platform-staff doesn't use tabs
  - Should work with `has_tabs: false`

- [ ] **Test responsive**:
  - Mobile layout
  - Tablet layout
  - Desktop layout

---

#### Task 8.3: Browser Compatibility

**Checklist**:
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Safari
- [ ] Mobile Chrome

---

### Phase 9: Cleanup & Polish

#### Task 9.1: Remove Duplicated Code

**After migration verified working**:

**In wp-customer**:
- [ ] Delete `/assets/css/customer/customer-style.css` (keep only custom overrides)
- [ ] Delete `/assets/js/customer/customer-script.js` (keep only business logic)
- [ ] Delete `/src/Views/templates/customer/customer-dashboard.php`
- [ ] Delete `/src/Views/templates/customer/customer-left-panel.php`
- [ ] Delete `/src/Views/templates/customer/customer-right-panel.php`

**In wp-customer (company context)**:
- [ ] Delete `/assets/css/company/company-style.css`
- [ ] Delete `/assets/js/company/company-script.js`
- [ ] Delete company dashboard templates

**Keep only**:
- Entity-specific styles (colors, unique layouts)
- Business logic JavaScript
- Tab content templates

---

#### Task 9.2: Code Review

**Checklist**:
- [ ] WordPress Coding Standards
- [ ] PHPDoc complete
- [ ] JavaScript documented
- [ ] CSS organized and commented
- [ ] No hardcoded values
- [ ] Proper escaping/sanitization
- [ ] Performance optimized
- [ ] Accessibility (ARIA labels)

---

## 🔧 Technical Notes

### CSS Class Naming Convention

**Global classes** (wp-app-core):
```css
.wpapp-datatable-layout         # Main container
.wpapp-left-panel               # Left panel
.wpapp-right-panel              # Right panel
.wpapp-stats-container          # Stats grid
.wpapp-stat-box                 # Individual stat card
.wpapp-panel-header             # Panel header
.wpapp-panel-content            # Panel content
.wpapp-panel-close              # Close button
.wpapp-tab-content              # Tab content container
```

**Plugin-specific classes** (wp-customer):
```css
.customer-specific-style        # Only for unique customer styling
.customer-custom-button         # Custom button style
```

### JavaScript Event System

**Events fired by wp-app-core**:
```javascript
// Panel events
'wpapp:panel-opened' (id, data)
'wpapp:panel-closed' ()
'wpapp:panel-data-loaded' (data, entity)

// Tab events
'wpapp:tab-switched' (tabId)
```

**Plugin listens to events**:
```javascript
$(document).on('wpapp:panel-data-loaded', function(e, data, entity) {
    if (entity !== 'customer') return;

    // Populate customer-specific fields
    $('#customer-name').text(data.name);
    $('#customer-email').text(data.email);
});
```

---

## 📊 Performance Considerations

### CSS
- [ ] Use CSS transitions instead of JavaScript animations
- [ ] Minimize repaints/reflows
- [ ] Use transform instead of width for animations (GPU accelerated)

### JavaScript
- [ ] Debounce resize events
- [ ] Use event delegation for dynamic elements
- [ ] Cache jQuery selectors
- [ ] Minimize DOM manipulation

### Loading
- [ ] Load CSS in `<head>`
- [ ] Load JS in footer
- [ ] Minify in production
- [ ] Consider CDN for assets (future)

---

## 🔍 Success Criteria

- [ ] All Phase 1-9 tasks completed
- [ ] Base templates working in wp-app-core
- [ ] At least 1 plugin migrated successfully (wp-customer)
- [ ] Panel animations smooth (60fps)
- [ ] No console errors
- [ ] No visual regressions
- [ ] Code duplication reduced by >70%
- [ ] Migration guide complete
- [ ] Documentation updated
- [ ] Tests passing

---

## 📝 Definition of Done

- [ ] All tasks completed
- [ ] Code reviewed
- [ ] Tests passing
- [ ] Documentation complete
- [ ] Migration guide tested
- [ ] At least 1 plugin migrated as proof
- [ ] No breaking changes for non-migrated plugins
- [ ] Performance benchmarks met
- [ ] Browser compatibility verified
- [ ] Accessibility checked
- [ ] Ready for production

---

## 🚀 Next Steps After Completion

1. **Migrate wp-customer** (customer context)
2. **Migrate wp-customer** (company context)
3. **Migrate wp-customer** (company-invoice context)
4. **Migrate wp-agency** (if has similar pattern)
5. **Deprecate old pattern** (add deprecation notices)

---

## 📚 References

- **TODO-2178**: Backend DataTable System (wp-app-core)
  - Path: `/wp-app-core/TODO/TODO-2178-implement-base-datatable-system.md`

- **TODO-2174**: Companies Implementation (wp-customer)
  - Path: `/wp-customer/TODO/TODO-2174-implement-companies-datatable.md`

- **Existing Templates** (to be replaced):
  - `/wp-customer/src/Views/templates/customer/customer-dashboard.php`
  - `/wp-customer/src/Views/templates/company/company-dashboard.php`
  - `/wp-app-core/src/Views/templates/platform-staff/platform-staff-dashboard.php`

- **Existing CSS** (to be consolidated):
  - `/wp-customer/assets/css/customer/customer-style.css`
  - `/wp-customer/assets/css/company/company-style.css`

- **Existing JS** (to be consolidated):
  - `/wp-customer/assets/js/customer/customer-script.js`
  - `/wp-customer/assets/js/company/company-script.js`

- **DataTable Documentation**:
  - `/wp-app-core/docs/datatable/ARCHITECTURE.md`
  - `/wp-app-core/docs/datatable/core/IMPLEMENTATION.md`

---

## 💡 Implementation Tips

1. **Start small**: Implement DashboardTemplate first, test with platform-staff
2. **Incremental migration**: Migrate one plugin at a time
3. **Keep old code**: Don't delete until new system verified working
4. **Test thoroughly**: Each template independently, then together
5. **Document as you go**: Update docs immediately after each feature
6. **Get feedback**: Show each phase to team for review
7. **Version control**: Commit after each working phase

---

## ⚠️ Potential Issues & Solutions

### Issue: Plugin CSS conflicts with global CSS
**Solution**: Use more specific selectors, allow plugin override via `!important` in custom CSS

### Issue: Hash navigation conflicts
**Solution**: Use entity prefix in hash: `#customer-123`, `#company-456`

### Issue: Multiple entities on same page
**Solution**: Use data attributes to scope JavaScript: `data-entity="customer"`

### Issue: Old plugins still work after base system added
**Solution**: Ensure backward compatibility - old and new can coexist during migration

### Issue: Performance with many stats/tabs
**Solution**: Lazy load tab content, render stats on demand

---

**Created**: 2025-10-23
**Estimated Time**: 4-5 days (implementation + testing + migration)
**Start Date**: TBD
**Target Completion**: TBD
**Actual Completion**: TBD

---

*Created by: arisciwek*
*Last Updated: 2025-10-23*
