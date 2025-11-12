<?php
/**
 * Platform Demo Data Generator
 *
 * @package     WP_App_Core
 * @subpackage  Database/Demo
 * @version     2.0.1
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Database/Demo/PlatformDemoData.php
 *
 * Description: Generate demo data untuk platform staff users.
 *              REFACTORED: Now extends AbstractDemoData (TODO-1207).
 *              Creates WordPress users dan platform staff records.
 *              20 users total across 7 platform roles.
 *              Uses filter hooks for static ID injection.
 *
 * Methods:
 * - run()            : Generate all platform staff (inherited from abstract)
 * - getLastResults() : Get detailed generation results
 * - deleteAll()      : Delete all platform staff demo data
 * - getStatistics()  : Get statistics about demo data
 *
 * Changelog:
 * 2.0.1 - 2025-01-12 (TODO-1207)
 * - Fixed: Null userGenerator error in deleteAll() and getStatistics()
 * - AbstractDemoData now initializes models immediately if plugins_loaded fired
 * - No longer needs ensureModelsInitialized() helper method
 * 2.0.0 - 2025-01-12 (TODO-1207)
 * - BREAKING: Now extends AbstractDemoData for consistency
 * - Converted from static methods to instance methods
 * - Implements initModels(), validate(), generate() abstract methods
 * - Uses abstract's transaction wrapper (cleaner code)
 * - Added getLastResults() for detailed results
 * - Reduced from 324 lines to ~250 lines (23% reduction)
 * - Pattern now consistent with CustomerDemoData, AgencyDemoData
 *
 * 1.0.8 - 2025-11-01 (TODO-1190: Static ID Hook Pattern)
 * - Added wp_app_core_platform_staff_before_insert filter hook
 * - Changed to use WPUserGenerator instance method (generateUser)
 * - Pass _demo_entity_id for entity static ID injection
 * - Follows wp-agency/wp-customer pattern
 *
 * 1.0.0 - 2025-10-19
 * - Initial creation
 * - Bulk user generation
 * - Platform staff record creation
 */

namespace WPAppCore\Database\Demo;

use WPAppCore\Database\Demo\AbstractDemoData;
use WPAppCore\Database\Demo\WPUserGenerator;
use WPAppCore\Database\Demo\Data\PlatformUsersData;

defined('ABSPATH') || exit;

class PlatformDemoData extends AbstractDemoData {
    /**
     * Platform staff model
     * @var \WPAppCore\Models\Platform\PlatformStaffModel
     */
    protected $staffModel;

    /**
     * User generator
     * @var WPUserGenerator
     */
    protected $userGenerator;

    /**
     * Last generation results
     * @var array
     */
    protected $results = [
        'success' => false,
        'message' => '',
        'users_created' => 0,
        'staff_records_created' => 0,
        'errors' => []
    ];

    /**
     * Initialize models
     * Called by parent constructor (immediately or via plugins_loaded hook)
     */
    public function initModels(): void {
        $this->userGenerator = new WPUserGenerator();

        if (class_exists('\WPAppCore\Models\Platform\PlatformStaffModel')) {
            $this->staffModel = new \WPAppCore\Models\Platform\PlatformStaffModel();
        }
    }

    /**
     * Validate before generation
     *
     * @return bool True if validation passes
     */
    protected function validate(): bool {
        // Check user capability
        if (!current_user_can('manage_options')) {
            $this->results['errors'][] = 'Permission denied: requires manage_options capability';
            return false;
        }

        // Check if staff model is initialized
        if (!$this->staffModel) {
            $this->results['errors'][] = 'PlatformStaffModel not available';
            return false;
        }

        return true;
    }

