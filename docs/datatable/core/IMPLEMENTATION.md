# Core Implementation Guide

## Overview

This guide covers the complete implementation of the DataTable system in `wp-app-core`. The core provides the base functionality that all module plugins will extend.

## File Reference

**Implementation Files:**
- `src/Models/DataTable/DataTableModel.php` - Base model class (abstract)
- `src/Models/DataTable/DataTableQueryBuilder.php` - SQL query builder
- `src/Controllers/DataTable/DataTableController.php` - AJAX request handler
- `src/Controllers/DataTable/DataTableAssetsController.php` - Asset enqueue management

**View Templates:**
- `src/Views/DataTable/Templates/DashboardTemplate.php` - Main dashboard container
- `src/Views/DataTable/Templates/PanelLayoutTemplate.php` - Left/right panel layout
- `src/Views/DataTable/Templates/TabSystemTemplate.php` - WordPress-style tabs
- `src/Views/DataTable/Templates/StatsBoxTemplate.php` - Statistics cards

**Assets:**
- `assets/css/datatable/wpapp-datatable.css` - Global panel styles
- `assets/js/datatable/wpapp-panel-manager.js` - Panel open/close, AJAX handling
- `assets/js/datatable/wpapp-tab-manager.js` - Tab switching functionality

---

## Directory Structure

```
wp-app-core/
├── src/
│   ├── Models/
│   │   └── DataTable/
│   │       ├── DataTableModel.php          # Base model
│   │       └── DataTableQueryBuilder.php   # Query builder
│   ├── Controllers/
│   │   └── DataTable/
│   │       └── DataTableController.php     # AJAX controller
│   └── Views/
│       └── datatable/
│           ├── base-template.php           # HTML template
│           └── scripts.js                  # JS initialization
└── docs/
    └── datatable/
        └── core/
            └── IMPLEMENTATION.md           # This file
```

---

## 1. DataTableModel (Base Class)

### File: `src/Models/DataTable/DataTableModel.php`

