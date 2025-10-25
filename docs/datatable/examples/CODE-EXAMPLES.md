# Code Examples & Use Cases

Real-world examples of DataTable implementations and common use cases.

## File Reference

**Core Files for Examples:**
- `src/Models/DataTable/DataTableModel.php` - Base class to extend
- `src/Controllers/DataTable/DataTableController.php` - AJAX handling
- `src/Models/Platform/PlatformStaffDataTableModel.php` - Reference implementation

**Related Documentation:**
- `docs/datatable/core/IMPLEMENTATION.md` - Implementation details
- `docs/datatable/modules/EXTENSION-GUIDE.md` - Extension patterns
- `docs/datatable/api/HOOKS.md` - Available filters

---

## Table of Contents

1. [Basic Customer DataTable](#basic-customer-datatable)
2. [Advanced Filtering](#advanced-filtering)
3. [Multi-Module Integration](#multi-module-integration)
4. [Complex JOINs](#complex-joins)
5. [Permission-Based Filtering](#permission-based-filtering)
6. [Export Functionality](#export-functionality)
7. [Custom Actions](#custom-actions)
8. [Real-Time Updates](#real-time-updates)

---

## Basic Customer DataTable

### Complete Implementation

#### Model

```php
<?php
namespace WPCustomer\Models;

use WPAppCore\Models\DataTable\DataTableModel;

class CustomerDataTableModel extends DataTableModel {

    public function __construct() {
        parent::__construct();

        $this->table = $this->wpdb->prefix . 'customers';
        $this->columns = ['id', 'company_name', 'email', 'phone', 'status', 'created_at'];
        $this->searchable_columns = ['company_name', 'email', 'phone'];
        $this->index_column = 'id';
    }

    protected function format_row($row) {
        return [
            $row->id,
            sprintf(
                '<a href="%s">%s</a>',
                admin_url('admin.php?page=view-customer&id=' . $row->id),
                esc_html($row->company_name)
            ),
            esc_html($row->email),
            esc_html($row->phone),
            $this->get_status_badge($row->status),
            date_i18n('Y-m-d', strtotime($row->created_at)),
            $this->get_actions($row)
        ];
    }

    private function get_status_badge($status) {
        $badges = [
            'active' => '<span class="badge bg-success">Active</span>',
            'inactive' => '<span class="badge bg-secondary">Inactive</span>',
        ];
        return $badges[$status] ?? $status;
    }

    private function get_actions($row) {
        return sprintf(
            '<a href="%s" class="btn btn-sm btn-primary">Edit</a> ' .
            '<a href="#" class="btn btn-sm btn-danger delete-customer" data-id="%d">Delete</a>',
            admin_url('admin.php?page=edit-customer&id=' . $row->id),
            $row->id
        );
    }
}
```

#### Controller

```php
<?php
namespace WPCustomer\Controllers;

use WPAppCore\Controllers\DataTable\DataTableController;
use WPCustomer\Models\CustomerDataTableModel;

DataTableController::register_ajax_action(
    'customer_datatable',
    CustomerDataTableModel::class
);
```

#### View

```php
<!-- wp-customer/src/Views/customers/list.php -->
<div class="wrap">
    <h1>Customers</h1>

    <table id="customers-table" class="display" style="width:100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Company</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    $('#customers-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'customer_datatable',
                nonce: '<?php echo wp_create_nonce('wpapp_datatable_nonce'); ?>'
            }
        },
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6, orderable: false }
        ]
    });
});
</script>
```

---

## Advanced Filtering

### Multi-Filter System with UI

#### Filter Class

```php
<?php
namespace WPCustomer\Filters;

class CustomerAdvancedFilters {

    public function __construct() {
        add_filter('wpapp_datatable_customers_where', [$this, 'apply_filters'], 10, 3);
    }

    public function apply_filters($where, $request, $model) {

        // Status filter
        if (isset($_GET['filter_status']) && $_GET['filter_status'] !== '') {
            $where[] = "status = '" . esc_sql($_GET['filter_status']) . "'";
        }

        // Date range filter
        if (!empty($_GET['filter_date_from'])) {
            global $wpdb;
            $where[] = $wpdb->prepare(
                "DATE(created_at) >= %s",
                sanitize_text_field($_GET['filter_date_from'])
            );
        }

        if (!empty($_GET['filter_date_to'])) {
            global $wpdb;
            $where[] = $wpdb->prepare(
                "DATE(created_at) <= %s",
                sanitize_text_field($_GET['filter_date_to'])
            );
        }

        // Multiple status filter (comma-separated)
        if (!empty($_GET['filter_statuses'])) {
            $statuses = array_map('esc_sql', explode(',', $_GET['filter_statuses']));
            $where[] = "status IN ('" . implode("','", $statuses) . "')";
        }

        // Search by custom field
        if (!empty($_GET['filter_industry'])) {
            $where[] = "industry = '" . esc_sql($_GET['filter_industry']) . "'";
        }

        // Numeric range (e.g., employee count)
        if (!empty($_GET['filter_employees_min'])) {
            $where[] = "employee_count >= " . intval($_GET['filter_employees_min']);
        }

        if (!empty($_GET['filter_employees_max'])) {
            $where[] = "employee_count <= " . intval($_GET['filter_employees_max']);
        }

        return $where;
    }
}

new CustomerAdvancedFilters();
```

#### Filter UI

```php
<!-- Filter Panel -->
<div class="datatable-filters card mb-3">
    <div class="card-body">
        <div class="row">
            <!-- Status Filter -->
            <div class="col-md-3">
                <label>Status</label>
                <select id="filter-status" class="form-control">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="pending">Pending</option>
                </select>
            </div>

            <!-- Date Range -->
            <div class="col-md-3">
                <label>Date From</label>
                <input type="date" id="filter-date-from" class="form-control">
            </div>

            <div class="col-md-3">
                <label>Date To</label>
                <input type="date" id="filter-date-to" class="form-control">
            </div>

            <!-- Industry Filter -->
            <div class="col-md-3">
                <label>Industry</label>
                <select id="filter-industry" class="form-control">
                    <option value="">All Industries</option>
                    <option value="tech">Technology</option>
                    <option value="finance">Finance</option>
                    <option value="retail">Retail</option>
                </select>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-12">
                <button id="apply-filters" class="btn btn-primary">Apply Filters</button>
                <button id="reset-filters" class="btn btn-secondary">Reset</button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var table = $('#customers-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: ajaxurl,
            type: 'POST',
            data: function(d) {
                d.action = 'customer_datatable';
                d.nonce = wpapp_datatable.nonce;
                d.filter_status = $('#filter-status').val();
                d.filter_date_from = $('#filter-date-from').val();
                d.filter_date_to = $('#filter-date-to').val();
                d.filter_industry = $('#filter-industry').val();
            }
        },
        columns: [/* ... */]
    });

    // Apply filters
    $('#apply-filters').on('click', function() {
        table.ajax.reload();
    });

    // Reset filters
    $('#reset-filters').on('click', function() {
        $('#filter-status').val('');
        $('#filter-date-from').val('');
        $('#filter-date-to').val('');
        $('#filter-industry').val('');
        table.ajax.reload();
    });

    // Live filter on change
    $('.form-control').on('change', function() {
        table.ajax.reload();
    });
});
</script>
```

---

## Multi-Module Integration

### Scenario: wp-agency adds columns to wp-customer DataTable

#### Agency Filter Class

```php
<?php
namespace WPAgency\Filters;

class AgencyCustomerIntegration {

    public function __construct() {
        // Add agency column
        add_filter('wpapp_datatable_customers_columns', [$this, 'add_agency_column'], 10, 3);

        // Add agency JOIN
        add_filter('wpapp_datatable_customers_joins', [$this, 'add_agency_join'], 10, 3);

        // Filter by agency
        add_filter('wpapp_datatable_customers_where', [$this, 'filter_by_agency'], 10, 3);

        // Add agency info to row
        add_filter('wpapp_datatable_customers_row_data', [$this, 'add_agency_to_row'], 10, 3);
    }

    public function add_agency_column($columns, $model, $request) {
        // Insert agency name after company name (position 1)
        array_splice($columns, 2, 0, ['agencies.name as agency_name']);
        return $columns;
    }

    public function add_agency_join($joins, $request, $model) {
        global $wpdb;
        $joins[] = "LEFT JOIN {$wpdb->prefix}agencies agencies ON agencies.id = {$model->get_table()}.agency_id";
        return $joins;
    }

    public function filter_by_agency($where, $request, $model) {
        // If user is agency staff, only show their customers
        if ($this->is_agency_staff()) {
            $agency_id = $this->get_user_agency_id();
            if ($agency_id) {
                $where[] = "{$model->get_table()}.agency_id = " . intval($agency_id);
            }
        }

        // URL filter
        if (isset($_GET['agency_id'])) {
            $where[] = "{$model->get_table()}.agency_id = " . intval($_GET['agency_id']);
        }

        return $where;
    }

    public function add_agency_to_row($formatted_row, $raw_row, $model) {
        // Insert agency link after company name (position 2)
        $agency_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=view-agency&id=' . ($raw_row->agency_id ?? 0)),
            esc_html($raw_row->agency_name ?? 'No Agency')
        );

        array_splice($formatted_row, 2, 0, [$agency_link]);

        return $formatted_row;
    }

    private function is_agency_staff() {
        return current_user_can('agency_staff');
    }

    private function get_user_agency_id() {
        return get_user_meta(get_current_user_id(), 'agency_id', true);
    }
}

new AgencyCustomerIntegration();
```

---

## Complex JOINs

### Multiple Tables with Subqueries

```php
<?php
namespace WPCustomer\Models;

use WPAppCore\Models\DataTable\DataTableModel;

class CustomerAdvancedDataTableModel extends DataTableModel {

    public function __construct() {
        parent::__construct();

        $this->table = $this->wpdb->prefix . 'customers';

        // Complex columns with subqueries
        $this->columns = [
            $this->table . '.id',
            $this->table . '.company_name',
            $this->table . '.email',
            'u.display_name as user_name',
            'a.name as agency_name',
            // Subquery for order count
            '(SELECT COUNT(*) FROM ' . $this->wpdb->prefix . 'orders o WHERE o.customer_id = ' . $this->table . '.id) as order_count',
            // Subquery for total revenue
            '(SELECT COALESCE(SUM(total), 0) FROM ' . $this->wpdb->prefix . 'orders o WHERE o.customer_id = ' . $this->table . '.id) as total_revenue',
            $this->table . '.created_at'
        ];

        // Multiple JOINs
        $this->base_joins = [
            "LEFT JOIN {$this->wpdb->prefix}users u ON u.ID = {$this->table}.user_id",
            "LEFT JOIN {$this->wpdb->prefix}agencies a ON a.id = {$this->table}.agency_id"
        ];

        $this->searchable_columns = [
            $this->table . '.company_name',
            $this->table . '.email',
            'u.display_name'
        ];
    }

    protected function format_row($row) {
        return [
            $row->id,
            esc_html($row->company_name),
            esc_html($row->email),
            esc_html($row->user_name ?? '-'),
            esc_html($row->agency_name ?? '-'),
            number_format($row->order_count),
            '$' . number_format($row->total_revenue, 2),
            date_i18n('Y-m-d', strtotime($row->created_at))
        ];
    }
}
```

---

## Permission-Based Filtering

### Role-Based Data Access

```php
<?php
namespace WPCustomer\Filters;

class CustomerPermissionFilters {

    public function __construct() {
        add_filter('wpapp_datatable_customers_where', [$this, 'apply_permission_filters'], 5, 3);
        add_filter('wpapp_datatable_can_access', [$this, 'check_access'], 10, 2);
    }

    /**
     * Apply WHERE filters based on user role
     */
    public function apply_permission_filters($where, $request, $model) {
        $user = wp_get_current_user();

        // Admins see everything
        if (current_user_can('manage_options')) {
            return $where;
        }

        // Agency managers see only their agency's customers
        if (in_array('agency_manager', $user->roles)) {
            $agency_id = get_user_meta(get_current_user_id(), 'agency_id', true);
            if ($agency_id) {
                $where[] = "agency_id = " . intval($agency_id);
            }
        }

        // Sales reps see only their assigned customers
        if (in_array('sales_rep', $user->roles)) {
            $where[] = "assigned_to = " . get_current_user_id();
        }

        // Customer role sees only their own record
        if (in_array('customer', $user->roles)) {
            $customer_id = get_user_meta(get_current_user_id(), 'customer_id', true);
            if ($customer_id) {
                $where[] = "id = " . intval($customer_id);
            }
        }

        return $where;
    }

    /**
     * Check if user can access DataTable
     */
    public function check_access($can_access, $model_class) {
        if ($model_class !== 'WPCustomer\\Models\\CustomerDataTableModel') {
            return $can_access;
        }

        // Check custom capability
        return current_user_can('view_customers');
    }
}

new CustomerPermissionFilters();
```

---

## Export Functionality

### Add Export Button

```php
<?php
namespace WPCustomer\Controllers;

class CustomerExportController {

    public function __construct() {
        add_action('wp_ajax_export_customers', [$this, 'export_csv']);
    }

    public function export_csv() {
        // Security checks
        check_ajax_referer('wpapp_export_nonce', 'nonce');

        if (!current_user_can('export_customers')) {
            wp_die('Permission denied');
        }

        global $wpdb;

        // Get filtered data (respect DataTable filters)
        $where = ['1=1'];

        // Apply same filters as DataTable
        $where = apply_filters('wpapp_datatable_customers_where', $where, $_POST, null);

        $query = "SELECT * FROM {$wpdb->prefix}customers WHERE " . implode(' AND ', $where);
        $results = $wpdb->get_results($query);

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="customers-' . date('Y-m-d') . '.csv"');

        // Output CSV
        $output = fopen('php://output', 'w');

        // Header row
        fputcsv($output, ['ID', 'Company', 'Email', 'Phone', 'Status', 'Created']);

        // Data rows
        foreach ($results as $row) {
            fputcsv($output, [
                $row->id,
                $row->company_name,
                $row->email,
                $row->phone,
                $row->status,
                $row->created_at
            ]);
        }

        fclose($output);
        exit;
    }
}

new CustomerExportController();
```

#### Export Button UI

```html
<button id="export-customers" class="btn btn-success">
    <i class="fa fa-download"></i> Export to CSV
</button>

<script>
jQuery('#export-customers').on('click', function() {
    // Build URL with current filters
    var params = new URLSearchParams({
        action: 'export_customers',
        nonce: wpapp_export_nonce,
        filter_status: jQuery('#filter-status').val(),
        // ... other filters
    });

    window.location.href = ajaxurl + '?' + params.toString();
});
</script>
```

---

## Custom Actions

### Bulk Actions

```php
<!-- Add bulk action dropdown -->
<select id="bulk-action">
    <option value="">Bulk Actions</option>
    <option value="activate">Activate</option>
    <option value="deactivate">Deactivate</option>
    <option value="delete">Delete</option>
</select>
<button id="apply-bulk-action" class="btn btn-primary">Apply</button>

<script>
jQuery(document).ready(function($) {
    var table = $('#customers-table').DataTable({
        // ... config
        columns: [
            {
                data: 0,
                render: function(data) {
                    return '<input type="checkbox" class="row-select" value="' + data + '">';
                }
            },
            // ... other columns
        ]
    });

    // Apply bulk action
    $('#apply-bulk-action').on('click', function() {
        var action = $('#bulk-action').val();
        var selected = [];

        $('.row-select:checked').each(function() {
            selected.push($(this).val());
        });

        if (action && selected.length > 0) {
            $.post(ajaxurl, {
                action: 'bulk_action_customers',
                bulk_action: action,
                ids: selected,
                nonce: wpapp_datatable.nonce
            }, function(response) {
                if (response.success) {
                    table.ajax.reload();
                    alert('Bulk action completed');
                }
            });
        }
    });
});
</script>
```

---

## Real-Time Updates

### Auto-Refresh DataTable

```javascript
jQuery(document).ready(function($) {
    var table = $('#customers-table').DataTable({
        // ... config
    });

    // Auto-refresh every 30 seconds
    setInterval(function() {
        table.ajax.reload(null, false); // false = stay on current page
    }, 30000);

    // Refresh on custom event
    $(document).on('customer-updated', function() {
        table.ajax.reload();
    });
});
```

### WebSocket Integration (Advanced)

```javascript
// Connect to WebSocket server
var ws = new WebSocket('ws://your-server.com:8080');

ws.onmessage = function(event) {
    var data = JSON.parse(event.data);

    if (data.type === 'customer_updated') {
        // Reload specific row or entire table
        $('#customers-table').DataTable().ajax.reload(null, false);
    }
};
```

---

## Summary

These examples cover:

✅ Basic implementation
✅ Advanced filtering with UI
✅ Multi-module integration
✅ Complex queries with JOINs
✅ Permission-based access
✅ Export functionality
✅ Bulk actions
✅ Real-time updates

---

**Next**: [Best Practices](../BEST-PRACTICES.md)
