# Filter Hooks Reference

Complete reference for all DataTable filter hooks.

## File Reference

**Core Implementation:**
- `src/Models/DataTable/DataTableModel.php` - Contains all hook execution points
- `src/Models/DataTable/DataTableQueryBuilder.php` - Uses filtered columns, joins, where

**Filter Examples:**
- `src/Tests/DataTable/PlatformStaffDataTableFilters.php` - Example filter class
- Module plugins (wp-customer, wp-agency) - Real filter implementations

---

## Hook Naming Convention

All hooks follow this pattern:

```
wpapp_datatable_{table}_{type}
```

Where:
- `{table}` = table name without wp_ prefix (e.g., `customers`, `agencies`)
- `{type}` = hook type (e.g., `columns`, `where`, `joins`, `row_data`)

**Example:** `wpapp_datatable_customers_where`

---

## Available Hooks

### 1. Columns Hook

**Hook Name:** `wpapp_datatable_{table}_columns`

**Purpose:** Modify or add columns to SELECT clause

**Parameters:**
1. `$columns` (array) - Current columns
2. `$model` (DataTableModel) - Model instance
3. `$request_data` (array) - DataTables request

**Return:** (array) Modified columns

**Example:**

```php
add_filter('wpapp_datatable_customers_columns', function($columns, $model, $request) {
    // Add subquery column
    $columns[] = '(SELECT COUNT(*) FROM wp_orders WHERE customer_id = wp_customers.id) as order_count';

    // Add joined column
    $columns[] = 'users.display_name as user_name';

    return $columns;
}, 10, 3);
```

**Use Cases:**
- Add calculated columns
- Add columns from joined tables
- Conditionally add columns based on permissions
- Add subquery results

---

### 2. WHERE Hook

**Hook Name:** `wpapp_datatable_{table}_where`

**Purpose:** Add WHERE conditions to filter records

**Parameters:**
1. `$where` (array) - Current WHERE conditions
2. `$request_data` (array) - DataTables request
3. `$model` (DataTableModel) - Model instance

**Return:** (array) Modified WHERE conditions

**Example:**

```php
add_filter('wpapp_datatable_customers_where', function($where, $request, $model) {
    // Filter by status from URL
    if (isset($_GET['status'])) {
        $where[] = "status = '" . esc_sql($_GET['status']) . "'";
    }

    // Filter by current user
    if (!current_user_can('view_all_customers')) {
        $user_id = get_current_user_id();
        $where[] = "assigned_to = {$user_id}";
    }

    // Filter by date range
    if (isset($_GET['date_from']) && isset($_GET['date_to'])) {
        global $wpdb;
        $where[] = $wpdb->prepare(
            "created_at BETWEEN %s AND %s",
            sanitize_text_field($_GET['date_from']),
            sanitize_text_field($_GET['date_to'])
        );
    }

    return $where;
}, 10, 3);
```

**Use Cases:**
- Filter by URL parameters
- Apply permission-based filtering
- Filter by date ranges
- Apply business logic filters
- Show only user's own records

**Security Note:** Always sanitize user input!

```php
// ❌ BAD - SQL Injection risk
$where[] = "status = '{$_GET['status']}'";

// ✅ GOOD - Sanitized
$where[] = "status = '" . esc_sql($_GET['status']) . "'";

// ✅ BEST - Prepared statement
global $wpdb;
$where[] = $wpdb->prepare("status = %s", $_GET['status']);
```

---

### 3. JOINs Hook

**Hook Name:** `wpapp_datatable_{table}_joins`

**Purpose:** Add JOIN clauses to query

**Parameters:**
1. `$joins` (array) - Current JOIN clauses
2. `$request_data` (array) - DataTables request
3. `$model` (DataTableModel) - Model instance

**Return:** (array) Modified JOIN clauses

**Example:**

