<?php
/**
 * Platform Staff DataTable Model Class
 *
 * @package     WP_App_Core
 * @subpackage  Models/Platform
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Models/Platform/PlatformStaffDataTableModel.php
 *
 * Description: DataTable model untuk server-side processing platform staff.
 *              Extends base DataTableModel dari wp-app-core.
 *              Implements columns, joins, dan row formatting.
 *              Integrates dengan base panel system.
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-1192)
 * - Updated to follow AgencyDataTableModel pattern
 * - Extends WPAppCore\Models\DataTable\DataTableModel
 * - Define columns: employee_id, full_name, department, hire_date, status, actions
 * - Implement get_columns() method
 * - Implement format_row() with DT_RowId and DT_RowData for panel
 * - Add format_status_badge() helper
 * - Add generate_action_buttons() helper
 * - Add get_total_count() for dashboard statistics
 * - Add get_where() for status filtering
 */

namespace WPAppCore\Models\Platform;

use WPAppCore\Models\DataTable\DataTableModel;

class PlatformStaffDataTableModel extends DataTableModel {

    /**
     * Constructor
     * Setup table and columns configuration
     */
    public function __construct() {
        parent::__construct();

        global $wpdb;

        $this->table = $wpdb->prefix . 'app_platform_staff s';  // Include alias 's' for columns
        $this->index_column = 's.id';

        // Define searchable columns for global search
        $this->searchable_columns = [
            's.employee_id',
            's.full_name',
            's.department',
            's.phone'
        ];

        // Define base JOINs (if needed in future)
        $this->base_joins = [];
    }

    /**
     * Get columns configuration for DataTable
     *
     * Defines all columns with their properties
     *
     * @return array Columns configuration
     */
    protected function get_columns(): array {
        return [
            's.id as id',
            's.employee_id as employee_id',
            's.full_name as full_name',
            's.department as department',
            's.hire_date as hire_date'
        ];
    }

    /**
     * Format row data for DataTable output
     *
     * Applies proper escaping and formatting to each row.
     * Adds DT_RowId and DT_RowData for panel functionality.
     *
     * @param object $row Database row object
     * @return array Formatted row data
     */
    protected function format_row($row): array {
        return [
            'DT_RowId' => 'platform-staff-' . $row->id,  // Required for panel open
            'DT_RowData' => [
                'id' => $row->id,                         // Required for panel AJAX
                'entity' => 'platform_staff'              // Required for panel entity detection
            ],
            'employee_id' => esc_html($row->employee_id),
            'full_name' => esc_html($row->full_name),
            'department' => esc_html($row->department ?? '-'),
            'hire_date' => $row->hire_date ? date('d/m/Y', strtotime($row->hire_date)) : '-',
            'actions' => $this->generate_action_buttons($row)
        ];
    }

    /**
     * Get WHERE conditions for filtering
     *
     * Applies status filter
     *
     * @return array WHERE conditions
     */
    public function get_where(): array {
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
     * Format status badge with color coding
     *
     * @param string $status Status value
     * @return string HTML badge
     */
    private function format_status_badge(string $status): string {
        $badge_class = $status === 'aktif' ? 'success' : 'error';
        $status_text = $status === 'aktif'
            ? __('Active', 'wp-app-core')
            : __('Inactive', 'wp-app-core');

        return sprintf(
            '<span class="wpapp-badge wpapp-badge-%s">%s</span>',
            esc_attr($badge_class),
            esc_html($status_text)
        );
    }

    /**
     * Generate action buttons for each row
     *
     * @param object $row Database row object
     * @return string HTML action buttons
     */
    private function generate_action_buttons($row): string {
        $buttons = [];

        // View button (always shown, opens panel)
        $buttons[] = sprintf(
            '<button type="button" class="button button-small wpapp-panel-trigger" data-id="%d" data-entity="platform_staff" title="%s">
                <span class="dashicons dashicons-visibility"></span>
            </button>',
            esc_attr($row->id),
            esc_attr__('View Details', 'wp-app-core')
        );

        // Edit button (if user has permission)
        if (current_user_can('edit_platform_users')) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small wpapp-edit-platform-staff" data-id="%d" title="%s">
                    <span class="dashicons dashicons-edit"></span>
                </button>',
                esc_attr($row->id),
                esc_attr__('Edit', 'wp-app-core')
            );
        }

        // Delete button (if user has permission)
        if (current_user_can('delete_platform_users')) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small wpapp-delete-platform-staff" data-id="%d" title="%s">
                    <span class="dashicons dashicons-trash"></span>
                </button>',
                esc_attr($row->id),
                esc_attr__('Delete', 'wp-app-core')
            );
        }

        return implode(' ', $buttons);
    }

    /**
     * Get table alias for WHERE/JOIN clauses
     *
     * @return string Table alias
     */
    protected function get_table_alias(): string {
        return 's';
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

        // Temporarily set POST for get_where() method
        $original_post = $_POST;
        $_POST['status_filter'] = $status_filter;

        // Build WHERE conditions
        $where_conditions = $this->get_where();

        // Restore original POST
        $_POST = $original_post;

        // Build count query
        $where_sql = '';
        if (!empty($where_conditions)) {
            $where_sql = ' WHERE ' . implode(' AND ', $where_conditions);
        }

        $count_sql = "SELECT COUNT(DISTINCT s.id) as total
                      FROM {$this->table}
                      {$where_sql}";

        return (int) $wpdb->get_var($count_sql);
    }
}
