# Module Filter Hooks Reference

Quick reference for all available filter hooks in the DataTable system.

## File Reference

**Core Implementation:**
- `src/Models/DataTable/DataTableModel.php` - Where hooks are executed
- `src/Tests/DataTable/PlatformStaffDataTableFilters.php` - Example filter usage

**Related Documentation:**
- `docs/datatable/api/HOOKS.md` - Complete filter hooks documentation
- `docs/datatable/modules/EXTENSION-GUIDE.md` - How to use filters

---

## Hook Naming Pattern

```
wpapp_datatable_{table}_columns
wpapp_datatable_{table}_where
wpapp_datatable_{table}_joins
wpapp_datatable_{table}_query_builder
wpapp_datatable_{table}_row_data
wpapp_datatable_{table}_response
wpapp_datatable_can_access
wpapp_datatable_output
```

---

## Available Hooks by Type

### 1. Column Hooks

#### `wpapp_datatable_{table}_columns`

Modify SELECT columns.

**Signature:**
```php
apply_filters('wpapp_datatable_{table}_columns', array $columns, object $model, array $request_data)
```

**Example:**
```php
add_filter('wpapp_datatable_customers_columns', function($columns, $model, $request) {
    $columns[] = 'custom_field';
    return $columns;
}, 10, 3);
```

---

### 2. WHERE Hooks

#### `wpapp_datatable_{table}_where`

Add WHERE conditions.

**Signature:**
```php
apply_filters('wpapp_datatable_{table}_where', array $where, array $request_data, object $model)
```

**Example:**
```php
add_filter('wpapp_datatable_customers_where', function($where, $request, $model) {
    $where[] = "status = 'active'";
    return $where;
}, 10, 3);
```

---

### 3. JOIN Hooks

#### `wpapp_datatable_{table}_joins`

Add JOIN clauses.

**Signature:**
```php
apply_filters('wpapp_datatable_{table}_joins', array $joins, array $request_data, object $model)
```

**Example:**
```php
add_filter('wpapp_datatable_customers_joins', function($joins, $request, $model) {
    global $wpdb;
    $joins[] = "LEFT JOIN {$wpdb->prefix}agencies ON agencies.id = agency_id";
    return $joins;
}, 10, 3);
```

---

### 4. Query Builder Hooks

#### `wpapp_datatable_{table}_query_builder`

Modify query builder before execution.

**Signature:**
```php
apply_filters('wpapp_datatable_{table}_query_builder', DataTableQueryBuilder $builder, array $request_data, object $model)
```

**Example:**
```php
add_filter('wpapp_datatable_customers_query_builder', function($builder, $request, $model) {
    // Modify builder
    return $builder;
}, 10, 3);
```

---

### 5. Row Data Hooks

#### `wpapp_datatable_{table}_row_data`

Modify formatted row output.

**Signature:**
```php
apply_filters('wpapp_datatable_{table}_row_data', array $formatted_row, object $raw_row, object $model)
```

**Example:**
```php
add_filter('wpapp_datatable_customers_row_data', function($formatted_row, $raw_row, $model) {
    $formatted_row[] = '<button>Action</button>';
    return $formatted_row;
}, 10, 3);
```

---

### 6. Response Hooks

#### `wpapp_datatable_{table}_response`

Modify entire response.

**Signature:**
```php
apply_filters('wpapp_datatable_{table}_response', array $response, object $model)
```

**Example:**
```php
add_filter('wpapp_datatable_customers_response', function($response, $model) {
    $response['metadata'] = ['custom' => 'data'];
    return $response;
}, 10, 2);
```

---

### 7. Permission Hooks

#### `wpapp_datatable_can_access`

Control access to DataTable.

**Signature:**
```php
apply_filters('wpapp_datatable_can_access', bool $can_access, string $model_class)
```

**Example:**
```php
add_filter('wpapp_datatable_can_access', function($can_access, $model_class) {
    if ($model_class === CustomerDataTableModel::class) {
        return current_user_can('view_customers');
    }
    return $can_access;
}, 10, 2);
```

---

### 8. Output Hooks

#### `wpapp_datatable_output`

Final modification before JSON response.

**Signature:**
```php
apply_filters('wpapp_datatable_output', array $data, string $model_class)
```

**Example:**
```php
add_filter('wpapp_datatable_output', function($data, $model_class) {
    $data['timestamp'] = current_time('mysql');
    return $data;
}, 10, 2);
```

---

## Complete Example: Multi-Hook Integration