```php
<?php
/**
 * Base DataTable Model
 *
 * Provides core functionality for server-side DataTables processing
 * inspired by Perfex CRM pattern.
 *
 * @package WPAppCore
 * @subpackage Models\DataTable
 * @since 1.0.0
 */

namespace WPAppCore\Models\DataTable;

use WPAppCore\Models\DataTable\DataTableQueryBuilder;

abstract class DataTableModel {

    /**
     * Database table name (without prefix)
     * Child classes MUST set this
     *
     * @var string
     */
    protected $table;

    /**
     * Columns to select
     * Child classes MUST set this
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Searchable columns (for DataTables search)
     *
     * @var array
     */
    protected $searchable_columns = [];

    /**
     * Primary key column name
     *
     * @var string
     */
    protected $index_column = 'id';

    /**
     * Custom WHERE conditions (static)
     * Can be set by child classes
     *
     * @var array
     */
    protected $base_where = [];

    /**
     * Custom JOINs
     * Can be set by child classes
     *
     * @var array
     */
    protected $base_joins = [];

    /**
     * WordPress database instance
     *
     * @var \wpdb
     */
    protected $wpdb;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Main method for server-side DataTables processing
     *
     * This is the core method that handles all DataTables requests.
     * It applies filters at multiple points to allow module extensions.
     *
     * @param array $request_data DataTables request parameters from $_POST
     * @return array DataTables response format
     */
    public function get_datatable_data($request_data) {

        // 1. Get columns (allow modules to modify)
        $columns = $this->get_columns();
        $columns = apply_filters(
            $this->get_filter_hook('columns'),
            $columns,
            $this,
            $request_data
        );

        // 2. Initialize query builder
        $query_builder = new DataTableQueryBuilder($this->table);
        $query_builder->set_columns($columns);
        $query_builder->set_searchable_columns($this->searchable_columns);
        $query_builder->set_index_column($this->index_column);

        // 3. Build WHERE conditions
        $where_conditions = $this->base_where;

        // Allow modules to add WHERE conditions
        $where_conditions = apply_filters(
            $this->get_filter_hook('where'),
            $where_conditions,
            $request_data,
            $this
        );

        $query_builder->set_where_conditions($where_conditions);

        // 4. Build JOINs
        $joins = $this->base_joins;

        // Allow modules to add JOINs
        $joins = apply_filters(
            $this->get_filter_hook('joins'),
            $joins,
            $request_data,
            $this
        );

        $query_builder->set_joins($joins);

        // 5. Apply search from DataTables
        if (!empty($request_data['search']['value'])) {
            $query_builder->set_search_value($request_data['search']['value']);
        }

        // 6. Apply ordering
        if (isset($request_data['order'])) {
            $order_column_index = $request_data['order'][0]['column'];
            $order_dir = $request_data['order'][0]['dir'];
            $order_column = $columns[$order_column_index];

            $query_builder->set_ordering($order_column, $order_dir);
        }

        // 7. Apply pagination
        $start = isset($request_data['start']) ? intval($request_data['start']) : 0;
        $length = isset($request_data['length']) ? intval($request_data['length']) : 10;

        $query_builder->set_pagination($start, $length);

        // 8. Allow modules to modify entire query before execution
        $query_builder = apply_filters(
            $this->get_filter_hook('query_builder'),
            $query_builder,
            $request_data,
            $this
        );

        // 9. Execute query and get results
        $results = $query_builder->get_results();

        // 10. Count totals for pagination
        $total_records = $query_builder->count_total();
        $filtered_records = $query_builder->count_filtered();

        // 11. Format each row
        $output_data = [];
        foreach ($results as $row) {
            $formatted_row = $this->format_row($row);

            // Allow modules to modify row data
            $formatted_row = apply_filters(
                $this->get_filter_hook('row_data'),
                $formatted_row,
                $row,
                $this
            );

            $output_data[] = $formatted_row;
        }

        // 12. Build DataTables response
        $response = [
            'draw' => isset($request_data['draw']) ? intval($request_data['draw']) : 0,
            'recordsTotal' => $total_records,
            'recordsFiltered' => $filtered_records,
            'data' => $output_data
        ];

        // 13. Allow modules to modify final response
        $response = apply_filters(
            $this->get_filter_hook('response'),
            $response,
            $this
        );

        return $response;
    }

    /**
     * Get columns
     * Can be overridden by child classes for dynamic columns
     *
     * @return array
     */
    protected function get_columns() {
        return $this->columns;
    }

    /**
     * Format a single row for output
     * Child classes SHOULD override this
     *
     * @param object $row Database row object
     * @return array Formatted row data
     */
    protected function format_row($row) {
        // Default: return all columns as array
        $output = [];
        foreach ($this->columns as $column) {
            // Remove table prefix if present (e.g., "table.column" -> "column")
            $col_name = strpos($column, '.') !== false
                ? substr($column, strpos($column, '.') + 1)
                : $column;

            $output[] = isset($row->$col_name) ? $row->$col_name : '';
        }
        return $output;
    }

    /**
     * Generate filter hook name
     *
     * Format: wpapp_datatable_{table}_{type}
     * Example: wpapp_datatable_customers_where
     *
     * @param string $type Hook type (columns, where, joins, row_data, etc.)
     * @return string Full hook name
     */
    protected function get_filter_hook($type) {
        // Remove prefix from table name
        $table_name = str_replace($this->wpdb->prefix, '', $this->table);
        return "wpapp_datatable_{$table_name}_{$type}";
    }

    /**
     * Get table name with prefix
     *
     * @return string
     */
    public function get_table() {
        return $this->table;
    }
}
```

### Key Features:

1. **Multiple Filter Hooks**: Provides hooks at every stage
2. **Flexible Column Definition**: Columns can be modified by filters
3. **Extensible WHERE/JOIN**: Modules can inject conditions
4. **Row Formatting**: Override in child classes for custom output
5. **Query Builder Integration**: Separates query logic

---

## 2. DataTableQueryBuilder

### File: `src/Models/DataTable/DataTableQueryBuilder.php`

