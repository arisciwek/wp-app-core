<?php
/**
 * Platform Demo Data Generator
 *
 * @package     WP_App_Core
 * @subpackage  Database/Demo
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Database/Demo/PlatformDemoData.php
 *
 * Description: Generate demo data untuk platform staff users.
 *              Creates WordPress users dan platform staff records.
 *              20 users total across 7 platform roles.
 *
 * Methods:
 * - generate()           : Generate all platform staff users
 * - deleteAll()          : Delete all platform staff demo data
 * - generateSingleUser() : Generate single user
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial creation
 * - Bulk user generation
 * - Platform staff record creation
 */

namespace WPAppCore\Database\Demo;

use WPAppCore\Database\Demo\WPUserGenerator;
use WPAppCore\Database\Demo\Data\PlatformUsersData;

defined('ABSPATH') || exit;

class PlatformDemoData {
    /**
     * Generate all platform staff demo users
     *
     * @return array Result array with success status and details
     */
    public static function generate() {
        global $wpdb;

        $results = [
            'success' => false,
            'message' => '',
            'users_created' => 0,
            'staff_records_created' => 0,
            'errors' => []
        ];

        try {
            $wpdb->query('START TRANSACTION');

            $platform_staff_table = $wpdb->prefix . 'app_platform_staff';
            $user_data = PlatformUsersData::getData();

            foreach ($user_data as $data) {
                // Create WordPress user
                $user_result = WPUserGenerator::createUser(
                    $data['id'],
                    $data['username'],
                    $data['display_name'],
                    $data['email'],
                    $data['roles'],
                    'password123' // Default password untuk demo
                );

                if (is_wp_error($user_result)) {
                    $results['errors'][] = sprintf(
                        'Failed to create user %s: %s',
                        $data['username'],
                        $user_result->get_error_message()
                    );
                    continue;
                }

                $results['users_created']++;

                // Create platform staff record
                $staff_data = [
                    'user_id' => $data['id'],
                    'employee_id' => $data['employee_id'],
                    'full_name' => $data['display_name'],
                    'department' => $data['department'],
                    'hire_date' => $data['hire_date'],
                    'phone' => $data['phone'],
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ];

                $inserted = $wpdb->insert(
                    $platform_staff_table,
                    $staff_data,
                    ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
                );

                if ($inserted === false) {
                    $results['errors'][] = sprintf(
                        'Failed to create staff record for user ID %d: %s',
                        $data['id'],
                        $wpdb->last_error
                    );
                    continue;
                }

                $results['staff_records_created']++;
            }

            // Commit transaction
            $wpdb->query('COMMIT');

            $results['success'] = true;
            $results['message'] = sprintf(
                'Successfully generated %d platform staff users with %d staff records.',
                $results['users_created'],
                $results['staff_records_created']
            );

            if (!empty($results['errors'])) {
                $results['message'] .= sprintf(
                    ' %d errors occurred during generation.',
                    count($results['errors'])
                );
            }

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            $results['success'] = false;
            $results['message'] = 'Failed to generate platform staff: ' . $e->getMessage();
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Delete all platform staff demo data
     *
     * @return array Result array with success status
     */
    public static function deleteAll() {
        global $wpdb;

        $results = [
            'success' => false,
            'message' => '',
            'users_deleted' => 0,
            'staff_records_deleted' => 0,
            'errors' => []
        ];

        try {
            $wpdb->query('START TRANSACTION');

            $platform_staff_table = $wpdb->prefix . 'app_platform_staff';

            // Delete staff records first
            $deleted_records = $wpdb->query($wpdb->prepare(
                "DELETE FROM {$platform_staff_table} WHERE user_id >= %d AND user_id <= %d",
                PlatformUsersData::USER_ID_START,
                PlatformUsersData::USER_ID_END
            ));

            if ($deleted_records === false) {
                throw new \Exception('Failed to delete staff records: ' . $wpdb->last_error);
            }

            $results['staff_records_deleted'] = $deleted_records;

            // Delete WordPress users
            $user_delete_result = WPUserGenerator::deleteUserRange(
                PlatformUsersData::USER_ID_START,
                PlatformUsersData::USER_ID_END
            );

            $results['users_deleted'] = $user_delete_result['deleted'];
            if (!empty($user_delete_result['failed'])) {
                $results['errors'][] = sprintf(
                    'Failed to delete %d users: IDs %s',
                    count($user_delete_result['failed']),
                    implode(', ', $user_delete_result['failed'])
                );
            }

            $wpdb->query('COMMIT');

            $results['success'] = true;
            $results['message'] = sprintf(
                'Successfully deleted %d users and %d staff records.',
                $results['users_deleted'],
                $results['staff_records_deleted']
            );

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            $results['success'] = false;
            $results['message'] = 'Failed to delete platform staff: ' . $e->getMessage();
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Generate single platform staff user
     *
     * @param int $user_id User ID to generate
     * @return array Result array
     */
    public static function generateSingleUser($user_id) {
        global $wpdb;

        $user_data = PlatformUsersData::getData();

        if (!isset($user_data[$user_id])) {
            return [
                'success' => false,
                'message' => sprintf('User ID %d not found in demo data', $user_id)
            ];
        }

        $data = $user_data[$user_id];

        try {
            $wpdb->query('START TRANSACTION');

            // Create WordPress user
            $user_result = WPUserGenerator::createUser(
                $data['id'],
                $data['username'],
                $data['display_name'],
                $data['email'],
                $data['roles']
            );

            if (is_wp_error($user_result)) {
                throw new \Exception($user_result->get_error_message());
            }

            // Create platform staff record
            $platform_staff_table = $wpdb->prefix . 'app_platform_staff';
            $staff_data = [
                'user_id' => $data['id'],
                'employee_id' => $data['employee_id'],
                'full_name' => $data['display_name'],
                'department' => $data['department'],
                'hire_date' => $data['hire_date'],
                'phone' => $data['phone'],
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ];

            $inserted = $wpdb->insert(
                $platform_staff_table,
                $staff_data,
                ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
            );

            if ($inserted === false) {
                throw new \Exception('Failed to create staff record: ' . $wpdb->last_error);
            }

            $wpdb->query('COMMIT');

            return [
                'success' => true,
                'message' => sprintf('Successfully created user %s', $data['username'])
            ];

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return [
                'success' => false,
                'message' => 'Failed to create user: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get statistics about platform staff demo data
     *
     * @return array Statistics array
     */
    public static function getStatistics() {
        global $wpdb;

        $platform_staff_table = $wpdb->prefix . 'app_platform_staff';

        $total_users = count(PlatformUsersData::getData());

        $existing_users = 0;
        for ($id = PlatformUsersData::USER_ID_START; $id <= PlatformUsersData::USER_ID_END; $id++) {
            if (WPUserGenerator::userExists($id)) {
                $existing_users++;
            }
        }

        $existing_staff_records = $wpdb->get_var($wpdb->prepare(
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
