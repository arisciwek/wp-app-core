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
 * @author arisciwek
 */

namespace WPAppCore\Models\DataTable;

/**
 * DataTableQueryBuilder class
 *
 * Builds optimized SQL queries for server-side DataTables processing
 * with security and performance in mind.
 *
 * @example
 * $builder = new DataTableQueryBuilder('wp_customers');
 * $builder->set_columns(['id', 'name', 'email'])
 *         ->set_searchable_columns(['name', 'email'])
 *         ->set_search_value('john')
 *         ->set_pagination(0, 10);
 * $results = $builder->get_results();
 */
class DataTableQueryBuilder {

    /**
     * Table name
     *
     * @var string
     */
    private $table;

    /**
     * Columns to select
     *
     * @var array
     */
    private $columns = [];

    /**
     * Searchable columns for DataTables search
     *
     * @var array
     */
    private $searchable_columns = [];

    /**
     * Index column (primary key)
     *
     * @var string
     */
    private $index_column = 'id';

    /**
     * WHERE conditions
     *
     * @var array
     */
    private $where_conditions = [];

    /**
     * JOIN clauses
     *
     * @var array
     */
    private $joins = [];

    /**
     * Search value from DataTables
     *
     * @var string
     */
    private $search_value = '';

    /**
     * Column to order by
     *
     * @var string
     */
    private $order_column = '';

    /**
     * Order direction (ASC/DESC)
     *
     * @var string
     */
    private $order_dir = 'ASC';

    /**
     * Pagination start (offset)
     *
     * @var int
     */
    private $limit_start = 0;

    /**
     * Pagination length (limit)
     *
     * @var int
     */
    private $limit_length = 10;

