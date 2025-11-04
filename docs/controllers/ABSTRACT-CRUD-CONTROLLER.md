# AbstractCrudController Documentation

**Version:** 1.0.0
**File:** `src/Controllers/Abstract/AbstractCrudController.php`
**Namespace:** `WPAppCore\Controllers\Abstract`
**Status:** ✅ Production Ready

---

## Overview

Base controller class for CRUD operations (Create, Read, Update, Delete). Eliminates code duplication across entity controllers by providing shared implementation for common CRUD patterns.

### Key Benefits

✅ **70-80% Code Reduction** - Child controllers only implement entity-specific logic
✅ **Consistent Error Handling** - Standardized across all entities
✅ **Automatic Cache Management** - Built-in cache invalidation
✅ **Type-Safe Method Signatures** - PHP type hints throughout
✅ **Security Built-In** - Nonce verification and permission checks
✅ **Easy Testing** - Consistent interface for unit tests

---

## Quick Start

### 1. Create Your Controller

```php
<?php
namespace WPCustomer\Controllers\Customer;

use WPAppCore\Controllers\Abstract\AbstractCrudController;
use WPCustomer\Models\Customer\CustomerModel;
use WPCustomer\Validators\CustomerValidator;

class CustomerController extends AbstractCrudController {

    private $model;
    private $validator;

    public function __construct() {
        $this->model = new CustomerModel();
        $this->validator = new CustomerValidator();
    }

    // ========================================
    // IMPLEMENT 9 ABSTRACT METHODS
    // ========================================

    protected function getEntityName(): string {
        return 'customer';
    }

    protected function getEntityNamePlural(): string {
        return 'customers';
    }

    protected function getNonceAction(): string {
        return 'wp_customer_nonce';
    }

    protected function getTextDomain(): string {
        return 'wp-customer';
    }

    protected function getValidator() {
        return $this->validator;
    }

    protected function getModel() {
        return $this->model;
    }

    protected function getCacheGroup(): string {
        return 'wp-customer';
    }

    protected function prepareCreateData(): array {
        return [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'address' => sanitize_textarea_field($_POST['address'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? 'active')
        ];
    }

    protected function prepareUpdateData(int $id): array {
        // Often same as create
        return $this->prepareCreateData();
    }
}
```

### 2. Register AJAX Handlers

```php
// In your plugin's main file or controller registration
add_action('wp_ajax_save_customer', [$customer_controller, 'store']);
add_action('wp_ajax_update_customer', [$customer_controller, 'update']);
add_action('wp_ajax_delete_customer', [$customer_controller, 'delete']);
add_action('wp_ajax_get_customer', [$customer_controller, 'show']);
```

### 3. Use from JavaScript

```javascript
// Create customer
$.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'save_customer',
        nonce: wpCustomer.nonce,
        name: 'John Doe',
        email: 'john@example.com',
        phone: '123456789',
        address: '123 Main St',
        status: 'active'
    },
    success: function(response) {
        if (response.success) {
            console.log('Customer created:', response.data);
            // Trigger auto-refresh
            $(document).trigger('customer:created', response.data);
        }
    }
});

// Update customer
$.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'update_customer',
        nonce: wpCustomer.nonce,
        id: 123,
        name: 'John Doe Updated',
        email: 'john.updated@example.com'
    },
    success: function(response) {
        if (response.success) {
            // Trigger auto-refresh
            $(document).trigger('customer:updated', response.data);
        }
    }
});

// Delete customer
$.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'delete_customer',
        nonce: wpCustomer.nonce,
        id: 123
    },
    success: function(response) {
        if (response.success) {
            // Trigger auto-refresh
            $(document).trigger('customer:deleted', {id: 123});
        }
    }
});

// Get customer
$.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'get_customer',
        nonce: wpCustomer.nonce,
        id: 123
    },
    success: function(response) {
        if (response.success) {
            console.log('Customer data:', response.data);
        }
    }
});
```

That's it! You get **4 CRUD methods FREE** with consistent behavior!

---

## Abstract Methods

