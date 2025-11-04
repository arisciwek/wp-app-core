# AbstractDashboardController Documentation

**Version:** 1.0.0
**File:** `src/Controllers/Abstract/AbstractDashboardController.php`
**Namespace:** `WPAppCore\Controllers\Abstract`
**Status:** ✅ Production Ready

---

## Overview

Base controller class for entity dashboard pages with DataTable and panel system. Eliminates code duplication across dashboard controllers by providing shared implementation for common dashboard patterns.

### Key Benefits

✅ **60-70% Code Reduction** - Child controllers only implement entity-specific views
✅ **Consistent UI/UX** - Standardized dashboard layout across all entities
✅ **Hook-Based Architecture** - Flexible content injection via WordPress hooks
✅ **Automatic DataTable Integration** - Built-in server-side processing
✅ **Tabbed Panel System** - Right-side panel with lazy-loaded tabs
✅ **Statistics Cards** - Header cards with real-time stats
✅ **Filter System** - Built-in status and custom filters
✅ **Type-Safe Method Signatures** - PHP type hints throughout

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     DASHBOARD LAYOUT                             │
├─────────────────────────────────────────────────────────────────┤
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Header: Title + Action Buttons (Add New, etc.)               │ │
│ └─────────────────────────────────────────────────────────────┘ │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Statistics Cards: Total | Active | Inactive                  │ │
│ └─────────────────────────────────────────────────────────────┘ │
│ ┌───────────────────────────┬─────────────────────────────────┐ │
│ │ LEFT PANEL (60%)          │ RIGHT PANEL (40%)               │ │
│ │                           │                                 │ │
│ │ ┌───────────────────────┐ │ ┌─────────────────────────────┐ │ │
│ │ │ Filters: Status, etc  │ │ │ Entity Detail Panel         │ │ │
│ │ └───────────────────────┘ │ │ (Lazy-loaded via AJAX)      │ │ │
│ │                           │ │                             │ │ │
│ │ ┌───────────────────────┐ │ │ ┌──────┬──────┬──────────┐ │ │ │
│ │ │ DataTable             │ │ │ │ Info │Branch│Employees │ │ │ │
│ │ │ - ID | Name | Status  │ │ │ └──────┴──────┴──────────┘ │ │ │
│ │ │ - 1  | John | Active  │ │ │                             │ │ │
│ │ │ - 2  | Jane | Active  │ │ │ Tab Content Here            │ │ │
│ │ │ (Server-side)         │ │ │                             │ │ │
│ │ └───────────────────────┘ │ └─────────────────────────────┘ │ │
│ └───────────────────────────┴─────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

---

## Quick Start

### 1. Create Your Dashboard Controller

```php
<?php
namespace WPCustomer\Controllers\Customer;

use WPAppCore\Controllers\Abstract\AbstractDashboardController;
use WPCustomer\Models\Customer\CustomerDataTableModel;
use WPCustomer\Models\Customer\CustomerModel;

class CustomerDashboardController extends AbstractDashboardController {

    // ========================================
    // IMPLEMENT 13 ABSTRACT METHODS
    // ========================================

    protected function getEntityName(): string {
        return 'customer';
    }

    protected function getEntityDisplayName(): string {
        return 'Customer';
    }

    protected function getEntityDisplayNamePlural(): string {
        return 'Customers';
    }

    protected function getTextDomain(): string {
        return 'wp-customer';
    }

    protected function getEntityPath(): string {
        return WP_CUSTOMER_PATH; // With trailing slash
    }

    protected function getDataTableModel() {
        return new CustomerDataTableModel();
    }

    protected function getModel() {
        return new CustomerModel();
    }

    protected function getDataTableAjaxAction(): string {
        return 'get_customer_datatable';
    }

    protected function getDetailsAjaxAction(): string {
        return 'get_customer_details';
    }

    protected function getStatsAjaxAction(): string {
        return 'get_customer_stats';
    }

    protected function getViewCapability(): string {
        return 'view_customer_list';
    }

    protected function getStatsConfig(): array {
        return [
            'total' => [
                'label' => __('Total Customers', 'wp-customer'),
                'value' => 0,
                'icon' => 'dashicons-businessperson',
                'color' => 'blue'
            ],
            'active' => [
                'label' => __('Active', 'wp-customer'),
                'value' => 0,
                'icon' => 'dashicons-yes-alt',
                'color' => 'green'
            ],
            'inactive' => [
                'label' => __('Inactive', 'wp-customer'),
                'value' => 0,
                'icon' => 'dashicons-dismiss',
                'color' => 'red'
            ]
        ];
    }

    protected function getTabsConfig(): array {
        return [
            'info' => [
                'title' => __('Customer Information', 'wp-customer'),
                'priority' => 10
            ],
            'branches' => [
                'title' => __('Branches', 'wp-customer'),
                'priority' => 20
            ],
            'employees' => [
                'title' => __('Employees', 'wp-customer'),
                'priority' => 30
            ]
        ];
    }
}
```