```php
<?php
/**
 * DataTable Query Builder
 *
 * Handles SQL query building for DataTables with proper escaping
 * and WordPress $wpdb integration.
 *
 * @package WPAppCore
 * @subpackage Models\DataTable
 * @since 1.0.0
 */

namespace WPAppCore\Models\DataTable;

class DataTableQueryBuilder {

    /**
     * @var string Table name
     */
    private $table;

    /**
     * @var array Columns to select
     */
    private $columns = [];

    /**
     * @var array Searchable columns
     */
    private $searchable_columns = [];

    /**
     * @var string Index column (primary key)
     */
    private $index_column = 'id';

    /**
     * @var array WHERE conditions
     */
    private $where_conditions = [];

    /**
     * @var array JOIN clauses
     */
    private $joins = [];

    /**
     * @var string Search value
     */
    private $search_value = '';

    /**
     * @var string Order column
     */
    private $order_column = '';

    /**
     * @var string Order direction (ASC/DESC)
     */
    private $order_dir = 'ASC';

    /**
     * @var int Pagination start
     */
    private $limit_start = 0;

    /**
     * @var int Pagination length
     */
    private $limit_length = 10;

    /**
     * @var \wpdb WordPress database instance
     */
    private $wpdb;

    /**
     * Constructor
     *
     * @param string $table Table name
     */
    public function __construct($table) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $table;
    }

    /**
     * Set columns
     *
     * @param array $columns
     * @return self
     */
    public function set_columns($columns) {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Set searchable columns
     *
     * @param array $searchable_columns
     * @return self
     */
    public function set_searchable_columns($searchable_columns) {
        $this->searchable_columns = $searchable_columns;
        return $this;
    }

    /**
     * Set index column
     *
     * @param string $index_column
     * @return self
     */
    public function set_index_column($index_column) {
        $this->index_column = $index_column;
        return $this;
    }

    /**
     * Set WHERE conditions
     *
     * @param array $conditions Array of SQL WHERE conditions
     * @return self
     */
    public function set_where_conditions($conditions) {
        $this->where_conditions = $conditions;
        return $this;
    }

    /**
     * Set JOINs
     *
     * @param array $joins Array of SQL JOIN clauses
     * @return self
     */
    public function set_joins($joins) {
        $this->joins = $joins;
        return $this;
    }

    /**
     * Set search value
     *
     * @param string $search_value
     * @return self
     */
    public function set_search_value($search_value) {
        $this->search_value = $search_value;
        return $this;
    }

    /**
     * Set ordering
     *
     * @param string $column Column to order by
     * @param string $dir Direction (ASC/DESC)
     * @return self
     */
    public function set_ordering($column, $dir = 'ASC') {
        $this->order_column = $column;
        $this->order_dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        return $this;
    }

    /**
     * Set pagination
     *
     * @param int $start Starting record
     * @param int $length Number of records
     * @return self
     */
    public function set_pagination($start, $length) {
        $this->limit_start = max(0, intval($start));
        $this->limit_length = max(1, intval($length));
        return $this;
    }

    /**
     * Build SELECT clause
     *
     * @return string
     */
    private function build_select() {
        $select = implode(', ', $this->columns);
        return "SELECT {$select}";
    }

    /**
     * Build FROM clause with JOINs
     *
     * @return string
     */
    private function build_from() {
        $from = "FROM {$this->table}";

        if (!empty($this->joins)) {
            $from .= ' ' . implode(' ', $this->joins);
        }

        return $from;
    }

    /**
     * Build WHERE clause
     *
     * @return string
     */
    private function build_where() {
        $where_parts = [];

        // Add base WHERE conditions
        if (!empty($this->where_conditions)) {
            $where_parts = array_merge($where_parts, $this->where_conditions);
        }

        // Add search conditions
        if (!empty($this->search_value) && !empty($this->searchable_columns)) {
            $search_parts = [];
            $search_value = '%' . $this->wpdb->esc_like($this->search_value) . '%';

            foreach ($this->searchable_columns as $column) {
                $search_parts[] = $this->wpdb->prepare("{$column} LIKE %s", $search_value);
            }

            if (!empty($search_parts)) {
                $where_parts[] = '(' . implode(' OR ', $search_parts) . ')';
            }
        }

        if (empty($where_parts)) {
            return 'WHERE 1=1';
        }

        return 'WHERE ' . implode(' AND ', $where_parts);
    }

    /**
     * Build ORDER BY clause
     *
     * @return string
     */
    private function build_order() {
        if (empty($this->order_column)) {
            return "ORDER BY {$this->index_column} DESC";
        }

        return "ORDER BY {$this->order_column} {$this->order_dir}";
    }

    /**
     * Build LIMIT clause
     *
     * @return string
     */
    private function build_limit() {
        return $this->wpdb->prepare("LIMIT %d, %d", $this->limit_start, $this->limit_length);
    }

    /**
     * Build complete query
     *
     * @param bool $with_limit Include LIMIT clause
     * @return string
     */
    private function build_query($with_limit = true) {
        $query = $this->build_select() . ' ' .
                 $this->build_from() . ' ' .
                 $this->build_where() . ' ' .
                 $this->build_order();

        if ($with_limit) {
            $query .= ' ' . $this->build_limit();
        }

        return $query;
    }

    /**
     * Execute query and get results
     *
     * @return array Array of row objects
     */
    public function get_results() {
        $query = $this->build_query(true);
        return $this->wpdb->get_results($query);
    }

    /**
     * Count total records (without filters)
     *
     * @return int
     */
    public function count_total() {
        $query = "SELECT COUNT({$this->index_column}) as total FROM {$this->table}";

        // Add JOINs if needed for count
        if (!empty($this->joins)) {
            $query .= ' ' . implode(' ', $this->joins);
        }

        // Add base WHERE conditions (but not search)
        if (!empty($this->where_conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $this->where_conditions);
        }

        $result = $this->wpdb->get_var($query);
        return intval($result);
    }

    /**
     * Count filtered records (with search applied)
     *
     * @return int
     */
    public function count_filtered() {
        $query = "SELECT COUNT({$this->index_column}) as total " .
                 $this->build_from() . ' ' .
                 $this->build_where();

        $result = $this->wpdb->get_var($query);
        return intval($result);
    }
}
```

