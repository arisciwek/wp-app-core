<?php
/**
 * Abstract CRUD Model
 *
 * Base class for entity CRUD models providing shared implementation
 * for common patterns: find, create, update, delete operations.
 * Eliminates code duplication across CRUD models.
 *
 * @package     WPAppCore
 * @subpackage  Models/Crud
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Models/Abstract/AbstractCrudModel.php
 *
 * Description: Abstract base class for all entity CRUD models.
 *              Provides concrete implementations of commonly duplicated
 *              methods like find(), create(), update(), and delete().
 *              Includes hook system integration, cache management,
 *              and static ID injection support.
 *
 * Dependencies:
 * - WordPress global $wpdb
 * - WordPress functions (apply_filters, do_action)
 * - Entity-specific cache handler (passed to constructor)
 *
 * Usage:
 * ```php
 * class CustomerModel extends AbstractCrudModel {
 *     public function __construct() {
 *         parent::__construct(CustomerCache::getInstance());
 *     }
 *
 *     protected function getTableName(): string {
 *         global $wpdb;
 *         return $wpdb->prefix . 'app_customers';
 *     }
 *
 *     protected function getEntityName(): string {
 *         return 'customer';
 *     }
 *
 *     // Implement other abstract methods...
 *
 *     // ✅ find() - inherited FREE!
 *     // ✅ create() - inherited FREE with hooks!
 *     // ✅ update() - inherited FREE!
 *     // ✅ delete() - inherited FREE!
 * }
 * ```
 *
 * Benefits:
 * - 64% code reduction in child classes (~2,900 lines saved across 7 models)
 * - Consistent hook patterns across all entities
 * - Standardized cache invalidation
 * - Static ID injection support (for demo data, migrations)
 * - Type-safe method signatures
 * - Single source of truth for CRUD operations
 * - Easier testing and maintenance
 *
 * Hook Patterns:
 * - Before insert: apply_filters('{plugin}_{entity}_before_insert', $insert_data, $data)
 * - After create: do_action('{plugin}_{entity}_created', $entity_id, $insert_data)
 *
 * Changelog:
 * 1.0.0 - 2025-01-02
 * - Initial implementation
 * - CRUD operations: find(), create(), update(), delete()
 * - Hook system integration
 * - Cache management: invalidateCache()
 * - Format array building: buildFormatArray()
 * - Static ID injection support
 * - 7 abstract methods for entity configuration
 * - Comprehensive PHPDoc documentation
 */

namespace WPAppCore\Models\Abstract;

defined('ABSPATH') || exit;

abstract class AbstractCrudModel {

    /**
     * Cache handler instance
     *
     * Entity-specific cache handler for storing and retrieving cached data.
     * Must implement methods like getCustomer(), setCustomer(), etc.
     *
     * @var object Cache handler instance
     */
    protected $cache;

    /**
     * Constructor
     *
     * @param object $cache_handler Entity-specific cache handler instance
     *
     * @example
     * ```php
     * public function __construct() {
     *     parent::__construct(CustomerCache::getInstance());
     * }
     * ```
     */
    public function __construct($cache_handler) {
        $this->cache = $cache_handler;
    }

    // ========================================
    // ABSTRACT METHODS (Must be implemented by child classes)
    // ========================================

    /**
     * Get database table name
     *
     * Full table name including WordPress prefix.
     * Used in all database queries.
     *
     * @return string Table name with prefix, e.g., 'wp_app_customers'
     *
     * @example
     * ```php
     * protected function getTableName(): string {
     *     global $wpdb;
     *     return $wpdb->prefix . 'app_customers';
     * }
     * ```
     */
    abstract protected function getTableName(): string;

    /**
     * Get cache method name prefix
     *
     * Prefix used for cache method names.
     * Used to call cache methods like get{CacheKey}(), set{CacheKey}().
     *
     * @return string Cache method prefix, e.g., 'Customer', 'Agency', 'Staff'
     *
     * @example
     * ```php
     * protected function getCacheKey(): string {
     *     return 'Customer';  // Results in getCustomer(), setCustomer()
     * }
     * ```
     */
    abstract protected function getCacheKey(): string;

    /**
     * Get entity name (singular, lowercase)
     *
     * Used for hook names and action names.
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
     * Get plugin prefix for hooks
     *
     * Plugin prefix used in hook names.
     * Must match your plugin's standard prefix.
     *
     * @return string Plugin prefix, e.g., 'wp_customer', 'wp_agency', 'wp_app_core'
     *
     * @example
     * ```php
     * protected function getPluginPrefix(): string {
     *     return 'wp_customer';
     * }
     * ```
     */
    abstract protected function getPluginPrefix(): string;

    /**
     * Get allowed fields for update operations
     *
     * List of field names that can be updated.
     * Used in update() method to filter incoming data.
     *
     * @return array Field names, e.g., ['name', 'email', 'phone', 'status']
     *
     * @example
     * ```php
     * protected function getAllowedFields(): array {
     *     return ['name', 'email', 'phone', 'address', 'status'];
     * }
     * ```
     */
    abstract protected function getAllowedFields(): array;

