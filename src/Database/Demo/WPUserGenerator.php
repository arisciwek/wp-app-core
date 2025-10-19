<?php
/**
 * WordPress User Generator
 *
 * @package     WP_App_Core
 * @subpackage  Database/Demo
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Database/Demo/WPUserGenerator.php
 *
 * Description: Utility class untuk generate WordPress users
 *              dengan ID tertentu dan role tertentu.
 *              Used by demo data generators.
 *
 * Methods:
 * - createUser() : Create WordPress user with specific ID and roles
 * - userExists() : Check if user exists
 * - deleteUser() : Delete user by ID
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial creation
 * - User generation dengan custom ID
 * - Role assignment support
 */

namespace WPAppCore\Database\Demo;

defined('ABSPATH') || exit;

class WPUserGenerator {
    /**
     * Create WordPress user dengan ID dan role tertentu
     *
     * @param int    $user_id      Target user ID
     * @param string $username     Username (unique)
     * @param string $display_name Display name
     * @param string $email        Email address
     * @param array  $roles        Array of role slugs
     * @param string $password     Password (default: 'password123')
     * @return int|WP_Error        User ID on success, WP_Error on failure
     */
    public static function createUser($user_id, $username, $display_name, $email, $roles = [], $password = 'password123') {
        global $wpdb;

        // Check if user ID already exists
        if (self::userExists($user_id)) {
            return new \WP_Error('user_exists', sprintf('User ID %d already exists', $user_id));
        }

        // Check if username exists
        if (username_exists($username)) {
            return new \WP_Error('username_exists', sprintf('Username "%s" already exists', $username));
        }

        // Check if email exists
        if (email_exists($email)) {
            return new \WP_Error('email_exists', sprintf('Email "%s" already exists', $email));
        }

        // Prepare user data
        $userdata = [
            'user_login'   => $username,
            'user_pass'    => $password,
            'user_email'   => $email,
            'display_name' => $display_name,
            'user_nicename'=> sanitize_title($display_name),
            'role'         => !empty($roles) ? $roles[0] : 'subscriber' // WordPress requires single role in wp_insert_user
        ];

        // Insert user
        $result = wp_insert_user($userdata);

        if (is_wp_error($result)) {
            return $result;
        }

        // Update user ID jika berbeda
        $created_user_id = $result;
        if ($created_user_id !== $user_id) {
            // Update user ID ke ID yang diinginkan
            $wpdb->update(
                $wpdb->users,
                ['ID' => $user_id],
                ['ID' => $created_user_id],
                ['%d'],
                ['%d']
            );

            // Update usermeta references
            $wpdb->update(
                $wpdb->usermeta,
                ['user_id' => $user_id],
                ['user_id' => $created_user_id],
                ['%d'],
                ['%d']
            );

            $created_user_id = $user_id;
        }

        // Set user roles (support multiple roles)
        if (!empty($roles) && count($roles) > 1) {
            $user = new \WP_User($created_user_id);
            // Remove default role
            $user->remove_role($roles[0]);
            // Add all specified roles
            foreach ($roles as $role) {
                $user->add_role($role);
            }
        }

        return $created_user_id;
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