### Key Features:

1. **SQL Injection Protection**: Uses `$wpdb->prepare()` and `$wpdb->esc_like()`
2. **Flexible Query Building**: Supports WHERE, JOIN, ORDER, LIMIT
3. **Search Functionality**: Handles DataTables search across columns
4. **Count Methods**: Separate counts for total and filtered records
5. **Fluent Interface**: Chainable methods for easy configuration

---

## 3. DataTableController

### File: `src/Controllers/DataTable/DataTableController.php`

```php
<?php
/**
 * DataTable Controller
 *
 * Handles AJAX requests for DataTables with security checks.
 *
 * @package WPAppCore
 * @subpackage Controllers\DataTable
 * @since 1.0.0
 */

namespace WPAppCore\Controllers\DataTable;

class DataTableController {

    /**
     * Handle AJAX request for DataTable
     *
     * @param string $model_class Fully qualified model class name
     * @return void (sends JSON response)
     */
    public function handle_ajax_request($model_class) {

        // 1. Validate AJAX request
        if (!wp_doing_ajax()) {
            wp_send_json_error([
                'message' => __('Invalid request', 'wp-app-core')
            ]);
        }

        // 2. Verify nonce
        if (!check_ajax_referer('wpapp_datatable_nonce', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Security check failed', 'wp-app-core')
            ]);
        }

        // 3. Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error([
                'message' => __('You must be logged in', 'wp-app-core')
            ]);
        }

        // 4. Check permissions (allow modules to override)
        $can_access = apply_filters(
            'wpapp_datatable_can_access',
            current_user_can('read'),
            $model_class
        );

        if (!$can_access) {
            wp_send_json_error([
                'message' => __('Permission denied', 'wp-app-core')
            ]);
        }

        // 5. Validate model class exists
        if (!class_exists($model_class)) {
            wp_send_json_error([
                'message' => __('Invalid model class', 'wp-app-core')
            ]);
        }

        try {
            // 6. Instantiate model
            $model = new $model_class();

            // 7. Get DataTable data
            $data = $model->get_datatable_data($_POST);

            // 8. Allow modules to modify final output
            $data = apply_filters('wpapp_datatable_output', $data, $model_class);

            // 9. Send success response
            wp_send_json_success($data);

        } catch (\Exception $e) {
            // Log error
            error_log('DataTable Error: ' . $e->getMessage());

            // Send error response
            wp_send_json_error([
                'message' => __('An error occurred while loading data', 'wp-app-core'),
                'debug' => WP_DEBUG ? $e->getMessage() : null
            ]);
        }
    }

    /**
     * Register AJAX action
     *
     * Helper method to register DataTable AJAX handlers
     *
     * @param string $action Action name (without 'wp_ajax_' prefix)
     * @param string $model_class Model class name
     * @return void
     */
    public static function register_ajax_action($action, $model_class) {
        add_action("wp_ajax_{$action}", function() use ($model_class) {
            $controller = new self();
            $controller->handle_ajax_request($model_class);
        });
    }
}
```

