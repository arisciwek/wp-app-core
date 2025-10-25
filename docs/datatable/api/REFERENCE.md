# DataTable API Reference

Complete API documentation for the DataTable system.

## File Reference

**Core Implementation:**
- `src/Models/DataTable/DataTableModel.php` - Base model class
- `src/Models/DataTable/DataTableQueryBuilder.php` - Query builder class
- `src/Controllers/DataTable/DataTableController.php` - AJAX controller

**Example Implementation:**
- `src/Models/Platform/PlatformStaffDataTableModel.php` - Reference model

**Related Documentation:**
- `docs/datatable/api/HOOKS.md` - Filter hooks reference
- `docs/datatable/core/IMPLEMENTATION.md` - Implementation guide

---

## Table of Contents

1. [DataTableModel](#datatablemodel)
2. [DataTableQueryBuilder](#datatablequerybuilder)
3. [DataTableController](#datatablecontroller)
4. [Filter Hooks](#filter-hooks)

---

## DataTableModel

Base class for all DataTable models.

### Properties

#### `protected string $table`

Database table name (with or without prefix).

```php
$this->table = $this->wpdb->prefix . 'customers';
```

#### `protected array $columns`

Array of column names to select.

```php
$this->columns = ['id', 'name', 'email', 'status'];
```

#### `protected array $searchable_columns`

Array of columns that should be searchable via DataTables search.

```php
$this->searchable_columns = ['name', 'email'];
```

#### `protected string $index_column`

Primary key column name. Default: `'id'`

```php
$this->index_column = 'customer_id';
```

#### `protected array $base_where`

Static WHERE conditions that always apply.

```php
$this->base_where = [
    "deleted_at IS NULL",
    "status != 'draft'"
];
```

#### `protected array $base_joins`

Static JOIN clauses that always apply.

```php
$this->base_joins = [
    "LEFT JOIN wp_users ON wp_users.ID = user_id"
];
```

---

### Methods

#### `__construct()`

Constructor. Sets up wpdb instance.

```php
public function __construct()
```

**Usage:**

```php
public function __construct() {
    parent::__construct();

    $this->table = $this->wpdb->prefix . 'customers';
    $this->columns = ['id', 'name', 'email'];
}
```

---

#### `get_datatable_data()`

Main method for processing DataTables requests.

```php
public function get_datatable_data(array $request_data): array
```

**Parameters:**

- `$request_data` (array): DataTables request parameters (from `$_POST`)

**Returns:** (array) DataTables response format:

```php
[
    'draw' => int,
    'recordsTotal' => int,
    'recordsFiltered' => int,
    'data' => array
]
```

**Usage:**

```php
$model = new CustomerDataTableModel();
$response = $model->get_datatable_data($_POST);
wp_send_json($response);
```

---

#### `get_columns()`

Get columns to select. Can be overridden for dynamic columns.

```php
protected function get_columns(): array
```

**Returns:** (array) Column names

**Usage:**

```php
protected function get_columns() {
    $columns = parent::get_columns();

    // Add conditional column
    if (current_user_can('view_stats')) {
        $columns[] = '(SELECT COUNT(*) FROM wp_orders WHERE customer_id = id) as order_count';
    }

    return $columns;
}
```

---

#### `format_row()`

Format a single row for output. **Should be overridden** in child classes.

```php
protected function format_row(object $row): array
```

**Parameters:**

- `$row` (object): Database row object

**Returns:** (array) Formatted row data

**Usage:**

```php
protected function format_row($row) {
    return [
        $row->id,
        '<a href="view.php?id=' . $row->id . '">' . esc_html($row->name) . '</a>',
        esc_html($row->email),
        $this->get_status_badge($row->status)
    ];
}
```

---

#### `get_filter_hook()`

Generate filter hook name for this table.

```php
protected function get_filter_hook(string $type): string
```

**Parameters:**

- `$type` (string): Hook type (e.g., 'columns', 'where', 'joins', 'row_data')

**Returns:** (string) Full hook name

**Example Output:**

```php
$hook = $this->get_filter_hook('where');
// Returns: 'wpapp_datatable_customers_where'
```

---

#### `get_table()`

Get table name with prefix.

```php
public function get_table(): string
```

**Returns:** (string) Table name

---

## DataTableQueryBuilder

Builds SQL queries for DataTables.

### Methods

#### `__construct()`

```php
public function __construct(string $table)
```

**Parameters:**

- `$table` (string): Table name

---

#### `set_columns()`

Set columns to select.

```php
public function set_columns(array $columns): self
```

**Parameters:**

- `$columns` (array): Column names

**Returns:** (self) For method chaining

---

#### `set_searchable_columns()`

Set searchable columns for DataTables search.

```php
public function set_searchable_columns(array $searchable_columns): self
```

**Parameters:**

- `$searchable_columns` (array): Column names

**Returns:** (self)

---

#### `set_index_column()`

Set primary key column.

```php
public function set_index_column(string $index_column): self
```

**Parameters:**

- `$index_column` (string): Column name

**Returns:** (self)

---

#### `set_where_conditions()`

Set WHERE conditions.

```php
public function set_where_conditions(array $conditions): self
```

**Parameters:**

- `$conditions` (array): SQL WHERE conditions

**Example:**

```php
$builder->set_where_conditions([
    "status = 'active'",
    "deleted_at IS NULL"
]);
```

**Returns:** (self)

---

#### `set_joins()`

Set JOIN clauses.

```php
public function set_joins(array $joins): self
```

**Parameters:**

- `$joins` (array): SQL JOIN clauses

**Example:**

```php
$builder->set_joins([
    "LEFT JOIN wp_users ON wp_users.ID = user_id",
    "LEFT JOIN wp_agencies ON wp_agencies.id = agency_id"
]);
```

**Returns:** (self)

---

#### `set_search_value()`

Set search value for DataTables search.

```php
public function set_search_value(string $search_value): self
```

**Parameters:**

- `$search_value` (string): Search term

**Returns:** (self)

---

#### `set_ordering()`

Set ORDER BY clause.

```php
public function set_ordering(string $column, string $dir = 'ASC'): self
```

**Parameters:**

- `$column` (string): Column to order by
- `$dir` (string): Direction ('ASC' or 'DESC')

**Returns:** (self)

---

#### `set_pagination()`

Set LIMIT clause for pagination.

```php
public function set_pagination(int $start, int $length): self
```

**Parameters:**

- `$start` (int): Starting record (0-based)
- `$length` (int): Number of records

**Returns:** (self)

---

#### `get_results()`

Execute query and get results.

```php
public function get_results(): array
```

**Returns:** (array) Array of row objects

---

#### `count_total()`

Count total records (without search filter).

```php
public function count_total(): int
```

**Returns:** (int) Total record count

---

#### `count_filtered()`

Count filtered records (with search applied).

```php
public function count_filtered(): int
```

**Returns:** (int) Filtered record count

---

## DataTableController

Handles AJAX requests for DataTables.

### Methods

#### `handle_ajax_request()`

Process DataTable AJAX request.

```php
public function handle_ajax_request(string $model_class): void
```

**Parameters:**

- `$model_class` (string): Fully qualified model class name

**Returns:** void (sends JSON response)

**Security Checks:**

1. Validates AJAX request
2. Verifies nonce
3. Checks user is logged in
4. Validates permissions (filterable)
5. Validates model class exists

**Response:**

- Success: JSON with DataTable data
- Error: JSON error with message

**Usage:**

```php
$controller = new DataTableController();
$controller->handle_ajax_request(CustomerDataTableModel::class);
```

---

#### `register_ajax_action()` (static)

Helper to register AJAX action.

```php
public static function register_ajax_action(string $action, string $model_class): void
```

**Parameters:**

- `$action` (string): AJAX action name (without 'wp_ajax_' prefix)
- `$model_class` (string): Model class name

**Usage:**

```php
DataTableController::register_ajax_action(
    'customer_datatable',
    CustomerDataTableModel::class
);
```

This is equivalent to:

```php
add_action('wp_ajax_customer_datatable', function() {
    $controller = new DataTableController();
    $controller->handle_ajax_request(CustomerDataTableModel::class);
});
```

---

## Filter Hooks

### Column Filters

#### `wpapp_datatable_{table}_columns`

Modify columns to select.

**Hook:** `apply_filters('wpapp_datatable_{table}_columns', $columns, $model, $request_data)`

**Parameters:**

1. `$columns` (array): Current columns
2. `$model` (object): DataTableModel instance
3. `$request_data` (array): DataTables request data

**Returns:** (array) Modified columns

**Example:**

```php
add_filter('wpapp_datatable_customers_columns', function($columns, $model, $request) {
    $columns[] = 'custom_field';
    return $columns;
}, 10, 3);
```

---

### WHERE Filters

#### `wpapp_datatable_{table}_where`

Add WHERE conditions.

**Hook:** `apply_filters('wpapp_datatable_{table}_where', $where, $request_data, $model)`

**Parameters:**

1. `$where` (array): Current WHERE conditions
2. `$request_data` (array): DataTables request data
3. `$model` (object): DataTableModel instance

**Returns:** (array) Modified WHERE conditions

**Example:**

```php
add_filter('wpapp_datatable_customers_where', function($where, $request, $model) {
    if (isset($_GET['status'])) {
        $where[] = "status = '" . esc_sql($_GET['status']) . "'";
    }
    return $where;
}, 10, 3);
```

---

### JOIN Filters

#### `wpapp_datatable_{table}_joins`

Add JOIN clauses.

**Hook:** `apply_filters('wpapp_datatable_{table}_joins', $joins, $request_data, $model)`

**Parameters:**

1. `$joins` (array): Current JOIN clauses
2. `$request_data` (array): DataTables request data
3. `$model` (object): DataTableModel instance

**Returns:** (array) Modified JOIN clauses

**Example:**

```php
add_filter('wpapp_datatable_customers_joins', function($joins, $request, $model) {
    global $wpdb;
    $joins[] = "LEFT JOIN {$wpdb->prefix}users ON {$wpdb->prefix}users.ID = user_id";
    return $joins;
}, 10, 3);
```

---

### Query Builder Filter

#### `wpapp_datatable_{table}_query_builder`

Modify query builder before execution.

**Hook:** `apply_filters('wpapp_datatable_{table}_query_builder', $builder, $request_data, $model)`

**Parameters:**

1. `$builder` (DataTableQueryBuilder): Query builder instance
2. `$request_data` (array): DataTables request data
3. `$model` (object): DataTableModel instance

**Returns:** (DataTableQueryBuilder) Modified builder

**Example:**

```php
add_filter('wpapp_datatable_customers_query_builder', function($builder, $request, $model) {
    // Modify builder directly
    return $builder;
}, 10, 3);
```

---

### Row Data Filter

#### `wpapp_datatable_{table}_row_data`

Modify formatted row data.

**Hook:** `apply_filters('wpapp_datatable_{table}_row_data', $formatted_row, $raw_row, $model)`

**Parameters:**

1. `$formatted_row` (array): Formatted row data
2. `$raw_row` (object): Raw database row
3. `$model` (object): DataTableModel instance

**Returns:** (array) Modified row data

**Example:**

```php
add_filter('wpapp_datatable_customers_row_data', function($formatted_row, $raw_row, $model) {
    // Add custom action button
    $formatted_row[] = '<button>Custom Action</button>';
    return $formatted_row;
}, 10, 3);
```

---

### Response Filter

#### `wpapp_datatable_{table}_response`

Modify final response before sending to client.

**Hook:** `apply_filters('wpapp_datatable_{table}_response', $response, $model)`

**Parameters:**

1. `$response` (array): DataTables response
2. `$model` (object): DataTableModel instance

**Returns:** (array) Modified response

**Example:**

```php
add_filter('wpapp_datatable_customers_response', function($response, $model) {
    // Add custom metadata
    $response['custom_meta'] = ['foo' => 'bar'];
    return $response;
}, 10, 2);
```

---

### Permission Filter

#### `wpapp_datatable_can_access`

Control access to DataTable.

**Hook:** `apply_filters('wpapp_datatable_can_access', $can_access, $model_class)`

**Parameters:**

1. `$can_access` (bool): Current access status
2. `$model_class` (string): Model class name

**Returns:** (bool) Access allowed

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

### Output Filter

#### `wpapp_datatable_output`

Modify final output before JSON response.

**Hook:** `apply_filters('wpapp_datatable_output', $data, $model_class)`

**Parameters:**

1. `$data` (array): DataTables response data
2. `$model_class` (string): Model class name

**Returns:** (array) Modified data

**Example:**

```php
add_filter('wpapp_datatable_output', function($data, $model_class) {
    // Log output
    error_log('DataTable output: ' . print_r($data, true));
    return $data;
}, 10, 2);
```

---

## JavaScript API

### Initialization

```javascript
$('#table-id').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: wpapp_datatable.ajax_url,
        type: 'POST',
        data: {
            action: 'your_datatable_action',
            nonce: wpapp_datatable.nonce
        }
    },
    columns: [
        { data: 0 },
        { data: 1 },
        // ...
    ]
});
```

### Reload DataTable

```javascript
var table = $('#table-id').DataTable();
table.ajax.reload();
```

### Add Custom Filter

```javascript
$('#filter-status').on('change', function() {
    var table = $('#table-id').DataTable();

    // Pass custom parameter
    table.ajax.url(wpapp_datatable.ajax_url + '?status=' + $(this).val());
    table.ajax.reload();
});
```

---

## Data Types

### DataTables Request Format

```php
[
    'draw' => 1,              // Draw counter
    'start' => 0,             // Pagination start
    'length' => 10,           // Records per page
    'search' => [
        'value' => 'search',  // Search term
        'regex' => false
    ],
    'order' => [
        [
            'column' => 0,    // Column index
            'dir' => 'asc'    // Direction
        ]
    ],
    'columns' => [...]        // Column definitions
]
```

### DataTables Response Format

```php
[
    'draw' => 1,                    // Same as request
    'recordsTotal' => 1000,         // Total records
    'recordsFiltered' => 50,        // Filtered records
    'data' => [                     // Array of rows
        [1, 'John', 'john@email'],
        [2, 'Jane', 'jane@email']
    ]
]
```

---

**Next**: [Filter Hooks Reference](HOOKS.md)
