# Common Issues & Solutions

**Quick Reference for DataTable Implementation**

Version: 1.0.0
Created: 2025-11-01
Author: Based on TODO-2187 (WP Customer Migration)

---

## Table of Contents

1. [Email Column Issues](#email-column-issues)
2. [AJAX 403 Forbidden](#ajax-403-forbidden)
3. [Table Layout Problems](#table-layout-problems)
4. [Empty Tab Content](#empty-tab-content)
5. [DataTable Model Best Practices](#datatable-model-best-practices)
6. [Complete Implementation Checklist](#complete-implementation-checklist)

---

## Email Column Issues

### Problem: "Unknown column 'c.email' in field list"

**Symptom:**
```
DataTables warning: table id=xxx-table - Invalid JSON response
Debug log: Unknown column 'c.email' in 'field list'
```

**Root Cause:**
Email column doesn't exist in main table - it's usually in a related table (users, branches, etc.)

**Solution:**

**Step 1: Identify where email is stored**

Check your database schema:
```sql
-- Example: Email in wp_users table
DESCRIBE wp_users;
-- Has: user_email column

-- Example: Email in branches table
DESCRIBE wp_app_customer_branches;
-- Has: email column
```

**Step 2: Add proper JOIN in DataTableModel**

```php
<?php
// File: YourDataTableModel.php

public function __construct() {
    parent::__construct();
    global $wpdb;

    $this->table = $wpdb->prefix . 'app_customers c';

    // IMPORTANT: Add JOIN to get email
    // Option 1: From wp_users
    $this->base_joins = [
        "LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID"
    ];

    // Option 2: From related table (e.g., branches)
    $this->base_joins = [
        "LEFT JOIN {$wpdb->prefix}app_customer_branches b ON c.id = b.customer_id AND b.type = 'pusat'"
    ];

    // Option 3: First branch with email (using subquery)
    $this->base_joins = [
        "LEFT JOIN (
            SELECT customer_id, MIN(id) as branch_id
            FROM {$wpdb->prefix}app_customer_branches
            WHERE email IS NOT NULL
            GROUP BY customer_id
        ) bmin ON c.id = bmin.customer_id",
        "LEFT JOIN {$wpdb->prefix}app_customer_branches b ON bmin.branch_id = b.id"
    ];

    // Update searchable columns
    $this->searchable_columns = [
        'c.code',
        'c.name',
        // Use the JOIN alias, NOT the main table
        'u.user_email'  // or 'b.email' depending on your JOIN
    ];
}

protected function get_columns(): array {
    return [
        'c.id as id',
        'c.code as code',
        'c.name as name',
        // IMPORTANT: Use the JOIN alias
        'u.user_email as email'  // or 'b.email as email'
    ];
}
```

**Common Mistake:**
```php
// ❌ WRONG: Trying to get email from main table
'c.email as email'  // Column doesn't exist!

// ✅ CORRECT: Get from JOIN
'u.user_email as email'  // or 'b.email as email'
```

---

## AJAX 403 Forbidden

### Problem: Statistics or Panel Loading Returns 403

**Symptom:**
```
/wp-admin/admin-ajax.php:1 Failed to load resource: 403 (Forbidden)
console: Failed to load statistics: Forbidden
```

**Root Cause:**
Duplicate AJAX handlers with different nonce requirements

**Solution:**

**Step 1: Check for duplicate handlers**

```bash
# Search for duplicate AJAX actions
wp eval "global \$wp_filter; if (isset(\$wp_filter['wp_ajax_get_customer_stats'])) { print_r(\$wp_filter['wp_ajax_get_customer_stats']->callbacks); }"
```

**Step 2: Disable old handlers**

If you have OLD and NEW implementations (e.g., during migration):

```php
<?php
// OLD Controller (CustomerController.php)
// COMMENT OUT old handler:
// add_action('wp_ajax_get_customer_stats', [$this, 'getStats']);

// NEW Controller (CustomerDashboardController.php)
add_action('wp_ajax_get_customer_stats', [$this, 'handle_get_stats']);
```

**Step 3: Ensure consistent nonce**

```php
<?php
// JavaScript localization
wp_localize_script('your-script', 'wpAppData', [
    'nonce' => wp_create_nonce('wpapp_panel_nonce'),  // Use wpapp_panel_nonce
]);

// AJAX Handler
public function handle_get_stats(): void {
    // Verify with SAME nonce
    check_ajax_referer('wpapp_panel_nonce', 'nonce');

    // ... your code
}
```

**Step 4: Disable conflicting scripts**

If you have old JavaScript files still loading:

```php
<?php
// File: class-dependencies.php

// COMMENT OUT old script
/*
wp_enqueue_script('old-customer-script',
    WP_PLUGIN_URL . 'assets/js/old-customer-script.js',
    [...],
    $this->version,
    true
);
*/

// Load ONLY new script
wp_enqueue_script('customer-datatable',
    WP_PLUGIN_URL . 'assets/js/customer/customer-datatable.js',
    ['jquery', 'datatables'],
    $this->version,
    true
);
```

**Common Mistake:**
```php
// ❌ WRONG: Using different nonces
// JavaScript: nonce from 'wp_customer_nonce'
// PHP Handler: checking 'wpapp_panel_nonce'

// ✅ CORRECT: Same nonce everywhere
// JavaScript: nonce from 'wpapp_panel_nonce'
// PHP Handler: checking 'wpapp_panel_nonce'
```

---

## Table Layout Problems

### Problem: DataTable Columns Overlapping/Nabrak

**Symptom:**
Text from different columns running into each other, columns too narrow or too wide

**Root Cause:**
No explicit column width control

**Solution:**

**Step 1: Create DataTable-specific CSS file**

```css
/* File: your-plugin/assets/css/your-entity/your-entity-datatable.css */

/* Force fixed table layout */
#your-entity-list-table {
    width: 100% !important;
    table-layout: fixed !important;
    border-collapse: collapse;
}

/* Prevent text overflow */
#your-entity-list-table thead th,
#your-entity-list-table tbody td {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Set explicit column widths */
/* Code Column - 10% */
#your-entity-list-table th:nth-child(1),
#your-entity-list-table td:nth-child(1) {
    width: 10%;
    max-width: 10%;
}

/* Name Column - 25% */
#your-entity-list-table th:nth-child(2),
#your-entity-list-table td:nth-child(2) {
    width: 25%;
    max-width: 25%;
}

/* Email Column - 20% */
#your-entity-list-table th:nth-child(3),
#your-entity-list-table td:nth-child(3) {
    width: 20%;
    max-width: 20%;
}

/* Actions Column - 15% */
#your-entity-list-table th:nth-child(4),
#your-entity-list-table td:nth-child(4) {
    width: 15%;
    max-width: 15%;
}
```

**Step 2: Add JavaScript configuration**

```javascript
// File: your-entity-datatable.js

var dataTable = $('#your-entity-list-table').DataTable({
    processing: true,
    serverSide: true,
    autoWidth: false,  // IMPORTANT: Disable auto width
    columns: [
        { data: 'code' },
        { data: 'name' },
        { data: 'email' },
        { data: 'actions', orderable: false, searchable: false }
    ],
    columnDefs: [
        { width: '10%', targets: 0 },   // Code
        { width: '25%', targets: 1 },   // Name
        { width: '20%', targets: 2 },   // Email
        { width: '15%', targets: 3 }    // Actions
    ]
});
```

**Step 3: Enqueue CSS file**

```php
<?php
// File: class-dependencies.php

if ($screen && $screen->id === 'toplevel_page_your-entity') {
    // Enqueue DataTable-specific CSS
    wp_enqueue_style(
        'your-entity-datatable',
        YOUR_PLUGIN_URL . 'assets/css/your-entity/your-entity-datatable.css',
        ['datatables'],  // Dependency on DataTables CSS
        $this->version
    );
}
```

**Width Planning Tips:**

```
Total available: 100%
Recommended distribution:
- ID/Code: 8-12%
- Short text (Status): 10-15%
- Medium text (Name): 20-30%
- Long text (Email, Address): 20-25%
- Actions (buttons): 10-15%

Example for 6 columns:
Code(10%) + Name(25%) + Email(20%) + Phone(15%) + Status(15%) + Actions(15%) = 100%
```

---

## Empty Tab Content

### Problem: Tab Shows "Data Not Available"

**Symptom:**
When clicking a row, tab opens but shows "Customer data not available" or similar message

**Root Cause:**
Variable `$data` not passed to tab template

**Solution:**

**In Controller's AJAX handler:**

```php
<?php
// File: YourDashboardController.php

public function handle_get_details(): void {
    try {
        check_ajax_referer('wpapp_panel_nonce', 'nonce');

        $entity_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $entity = $this->model->find($entity_id);

        if (!$entity) {
            throw new \Exception(__('Entity not found'));
        }

        // IMPORTANT: Make data available as $data for templates
        $data = $entity;

        $tabs = [];

        // Info tab
        ob_start();
        include YOUR_PLUGIN_PATH . 'src/Views/entity/tabs/info.php';
        $tabs['info'] = ob_get_clean();

        // Other tabs...

        wp_send_json_success([
            'title' => $entity->name,
            'tabs' => $tabs,
            'data' => $entity
        ]);

    } catch (\Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
```

**In Tab Template:**

```php
<?php
// File: src/Views/entity/tabs/info.php

defined('ABSPATH') || exit;

// Check if $data is available
if (!isset($data) || !is_object($data)) {
    echo '<p>' . esc_html__('Data not available') . '</p>';
    return;
}

// Now you can safely use $data
$entity = $data;
?>

<div class="entity-info-tab">
    <div class="entity-info-row">
        <label><?php echo esc_html__('Name:'); ?></label>
        <span><?php echo esc_html($entity->name ?? '-'); ?></span>
    </div>

    <div class="entity-info-row">
        <label><?php echo esc_html__('Email:'); ?></label>
        <span><?php echo esc_html($entity->email ?? '-'); ?></span>
    </div>
</div>
```

**Common Mistake:**
```php
// ❌ WRONG: Not setting $data before include
ob_start();
include 'tabs/info.php';  // Template expects $data but it doesn't exist
$tabs['info'] = ob_get_clean();

// ✅ CORRECT: Set $data first
$data = $customer;  // Make it available
ob_start();
include 'tabs/info.php';  // Now template has $data
$tabs['info'] = ob_get_clean();
```

---

## DataTable Model Best Practices

### Complete Model Example

```php
<?php
/**
 * Entity DataTable Model
 *
 * @package     YourPlugin
 * @version     1.0.0
 */

namespace YourPlugin\Models\Entity;

use WPAppCore\Models\DataTable\DataTableModel;

class EntityDataTableModel extends DataTableModel {

    public function __construct() {
        parent::__construct();

        global $wpdb;

        // Set table with alias
        $this->table = $wpdb->prefix . 'app_entities e';
        $this->index_column = 'e.id';

        // Add JOINs if needed for related data
        $this->base_joins = [
            "LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID"
        ];

        // Define searchable columns (use aliases from JOINs)
        $this->searchable_columns = [
            'e.code',
            'e.name',
            'e.npwp',
            'u.user_email'  // From JOIN
        ];

        // Optional: Base WHERE conditions
        $this->base_where = [
            'e.deleted_at IS NULL'  // Soft delete check
        ];
    }

    /**
     * Define columns to SELECT
     */
    protected function get_columns(): array {
        return [
            'e.id as id',
            'e.code as code',
            'e.name as name',
            'e.npwp as npwp',
            'e.status as status',
            'u.user_email as email'  // From JOIN
        ];
    }

    /**
     * Format each row for DataTable output
     */
    protected function format_row($row): array {
        return [
            // Required for panel system
            'DT_RowId' => 'entity-' . $row->id,
            'DT_RowData' => [
                'id' => $row->id,
                'entity' => 'entity'  // Must match your entity name
            ],

            // Data columns
            'code' => esc_html($row->code),
            'name' => esc_html($row->name),
            'npwp' => esc_html($row->npwp ?? '-'),
            'email' => esc_html($row->email ?? '-'),
            'status' => $this->format_status_badge($row->status),
            'actions' => $this->generate_action_buttons($row)
        ];
    }

    /**
     * Apply WHERE conditions for filtering
     */
    protected function get_where(): array {
        global $wpdb;
        $where = [];

        // Status filter from POST
        $status_filter = isset($_POST['status_filter'])
            ? sanitize_text_field($_POST['status_filter'])
            : 'active';

        if ($status_filter !== 'all') {
            $where[] = $wpdb->prepare('e.status = %s', $status_filter);
        }

        return $where;
    }

    /**
     * Helper: Format status badge
     */
    private function format_status_badge($status): string {
        $class = $status === 'active' ? 'status-active' : 'status-inactive';
        $label = $status === 'active' ? __('Active') : __('Inactive');

        return sprintf(
            '<span class="status-badge %s">%s</span>',
            esc_attr($class),
            esc_html($label)
        );
    }

    /**
     * Helper: Generate action buttons
     */
    private function generate_action_buttons($row): string {
        $actions = [];

        // View button (opens panel)
        $actions[] = sprintf(
            '<button class="button button-small view-entity-btn" data-id="%d">%s</button>',
            $row->id,
            __('View')
        );

        // Edit button (if has permission)
        if (current_user_can('edit_entities')) {
            $actions[] = sprintf(
                '<button class="button button-small edit-entity-btn" data-id="%d">%s</button>',
                $row->id,
                __('Edit')
            );
        }

        // Delete button (if has permission)
        if (current_user_can('delete_entities')) {
            $actions[] = sprintf(
                '<button class="button button-small button-link-delete delete-entity-btn" data-id="%d">%s</button>',
                $row->id,
                __('Delete')
            );
        }

        return implode(' ', $actions);
    }

    /**
     * Get total count for statistics
     */
    public function get_total_count(string $status_filter = 'all'): int {
        global $wpdb;

        $sql = "SELECT COUNT(e.id) FROM {$this->table}";

        // Add JOINs
        foreach ($this->base_joins as $join) {
            $sql .= " {$join}";
        }

        $where = ['1=1'];

        // Add base WHERE
        foreach ($this->base_where as $condition) {
            $where[] = $condition;
        }

        // Add status filter
        if ($status_filter !== 'all') {
            $where[] = $wpdb->prepare('e.status = %s', $status_filter);
        }

        $sql .= ' WHERE ' . implode(' AND ', $where);

        return (int) $wpdb->get_var($sql);
    }
}
```

---

## Complete Implementation Checklist

Use this checklist when implementing a new DataTable page:

### Phase 1: Model & Controller

- [ ] **Create DataTableModel class**
  - [ ] Extends `WPAppCore\Models\DataTable\DataTableModel`
  - [ ] Set `$this->table` with alias (e.g., `wp_app_entities e`)
  - [ ] Set `$this->index_column` (e.g., `e.id`)
  - [ ] Add `$this->base_joins` if need related data (email, etc.)
  - [ ] Define `$this->searchable_columns` (use JOIN aliases)
  - [ ] Implement `get_columns()` method (use JOIN aliases for email)
  - [ ] Implement `format_row()` method (include DT_RowId, DT_RowData)
  - [ ] Implement `get_where()` for filtering
  - [ ] Add `get_total_count()` for statistics

- [ ] **Create DashboardController class**
  - [ ] Register hooks in `__construct()`
  - [ ] Hook: `wpapp_left_panel_content` → render DataTable HTML
  - [ ] Hook: `wpapp_page_header_left` → render header title
  - [ ] Hook: `wpapp_page_header_right` → render header buttons
  - [ ] Hook: `wpapp_statistics_cards_content` → render stat cards
  - [ ] Hook: `wpapp_dashboard_filters` → render filters
  - [ ] Filter: `wpapp_datatable_stats` → register stats
  - [ ] Filter: `wpapp_datatable_tabs` → register tabs
  - [ ] Action: `wpapp_tab_view_content` → render each tab
  - [ ] AJAX: `wp_ajax_get_entity_datatable` → DataTable data
  - [ ] AJAX: `wp_ajax_get_entity_details` → panel data (SET $data variable!)
  - [ ] AJAX: `wp_ajax_get_entity_stats` → statistics
  - [ ] Implement `renderDashboard()` using `DashboardTemplate::render()`

### Phase 2: Views

- [ ] **Create view structure**
  - [ ] `src/Views/entity/partials/header-title.php`
  - [ ] `src/Views/entity/partials/header-buttons.php`
  - [ ] `src/Views/entity/partials/stat-cards.php`
  - [ ] `src/Views/entity/tabs/info.php` (check for `$data` variable!)
  - [ ] `src/Views/entity/tabs/placeholder.php`

- [ ] **Verify directory permissions**
  ```bash
  chmod 755 src/Views/entity
  chmod 755 src/Views/entity/partials
  chmod 755 src/Views/entity/tabs
  ```

### Phase 3: Assets

- [ ] **Create CSS files**
  - [ ] `assets/css/entity/entity-header-cards.css`
  - [ ] `assets/css/entity/entity-filter.css`
  - [ ] `assets/css/entity/entity-datatable.css` (table-layout: fixed!)

- [ ] **Create JavaScript file**
  - [ ] `assets/js/entity/entity-datatable.js`
  - [ ] Add `autoWidth: false`
  - [ ] Add `columnDefs` with width percentages
  - [ ] Initialize DataTable with server-side processing
  - [ ] Load statistics via AJAX

- [ ] **Enqueue assets**
  - [ ] Enqueue CSS files (dependencies: `['datatables']`)
  - [ ] Enqueue JavaScript file (dependencies: `['jquery', 'datatables']`)
  - [ ] Add localization with `wp_localize_script()`
  - [ ] Use nonce: `'wpapp_panel_nonce'`

### Phase 4: Integration

- [ ] **Update MenuManager**
  - [ ] Import DashboardController
  - [ ] Add controller property
  - [ ] Initialize in constructor
  - [ ] Change menu callback to `[$this->dashboard_controller, 'renderDashboard']`

- [ ] **Disable old implementations**
  - [ ] Comment out old AJAX handlers
  - [ ] Comment out old JavaScript files
  - [ ] Remove script dependencies on old files

### Phase 5: Testing

- [ ] **Test DataTable**
  - [ ] Page loads without errors
  - [ ] Table displays data correctly
  - [ ] Search works
  - [ ] Sorting works
  - [ ] Pagination works
  - [ ] Status filter works
  - [ ] No column overlap/nabrak
  - [ ] Email column displays correctly (from JOIN)

- [ ] **Test Statistics**
  - [ ] Stat cards load via AJAX
  - [ ] Numbers display correctly
  - [ ] No 403 errors in console

- [ ] **Test Panel**
  - [ ] Row click opens panel
  - [ ] Panel slides in smoothly
  - [ ] Close button works
  - [ ] Hash navigation works (#entity-123)

- [ ] **Test Tabs**
  - [ ] Info tab shows data (not "Data not available")
  - [ ] All data fields display correctly
  - [ ] Tab switching works
  - [ ] Keyboard navigation works

- [ ] **Test Responsive**
  - [ ] Mobile view works
  - [ ] Tablet view works
  - [ ] Panel closes properly on small screens

### Phase 6: Cleanup

- [ ] **Documentation**
  - [ ] Update TODO file with implementation notes
  - [ ] Document any issues found and fixed
  - [ ] Add version numbers to all files

- [ ] **Performance**
  - [ ] Check query performance (EXPLAIN)
  - [ ] Add indexes if needed
  - [ ] Cache statistics if expensive

- [ ] **Clear caches**
  ```bash
  wp cache flush
  ```

---

## Quick Debug Commands

```bash
# Check AJAX handlers
wp eval "global \$wp_filter; print_r(\$wp_filter['wp_ajax_get_entity_stats']->callbacks);"

# Test database query
wp db query "DESCRIBE wp_app_entities"

# Check directory permissions
ls -la /path/to/src/Views/entity/

# Fix permissions
chmod 755 /path/to/src/Views/entity/
chmod 755 /path/to/src/Views/entity/partials/
chmod 755 /path/to/src/Views/entity/tabs/

# Clear cache
wp cache flush

# Check debug log
tail -f /path/to/wp-content/debug.log | grep -i error
```

---

**Pro Tip:** Print this checklist and check off items as you implement. It will save you hours of debugging!

---

**Version**: 1.0.0
**Author**: Based on TODO-2187 lessons learned
**Date**: 2025-11-01