### 2. Register Tab Content Hooks

```php
class CustomerDashboardController extends AbstractDashboardController {

    // ... abstract methods ...

    protected function register_hooks(): void {
        // Call parent to register base hooks
        parent::register_hooks();

        // Register tab content injection hooks
        add_action('wpapp_tab_view_content', [$this, 'render_info_tab'], 10, 3);
        add_action('wpapp_tab_view_content', [$this, 'render_branches_tab'], 10, 3);
        add_action('wpapp_tab_view_content', [$this, 'render_employees_tab'], 10, 3);
    }

    /**
     * Render info tab content
     */
    public function render_info_tab($tab_id, $entity, $data): void {
        if ($entity !== 'customer' || $tab_id !== 'info') {
            return;
        }

        $tab_file = WP_CUSTOMER_PATH . 'src/Views/customer/tabs/info.php';

        if (file_exists($tab_file)) {
            include $tab_file;
        }
    }

    /**
     * Render branches tab content
     */
    public function render_branches_tab($tab_id, $entity, $data): void {
        if ($entity !== 'customer' || $tab_id !== 'branches') {
            return;
        }

        $tab_file = WP_CUSTOMER_PATH . 'src/Views/customer/tabs/branches.php';

        if (file_exists($tab_file)) {
            $customer = $data; // Make available to template
            include $tab_file;
        }
    }

    /**
     * Render employees tab content
     */
    public function render_employees_tab($tab_id, $entity, $data): void {
        if ($entity !== 'customer' || $tab_id !== 'employees') {
            return;
        }

        $tab_file = WP_CUSTOMER_PATH . 'src/Views/customer/tabs/employees.php';

        if (file_exists($tab_file)) {
            $customer = $data; // Make available to template
            include $tab_file;
        }
    }
}
```

### 3. Create View Partials

Create partial template files in your plugin:

```
wp-customer/
└── src/
    └── Views/
        └── customer/
            ├── partials/
            │   ├── header-title.php        (Page title)
            │   ├── header-buttons.php      (Action buttons)
            │   └── stat-cards.php          (Statistics cards)
            └── tabs/
                ├── info.php                (Info tab content)
                ├── branches.php            (Branches tab content)
                └── employees.php           (Employees tab content)
```

**Example: `header-title.php`**
```php
<h1 class="wp-heading-inline">
    <?php echo esc_html__('Customers', 'wp-customer'); ?>
</h1>
```

**Example: `header-buttons.php`**
```php
<button class="page-title-action wpapp-modal-trigger"
        data-modal-action="create_customer"
        data-entity="customer">
    <?php echo esc_html__('Add New', 'wp-customer'); ?>
</button>
```

**Example: `stat-cards.php`**
```php
<div class="wpapp-stats-grid">
    <div class="wpapp-stat-card blue">
        <span class="dashicons dashicons-businessperson"></span>
        <div class="wpapp-stat-content">
            <div class="wpapp-stat-value"><?php echo esc_html($total); ?></div>
            <div class="wpapp-stat-label"><?php echo esc_html__('Total Customers', 'wp-customer'); ?></div>
        </div>
    </div>
    <div class="wpapp-stat-card green">
        <span class="dashicons dashicons-yes-alt"></span>
        <div class="wpapp-stat-content">
            <div class="wpapp-stat-value"><?php echo esc_html($active); ?></div>
            <div class="wpapp-stat-label"><?php echo esc_html__('Active', 'wp-customer'); ?></div>
        </div>
    </div>
    <div class="wpapp-stat-card red">
        <span class="dashicons dashicons-dismiss"></span>
        <div class="wpapp-stat-content">
            <div class="wpapp-stat-value"><?php echo esc_html($inactive); ?></div>
            <div class="wpapp-stat-label"><?php echo esc_html__('Inactive', 'wp-customer'); ?></div>
        </div>
    </div>
</div>
```

