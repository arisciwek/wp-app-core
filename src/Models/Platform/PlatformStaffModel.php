<?php
/**
 * Platform Staff Model
 *
 * @package     WP_App_Core
 * @subpackage  Models/Platform
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Models/Platform/PlatformStaffModel.php
 *
 * Description: Model untuk mengelola data platform staff.
 *              Menangani operasi terkait staff profile dan management.
 *              Includes:
 *              - CRUD operations untuk staff
 *              - Employee ID generation
 *              - DataTable server-side processing
 *              - Statistics dan reporting
 *              - Cache management untuk optimasi performa
 *
 * Dependencies:
 * - WPAppCore\Cache\PlatformStaffCacheManager
 * - WordPress $wpdb
 *
 * Changelog:
 * 1.0.11 - 2025-11-01 (TODO-1190: Static ID Hook Pattern)
 * - Added wp_app_core_platform_staff_before_insert filter hook
 * - Reorder $insert_data if 'id' field injected via hook
 * - Rebuild format array to match data order (prevents column mismatch)
 * - Follows wp-agency/wp-customer pattern for entity static IDs
 *
 * 1.0.0 - 2025-10-19
 * - Initial implementation
 * - CRUD operations
 * - DataTable support
 * - Statistics
 * - Cache integration
 */

namespace WPAppCore\Models\Platform;

use WPAppCore\Cache\PlatformCacheManager;

defined('ABSPATH') || exit;

class PlatformStaffModel {
    /**
     * Database table name
     * @var string
     */
    private $table;