    /**
     * WordPress database instance
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * Constructor
     *
     * @param string $table Table name
     * @since 1.0.0
     */
    public function __construct($table) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $table;
    }

    /**
     * Set columns to select
     *
     * @param array $columns Column names
     * @return self For method chaining
     * @since 1.0.0
     */
    public function set_columns($columns) {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Set searchable columns
     *
     * @param array $searchable_columns Column names that can be searched
     * @return self For method chaining
     * @since 1.0.0
     */
    public function set_searchable_columns($searchable_columns) {
        $this->searchable_columns = $searchable_columns;
        return $this;
    }

    /**
     * Set index column (primary key)
     *
     * @param string $index_column Column name
     * @return self For method chaining
     * @since 1.0.0
     */
    public function set_index_column($index_column) {
        $this->index_column = $index_column;
        return $this;
    }

    /**
     * Set WHERE conditions
     *
     * @param array $conditions Array of SQL WHERE conditions
     * @return self For method chaining
     * @since 1.0.0
     *
     * @example
     * $builder->set_where_conditions([
     *     "status = 'active'",
     *     "deleted_at IS NULL"
     * ]);
     */
    public function set_where_conditions($conditions) {
        $this->where_conditions = $conditions;
        return $this;
    }

    /**
     * Set JOIN clauses
     *
     * @param array $joins Array of SQL JOIN clauses
     * @return self For method chaining
     * @since 1.0.0
     *
     * @example
     * $builder->set_joins([
     *     "LEFT JOIN wp_users ON wp_users.ID = user_id"
     * ]);
     */
    public function set_joins($joins) {
        $this->joins = $joins;
        return $this;
    }

    /**
     * Set search value for DataTables global search
     *
     * @param string $search_value Search term
     * @return self For method chaining
     * @since 1.0.0
     */
    public function set_search_value($search_value) {
        $this->search_value = $search_value;
        return $this;
    }

    /**
     * Set ordering
     *
     * @param string $column Column to order by
     * @param string $dir Direction (ASC or DESC)
     * @return self For method chaining
     * @since 1.0.0
     */
    public function set_ordering($column, $dir = 'ASC') {
        $this->order_column = $column;
        $this->order_dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        return $this;
    }

    /**
     * Set pagination
     *
     * @param int $start Starting record (0-based offset)
     * @param int $length Number of records to fetch
     * @return self For method chaining
     * @since 1.0.0
     */
    public function set_pagination($start, $length) {
        $this->limit_start = max(0, intval($start));
        $this->limit_length = max(1, intval($length));
        return $this;
    }

    /**
     * Build SELECT clause
     *
     * @return string SQL SELECT clause
     * @since 1.0.0
     */
    private function build_select() {
        $select = implode(', ', $this->columns);
        return "SELECT {$select}";
    }

    /**
     * Build FROM clause with JOINs
     *
     * @return string SQL FROM clause with JOINs
     * @since 1.0.0
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
     * Includes both base conditions and search conditions
     *
     * @return string SQL WHERE clause
     * @since 1.0.0
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

        // Always include 1=1 for easier query building
        if (empty($where_parts)) {
            return 'WHERE 1=1';
        }

        return 'WHERE ' . implode(' AND ', $where_parts);
    }

    /**
     * Build ORDER BY clause
     *
     * @return string SQL ORDER BY clause
     * @since 1.0.0
     */
    private function build_order() {
        if (empty($this->order_column)) {
            // Default ordering by index column descending (newest first)
            $order_column = $this->index_column;
            // If index column has table prefix, use it
            if (strpos($order_column, '.') === false && !empty($this->columns[0])) {
                // Try to get table prefix from first column
                if (strpos($this->columns[0], '.') !== false) {
                    $table_prefix = substr($this->columns[0], 0, strpos($this->columns[0], '.'));
                    $order_column = $table_prefix . '.' . $order_column;
                }
            }
            return "ORDER BY {$order_column} DESC";
        }

        // Extract column name or alias from full column definition
        // E.g., "a.name as name" -> "name" (use alias)
        // E.g., "a.name" -> "a.name" (use column)
        $order_column = $this->order_column;
        if (stripos($order_column, ' as ') !== false) {
            // Extract alias (everything after " as ")
            $parts = preg_split('/\s+as\s+/i', $order_column);
            $order_column = trim($parts[1]);
        }

        return "ORDER BY {$order_column} {$this->order_dir}";
    }

    /**
     * Build LIMIT clause for pagination
     *
     * @return string SQL LIMIT clause
     * @since 1.0.0
     */
    private function build_limit() {
        return $this->wpdb->prepare("LIMIT %d, %d", $this->limit_start, $this->limit_length);
    }

    /**
     * Build complete query
     *
     * @param bool $with_limit Include LIMIT clause
     * @return string Complete SQL query
     * @since 1.0.0
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
     * @since 1.0.0
     */
    public function get_results() {
        $query = $this->build_query(true);

        // Log query in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('DataTable Query: ' . $query);
        }

        $results = $this->wpdb->get_results($query);

        // Log any database errors
        if ($this->wpdb->last_error) {
            error_log('DataTable Query Error: ' . $this->wpdb->last_error);
        }

        return $results ? $results : [];
    }

    /**
     * Count total records (without filters or search)
     *
     * @return int Total record count
     * @since 1.0.0
     */
    public function count_total() {
        // Build count query with only base WHERE conditions (no search)
        $query = "SELECT COUNT({$this->index_column}) as total FROM {$this->table}";

        // Add JOINs if needed for count
        if (!empty($this->joins)) {
            $query .= ' ' . implode(' ', $this->joins);
        }

        // Add base WHERE conditions (but not search)
        if (!empty($this->where_conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $this->where_conditions);
        }

        // Log query in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('DataTable Count Total Query: ' . $query);
        }

        $result = $this->wpdb->get_var($query);

        return intval($result);
    }

    /**
     * Count filtered records (with search applied)
     *
     * @return int Filtered record count
     * @since 1.0.0
     */
    public function count_filtered() {
        $query = "SELECT COUNT({$this->index_column}) as total " .
                 $this->build_from() . ' ' .
                 $this->build_where();

        // Log query in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('DataTable Count Filtered Query: ' . $query);
        }

        $result = $this->wpdb->get_var($query);

        return intval($result);
    }

    /**
     * Get current WHERE conditions
     *
     * @return array WHERE conditions
     * @since 1.0.0
     */
    public function get_where_conditions() {
        return $this->where_conditions;
    }

    /**
     * Get current columns
     *
     * @return array Columns
     * @since 1.0.0
     */
    public function get_columns() {
        return $this->columns;
    }

    /**
     * Get last executed query (for debugging)
     *
     * @return string Last query
     * @since 1.0.0
     */
    public function get_last_query() {
        return $this->wpdb->last_query;
    }
}