These 9 methods **MUST** be implemented by child classes:

### 1. `getEntityName(): string`

Entity name (singular, lowercase). Used for cache keys, error messages, logging.

**Example:**
```php
protected function getEntityName(): string {
    return 'customer';
}
```

---

### 2. `getEntityNamePlural(): string`

Entity name (plural, lowercase). Used for UI messages and logging.

**Example:**
```php
protected function getEntityNamePlural(): string {
    return 'customers';
}
```

---

### 3. `getNonceAction(): string`

Nonce action for AJAX request verification. Must match JavaScript nonce.

**Example:**
```php
protected function getNonceAction(): string {
    return 'wp_customer_nonce';
}
```

---

### 4. `getTextDomain(): string`

Text domain for translations.

**Example:**
```php
protected function getTextDomain(): string {
    return 'wp-customer';
}
```

---

### 5. `getValidator()`

Validator instance with `validatePermission()` and `validate()` methods.

**Example:**
```php
protected function getValidator() {
    return $this->validator;
}
```

**Required Validator Methods:**
```php
class CustomerValidator {
    public function validatePermission(string $action): array {
        // Return empty array if valid, error messages if invalid
        if (!current_user_can('edit_customers')) {
            return ['Permission denied'];
        }
        return [];
    }

    public function validate(array $data, ?int $id = null): array {
        // Return empty array if valid, error messages if invalid
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = 'Name is required';
        }

        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        }

        return $errors;
    }
}
```

---

### 6. `getModel()`

CRUD model instance with `create()`, `update()`, `delete()`, `find()` methods.

**Example:**
```php
protected function getModel() {
    return $this->model;
}
```

**Required Model Methods:**
```php
class CustomerModel {
    public function create(array $data) {
        // Insert into database
        // Return created entity data
    }

    public function update(int $id, array $data) {
        // Update in database
        // Return updated entity data
    }

    public function delete(int $id): bool {
        // Delete from database (or soft delete)
        // Return true on success, false on failure
    }

    public function find(int $id) {
        // Retrieve from database
        // Return entity object or null
    }
}
```

---

### 7. `getCacheGroup(): string`

Cache group for WordPress object cache.

**Example:**
```php
protected function getCacheGroup(): string {
    return 'wp-customer';
}
```

---

### 8. `prepareCreateData(): array`

Sanitize and prepare data from `$_POST` for create operation.

**Example:**
```php
protected function prepareCreateData(): array {
    return [
        'name' => sanitize_text_field($_POST['name'] ?? ''),
        'email' => sanitize_email($_POST['email'] ?? ''),
        'phone' => sanitize_text_field($_POST['phone'] ?? ''),
        'address' => sanitize_textarea_field($_POST['address'] ?? ''),
        'status' => sanitize_text_field($_POST['status'] ?? 'active'),
        'created_at' => current_time('mysql'),
        'created_by' => get_current_user_id()
    ];
}
```

---

### 9. `prepareUpdateData(int $id): array`

Sanitize and prepare data from `$_POST` for update operation.

**Example:**
```php
protected function prepareUpdateData(int $id): array {
    // Often same as create, but can be different
    $data = $this->prepareCreateData();

    // Override timestamps
    $data['updated_at'] = current_time('mysql');
    $data['updated_by'] = get_current_user_id();

    return $data;
}
```

---

## Concrete Methods (Inherited FREE)

These 4 CRUD methods are automatically available in child classes:

### 1. `store(): void`

Create new entity.

**Flow:**
1. Verify nonce
2. Check permission
3. Prepare data (via `prepareCreateData()`)
4. Validate data
5. Create entity via model
6. Send success response

**AJAX Request:**
```javascript
{
    action: 'save_customer',
    nonce: 'abc123',
    name: 'John Doe',
    email: 'john@example.com'
}
```

**Success Response:**
```json
{
    "success": true,
    "data": {
        "message": "Customer created successfully",
        "data": {
            "id": 123,
            "name": "John Doe",
            "email": "john@example.com"
        }
    }
}
```

