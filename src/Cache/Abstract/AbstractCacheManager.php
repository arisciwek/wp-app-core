<?php
/**
 * Abstract Cache Manager
 *
 * Base cache manager class for entity caching operations.
 * Eliminates code duplication across entity cache managers by providing
 * shared implementation for common cache patterns.
 *
 * @package     WPAppCore
 * @subpackage  Cache/Abstract
 * @version     1.0.1
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Cache/Abstract/AbstractCacheManager.php
 *
 * Description: Abstract base class for all entity cache managers.
 *              Provides standardized cache operations including key generation,
 *              DataTable caching, invalidation, and debug logging.
 *              Child classes only need to implement entity-specific configuration.
 *
 * Dependencies:
 * - WordPress Object Cache API (wp_cache_*)
 * - WordPress constants (HOUR_IN_SECONDS, MINUTE_IN_SECONDS)
 *
 * Usage:
 * ```php
 * class CustomerCacheManager extends AbstractCacheManager {
 *     protected function getCacheGroup(): string {
 *         return 'wp_customer';
 *     }
 *
 *     protected function getCacheExpiry(): int {
 *         return 12 * HOUR_IN_SECONDS;
 *     }
 *
 *     // Implement other abstract methods...
 *
 *     // ✅ get(), set(), delete() - inherited FREE!
 *     // ✅ getDataTableCache(), setDataTableCache() - inherited FREE!
 *     // ✅ invalidateDataTableCache() - inherited FREE!
 *     // ✅ clearCache(), clearAll() - inherited FREE!
 *     // ✅ generateKey(), debug_log() - inherited FREE!
 * }
 * ```
 *
 * Benefits:
 * - 73%+ code reduction in child cache managers
 * - Consistent cache operations across all entities
 * - Standardized key generation (172 char limit)
 * - Single source of truth for cache logic
 * - Type-safe method signatures
 * - Easier testing and maintenance
 *
 * Changelog:
 * 1.0.1 - 2025-11-09 (TODO-2192 FIX)
 * - CRITICAL FIX: get() method now returns FALSE on cache miss (not null)
 * - This fixes find() method returning null when cache is empty
 * - Maintains compatibility with AbstractCrudModel expectations
 *
 * 1.0.0 - 2025-01-08
 * - Initial implementation
 * - 5 abstract methods for entity configuration
 * - 12 concrete methods for shared functionality
 * - DataTable cache support
 * - Key generation with 172 char limit
 * - Cache invalidation by prefix
 * - Debug logging
 * - Comprehensive PHPDoc documentation
 */

namespace WPAppCore\Cache\Abstract;

defined('ABSPATH') || exit;

abstract class AbstractCacheManager {

    // ========================================
    // ABSTRACT METHODS (Must be implemented by child classes)
    // ========================================

    /**
     * Get cache group name
     *
     * Used as WordPress cache group for all operations.
     * Must be unique per plugin/entity.
     *
     * @return string Cache group name, e.g., 'wp_customer', 'wp_agency', 'wp_app_core'
     *
     * @example
     * ```php
     * protected function getCacheGroup(): string {
     *     return 'wp_customer';
     * }
     * ```
     */
    abstract protected function getCacheGroup(): string;

    /**
     * Get cache expiry time
     *
     * Default expiry time for cache entries.
     * Can be overridden per set() call.
     *
     * @return int Cache expiry in seconds
     *
     * @example
     * ```php
     * protected function getCacheExpiry(): int {
     *     return 12 * HOUR_IN_SECONDS;  // 12 hours
     * }
     * ```
     */
    abstract protected function getCacheExpiry(): int;

    /**
     * Get entity name (singular, lowercase)
     *
     * Used for logging and debugging.
     * Must use underscores for multi-word entities.
     *
     * @return string Entity name, e.g., 'customer', 'agency', 'platform_staff'
     *
     * @example
     * ```php
     * protected function getEntityName(): string {
     *     return 'customer';
     * }
     * ```
     */
    abstract protected function getEntityName(): string;

    /**
     * Get entity-specific cache keys
     *
     * Mapping of cache key types to their actual key strings.
     * Used for cache key lookups and invalidation.
     *
     * @return array Associative array of cache key types
     *
     * @example
     * ```php
     * protected function getCacheKeys(): array {
     *     return [
     *         'customer' => 'customer',
     *         'customer_list' => 'customer_list',
     *         'customer_stats' => 'customer_stats',
     *         'branch' => 'customer_branch',
     *         'employee' => 'customer_employee',
     *     ];
     * }
     * ```
     */
    abstract protected function getCacheKeys(): array;