**Example: `info.php`** (Info tab)
```php
<?php
/**
 * Customer Info Tab
 *
 * Variables available:
 * @var object $data Customer data from model->find()
 */
?>
<div class="wpapp-tab-section">
    <h3><?php echo esc_html__('Basic Information', 'wp-customer'); ?></h3>
    <table class="wpapp-info-table">
        <tr>
            <th><?php echo esc_html__('Name', 'wp-customer'); ?></th>
            <td><?php echo esc_html($data->name ?? '-'); ?></td>
        </tr>
        <tr>
            <th><?php echo esc_html__('Email', 'wp-customer'); ?></th>
            <td><?php echo esc_html($data->email ?? '-'); ?></td>
        </tr>
        <tr>
            <th><?php echo esc_html__('Phone', 'wp-customer'); ?></th>
            <td><?php echo esc_html($data->phone ?? '-'); ?></td>
        </tr>
        <tr>
            <th><?php echo esc_html__('Status', 'wp-customer'); ?></th>
            <td>
                <span class="wpapp-status-badge <?php echo esc_attr($data->status ?? 'inactive'); ?>">
                    <?php echo esc_html(ucfirst($data->status ?? 'inactive')); ?>
                </span>
            </td>
        </tr>
    </table>
</div>
```

### 4. Register Menu and Initialize Controller

```php
// In your plugin's main file or menu registration

add_action('admin_menu', function() {
    add_menu_page(
        __('Customers', 'wp-customer'),
        __('Customers', 'wp-customer'),
        'view_customer_list',
        'wp-customer-dashboard',
        function() {
            $controller = new \WPCustomer\Controllers\Customer\CustomerDashboardController();
            $controller->renderDashboard();
        },
        'dashicons-businessperson',
        30
    );
});

// Initialize controller (for AJAX handlers)
add_action('plugins_loaded', function() {
    new \WPCustomer\Controllers\Customer\CustomerDashboardController();
});
```

That's it! You get a **fully functional dashboard** with DataTable, statistics, filters, and tabbed panel!

---

## Abstract Methods

These 13 methods **MUST** be implemented by child classes:

### 1. `getEntityName(): string`

Entity name (singular, lowercase). Used for hook names and JavaScript.

**Important:** Use underscores for multi-word entities (e.g., 'platform_staff').

**Example:**
```php
protected function getEntityName(): string {
    return 'customer';
}
```

---

### 2. `getEntityDisplayName(): string`

Entity display name (singular). Used for page titles and UI labels.

**Example:**
```php
protected function getEntityDisplayName(): string {
    return 'Customer';
}
```

---

### 3. `getEntityDisplayNamePlural(): string`

Entity display name (plural). Used for page titles and list labels.

**Example:**
```php
protected function getEntityDisplayNamePlural(): string {
    return 'Customers';
}
```

---

### 4. `getTextDomain(): string`

Text domain for translations.

**Example:**
```php
protected function getTextDomain(): string {
    return 'wp-customer';
}
```

---

### 5. `getEntityPath(): string`

Entity plugin path. Used for locating view templates.

**Must include trailing slash.**

**Example:**
```php
protected function getEntityPath(): string {
    return WP_CUSTOMER_PATH; // e.g., '/path/to/wp-customer/'
}
```

---

### 6. `getDataTableModel()`

DataTable model instance. Must have `get_datatable_data()` and `get_total_count()` methods.

**Example:**
```php
protected function getDataTableModel() {
    return new CustomerDataTableModel();
}
```

**Required Model Methods:**
```php
class CustomerDataTableModel {
    public function get_datatable_data(array $request) {
        // Process DataTable request
        // Return: ['draw' => 1, 'recordsTotal' => 100, 'recordsFiltered' => 50, 'data' => [...]]
    }

    public function get_total_count(string $status = 'all'): int {
        // Return count for status: 'all', 'active', 'inactive'
    }
}
```

---

### 7. `getModel()`

CRUD model instance. Must have `find()` method.

**Example:**
```php
protected function getModel() {
    return new CustomerModel();
}
```

**Required Model Method:**
```php
class CustomerModel {
    public function find(int $id) {
        // Retrieve entity from database
        // Return entity object or null
    }
}
```

---

### 8. `getDataTableAjaxAction(): string`

AJAX action for DataTable requests.

**Example:**
```php
protected function getDataTableAjaxAction(): string {
    return 'get_customer_datatable';
}
```

---

### 9. `getDetailsAjaxAction(): string`

AJAX action for entity details (panel content).

**Example:**
```php
protected function getDetailsAjaxAction(): string {
    return 'get_customer_details';
}
```

