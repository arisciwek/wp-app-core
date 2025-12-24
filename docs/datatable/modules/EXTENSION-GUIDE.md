# Module Extension Guide

## Overview

This guide shows how to extend the DataTable system from module plugins (like `wp-customer`, `wp-agency`, etc.) without modifying the core code.

## File Reference

**Core Files (wp-app-core) - You Will Extend These:**
- `src/Models/DataTable/DataTableModel.php` - Base model to extend
- `src/Controllers/DataTable/DataTableController.php` - Use for AJAX handling

**Example Module Implementation (wp-customer):**
- `wp-customer/src/Models/Customer/CustomerDataTableModel.php` - Extended model
- `wp-customer/src/Filters/CustomerDataTableFilters.php` - Filter implementations
- `wp-customer/src/Controllers/Customer/CustomerController.php` - AJAX registration

**Example Core Implementation (wp-app-core):**
- `src/Models/Platform/PlatformStaffDataTableModel.php` - Reference implementation
- `src/Tests/DataTable/PlatformStaffDataTableFilters.php` - Filter examples

---

## Extension Patterns

There are two main ways to extend DataTables:

1. **Create Module-Specific DataTables**: Extend `DataTableModel` for module tables
2. **Hook into Existing DataTables**: Use filters to modify other DataTables

---

## Pattern 1: Create Module-Specific DataTable

### Example: Customer DataTable in wp-customer

#### Step 1: Create Model

**File**: `wp-customer/src/Models/Customer/CustomerDataTableModel.php`

```php
<?php
/**
 * Customer DataTable Model
 *
 * @package WPCustomer
 */

namespace WPCustomer\Models\Customer;

use WPAppCore\Models\DataTable\DataTableModel;

class CustomerDataTableModel extends DataTableModel {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        // Define table
        $this->table = $this->wpdb->prefix . 'customers';

        // Define columns to select
        $this->columns = [
            'id',
            'company_name',
            'contact_name',
            'email',
            'phone',
            'status',
            'created_at'
        ];

        // Define searchable columns
        $this->searchable_columns = [
            'company_name',
            'contact_name',
            'email',
            'phone'
        ];

        // Define primary key
        $this->index_column = 'id';

        // Optional: Add base WHERE conditions
        $this->base_where = [
            "deleted_at IS NULL" // Only show non-deleted customers
        ];

        // Optional: Add base JOINs
        // $this->base_joins = [
        //     "LEFT JOIN {$this->wpdb->prefix}users u ON u.ID = user_id"
        // ];
    }

    /**
     * Format row for output
     *
     * This is where you customize how each row appears in the DataTable
     *
     * @param object $row Database row object
     * @return array Formatted row data
     */
    protected function format_row($row) {
        return [
            // Column 0: ID
            $row->id,

            // Column 1: Company name with link
            sprintf(
                '<a href="%s" class="customer-link">%s</a>',
                admin_url('admin.php?page=customer-view&id=' . $row->id),
                esc_html($row->company_name)
            ),

            // Column 2: Contact name
            esc_html($row->contact_name),

            // Column 3: Email with mailto link
            sprintf(
                '<a href="mailto:%s">%s</a>',
                esc_attr($row->email),
                esc_html($row->email)
            ),

            // Column 4: Phone
            esc_html($row->phone),

            // Column 5: Status badge
            $this->get_status_badge($row->status),

            // Column 6: Formatted date
            date_i18n(get_option('date_format'), strtotime($row->created_at)),

            // Column 7: Actions
            $this->get_action_buttons($row)
        ];
    }

    /**
     * Get status badge HTML
     *
     * @param string $status
     * @return string
     */
    private function get_status_badge($status) {
        $badges = [
            'active' => '<span class="badge badge-success">Active</span>',
            'inactive' => '<span class="badge badge-secondary">Inactive</span>',
            'pending' => '<span class="badge badge-warning">Pending</span>',
        ];

        return $badges[$status] ?? esc_html($status);
    }

    /**
     * Get action buttons HTML
     *
     * @param object $row
     * @return string
     */
    private function get_action_buttons($row) {
        $actions = [];

        // View button
        if (current_user_can('view_customers')) {
            $actions[] = sprintf(
                '<a href="%s" class="btn btn-sm btn-info" title="%s">
                    <i class="fa fa-eye"></i>
                </a>',
                admin_url('admin.php?page=customer-view&id=' . $row->id),
                __('View', 'wp-customer')
            );
        }

        // Edit button
        if (current_user_can('edit_customers')) {
            $actions[] = sprintf(
                '<a href="%s" class="btn btn-sm btn-primary" title="%s">
                    <i class="fa fa-edit"></i>
                </a>',
                admin_url('admin.php?page=customer-edit&id=' . $row->id),
                __('Edit', 'wp-customer')
            );
        }

        // Delete button
        if (current_user_can('delete_customers')) {
            $actions[] = sprintf(
                '<a href="#" class="btn btn-sm btn-danger delete-customer"
                   data-id="%d" title="%s">
                    <i class="fa fa-trash"></i>
                </a>',
                $row->id,
                __('Delete', 'wp-customer')
            );
        }

        return '<div class="btn-group">' . implode('', $actions) . '</div>';
    }

    /**
     * Optional: Override get_columns() for dynamic columns
     *
     * @return array
     */
    protected function get_columns() {
        $columns = parent::get_columns();

        // Example: Add custom column based on some condition
        if (current_user_can('view_customer_stats')) {
            $columns[] = '(SELECT COUNT(*) FROM ' .
                         $this->wpdb->prefix . 'orders WHERE customer_id = ' .
                         $this->table . '.id) as order_count';
        }

        return $columns;
    }
}
```