```php
add_filter('wpapp_datatable_customers_joins', function($joins, $request, $model) {
    global $wpdb;

    // Join users table
    $joins[] = "LEFT JOIN {$wpdb->prefix}users ON {$wpdb->prefix}users.ID = user_id";

    // Join agencies table
    $joins[] = "LEFT JOIN {$wpdb->prefix}agencies ON {$wpdb->prefix}agencies.id = agency_id";

    // Conditional join
    if (isset($_GET['include_stats'])) {
        $joins[] = "LEFT JOIN {$wpdb->prefix}customer_stats ON customer_stats.customer_id = wp_customers.id";
    }

    return $joins;
}, 10, 3);
```

**Join Types:**
- `INNER JOIN` - Only matching records
- `LEFT JOIN` - All from left table, matching from right
- `RIGHT JOIN` - All from right table, matching from left
- `CROSS JOIN` - Cartesian product (rarely used)

**Best Practices:**
- Use table aliases for readability
- Always use `{$wpdb->prefix}` for table names
- Consider performance impact of joins
- Use LEFT JOIN for optional relationships

---

### 4. Query Builder Hook

**Hook Name:** `wpapp_datatable_{table}_query_builder`

**Purpose:** Modify query builder before execution

**Parameters:**
1. `$builder` (DataTableQueryBuilder) - Query builder instance
2. `$request_data` (array) - DataTables request
3. `$model` (DataTableModel) - Model instance

**Return:** (DataTableQueryBuilder) Modified builder

**Example:**

```php
add_filter('wpapp_datatable_customers_query_builder', function($builder, $request, $model) {
    // You can call any builder method here

    // Force specific ordering
    $builder->set_ordering('created_at', 'DESC');

    // Add additional WHERE
    $builder->set_where_conditions(array_merge(
        $builder->get_where_conditions(),
        ["is_deleted = 0"]
    ));

    return $builder;
}, 10, 3);
```

**Use Cases:**
- Override default ordering
- Add complex query modifications
- Debug query building
- Completely customize query logic

**Note:** This is an advanced hook. Use specific hooks (where, joins, columns) when possible.

---

### 5. Row Data Hook

**Hook Name:** `wpapp_datatable_{table}_row_data`

**Purpose:** Modify formatted row output

**Parameters:**
1. `$formatted_row` (array) - Formatted row data
2. `$raw_row` (object) - Raw database row
3. `$model` (DataTableModel) - Model instance

**Return:** (array) Modified row data

**Example:**

```php
add_filter('wpapp_datatable_customers_row_data', function($formatted_row, $raw_row, $model) {
    // Add custom column at end
    $formatted_row[] = '<button class="custom-action" data-id="' . $raw_row->id . '">Action</button>';

    // Modify existing column (company name at index 1)
    if (isset($formatted_row[1])) {
        $formatted_row[1] = '<strong>' . $formatted_row[1] . '</strong>';
    }

    // Add icon based on status
    if ($raw_row->is_vip) {
        $formatted_row[1] = '<i class="fa fa-star"></i> ' . $formatted_row[1];
    }

    // Conditional formatting
    if ($raw_row->total_orders > 100) {
        $formatted_row[0] = '<span class="text-success">' . $formatted_row[0] . '</span>';
    }

    return $formatted_row;
}, 10, 3);
```

**Use Cases:**
- Add action buttons
- Modify cell display
- Add icons or badges
- Apply conditional formatting
- Add tooltips
- Insert additional data

**Tips:**
- Use `$raw_row` to access any database column
- Use `$formatted_row` array indices carefully
- Consider column order
- Escape HTML output

---

### 6. Response Hook

**Hook Name:** `wpapp_datatable_{table}_response`

**Purpose:** Modify entire response before sending

**Parameters:**
1. `$response` (array) - DataTables response
2. `$model` (DataTableModel) - Model instance

**Return:** (array) Modified response

**Response Structure:**
```php
[
    'draw' => 1,
    'recordsTotal' => 1000,
    'recordsFiltered' => 50,
    'data' => [...]
]
```

**Example:**

