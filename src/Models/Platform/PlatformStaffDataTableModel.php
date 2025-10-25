<?php
/**
 * Platform Staff DataTable Model
 *
 * Test implementation of DataTable system for platform staff
 *
 * @package WPAppCore
 * @subpackage Models\Platform
 * @since 1.0.0
 * @author arisciwek
 */

namespace WPAppCore\Models\Platform;

use WPAppCore\Models\DataTable\DataTableModel;

/**
 * PlatformStaffDataTableModel class
 *
 * Example implementation showing how to use DataTableModel base class
 * for platform staff management
 */
class PlatformStaffDataTableModel extends DataTableModel {

    /**
     * Constructor
     * Setup table, columns, and searchable columns
     */
    public function __construct() {
        parent::__construct();

        // Set table name
        $this->table = $this->wpdb->prefix . 'app_platform_staff';

        // Define columns to select
        $this->columns = [
            $this->table . '.id',
            $this->table . '.employee_id',
            $this->table . '.full_name',
            $this->table . '.department',
            $this->table . '.phone',
            $this->table . '.status',
            $this->table . '.hire_date',
            $this->table . '.created_at'
        ];

        // Define searchable columns
        $this->searchable_columns = [
            $this->table . '.employee_id',
            $this->table . '.full_name',
            $this->table . '.department',
            $this->table . '.phone'
        ];

        // Set primary key
        $this->index_column = $this->table . '.id';

        // Optional: Add base WHERE conditions
        // Only show non-deleted staff (if soft delete implemented)
        // $this->base_where = [
        //     "deleted_at IS NULL"
        // ];
    }

    /**
     * Format row for DataTable output
     *
     * @param object $row Database row object
     * @return array Formatted row data
     */
    protected function format_row($row) {
        return [
            // Column 0: ID
            $row->id,

            // Column 1: Employee ID
            esc_html($row->employee_id),

            // Column 2: Full Name with link
            sprintf(
                '<a href="%s" class="staff-link">%s</a>',
                admin_url('admin.php?page=wp-app-core-platform-staff&action=view&id=' . $row->id),
                esc_html($row->full_name)
            ),

            // Column 3: Department
            esc_html($row->department ?: '-'),

            // Column 4: Phone
            esc_html($row->phone ?: '-'),

            // Column 5: Status badge
            $this->get_status_badge($row->status),

            // Column 6: Hire date
            $row->hire_date ? date_i18n('d M Y', strtotime($row->hire_date)) : '-',

            // Column 7: Created date
            date_i18n('d M Y', strtotime($row->created_at)),

            // Column 8: Actions
            $this->get_action_buttons($row)
        ];
    }

    /**
     * Get status badge HTML
     *
     * @param string $status Status value
     * @return string HTML badge
     */
    private function get_status_badge($status) {
        $badges = [
            'aktif' => '<span class="badge badge-success">Aktif</span>',
            'nonaktif' => '<span class="badge badge-secondary">Non-Aktif</span>',
            'cuti' => '<span class="badge badge-warning">Cuti</span>',
        ];

        return $badges[$status] ?? '<span class="badge badge-secondary">' . esc_html($status) . '</span>';
    }

    /**
     * Get action buttons HTML
     *
     * @param object $row Row object
     * @return string HTML action buttons
     */
    private function get_action_buttons($row) {
        $actions = [];

        // View button
        if (current_user_can('view_platform_staff')) {
            $actions[] = sprintf(
                '<a href="%s" class="btn btn-sm btn-info" title="%s">
                    <i class="dashicons dashicons-visibility"></i>
                </a>',
                admin_url('admin.php?page=wp-app-core-platform-staff&action=view&id=' . $row->id),
                __('View', 'wp-app-core')
            );
        }

        // Edit button
        if (current_user_can('edit_platform_staff')) {
            $actions[] = sprintf(
                '<a href="%s" class="btn btn-sm btn-primary" title="%s">
                    <i class="dashicons dashicons-edit"></i>
                </a>',
                admin_url('admin.php?page=wp-app-core-platform-staff&action=edit&id=' . $row->id),
                __('Edit', 'wp-app-core')
            );
        }

        // Delete button
        if (current_user_can('delete_platform_staff')) {
            $actions[] = sprintf(
                '<a href="#" class="btn btn-sm btn-danger delete-staff"
                   data-id="%d"
                   data-name="%s"
                   title="%s">
                    <i class="dashicons dashicons-trash"></i>
                </a>',
                $row->id,
                esc_attr($row->full_name),
                __('Delete', 'wp-app-core')
            );
        }

        if (empty($actions)) {
            return '-';
        }

        return '<div class="btn-group">' . implode('', $actions) . '</div>';
    }
}