#### Step 2: Register AJAX Handler

**File**: `wp-customer/src/Controllers/CustomerController.php` or init file

```php
<?php
namespace WPCustomer\Controllers;

use WPAppCore\Controllers\DataTable\DataTableController;
use WPCustomer\Models\Customer\CustomerDataTableModel;

class CustomerController {

    public function __construct() {
        $this->register_ajax_handlers();
    }

    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        // Register customer datatable AJAX handler
        DataTableController::register_ajax_action(
            'customer_datatable',
            CustomerDataTableModel::class
        );
    }
}

// Initialize
new CustomerController();
```

#### Step 3: Create Admin Page with DataTable

**File**: `wp-customer/src/Views/customers/list.php`

```php
<div class="wrap">
    <h1><?php _e('Customers', 'wp-customer'); ?></h1>

    <div class="card">
        <div class="card-body">
            <table id="customers-datatable" class="table table-striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'wp-customer'); ?></th>
                        <th><?php _e('Company', 'wp-customer'); ?></th>
                        <th><?php _e('Contact', 'wp-customer'); ?></th>
                        <th><?php _e('Email', 'wp-customer'); ?></th>
                        <th><?php _e('Phone', 'wp-customer'); ?></th>
                        <th><?php _e('Status', 'wp-customer'); ?></th>
                        <th><?php _e('Created', 'wp-customer'); ?></th>
                        <th><?php _e('Actions', 'wp-customer'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- DataTables will populate this -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#customers-datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: wpapp_datatable.ajax_url,
            type: 'POST',
            data: {
                action: 'customer_datatable',
                nonce: wpapp_datatable.nonce,
                // Add custom filters
                status: $('#filter-status').val(),
            }
        },
        columns: [
            { data: 0, orderable: true },  // ID
            { data: 1, orderable: true },  // Company
            { data: 2, orderable: true },  // Contact
            { data: 3, orderable: true },  // Email
            { data: 4, orderable: false }, // Phone
            { data: 5, orderable: true },  // Status
            { data: 6, orderable: true },  // Created
            { data: 7, orderable: false }  // Actions
        ],
        order: [[0, 'desc']], // Default sort by ID descending
        pageLength: 25,
        language: {
            processing: '<?php _e('Loading...', 'wp-customer'); ?>',
            search: '<?php _e('Search:', 'wp-customer'); ?>',
            lengthMenu: '<?php _e('Show _MENU_ entries', 'wp-customer'); ?>',
            info: '<?php _e('Showing _START_ to _END_ of _TOTAL_ entries', 'wp-customer'); ?>',
            infoEmpty: '<?php _e('No entries to show', 'wp-customer'); ?>',
            infoFiltered: '<?php _e('(filtered from _MAX_ total entries)', 'wp-customer'); ?>',
            paginate: {
                first: '<?php _e('First', 'wp-customer'); ?>',
                last: '<?php _e('Last', 'wp-customer'); ?>',
                next: '<?php _e('Next', 'wp-customer'); ?>',
                previous: '<?php _e('Previous', 'wp-customer'); ?>'
            }
        }
    });
});
</script>
```

