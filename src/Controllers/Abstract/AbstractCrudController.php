<?php
/**
 * Abstract CRUD Controller
 *
 * Base controller class for CRUD operations (Create, Read, Update, Delete).
 * Eliminates code duplication across entity controllers by providing
 * shared implementation for common CRUD patterns.
 *
 * @package     WPAppCore
 * @subpackage  Controllers/Abstract
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Abstract/AbstractCrudController.php
 *
 * Description: Abstract base class for all entity CRUD controllers.
 *              Provides standardized CRUD operations with consistent
 *              error handling, validation, caching, and response formatting.
 *              Child classes only need to implement entity-specific logic.
 *
 * Dependencies:
 * - WordPress AJAX functions (check_ajax_referer, wp_send_json_*)
 * - WordPress cache functions (wp_cache_delete)
 * - WordPress i18n functions (__)
 * - Entity-specific Model (via getModel())
 * - Entity-specific Validator (via getValidator())
 *
 * Usage:
 * ```php
 * class CustomerController extends AbstractCrudController {
 *     protected function getEntityName(): string {
 *         return 'customer';
 *     }
 *
 *     protected function prepareCreateData(): array {
 *         return [
 *             'name' => sanitize_text_field($_POST['name'] ?? ''),
 *             // ... more fields
 *         ];
 *     }
 *
 *     // Implement other abstract methods...
 *
 *     // ✅ store(), update(), delete(), show() inherited FREE!
 * }
 * ```
 *
 * Benefits:
 * - 70-80% code reduction in child controllers
 * - Consistent error handling across all entities
 * - Standardized validation and permission checks
 * - Automatic cache management
 * - Type-safe method signatures
 * - Easier testing and maintenance
 *
 * Changelog:
 * 1.0.0 - 2025-01-02
 * - Initial implementation
 * - CRUD methods: store(), update(), delete(), show()
 * - Utility methods: verifyNonce(), checkPermission(), validate()
 * - Cache management: clearCache()
 * - Response helpers: sendSuccess(), handleError()
 * - Abstract methods for entity-specific configuration
 */

namespace WPAppCore\Controllers\Abstract;

defined('ABSPATH') || exit;

abstract class AbstractCrudController {

    // ========================================
    // ABSTRACT METHODS (Must be implemented by child classes)
    // ========================================

    /**
     * Get entity name (singular, lowercase)
     *
     * Used for cache keys, error messages, and logging.
     *
     * @return string Entity name, e.g., 'customer', 'agency', 'staff'
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
     * Get entity name (plural, lowercase)
     *
     * Used for UI messages and logging.
     *
     * @return string Plural entity name, e.g., 'customers', 'agencies', 'staff'
     *
     * @example
     * ```php
     * protected function getEntityNamePlural(): string {
     *     return 'customers';
     * }
     * ```
     */
    abstract protected function getEntityNamePlural(): string;

    /**
     * Get nonce action for AJAX request verification
     *
     * Must match the nonce action used in JavaScript AJAX calls.
     *
     * @return string Nonce action, e.g., 'wp_customer_nonce'
     *
     * @example
     * ```php
     * protected function getNonceAction(): string {
     *     return 'wp_customer_nonce';
     * }
     * ```
     */
    abstract protected function getNonceAction(): string;

    /**
     * Get text domain for translations
     *
     * Used for __() translation functions.
     *
     * @return string Text domain, e.g., 'wp-customer'
     *
     * @example
     * ```php
     * protected function getTextDomain(): string {
     *     return 'wp-customer';
     * }
     * ```
     */
    abstract protected function getTextDomain(): string;

    /**
     * Get validator instance
     *
     * Must return an instance of the entity's validator class.
     * Validator should have validatePermission() and validate() methods.
     *
     * @return object Validator instance
     *
     * @example
     * ```php
     * protected function getValidator() {
     *     return $this->validator;
     * }
     * ```
     */
    abstract protected function getValidator();

    /**
     * Get model instance
     *
     * Must return an instance of the entity's model class.
     * Model should have create(), update(), delete(), find() methods.
     *
     * @return object Model instance
     *
     * @example
     * ```php
     * protected function getModel() {
     *     return $this->model;
     * }
     * ```
     */
    abstract protected function getModel();

    /**
     * Get cache group name
     *
     * Used for WordPress object cache operations.
     *
     * @return string Cache group, e.g., 'wp-customer'
     *
     * @example
     * ```php
     * protected function getCacheGroup(): string {
     *     return 'wp-customer';
     * }
     * ```
     */
    abstract protected function getCacheGroup(): string;

