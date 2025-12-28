<?php
/**
 * Platform Staff DataTable Model Class
 *
 * @package     WP_App_Core
 * @subpackage  Models/Platform
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Models/Platform/PlatformStaffDataTableModel.php
 *
 * Description: DataTable model untuk server-side processing platform staff.
 *              NOW extends AbstractDataTable from wp-datatable (v2.0).
 *              Uses WordPress native $wpdb pattern.
 *              Integrates dengan wp-datatable's panel system.
 *
 * Changelog:
 * 2.0.0 - 2025-12-28
 * - BREAKING: Changed to extend WPDataTable\Core\AbstractDataTable
 * - Removed dependency on deprecated wp-app-core DataTableModel
 * - Uses WordPress native $wpdb pattern (no QueryBuilder)
 * - Uses DataTableHelpers trait methods for badges and buttons
 * - Implements get_entity_name() and get_text_domain()
 * - Updated badge classes: wpapp-badge → wpdt-badge
 *
 * 1.0.0 - 2025-11-01 (TODO-1192)
 * - Initial version extending wp-app-core's DataTableModel
 */

namespace WPAppCore\Models\Platform;

use WPDataTable\Core\AbstractDataTable;

class PlatformStaffDataTableModel extends AbstractDataTable {

    /**
     * Constructor
     * Setup table and columns configuration
     */
    public function __construct() {
        parent::__construct();

        global $wpdb;

        $this->table = $wpdb->prefix . 'app_platform_staff s';
        $this->index_column = 's.id';

        // Columns to SELECT in SQL query
        $this->columns = [
            's.id as id',
            's.full_name as name',
            'u.user_email as email',
            's.phone as phone',
            's.status as status'
        ];

        // Define searchable columns for global search
        $this->searchable_columns = [
            's.employee_id',
            's.full_name',
            's.department',
            's.phone',
            'u.user_email'
        ];

        // Define base JOINs to get user email
        $this->base_joins = [
            "LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID"
        ];
    }

    /**
     * Get entity name for this DataTable
     *
     * @return string
     */
    public function get_entity_name(): string {
        return 'platform_staff';
    }

    /**
     * Get text domain for translations
     *
     * @return string
     */
    public function get_text_domain(): string {
        return 'wp-app-core';
    }

    /**
     * Format row data for DataTable output
     *
     * Applies proper escaping and formatting to each row.
     * Uses DataTableHelpers trait methods for consistent UI.
     *
     * @param object $row Database row object
     * @return array Formatted row data
     */
    protected function format_row($row): array {
        return array_merge(
            $this->format_panel_row_data($row, 'platform_staff'),
            [
                'name' => $this->esc_output($row->name ?? ''),
                'email' => $this->esc_output($row->email ?? ''),
                'phone' => $this->esc_output($row->phone ?? ''),
                'status' => $this->format_status_badge($row->status ?? 'inactive', [
                    'text_domain' => 'wp-app-core',
                    'active_value' => 'aktif'
                ]),
                'actions' => $this->generate_action_buttons($row, [
                    'entity' => 'platform_staff',
                    'edit_capability' => 'edit_platform_users',
                    'delete_capability' => 'delete_platform_users',
                    'text_domain' => 'wp-app-core'
                ])
            ]
        );
    }

    /**
     * Get WHERE conditions for filtering
     *
     * Override to add status filter support
     *
     * @param array $request_data DataTables request data
     * @return array WHERE conditions
     */
    protected function get_where_conditions(array $request_data): array {
        global $wpdb;
        $where = [];

        // Status filter
        $status_filter = isset($_POST['status_filter']) ? sanitize_text_field($_POST['status_filter']) : 'aktif';

        if ($status_filter !== 'all') {
            $where[] = $wpdb->prepare('s.status = %s', $status_filter);
        }

        return $where;
    }

    /**
     * Get total count with filtering
     *
     * Helper method for dashboard statistics
     *
     * @param string $status_filter Status to filter
     * @return int Total count
     */
    public function get_total_count(string $status_filter = 'aktif'): int {
        global $wpdb;

        // Build WHERE condition
        $where_sql = '';
        if ($status_filter !== 'all') {
            $where_sql = $wpdb->prepare(' WHERE s.status = %s', $status_filter);
        }

        $count_sql = "SELECT COUNT(DISTINCT s.id) as total
                      FROM {$this->table}
                      {$where_sql}";

        return (int) $wpdb->get_var($count_sql);
    }
}
