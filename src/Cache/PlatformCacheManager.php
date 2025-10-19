<?php
/**
 * Platform Cache Manager
 *
 * @package     WP_App_Core
 * @subpackage  Cache
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Cache/PlatformCacheManager.php
 *
 * Description: Generic cache manager untuk semua data platform.
 *              Menggunakan WordPress Object Cache API.
 *              Dapat digunakan untuk semua entitas platform:
 *              - Platform Staff
 *              - Platform Settings
 *              - Platform Permissions
 *              - Dan lainnya
 *
 * Cache Groups:
 * - wp_app_core: Grup utama untuk semua cache
 *
 * Cache Keys Pattern:
 * - platform_{entity}_{id}: Data single entity
 * - platform_{entity}_list: Daftar semua entity
 * - platform_{entity}_stats: Statistik entity
 * - platform_{filter}_{value}: Filtered data
 * - user_platform_{entity}_{user_id}: Relasi user-entity
 *
 * Dependencies:
 * - WordPress Object Cache API
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial implementation
 * - Generic cache support untuk semua platform entities
 * - Staff caching support
 * - Settings caching support
 * - Extensible untuk entity baru
 */

namespace WPAppCore\Cache;

defined('ABSPATH') || exit;

class PlatformCacheManager {

    // Cache configuration
    private const CACHE_GROUP = 'wp_app_core';
    private const CACHE_EXPIRY = 12 * HOUR_IN_SECONDS;

    /**
     * Get cache group name
     *
     * @return string Cache group
     */
    public static function getCacheGroup(): string {
        return self::CACHE_GROUP;
    }

    /**
     * Get cache expiry time
     *
     * @return int Cache expiry in seconds
     */
    public static function getCacheExpiry(): int {
        return self::CACHE_EXPIRY;
    }

    // ============================================================
    // GENERIC CACHE METHODS (untuk semua entities)
    // ============================================================

    /**
     * Get cache data by key
     *
     * @param string $key Cache key
     * @return mixed|false Data or false if not found
     */
    public function get(string $key) {
        return wp_cache_get($key, self::CACHE_GROUP);
    }

    /**
     * Set cache data
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int|null $expiry Optional custom expiry time
     * @return bool True on success, false on failure
     */
    public function set(string $key, $data, ?int $expiry = null): bool {
        $expiry = $expiry ?? self::CACHE_EXPIRY;
        return wp_cache_set($key, $data, self::CACHE_GROUP, $expiry);
    }

    /**
     * Delete cache data
     *
     * @param string $key Cache key
     * @return bool True on success, false on failure
     */
    public function delete(string $key): bool {
        return wp_cache_delete($key, self::CACHE_GROUP);
    }

    /**
     * Clear all platform cache
     *
     * @return void
     */
    public function clearAllCache(): void {
        // Clear staff cache
        $this->clearStaffCache();

        // Add other entity cache clearing here as needed
    }

    // ============================================================
    // PLATFORM STAFF CACHE METHODS
    // ============================================================

    /**
     * Get single staff data from cache
     *
     * @param int $staff_id Staff ID
     * @return mixed|false Staff data or false if not found
     */
    public function getStaff(int $staff_id) {
        $cache_key = 'platform_staff_' . $staff_id;
        return wp_cache_get($cache_key, self::CACHE_GROUP);
    }

    /**
     * Set single staff data to cache
     *
     * @param int $staff_id Staff ID
     * @param mixed $data Staff data
     * @return bool True on success, false on failure
     */
    public function setStaff(int $staff_id, $data): bool {
        $cache_key = 'platform_staff_' . $staff_id;
        return wp_cache_set($cache_key, $data, self::CACHE_GROUP, self::CACHE_EXPIRY);
    }

    /**
     * Delete single staff data from cache
     *
     * @param int $staff_id Staff ID
     * @return bool True on success, false on failure
     */
    public function deleteStaff(int $staff_id): bool {
        $cache_key = 'platform_staff_' . $staff_id;
        return wp_cache_delete($cache_key, self::CACHE_GROUP);
    }

    /**
     * Get staff list from cache
     *
     * @return mixed|false Staff list or false if not found
     */
    public function getStaffList() {
        return wp_cache_get('platform_staff_list', self::CACHE_GROUP);
    }