```php
add_filter('wpapp_datatable_customers_response', function($response, $model) {
    // Add custom metadata
    $response['metadata'] = [
        'total_value' => calculate_total_customer_value(),
        'active_count' => count_active_customers(),
        'timestamp' => current_time('mysql')
    ];

    // Add summary row
    $response['summary'] = [
        'Total Customers' => $response['recordsTotal'],
        'Filtered' => $response['recordsFiltered']
    ];

    // Log for debugging
    if (WP_DEBUG) {
        error_log('DataTable Response: ' . print_r($response, true));
    }

    return $response;
}, 10, 2);
```

**Use Cases:**
- Add custom metadata
- Add summary data
- Log responses for debugging
- Add aggregated statistics
- Add footer totals

---

### 7. Permission Hook

**Hook Name:** `wpapp_datatable_can_access`

**Purpose:** Control access to DataTable

**Parameters:**
1. `$can_access` (bool) - Current access status
2. `$model_class` (string) - Model class name

**Return:** (bool) Access allowed

**Example:**

```php
add_filter('wpapp_datatable_can_access', function($can_access, $model_class) {
    // Allow access to customers datatable
    if ($model_class === 'WPCustomer\\Models\\Customer\\CustomerDataTableModel') {
        return current_user_can('view_customers');
    }

    // Allow access to agencies datatable
    if ($model_class === 'WPAgency\\Models\\Agency\\AgencyDataTableModel') {
        return current_user_can('view_agencies');
    }

    // Check for specific role
    $user = wp_get_current_user();
    if (in_array('agency_manager', $user->roles)) {
        return true;
    }

    // Default
    return $can_access;
}, 10, 2);
```

**Use Cases:**
- Permission checks by capability
- Role-based access control
- Module-specific permissions
- Custom authentication logic

**Security Best Practices:**
- Always check permissions
- Use WordPress capabilities when possible
- Return false by default for unknown models
- Log permission denials for security auditing

---

### 8. Output Hook

**Hook Name:** `wpapp_datatable_output`

**Purpose:** Final modification before JSON response

**Parameters:**
1. `$data` (array) - Complete response data
2. `$model_class` (string) - Model class name

**Return:** (array) Modified data

**Example:**

```php
add_filter('wpapp_datatable_output', function($data, $model_class) {
    // Add timestamp to all responses
    $data['server_time'] = current_time('mysql');

    // Add user info
    $data['current_user'] = [
        'id' => get_current_user_id(),
        'name' => wp_get_current_user()->display_name
    ];

    // Log for debugging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("DataTable output for {$model_class}");
    }

    return $data;
}, 10, 2);
```

**Use Cases:**
- Add global metadata
- Log all DataTable responses
- Add user context
- Add application state

---

## Hook Priority Guidelines

### Priority Values

```php
add_filter('hook_name', 'callback', PRIORITY, NUM_ARGS);
```

- `1-4`: Very early (system defaults)
- `5-9`: Early (core logic)
- `10`: Default (most filters)
- `11-19`: Late (overrides)
- `20+`: Very late (final overrides)

### Recommended Usage

```php
// Core/System defaults
add_filter('wpapp_datatable_customers_where', 'system_defaults', 5);

// Module business logic
add_filter('wpapp_datatable_customers_where', 'customer_filters', 10);
add_filter('wpapp_datatable_customers_where', 'agency_filters', 10);

// Enhancements
add_filter('wpapp_datatable_customers_where', 'add_features', 15);

// Admin overrides
add_filter('wpapp_datatable_customers_where', 'admin_override', 20);
```

---

## Hook Execution Order

When multiple filters are registered:

```php
// Filter 1 (priority 10)
add_filter('wpapp_datatable_customers_where', function($where) {
    $where[] = "status = 'active'";
    return $where;
}, 10);

// Filter 2 (priority 10)
add_filter('wpapp_datatable_customers_where', function($where) {
    $where[] = "agency_id = 5";
    return $where;
}, 10);

// Filter 3 (priority 20)
add_filter('wpapp_datatable_customers_where', function($where) {
    $where[] = "is_vip = 1";
    return $where;
}, 20);

// Result:
// $where = [
//     "status = 'active'",
//     "agency_id = 5",
//     "is_vip = 1"
// ]
```