---

### 10. `getStatsAjaxAction(): string`

AJAX action for statistics requests.

**Example:**
```php
protected function getStatsAjaxAction(): string {
    return 'get_customer_stats';
}
```

---

### 11. `getViewCapability(): string`

WordPress capability required to view the dashboard.

**Example:**
```php
protected function getViewCapability(): string {
    return 'view_customer_list';
}
```

---

### 12. `getStatsConfig(): array`

Statistics boxes configuration for header cards.

**Format:**
```php
[
    'stat_key' => [
        'label' => 'Display Label',
        'value' => 0,              // Initial value (updated by AJAX)
        'icon' => 'dashicons-...',
        'color' => 'blue|green|red|yellow|purple'
    ]
]
```

**Example:**
```php
protected function getStatsConfig(): array {
    return [
        'total' => [
            'label' => __('Total Customers', 'wp-customer'),
            'value' => 0,
            'icon' => 'dashicons-businessperson',
            'color' => 'blue'
        ],
        'active' => [
            'label' => __('Active', 'wp-customer'),
            'value' => 0,
            'icon' => 'dashicons-yes-alt',
            'color' => 'green'
        ],
        'inactive' => [
            'label' => __('Inactive', 'wp-customer'),
            'value' => 0,
            'icon' => 'dashicons-dismiss',
            'color' => 'red'
        ],
        'pending' => [
            'label' => __('Pending Approval', 'wp-customer'),
            'value' => 0,
            'icon' => 'dashicons-clock',
            'color' => 'yellow'
        ]
    ];
}
```

---

### 13. `getTabsConfig(): array`

Tabs configuration for right panel.

**Format:**
```php
[
    'tab_id' => [
        'title' => 'Tab Display Title',
        'priority' => 10  // Lower = earlier (10, 20, 30, ...)
    ]
]
```

**Example:**
```php
protected function getTabsConfig(): array {
    return [
        'info' => [
            'title' => __('Customer Information', 'wp-customer'),
            'priority' => 10
        ],
        'branches' => [
            'title' => __('Branches', 'wp-customer'),
            'priority' => 20
        ],
        'employees' => [
            'title' => __('Employees', 'wp-customer'),
            'priority' => 30
        ],
        'history' => [
            'title' => __('Activity History', 'wp-customer'),
            'priority' => 40
        ]
    ];
}
```

---

## Concrete Methods (Inherited FREE)

### Dashboard Rendering

#### 1. `renderDashboard(): void`

Main entry point. Renders the complete dashboard using DashboardTemplate.

**Called by:** Menu callback

**Example:**
```php
add_menu_page(
    'Customers',
    'Customers',
    'view_customer_list',
    'wp-customer-dashboard',
    function() {
        $controller = new CustomerDashboardController();
        $controller->renderDashboard(); // ← Called here
    }
);
```

---

#### 2. `render_datatable($config): void`

Renders DataTable HTML in left panel.

**Hooked to:** `wpapp_left_panel_content`

**Parameters:**
- `$config`: Configuration array with 'entity' key

**View File:** `/wp-app-core/src/Views/DataTable/Templates/datatable.php`

---

#### 3. `render_header_title($config, $entity): void`

Renders page header title using partial template.

**Hooked to:** `wpapp_page_header_left`

**Partial:** `{entity}/partials/header-title.php`

---

#### 4. `render_header_buttons($config, $entity): void`

Renders page header action buttons using partial template.

**Hooked to:** `wpapp_page_header_right`

**Partial:** `{entity}/partials/header-buttons.php`

---

#### 5. `render_header_cards($entity): void`

Renders statistics cards in header using partial template.

**Hooked to:** `wpapp_statistics_cards_content`

**Partial:** `{entity}/partials/stat-cards.php`

**Variables Passed:**
- `$total`: Total count
- `$active`: Active count
- `$inactive`: Inactive count

---

#### 6. `render_filters($config, $entity): void`

Renders filter controls using core template.

**Hooked to:** `wpapp_dashboard_filters`

**View File:** `/wp-app-core/src/Views/DataTable/Templates/partials/status-filter.php`

---

### Hook Registrations

#### 7. `register_stats($stats, $entity): array`

Registers statistics boxes.

**Hooked to:** `wpapp_datatable_stats` filter

**Returns:** Stats configuration from `getStatsConfig()`

---

#### 8. `register_tabs($tabs, $entity): array`

Registers tabs for right panel.