**Error Response:**
```json
{
    "success": false,
    "data": {
        "message": "Name is required"
    }
}
```

---

### 2. `update(): void`

Update existing entity.

**Flow:**
1. Verify nonce
2. Check permission
3. Get and validate ID
4. Prepare data (via `prepareUpdateData()`)
5. Validate data
6. Update entity via model
7. Clear cache
8. Send success response

**AJAX Request:**
```javascript
{
    action: 'update_customer',
    nonce: 'abc123',
    id: 123,
    name: 'John Doe Updated'
}
```

**Success Response:**
```json
{
    "success": true,
    "data": {
        "message": "Customer updated successfully",
        "data": {
            "id": 123,
            "name": "John Doe Updated"
        }
    }
}
```

---

### 3. `delete(): void`

Delete entity (soft delete).

**Flow:**
1. Verify nonce
2. Check permission
3. Get and validate ID
4. Delete entity via model
5. Clear cache
6. Send success response

**AJAX Request:**
```javascript
{
    action: 'delete_customer',
    nonce: 'abc123',
    id: 123
}
```

**Success Response:**
```json
{
    "success": true,
    "data": {
        "message": "Customer deleted successfully"
    }
}
```

---

### 4. `show(): void`

Show single entity (read).

**Flow:**
1. Verify nonce
2. Check permission
3. Get and validate ID
4. Retrieve entity via model
5. Send success response with entity data

**AJAX Request:**
```javascript
{
    action: 'get_customer',
    nonce: 'abc123',
    id: 123
}
```

**Success Response:**
```json
{
    "success": true,
    "data": {
        "message": "Customer retrieved successfully",
        "data": {
            "id": 123,
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "123456789"
        }
    }
}
```

---

## Utility Methods (Protected Helpers)

These 7 helper methods are available to child classes:

### 1. `verifyNonce(): void`

Verify WordPress nonce for security. Checks `$_POST['nonce']` or `$_GET['nonce']`.

**Throws:** `\Exception` if nonce verification fails

---

### 2. `checkPermission(string $action): void`

Check user permission for action. Calls validator's `validatePermission()` method.

**Parameters:**
- `$action`: 'create', 'edit', 'delete', or 'read'

**Throws:** `\Exception` if permission denied

---

### 3. `validate(array $data, ?int $id = null): void`

Validate entity data. Calls validator's `validate()` method.

**Throws:** `\Exception` if validation fails

---

### 4. `getId(): int`

Get entity ID from request. Checks `$_POST['id']` and `$_GET['id']`.

**Returns:** `int` Entity ID

**Throws:** `\Exception` if ID is missing or invalid

---

### 5. `clearCache(int $id): void`

Clear entity from WordPress object cache.

**Cache Key Pattern:** `{entity_name}_{id}`

**Example:** `customer_123`

---

### 6. `sendSuccess(string $message, $data = null): void`

Send success JSON response.

**Example:**
```php
$this->sendSuccess('Customer created successfully', $customer);
```

---

### 7. `handleError(\Exception $e, string $action): void`

Handle error and send error response. Logs error to error_log.

---

## Real-World Examples

### Example 1: Basic Customer Controller

```php
<?php
namespace WPCustomer\Controllers\Customer;

use WPAppCore\Controllers\Abstract\AbstractCrudController;

class CustomerController extends AbstractCrudController {

    private $model;
    private $validator;

    public function __construct() {
        $this->model = new \WPCustomer\Models\Customer\CustomerModel();
        $this->validator = new \WPCustomer\Validators\CustomerValidator();
    }

    protected function getEntityName(): string {
        return 'customer';
    }

    protected function getEntityNamePlural(): string {
        return 'customers';
    }

    protected function getNonceAction(): string {
        return 'wp_customer_nonce';
    }

    protected function getTextDomain(): string {
        return 'wp-customer';
    }

    protected function getValidator() {
        return $this->validator;
    }

    protected function getModel() {
        return $this->model;
    }

    protected function getCacheGroup(): string {
        return 'wp-customer';
    }

    protected function prepareCreateData(): array {
        return [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'status' => 'active'
        ];
    }

    protected function prepareUpdateData(int $id): array {
        return $this->prepareCreateData();
    }
}
```

