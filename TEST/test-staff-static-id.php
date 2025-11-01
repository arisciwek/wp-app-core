#!/usr/bin/env php
<?php
/**
 * Test Platform Staff Static ID Generation
 *
 * Verifies that platform staff are created with static IDs via hook
 *
 * Path: /wp-app-core/TEST/test-staff-static-id.php
 *
 * Tests:
 * - WordPress user creation with static IDs
 * - Platform staff entity creation with static IDs
 * - Filter hook functionality
 * - Full demo data generation flow
 */

require_once('/home/mkt01/Public/wppm/public_html/wp-load.php');

echo "=== Testing Platform Staff Static ID Generation ===\n\n";

// Get demo user IDs from PlatformUsersData
$user_data_class = new \WPAppCore\Database\Demo\Data\PlatformUsersData();
$user_data = $user_data_class::getData();
$demo_user_ids = array_keys($user_data);

echo "Demo user IDs: " . implode(', ', $demo_user_ids) . "\n";
echo "Total demo users: " . count($demo_user_ids) . "\n\n";

// Clean up existing demo data
global $wpdb;

// Delete existing demo staff via Model (uses hooks if any)
$staff_model = new \WPAppCore\Models\Platform\PlatformStaffModel();
$existing_staff = $wpdb->get_results("
    SELECT s.id, s.user_id
    FROM {$wpdb->prefix}app_platform_staff s
    WHERE s.user_id IN (" . implode(',', $demo_user_ids) . ")
");

if (!empty($existing_staff)) {
    echo "Found " . count($existing_staff) . " existing demo staff to delete\n";
    foreach ($existing_staff as $staff) {
        $staff_model->delete($staff->id);
    }
}

// Delete demo users
$user_generator = new \WPAppCore\Database\Demo\WPUserGenerator();
$deleted_count = $user_generator->deleteUsers($demo_user_ids, true);
echo "Deleted {$deleted_count} demo users\n";

// CRITICAL: Flush cache to avoid "user already exists" errors
wp_cache_flush();
echo "Flushed WordPress cache\n\n";

// Generate demo data with static IDs
echo "Generating platform staff with static IDs...\n";
$result = \WPAppCore\Database\Demo\PlatformDemoData::generate();

if ($result['success']) {
    echo "✅ SUCCESS: " . $result['message'] . "\n";
    echo "Users created: {$result['users_created']}\n";
    echo "Staff records created: {$result['staff_records_created']}\n";

    if (!empty($result['errors'])) {
        echo "\n⚠️  Errors encountered:\n";
        foreach ($result['errors'] as $error) {
            echo "  - {$error}\n";
        }
    }
} else {
    echo "❌ FAILED: " . $result['message'] . "\n";
    if (!empty($result['errors'])) {
        echo "Errors:\n";
        foreach ($result['errors'] as $error) {
            echo "  - {$error}\n";
        }
    }
    exit(1);
}

// Verify results
echo "\n=== Verification ===\n";

// Check WordPress users
echo "\nWordPress Users (first 5):\n";
$users = $wpdb->get_results("
    SELECT ID, user_login, display_name
    FROM {$wpdb->users}
    WHERE ID IN (" . implode(',', array_slice($demo_user_ids, 0, 5)) . ")
    ORDER BY ID
");

foreach ($users as $user) {
    echo "  ID: {$user->ID}, Login: {$user->user_login}, Name: {$user->display_name}\n";
}

// Check platform staff
echo "\nPlatform Staff (first 5):\n";
$staff = $wpdb->get_results("
    SELECT id, user_id, employee_id, full_name, department
    FROM {$wpdb->prefix}app_platform_staff
    WHERE user_id IN (" . implode(',', array_slice($demo_user_ids, 0, 5)) . ")
    ORDER BY id
");

foreach ($staff as $s) {
    echo "  Staff ID: {$s->id}, User ID: {$s->user_id}, Employee: {$s->employee_id}, Name: {$s->full_name}, Dept: {$s->department}\n";
}

// Verify ID matching
echo "\n=== ID Verification ===\n";
$expected_ids = array_slice($demo_user_ids, 0, 5);
$actual_user_ids = array_column($users, 'ID');
$actual_staff_ids = array_column($staff, 'user_id');

echo "Expected user IDs: " . implode(', ', $expected_ids) . "\n";
echo "Actual user IDs:   " . implode(', ', $actual_user_ids) . "\n";
echo "Actual staff.user_id: " . implode(', ', $actual_staff_ids) . "\n";

$user_ids_match = ($actual_user_ids == $expected_ids);
$staff_ids_match = ($actual_staff_ids == $expected_ids);

if ($user_ids_match && $staff_ids_match) {
    echo "\n✅ SUCCESS: All IDs match expected values!\n";
} else {
    echo "\n❌ FAIL: ID mismatch detected\n";
    if (!$user_ids_match) {
        echo "  - WordPress user IDs don't match\n";
    }
    if (!$staff_ids_match) {
        echo "  - Platform staff user_id don't match\n";
    }
}

// Statistics
$total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users} WHERE ID IN (" . implode(',', $demo_user_ids) . ")");
$total_staff = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}app_platform_staff WHERE user_id IN (" . implode(',', $demo_user_ids) . ")");

echo "\n=== Statistics ===\n";
echo "Total demo users in database: {$total_users}\n";
echo "Total demo staff in database: {$total_staff}\n";
echo "Expected count: " . count($demo_user_ids) . "\n";

if ($total_users == count($demo_user_ids) && $total_staff == count($demo_user_ids)) {
    echo "✅ All counts match!\n";
} else {
    echo "❌ Count mismatch!\n";
}

echo "\n=== Test Complete ===\n";
