<?php
/**
 * WordPress User Generator for Demo Data
 *
 * @package     WP_App_Core
 * @subpackage  Database/Demo
 * @version     1.0.8
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Database/Demo/WPUserGenerator.php
 *
 * Description: Utility class untuk generate WordPress users
 *              dengan ID tertentu dan role tertentu.
 *              Used by demo data generators.
 *              Follows wp-agency/wp-customer hook pattern.
 *
 * Methods:
 * - generateUser() : Create WordPress user with specific ID and roles via wp_insert_user
 * - validate()     : Check CLI/capability for user creation
 * - deleteUsers()  : Delete demo users by IDs
 *
 * Changelog:
 * 1.0.8 - 2025-11-01 (TODO-1190: Static ID Hook Pattern)
 * - BREAKING: Changed to instance-based class (from static)
 * - Added validate() with CLI detection for script execution
 * - Added generateUser() using wp_insert_user + ID update pattern
 * - Added proper cache clearing after user operations
 * - Follows wp-agency WPUserGenerator v1.0.8 pattern exactly
 * - Added wp_app_core_demo_user meta for safety
 * - Support multiple roles via add_role()
 *
 * 1.0.0 - 2025-10-19
 * - Initial creation (DEPRECATED static methods)
 */

namespace WPAppCore\Database\Demo;

defined('ABSPATH') || exit;

class WPUserGenerator {
    private static $usedUsernames = [];

    protected function validate(): bool {
        // Allow CLI/script execution for demo data generation
        if ((defined('WP_CLI') && WP_CLI) || (PHP_SAPI === 'cli')) {
            return true; // CLI/script always allowed for demo data
        }

        // For web requests, check user capability
        if (!current_user_can('create_users')) {
            $this->debug('Current user cannot create users');
            return false;
        }
        return true;
    }