**Hooked to:** `wpapp_datatable_tabs` filter

**Returns:** Tabs configuration from `getTabsConfig()`

---

### AJAX Handlers

#### 9. `handle_datatable_ajax(): void`

Handles DataTable AJAX requests (server-side processing).

**AJAX Action:** Value from `getDataTableAjaxAction()`

**Request:**
```javascript
{
    action: 'get_customer_datatable',
    nonce: 'abc123',
    draw: 1,
    start: 0,
    length: 10,
    search: { value: 'john' },
    order: [{ column: 0, dir: 'asc' }]
}
```

**Response:**
```json
{
    "draw": 1,
    "recordsTotal": 100,
    "recordsFiltered": 50,
    "data": [
        {"id": 1, "name": "John Doe", "status": "active"},
        {"id": 2, "name": "Jane Smith", "status": "active"}
    ]
}
```

---

#### 10. `handle_get_details(): void`

Handles entity details requests (panel content).

**AJAX Action:** Value from `getDetailsAjaxAction()`

**Request:**
```javascript
{
    action: 'get_customer_details',
    nonce: 'abc123',
    id: 123
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "title": "John Doe",
        "tabs": {
            "info": "<div>Info content HTML</div>",
            "branches": "<div>Branches content HTML</div>",
            "employees": "<div>Employees content HTML</div>"
        },
        "data": {
            "id": 123,
            "name": "John Doe",
            "email": "john@example.com"
        }
    }
}
```

---

#### 11. `handle_get_stats(): void`

Handles statistics requests.

**AJAX Action:** Value from `getStatsAjaxAction()`

**Request:**
```javascript
{
    action: 'get_customer_stats',
    nonce: 'abc123'
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "total": 150,
        "active": 120,
        "inactive": 30
    }
}
```

---

### Utility Methods

#### 12. `render_tab_contents($entity_data): array`

Renders all tab contents using hook-based pattern.

**Called by:** `handle_get_details()`

**How it Works:**
1. Gets registered tabs from `getTabsConfig()`
2. For each tab, triggers `wpapp_tab_view_content` action
3. Child classes hook into action to inject content
4. Returns array of `tab_id => html`

**Usage in Child Class:**
```php
// Hook tab content injection
add_action('wpapp_tab_view_content', [$this, 'render_info_tab'], 10, 3);

public function render_info_tab($tab_id, $entity, $data): void {
    if ($entity !== 'customer' || $tab_id !== 'info') {
        return;
    }

    // Render tab content
    include WP_CUSTOMER_PATH . 'src/Views/customer/tabs/info.php';
}
```

---

#### 13. `render_partial(string $partial, array $data, string $context): void`

Renders a partial template file.

**Parameters:**
- `$partial`: Partial name (without .php)
- `$data`: Data array (extracted as variables)
- `$context`: Entity context (directory name)

**File Path Pattern:**
```
{getEntityPath()}/src/Views/{$context}/partials/{$partial}.php
```

**Example:**
```php
// Renders: /wp-customer/src/Views/customer/partials/stat-cards.php
$this->render_partial('stat-cards', ['total' => 100, 'active' => 80], 'customer');
```

---

#### 14. `getStatsData(): array`

Retrieves statistics data from DataTable model.

**Can be overridden** by child classes for custom logic.

**Default Implementation:**
```php
protected function getStatsData(): array {
    return [
        'total' => $this->datatable_model->get_total_count('all'),
        'active' => $this->datatable_model->get_total_count('active'),
        'inactive' => $this->datatable_model->get_total_count('inactive')
    ];
}
```

**Custom Override Example:**
```php
protected function getStatsData(): array {
    return [
        'total' => $this->datatable_model->get_total_count('all'),
        'active' => $this->datatable_model->get_total_count('active'),
        'inactive' => $this->datatable_model->get_total_count('inactive'),
        'pending' => $this->datatable_model->get_total_count('pending'),
        'archived' => $this->datatable_model->get_total_count('archived')
    ];
}
```

---

## Hook-Based Architecture

### How Hooks Work

AbstractDashboardController uses WordPress hooks for flexible content injection:

