# WP App Core - DataTable Panel System

**Comprehensive Documentation**

Version: 1.0.0
Last Updated: 2025-10-23
Author: arisciwek

## File Reference

**Template Classes:**
- `src/Views/DataTable/Templates/DashboardTemplate.php` - Main dashboard container
- `src/Views/DataTable/Templates/PanelLayoutTemplate.php` - Left/right panel layout
- `src/Views/DataTable/Templates/TabSystemTemplate.php` - WordPress-style tabs
- `src/Views/DataTable/Templates/StatsBoxTemplate.php` - Statistics cards
- `src/Views/DataTable/Templates/FiltersTemplate.php` - Filter controls

**Assets:**
- `assets/css/datatable/wpapp-datatable.css` - Global panel styles
- `assets/js/datatable/wpapp-panel-manager.js` - Panel open/close, AJAX
- `assets/js/datatable/wpapp-tab-manager.js` - Tab switching

**Quick Reference:**
- `src/Views/DataTable/QUICK-REFERENCE.md` - Common tasks quick lookup
- `src/Views/DataTable/MIGRATION-EXAMPLE.md` - Real migration example

---

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Quick Start](#quick-start)
4. [Template Reference](#template-reference)
5. [Hook System](#hook-system)
6. [JavaScript API](#javascript-api)
7. [Migration Guide](#migration-guide)
8. [Best Practices](#best-practices)
9. [Troubleshooting](#troubleshooting)

---

## Overview

### What is DataTable Panel System?

The DataTable Panel System is a reusable, extensible framework for building admin dashboard pages with:

- **Left Panel**: DataTable listing (e.g., customers, companies, employees)
- **Right Panel**: Detail view with tabs (e.g., customer details, membership, invoices)
- **Statistics Cards**: Reusable stat boxes (e.g., total customers, active members)
- **Smooth Animations**: Perfex CRM-style transitions (no flicker)

### Why Use This System?

**Before (Duplicated Code):**
```
wp-customer/
  ├── assets/css/customer-style.css      (300 lines)
  ├── assets/css/company-style.css       (300 lines)
  ├── assets/js/customer-panel.js        (200 lines)
  ├── assets/js/company-panel.js         (200 lines)
  └── ...nearly identical code...

wp-agency/
  ├── assets/css/employee-style.css      (300 lines)
  ├── assets/js/employee-panel.js        (200 lines)
  └── ...nearly identical code...
```

**After (Centralized in wp-app-core):**
```
wp-app-core/
  ├── assets/css/datatable/wpapp-datatable.css  (400 lines, shared)
  ├── assets/js/datatable/wpapp-panel-manager.js (shared)
  └── src/Views/DataTable/Templates/            (base templates)

Plugins only need to:
  - Hook into filters/actions
  - Provide content templates
  - Register stats/tabs
```

**Benefits:**
- ✅ No code duplication
- ✅ Consistent UX across all plugins
- ✅ Easier maintenance (fix once, apply everywhere)
- ✅ Extensible via WordPress hook system

---

## Architecture

### Component Structure

```
wp-app-core/
├── src/
│   ├── Controllers/
│   │   └── DataTable/
│   │       └── DataTableAssetsController.php   # Asset management
│   └── Views/
│       └── DataTable/
│           └── Templates/
│               ├── DashboardTemplate.php        # Main dashboard
│               ├── PanelLayoutTemplate.php      # Left/right panels
│               ├── TabSystemTemplate.php        # Tab navigation
│               └── StatsBoxTemplate.php         # Statistics cards
├── assets/
│   ├── css/
│   │   └── datatable/
│   │       └── wpapp-datatable.css             # Global styles (v1.1.0)
│   └── js/
│       └── datatable/
│           ├── wpapp-panel-manager.js          # Panel open/close
│           └── wpapp-tab-manager.js            # Tab switching
└── docs/
    └── datatable/                              # Documentation
```

**Version 1.1.0 (TODO-1187)**: Simplified container structure for consistent spacing

### Container Structure & Full Width Pattern

All dashboard containers use a consistent pattern with **negative margins** to utilize full available width:

```css
/* All containers extend to full width using negative margin */
.wpapp-page-header-container {
    margin: 0 -15px 0 -15px;  /* Extends 15px on both sides */
}

.wpapp-statistics-container {
    margin: 20px -15px 20px -15px;
}

.wpapp-filters-container {
    margin: 0 -15px 20px -15px;
}

.wpapp-datatable-container {
    margin: 0 -15px 20px -15px;
}
```

**Why -15px margin?**
- WordPress `.wrap` class has 20px left/right padding by default
- Negative 15px margin extends containers to utilize full width
- Creates consistent boundaries across all dashboard sections
- Eliminates wasted space on left and right sides

**Visual Result:**
```
┌──────────────────────────────────────────────────────┐
│ WordPress Admin Wrap (padding: 20px)                 │
│  ┌────────────────────────────────────────────────┐  │
│  │ Page Header (margin: 0 -15px)                  │  │
│  └────────────────────────────────────────────────┘  │
│  ┌────────────────────────────────────────────────┐  │
│  │ Statistics (margin: 20px -15px)                │  │
│  └────────────────────────────────────────────────┘  │
│  ┌────────────────────────────────────────────────┐  │
│  │ Filters (margin: 0 -15px 20px)                 │  │
│  └────────────────────────────────────────────────┘  │
│  ┌────────────────────────────────────────────────┐  │
│  │ DataTable (margin: 0 -15px 20px)               │  │
│  └────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────┘
```

All containers align perfectly with consistent boundaries.

### Data Flow

```
┌─────────────────────────────────────────────────────────────┐
│ DashboardTemplate::render()                                 │
│  ├─ Validates config                                        │
│  ├─ Renders statistics (optional)                           │
│  └─ Calls PanelLayoutTemplate::render()                     │
│      ├─ Left Panel                                          │
│      │   └─ do_action('wpapp_left_panel_content')           │
│      │       └─ Plugin hooks here to render DataTable HTML  │
│      └─ Right Panel                                         │
│          └─ TabSystemTemplate::render() OR                  │
│              do_action('wpapp_right_panel_content')         │
└─────────────────────────────────────────────────────────────┘
```

---

## Quick Start

### Basic Usage

```php
<?php
/**
 * File: wp-customer/src/Views/customers/dashboard.php
 */

use WPAppCore\Views\DataTable\Templates\DashboardTemplate;

// Render dashboard with all features
DashboardTemplate::render([
    'entity' => 'customer',
    'title' => __('Customers', 'wp-customer'),
    'ajax_action' => 'get_customer_details',
    'has_stats' => true,
    'has_tabs' => true,
]);
```

### Configuration Options

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `entity` | string | ✅ Yes | Entity identifier (customer, company, employee) |
| `title` | string | ❌ No | Page title (defaults to ucfirst(entity)) |
| `ajax_action` | string | ❌ No | AJAX action for loading panel data |
| `has_stats` | bool | ❌ No | Show statistics section (default: false) |
| `has_tabs` | bool | ❌ No | Enable tab system in right panel (default: false) |
| `nonce` | string | ❌ No | Security nonce (auto-generated if not provided) |

---

## Template Reference

### 1. DashboardTemplate

Main container for the entire dashboard page.

**File:** `src/Views/DataTable/Templates/DashboardTemplate.php`

**Usage:**
```php
use WPAppCore\Views\DataTable\Templates\DashboardTemplate;

DashboardTemplate::render([
    'entity' => 'customer',
    'title' => 'Customers',
    'has_stats' => true,
    'has_tabs' => true,
]);
```

**Hooks Provided:**
- `wpapp_dashboard_before_content` - Before all content
- `wpapp_dashboard_before_stats` - Before statistics section
- `wpapp_dashboard_after_stats` - After statistics section
- `wpapp_dashboard_after_content` - After all content

---

### 2. PanelLayoutTemplate

Left/right panel system with smooth transitions.

**File:** `src/Views/DataTable/Templates/PanelLayoutTemplate.php`

**HTML Structure:**
```html
<div class="wpapp-datatable-layout">
    <div class="wpapp-left-panel">
        <!-- DataTable listing here -->
    </div>
    <div class="wpapp-right-panel hidden">
        <!-- Detail view here -->
    </div>
</div>
```

**Hooks Provided:**

**Left Panel:**
- `wpapp_left_panel_header` - Header area (title, buttons)
- `wpapp_left_panel_content` - **Main content area** (DataTable HTML)
- `wpapp_left_panel_footer` - Footer area

**Right Panel:**
- `wpapp_right_panel_header` - Header area (entity name, close button)
- `wpapp_right_panel_content` - Content area (when tabs disabled)
- `wpapp_right_panel_footer` - Footer area (action buttons)

**Example: Hook into left panel content:**
```php
add_action('wpapp_left_panel_content', function($config) {
    if ($config['entity'] !== 'customer') return;

    // Render DataTable HTML
    include WP_CUSTOMER_PATH . 'src/Views/customers/datatable.php';
}, 10, 1);
```

---

### 3. TabSystemTemplate

WordPress-style tab navigation with priority-based sorting.

**File:** `src/Views/DataTable/Templates/TabSystemTemplate.php`

**Tab Structure:**
```php
[
    'tab-id' => [
        'title' => 'Tab Title',        // Display title
        'template' => '/path/to/file', // Template file path
        'priority' => 10               // Sorting order (lower = first)
    ]
]
```

**Register Tabs via Filter:**
```php
add_filter('wpapp_datatable_tabs', function($tabs, $entity) {
    if ($entity !== 'customer') {
        return $tabs;
    }

    return [
        'details' => [
            'title' => __('Customer Details', 'wp-customer'),
            'template' => WP_CUSTOMER_PATH . 'src/Views/customers/tabs/details.php',
            'priority' => 10
        ],
        'membership' => [
            'title' => __('Membership', 'wp-customer'),
            'template' => WP_CUSTOMER_PATH . 'src/Views/customers/tabs/membership.php',
            'priority' => 20
        ],
        'invoices' => [
            'title' => __('Invoices', 'wp-customer'),
            'template' => WP_CUSTOMER_PATH . 'src/Views/customers/tabs/invoices.php',
            'priority' => 30
        ]
    ];
}, 10, 2);
```

**Hooks Provided:**
- `wpapp_datatable_tabs` (filter) - Register tabs
- `wpapp_datatable_tab_template` (filter) - Override template path
- `wpapp_before_tab_template` - Before template includes
- `wpapp_after_tab_template` - After template includes
- `wpapp_no_tabs_content` - When no tabs registered

---

### 4. StatsBoxTemplate

Reusable statistics cards with loading states.

**File:** `src/Views/DataTable/Templates/StatsBoxTemplate.php`

**Stats Structure:**
```php
[
    [
        'id' => 'total-customers',           // Unique ID (also used as DOM ID)
        'label' => 'Total Customers',        // Display label
        'icon' => 'dashicons-groups',        // Optional: Dashicon class
        'class' => 'primary'                 // Optional: primary, success, warning, danger, info
    ]
]
```

**Register Stats via Filter:**
```php
add_filter('wpapp_datatable_stats', function($stats, $entity) {
    if ($entity !== 'customer') {
        return $stats;
    }

    return [
        [
            'id' => 'total-customers',
            'label' => __('Total Customers', 'wp-customer'),
            'icon' => 'dashicons-groups',
            'class' => 'primary'
        ],
        [
            'id' => 'active-customers',
            'label' => __('Active', 'wp-customer'),
            'icon' => 'dashicons-yes-alt',
            'class' => 'success'
        ],
        [
            'id' => 'inactive-customers',
            'label' => __('Inactive', 'wp-customer'),
            'icon' => 'dashicons-dismiss',
            'class' => 'warning'
        ]
    ];
}, 10, 2);
```

**Load Stats via JavaScript:**
```javascript
// Stats are loaded via AJAX automatically
// Numbers are injected into DOM element with ID matching stat['id']

// Example AJAX response format:
{
    success: true,
    data: {
        'total-customers': 1234,
        'active-customers': 890,
        'inactive-customers': 344
    }
}
```

**Color Variants:**
- `primary` - Blue (default)
- `success` - Green
- `warning` - Yellow/Orange
- `danger` - Red
- `info` - Cyan

---

## Hook System

### Complete Hook Reference

#### Dashboard Hooks

| Hook | Type | Parameters | Description |
|------|------|------------|-------------|
| `wpapp_dashboard_before_content` | action | `$config, $entity` | Before all dashboard content |
| `wpapp_dashboard_before_stats` | action | `$config, $entity` | Before statistics section |
| `wpapp_dashboard_after_stats` | action | `$config, $entity` | After statistics section |
| `wpapp_dashboard_after_content` | action | `$config, $entity` | After all dashboard content |

#### Panel Hooks

| Hook | Type | Parameters | Description |
|------|------|------------|-------------|
| `wpapp_left_panel_header` | action | `$config` | Left panel header |
| `wpapp_left_panel_content` | action | `$config` | **Left panel main content** |
| `wpapp_left_panel_footer` | action | `$config` | Left panel footer |
| `wpapp_right_panel_header` | action | `$config` | Right panel header |
| `wpapp_right_panel_content` | action | `$config` | Right panel content (no tabs) |
| `wpapp_right_panel_footer` | action | `$config` | Right panel footer |

#### Tab Hooks

| Hook | Type | Parameters | Description |
|------|------|------------|-------------|
| `wpapp_datatable_tabs` | filter | `$tabs, $entity` | Register tabs |
| `wpapp_datatable_tab_template` | filter | `$template, $tab_id, $entity` | Override template path |
| `wpapp_before_tab_template` | action | `$tab_id, $entity` | Before template include |
| `wpapp_after_tab_template` | action | `$tab_id, $entity` | After template include |
| `wpapp_no_tabs_content` | action | `$entity` | No tabs registered |

#### Stats Hooks

| Hook | Type | Parameters | Description |
|------|------|------------|-------------|
| `wpapp_datatable_stats` | filter | `$stats, $entity` | Register stats |

#### Asset Hooks

| Hook | Type | Parameters | Description |
|------|------|------------|-------------|
| `wpapp_datatable_allowed_hooks` | filter | `$hooks` | Allowed admin page hooks |
| `wpapp_datatable_should_load_assets` | filter | `$should_load, $hook` | Override asset loading |
| `wpapp_datatable_after_enqueue_styles` | action | `$version` | After CSS enqueued |
| `wpapp_datatable_after_enqueue_scripts` | action | `$version` | After JS enqueued |
| `wpapp_datatable_localize_data` | filter | `$config` | Modify localized data |
| `wpapp_datatable_i18n_strings` | filter | `$strings` | Modify translations |

---

## JavaScript API

### Panel Manager

**Global Instance:** `window.wpAppPanelManager`

**Methods:**

```javascript
// Open panel programmatically
wpAppPanelManager.open(entityId);

// Close panel
wpAppPanelManager.close();

// Refresh current panel
wpAppPanelManager.refresh();
```

**Events:**

```javascript
// Before panel opens
jQuery(document).on('wpapp:panel-opening', function(e, data) {
    console.log('Opening panel:', data.entity, data.id);

    // Prevent opening if needed
    e.preventDefault();
});

// After panel fully opened
jQuery(document).on('wpapp:panel-opened', function(e, data) {
    console.log('Panel opened:', data.entity, data.id);
});

// Before panel closes
jQuery(document).on('wpapp:panel-closing', function(e, data) {
    console.log('Closing panel:', data.entity, data.id);

    // Prevent closing if unsaved changes
    if (hasUnsavedChanges) {
        e.preventDefault();
        confirm('You have unsaved changes. Close anyway?');
    }
});

// After panel closed
jQuery(document).on('wpapp:panel-closed', function(e, data) {
    console.log('Panel closed:', data.entity);
});

// Data loading started
jQuery(document).on('wpapp:panel-loading', function(e, data) {
    console.log('Loading data:', data.entity, data.id);
});

// Data loaded successfully
jQuery(document).on('wpapp:panel-data-loaded', function(e, data) {
    console.log('Data loaded:', data.entity, data.id, data.data);

    // Initialize custom components
    initDatePickers();
    initSelect2();
});

// Error occurred
jQuery(document).on('wpapp:panel-error', function(e, data) {
    console.error('Error:', data.message);
});
```

**Hash Navigation:**

```javascript
// URL format: #entity-id
// Example: #customer-123 opens customer with ID 123

// Panel opens automatically on page load if hash present
// Browser back/forward supported
```

---

### Tab Manager

**Global Instance:** `window.wpAppTabManager`

**Methods:**

```javascript
// Switch to specific tab
wpAppTabManager.goTo('details');

// Get current active tab
const currentTab = wpAppTabManager.getCurrent();

// Get all available tabs
const allTabs = wpAppTabManager.getAll();
// Returns: ['details', 'membership', 'invoices']
```

**Events:**

```javascript
// Before tab switches
jQuery(document).on('wpapp:tab-switching', function(e, data) {
    console.log('Switching from', data.fromTab, 'to', data.toTab);

    // Prevent switch if needed
    if (hasUnsavedChanges) {
        e.preventDefault();
    }
});

// After tab switched
jQuery(document).on('wpapp:tab-switched', function(e, data) {
    console.log('Switched to:', data.tabId);

    // Load tab-specific data
    loadTabData(data.tabId);
});
```

**Hash Navigation:**

```javascript
// URL format: #entity-id&tab=tab-id
// Example: #customer-123&tab=membership

// Tab switches automatically on page load if hash present
// Tab state preserved on refresh
```

**Keyboard Navigation:**

- **Arrow Left/Up**: Previous tab
- **Arrow Right/Down**: Next tab
- Wraps around (last → first, first → last)

---

## Migration Guide

### Before: Old Pattern (wp-customer)

**File: `wp-customer/src/Views/customers/dashboard.php`**

```php
<?php
// Old pattern: Manual HTML structure

?>
<div class="wrap">
    <h1>Customers</h1>

    <!-- Statistics -->
    <div class="customer-stats">
        <div class="stat-box">
            <span class="stat-number" id="total-customers">0</span>
            <span class="stat-label">Total Customers</span>
        </div>
        <!-- More stats... -->
    </div>

    <!-- Panel Layout -->
    <div class="customer-layout">
        <div class="customer-left-panel">
            <?php include 'datatable.php'; ?>
        </div>

        <div class="customer-right-panel hidden">
            <!-- Tabs -->
            <div class="nav-tab-wrapper">
                <a href="#" class="nav-tab nav-tab-active" data-tab="details">Details</a>
                <a href="#" class="nav-tab" data-tab="membership">Membership</a>
            </div>

            <!-- Tab Contents -->
            <div id="details" class="tab-content active">
                <?php include 'tabs/details.php'; ?>
            </div>
            <div id="membership" class="tab-content">
                <?php include 'tabs/membership.php'; ?>
            </div>
        </div>
    </div>
</div>

<!-- Enqueue custom CSS/JS -->
<script>
    // Custom panel logic (200+ lines)
    // Custom tab logic (100+ lines)
</script>
```

**Problems:**
- ❌ 300+ lines of duplicated HTML structure
- ❌ 300+ lines of duplicated CSS
- ❌ 300+ lines of duplicated JavaScript
- ❌ Hard to maintain consistency
- ❌ Same code repeated in company/company-invoice contexts

---

### After: New Pattern (Using wp-app-core)

**File: `wp-customer/src/Views/customers/dashboard.php`**

```php
<?php
// New pattern: Clean and simple

use WPAppCore\Views\DataTable\Templates\DashboardTemplate;

DashboardTemplate::render([
    'entity' => 'customer',
    'title' => __('Customers', 'wp-customer'),
    'ajax_action' => 'get_customer_details',
    'has_stats' => true,
    'has_tabs' => true,
]);
```

**That's it! Only 7 lines of code.**

**File: `wp-customer/includes/class-customer-dashboard.php`**

```php
<?php
/**
 * Register hooks for Customer dashboard
 */

class WP_Customer_Dashboard {

    public function __construct() {
        // Register left panel content (DataTable)
        add_action('wpapp_left_panel_content', [$this, 'render_datatable'], 10, 1);

        // Register statistics
        add_filter('wpapp_datatable_stats', [$this, 'register_stats'], 10, 2);

        // Register tabs
        add_filter('wpapp_datatable_tabs', [$this, 'register_tabs'], 10, 2);
    }

    /**
     * Render DataTable HTML
     */
    public function render_datatable($config) {
        if ($config['entity'] !== 'customer') return;

        include WP_CUSTOMER_PATH . 'src/Views/customers/datatable.php';
    }

    /**
     * Register statistics boxes
     */
    public function register_stats($stats, $entity) {
        if ($entity !== 'customer') return $stats;

        return [
            [
                'id' => 'total-customers',
                'label' => __('Total Customers', 'wp-customer'),
                'icon' => 'dashicons-groups',
                'class' => 'primary'
            ],
            [
                'id' => 'active-customers',
                'label' => __('Active', 'wp-customer'),
                'icon' => 'dashicons-yes-alt',
                'class' => 'success'
            ]
        ];
    }

    /**
     * Register tabs
     */
    public function register_tabs($tabs, $entity) {
        if ($entity !== 'customer') return $tabs;

        return [
            'details' => [
                'title' => __('Customer Details', 'wp-customer'),
                'template' => WP_CUSTOMER_PATH . 'src/Views/customers/tabs/details.php',
                'priority' => 10
            ],
            'membership' => [
                'title' => __('Membership', 'wp-customer'),
                'template' => WP_CUSTOMER_PATH . 'src/Views/customers/tabs/membership.php',
                'priority' => 20
            ]
        ];
    }
}

new WP_Customer_Dashboard();
```

**Benefits:**
- ✅ Clean separation of concerns
- ✅ No duplicated code
- ✅ Easy to extend via hooks
- ✅ Consistent UX automatically
- ✅ CSS/JS handled by wp-app-core

---

### Migration Checklist

**Step 1: Update Dashboard File**
- [ ] Replace manual HTML with `DashboardTemplate::render()`
- [ ] Remove custom CSS enqueues
- [ ] Remove custom JS enqueues

**Step 2: Create Hook Class**
- [ ] Create class to register hooks (e.g., `WP_Customer_Dashboard`)
- [ ] Hook into `wpapp_left_panel_content` for DataTable
- [ ] Hook into `wpapp_datatable_stats` for statistics
- [ ] Hook into `wpapp_datatable_tabs` for tabs

**Step 3: Update Tab Templates**
- [ ] Ensure tab template files exist
- [ ] Remove manual tab navigation HTML
- [ ] Tab content templates remain unchanged

**Step 4: Update AJAX Handler**
- [ ] Implement AJAX action specified in `ajax_action` config
- [ ] Return proper response format (see below)

**Step 5: Test**
- [ ] Test panel open/close
- [ ] Test tab switching
- [ ] Test statistics loading
- [ ] Test browser back/forward
- [ ] Test hash navigation
- [ ] Test mobile responsive

**Step 6: Cleanup**
- [ ] Remove old CSS files (after verifying everything works)
- [ ] Remove old JS files
- [ ] Update plugin version

---

### AJAX Response Format

**Panel Data Response:**
```php
<?php
// File: wp-customer/includes/ajax/class-customer-ajax.php

add_action('wp_ajax_get_customer_details', function() {
    check_ajax_referer('wpapp_panel_nonce', 'nonce');

    $customer_id = intval($_POST['id']);

    // Get customer data
    $customer = Customer::find($customer_id);

    if (!$customer) {
        wp_send_json_error([
            'message' => __('Customer not found', 'wp-customer')
        ]);
    }

    // Render tab contents
    ob_start();
    include WP_CUSTOMER_PATH . 'src/Views/customers/tabs/details.php';
    $details_html = ob_get_clean();

    ob_start();
    include WP_CUSTOMER_PATH . 'src/Views/customers/tabs/membership.php';
    $membership_html = ob_get_clean();

    wp_send_json_success([
        'title' => $customer->get_name(),  // Panel title
        'tabs' => [
            'details' => $details_html,
            'membership' => $membership_html
        ]
    ]);
});
```

**Statistics Response:**
```php
<?php
add_action('wp_ajax_get_customer_stats', function() {
    check_ajax_referer('wpapp_panel_nonce', 'nonce');

    wp_send_json_success([
        'total-customers' => Customer::count(),
        'active-customers' => Customer::where('status', 'active')->count(),
        'inactive-customers' => Customer::where('status', 'inactive')->count()
    ]);
});
```

---

## Best Practices

### 1. Entity Naming

Use consistent entity names across all hooks:

```php
// ✅ Good
'entity' => 'customer'  // Same everywhere

// Hook checks
if ($entity !== 'customer') return;

// ❌ Bad
'entity' => 'Customer'   // Inconsistent casing
'entity' => 'customers'  // Plural/singular mismatch
```

### 2. Tab Template Organization

Keep tab templates in dedicated directory:

```
wp-customer/src/Views/customers/
├── dashboard.php           # Main dashboard
├── datatable.php          # DataTable HTML
└── tabs/
    ├── details.php        # Tab 1
    ├── membership.php     # Tab 2
    └── invoices.php       # Tab 3
```

### 3. Hook Priority

Use priorities to control execution order:

```php
// Render content early
add_action('wpapp_left_panel_content', 'render_datatable', 5);

// Add buttons after content
add_action('wpapp_left_panel_footer', 'render_bulk_actions', 10);
```

### 4. Conditional Hook Registration

Only register hooks when needed:

```php
// ✅ Good: Check entity inside hook
add_filter('wpapp_datatable_tabs', function($tabs, $entity) {
    if ($entity !== 'customer') return $tabs;
    // ... register tabs
}, 10, 2);

// ❌ Bad: Register multiple entity-specific hooks
add_filter('wpapp_datatable_customer_tabs', ...);  // Non-standard
add_filter('wpapp_datatable_company_tabs', ...);   // Non-standard
```

### 5. CSS Customization

Use CSS custom properties for easy theming:

```css
/* wp-customer/assets/css/customer-custom.css */

/* Override stat box colors */
.wpapp-stat-box.customer-gold .wpapp-stat-icon {
    background: #ffd700;
    color: #fff;
}

/* Custom panel width */
.wpapp-datatable-layout.with-right-panel .wpapp-left-panel {
    width: 50%; /* Override default 45% */
}
```

### 6. JavaScript Extension

Extend functionality via events:

```javascript
// wp-customer/assets/js/customer-panel-extension.js

jQuery(document).on('wpapp:panel-data-loaded', function(e, data) {
    if (data.entity !== 'customer') return;

    // Initialize custom components
    jQuery('#customer-membership-select').select2();
    jQuery('#customer-birthdate').datepicker();
});
```

### 7. Error Handling

Always handle errors gracefully:

```php
add_action('wp_ajax_get_customer_details', function() {
    try {
        check_ajax_referer('wpapp_panel_nonce', 'nonce');

        $customer_id = intval($_POST['id']);
        $customer = Customer::find($customer_id);

        if (!$customer) {
            throw new Exception(__('Customer not found', 'wp-customer'));
        }

        // ... render content

        wp_send_json_success(['title' => $customer->name, ...]);

    } catch (Exception $e) {
        wp_send_json_error([
            'message' => $e->getMessage()
        ]);
    }
});
```

### 8. Accessibility

Ensure proper ARIA attributes:

```php
// In tab template
<div role="tabpanel" aria-labelledby="details-tab">
    <!-- Tab content -->
</div>
```

---

## Troubleshooting

### Issue: Assets Not Loading

**Symptom:** CSS/JS not loading on page.

**Causes:**
1. Page hook not in allowed list
2. Assets controller not initialized

**Solution:**
```php
// Add your page hook to allowed list
add_filter('wpapp_datatable_allowed_hooks', function($hooks) {
    $hooks[] = 'toplevel_page_my-custom-page';
    return $hooks;
});

// OR force enqueue on specific page
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'toplevel_page_my-custom-page') {
        \WPAppCore\Controllers\DataTable\DataTableAssetsController::force_enqueue();
    }
});
```

---

### Issue: Panel Not Opening

**Symptom:** Clicking row does nothing.

**Causes:**
1. DataTable rows missing `data-id` attribute
2. JavaScript errors

**Solution:**
```php
// Ensure DataTable rows have data-id
<tr data-id="<?php echo esc_attr($customer->id); ?>">
    <td><?php echo esc_html($customer->name); ?></td>
</tr>
```

Check browser console for JavaScript errors.

---

### Issue: Tabs Not Appearing

**Symptom:** Right panel shows but no tabs.

**Causes:**
1. `has_tabs` not set to `true`
2. No tabs registered via filter

**Solution:**
```php
// 1. Enable tabs in config
DashboardTemplate::render([
    'entity' => 'customer',
    'has_tabs' => true,  // Must be true
]);

// 2. Register tabs
add_filter('wpapp_datatable_tabs', function($tabs, $entity) {
    if ($entity !== 'customer') return $tabs;

    return [
        'details' => [
            'title' => 'Details',
            'template' => __DIR__ . '/tabs/details.php',
            'priority' => 10
        ]
    ];
}, 10, 2);
```

---

### Issue: Statistics Not Loading

**Symptom:** Stat boxes show spinner forever.

**Causes:**
1. AJAX action not implemented
2. Wrong response format

**Solution:**
```php
// Implement AJAX action to load stats
add_action('wp_ajax_get_customer_stats', function() {
    check_ajax_referer('wpapp_panel_nonce', 'nonce');

    wp_send_json_success([
        'total-customers' => 1234,  // ID must match stat box ID
        'active-customers' => 890
    ]);
});
```

---

### Issue: Hash Navigation Not Working

**Symptom:** URL hash changes but panel doesn't open.

**Causes:**
1. Entity name mismatch
2. ID not numeric

**Solution:**
```php
// Ensure entity name matches exactly
DashboardTemplate::render([
    'entity' => 'customer',  // Must match hash prefix
]);

// URL format: #customer-123
// Entity: 'customer'
// ID: 123 (numeric)
```

---

### Issue: Custom Styles Not Applied

**Symptom:** CSS changes not visible.

**Causes:**
1. Specificity issue
2. CSS not enqueued after base styles

**Solution:**
```php
// Enqueue custom CSS with higher priority
add_action('wpapp_datatable_after_enqueue_styles', function() {
    wp_enqueue_style(
        'customer-custom-css',
        WP_CUSTOMER_URL . 'assets/css/customer-custom.css',
        ['wpapp-datatable-css'],  // Depend on base CSS
        '1.0.0'
    );
});
```

---

## Performance Tips

### 1. Lazy Load Tab Content

Don't render all tabs upfront. Load via AJAX when tab is clicked:

```javascript
jQuery(document).on('wpapp:tab-switched', function(e, data) {
    const $tab = jQuery('#' + data.tabId);

    // Check if tab already loaded
    if ($tab.data('loaded')) return;

    // Load tab content via AJAX
    jQuery.ajax({
        url: wpAppConfig.ajaxUrl,
        data: {
            action: 'load_tab_content',
            tab_id: data.tabId,
            entity: data.entity,
            nonce: wpAppConfig.nonce
        },
        success: function(response) {
            $tab.html(response.data.html);
            $tab.data('loaded', true);
        }
    });
});
```

### 2. Cache Statistics

Cache expensive stat calculations:

```php
add_action('wp_ajax_get_customer_stats', function() {
    $cache_key = 'customer_stats';
    $stats = wp_cache_get($cache_key);

    if (false === $stats) {
        $stats = [
            'total-customers' => Customer::count(),
            'active-customers' => Customer::where('status', 'active')->count()
        ];

        wp_cache_set($cache_key, $stats, '', 300); // 5 minutes
    }

    wp_send_json_success($stats);
});
```

### 3. Minimize DOM Manipulation

Use DocumentFragment for bulk operations:

```javascript
const fragment = document.createDocumentFragment();
for (let i = 0; i < items.length; i++) {
    const div = document.createElement('div');
    div.textContent = items[i];
    fragment.appendChild(div);
}
container.appendChild(fragment);
```

---

## Browser Compatibility

Tested and compatible with:

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ⚠️ IE11 (requires polyfills)

### IE11 Polyfills

If supporting IE11, include polyfills:

```php
add_action('wpapp_datatable_after_enqueue_scripts', function() {
    wp_enqueue_script(
        'promise-polyfill',
        'https://cdn.jsdelivr.net/npm/promise-polyfill@8/dist/polyfill.min.js',
        [],
        '8.0.0',
        true
    );
});
```

---

## Related Documentation

- [TODO-2179: Base Panel Dashboard System](../../../TODO/TODO-2179-implement-base-panel-dashboard-system.md)
- [TODO-2178: Base DataTable System](../../../TODO/TODO-2178-implement-base-datatable-system.md)
- [TODO-2174: Companies DataTable Implementation](/wp-customer/TODO/TODO-2174-implement-companies-datatable.md)
- [WP Customer Hooks](/wp-customer/docs/hooks/README.md)

---

## Changelog

### Version 1.1.0 (2025-10-28) - TODO-1187
- ✅ Simplified container structure for consistency
- ✅ DashboardTemplate: Removed nested `wpapp-page-header` wrapper
- ✅ PanelLayoutTemplate: Added `wpapp-datatable-container` wrapper
- ✅ CSS: Updated all selectors from `.wpapp-page-header .wpapp-*` to `.wpapp-page-header-container .wpapp-*`
- ✅ CSS: Added `.wpapp-datatable-container` with consistent margin
- ✅ All containers now have uniform left/right boundaries

**Post-Implementation Reviews:**
- ✅ Review-01: Fixed header flexbox alignment with increased CSS specificity
- ✅ Review-02: Fixed container boundaries with offsetting padding
- ✅ Review-03: Added panel spacing (20px top/bottom, 15px gap)
- ✅ Review-04: Full width utilization using negative margins (-15px left/right)

**Full Width Pattern:**
All containers now use `margin: [top] -15px [bottom] -15px` to extend to full available width:
- `.wpapp-page-header-container`: `margin: 0 -15px 0 -15px`
- `.wpapp-statistics-container`: `margin: 20px -15px 20px -15px`
- `.wpapp-filters-container`: `margin: 0 -15px 20px -15px`
- `.wpapp-datatable-container`: `margin: 0 -15px 20px -15px`

**Breaking Changes**: None - backward compatible
**Benefits**:
- Consistent spacing across all dashboard containers
- Full width utilization, no wasted space
- Perfect alignment of all container boundaries
- Foundation for all dashboard pages in the application

### Version 1.0.0 (2025-10-23)
- ✅ Initial release
- ✅ DashboardTemplate implementation
- ✅ PanelLayoutTemplate implementation
- ✅ TabSystemTemplate implementation
- ✅ StatsBoxTemplate implementation
- ✅ Global CSS (wpapp-datatable.css)
- ✅ Panel Manager JavaScript
- ✅ Tab Manager JavaScript
- ✅ Asset Management Controller
- ✅ Comprehensive documentation

---

## Support

**Issues:** Report bugs or request features at [GitHub Issues](#)

**Questions:** Ask in [WordPress Stack Exchange](https://wordpress.stackexchange.com/) with tag `wp-app-core`

---

**End of Documentation**
