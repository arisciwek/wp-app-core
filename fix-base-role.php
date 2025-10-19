<?php
/**
 * Quick Fix Script: Add base role to platform users
 *
 * Run this once via: wp eval-file fix-base-role.php
 */

// Load WordPress
require_once __DIR__ . '/../../../wp-load.php';

// Load RoleManager
require_once __DIR__ . '/includes/class-role-manager.php';

echo "=== Platform Base Role Fix Script ===\n\n";

// 1. Remove old platform_staff role if exists
$old_role = get_role('platform_staff');
if ($old_role) {
    remove_role('platform_staff');
    echo "✓ Removed old platform_staff role\n";
}

// 2. Create base role with 'read' capability
add_role('platform_staff', 'Platform Staff', ['read' => true]);
echo "✓ Created platform_staff role with 'read' capability\n";

// 3. Verify role
$role = get_role('platform_staff');
if ($role && $role->has_cap('read')) {
    echo "✓ Verified: platform_staff has 'read' capability\n";
} else {
    echo "✗ ERROR: platform_staff doesn't have 'read' capability\n";
    exit(1);
}

// 4. Get all platform admin roles
$admin_roles = WP_App_Core_Role_Manager::getAdminRoles();
$users_updated = 0;
$users_total = 0;

echo "\n=== Adding base role to platform users ===\n";

foreach ($admin_roles as $admin_role => $role_name) {
    $users = get_users(['role' => $admin_role]);

    foreach ($users as $user) {
        $users_total++;

        // Check if user already has base role
        if (!in_array('platform_staff', $user->roles)) {
            // Add base role (this will not remove existing roles)
            $user->add_role('platform_staff');
            $users_updated++;
            echo "✓ Added platform_staff to user {$user->ID} ({$user->user_login})\n";
        } else {
            echo "- User {$user->ID} ({$user->user_login}) already has platform_staff\n";
        }
    }
}

echo "\n=== Summary ===\n";
echo "Total platform users: {$users_total}\n";
echo "Users updated: {$users_updated}\n";

// 5. Flush cache to ensure changes take effect
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "\n✓ Cache flushed - role changes active\n";
} else {
    echo "\n⚠ Warning: wp_cache_flush not available, please run 'wp cache flush' manually\n";
}

echo "\n✓ Base role fix completed!\n";

// 6. Show sample user
if ($users_total > 0) {
    $sample_users = get_users(['role' => 'platform_finance', 'number' => 1]);
    if (!empty($sample_users)) {
        $sample = $sample_users[0];
        echo "\nSample user ({$sample->user_login}) roles: " . implode(', ', $sample->roles) . "\n";
    }
}

echo "\n📌 IMPORTANT: If users still can't access wp-admin:\n";
echo "   1. Run: wp cache flush\n";
echo "   2. Clear browser cache/cookies\n";
echo "   3. Logout and login again\n";