**Execution Order:**
1. Priority 10, Filter 1
2. Priority 10, Filter 2
3. Priority 20, Filter 3

---

## Debugging Hooks

### Check if Hook Has Callbacks

```php
global $wp_filter;
if (isset($wp_filter['wpapp_datatable_customers_where'])) {
    $callbacks = $wp_filter['wpapp_datatable_customers_where'];
    error_log(print_r($callbacks, true));
}
```

### Log Filter Execution

```php
add_filter('wpapp_datatable_customers_where', function($where, $request, $model) {
    error_log('WHERE filter executed');
    error_log('Current WHERE: ' . print_r($where, true));
    error_log('Request: ' . print_r($request, true));

    // Your logic here

    return $where;
}, 10, 3);
```

### Test Filter Output

```php
// Simulate filter
$test_where = ['status = "active"'];
$result = apply_filters('wpapp_datatable_customers_where', $test_where, [], null);
var_dump($result);
```

---

## Common Patterns

### Pattern 1: Conditional Column

```php
add_filter('wpapp_datatable_customers_columns', function($columns) {
    if (current_user_can('view_revenue')) {
        $columns[] = 'total_revenue';
    }
    return $columns;
});
```

### Pattern 2: Multi-Value Filter

```php
add_filter('wpapp_datatable_customers_where', function($where) {
    if (isset($_GET['statuses'])) {
        $statuses = array_map('esc_sql', explode(',', $_GET['statuses']));
        $where[] = "status IN ('" . implode("','", $statuses) . "')";
    }
    return $where;
});
```

### Pattern 3: Date Range Filter

```php
add_filter('wpapp_datatable_customers_where', function($where) {
    if (!empty($_GET['date_from'])) {
        global $wpdb;
        $where[] = $wpdb->prepare("created_at >= %s", sanitize_text_field($_GET['date_from']));
    }
    if (!empty($_GET['date_to'])) {
        global $wpdb;
        $where[] = $wpdb->prepare("created_at <= %s", sanitize_text_field($_GET['date_to']));
    }
    return $where;
});
```

### Pattern 4: User-Based Filter

```php
add_filter('wpapp_datatable_customers_where', function($where) {
    // Agency staff only see their agency's customers
    if (current_user_can('agency_staff')) {
        $agency_id = get_user_meta(get_current_user_id(), 'agency_id', true);
        if ($agency_id) {
            $where[] = "agency_id = " . intval($agency_id);
        }
    }
    return $where;
});
```

### Pattern 5: Add Action Buttons

```php
add_filter('wpapp_datatable_customers_row_data', function($row, $raw) {
    $actions = '';

    if (current_user_can('edit_customers')) {
        $actions .= '<a href="edit.php?id=' . $raw->id . '">Edit</a> ';
    }

    if (current_user_can('delete_customers')) {
        $actions .= '<a href="#" class="delete" data-id="' . $raw->id . '">Delete</a>';
    }

    $row[] = $actions;
    return $row;
}, 10, 2);
```

---

## Hook Best Practices

1. **Always return the value**: Don't forget to return the modified value
2. **Sanitize input**: Use `esc_sql()`, `intval()`, `$wpdb->prepare()`
3. **Check permissions**: Verify user capabilities
4. **Use correct priority**: Default is 10, adjust as needed
5. **Accept all parameters**: Even if you don't use them
6. **Document your hooks**: Add comments explaining what the filter does
7. **Test thoroughly**: Ensure your filter doesn't break existing functionality
8. **Handle edge cases**: Check for empty values, null, etc.

---

**Next**: [Code Examples](../examples/CODE-EXAMPLES.md)