```php
<?php
namespace WPAgency\Filters;

class CustomerDataTableFilters {

    public function __construct() {
        // Register all filters
        add_filter('wpapp_datatable_customers_columns', [$this, 'add_columns'], 10, 3);
        add_filter('wpapp_datatable_customers_where', [$this, 'add_where'], 10, 3);
        add_filter('wpapp_datatable_customers_joins', [$this, 'add_joins'], 10, 3);
        add_filter('wpapp_datatable_customers_row_data', [$this, 'modify_row'], 10, 3);
        add_filter('wpapp_datatable_can_access', [$this, 'check_access'], 10, 2);
    }

    public function add_columns($columns, $model, $request) {
        $columns[] = 'agencies.name as agency_name';
        return $columns;
    }

    public function add_where($where, $request, $model) {
        if (isset($_GET['agency_id'])) {
            $where[] = "agency_id = " . intval($_GET['agency_id']);
        }
        return $where;
    }

    public function add_joins($joins, $request, $model) {
        global $wpdb;
        $joins[] = "LEFT JOIN {$wpdb->prefix}agencies agencies ON agencies.id = agency_id";
        return $joins;
    }

    public function modify_row($formatted_row, $raw_row, $model) {
        // Insert agency name after company name (position 2)
        array_splice($formatted_row, 2, 0, [esc_html($raw_row->agency_name ?? '-')]);
        return $formatted_row;
    }

    public function check_access($can_access, $model_class) {
        if ($model_class === 'WPCustomer\\Models\\CustomerDataTableModel') {
            return current_user_can('view_customers');
        }
        return $can_access;
    }
}

new CustomerDataTableFilters();
```

---

## Hook Priority Guidelines

```php
add_filter('hook_name', 'callback', PRIORITY, NUM_ARGS);
```

| Priority | Usage |
|----------|-------|
| 1-4 | System defaults |
| 5-9 | Core logic |
| 10 | Default (most filters) |
| 11-19 | Overrides |
| 20+ | Final overrides |

**Example:**
```php
// Core defaults
add_filter('wpapp_datatable_customers_where', 'set_defaults', 5);

// Module logic
add_filter('wpapp_datatable_customers_where', 'customer_filters', 10);
add_filter('wpapp_datatable_customers_where', 'agency_filters', 10);

// Admin overrides
add_filter('wpapp_datatable_customers_where', 'admin_override', 20);
```

---

## Common Patterns

### Filter by URL Parameter

```php
add_filter('wpapp_datatable_customers_where', function($where) {
    if (isset($_GET['status'])) {
        $where[] = "status = '" . esc_sql($_GET['status']) . "'";
    }
    return $where;
});
```

### Filter by User Role

```php
add_filter('wpapp_datatable_customers_where', function($where) {
    if (!current_user_can('view_all_customers')) {
        $where[] = "assigned_to = " . get_current_user_id();
    }
    return $where;
});
```

### Add Calculated Column

```php
add_filter('wpapp_datatable_customers_columns', function($columns) {
    $columns[] = '(SELECT COUNT(*) FROM wp_orders WHERE customer_id = wp_customers.id) as order_count';
    return $columns;
});
```

### Add Action Buttons

```php
add_filter('wpapp_datatable_customers_row_data', function($row, $raw) {
    $actions = sprintf(
        '<a href="edit.php?id=%d">Edit</a> | <a href="#" class="delete" data-id="%d">Delete</a>',
        $raw->id,
        $raw->id
    );
    $row[] = $actions;
    return $row;
}, 10, 2);
```

---

## Debugging Hooks

### Check if Hook is Registered

```php
global $wp_filter;
if (isset($wp_filter['wpapp_datatable_customers_where'])) {
    var_dump($wp_filter['wpapp_datatable_customers_where']);
}
```

### Log Hook Execution

```php
add_filter('wpapp_datatable_customers_where', function($where, $request, $model) {
    error_log('WHERE Hook Executed');
    error_log('Current WHERE: ' . print_r($where, true));

    // Your logic here

    return $where;
}, 10, 3);
```

---

## Hook Registry

Quick lookup table:

| Hook | Purpose | Parameters | Return |
|------|---------|------------|--------|
| `{table}_columns` | Modify columns | $columns, $model, $request | array |
| `{table}_where` | Add WHERE | $where, $request, $model | array |
| `{table}_joins` | Add JOINs | $joins, $request, $model | array |
| `{table}_query_builder` | Modify builder | $builder, $request, $model | DataTableQueryBuilder |
| `{table}_row_data` | Modify row | $formatted_row, $raw_row, $model | array |
| `{table}_response` | Modify response | $response, $model | array |
| `can_access` | Check permission | $can_access, $model_class | bool |
| `output` | Final output | $data, $model_class | array |

---

## Next Steps

1. 📖 Read [Extension Guide](EXTENSION-GUIDE.md)
2. 📝 Review [Code Examples](../examples/CODE-EXAMPLES.md)
3. 🏆 Follow [Best Practices](../BEST-PRACTICES.md)

---

**Quick Reference Card** - Print this for easy access! 📋
