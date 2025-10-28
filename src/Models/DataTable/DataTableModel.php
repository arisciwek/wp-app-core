<?php
/**
 * DataTable Base Model
 *
 * Base class for all DataTable models in the application.
 * Provides core functionality for server-side DataTables processing
 * inspired by Perfex CRM pattern.
 *
 * @package WPAppCore
 * @subpackage Models\DataTable
 * @since 1.0.0
 * @author arisciwek
 */

namespace WPAppCore\Models\DataTable;

use WPAppCore\Models\DataTable\DataTableQueryBuilder;

/**
 * Abstract DataTableModel class
 *
 * All DataTable models should extend this class and implement format_row() method.
 *
 * @example
 * class CustomerDataTableModel extends DataTableModel {
 *     public function __construct() {
 *         parent::__construct();
 *         $this->table = $this->wpdb->prefix . 'customers';
 *         $this->columns = ['id', 'name', 'email'];
 *         $this->searchable_columns = ['name', 'email'];
 *     }
 *
 *     protected function format_row($row) {
 *         return [$row->id, $row->name, $row->email];
 *     }
 * }
 */
abstract class DataTableModel {

    /**
     * Database table name (with prefix)
     * Child classes MUST set this in constructor
     *
     * @var string
     */
    protected $table;

    /**
     * Columns to select from database
     * Child classes MUST set this in constructor
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Searchable columns (for DataTables global search)
     * Child classes SHOULD set this for search functionality
     *
     * @var array
     */
    protected $searchable_columns = [];

    /**
     * Primary key column name
     * Used for counting and default ordering
     *
     * @var string
     */
    protected $index_column = 'id';

    /**
     * Base WHERE conditions that always apply
     * Can be set by child classes for default filtering
     *
     * @var array
     */
    protected $base_where = [];

    /**
     * Base JOIN clauses that always apply
     * Can be set by child classes for default joins
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
     * Sets up WordPress database instance
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
     * Filter hooks applied:
     * - wpapp_datatable_{table}_columns: Modify columns
     * - wpapp_datatable_{table}_where: Add WHERE conditions
     * - wpapp_datatable_{table}_joins: Add JOIN clauses
     * - wpapp_datatable_{table}_query_builder: Modify query builder
     * - wpapp_datatable_{table}_row_data: Modify row output
     * - wpapp_datatable_{table}_response: Modify final response
     *
     * @param array $request_data DataTables request parameters from $_POST
     * @return array DataTables response format
     *
     * @since 1.0.0
     */
    public function get_datatable_data($request_data) {

        // 1. Get columns (allow modules to modify)
        $columns = $this->get_columns();

        /**
         * Filter columns to select
         *
         * @param array $columns Current columns
         * @param DataTableModel $this Model instance
         * @param array $request_data DataTables request
         */
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

        /**
         * Filter WHERE conditions
         *
         * Modules can add WHERE conditions to filter records
         *
         * @param array $where_conditions Current WHERE conditions
         * @param array $request_data DataTables request
         * @param DataTableModel $this Model instance
         */
        $where_conditions = apply_filters(
            $this->get_filter_hook('where'),
            $where_conditions,
            $request_data,
            $this
        );

        $query_builder->set_where_conditions($where_conditions);

        // 4. Build JOINs
        $joins = $this->base_joins;

        /**
         * Filter JOIN clauses
         *
         * Modules can add JOINs to include related tables
         *
         * @param array $joins Current JOIN clauses
         * @param array $request_data DataTables request
         * @param DataTableModel $this Model instance
         */
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
        if (isset($request_data['order'][0])) {
            $order_column_index = intval($request_data['order'][0]['column']);
            $order_dir = sanitize_text_field($request_data['order'][0]['dir']);

            // Validate column index
            if (isset($columns[$order_column_index])) {
                $order_column = $columns[$order_column_index];
                $query_builder->set_ordering($order_column, $order_dir);
            }
        }

        // 7. Apply pagination
        $start = isset($request_data['start']) ? intval($request_data['start']) : 0;
        $length = isset($request_data['length']) ? intval($request_data['length']) : 10;

        // Limit max records per page to prevent performance issues
        $length = min($length, 100);

        $query_builder->set_pagination($start, $length);

        /**
         * Filter query builder before execution
         *
         * Advanced hook to modify entire query builder
         *
         * @param DataTableQueryBuilder $query_builder Query builder instance
         * @param array $request_data DataTables request
         * @param DataTableModel $this Model instance
         */
        $query_builder = apply_filters(
            $this->get_filter_hook('query_builder'),
            $query_builder,
            $request_data,
            $this
        );

        // 8. Execute query and get results
        $results = $query_builder->get_results();

        // 9. Count totals for pagination
        $total_records = $query_builder->count_total();
        $filtered_records = $query_builder->count_filtered();

        // 10. Format each row
        $output_data = [];
        foreach ($results as $row) {
            $formatted_row = $this->format_row($row);

            /**
             * Filter formatted row data
             *
             * Modules can modify row output (add buttons, change display, etc)
             *
             * @param array $formatted_row Formatted row data
             * @param object $row Raw database row
             * @param DataTableModel $this Model instance
             */
            $formatted_row = apply_filters(
                $this->get_filter_hook('row_data'),
                $formatted_row,
                $row,
                $this
            );

            $output_data[] = $formatted_row;
        }

        // 11. Build DataTables response
        $response = [
            'draw' => isset($request_data['draw']) ? intval($request_data['draw']) : 0,
            'recordsTotal' => $total_records,
            'recordsFiltered' => $filtered_records,
            'data' => $output_data
        ];

        /**
         * Filter final response before sending to client
         *
         * @param array $response DataTables response
         * @param DataTableModel $this Model instance
         */
        $response = apply_filters(
            $this->get_filter_hook('response'),
            $response,
            $this
        );

        return $response;
    }

    /**
     * Get columns to select
     *
     * Can be overridden by child classes for dynamic column logic
     *
     * @return array Column names
     * @since 1.0.0
     */
    protected function get_columns() {
        return $this->columns;
    }

    /**
     * Format a single row for output
     *
     * Child classes MUST override this method to format row data
     * for display in DataTable
     *
     * @param object $row Database row object
     * @return array Formatted row data (array of values matching columns)
     *
     * @since 1.0.0
     */
    abstract protected function format_row($row);

    /**
     * Generate filter hook name for this table
     *
     * Format: wpapp_datatable_{table}_{type}
     * Example: wpapp_datatable_customers_where
     *
     * @param string $type Hook type (columns, where, joins, row_data, etc.)
     * @return string Full hook name
     *
     * @since 1.0.0
     */
    protected function get_filter_hook($type) {
        // Remove prefix from table name for cleaner hook names
        $table_name = str_replace($this->wpdb->prefix, '', $this->table);

        // Remove 'app_' prefix if present (optional)
        $table_name = str_replace('app_', '', $table_name);

        // Remove alias if present (e.g., "agencies a" → "agencies")
        // Split by space and take first part
        $table_name = preg_replace('/\s+.*$/', '', $table_name);

        return "wpapp_datatable_{$table_name}_{$type}";
    }

    /**
     * Get table name with prefix
     *
     * @return string Table name
     * @since 1.0.0
     */
    public function get_table() {
        return $this->table;
    }

    /**
     * Get searchable columns
     *
     * @return array Searchable column names
     * @since 1.0.0
     */
    public function get_searchable_columns() {
        return $this->searchable_columns;
    }

    /**
     * Get index column
     *
     * @return string Index column name
     * @since 1.0.0
     */
    public function get_index_column() {
        return $this->index_column;
    }
}