```
┌───────────────────────────────────────────────────┐
│ AbstractDashboardController (Base)                │
├───────────────────────────────────────────────────┤
│                                                   │
│  renderDashboard()                                │
│    │                                              │
│    ├──> do_action('wpapp_page_header_left')      │
│    │      └──> Child: render_header_title()      │
│    │                                              │
│    ├──> do_action('wpapp_page_header_right')     │
│    │      └──> Child: render_header_buttons()    │
│    │                                              │
│    ├──> do_action('wpapp_statistics_cards')      │
│    │      └──> Child: render_header_cards()      │
│    │                                              │
│    ├──> do_action('wpapp_left_panel_content')    │
│    │      └──> Child: render_datatable()         │
│    │                                              │
│    └──> do_action('wpapp_tab_view_content')      │
│           └──> Child: render_info_tab()          │
│           └──> Child: render_branches_tab()      │
│           └──> Child: render_employees_tab()     │
│                                                   │
└───────────────────────────────────────────────────┘
```

### Available Hooks

#### Actions (for Content Injection)

1. **`wpapp_left_panel_content`**
   - **Purpose:** Inject DataTable HTML
   - **Parameters:** `$config` (array)
   - **Hooked by:** `render_datatable()`

2. **`wpapp_page_header_left`**
   - **Purpose:** Inject page title
   - **Parameters:** `$config` (array), `$entity` (string)
   - **Hooked by:** `render_header_title()`

3. **`wpapp_page_header_right`**
   - **Purpose:** Inject action buttons
   - **Parameters:** `$config` (array), `$entity` (string)
   - **Hooked by:** `render_header_buttons()`

4. **`wpapp_statistics_cards_content`**
   - **Purpose:** Inject statistics cards
   - **Parameters:** `$entity` (string)
   - **Hooked by:** `render_header_cards()`

5. **`wpapp_dashboard_filters`**
   - **Purpose:** Inject filter controls
   - **Parameters:** `$config` (array), `$entity` (string)
   - **Hooked by:** `render_filters()`

6. **`wpapp_tab_view_content`**
   - **Purpose:** Inject tab content
   - **Parameters:** `$tab_id` (string), `$entity` (string), `$data` (object)
   - **Hooked by:** Child class methods (e.g., `render_info_tab()`)

#### Filters (for Configuration)

1. **`wpapp_datatable_stats`**
   - **Purpose:** Register statistics boxes
   - **Parameters:** `$stats` (array), `$entity` (string)
   - **Returns:** Stats configuration array
   - **Hooked by:** `register_stats()`

2. **`wpapp_datatable_tabs`**
   - **Purpose:** Register tabs
   - **Parameters:** `$tabs` (array), `$entity` (string)
   - **Returns:** Tabs configuration array
   - **Hooked by:** `register_tabs()`

---

## Real-World Examples

### Example 1: Basic Dashboard

```php
<?php
namespace WPCustomer\Controllers\Customer;

use WPAppCore\Controllers\Abstract\AbstractDashboardController;

class CustomerDashboardController extends AbstractDashboardController {

    // Implement 13 abstract methods
    protected function getEntityName(): string {
        return 'customer';
    }

    protected function getEntityDisplayName(): string {
        return 'Customer';
    }

    protected function getEntityDisplayNamePlural(): string {
        return 'Customers';
    }

    protected function getTextDomain(): string {
        return 'wp-customer';
    }

    protected function getEntityPath(): string {
        return WP_CUSTOMER_PATH;
    }

    protected function getDataTableModel() {
        return new \WPCustomer\Models\Customer\CustomerDataTableModel();
    }

    protected function getModel() {
        return new \WPCustomer\Models\Customer\CustomerModel();
    }

    protected function getDataTableAjaxAction(): string {
        return 'get_customer_datatable';
    }

    protected function getDetailsAjaxAction(): string {
        return 'get_customer_details';
    }

    protected function getStatsAjaxAction(): string {
        return 'get_customer_stats';
    }

    protected function getViewCapability(): string {
        return 'view_customer_list';
    }

    protected function getStatsConfig(): array {
        return [
            'total' => [
                'label' => __('Total', 'wp-customer'),
                'value' => 0,
                'icon' => 'dashicons-businessperson',
                'color' => 'blue'
            ],
            'active' => [
                'label' => __('Active', 'wp-customer'),
                'value' => 0,
                'icon' => 'dashicons-yes-alt',
                'color' => 'green'
            ],
            'inactive' => [
                'label' => __('Inactive', 'wp-customer'),
                'value' => 0,
                'icon' => 'dashicons-dismiss',
                'color' => 'red'
            ]
        ];
    }

    protected function getTabsConfig(): array {
        return [
            'info' => [
                'title' => __('Information', 'wp-customer'),
                'priority' => 10
            ]
        ];
    }

    // Override register_hooks to add tab content
    protected function register_hooks(): void {
        parent::register_hooks();
        add_action('wpapp_tab_view_content', [$this, 'render_info_tab'], 10, 3);
    }

    public function render_info_tab($tab_id, $entity, $data): void {
        if ($entity !== 'customer' || $tab_id !== 'info') {
            return;
        }

        include WP_CUSTOMER_PATH . 'src/Views/customer/tabs/info.php';
    }
}
```