    /**
     * Cache manager instance
     * @var PlatformCacheManager
     */
    private $cache;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'app_platform_staff';
        $this->cache = new PlatformCacheManager();
    }

    /**
     * Find staff by ID
     *
     * @param int $id Staff ID
     * @return object|null Staff data or null
     */
    public function find(int $id): ?object {
        // Try cache first
        $cached = $this->cache->getStaff($id);
        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;
        $staff = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*,
                    s.full_name as name,
                    u.user_email as email
             FROM {$this->table} s
             LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
             WHERE s.id = %d",
            $id
        ));

        if ($staff) {
            $this->cache->setStaff($id, $staff);
        }

        return $staff;
    }

    /**
     * Find staff by user ID
     *
     * @param int $user_id User ID
     * @return object|null Staff data or null
     */
    public function findByUserId(int $user_id): ?object {
        // Try cache first
        $cached = $this->cache->getUserStaff($user_id);
        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;
        $staff = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE user_id = %d",
            $user_id
        ));

        if ($staff) {
            $this->cache->setUserStaff($user_id, $staff);
            $this->cache->setStaff($staff->id, $staff);
        }

        return $staff;
    }

    /**
     * Find staff by employee ID
     *
     * @param string $employee_id Employee ID (e.g., STAFF-001)
     * @return object|null Staff data or null
     */
    public function findByEmployeeId(string $employee_id): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE employee_id = %s",
            $employee_id
        ));
    }

    /**
     * Get all staff
     *
     * @param array $args Optional query arguments
     * @return array Staff list
     */
    public function getAll(array $args = []): array {
        // Try cache first (only if no specific filters)
        if (empty($args)) {
            $cached = $this->cache->getStaffList();
            if ($cached !== false) {
                return $cached;
            }
        }

        global $wpdb;

        $where = ['1=1'];
        $params = [];

        // Filter by department
        if (!empty($args['department'])) {
            $where[] = 'department = %s';
            $params[] = $args['department'];
        }

        // Order by
        $order_by = !empty($args['order_by']) ? $args['order_by'] : 'created_at';
        $order = !empty($args['order']) ? $args['order'] : 'DESC';

        $query = "SELECT * FROM {$this->table}
                  WHERE " . implode(' AND ', $where) . "
                  ORDER BY {$order_by} {$order}";

        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }

        $results = $wpdb->get_results($query);

        // Cache only if no filters
        if (empty($args)) {
            $this->cache->setStaffList($results);
        }

        return $results;
    }

    /**
     * Get staff by department
     *
     * @param string $department Department name
     * @return array Staff list
     */
    public function getByDepartment(string $department): array {
        // Try cache first
        $cached = $this->cache->getStaffByDepartment($department);
        if ($cached !== false) {
            return $cached;
        }

        $results = $this->getAll(['department' => $department]);
        $this->cache->setStaffByDepartment($department, $results);

        return $results;
    }

    /**
     * Create new staff
     *
     * @param array $data Staff data
     * @return int|false Staff ID on success, false on failure
     */
    public function create(array $data) {
        global $wpdb;

        // Generate employee_id if not provided
        if (empty($data['employee_id'])) {
            $data['employee_id'] = $this->generateEmployeeId();
        }

        $insert_data = [
            'user_id' => $data['user_id'],
            'employee_id' => $data['employee_id'],
            'full_name' => $data['full_name'],
            'department' => !empty($data['department']) ? $data['department'] : null,
            'hire_date' => !empty($data['hire_date']) ? $data['hire_date'] : null,
            'phone' => !empty($data['phone']) ? $data['phone'] : null,
            'status' => !empty($data['status']) ? $data['status'] : 'aktif',
        ];

        // HOOK: Allow static ID injection via filter
        $insert_data = apply_filters('wp_app_core_platform_staff_before_insert', $insert_data, $data);

        // If 'id' field was injected via filter, reorder to put it first
        if (isset($insert_data['id'])) {
            $static_id = $insert_data['id'];
            unset($insert_data['id']);
            $insert_data = array_merge(['id' => $static_id], $insert_data);
        }

        // Prepare format array (must match key order after reordering)
        $format = [];
        if (isset($insert_data['id'])) {
            $format[] = '%d';  // id (if injected)
        }
        $format = array_merge($format, [
            '%d',  // user_id
            '%s',  // employee_id
            '%s',  // full_name
            '%s',  // department
            '%s',  // hire_date
            '%s',  // phone
            '%s',  // status
        ]);

        $result = $wpdb->insert($this->table, $insert_data, $format);

        if ($result) {
            $staff_id = $wpdb->insert_id;

            // Clear caches
            $this->cache->clearStaffCache();

            return $staff_id;
        }

        return false;
    }

    /**
     * Update existing staff
     *
     * @param int $id Staff ID
     * @param array $data Staff data to update
     * @return bool True on success, false on failure
     */
    public function update(int $id, array $data): bool {
        global $wpdb;

        // Get current staff data for cache clearing
        $current_staff = $this->find($id);
        if (!$current_staff) {
            return false;
        }

        $update_data = [];
        $format = [];

        // Only update provided fields
        $allowed_fields = ['full_name', 'department', 'hire_date', 'phone', 'status'];

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
                $format[] = '%s';
            }
        }

        if (empty($update_data)) {
            return false;
        }

        $result = $wpdb->update(
            $this->table,
            $update_data,
            ['id' => $id],
            $format,
            ['%d']
        );

        if ($result !== false) {
            // Clear caches
            $this->cache->clearSpecificStaffCache(
                $id,
                $current_staff->user_id,
                $current_staff->department
            );

            return true;
        }

        return false;
    }

    /**
     * Delete staff
     *
     * @param int $id Staff ID
     * @return bool True on success, false on failure
     */
    public function delete(int $id): bool {
        global $wpdb;

        // Get staff data before deletion for cache clearing
        $staff = $this->find($id);
        if (!$staff) {
            return false;
        }

        $result = $wpdb->delete(
            $this->table,
            ['id' => $id],
            ['%d']
        );

        if ($result) {
            // Clear caches
            $this->cache->clearSpecificStaffCache(
                $id,
                $staff->user_id,
                $staff->department
            );

            return true;
        }

        return false;
    }

    /**
     * Get DataTable data for server-side processing
     *
     * @param array $params DataTable parameters
     * @return array DataTable response
     */
    public function getDataTableData(array $params): array {
        global $wpdb;

        // Extract DataTable parameters
        $draw = isset($params['draw']) ? intval($params['draw']) : 1;
        $start = isset($params['start']) ? intval($params['start']) : 0;
        $length = isset($params['length']) ? intval($params['length']) : 10;
        $search_value = isset($params['search']['value']) ? sanitize_text_field($params['search']['value']) : '';

        // Column mapping for sorting
        $columns = ['id', 'employee_id', 'full_name', 'department', 'hire_date', 'phone', 'created_at'];
        $order_column_index = isset($params['order'][0]['column']) ? intval($params['order'][0]['column']) : 0;
        $order_column = isset($columns[$order_column_index]) ? $columns[$order_column_index] : 'created_at';
        $order_dir = isset($params['order'][0]['dir']) && $params['order'][0]['dir'] === 'asc' ? 'ASC' : 'DESC';

        // Build WHERE clause
        $where = ['1=1'];
        $where_params = [];

        // Search
        if (!empty($search_value)) {
            $where[] = '(employee_id LIKE %s OR full_name LIKE %s OR department LIKE %s OR phone LIKE %s)';
            $search_param = '%' . $wpdb->esc_like($search_value) . '%';
            $where_params[] = $search_param;
            $where_params[] = $search_param;
            $where_params[] = $search_param;
            $where_params[] = $search_param;
        }

        // Filter by department
        if (!empty($params['filter_department'])) {
            $where[] = 'department = %s';
            $where_params[] = sanitize_text_field($params['filter_department']);
        }

        // Filter by status
        if (!empty($params['filter_status'])) {
            $where[] = 'status = %s';
            $where_params[] = sanitize_text_field($params['filter_status']);
        }

        $where_clause = implode(' AND ', $where);

        // Get total records (without filtering)
        $total_records = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");

        // Get filtered records count
        $count_query = "SELECT COUNT(*) FROM {$this->table} WHERE {$where_clause}";
        if (!empty($where_params)) {
            $count_query = $wpdb->prepare($count_query, $where_params);
        }
        $filtered_records = $wpdb->get_var($count_query);

        // Get data
        $data_query = "SELECT s.*, u.user_email, u.user_login
                       FROM {$this->table} s
                       LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
                       WHERE {$where_clause}
                       ORDER BY s.{$order_column} {$order_dir}
                       LIMIT %d OFFSET %d";

        $query_params = array_merge($where_params, [$length, $start]);
        $data_query = $wpdb->prepare($data_query, $query_params);

        $data = $wpdb->get_results($data_query);

        // Format data for DataTable
        $formatted_data = array_map(function($row) {
            return [
                'id' => $row->id,
                'employee_id' => $row->employee_id,
                'full_name' => $row->full_name,
                'department' => $row->department ?: '-',
                'hire_date' => $row->hire_date ? date('d/m/Y', strtotime($row->hire_date)) : '-',
                'phone' => $row->phone ?: '-',
                'status' => $row->status,
                'status_label' => $row->status === 'aktif' ? 'Aktif' : 'Tidak Aktif',
                'user_email' => $row->user_email,
                'user_login' => $row->user_login,
                'created_at' => date('d/m/Y H:i', strtotime($row->created_at)),
            ];
        }, $data);

        return [
            'draw' => $draw,
            'recordsTotal' => $total_records,
            'recordsFiltered' => $filtered_records,
            'data' => $formatted_data,
        ];
    }

    /**
     * Get statistics
     *
     * @return array Statistics data
     */
    public function getStatistics(): array {
        // Try cache first
        $cached = $this->cache->getStaffStats();
        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;

        $stats = [
            'total_staff' => 0,
            'by_department' => [],
            'recent_hires' => 0,
            'departments' => [],
        ];

        // Total staff
        $stats['total_staff'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");

        // Staff by department
        $dept_stats = $wpdb->get_results(
            "SELECT department, COUNT(*) as count
             FROM {$this->table}
             WHERE department IS NOT NULL
             GROUP BY department
             ORDER BY count DESC"
        );

        foreach ($dept_stats as $dept) {
            $stats['by_department'][$dept->department] = $dept->count;
            $stats['departments'][] = $dept->department;
        }

        // Recent hires (last 30 days)
        $stats['recent_hires'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table}
             WHERE hire_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        // Cache statistics
        $this->cache->setStaffStats($stats);

        return $stats;
    }

    /**
     * Generate next employee ID
     *
     * @return string Employee ID (e.g., STAFF-001)
     */
    public function generateEmployeeId(): string {
        global $wpdb;

        // Get last employee ID
        $last_employee_id = $wpdb->get_var(
            "SELECT employee_id FROM {$this->table}
             WHERE employee_id LIKE 'STAFF-%'
             ORDER BY employee_id DESC
             LIMIT 1"
        );

        if ($last_employee_id) {
            // Extract number from STAFF-XXX
            $last_number = intval(str_replace('STAFF-', '', $last_employee_id));
            $next_number = $last_number + 1;
        } else {
            $next_number = 1;
        }

        // Format: STAFF-001, STAFF-002, etc.
        return 'STAFF-' . str_pad($next_number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get all departments
     *
     * @return array List of unique departments
     */
    public function getDepartments(): array {
        global $wpdb;

        $departments = $wpdb->get_col(
            "SELECT DISTINCT department
             FROM {$this->table}
             WHERE department IS NOT NULL
             ORDER BY department ASC"
        );

        return $departments;
    }
}