**Usage:**
```php
// Register AJAX handlers
$customer_controller = new CustomerController();
add_action('wp_ajax_save_customer', [$customer_controller, 'store']);
add_action('wp_ajax_update_customer', [$customer_controller, 'update']);
add_action('wp_ajax_delete_customer', [$customer_controller, 'delete']);
add_action('wp_ajax_get_customer', [$customer_controller, 'show']);
```

**Lines of Code:**
- **Before AbstractCrudController:** ~300 lines
- **After AbstractCrudController:** ~60 lines
- **Reduction:** 80% 🎉

---

### Example 2: Advanced with Custom Business Logic

```php
class CustomerController extends AbstractCrudController {

    // ... abstract methods ...

    /**
     * Override prepareCreateData for custom logic
     */
    protected function prepareCreateData(): array {
        $data = [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'company' => sanitize_text_field($_POST['company'] ?? ''),
            'status' => 'active',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ];

        // Custom logic: Auto-assign territory based on location
        if (!empty($_POST['city'])) {
            $data['territory_id'] = $this->getTerritoryByCity($_POST['city']);
        }

        // Custom logic: Generate unique customer code
        $data['customer_code'] = $this->generateCustomerCode();

        return $data;
    }

    /**
     * Custom business logic method
     */
    private function getTerritoryByCity(string $city): int {
        // Implementation...
    }

    /**
     * Custom business logic method
     */
    private function generateCustomerCode(): string {
        // Implementation...
    }
}
```

---

## Testing

### Unit Test Example

```php
<?php
namespace WPCustomer\Tests\Controllers;

use PHPUnit\Framework\TestCase;
use WPCustomer\Controllers\Customer\CustomerController;

class CustomerControllerTest extends TestCase {

    private $controller;

    public function setUp(): void {
        parent::setUp();
        $this->controller = new CustomerController();
    }

    public function test_store_creates_customer() {
        // Mock $_POST data
        $_POST = [
            'nonce' => wp_create_nonce('wp_customer_nonce'),
            'name' => 'Test Customer',
            'email' => 'test@example.com'
        ];

        // Call store method
        ob_start();
        $this->controller->store();
        $output = ob_get_clean();

        // Assert success response
        $response = json_decode($output, true);
        $this->assertTrue($response['success']);
        $this->assertEquals('Customer created successfully', $response['data']['message']);
    }

    public function test_update_modifies_customer() {
        // Similar to above...
    }

    public function test_delete_removes_customer() {
        // Similar to above...
    }
}
```

---

## Best Practices

### ✅ DO:

1. **Sanitize ALL Input**
```php
protected function prepareCreateData(): array {
    return [
        'name' => sanitize_text_field($_POST['name'] ?? ''),
        'email' => sanitize_email($_POST['email'] ?? ''),
        'content' => wp_kses_post($_POST['content'] ?? '')
    ];
}
```

2. **Validate Before Processing**
```php
public function validate(array $data, ?int $id = null): array {
    $errors = [];

    if (empty($data['name'])) {
        $errors[] = __('Name is required', 'wp-customer');
    }

    if (!is_email($data['email'])) {
        $errors[] = __('Invalid email', 'wp-customer');
    }

    return $errors;
}
```

3. **Check Permissions Granularly**
```php
public function validatePermission(string $action): array {
    switch ($action) {
        case 'create':
            return current_user_can('create_customers') ? [] : ['Permission denied'];
        case 'edit':
            return current_user_can('edit_customers') ? [] : ['Permission denied'];
        case 'delete':
            return current_user_can('delete_customers') ? [] : ['Permission denied'];
        default:
            return ['Invalid action'];
    }
}
```

---

### ❌ DON'T:

1. **Don't Skip Validation**
```php
// ❌ Bad
protected function prepareCreateData(): array {
    return $_POST; // NEVER do this!
}

// ✅ Good
protected function prepareCreateData(): array {
    return [
        'name' => sanitize_text_field($_POST['name'] ?? ''),
        'email' => sanitize_email($_POST['email'] ?? '')
    ];
}
```

2. **Don't Return Raw Database Errors**
```php
// ❌ Bad - exposes database structure
catch (\Exception $e) {
    wp_send_json_error(['message' => $e->getMessage()]);
}

// ✅ Good - generic error message
catch (\Exception $e) {
    error_log('Customer create error: ' . $e->getMessage());
    wp_send_json_error(['message' => __('Error creating customer', 'wp-customer')]);
}
```

3. **Don't Forget Cache Invalidation**
- AbstractCrudController handles this automatically in `update()` and `delete()`
- If you override these methods, remember to call `$this->clearCache($id)`

---

## Troubleshooting

### Issue 1: "Security check failed"

**Cause:** Nonce mismatch between PHP and JavaScript.

**Solution:**
```php
// PHP - getNonceAction()
protected function getNonceAction(): string {
    return 'wp_customer_nonce';
}

// JavaScript - must match!
wp_localize_script('customer-script', 'wpCustomer', [
    'nonce' => wp_create_nonce('wp_customer_nonce')  // Must match!
]);
```

---

### Issue 2: "Permission denied"

**Cause:** User lacks required capability.

**Solution:** Check validator's `validatePermission()` method:
```php
public function validatePermission(string $action): array {
    // Debug
    error_log('Checking permission for action: ' . $action);
    error_log('Current user can edit_customers: ' . (current_user_can('edit_customers') ? 'YES' : 'NO'));

    if (!current_user_can('edit_customers')) {
        return ['Permission denied'];
    }

    return [];
}
```

---

### Issue 3: Validation Errors Not Shown

**Cause:** Validator returning wrong format.

**Solution:** Validator must return array of error strings:
```php
// ❌ Wrong
public function validate(array $data, ?int $id = null): bool {
    return !empty($data['name']);
}

// ✅ Correct
public function validate(array $data, ?int $id = null): array {
    $errors = [];

    if (empty($data['name'])) {
        $errors[] = 'Name is required';
    }

    return $errors; // Array of strings
}
```

---

## Migration Guide

### Before (Manual CRUD)

```php
class CustomerController {
    public function save_customer() {
        // Verify nonce - 5 lines
        // Check permission - 5 lines
        // Sanitize data - 20 lines
        // Validate data - 30 lines
        // Create/update logic - 40 lines
        // Error handling - 20 lines
        // Cache clearing - 10 lines
        // Response - 10 lines
    }

    public function update_customer() {
        // 140 lines (mostly duplicate)
    }

    public function delete_customer() {
        // 60 lines
    }

    public function get_customer() {
        // 50 lines
    }
}
// TOTAL: ~390 lines
```

### After (AbstractCrudController)

```php
class CustomerController extends AbstractCrudController {
    // 9 abstract methods: ~60 lines
    // store(), update(), delete(), show() inherited FREE
}
// TOTAL: ~60 lines (85% reduction!)
```

---

## Changelog

### v1.0.0 (2025-01-02)
- ✅ Initial implementation
- ✅ CRUD methods: `store()`, `update()`, `delete()`, `show()`
- ✅ Utility methods: `verifyNonce()`, `checkPermission()`, `validate()`
- ✅ Cache management: `clearCache()`
- ✅ Response helpers: `sendSuccess()`, `handleError()`
- ✅ Abstract methods for entity-specific configuration
- ✅ Comprehensive PHPDoc documentation

---

## Related Documentation

- [AbstractDashboardController](./ABSTRACT-DASHBOARD-CONTROLLER.md) - Dashboard UI controller
- [DataTable System](../datatable/ARCHITECTURE.md) - DataTable implementation
- [Creating New Plugin](./CREATING-NEW-PLUGIN-GUIDE.md) - Complete guide

---

**Last Updated:** 2025-01-02
**Version:** 1.0.0
**Status:** ✅ Production Ready