**Lines of Code:**
- **Before AbstractDashboardController:** ~900 lines
- **After AbstractDashboardController:** ~100 lines
- **Reduction:** 89% 🎉

---

### Example 2: Advanced with Custom Stats

```php
class CustomerDashboardController extends AbstractDashboardController {

    // ... abstract methods ...

    /**
     * Override getStatsData for custom logic
     */
    protected function getStatsData(): array {
        // Get base stats
        $stats = [
            'total' => $this->datatable_model->get_total_count('all'),
            'active' => $this->datatable_model->get_total_count('active'),
            'inactive' => $this->datatable_model->get_total_count('inactive')
        ];

        // Add custom stats
        $stats['premium'] = $this->getPremiumCustomersCount();
        $stats['trial'] = $this->getTrialCustomersCount();

        return $stats;
    }

    /**
     * Custom business logic
     */
    private function getPremiumCustomersCount(): int {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customers
             WHERE subscription_type = 'premium' AND status = 'active'"
        );
    }

    /**
     * Custom business logic
     */
    private function getTrialCustomersCount(): int {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}app_customers
             WHERE subscription_type = 'trial' AND status = 'active'"
        );
    }

    /**
     * Override getStatsConfig for additional stats
     */
    protected function getStatsConfig(): array {
        return [
            'total' => [
                'label' => __('Total', 'wp-customer'),
                'value' => 0,
                'icon' => 'dashicons-businessperson',
                'color' => 'blue'
            ],
            'active' => [
                'label' => __('Active', 'wp-customer'),
                'value' => 0,
                'icon' => 'dashicons-yes-alt',
                'color' => 'green'
            ],
            'inactive' => [
                'label' => __('Inactive', 'wp-customer'),
                'value' => 0,
                'icon' => 'dashicons-dismiss',
                'color' => 'red'
            ],
            'premium' => [
                'label' => __('Premium', 'wp-customer'),
                'value' => 0,
                'icon' => 'dashicons-star-filled',
                'color' => 'purple'
            ],
            'trial' => [
                'label' => __('Trial', 'wp-customer'),
                'value' => 0,
                'icon' => 'dashicons-clock',
                'color' => 'yellow'
            ]
        ];
    }
}
```

---

## Best Practices

### ✅ DO:

1. **Use Partial Templates for Reusability**
```php
// Good - reusable partials
render_partial('header-title', [], 'customer');
render_partial('stat-cards', $stats, 'customer');
```

2. **Follow Naming Conventions**
```php
// Views structure
/src/Views/{entity}/
    partials/
        header-title.php
        header-buttons.php
        stat-cards.php
    tabs/
        info.php
        branches.php
```

3. **Check Entity in Tab Render Methods**
```php
public function render_info_tab($tab_id, $entity, $data): void {
    // Always check both!
    if ($entity !== 'customer' || $tab_id !== 'info') {
        return;
    }

    // Render content
}
```

4. **Pass Data to Templates Properly**
```php
public function render_branches_tab($tab_id, $entity, $data): void {
    if ($entity !== 'customer' || $tab_id !== 'branches') {
        return;
    }

    // Make data available with clear variable name
    $customer = $data;
    include WP_CUSTOMER_PATH . 'src/Views/customer/tabs/branches.php';
}
```

---

### ❌ DON'T:

1. **Don't Forget to Call parent::register_hooks()**
```php
// ❌ Bad
protected function register_hooks(): void {
    add_action('wpapp_tab_view_content', [$this, 'render_info_tab'], 10, 3);
}

// ✅ Good
protected function register_hooks(): void {
    parent::register_hooks(); // MUST call parent!
    add_action('wpapp_tab_view_content', [$this, 'render_info_tab'], 10, 3);
}
```

2. **Don't Hardcode Entity Names**
```php
// ❌ Bad
protected function getEntityName(): string {
    return 'customer';
}

public function render_info_tab($tab_id, $entity, $data): void {
    if ($entity !== 'customer') { // Hardcoded!
        return;
    }
}

// ✅ Good
public function render_info_tab($tab_id, $entity, $data): void {
    if ($entity !== $this->getEntityName()) { // Dynamic!
        return;
    }
}
```