---

## Pattern 2: Hook into Existing DataTables

Sometimes you want to modify a DataTable created by another plugin without changing their code.

### Example: Agency Filter for Customer DataTable

**Scenario**: wp-agency wants to filter customers by agency_id in the customer DataTable

**File**: `wp-agency/src/Filters/CustomerDataTableFilters.php`

```php
<?php
/**
 * Agency filters for Customer DataTable
 *
 * This class hooks into customer DataTable to add agency-specific filtering
 *
 * @package WPAgency
 */

namespace WPAgency\Filters;

class CustomerDataTableFilters {

    /**
     * Constructor - Register all filters
     */
    public function __construct() {
        // Add WHERE condition to filter by agency
        add_filter(
            'wpapp_datatable_customers_where',
            [$this, 'filter_by_agency'],
            10,
            3
        );

        // Add agency column to customer table
        add_filter(
            'wpapp_datatable_customers_columns',
            [$this, 'add_agency_column'],
            10,
            3
        );

        // Add JOIN to get agency name
        add_filter(
            'wpapp_datatable_customers_joins',
            [$this, 'add_agency_join'],
            10,
            3
        );

        // Modify row data to show agency name
        add_filter(
            'wpapp_datatable_customers_row_data',
            [$this, 'add_agency_to_row'],
            10,
            3
        );

        // Add permission check
        add_filter(
            'wpapp_datatable_can_access',
            [$this, 'check_agency_permission'],
            10,
            2
        );
    }

    /**
     * Filter customers by agency
     *
     * @param array $where Existing WHERE conditions
     * @param array $request_data DataTables request data
     * @param object $model DataTable model instance
     * @return array Modified WHERE conditions
     */
    public function filter_by_agency($where, $request_data, $model) {

        // If agency_id is in request, filter by it
        if (isset($_GET['agency_id']) && !empty($_GET['agency_id'])) {
            $agency_id = intval($_GET['agency_id']);
            $where[] = "agency_id = {$agency_id}";
        }

        // If user is agency staff, only show their agency's customers
        if ($this->is_agency_staff()) {
            $agency_id = $this->get_user_agency_id();
            if ($agency_id) {
                $where[] = "agency_id = {$agency_id}";
            }
        }

        return $where;
    }

    /**
     * Add agency column
     *
     * @param array $columns Existing columns
     * @param object $model DataTable model instance
     * @param array $request_data DataTables request data
     * @return array Modified columns
     */
    public function add_agency_column($columns, $model, $request_data) {
        // Add agency name from joined table
        $columns[] = 'agencies.name as agency_name';

        return $columns;
    }

    /**
     * Add JOIN to agencies table
     *
     * @param array $joins Existing JOINs
     * @param array $request_data DataTables request data
     * @param object $model DataTable model instance
     * @return array Modified JOINs
     */
    public function add_agency_join($joins, $request_data, $model) {
        global $wpdb;

        $joins[] = "LEFT JOIN {$wpdb->prefix}agencies agencies ON agencies.id = agency_id";

        return $joins;
    }

    /**
     * Add agency name to row data
     *
     * @param array $formatted_row Formatted row data
     * @param object $raw_row Raw database row
     * @param object $model DataTable model instance
     * @return array Modified row data
     */
    public function add_agency_to_row($formatted_row, $raw_row, $model) {

        // Insert agency name after company name
        // Assuming company name is at index 1
        array_splice($formatted_row, 2, 0, [
            sprintf(
                '<a href="%s">%s</a>',
                admin_url('admin.php?page=agency-view&id=' . $raw_row->agency_id),
                esc_html($raw_row->agency_name ?? '-')
            )
        ]);

        return $formatted_row;
    }

    /**
     * Check if user can access customer datatable
     *
     * @param bool $can_access Current access status
     * @param string $model_class Model class name
     * @return bool Modified access status
     */
    public function check_agency_permission($can_access, $model_class) {

        // Only apply to customer datatable
        if ($model_class !== 'WPCustomer\\Models\\Customer\\CustomerDataTableModel') {
            return $can_access;
        }

        // Agency staff can view customers
        if ($this->is_agency_staff()) {
            return true;
        }

        return $can_access;
    }

    /**
     * Check if current user is agency staff
     *
     * @return bool
     */
    private function is_agency_staff() {
        $user = wp_get_current_user();
        return in_array('agency_staff', $user->roles);
    }

    /**
     * Get agency ID for current user
     *
     * @return int|null
     */
    private function get_user_agency_id() {
        return get_user_meta(get_current_user_id(), 'agency_id', true);
    }
}

// Initialize filters
new CustomerDataTableFilters();
```