### Key Features:

1. **Security Checks**: Nonce, login, permissions
2. **Error Handling**: Try-catch with logging
3. **Filter Integration**: Permission and output filters
4. **Helper Method**: Easy AJAX action registration

---

## 4. Registration & Initialization

### File: `src/init.php` or main plugin file

```php
<?php
/**
 * Initialize DataTable System
 */

// Autoload classes (if not using Composer)
spl_autoload_register(function ($class) {
    $prefix = 'WPAppCore\\';
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Register nonce for AJAX
add_action('admin_enqueue_scripts', function() {
    wp_localize_script('jquery', 'wpapp_datatable', [
        'nonce' => wp_create_nonce('wpapp_datatable_nonce'),
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
});
```

---

## Usage in Core

### Example: Create a Staff DataTable

```php
<?php
namespace WPAppCore\Models\Staff;

use WPAppCore\Models\DataTable\DataTableModel;

class StaffDataTableModel extends DataTableModel {

    public function __construct() {
        parent::__construct();

        $this->table = $this->wpdb->prefix . 'app_staff';
        $this->columns = [
            'id',
            'user_id',
            'full_name',
            'email',
            'role',
            'status',
            'created_at'
        ];
        $this->searchable_columns = ['full_name', 'email'];
        $this->index_column = 'id';
    }

    protected function format_row($row) {
        return [
            $row->id,
            $row->user_id,
            '<a href="' . admin_url('admin.php?page=staff&id=' . $row->id) . '">' .
                esc_html($row->full_name) .
            '</a>',
            esc_html($row->email),
            esc_html($row->role),
            $this->get_status_badge($row->status),
            date_i18n(get_option('date_format'), strtotime($row->created_at))
        ];
    }

    private function get_status_badge($status) {
        $badges = [
            'active' => '<span class="badge badge-success">Active</span>',
            'inactive' => '<span class="badge badge-secondary">Inactive</span>',
        ];
        return $badges[$status] ?? $status;
    }
}

// Register AJAX handler
use WPAppCore\Controllers\DataTable\DataTableController;

DataTableController::register_ajax_action(
    'staff_datatable',
    'WPAppCore\\Models\\Staff\\StaffDataTableModel'
);
```

---

## Testing Core Implementation

### Unit Test Example

```php
<?php
use PHPUnit\Framework\TestCase;
use WPAppCore\Models\DataTable\DataTableQueryBuilder;

class DataTableQueryBuilderTest extends TestCase {

    public function test_build_query_with_search() {
        $builder = new DataTableQueryBuilder('wp_customers');
        $builder->set_columns(['id', 'name', 'email'])
                ->set_searchable_columns(['name', 'email'])
                ->set_search_value('john')
                ->set_pagination(0, 10);

        $results = $builder->get_results();

        $this->assertIsArray($results);
    }
}
```

---

## Next Steps

1. ✅ Core implementation complete
2. 📖 Read [Module Extension Guide](../modules/EXTENSION-GUIDE.md)
3. 🔌 Implement in wp-customer or wp-agency
4. 📝 Review [Examples](../examples/CODE-EXAMPLES.md)

---

**Next**: [Module Extension Guide](../modules/EXTENSION-GUIDE.md)