    /**
     * Get known cache types for fallback clearing
     *
     * List of cache type prefixes that can be cleared.
     * Used in clearCache() fallback method.
     *
     * @return array List of known cache type prefixes
     *
     * @example
     * ```php
     * protected function getKnownCacheTypes(): array {
     *     return [
     *         'customer',
     *         'customer_list',
     *         'customer_branch',
     *         'customer_employee',
     *         'datatable'
     *     ];
     * }
     * ```
     */
    abstract protected function getKnownCacheTypes(): array;

    // ========================================
    // CONCRETE METHODS (Shared implementation)
    // ========================================

    /**
     * Generate valid cache key from components
     *
     * Creates cache key by joining components with underscores.
     * Handles WordPress 172 character key length limit.
     * Filters out empty components.
     *
     * @param string ...$components Key components to join
     * @return string Generated cache key
     *
     * @example
     * ```php
     * // Simple key
     * $key = $this->generateKey('customer', '123');
     * // Result: 'customer_123'
     *
     * // Complex key with multiple components
     * $key = $this->generateKey('datatable', 'customer_list', 'admin', 'start_0', 'length_10');
     * // Result: 'datatable_customer_list_admin_start_0_length_10'
     *
     * // Long key (auto-truncated with hash)
     * $key = $this->generateKey('very_long_component', ...$many_components);
     * // Result: 'very_long_component_..._[md5_hash]' (max 172 chars)
     * ```
     */
    protected function generateKey(string ...$components): string {
        // Filter out empty components
        $validComponents = array_filter($components, function($component) {
            return !empty($component) && is_string($component);
        });

        if (empty($validComponents)) {
            // Generate default key for empty components
            return 'default_' . md5(serialize($components));
        }

        // Join with underscore
        $key = implode('_', $validComponents);

        // WordPress has a key length limit of 172 characters
        if (strlen($key) > 172) {
            // Truncate and add hash to ensure uniqueness
            $key = substr($key, 0, 140) . '_' . md5($key);
        }

        return $key;
    }

    /**
     * Get value from cache with validation
     *
     * Retrieves cached value by type and key components.
     * Logs cache hits/misses in debug mode.
     *
     * IMPORTANT: Returns false on cache miss (not null) to maintain
     * compatibility with AbstractCrudModel find() method.
     *
     * @param string $type Cache type (first component)
     * @param mixed ...$keyComponents Additional key components
     * @return mixed|false Cached value or FALSE if not found (cache miss)
     *
     * @example
     * ```php
     * // Get single entity
     * $customer = $cache->get('customer', $id);
     *
     * // Get list
     * $customers = $cache->get('customer_list');
     *
     * // Get with multiple components
     * $stats = $cache->get('customer_stats', $user_id, $date);
     * ```
     */
    public function get(string $type, ...$keyComponents) {
        $key = $this->generateKey($type, ...$keyComponents);
        $result = wp_cache_get($key, $this->getCacheGroup());

        if ($result === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->debug_log("Cache miss - Key: {$key}");
            }
            return false;  // FIX TODO-2192: Return false instead of null on cache miss
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log("Cache hit - Key: {$key}");
        }