---

## Advanced Extension Patterns

### 1. Dynamic Column Addition Based on Settings

```php
<?php
public function add_conditional_columns($columns, $model, $request_data) {

    // Check plugin settings
    $show_stats = get_option('customer_show_stats', false);

    if ($show_stats) {
        // Add order count subquery
        $columns[] = '(SELECT COUNT(*) FROM wp_orders WHERE customer_id = wp_customers.id) as order_count';

        // Add total revenue subquery
        $columns[] = '(SELECT SUM(total) FROM wp_orders WHERE customer_id = wp_customers.id) as total_revenue';
    }

    return $columns;
}
```

### 2. Complex WHERE Conditions

```php
<?php
public function add_complex_filters($where, $request_data, $model) {

    // Filter by date range
    if (isset($_GET['date_from']) && isset($_GET['date_to'])) {
        $date_from = sanitize_text_field($_GET['date_from']);
        $date_to = sanitize_text_field($_GET['date_to']);

        global $wpdb;
        $where[] = $wpdb->prepare(
            "created_at BETWEEN %s AND %s",
            $date_from,
            $date_to
        );
    }

    // Filter by multiple statuses (comma-separated)
    if (isset($_GET['statuses']) && !empty($_GET['statuses'])) {
        $statuses = array_map('sanitize_text_field', explode(',', $_GET['statuses']));
        $placeholders = implode(',', array_fill(0, count($statuses), '%s'));

        global $wpdb;
        $where[] = $wpdb->prepare(
            "status IN ({$placeholders})",
            ...$statuses
        );
    }

    // Complex OR condition
    if (isset($_GET['vip_or_high_value'])) {
        $where[] = "(is_vip = 1 OR total_orders > 100)";
    }

    return $where;
}
```

### 3. Multiple JOINs

```php
<?php
public function add_multiple_joins($joins, $request_data, $model) {
    global $wpdb;

    // Join users table
    $joins[] = "LEFT JOIN {$wpdb->prefix}users u ON u.ID = user_id";

    // Join agencies table
    $joins[] = "LEFT JOIN {$wpdb->prefix}agencies a ON a.id = agency_id";

    // Join custom meta table
    $joins[] = "LEFT JOIN {$wpdb->prefix}customer_meta cm ON cm.customer_id = wp_customers.id";

    return $joins;
}
```

### 4. Row Data Transformation

