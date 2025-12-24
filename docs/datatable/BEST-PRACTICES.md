# DataTable Best Practices

Guidelines and best practices for building maintainable, performant, and secure DataTables.

## File Reference

**Core Files to Follow:**
- `src/Models/DataTable/DataTableModel.php` - Security patterns in base model
- `src/Controllers/DataTable/DataTableController.php` - AJAX security validation
- `src/Models/Platform/PlatformStaffDataTableModel.php` - Best practice example

**Related Documentation:**
- `docs/datatable/ARCHITECTURE.md` - System architecture
- `docs/datatable/api/HOOKS.md` - Available filters
- `docs/datatable/modules/EXTENSION-GUIDE.md` - Extension patterns

---

## Table of Contents

1. [Security](#security)
2. [Performance](#performance)
3. [Code Organization](#code-organization)
4. [Query Optimization](#query-optimization)
5. [UI/UX](#ui-ux)
6. [Error Handling](#error-handling)
7. [Testing](#testing)
8. [Documentation](#documentation)

---

## Security

### 1. Always Sanitize User Input

```php
// ❌ BAD - SQL Injection vulnerability
$where[] = "status = '{$_GET['status']}'";

// ✅ GOOD - Use esc_sql()
$where[] = "status = '" . esc_sql($_GET['status']) . "'";

// ✅ BETTER - Use prepared statements
global $wpdb;
$where[] = $wpdb->prepare("status = %s", $_GET['status']);
```

### 2. Validate Nonce

```php
// Always verify nonce in AJAX handlers
check_ajax_referer('wpapp_datatable_nonce', 'nonce');
```

### 3. Check Permissions

```php
// Check user capabilities
if (!current_user_can('view_customers')) {
    wp_send_json_error('Permission denied');
}

// Use filter for custom permission logic
add_filter('wpapp_datatable_can_access', function($can_access, $model_class) {
    if ($model_class === CustomerDataTableModel::class) {
        return current_user_can('view_customers');
    }
    return $can_access;
}, 10, 2);
```

### 4. Escape Output

```php
// Always escape HTML output
protected function format_row($row) {
    return [
        $row->id,
        esc_html($row->name),  // ✅ Escaped
        esc_url($row->website), // ✅ URL escaped
        esc_attr($row->title)   // ✅ Attribute escaped
    ];
}
```

### 5. Validate Data Types

```php
// Validate and cast data types
$agency_id = isset($_GET['agency_id']) ? intval($_GET['agency_id']) : 0;

if ($agency_id > 0) {
    $where[] = "agency_id = {$agency_id}";
}
```

### 6. Limit Data Exposure

```php
// Don't expose sensitive data
protected function format_row($row) {
    return [
        $row->id,
        $row->name,
        // ❌ Don't expose password hashes, API keys, etc.
        // $row->password_hash,
        // $row->api_secret,
    ];
}

// Filter sensitive columns based on permissions
protected function get_columns() {
    $columns = ['id', 'name', 'email'];

    if (current_user_can('view_sensitive_data')) {
        $columns[] = 'ssn';
        $columns[] = 'salary';
    }

    return $columns;
}
```

---

## Performance

### 1. Use Indexes

```sql
-- Add indexes for columns used in WHERE, JOIN, ORDER BY
ALTER TABLE wp_customers ADD INDEX idx_status (status);
ALTER TABLE wp_customers ADD INDEX idx_agency_id (agency_id);
ALTER TABLE wp_customers ADD INDEX idx_created_at (created_at);

-- Composite index for common filter combinations
ALTER TABLE wp_customers ADD INDEX idx_status_agency (status, agency_id);
```

### 2. Limit Columns

```php
// ❌ BAD - Select all columns
$this->columns = ['*'];

// ✅ GOOD - Select only needed columns
$this->columns = ['id', 'name', 'email', 'status'];
```

### 3. Avoid N+1 Queries

```php
// ❌ BAD - Queries in loop
protected function format_row($row) {
    // This runs a query for EACH row!
    $order_count = $wpdb->get_var("SELECT COUNT(*) FROM wp_orders WHERE customer_id = {$row->id}");
    return [$row->id, $row->name, $order_count];
}

// ✅ GOOD - Use subquery or JOIN
public function __construct() {
    $this->columns = [
        'id',
        'name',
        '(SELECT COUNT(*) FROM wp_orders WHERE customer_id = wp_customers.id) as order_count'
    ];
}

protected function format_row($row) {
    return [$row->id, $row->name, $row->order_count];
}
```

### 4. Cache Heavy Queries

```php
public function count_total() {
    $cache_key = 'customers_total_count';
    $total = wp_cache_get($cache_key);

    if (false === $total) {
        $total = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");
        wp_cache_set($cache_key, $total, '', HOUR_IN_SECONDS);
    }

    return intval($total);
}
```

### 5. Optimize Subqueries

```php
// ❌ BAD - Multiple separate subqueries
$this->columns = [
    '(SELECT COUNT(*) FROM wp_orders WHERE customer_id = wp_customers.id)',
    '(SELECT SUM(total) FROM wp_orders WHERE customer_id = wp_customers.id)',
    '(SELECT MAX(created_at) FROM wp_orders WHERE customer_id = wp_customers.id)'
];

// ✅ BETTER - Use JOIN with aggregation
$this->base_joins = [
    "LEFT JOIN (
        SELECT
            customer_id,
            COUNT(*) as order_count,
            SUM(total) as total_revenue,
            MAX(created_at) as last_order_date
        FROM wp_orders
        GROUP BY customer_id
    ) orders ON orders.customer_id = wp_customers.id"
];

$this->columns = [
    'wp_customers.id',
    'wp_customers.name',
    'COALESCE(orders.order_count, 0) as order_count',
    'COALESCE(orders.total_revenue, 0) as total_revenue',
    'orders.last_order_date'
];
```

### 6. Pagination Limits

```php
// Set reasonable pagination limits
public function get_datatable_data($request_data) {
    // Limit max records per page
    $length = isset($request_data['length']) ? intval($request_data['length']) : 10;
    $length = min($length, 100); // Max 100 records per page

    // ... rest of code
}
```

---

## Code Organization

### 1. Separation of Concerns

```
✅ GOOD Structure:

wp-customer/
├── src/
│   ├── Models/
│   │   └── Customer/
│   │       ├── CustomerModel.php           # Business logic
│   │       └── CustomerDataTableModel.php  # DataTable logic
│   ├── Controllers/
│   │   └── CustomerController.php          # Request handling
│   ├── Views/
│   │   └── customers/
│   │       └── list.php                    # UI
│   └── Filters/
│       └── CustomerFilters.php             # Hook handlers
```

### 2. Single Responsibility

```php
// ✅ GOOD - Each class has one responsibility

// Model: Handle data retrieval and formatting
class CustomerDataTableModel extends DataTableModel {
    // Only DataTable-specific logic
}

// Controller: Handle requests
class CustomerController {
    // Only request/response logic
}

// Filter: Handle hooks
class CustomerFilters {
    // Only filter logic
}
```

### 3. DRY (Don't Repeat Yourself)

```php
// ✅ GOOD - Reusable methods
class CustomerDataTableModel extends DataTableModel {

    protected function format_row($row) {
        return [
            $row->id,
            $this->format_company_link($row),
            $this->format_status_badge($row->status),
            $this->format_actions($row)
        ];
    }

    private function format_company_link($row) {
        return sprintf(
            '<a href="%s">%s</a>',
            $this->get_view_url($row->id),
            esc_html($row->company_name)
        );
    }

    private function format_status_badge($status) {
        $badges = [
            'active' => 'success',
            'inactive' => 'secondary',
        ];
        $class = $badges[$status] ?? 'secondary';
        return "<span class='badge bg-{$class}'>" . esc_html($status) . "</span>";
    }

    private function format_actions($row) {
        // Reusable action buttons
    }

    private function get_view_url($id) {
        return admin_url('admin.php?page=view-customer&id=' . $id);
    }
}
```

### 4. Naming Conventions

```php
// Use clear, descriptive names

// ✅ GOOD
class CustomerDataTableModel
public function get_datatable_data()
protected function format_row()
private function get_status_badge()

// ❌ BAD
class CDT
public function getData()
protected function fmt()
private function badge()
```

---

## Query Optimization

### 1. Use EXPLAIN

```php
// Debug query performance
add_filter('wpapp_datatable_customers_query_builder', function($builder) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        add_action('shutdown', function() {
            global $wpdb;
            error_log('Query: ' . $wpdb->last_query);

            // Run EXPLAIN
            $explain = $wpdb->get_results('EXPLAIN ' . $wpdb->last_query);
            error_log('EXPLAIN: ' . print_r($explain, true));
        });
    }
    return $builder;
}, 999);
```

### 2. Avoid OR in WHERE

```php
// ❌ SLOW - OR conditions
$where[] = "(status = 'active' OR status = 'pending')";

// ✅ FASTER - Use IN
$where[] = "status IN ('active', 'pending')";
```

### 3. Use Proper JOIN Types

```php
// INNER JOIN - Only matching records (faster)
$joins[] = "INNER JOIN wp_agencies ON wp_agencies.id = agency_id";

// LEFT JOIN - All from left, matching from right (slower)
$joins[] = "LEFT JOIN wp_agencies ON wp_agencies.id = agency_id";

// Use INNER JOIN when relationship is required
// Use LEFT JOIN when relationship is optional
```

### 4. Limit Searchable Columns

```php
// ❌ BAD - Too many searchable columns
$this->searchable_columns = ['id', 'name', 'email', 'phone', 'address', 'city', 'state', 'zip', 'notes'];

// ✅ GOOD - Only essential columns
$this->searchable_columns = ['name', 'email'];
```

---

## UI/UX

### 1. Loading States

```javascript
$('#customers-table').DataTable({
    processing: true,  // Show loading indicator
    language: {
        processing: '<i class="fa fa-spinner fa-spin"></i> Loading...'
    }
});
```

### 2. User-Friendly Messages

```javascript
language: {
    emptyTable: 'No customers found',
    info: 'Showing _START_ to _END_ of _TOTAL_ customers',
    infoEmpty: 'No customers to display',
    infoFiltered: '(filtered from _MAX_ total customers)',
    search: 'Search customers:',
    zeroRecords: 'No matching customers found'
}
```

### 3. Responsive Design

```javascript
$('#customers-table').DataTable({
    responsive: true,  // Enable responsive mode
    // OR use scrollX for horizontal scroll
    scrollX: true
});
```

### 4. Save State

```javascript
$('#customers-table').DataTable({
    stateSave: true,  // Remember pagination, sorting, filters
    stateDuration: 60 * 60  // 1 hour
});
```

### 5. Default Sorting

```javascript
$('#customers-table').DataTable({
    order: [[0, 'desc']],  // Sort by first column descending (newest first)
});
```

---

## Error Handling

### 1. Try-Catch in Controllers

```php
public function handle_ajax_request($model_class) {
    try {
        // Security checks
        if (!wp_doing_ajax()) {
            throw new \Exception('Invalid request');
        }

        check_ajax_referer('wpapp_datatable_nonce', 'nonce');

        // Process request
        $model = new $model_class();
        $data = $model->get_datatable_data($_POST);

        wp_send_json_success($data);

    } catch (\Exception $e) {
        // Log error
        error_log('DataTable Error: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());

        // User-friendly error
        wp_send_json_error([
            'message' => __('An error occurred. Please try again.', 'wp-app-core'),
            'debug' => WP_DEBUG ? $e->getMessage() : null
        ]);
    }
}
```

### 2. Validate Data Exists

```php
protected function format_row($row) {
    return [
        $row->id ?? 0,
        esc_html($row->name ?? ''),
        esc_html($row->email ?? 'N/A'),
        // Use null coalescing operator
    ];
}
```

### 3. Handle AJAX Errors

```javascript
$('#customers-table').DataTable({
    ajax: {
        url: ajaxurl,
        error: function(xhr, error, thrown) {
            console.error('DataTable error:', error, thrown);
            alert('Failed to load data. Please refresh the page.');
        }
    }
});
```

---

## Testing

### 1. Unit Tests

```php
use PHPUnit\Framework\TestCase;

class CustomerDataTableModelTest extends TestCase {

    public function test_format_row() {
        $model = new CustomerDataTableModel();

        $row = (object)[
            'id' => 1,
            'name' => 'Test Company',
            'email' => 'test@example.com',
            'status' => 'active'
        ];

        $formatted = $model->format_row($row);

        $this->assertIsArray($formatted);
        $this->assertEquals(1, $formatted[0]);
        $this->assertStringContainsString('Test Company', $formatted[1]);
    }

    public function test_filter_by_status() {
        $_GET['status'] = 'active';

        $where = [];
        $where = apply_filters('wpapp_datatable_customers_where', $where, [], null);

        $this->assertContains("status = 'active'", $where);
    }
}
```

### 2. Integration Tests

```php
public function test_datatable_ajax_request() {
    // Simulate AJAX request
    $_POST['action'] = 'customer_datatable';
    $_POST['draw'] = 1;
    $_POST['start'] = 0;
    $_POST['length'] = 10;

    // Mock nonce
    wp_set_current_user(1); // Admin user

    ob_start();
    do_action('wp_ajax_customer_datatable');
    $response = ob_get_clean();

    $data = json_decode($response, true);

    $this->assertArrayHasKey('data', $data);
    $this->assertArrayHasKey('recordsTotal', $data);
}
```

### 3. Manual Testing Checklist

- [ ] Search functionality works
- [ ] Sorting works for all sortable columns
- [ ] Pagination works correctly
- [ ] Filters apply correctly
- [ ] Actions (edit, delete) work
- [ ] Permissions respected
- [ ] No console errors
- [ ] Responsive on mobile
- [ ] Works with no data
- [ ] Works with large datasets (10,000+ rows)

---

## Documentation

### 1. Document Custom Hooks

```php
/**
 * Filter customer DataTable WHERE conditions
 *
 * @param array $where Current WHERE conditions
 * @param array $request_data DataTables request data
 * @param CustomerDataTableModel $model Model instance
 * @return array Modified WHERE conditions
 *
 * @example
 * add_filter('wpapp_datatable_customers_where', function($where) {
 *     $where[] = "status = 'active'";
 *     return $where;
 * });
 */
$where = apply_filters('wpapp_datatable_customers_where', $where, $request_data, $this);
```

### 2. Add Inline Comments

```php
protected function format_row($row) {
    return [
        // Column 0: ID
        $row->id,

        // Column 1: Company name with link to view page
        sprintf('<a href="%s">%s</a>',
            admin_url('admin.php?page=view-customer&id=' . $row->id),
            esc_html($row->company_name)
        ),

        // Column 2: Status badge (color-coded)
        $this->get_status_badge($row->status),
    ];
}
```

### 3. Maintain README

Keep module README updated with:
- DataTable features
- Available filters
- Custom columns
- Usage examples

---

## Common Pitfalls to Avoid

### 1. ❌ Forgetting to Return Filtered Value

```php
// ❌ BAD
add_filter('wpapp_datatable_customers_where', function($where) {
    $where[] = "status = 'active'";
    // Missing return!
});

// ✅ GOOD
add_filter('wpapp_datatable_customers_where', function($where) {
    $where[] = "status = 'active'";
    return $where;  // Don't forget!
});
```

### 2. ❌ SQL Injection

```php
// ❌ BAD
$where[] = "status = '{$_GET['status']}'";

// ✅ GOOD
$where[] = "status = '" . esc_sql($_GET['status']) . "'";
```

### 3. ❌ Not Using Table Prefix

```php
// ❌ BAD
$this->table = 'customers';

// ✅ GOOD
$this->table = $this->wpdb->prefix . 'customers';
```

### 4. ❌ Heavy Processing in format_row()

```php
// ❌ BAD - API call for each row!
protected function format_row($row) {
    $external_data = file_get_contents('https://api.example.com/data/' . $row->id);
    // ...
}

// ✅ GOOD - Pre-fetch or cache
```

### 5. ❌ Not Escaping Output

```php
// ❌ BAD - XSS vulnerability
return [$row->id, $row->name];

// ✅ GOOD
return [$row->id, esc_html($row->name)];
```

---

## Performance Checklist

- [ ] Indexes on WHERE columns
- [ ] Indexes on JOIN columns
- [ ] Indexes on ORDER BY columns
- [ ] No SELECT *
- [ ] No N+1 queries
- [ ] Reasonable searchable columns
- [ ] Cache expensive queries
- [ ] Limit max page size
- [ ] Use EXPLAIN to analyze queries
- [ ] Monitor slow query log

---

## Security Checklist

- [ ] Nonce verification
- [ ] Permission checks
- [ ] Input sanitization (esc_sql, intval, sanitize_text_field)
- [ ] Output escaping (esc_html, esc_url, esc_attr)
- [ ] No direct $_GET/$_POST in queries
- [ ] Prepared statements for dynamic values
- [ ] Validate data types
- [ ] Limit data exposure
- [ ] Log security events
- [ ] Test with different user roles

---

## Maintenance Checklist

- [ ] Code documented
- [ ] Unit tests written
- [ ] Integration tests written
- [ ] README updated
- [ ] Changelog maintained
- [ ] Performance tested with large dataset
- [ ] Security tested
- [ ] Cross-browser tested
- [ ] Mobile tested
- [ ] Backwards compatibility considered

---

## Summary

Following these best practices will result in:

✅ **Secure** - Protected against SQL injection, XSS, and unauthorized access
✅ **Performant** - Fast queries even with large datasets
✅ **Maintainable** - Clean, organized, well-documented code
✅ **Scalable** - Can handle growth in data and features
✅ **User-Friendly** - Good UX with proper feedback and error handling
✅ **Testable** - Easy to write tests and verify functionality

---

**Remember**: Security and performance should never be afterthoughts!

---

## Resources

- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [DataTables Documentation](https://datatables.net/manual/)
- [MySQL Performance Tuning](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)
- [OWASP Security Guidelines](https://owasp.org/www-project-web-security-testing-guide/)

---

**End of Documentation** 🎉