    public function generateUser($data) {
        global $wpdb;

        if (!$this->validate()) {
            throw new \Exception('Validation failed: cannot create users');
        }

        $this->debug("=== generateUser called ===");
        $this->debug("Input data: " . json_encode($data));

        // 1. Check if user with this ID already exists using DIRECT DATABASE QUERY
        $existing_user_row = $wpdb->get_row($wpdb->prepare(
            "SELECT ID, user_login, display_name FROM {$wpdb->users} WHERE ID = %d",
            $data['id']
        ));

        $this->debug("Checking existing user with ID {$data['id']}: " . ($existing_user_row ? 'EXISTS' : 'NOT FOUND'));

        if ($existing_user_row) {
            $this->debug("User exists - Display Name: {$existing_user_row->display_name}");

            // Update display name if different
            if ($existing_user_row->display_name !== $data['display_name']) {
                wp_update_user([
                    'ID' => $data['id'],
                    'display_name' => $data['display_name']
                ]);
                $this->debug("Updated user display name: {$data['display_name']}");
            }

            // Update roles if provided as array
            if (isset($data['roles']) && is_array($data['roles'])) {
                $this->updateUserRoles($data['id'], $data['roles']);
            }

            $this->debug("Returning existing user ID: {$data['id']}");
            return $data['id'];
        }

        // 2. Use username from data or generate new one
        $username = isset($data['username'])
            ? $data['username']
            : $this->generateUniqueUsername($data['display_name']);

        $this->debug("Username to use: {$username}");

        // Check if username already exists with DIFFERENT ID
        $username_exists = username_exists($username);
        $this->debug("Username '{$username}' exists check: " . ($username_exists ? "YES (ID: {$username_exists})" : 'NO'));

        if ($username_exists && $username_exists != $data['id']) {
            $this->debug("WARNING: Username '{$username}' exists with ID {$username_exists}, need ID {$data['id']}");
            $this->debug("Deleting existing user ID {$username_exists} to re-create with static ID {$data['id']}");

            // Delete existing user to make room for static ID
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            $delete_result = wp_delete_user($username_exists);

            if ($delete_result) {
                $this->debug("Successfully deleted user ID {$username_exists}");
            } else {
                throw new \Exception("Cannot delete existing user '{$username}' (ID: {$username_exists})");
            }
        }

        // 3. Insert new user via wp_insert_user() for proper WordPress integration
        $base_role = isset($data['roles'][0]) ? $data['roles'][0] : 'subscriber';
        $email = isset($data['email']) ? $data['email'] : ($username . '@example.com');

        $user_data_to_insert = [
            'user_login' => $username,
            'user_pass' => 'Demo_Data-2025',  // Will be hashed by wp_insert_user()
            'user_email' => $email,
            'display_name' => $data['display_name'],
            'role' => $base_role  // Base role (first role only)
        ];

        $this->debug("Creating user via wp_insert_user()");

        // Create user with wp_insert_user() (auto-generates ID)
        $user_id = wp_insert_user($user_data_to_insert);

        if (is_wp_error($user_id)) {
            $error_message = $user_id->get_error_message();
            $this->debug("ERROR: wp_insert_user failed: {$error_message}");
            throw new \Exception($error_message);
        }

        $this->debug("User created successfully with auto ID: {$user_id}");

        // 4. Add additional roles (platform pattern: base + admin role)
        if (isset($data['roles']) && is_array($data['roles'])) {
            $user = get_user_by('ID', $user_id);
            if ($user) {
                foreach ($data['roles'] as $role) {
                    if ($role !== $base_role) {  // Skip base role already set
                        $user->add_role($role);
                        $this->debug("Added role: {$role}");
                    }
                }
            }
        }

        // 5. Now update the ID to match static ID from data
        $this->debug("Updating user ID from {$user_id} to static ID: {$data['id']}");

        $wpdb->query('SET FOREIGN_KEY_CHECKS=0');

        // Update wp_users ID
        $result_users = $wpdb->update(
            $wpdb->users,
            ['ID' => $data['id']],
            ['ID' => $user_id],
            ['%d'],
            ['%d']
        );

        // Update wp_usermeta user_id references
        $result_meta = $wpdb->update(
            $wpdb->usermeta,
            ['user_id' => $data['id']],
            ['user_id' => $user_id],
            ['%d'],
            ['%d']
        );

        $wpdb->query('SET FOREIGN_KEY_CHECKS=1');

        $this->debug("ID update results - users: {$result_users}, usermeta: {$result_meta}");

        if ($result_users === false || $result_meta === false) {
            $this->debug("ERROR: Failed to update user ID - " . $wpdb->last_error);
            // Delete the created user
            wp_delete_user($user_id);
            throw new \Exception("Failed to set static user ID: " . $wpdb->last_error);
        }

        // Clear WordPress user caches for both old and new IDs
        $old_user_id = $user_id;
        $new_user_id = $data['id'];

        clean_user_cache($old_user_id);  // Clear cache for old auto ID
        clean_user_cache($new_user_id);  // Clear cache for new static ID

        // Also clear the email-to-ID mapping cache (if exists)
        wp_cache_delete($username, 'userlogins');
        wp_cache_delete($username, 'userslugs');
        wp_cache_delete(md5($email), 'useremail');

        $this->debug("Cleared WordPress user caches for IDs {$old_user_id} and {$new_user_id}");

        $user_id = $data['id'];
        $this->debug("User ID successfully changed to static ID: {$user_id}");

        // Add demo user meta
        update_user_meta($user_id, 'wp_app_core_demo_user', '1');
        $this->debug("Added demo user meta");

        $roles_string = isset($data['roles']) ? implode(', ', $data['roles']) : $base_role;
        $this->debug("=== User creation completed ===");
        $this->debug("Created user: {$data['display_name']} with ID: {$user_id} and roles: {$roles_string}");

        return $user_id;
    }

    /**
     * Update user roles (for existing users)
     */
    private function updateUserRoles(int $user_id, array $roles): void {
        global $wpdb;

        // Build capabilities array with all roles
        $capabilities = [];
        foreach ($roles as $role) {
            $capabilities[$role] = true;
        }

        // Update capabilities in usermeta
        $wpdb->update(
            $wpdb->usermeta,
            ['meta_value' => serialize($capabilities)],
            [
                'user_id' => $user_id,
                'meta_key' => $wpdb->prefix . 'capabilities'
            ],
            ['%s'],
            ['%d', '%s']
        );

        $roles_string = implode(', ', $roles);
        $this->debug("Updated roles for user {$user_id}: {$roles_string}");
    }

    private function generateUniqueUsername($display_name) {
        $base_username = strtolower(str_replace(' ', '_', $display_name));
        $username = $base_username;
        $suffix = 1;

        while (in_array($username, self::$usedUsernames) || username_exists($username)) {
            $username = $base_username . $suffix;
            $suffix++;
        }

        self::$usedUsernames[] = $username;
        return $username;
    }