    /**
     * Generate platform staff demo data
     * Implements abstract method
     *
     * @return void
     */
    protected function generate(): void {
        // Reset results
        $this->results = [
            'success' => true,
            'message' => '',
            'users_created' => 0,
            'staff_records_created' => 0,
            'errors' => []
        ];

        // Add filter hook to inject static entity IDs for demo data
        add_filter('wp_app_core_platform_staff_before_insert', function($insert_data, $data) {
            if (isset($data['_demo_entity_id'])) {
                $insert_data['id'] = $data['_demo_entity_id'];
                $this->debug("Injecting static staff ID: {$insert_data['id']}");
            }
            return $insert_data;
        }, 10, 2);

        $user_data = PlatformUsersData::getData();

        foreach ($user_data as $data) {
            // Create WordPress user
            try {
                $user_id = $this->userGenerator->generateUser([
                    'id' => $data['id'],
                    'username' => $data['username'],
                    'display_name' => $data['display_name'],
                    'email' => $data['email'],
                    'roles' => $data['roles']
                ]);

                $this->results['users_created']++;
                $this->debug("Created user {$data['username']} with ID {$user_id}");

            } catch (\Exception $e) {
                $this->results['errors'][] = sprintf(
                    'Failed to create user %s: %s',
                    $data['username'],
                    $e->getMessage()
                );
                continue;
            }

            // Create platform staff record via Model (with static ID injection)
            $staff_data = [
                'user_id' => $data['id'],
                'employee_id' => $data['employee_id'],
                'full_name' => $data['display_name'],
                'department' => $data['department'],
                'hire_date' => $data['hire_date'],
                'phone' => $data['phone'],
                '_demo_entity_id' => $data['id']  // Pass for hook injection
            ];

            $staff_id = $this->staffModel->create($staff_data);

            if (!$staff_id) {
                $this->results['errors'][] = sprintf(
                    'Failed to create staff record for user ID %d: %s',
                    $data['id'],
                    $this->wpdb->last_error
                );
                continue;
            }

            $this->results['staff_records_created']++;
            $this->debug("Created staff record ID {$staff_id}");
        }

        // Build result message
        $this->results['message'] = sprintf(
            'Successfully generated %d platform staff users with %d staff records.',
            $this->results['users_created'],
            $this->results['staff_records_created']
        );

        if (!empty($this->results['errors'])) {
            $this->results['message'] .= sprintf(
                ' %d errors occurred during generation.',
                count($this->results['errors'])
            );
        }

        $this->debug($this->results['message']);
    }

    /**
     * Get last generation results
     * Use after calling run()
     *
     * @return array Detailed results array
     */
    public function getLastResults(): array {
        return $this->results;
    }

    /**
     * Delete all platform staff demo data
     *
     * @return array Result array with success status
     */
    public function deleteAll(): array {
        $results = [
            'success' => false,
            'message' => '',
            'users_deleted' => 0,
            'staff_records_deleted' => 0,
            'errors' => []
        ];

        try {
            $this->wpdb->query('START TRANSACTION');

            $platform_staff_table = $this->wpdb->prefix . 'app_platform_staff';

            // Delete staff records first
            $deleted_records = $this->wpdb->query($this->wpdb->prepare(
                "DELETE FROM {$platform_staff_table} WHERE user_id >= %d AND user_id <= %d",
                PlatformUsersData::USER_ID_START,
                PlatformUsersData::USER_ID_END
            ));

            if ($deleted_records === false) {
                throw new \Exception('Failed to delete staff records: ' . $this->wpdb->last_error);
            }

            $results['staff_records_deleted'] = $deleted_records;
            $this->debug("Deleted {$deleted_records} staff records");

            // Delete WordPress users
            $user_ids = range(PlatformUsersData::USER_ID_START, PlatformUsersData::USER_ID_END);
            $deleted_users = $this->userGenerator->deleteUsers($user_ids);

            $results['users_deleted'] = $deleted_users;
            $this->debug("Deleted {$deleted_users} users");

            $this->wpdb->query('COMMIT');

            $results['success'] = true;
            $results['message'] = sprintf(
                'Successfully deleted %d users and %d staff records.',
                $results['users_deleted'],
                $results['staff_records_deleted']
            );

        } catch (\Exception $e) {
            $this->wpdb->query('ROLLBACK');
            $results['success'] = false;
            $results['message'] = 'Failed to delete platform staff: ' . $e->getMessage();
            $results['errors'][] = $e->getMessage();
            $this->debug("Delete failed: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Get statistics about platform staff demo data
     *
     * @return array Statistics array
     */
    public function getStatistics(): array {
        $platform_staff_table = $this->wpdb->prefix . 'app_platform_staff';

        $total_users = count(PlatformUsersData::getData());

        $existing_users = 0;
        for ($id = PlatformUsersData::USER_ID_START; $id <= PlatformUsersData::USER_ID_END; $id++) {
            if (get_user_by('ID', $id)) {
                $existing_users++;
            }
        }

        $existing_staff_records = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$platform_staff_table} WHERE user_id >= %d AND user_id <= %d",
            PlatformUsersData::USER_ID_START,
            PlatformUsersData::USER_ID_END
        ));

        return [
            'total_users' => $total_users,
            'existing_users' => $existing_users,
            'existing_staff_records' => (int) $existing_staff_records,
            'users_to_create' => $total_users - $existing_users
        ];
    }
}
