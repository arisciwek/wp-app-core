<?php
/**
 * Platform Staff DataTable Test Filters
 *
 * Demonstrates how to extend DataTable via filter hooks
 * This is a TEST class showing all available hook types
 *
 * @package WPAppCore
 * @subpackage Tests\DataTable
 * @since 1.0.0
 * @author arisciwek
 */

namespace WPAppCore\Tests\DataTable;

defined('ABSPATH') || exit;

/**
 * PlatformStaffDataTableFilters class
 *
 * Example filter class showing how modules can extend DataTables
 * without modifying core code
 */
class PlatformStaffDataTableFilters {

    /**
     * Constructor
     * Register all filter hooks
     */
    public function __construct() {
        // Hook 1: Modify columns
        add_filter('wpapp_datatable_platform_staff_columns', [$this, 'add_custom_columns'], 10, 3);

        // Hook 2: Add WHERE conditions
        add_filter('wpapp_datatable_platform_staff_where', [$this, 'add_where_conditions'], 10, 3);

        // Hook 3: Add JOINs (if needed)
        // add_filter('wpapp_datatable_platform_staff_joins', [$this, 'add_joins'], 10, 3);

        // Hook 4: Modify row data
        add_filter('wpapp_datatable_platform_staff_row_data', [$this, 'modify_row_data'], 10, 3);

        // Hook 5: Modify final response
        add_filter('wpapp_datatable_platform_staff_response', [$this, 'add_response_metadata'], 10, 2);

        // Hook 6: Check permissions
        add_filter('wpapp_datatable_can_access', [$this, 'check_access_permission'], 10, 2);

        // Log that filters are registered
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('DataTable Test Filters: All hooks registered for platform_staff');
        }
    }

    /**
     * Add custom columns
     *
     * Example: Add a calculated column
     *
     * @param array $columns Current columns
     * @param object $model Model instance
     * @param array $request_data Request data
     * @return array Modified columns
     */
    public function add_custom_columns($columns, $model, $request_data) {
        // Log for debugging
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('DataTable Filter: add_custom_columns executed');
        }

        // Example: Add user email from joined users table
        // (In real implementation, you'd also add the JOIN in add_joins method)
        // $columns[] = 'u.user_email as user_email';

        return $columns;
    }

    /**
     * Add WHERE conditions
     *
     * Example: Filter by status from URL parameter
     *
     * @param array $where Current WHERE conditions
     * @param array $request_data Request data
     * @param object $model Model instance
     * @return array Modified WHERE conditions
     */
    public function add_where_conditions($where, $request_data, $model) {
        // Log for debugging
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('DataTable Filter: add_where_conditions executed');
        }

        // Example 1: Filter by status from URL
        if (isset($_GET['filter_status']) && !empty($_GET['filter_status'])) {
            $status = sanitize_text_field($_GET['filter_status']);
            $where[] = "status = '" . esc_sql($status) . "'";

            error_log("DataTable Filter: Added WHERE status = '{$status}'");
        }

        // Example 2: Filter by department from URL
        if (isset($_GET['filter_department']) && !empty($_GET['filter_department'])) {
            $department = sanitize_text_field($_GET['filter_department']);
            $where[] = "department = '" . esc_sql($department) . "'";

            error_log("DataTable Filter: Added WHERE department = '{$department}'");
        }

        // Example 3: Only show active staff by default (commented out for testing)
        // $where[] = "status = 'aktif'";

        return $where;
    }

    /**
     * Add JOIN clauses
     *
     * Example: Join users table to get email
     *
     * @param array $joins Current JOINs
     * @param array $request_data Request data
     * @param object $model Model instance
     * @return array Modified JOINs
     */
    public function add_joins($joins, $request_data, $model) {
        global $wpdb;

        // Log for debugging
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('DataTable Filter: add_joins executed');
        }

        // Example: Join users table
        // $joins[] = "LEFT JOIN {$wpdb->prefix}users u ON u.ID = user_id";

        return $joins;
    }

    /**
     * Modify row data
     *
     * Example: Add custom styling, tooltips, or additional data
     *
     * @param array $formatted_row Formatted row data
     * @param object $raw_row Raw database row
     * @param object $model Model instance
     * @return array Modified row data
     */
    public function modify_row_data($formatted_row, $raw_row, $model) {
        // Log for debugging (commented to avoid too much logging)
        // error_log('DataTable Filter: modify_row_data executed for ID ' . $raw_row->id);

        // Example 1: Add icon to full name if status is aktif
        if (isset($formatted_row[2]) && $raw_row->status === 'aktif') {
            $formatted_row[2] = '<span class="dashicons dashicons-star-filled" style="color: gold;"></span> ' . $formatted_row[2];
        }

        // Example 2: Add tooltip to department
        if (isset($formatted_row[3]) && !empty($raw_row->department)) {
            $formatted_row[3] = '<span title="Department: ' . esc_attr($raw_row->department) . '">' . $formatted_row[3] . '</span>';
        }

        // Example 3: Add custom data attribute
        // This can be used by JavaScript for additional functionality
        // $formatted_row[0] = '<span data-staff-id="' . $raw_row->id . '">' . $formatted_row[0] . '</span>';

        return $formatted_row;
    }

    /**
     * Add metadata to response
     *
     * Example: Add summary statistics or additional info
     *
     * @param array $response DataTables response
     * @param object $model Model instance
     * @return array Modified response
     */
    public function add_response_metadata($response, $model) {
        // Log for debugging
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('DataTable Filter: add_response_metadata executed');
        }

        // Example: Add custom metadata
        $response['metadata'] = [
            'filter_applied' => isset($_GET['filter_status']) || isset($_GET['filter_department']),
            'timestamp' => current_time('mysql'),
            'test_mode' => true
        ];

        // Example: Add summary stats
        global $wpdb;
        $table = $wpdb->prefix . 'app_platform_staff';

        $response['summary'] = [
            'total_staff' => $wpdb->get_var("SELECT COUNT(*) FROM {$table}"),
            'active_staff' => $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'aktif'"),
            'departments' => $wpdb->get_var("SELECT COUNT(DISTINCT department) FROM {$table} WHERE department IS NOT NULL")
        ];

        return $response;
    }

    /**
     * Check access permission
     *
     * Example: Custom permission logic
     *
     * @param bool $can_access Current access status
     * @param string $model_class Model class name
     * @return bool Modified access status
     */
    public function check_access_permission($can_access, $model_class) {
        // Only apply to our model
        if ($model_class !== 'WPAppCore\\Models\\Platform\\PlatformStaffDataTableModel') {
            return $can_access;
        }

        // Log for debugging
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('DataTable Filter: check_access_permission executed');
        }

        // Example: Custom permission check
        // In real implementation, check specific capability
        // return current_user_can('view_platform_staff');

        // For testing, allow if user can manage_options
        return current_user_can('manage_options');
    }
}

// Initialize filters (for testing purposes)
// In real implementation, this would be in the module's init file
// and only loaded when needed
if (defined('WP_DEBUG') && WP_DEBUG) {
    // Only initialize in debug mode for testing
    add_action('init', function() {
        new PlatformStaffDataTableFilters();
    }, 20);
}