```php
<?php
public function transform_row_data($formatted_row, $raw_row, $model) {

    // Add tooltip to company name (assuming it's at index 1)
    if (isset($formatted_row[1])) {
        $formatted_row[1] = sprintf(
            '<span data-toggle="tooltip" title="%s">%s</span>',
            esc_attr($raw_row->description ?? ''),
            $formatted_row[1]
        );
    }

    // Add icon based on status (assuming status is at index 5)
    if (isset($formatted_row[5])) {
        $icons = [
            'active' => '<i class="fa fa-check-circle text-success"></i>',
            'inactive' => '<i class="fa fa-times-circle text-danger"></i>',
        ];

        $status = $raw_row->status;
        $icon = $icons[$status] ?? '';

        $formatted_row[5] = $icon . ' ' . $formatted_row[5];
    }

    // Conditionally show/hide action buttons based on permissions
    if (isset($formatted_row[7])) {
        if (!current_user_can('edit_customers')) {
            // Remove edit button
            $formatted_row[7] = preg_replace(
                '/<a[^>]*btn-primary[^>]*>.*?<\/a>/',
                '',
                $formatted_row[7]
            );
        }
    }

    return $formatted_row;
}
```

---

## Module Organization

### Recommended Structure

```
wp-customer/
├── src/
│   ├── Models/
│   │   └── Customer/
│   │       ├── CustomerDataTableModel.php
│   │       └── CustomerModel.php
│   ├── Controllers/
│   │   └── CustomerController.php
│   ├── Views/
│   │   └── customers/
│   │       ├── list.php              # DataTable view
│   │       ├── view.php              # Single customer view
│   │       └── edit.php              # Edit form
│   └── Filters/
│       └── CustomerDataTableFilters.php  # Optional: if hooking into others
└── assets/
    ├── js/
    │   └── customer-datatable.js     # DataTable-specific JS
    └── css/
        └── customer-datatable.css    # DataTable-specific CSS
```

---

## Filter Priority Best Practices

### Understanding Priority

```php
add_filter('hook_name', 'callback', PRIORITY, NUM_ARGS);
```

- **Lower number** = Executes earlier
- **Higher number** = Executes later
- **Default** = 10

### Recommended Priorities

```php
// 5 - Core/Base filters (set defaults)
add_filter('wpapp_datatable_customers_where', 'set_defaults', 5);

// 10 - Module business logic (default priority)
add_filter('wpapp_datatable_customers_where', 'customer_logic', 10);
add_filter('wpapp_datatable_customers_where', 'agency_logic', 10);

// 15 - Override/Enhancement
add_filter('wpapp_datatable_customers_where', 'enhance_filters', 15);

// 20 - Admin/Final overrides
add_filter('wpapp_datatable_customers_where', 'admin_override', 20);
```

---

## Debugging Tips

### 1. Log Filter Execution

```php
<?php
public function debug_filter($value, $request_data, $model) {
    error_log('Filter executed: ' . current_filter());
    error_log('Value: ' . print_r($value, true));
    return $value;
}
```

### 2. Inspect Final Query

```php
<?php
add_filter('wpapp_datatable_customers_query_builder', function($builder) {
    // Log the query before execution
    add_action('shutdown', function() use ($builder) {
        global $wpdb;
        error_log('Last Query: ' . $wpdb->last_query);
    });

    return $builder;
}, 999);
```

### 3. Check Hook Registration

```php
<?php
// Check if hook has callbacks
global $wp_filter;
if (isset($wp_filter['wpapp_datatable_customers_where'])) {
    error_log('Registered callbacks: ' . print_r($wp_filter['wpapp_datatable_customers_where'], true));
}
```

---

## Complete Example: Agency Module

See [Agency DataTable Example](../examples/AGENCY-EXAMPLE.md) for a complete, production-ready implementation.

---

## Next Steps

1. ✅ Created module-specific DataTable
2. 📖 Read [Filter Hooks Reference](FILTER-HOOKS.md)
3. 📝 Review [Code Examples](../examples/CODE-EXAMPLES.md)
4. 🏆 Follow [Best Practices](../BEST-PRACTICES.md)

---

**Next**: [Filter Hooks Reference](FILTER-HOOKS.md)