    /**
     * Set staff list to cache
     *
     * @param mixed $data Staff list data
     * @return bool True on success, false on failure
     */
    public function setStaffList($data): bool {
        return wp_cache_set('platform_staff_list', $data, self::CACHE_GROUP, self::CACHE_EXPIRY);
    }

    /**
     * Delete staff list from cache
     *
     * @return bool True on success, false on failure
     */
    public function deleteStaffList(): bool {
        return wp_cache_delete('platform_staff_list', self::CACHE_GROUP);
    }

    /**
     * Get staff statistics from cache
     *
     * @return mixed|false Statistics data or false if not found
     */
    public function getStaffStats() {
        return wp_cache_get('platform_staff_stats', self::CACHE_GROUP);
    }

    /**
     * Set staff statistics to cache
     *
     * @param mixed $data Statistics data
     * @return bool True on success, false on failure
     */
    public function setStaffStats($data): bool {
        return wp_cache_set('platform_staff_stats', $data, self::CACHE_GROUP, self::CACHE_EXPIRY);
    }

    /**
     * Delete staff statistics from cache
     *
     * @return bool True on success, false on failure
     */
    public function deleteStaffStats(): bool {
        return wp_cache_delete('platform_staff_stats', self::CACHE_GROUP);
    }

    /**
     * Get staff by department from cache
     *
     * @param string $department Department name
     * @return mixed|false Staff list or false if not found
     */
    public function getStaffByDepartment(string $department) {
        $cache_key = 'platform_staff_department_' . sanitize_key($department);
        return wp_cache_get($cache_key, self::CACHE_GROUP);
    }

    /**
     * Set staff by department to cache
     *
     * @param string $department Department name
     * @param mixed $data Staff list data
     * @return bool True on success, false on failure
     */
    public function setStaffByDepartment(string $department, $data): bool {
        $cache_key = 'platform_staff_department_' . sanitize_key($department);
        return wp_cache_set($cache_key, $data, self::CACHE_GROUP, self::CACHE_EXPIRY);
    }

    /**
     * Delete staff by department from cache
     *
     * @param string $department Department name
     * @return bool True on success, false on failure
     */
    public function deleteStaffByDepartment(string $department): bool {
        $cache_key = 'platform_staff_department_' . sanitize_key($department);
        return wp_cache_delete($cache_key, self::CACHE_GROUP);
    }

    /**
     * Get user's staff data from cache
     *
     * @param int $user_id User ID
     * @return mixed|false Staff data or false if not found
     */
    public function getUserStaff(int $user_id) {
        $cache_key = 'user_platform_staff_' . $user_id;
        return wp_cache_get($cache_key, self::CACHE_GROUP);
    }

    /**
     * Set user's staff data to cache
     *
     * @param int $user_id User ID
     * @param mixed $data Staff data
     * @return bool True on success, false on failure
     */
    public function setUserStaff(int $user_id, $data): bool {
        $cache_key = 'user_platform_staff_' . $user_id;
        return wp_cache_set($cache_key, $data, self::CACHE_GROUP, self::CACHE_EXPIRY);
    }

    /**
     * Delete user's staff data from cache
     *
     * @param int $user_id User ID
     * @return bool True on success, false on failure
     */
    public function deleteUserStaff(int $user_id): bool {
        $cache_key = 'user_platform_staff_' . $user_id;
        return wp_cache_delete($cache_key, self::CACHE_GROUP);
    }

    /**
     * Clear all staff-related cache
     *
     * @return void
     */
    public function clearStaffCache(): void {
        // Delete all common caches
        $this->deleteStaffList();
        $this->deleteStaffStats();
    }

    /**
     * Clear all cache related to a specific staff
     *
     * @param int $staff_id Staff ID
     * @param int|null $user_id Optional user ID
     * @param string|null $department Optional department
     * @return void
     */
    public function clearSpecificStaffCache(int $staff_id, ?int $user_id = null, ?string $department = null): void {
        // Delete specific staff cache
        $this->deleteStaff($staff_id);

        // Delete user-staff relation cache if user_id provided
        if ($user_id) {
            $this->deleteUserStaff($user_id);
        }

        // Delete department cache if department provided
        if ($department) {
            $this->deleteStaffByDepartment($department);
        }

        // Delete list and stats caches (they need to be regenerated)
        $this->deleteStaffList();
        $this->deleteStaffStats();
    }
}