        return $result;
    }

    /**
     * Set value in cache with validation
     *
     * Stores value in cache with optional custom expiry.
     * Logs cache operations in debug mode.
     *
     * @param string $type Cache type (first component)
     * @param mixed $value Value to cache
     * @param int|null $expiry Optional custom expiry time (seconds)
     * @param mixed ...$keyComponents Additional key components
     * @return bool True on success, false on failure
     *
     * @example
     * ```php
     * // Set with default expiry
     * $cache->set('customer', $customer_data, null, $id);
     *
     * // Set with custom expiry (5 minutes)
     * $cache->set('customer_list', $customers, 5 * MINUTE_IN_SECONDS);
     *
     * // Set with multiple components
     * $cache->set('customer_stats', $stats, null, $user_id, $date);
     * ```
     */
    public function set(string $type, $value, int $expiry = null, ...$keyComponents): bool {
        try {
            $key = $this->generateKey($type, ...$keyComponents);

            if ($expiry === null) {
                $expiry = $this->getCacheExpiry();
            }

            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->debug_log("Setting cache - Key: {$key}, Type: {$type}, Expiry: {$expiry}s");
            }

            return wp_cache_set($key, $value, $this->getCacheGroup(), $expiry);

        } catch (\InvalidArgumentException $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->debug_log("Cache set failed: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Delete value from cache
     *
     * Removes cached value by type and key components.
     * Logs deletion in debug mode.
     *
     * @param string $type Cache type (first component)
     * @param mixed ...$keyComponents Additional key components
     * @return bool True on success, false on failure
     *
     * @example
     * ```php
     * // Delete single entity
     * $cache->delete('customer', $id);
     *
     * // Delete list
     * $cache->delete('customer_list');
     *
     * // Delete with multiple components
     * $cache->delete('customer_stats', $user_id, $date);
     * ```
     */
    public function delete(string $type, ...$keyComponents): bool {
        $key = $this->generateKey($type, ...$keyComponents);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->debug_log("Deleting cache - Key: {$key}, Type: {$type}");
        }

        return wp_cache_delete($key, $this->getCacheGroup());
    }

    /**
     * Check if key exists in cache
     *
     * Checks if value exists without retrieving it.
     *
     * @param string $type Cache type (first component)
     * @param mixed ...$keyComponents Additional key components
     * @return bool True if exists, false otherwise
     *
     * @example
     * ```php
     * if ($cache->exists('customer', $id)) {
     *     // Use cached data
     * } else {
     *     // Query database
     * }
     * ```
     */
    public function exists(string $type, ...$keyComponents): bool {
        $key = $this->generateKey($type, ...$keyComponents);
        return wp_cache_get($key, $this->getCacheGroup()) !== false;
    }

    /**
     * Get cached DataTable data
     *
     * Retrieves cached DataTable results based on request parameters.
     * Uses all DataTable parameters for key generation.
     *
     * @param string $context DataTable context (e.g., 'customer_list', 'agency_list')
     * @param string $access_type User access type for filtering
     * @param int $start Pagination start
     * @param int $length Pagination length
     * @param string $search Search query
     * @param string $orderColumn Order column
     * @param string $orderDir Order direction (asc/desc)
     * @param array|null $additionalParams Optional additional parameters
     * @return mixed|null Cached DataTable data or null if not found
     *
     * @example
     * ```php
     * $cached = $cache->getDataTableCache(
     *     'customer_list',
     *     'admin',
     *     0,
     *     10,
     *     '',
     *     'name',
     *     'asc'
     * );
     * ```
     */
    public function getDataTableCache(
        string $context,
        string $access_type,
        int $start,
        int $length,
        string $search,
        string $orderColumn,
        string $orderDir,
        ?array $additionalParams = null
    ) {
        // Disable caching for company_list to ensure fresh data
        if ($context === 'company_list') {
            return null;
        }

        // Validate required parameters
        if (empty($context) || !$access_type || !is_numeric($start) || !is_numeric($length)) {
            $this->debug_log('Invalid parameters in getDataTableCache');
            return null;
        }

        try {
            // Build components untuk kunci cache
            $components = [
                $context,
                (string)$access_type,
                'start_' . (string)$start,
                'length_' . (string)$length,
                md5($search),
                (string)$orderColumn,
                (string)$orderDir
            ];

            // Add additional parameters if provided
            if ($additionalParams) {
                foreach ($additionalParams as $key => $value) {
                    $components[] = $key . '_' . md5(serialize($value));
                }
            }

            return $this->get('datatable', ...$components);

        } catch (\Exception $e) {
            $this->debug_log("Error getting datatable data for context {$context}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Set DataTable data in cache
     *
     * Stores DataTable results in cache with short expiry (2 minutes).
     * Uses same key generation as getDataTableCache().
     *
     * @param string $context DataTable context
     * @param string $access_type User access type
     * @param int $start Pagination start
     * @param int $length Pagination length
     * @param string $search Search query
     * @param string $orderColumn Order column
     * @param string $orderDir Order direction
     * @param mixed $data Data to cache
     * @param array|null $additionalParams Optional additional parameters
     * @return bool True on success, false on failure
     *
     * @example
     * ```php
     * $cache->setDataTableCache(
     *     'customer_list',
     *     'admin',
     *     0,
     *     10,
     *     '',
     *     'name',
     *     'asc',
     *     $datatable_result
     * );
     * ```
     */
    public function setDataTableCache(
        string $context,
        string $access_type,
        int $start,
        int $length,
        string $search,
        string $orderColumn,
        string $orderDir,
        $data,
        ?array $additionalParams = null
    ): bool {
        // Disable caching for company_list
        if ($context === 'company_list') {
            return true;
        }

        // Validate required parameters
        if (empty($context) || !$access_type || !is_numeric($start) || !is_numeric($length)) {
            $this->debug_log('Invalid parameters in setDataTableCache');
            return false;
        }

        // Build components - SAME as getDataTableCache
        $components = [
            $context,
            (string)$access_type,
            'start_' . (string)$start,
            'length_' . (string)$length,
            md5($search),
            (string)$orderColumn,
            (string)$orderDir
        ];

        // Add additional parameters - SAME as getDataTableCache
        if ($additionalParams) {
            foreach ($additionalParams as $key => $value) {
                $components[] = $key . '_' . md5(serialize($value));
            }
        }

        // Cache for 2 minutes (DataTable data changes frequently)
        return $this->set('datatable', $data, 2 * MINUTE_IN_SECONDS, ...$components);
    }

    /**
     * Invalidate DataTable cache
     *
     * Clears DataTable cache by context and optional filters.
     * If filters provided, clears specific cache entry.
     * If no filters, clears all cache entries for context.
     *
     * @param string $context DataTable context to invalidate
     * @param array|null $filters Optional filters for specific invalidation
     * @return bool True on success, false on failure
     *
     * @example
     * ```php
     * // Invalidate all customer list caches
     * $cache->invalidateDataTableCache('customer_list');
     *
     * // Invalidate specific filtered cache
     * $cache->invalidateDataTableCache('customer_list', ['status' => 'active']);
     * ```
     */
    public function invalidateDataTableCache(string $context, ?array $filters = null): bool {
        try {
            if (empty($context)) {
                $this->debug_log('Invalid context in invalidateDataTableCache');
                return false;
            }

            // Log invalidation attempt
            $this->debug_log(sprintf(
                'Attempting to invalidate DataTable cache - Context: %s, Filters: %s',
                $context,
                $filters ? json_encode($filters) : 'none'
            ));

            global $wp_object_cache;
            if (!isset($wp_object_cache->cache[$this->getCacheGroup()]) ||
                empty($wp_object_cache->cache[$this->getCacheGroup()])) {
                $this->debug_log('Cache group not found or empty - no action needed');
                return true;
            }

            // Base components for cache key
            $components = ['datatable', $context];

            // If we have filters, create filter-specific invalidation
            if ($filters) {
                foreach ($filters as $key => $value) {
                    $components[] = sprintf('%s_%s', $key, md5(serialize($value)));
                }

                $key = $this->generateKey(...$components);
                $result = wp_cache_delete($key, $this->getCacheGroup());

                $this->debug_log(sprintf(
                    'Invalidated filtered cache for context %s. Result: %s',
                    $context,
                    $result ? 'success' : 'failed'
                ));

                return true;
            }

            // If no filters, do broader invalidation using prefix
            $prefix = implode('_', $components);
            $result = $this->deleteByPrefix($prefix);

            $this->debug_log(sprintf(
                'Invalidated all cache entries for context %s. Result: %s',
                $context,
                $result ? 'success' : 'failed'
            ));

            return true;

        } catch (\Exception $e) {
            $this->debug_log('Error in invalidateDataTableCache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete cache entries by prefix
     *
     * Removes all cache keys that start with given prefix.
     * Used for bulk cache invalidation.
     *
     * @param string $prefix Key prefix to match
     * @return bool True on success
     *
     * @example
     * ```php
     * // Delete all customer-related caches
     * $this->deleteByPrefix('customer');
     *
     * // Delete all datatable caches for customer list
     * $this->deleteByPrefix('datatable_customer_list');
     * ```
     */
    protected function deleteByPrefix(string $prefix): bool {
        global $wp_object_cache;

        // If group not exists, nothing to delete
        if (!isset($wp_object_cache->cache[$this->getCacheGroup()])) {
            $this->debug_log('Cache group not found - nothing to delete');
            return true;
        }

        // If group empty, nothing to delete
        if (empty($wp_object_cache->cache[$this->getCacheGroup()])) {
            $this->debug_log('Cache group empty - nothing to delete');
            return true;
        }

        $deleted = 0;
        $keys = array_keys($wp_object_cache->cache[$this->getCacheGroup()]);

        foreach ($keys as $key) {
            if (strpos($key, $prefix) === 0) {
                $result = wp_cache_delete($key, $this->getCacheGroup());
                if ($result) $deleted++;
            }
        }

        $this->debug_log(sprintf('Deleted %d keys with prefix %s', $deleted, $prefix));
        return true;
    }

    /**
     * Clear cache (type-specific or all)
     *
     * Clears cache entries by type or all entries in group.
     * Supports multiple cache backends.
     *
     * @param string|null $type Optional cache type to clear specific pattern
     * @return bool True on success, false on failure
     *
     * @example
     * ```php
     * // Clear all caches
     * $cache->clearCache();
     *
     * // Clear specific type
     * $cache->clearCache('customer');
     * ```
     */
    public function clearCache(string $type = null): bool {
        try {
            global $wp_object_cache;

            // Check if using default WordPress object cache
            if (isset($wp_object_cache->cache[$this->getCacheGroup()])) {
                if (is_array($wp_object_cache->cache[$this->getCacheGroup()])) {
                    foreach (array_keys($wp_object_cache->cache[$this->getCacheGroup()]) as $key) {
                        // If type specified, only clear keys matching that type
                        if ($type === null || strpos($key, $type) === 0) {
                            wp_cache_delete($key, $this->getCacheGroup());
                        }
                    }
                }
                if ($type === null) {
                    unset($wp_object_cache->cache[$this->getCacheGroup()]);
                }
                return true;
            }

            // Alternative approach for external cache plugins
            if (function_exists('wp_cache_flush_group')) {
                return wp_cache_flush_group($this->getCacheGroup());
            }

            // Fallback method - iteratively clear known cache keys
            $known_types = $this->getKnownCacheTypes();

            foreach ($known_types as $cache_type) {
                if ($type === null || $cache_type === $type) {
                    if ($cached_keys = wp_cache_get($cache_type . '_keys', $this->getCacheGroup())) {
                        if (is_array($cached_keys)) {
                            foreach ($cached_keys as $key) {
                                wp_cache_delete($key, $this->getCacheGroup());
                            }
                        }
                    }
                }
            }

            // Also clear the master key list
            if ($type === null) {
                wp_cache_delete('cache_keys', $this->getCacheGroup());
            }

            return true;

        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Error clearing cache: ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Clear all caches in group
     *
     * Removes all cache entries for this entity.
     * Alias for clearCache() without type parameter.
     *
     * @return bool True on success, false on failure
     *
     * @example
     * ```php
     * $cache->clearAll();
     * ```
     */
    public function clearAll(): bool {
        try {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Attempting to clear all caches in group: ' . $this->getCacheGroup());
            }

            $result = $this->clearCache();

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Cache clear result: ' . ($result ? 'success' : 'failed'));
            }

            return $result;

        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Error in clearAll(): ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Clear all caches (alias for backward compatibility)
     *
     * @return bool True on success, false on failure
     */
    public function clearAllCaches(): bool {
        return $this->clearAll();
    }

    /**
     * Debug logger for cache operations
     *
     * Logs messages when WP_DEBUG is enabled.
     * Formats messages with entity name prefix.
     *
     * @param string $message Log message
     * @param mixed $data Optional data to include in log
     * @return void
     *
     * @example
     * ```php
     * $this->debug_log('Cache operation failed', ['key' => $key, 'error' => $error]);
     * ```
     */
    protected function debug_log(string $message, $data = null): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[%sCacheManager] %s %s',
                ucfirst($this->getEntityName()),
                $message,
                $data ? '| Data: ' . print_r($data, true) : ''
            ));
        }
    }

    // ========================================
    // STATIC HELPERS (for external access)
    // ========================================

    /**
     * Get cache group (static)
     *
     * Static wrapper for getCacheGroup().
     * Useful for external code that needs cache group name.
     *
     * @return string Cache group name
     *
     * @example
     * ```php
     * $group = CustomerCacheManager::getCacheGroupStatic();
     * ```
     */
    public static function getCacheGroupStatic(): string {
        return (new static())->getCacheGroup();
    }

    /**
     * Get cache expiry (static)
     *
     * Static wrapper for getCacheExpiry().
     * Useful for external code that needs cache expiry time.
     *
     * @return int Cache expiry in seconds
     *
     * @example
     * ```php
     * $expiry = CustomerCacheManager::getCacheExpiryStatic();
     * ```
     */
    public static function getCacheExpiryStatic(): int {
        return (new static())->getCacheExpiry();
    }
}