    /**
     * Delete demo users by IDs
     *
     * @param array $user_ids Array of user IDs to delete
     * @param bool $force_delete Force delete without demo user check (for development)
     * @return int Number of users deleted
     */
    public function deleteUsers(array $user_ids, bool $force_delete = false): int {
        if (empty($user_ids)) {
            return 0;
        }

        $this->debug("=== Deleting demo users ===");
        $this->debug("User IDs to delete: " . json_encode($user_ids));
        $this->debug("Force delete mode: " . ($force_delete ? 'YES' : 'NO'));

        $deleted_count = 0;

        foreach ($user_ids as $user_id) {
            // Check if user exists
            $existing_user = get_user_by('ID', $user_id);

            if (!$existing_user) {
                $this->debug("User ID {$user_id} not found, skipping");
                continue;
            }

            // Skip ID 1 (main admin) for safety
            if ($user_id == 1) {
                $this->debug("User ID 1 is main admin, skipping for safety");
                continue;
            }

            // Check if this is a demo user (unless force delete)
            if (!$force_delete) {
                $is_demo = get_user_meta($user_id, 'wp_app_core_demo_user', true);

                if ($is_demo !== '1') {
                    $this->debug("User ID {$user_id} is not a demo user, skipping for safety");
                    continue;
                }
            } else {
                $this->debug("Force deleting user ID {$user_id} ({$existing_user->user_login})");
            }

            // Use WordPress function to delete user (deletes meta automatically)
            require_once(ABSPATH . 'wp-admin/includes/user.php');

            $result = wp_delete_user($user_id);

            if ($result) {
                $deleted_count++;
                $this->debug("Deleted user ID {$user_id} ({$existing_user->user_login})");
            } else {
                $this->debug("Failed to delete user ID {$user_id}");
            }
        }

        $this->debug("Deleted {$deleted_count} users");

        return $deleted_count;
    }

    private function debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[WPAppCore\WPUserGenerator] ' . $message);
        }
    }

    // ====== LEGACY STATIC METHODS (DEPRECATED - For backward compatibility) ======
    // These methods are kept for backward compatibility with existing code.
    // New code should use instance methods (generateUser, deleteUsers)

    /**
     * @deprecated 1.0.8 Use generateUser() instance method instead
     */
    public static function createUser($user_id, $username, $display_name, $email, $roles = [], $password = 'password123') {
        $generator = new self();
        return $generator->generateUser([
            'id' => $user_id,
            'username' => $username,
            'display_name' => $display_name,
            'email' => $email,
            'roles' => $roles
        ]);
    }

    /**
     * Check if user exists by ID
     *
     * @param int $user_id User ID to check
     * @return bool True if user exists
     */
    public static function userExists($user_id) {
        $user = get_user_by('ID', $user_id);
        return $user !== false;
    }

    /**
     * Delete user by ID
     *
     * @param int $user_id User ID to delete
     * @return bool True on success, false on failure
     */
    public static function deleteUser($user_id) {
        if (!self::userExists($user_id)) {
            return false;
        }

        // WordPress function to delete user
        // null = no reassign of content
        return wp_delete_user($user_id, null);
    }

    /**
     * Delete multiple users by ID range
     *
     * @param int $start_id Start of ID range
     * @param int $end_id   End of ID range
     * @return array Array of results ['deleted' => count, 'failed' => [ids]]
     */
    public static function deleteUserRange($start_id, $end_id) {
        $deleted = 0;
        $failed = [];

        for ($id = $start_id; $id <= $end_id; $id++) {
            if (self::userExists($id)) {
                if (self::deleteUser($id)) {
                    $deleted++;
                } else {
                    $failed[] = $id;
                }
            }
        }

        return [
            'deleted' => $deleted,
            'failed' => $failed
        ];
    }

    /**
     * Get user by ID
     *
     * @param int $user_id User ID
     * @return WP_User|false User object or false
     */
    public static function getUser($user_id) {
        return get_user_by('ID', $user_id);
    }

    /**
     * Check if username is available
     *
     * @param string $username Username to check
     * @return bool True if available
     */
    public static function isUsernameAvailable($username) {
        return !username_exists($username);
    }

    /**
     * Check if email is available
     *
     * @param string $email Email to check
     * @return bool True if available
     */
    public static function isEmailAvailable($email) {
        return !email_exists($email);
    }
}