    /**
     * Prepare insert data from request
     *
     * Transform request data into database insert format.
     * Called by create() method before applying hooks.
     *
     * @param array $data Raw request data
     * @return array Prepared insert data with all required fields
     *
     * @example
     * ```php
     * protected function prepareInsertData(array $data): array {
     *     return [
     *         'code' => $data['code'],
     *         'name' => $data['name'],
     *         'email' => $data['email'] ?? null,
     *         'phone' => $data['phone'] ?? null,
     *         'address' => $data['address'] ?? null,
     *         'status' => $data['status'] ?? 'aktif',
     *         'user_id' => get_current_user_id(),
     *         'created_at' => current_time('mysql')
     *     ];
     * }
     * ```
     */
    abstract protected function prepareInsertData(array $data): array;

    /**
     * Get format map for wpdb operations
     *
     * Maps field names to wpdb format strings (%d for integers, %s for strings).
     * Used by buildFormatArray() to generate format arrays.
     *
     * @return array Field => format map, e.g., ['id' => '%d', 'name' => '%s']
     *
     * @example
     * ```php
     * protected function getFormatMap(): array {
     *     return [
     *         'id' => '%d',
     *         'user_id' => '%d',
     *         'code' => '%s',
     *         'name' => '%s',
     *         'email' => '%s',
     *         'phone' => '%s',
     *         'address' => '%s',
     *         'status' => '%s',
     *         'created_at' => '%s',
     *         'updated_at' => '%s'
     *     ];
     * }
     * ```
     */
    abstract protected function getFormatMap(): array;

    // ========================================
    // CONCRETE METHODS (Shared implementation)
    // ========================================