3. **Don't Skip Path Validation**
```php
// ❌ Bad
public function render_info_tab($tab_id, $entity, $data): void {
    include WP_CUSTOMER_PATH . 'src/Views/customer/tabs/info.php';
}

// ✅ Good
public function render_info_tab($tab_id, $entity, $data): void {
    $tab_file = WP_CUSTOMER_PATH . 'src/Views/customer/tabs/info.php';

    if (file_exists($tab_file)) {
        include $tab_file;
    }
}
```

---

## Troubleshooting

### Issue 1: Tabs Not Showing

**Cause:** Tab content hooks not registered.

**Solution:**
```php
protected function register_hooks(): void {
    parent::register_hooks(); // MUST call parent!

    // Register tab content hooks
    add_action('wpapp_tab_view_content', [$this, 'render_info_tab'], 10, 3);
}
```

---

### Issue 2: Statistics Not Updating

**Cause:** AJAX action mismatch or permission issue.

**Solution:**
```php
// PHP - getStatsAjaxAction()
protected function getStatsAjaxAction(): string {
    return 'get_customer_stats';
}

// JavaScript - must match!
$.ajax({
    url: ajaxurl,
    data: {
        action: 'get_customer_stats', // Must match!
        nonce: wpCustomer.nonce
    }
});

// Check permission capability
protected function getViewCapability(): string {
    return 'view_customer_list'; // User must have this!
}
```

---

### Issue 3: DataTable Not Loading

**Cause:** DataTable model method signature incorrect.

**Solution:**
```php
// DataTable model MUST have this method
public function get_datatable_data(array $request) {
    return [
        'draw' => (int) $request['draw'],
        'recordsTotal' => 100,
        'recordsFiltered' => 50,
        'data' => [
            // Row data...
        ]
    ];
}
```

---

### Issue 4: Panel Not Opening

**Cause:** Details AJAX action not working.

**Debug:**
```php
// Add logging
public function handle_get_details(): void {
    error_log('=== handle_get_details() called ===');
    error_log('POST data: ' . print_r($_POST, true));

    try {
        // ... rest of method
    } catch (\Exception $e) {
        error_log('Error: ' . $e->getMessage());
    }
}
```

---

## Migration Guide

### Before (Manual Dashboard)

```php
class CustomerDashboardController {

    public function __construct() {
        // Hook registration: 50 lines
    }

    public function renderDashboard() {
        // Template loading: 100 lines
    }

    public function render_datatable() {
        // DataTable HTML: 80 lines
    }

    public function render_header() {
        // Header rendering: 60 lines
    }

    public function render_stats() {
        // Stats cards: 100 lines
    }

    public function handle_datatable_ajax() {
        // AJAX handler: 120 lines
    }

    public function handle_get_details() {
        // Details handler: 150 lines
    }

    public function handle_get_stats() {
        // Stats handler: 80 lines
    }

    // Tab rendering methods: 200 lines
}
// TOTAL: ~940 lines
```

### After (AbstractDashboardController)

```php
class CustomerDashboardController extends AbstractDashboardController {
    // 13 abstract methods: ~100 lines
    // Tab rendering: ~40 lines
    // All other methods inherited FREE
}
// TOTAL: ~140 lines (85% reduction!)
```

---

## Changelog

### v1.0.0 (2025-01-02)
- ✅ Initial implementation
- ✅ Dashboard rendering: `renderDashboard()`
- ✅ Hook-based architecture with 6 action hooks
- ✅ DataTable integration: `render_datatable()`, `handle_datatable_ajax()`
- ✅ Statistics system: `render_header_cards()`, `handle_get_stats()`
- ✅ Tabbed panel: `render_tab_contents()`, `handle_get_details()`
- ✅ Filter system: `render_filters()`
- ✅ Partial rendering: `render_partial()`
- ✅ 13 abstract methods for entity-specific configuration
- ✅ Comprehensive PHPDoc documentation

---

## Related Documentation

- [AbstractCrudController](./ABSTRACT-CRUD-CONTROLLER.md) - CRUD operations controller
- [DataTable System](../datatable/ARCHITECTURE.md) - DataTable implementation guide
- [Creating New Plugin](./CREATING-NEW-PLUGIN-GUIDE.md) - Complete step-by-step guide

---

**Last Updated:** 2025-01-02
**Version:** 1.0.0
**Status:** ✅ Production Ready
