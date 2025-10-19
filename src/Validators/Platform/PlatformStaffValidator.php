<?php
/**
 * Platform Staff Validator
 *
 * @package     WP_App_Core
 * @subpackage  Validators/Platform
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Validators/Platform/PlatformStaffValidator.php
 *
 * Description: Validator untuk memvalidasi operasi terkait Platform staff
 *              - Validasi staff creation
 *              - Validasi staff updates
 *              - Validasi user eligibility
 *              - Permission validation
 *
 * Dependencies:
 * - PlatformStaffModel untuk check data
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial implementation
 * - Staff creation validation
 * - Staff update validation
 * - Permission checks
 */

namespace WPAppCore\Validators\Platform;

use WPAppCore\Models\Platform\PlatformStaffModel;

defined('ABSPATH') || exit;

class PlatformStaffValidator {
    private $staff_model;

    public function __construct() {
        $this->staff_model = new PlatformStaffModel();
    }

    /**
     * Validate staff creation data
     *
     * @param array $staff_data Staff data to validate
     * @return bool|\WP_Error True if valid or WP_Error with reason
     */
    public function validateStaffCreation(array $staff_data) {
        // Check required fields
        $required_fields = ['user_id', 'full_name'];

        foreach ($required_fields as $field) {
            if (empty($staff_data[$field])) {
                return new \WP_Error(
                    'missing_field',
                    sprintf(__('Field %s harus diisi', 'wp-app-core'), $field)
                );
            }
        }

        // Validate user exists
        $user = get_userdata($staff_data['user_id']);
        if (!$user) {
            return new \WP_Error(
                'invalid_user',
                __('User tidak ditemukan', 'wp-app-core')
            );
        }

        // Check if user already has staff profile
        $existing_staff = $this->staff_model->findByUserId($staff_data['user_id']);
        if ($existing_staff) {
            return new \WP_Error(
                'duplicate_user',
                __('User sudah memiliki profil staff', 'wp-app-core')
            );
        }

        // Validate employee_id if provided
        if (!empty($staff_data['employee_id'])) {
            $existing = $this->staff_model->findByEmployeeId($staff_data['employee_id']);
            if ($existing) {
                return new \WP_Error(
                    'duplicate_employee_id',
                    __('Employee ID sudah digunakan', 'wp-app-core')
                );
            }
        }

        // Validate full_name length
        if (strlen($staff_data['full_name']) > 100) {
            return new \WP_Error(
                'invalid_full_name',
                __('Nama lengkap tidak boleh lebih dari 100 karakter', 'wp-app-core')
            );
        }

        // Validate department if provided
        if (!empty($staff_data['department'])) {
            $validation = $this->validateDepartment($staff_data['department']);
            if (is_wp_error($validation)) {
                return $validation;
            }
        }

        // Validate hire_date if provided
        if (!empty($staff_data['hire_date'])) {
            if (!$this->validateDate($staff_data['hire_date'])) {
                return new \WP_Error(
                    'invalid_hire_date',
                    __('Format tanggal hire_date tidak valid (gunakan YYYY-MM-DD)', 'wp-app-core')
                );
            }
        }

        // Validate phone if provided
        if (!empty($staff_data['phone'])) {
            $validation = $this->validatePhone($staff_data['phone']);
            if (is_wp_error($validation)) {
                return $validation;
            }
        }

        return true;
    }

    /**
     * Validate staff update data
     *
     * @param int $staff_id Staff ID
     * @param array $staff_data Staff data to validate
     * @return bool|\WP_Error True if valid or WP_Error with reason
     */
    public function validateStaffUpdate(int $staff_id, array $staff_data) {
        // Check if staff exists
        $staff = $this->staff_model->find($staff_id);
        if (!$staff) {
            return new \WP_Error(
                'staff_not_found',
                __('Staff tidak ditemukan', 'wp-app-core')
            );
        }

        // Validate full_name if provided
        if (isset($staff_data['full_name'])) {
            if (empty($staff_data['full_name'])) {
                return new \WP_Error(
                    'empty_full_name',
                    __('Nama lengkap tidak boleh kosong', 'wp-app-core')
                );
            }

            if (strlen($staff_data['full_name']) > 100) {
                return new \WP_Error(
                    'invalid_full_name',
                    __('Nama lengkap tidak boleh lebih dari 100 karakter', 'wp-app-core')
                );
            }
        }

        // Validate department if provided
        if (isset($staff_data['department']) && !empty($staff_data['department'])) {
            $validation = $this->validateDepartment($staff_data['department']);
            if (is_wp_error($validation)) {
                return $validation;
            }
        }

        // Validate hire_date if provided
        if (isset($staff_data['hire_date']) && !empty($staff_data['hire_date'])) {
            if (!$this->validateDate($staff_data['hire_date'])) {
                return new \WP_Error(
                    'invalid_hire_date',
                    __('Format tanggal hire_date tidak valid (gunakan YYYY-MM-DD)', 'wp-app-core')
                );
            }
        }

        // Validate phone if provided
        if (isset($staff_data['phone']) && !empty($staff_data['phone'])) {
            $validation = $this->validatePhone($staff_data['phone']);
            if (is_wp_error($validation)) {
                return $validation;
            }
        }

        return true;
    }