    /**
     * Prepare data from $_POST for create operation
     *
     * Should sanitize and format all input data.
     * Return array with all fields needed for entity creation.
     *
     * @return array Sanitized data ready for model->create()
     *
     * @example
     * ```php
     * protected function prepareCreateData(): array {
     *     return [
     *         'name' => sanitize_text_field($_POST['name'] ?? ''),
     *         'email' => sanitize_email($_POST['email'] ?? ''),
     *         'status' => sanitize_text_field($_POST['status'] ?? 'active')
     *     ];
     * }
     * ```
     */
    abstract protected function prepareCreateData(): array;

    /**
     * Prepare data from $_POST for update operation
     *
     * Should sanitize and format all input data.
     * May have different logic than prepareCreateData() if needed.
     *
     * @param int $id Entity ID being updated
     * @return array Sanitized data ready for model->update()
     *
     * @example
     * ```php
     * protected function prepareUpdateData(int $id): array {
     *     // Often same as create, but can be different
     *     return $this->prepareCreateData();
     * }
     * ```
     */
    abstract protected function prepareUpdateData(int $id): array;

    // ========================================
    // CONCRETE METHODS (Shared implementation)
    // ========================================

    /**
     * Create new entity (store)
     *
     * Handles the complete create workflow:
     * 1. Verify nonce
     * 2. Check permission
     * 3. Prepare data (via prepareCreateData())
     * 4. Validate data
     * 5. Create entity via model
     * 6. Send success response
     *
     * @return void Sends JSON response and dies
     *
     * @throws \Exception On validation or creation failure
     */
    public function store(): void {
        try {
            // Verify nonce
            $this->verifyNonce();

            // Check permission
            $this->checkPermission('create');

            // Prepare data (call child implementation)
            $data = $this->prepareCreateData();

            // Validate
            $this->validate($data);

            // Create via model
            $result = $this->getModel()->create($data);

            // Send success response
            $this->sendSuccess(
                sprintf(
                    __('%s created successfully', $this->getTextDomain()),
                    ucfirst($this->getEntityName())
                ),
                $result
            );

        } catch (\Exception $e) {
            $this->handleError($e, 'create');
        }
    }

    /**
     * Update existing entity
     *
     * Handles the complete update workflow:
     * 1. Verify nonce
     * 2. Check permission
     * 3. Get and validate ID
     * 4. Prepare data (via prepareUpdateData())
     * 5. Validate data
     * 6. Update entity via model
     * 7. Clear cache
     * 8. Send success response
     *
     * @return void Sends JSON response and dies
     *
     * @throws \Exception On validation or update failure
     */
    public function update(): void {
        try {
            // Verify nonce
            $this->verifyNonce();

            // Check permission
            $this->checkPermission('edit');

            // Get and validate ID
            $id = $this->getId();

            // Prepare data (call child implementation)
            $data = $this->prepareUpdateData($id);

            // Validate
            $this->validate($data, $id);

            // Update via model
            $result = $this->getModel()->update($id, $data);

            // Clear cache
            $this->clearCache($id);

            // Send success response
            $this->sendSuccess(
                sprintf(
                    __('%s updated successfully', $this->getTextDomain()),
                    ucfirst($this->getEntityName())
                ),
                $result
            );

        } catch (\Exception $e) {
            $this->handleError($e, 'update');
        }
    }

    /**
     * Delete entity (soft delete)
     *
     * Handles the complete delete workflow:
     * 1. Verify nonce
     * 2. Check permission
     * 3. Get and validate ID
     * 4. Delete entity via model
     * 5. Clear cache
     * 6. Send success response
     *
     * Note: Most models implement soft delete (status = 'inactive')
     *
     * @return void Sends JSON response and dies
     *
     * @throws \Exception On deletion failure
     */
    public function delete(): void {
        try {
            // Verify nonce
            $this->verifyNonce();

            // Check permission
            $this->checkPermission('delete');

            // Get and validate ID
            $id = $this->getId();

            // Delete via model
            $result = $this->getModel()->delete($id);

            if ($result) {
                // Clear cache
                $this->clearCache($id);

                // Send success response
                $this->sendSuccess(
                    sprintf(
                        __('%s deleted successfully', $this->getTextDomain()),
                        ucfirst($this->getEntityName())
                    )
                );
            } else {
                throw new \Exception(
                    sprintf(
                        __('Failed to delete %s', $this->getTextDomain()),
                        $this->getEntityName()
                    )
                );
            }

        } catch (\Exception $e) {
            $this->handleError($e, 'delete');
        }
    }