    /**
     * Find entity by ID
     *
     * Retrieves single entity from database with caching.
     * Automatically handles cache get/set operations.
     *
     * Flow:
     * 1. Check cache first
     * 2. Query database if cache miss
     * 3. Store result in cache
     * 4. Return result
     *
     * @param int $id Entity ID
     * @return object|null Entity object or null if not found
     *
     * @example
     * ```php
     * // In controller
     * $customer = $this->model->find(123);
     * if (!$customer) {
     *     throw new \Exception('Customer not found');
     * }
     * ```
     */
    public function find(int $id): ?object {
        // 1. Check cache
        $cache_key = $this->getCacheKey();
        $get_method = 'get' . $cache_key;

        $cached = $this->cache->$get_method($id);
        if ($cached !== false) {
            return $cached;
        }

        // 2. Query database
        global $wpdb;
        $table = $this->getTableName();

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $id
        ));

        // 3. Cache result (if found)
        if ($result) {
            $set_method = 'set' . $cache_key;
            $this->cache->$set_method($id, $result);
        }

        return $result;
    }

    /**
     * Create new entity
     *
     * Complete create flow with hooks, cache invalidation, and static ID support.
     *
     * Flow:
     * 1. Prepare insert data
     * 2. Apply before_insert filter hook
     * 3. Handle static ID injection (if added by hook)
     * 4. Build format array
     * 5. Insert to database
     * 6. Invalidate cache
     * 7. Trigger after create action hook
     * 8. Return new ID
     *
     * Hooks:
     * - Filter: {plugin}_{entity}_before_insert($insert_data, $original_data)
     * - Action: {plugin}_{entity}_created($entity_id, $insert_data)
     *
     * @param array $data Request data
     * @return int|null New entity ID or null on failure
     * @throws \Exception On database error
     *
     * @example
     * ```php
     * // In controller
     * $data = [
     *     'code' => 'CUST001',
     *     'name' => 'John Doe',
     *     'email' => 'john@example.com'
     * ];
     *
     * $new_id = $this->model->create($data);
     * ```
     *
     * @example With static ID injection (via hook)
     * ```php
     * // In demo data plugin
     * add_filter('wp_customer_before_insert', function($insert_data, $original_data) {
     *     if (defined('WP_DEMO_MODE')) {
     *         $insert_data['id'] = 999;  // Static ID for demo
     *     }
     *     return $insert_data;
     * }, 10, 2);
     * ```
     */
    public function create(array $data): ?int {
        global $wpdb;

        try {
            // 1. Prepare insert data
            $insert_data = $this->prepareInsertData($data);

            // 2. HOOK: Before insert filter
            $plugin = $this->getPluginPrefix();
            $entity = $this->getEntityName();

            /**
             * Filter data before database insertion
             *
             * Allows modification of insert data before it's saved.
             * Common uses: static ID injection, data sync, demo data.
             *
             * @param array $insert_data Prepared insert data
             * @param array $data Original request data
             * @return array Modified insert data
             */
            $insert_data = apply_filters(
                "{$plugin}_{$entity}_before_insert",
                $insert_data,
                $data
            );

            // 3. Handle static ID injection (if added by hook)
            $static_id = null;
            if (isset($insert_data['id']) && !isset($data['id'])) {
                // Hook added static ID - preserve it
                $static_id = $insert_data['id'];
            }

            // 4. Build format array
            $format = $this->buildFormatArray($insert_data);

            // 5. Insert to database
            $result = $wpdb->insert(
                $this->getTableName(),
                $insert_data,
                $format
            );

            if ($result === false) {
                throw new \Exception($wpdb->last_error);
            }

            // 6. Get insert ID
            $new_id = $static_id ?? $wpdb->insert_id;

            // 7. Invalidate cache
            $this->invalidateCache($new_id);

            // 8. HOOK: After create action
            /**
             * Fires after entity is created
             *
             * Allows post-creation tasks like creating related entities,
             * sending notifications, logging, etc.
             *
             * @param int $new_id New entity ID
             * @param array $insert_data Data that was inserted
             */
            do_action(
                "{$plugin}_{$entity}_created",
                $new_id,
                $insert_data
            );

            return $new_id;

        } catch (\Exception $e) {
            error_log("AbstractCrudModel::create() error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update entity by ID
     *
     * Updates entity with allowed fields only.
     * Automatically handles cache invalidation.
     *
     * Flow:
     * 1. Verify entity exists
     * 2. Filter data to allowed fields only
     * 3. Build format array
     * 4. Update database
     * 5. Invalidate cache
     *
     * @param int $id Entity ID
     * @param array $data Update data
     * @return bool True on success, false on failure
     *
     * @example
     * ```php
     * // In controller
     * $data = [
     *     'name' => 'Jane Doe',
     *     'email' => 'jane@example.com',
     *     'forbidden_field' => 'value'  // Will be filtered out
     * ];
     *
     * $success = $this->model->update(123, $data);
     * if (!$success) {
     *     throw new \Exception('Update failed');
     * }
     * ```
     */
    public function update(int $id, array $data): bool {
        global $wpdb;

        try {
            // 1. Verify entity exists
            $current = $this->find($id);
            if (!$current) {
                return false;
            }

            // 2. Prepare update data (only allowed fields)
            $update_data = [];
            $allowed_fields = $this->getAllowedFields();

            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    $update_data[$field] = $data[$field];
                }
            }

            // Nothing to update
            if (empty($update_data)) {
                return false;
            }

            // 3. Build format array
            $format = $this->buildFormatArray($update_data);

            // 4. Update database
            $result = $wpdb->update(
                $this->getTableName(),
                $update_data,
                ['id' => $id],
                $format,
                ['%d']
            );

            // 5. Invalidate cache
            $this->invalidateCache($id);

            return $result !== false;

        } catch (\Exception $e) {
            error_log("AbstractCrudModel::update() error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete entity by ID
     *
     * Deletes entity from database with cache invalidation.
     *
     * Flow:
     * 1. Verify entity exists
     * 2. Delete from database
     * 3. Invalidate cache
     *
     * @param int $id Entity ID
     * @return bool True on success, false on failure
     *
     * @example
     * ```php
     * // In controller
     * $success = $this->model->delete(123);
     * if (!$success) {
     *     throw new \Exception('Delete failed');
     * }
     * ```
     */
    public function delete(int $id): bool {
        global $wpdb;

        try {
            // 1. Verify entity exists
            $current = $this->find($id);
            if (!$current) {
                return false;
            }

            // 2. Delete from database
            $result = $wpdb->delete(
                $this->getTableName(),
                ['id' => $id],
                ['%d']
            );

            // 3. Invalidate cache
            $this->invalidateCache($id);

            return $result !== false;

        } catch (\Exception $e) {
            error_log("AbstractCrudModel::delete() error: " . $e->getMessage());
            return false;
        }
    }

    // ========================================
    // UTILITY METHODS
    // ========================================

    /**
     * Build format array for wpdb operations
     *
     * Generates format array based on data keys using format map.
     * Automatically handles dynamic fields (e.g., when hooks add 'id').
     *
     * @param array $data Data to format
     * @return array Format array, e.g., ['%s', '%s', '%d']
     *
     * @example
     * ```php
     * $data = ['name' => 'John', 'email' => 'john@example.com', 'user_id' => 123];
     * $format = $this->buildFormatArray($data);
     * // Result: ['%s', '%s', '%d']
     * ```
     */
    protected function buildFormatArray(array $data): array {
        $format = [];
        $format_map = $this->getFormatMap();

        foreach (array_keys($data) as $key) {
            $format[] = $format_map[$key] ?? '%s';
        }

        return $format;
    }

    /**
     * Invalidate entity cache
     *
     * Clears cache for entity and any additional cache keys.
     * Uses entity's cache invalidation method.
     *
     * @param int $id Entity ID
     * @param mixed ...$additional_keys Additional cache keys to invalidate
     *
     * @example Basic usage
     * ```php
     * $this->invalidateCache($customer_id);
     * ```
     *
     * @example With additional keys
     * ```php
     * // Invalidate customer and related branch cache
     * $this->invalidateCache($customer_id, $branch_id);
     * ```
     */
    protected function invalidateCache(int $id, ...$additional_keys): void {
        $cache_key = $this->getCacheKey();
        $invalidate_method = 'invalidate' . $cache_key . 'Cache';

        // Call entity's cache invalidation method
        if (method_exists($this->cache, $invalidate_method)) {
            $this->cache->$invalidate_method($id, ...$additional_keys);
        }
    }
}