    /**
     * Validate department
     *
     * @param string $department Department name
     * @return bool|\WP_Error True if valid or WP_Error
     */
    public function validateDepartment(string $department) {
        if (strlen($department) > 50) {
            return new \WP_Error(
                'invalid_department',
                __('Nama department tidak boleh lebih dari 50 karakter', 'wp-app-core')
            );
        }

        // Allowed departments (optional - bisa disesuaikan)
        $allowed_departments = apply_filters('wp_app_core_allowed_departments', [
            'IT',
            'Finance',
            'HR',
            'Marketing',
            'Operations',
            'Sales',
            'Support',
            'Management',
        ]);

        // Skip validation if no allowed departments defined
        if (empty($allowed_departments)) {
            return true;
        }

        if (!in_array($department, $allowed_departments)) {
            return new \WP_Error(
                'invalid_department',
                sprintf(
                    __('Department harus salah satu dari: %s', 'wp-app-core'),
                    implode(', ', $allowed_departments)
                )
            );
        }

        return true;
    }

    /**
     * Validate phone number
     *
     * @param string $phone Phone number
     * @return bool|\WP_Error True if valid or WP_Error
     */
    public function validatePhone(string $phone) {
        if (strlen($phone) > 20) {
            return new \WP_Error(
                'invalid_phone',
                __('Nomor telepon tidak boleh lebih dari 20 karakter', 'wp-app-core')
            );
        }

        // Basic phone validation (numbers, +, -, space, parentheses)
        if (!preg_match('/^[\d\s\+\-\(\)]+$/', $phone)) {
            return new \WP_Error(
                'invalid_phone_format',
                __('Format nomor telepon tidak valid', 'wp-app-core')
            );
        }

        return true;
    }

    /**
     * Validate date format
     *
     * @param string $date Date string
     * @param string $format Date format (default: Y-m-d)
     * @return bool True if valid
     */
    private function validateDate(string $date, string $format = 'Y-m-d'): bool {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Check if user can view staff
     *
     * @param int|null $user_id User ID (null for current user)
     * @return bool True if allowed
     */
    public function canViewStaff(?int $user_id = null): bool {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        // Administrator always can
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Check platform staff view capability
        if (user_can($user_id, 'view_platform_staff')) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can create staff
     *
     * @param int|null $user_id User ID (null for current user)
     * @return bool True if allowed
     */
    public function canCreateStaff(?int $user_id = null): bool {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        // Administrator always can
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Check platform staff create capability
        if (user_can($user_id, 'create_platform_staff')) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can edit staff
     *
     * @param int $staff_id Staff ID
     * @param int|null $user_id User ID (null for current user)
     * @return bool True if allowed
     */
    public function canEditStaff(int $staff_id, ?int $user_id = null): bool {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        // Administrator always can
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Check platform staff edit capability
        if (user_can($user_id, 'edit_platform_staff')) {
            return true;
        }

        // Users can edit their own profile
        $staff = $this->staff_model->find($staff_id);
        if ($staff && $staff->user_id === $user_id) {
            if (user_can($user_id, 'edit_own_platform_staff')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user can delete staff
     *
     * @param int $staff_id Staff ID
     * @param int|null $user_id User ID (null for current user)
     * @return bool True if allowed
     */
    public function canDeleteStaff(int $staff_id, ?int $user_id = null): bool {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        // Administrator always can
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Check platform staff delete capability
        if (user_can($user_id, 'delete_platform_staff')) {
            return true;
        }

        // Users cannot delete their own profile (security measure)
        return false;
    }

    /**
     * Check if user can view statistics
     *
     * @param int|null $user_id User ID (null for current user)
     * @return bool True if allowed
     */
    public function canViewStaffStats(?int $user_id = null): bool {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        // Administrator always can
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        // Check view statistics capability
        if (user_can($user_id, 'view_platform_staff_stats')) {
            return true;
        }

        // Anyone who can view staff can view stats
        if ($this->canViewStaff($user_id)) {
            return true;
        }

        return false;
    }
}