    /**
     * Show single entity (read)
     *
     * Handles the complete read workflow:
     * 1. Verify nonce
     * 2. Check permission
     * 3. Get and validate ID
     * 4. Retrieve entity via model
     * 5. Send success response with entity data
     *
     * @return void Sends JSON response and dies
     *
     * @throws \Exception If entity not found
     */
    public function show(): void {
        try {
            // Verify nonce
            $this->verifyNonce();

            // Check permission
            $this->checkPermission('read');

            // Get and validate ID
            $id = $this->getId();

            // Get entity via model
            $entity = $this->getModel()->find($id);

            if (!$entity) {
                throw new \Exception(
                    sprintf(
                        __('%s not found', $this->getTextDomain()),
                        ucfirst($this->getEntityName())
                    )
                );
            }

            // Send success response
            $this->sendSuccess(
                sprintf(
                    __('%s retrieved successfully', $this->getTextDomain()),
                    ucfirst($this->getEntityName())
                ),
                $entity
            );

        } catch (\Exception $e) {
            $this->handleError($e, 'show');
        }
    }

    // ========================================
    // UTILITY METHODS (Protected helpers)
    // ========================================

    /**
     * Verify WordPress nonce for security
     *
     * Checks nonce from $_POST['nonce'] parameter.
     * Uses nonce action from getNonceAction().
     *
     * @return void
     * @throws \Exception If nonce verification fails
     */
    protected function verifyNonce(): void {
        $nonce = $_POST['nonce'] ?? $_GET['nonce'] ?? '';

        if (!check_ajax_referer($this->getNonceAction(), 'nonce', false)) {
            throw new \Exception(__('Security check failed', $this->getTextDomain()));
        }
    }

    /**
     * Check user permission for action
     *
     * Calls validator's validatePermission() method.
     * Throws exception if permission check fails.
     *
     * @param string $action Action name: 'create', 'edit', 'delete', 'read'
     * @return void
     * @throws \Exception If permission denied
     */
    protected function checkPermission(string $action): void {
        $errors = $this->getValidator()->validatePermission($action);

        if (!empty($errors)) {
            throw new \Exception(reset($errors));
        }
    }

    /**
     * Validate entity data
     *
     * Calls validator's validate() method.
     * Throws exception if validation fails.
     *
     * @param array $data Data to validate
     * @param int|null $id Entity ID (for update validation)
     * @return void
     * @throws \Exception If validation fails
     */
    protected function validate(array $data, ?int $id = null): void {
        $errors = $this->getValidator()->validate($data, $id);

        if (!empty($errors)) {
            throw new \Exception(implode(' ', $errors));
        }
    }

    /**
     * Get entity ID from request
     *
     * Checks $_POST['id'] and $_GET['id'].
     * Returns integer ID or throws exception.
     *
     * @return int Entity ID
     * @throws \Exception If ID is missing or invalid
     */
    protected function getId(): int {
        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

        if (!$id) {
            throw new \Exception(__('Invalid ID', $this->getTextDomain()));
        }

        return $id;
    }

    /**
     * Clear entity cache
     *
     * Removes entity from WordPress object cache.
     * Uses pattern: {entity_name}_{id}
     *
     * @param int $id Entity ID
     * @return void
     */
    protected function clearCache(int $id): void {
        $entity_name = $this->getEntityName();
        $cache_key = "{$entity_name}_{$id}";

        wp_cache_delete($cache_key, $this->getCacheGroup());
    }

    /**
     * Send success JSON response
     *
     * Sends standardized success response with optional data.
     *
     * @param string $message Success message
     * @param mixed $data Optional data to include in response
     * @return void Dies after sending response
     */
    protected function sendSuccess(string $message, $data = null): void {
        $response = ['message' => $message];

        if ($data !== null) {
            $response['data'] = $data;
        }

        wp_send_json_success($response);
    }

    /**
     * Handle error and send error response
     *
     * Logs error and sends standardized error response.
     *
     * @param \Exception $e Exception that occurred
     * @param string $action Action name (for logging)
     * @return void Dies after sending response
     */
    protected function handleError(\Exception $e, string $action): void {
        $entity = $this->getEntityName();

        // Log error
        error_log("[{$entity}] {$action} error: " . $e->getMessage());

        // Send error response
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
