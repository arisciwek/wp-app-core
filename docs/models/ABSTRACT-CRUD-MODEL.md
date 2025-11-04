# AbstractCrudModel Documentation

**Version:** 1.0.0
**Last Updated:** 2025-01-02
**Path:** `/wp-app-core/src/Models/Crud/AbstractCrudModel.php`

---

## Table of Contents

1. [Overview](#overview)
2. [Benefits](#benefits)
3. [Quick Start](#quick-start)
4. [Architecture](#architecture)
5. [Abstract Methods](#abstract-methods)
6. [Concrete Methods](#concrete-methods)
7. [Hook System](#hook-system)
8. [Static ID Injection](#static-id-injection)
9. [Cache Management](#cache-management)
10. [Real-World Examples](#real-world-examples)
11. [Best Practices](#best-practices)
12. [Migration Guide](#migration-guide)
13. [Troubleshooting](#troubleshooting)

---

## Overview

`AbstractCrudModel` is a base class for all entity CRUD (Create, Read, Update, Delete) models in the WPApp ecosystem. It provides concrete implementations of common CRUD operations, eliminating **60-80% code duplication** across model files.

### What It Does

- ✅ **find()**: Retrieves entity with caching
- ✅ **create()**: Inserts entity with hooks and static ID support
- ✅ **update()**: Updates entity with field filtering
- ✅ **delete()**: Removes entity with cache invalidation
- ✅ **Hook Integration**: Standardized before/after hooks
- ✅ **Cache Management**: Automatic cache invalidation
- ✅ **Format Array Building**: Dynamic wpdb format generation

### What It Doesn't Do

- ❌ Custom queries (implement in child class)
- ❌ Complex validation (implement in child class or controller)
- ❌ Business logic (belongs in child class)
- ❌ Entity relationships (implement in child class)

---

## Benefits

### Code Reduction

**Before AbstractCrudModel:**
```
CustomerModel.php:       1,200 lines (CRUD: ~350 lines)
AgencyModel.php:         1,000 lines (CRUD: ~300 lines)
BranchModel.php:           800 lines (CRUD: ~280 lines)
PlatformStaffModel.php:    500 lines (CRUD: ~250 lines)
---------------------------------------------------------
Total:                   3,500 lines (CRUD: ~1,180 lines)
```

**After AbstractCrudModel:**
```
CustomerModel.php:         850 lines (config: ~80 lines)
AgencyModel.php:           700 lines (config: ~70 lines)
BranchModel.php:           520 lines (config: ~60 lines)
PlatformStaffModel.php:    250 lines (config: ~50 lines)
AbstractCrudModel.php:     520 lines (shared)
---------------------------------------------------------
Total:                   2,840 lines (~660 lines saved, 19% reduction)
```

### Consistency

- **Standardized Hooks**: All entities use same hook pattern
- **Cache Invalidation**: Automatic and consistent
- **Error Handling**: Centralized error logging
- **Format Arrays**: Dynamic generation based on data

### Maintainability

- **Single Source of Truth**: CRUD logic in one place
- **Type Safety**: Full type hints and return types
- **Documentation**: Comprehensive PHPDoc
- **Testing**: Easier to test shared logic

---

## Quick Start

### Step 1: Extend AbstractCrudModel

```php
<?php
namespace WPCustomer\Models\Customer;

use WPAppCore\Models\Crud\AbstractCrudModel;
use WPCustomer\Cache\CustomerCache;

class CustomerModel extends AbstractCrudModel {

    public function __construct() {
        parent::__construct(CustomerCache::getInstance());
    }

    // Implement 7 abstract methods (see below)
}
```

### Step 2: Implement Abstract Methods

```php
protected function getTableName(): string {
    global $wpdb;
    return $wpdb->prefix . 'app_customers';
}

protected function getCacheKey(): string {
    return 'Customer';  // For getCustomer(), setCustomer()
}

protected function getEntityName(): string {
    return 'customer';  // For hook names
}

protected function getPluginPrefix(): string {
    return 'wp_customer';  // For hook names
}

protected function getAllowedFields(): array {
    return ['code', 'name', 'email', 'phone', 'address', 'status'];
}

protected function prepareInsertData(array $data): array {
    return [
        'code' => $data['code'],
        'name' => $data['name'],
        'email' => $data['email'] ?? null,
        'phone' => $data['phone'] ?? null,
        'address' => $data['address'] ?? null,
        'status' => $data['status'] ?? 'aktif',
        'user_id' => get_current_user_id(),
        'created_at' => current_time('mysql')
    ];
}

protected function getFormatMap(): array {
    return [
        'id' => '%d',
        'code' => '%s',
        'name' => '%s',
        'email' => '%s',
        'phone' => '%s',
        'address' => '%s',
        'status' => '%s',
        'user_id' => '%d',
        'created_at' => '%s',
        'updated_at' => '%s'
    ];
}
```

### Step 3: Use CRUD Methods

```php
// In CustomerController
class CustomerController extends AbstractCrudController {

    public function store(): void {
        $data = [
            'code' => 'CUST001',
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];

        // ✅ FREE! Inherited from AbstractCrudModel
        $new_id = $this->model->create($data);
    }

    public function show(): void {
        // ✅ FREE! With caching
        $customer = $this->model->find(123);
    }

    public function update(): void {
        $data = ['name' => 'Jane Doe'];

        // ✅ FREE! With field filtering
        $success = $this->model->update(123, $data);
    }

    public function delete(): void {
        // ✅ FREE! With cache invalidation
        $success = $this->model->delete(123);
    }
}
```

**That's it!** You get full CRUD operations with hooks, caching, and validation.

---

## Architecture

### Class Hierarchy

```
AbstractCrudModel (abstract)
├── CustomerModel
├── AgencyModel
├── BranchModel
├── PlatformStaffModel
├── AgencyEmployeeModel
├── CustomerEmployeeModel
└── DivisionModel
```

### Method Responsibility

| Method | Responsibility | Location |
|--------|---------------|----------|
| `find()` | Retrieve with cache | AbstractCrudModel ✅ |
| `create()` | Insert with hooks | AbstractCrudModel ✅ |
| `update()` | Update with filtering | AbstractCrudModel ✅ |
| `delete()` | Delete with cache | AbstractCrudModel ✅ |
| `buildFormatArray()` | Format array generation | AbstractCrudModel ✅ |
| `invalidateCache()` | Cache invalidation | AbstractCrudModel ✅ |
| `getTableName()` | Entity configuration | Child class 📝 |
| `getCacheKey()` | Entity configuration | Child class 📝 |
| `getEntityName()` | Entity configuration | Child class 📝 |
| `getPluginPrefix()` | Entity configuration | Child class 📝 |
| `getAllowedFields()` | Entity configuration | Child class 📝 |
| `prepareInsertData()` | Entity configuration | Child class 📝 |
| `getFormatMap()` | Entity configuration | Child class 📝 |
| Custom queries | Business logic | Child class 📝 |

---

## Abstract Methods

Child classes **MUST** implement these 7 methods:

---

### 1. getTableName()

**Purpose**: Return full database table name with WordPress prefix.

**Signature:**
```php
abstract protected function getTableName(): string;
```

**Implementation:**
```php
protected function getTableName(): string {
    global $wpdb;
    return $wpdb->prefix . 'app_customers';
}
```

**Used In:**
- `find()` - SELECT query
- `create()` - INSERT query
- `update()` - UPDATE query
- `delete()` - DELETE query

---

### 2. getCacheKey()

**Purpose**: Return cache method name prefix.

**Signature:**
```php
abstract protected function getCacheKey(): string;
```

**Implementation:**
```php
protected function getCacheKey(): string {
    return 'Customer';  // Results in getCustomer(), setCustomer()
}
```

**Used In:**
- `find()` - Calls `get{CacheKey}($id)`, `set{CacheKey}($id, $data)`
- `invalidateCache()` - Calls `invalidate{CacheKey}Cache($id)`

**Cache Method Examples:**

| Cache Key | Get Method | Set Method | Invalidate Method |
|-----------|-----------|-----------|-------------------|
| `Customer` | `getCustomer()` | `setCustomer()` | `invalidateCustomerCache()` |
| `Agency` | `getAgency()` | `setAgency()` | `invalidateAgencyCache()` |
| `Staff` | `getStaff()` | `setStaff()` | `invalidateStaffCache()` |

---

### 3. getEntityName()

**Purpose**: Return entity name for hook names (singular, lowercase, underscores).

**Signature:**
```php
abstract protected function getEntityName(): string;
```

**Implementation:**
```php
// Single word
protected function getEntityName(): string {
    return 'customer';
}

// Multi-word (use underscores)
protected function getEntityName(): string {
    return 'platform_staff';  // NOT 'platformStaff' or 'platform-staff'
}
```

**Used In:**
- `create()` - Hook names: `{plugin}_{entity}_before_insert`, `{plugin}_{entity}_created`

**Hook Name Examples:**

| Entity Name | Before Insert Hook | After Create Hook |
|-------------|-------------------|------------------|
| `customer` | `wp_customer_before_insert` | `wp_customer_created` |
| `agency` | `wp_agency_before_insert` | `wp_agency_created` |
| `platform_staff` | `wp_app_core_platform_staff_before_insert` | `wp_app_core_platform_staff_created` |

---

### 4. getPluginPrefix()

**Purpose**: Return plugin prefix for hook names.

**Signature:**
```php
abstract protected function getPluginPrefix(): string;
```

**Implementation:**
```php
// Customer plugin
protected function getPluginPrefix(): string {
    return 'wp_customer';
}

// Agency plugin
protected function getPluginPrefix(): string {
    return 'wp_agency';
}

// Core plugin (multi-word entities)
protected function getPluginPrefix(): string {
    return 'wp_app_core';  // NOT 'wp_app_core_platform'
}
```

**Used In:**
- `create()` - Hook names: `{plugin}_{entity}_before_insert`, `{plugin}_{entity}_created`

---

### 5. getAllowedFields()

**Purpose**: Return list of fields that can be updated.

**Signature:**
```php
abstract protected function getAllowedFields(): array;
```

**Implementation:**
```php
protected function getAllowedFields(): array {
    return [
        'code',
        'name',
        'email',
        'phone',
        'address',
        'status'
    ];

    // ❌ Typically excluded:
    // - 'id' (never updated)
    // - 'user_id' (set on creation only)
    // - 'created_at' (set on creation only)
}
```

**Used In:**
- `update()` - Filters incoming data to allowed fields only

**Example:**
```php
// Request data
$data = [
    'name' => 'Jane Doe',
    'email' => 'jane@example.com',
    'id' => 999,              // ❌ Filtered out
    'user_id' => 123,         // ❌ Filtered out
    'forbidden_field' => 'x'  // ❌ Filtered out
];

$this->model->update($id, $data);

// Actually updates:
// UPDATE wp_app_customers
// SET name = 'Jane Doe', email = 'jane@example.com'
// WHERE id = 123
```

---

### 6. prepareInsertData()

**Purpose**: Transform request data into database insert format.

**Signature:**
```php
abstract protected function prepareInsertData(array $data): array;
```

**Implementation:**
```php
protected function prepareInsertData(array $data): array {
    return [
        // Required fields
        'code' => $data['code'],
        'name' => $data['name'],

        // Optional fields (with defaults)
        'email' => $data['email'] ?? null,
        'phone' => $data['phone'] ?? null,
        'address' => $data['address'] ?? null,
        'status' => $data['status'] ?? 'aktif',

        // Auto-populated fields
        'user_id' => get_current_user_id(),
        'created_at' => current_time('mysql')
    ];
}
```

**Used In:**
- `create()` - Called before hook filter, before database insert

**Flow:**
```
Request Data
    ↓
prepareInsertData()  ← You implement this
    ↓
before_insert filter hook
    ↓
Database INSERT
    ↓
after create action hook
```

**Best Practices:**

✅ **DO:**
```php
// Set defaults for optional fields
'status' => $data['status'] ?? 'aktif',

// Add auto-populated fields
'user_id' => get_current_user_id(),
'created_at' => current_time('mysql'),

// Transform data if needed
'slug' => sanitize_title($data['name']),

// Handle nullable fields
'email' => $data['email'] ?? null,
```

❌ **DON'T:**
```php
// Don't validate here (do in controller)
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    throw new \Exception('Invalid email');
}

// Don't add 'id' (unless you know what you're doing)
'id' => $data['id'],  // Use static ID injection instead

// Don't call external services (do in hooks)
'avatar_url' => $this->uploadAvatar($data['avatar']);
```

---

### 7. getFormatMap()

**Purpose**: Map field names to wpdb format strings.

**Signature:**
```php
abstract protected function getFormatMap(): array;
```

**Implementation:**
```php
protected function getFormatMap(): array {
    return [
        // Integer fields
        'id' => '%d',
        'user_id' => '%d',
        'parent_id' => '%d',

        // String fields
        'code' => '%s',
        'name' => '%s',
        'email' => '%s',
        'phone' => '%s',
        'address' => '%s',
        'status' => '%s',

        // Datetime fields (stored as strings)
        'created_at' => '%s',
        'updated_at' => '%s'
    ];
}
```

**Used In:**
- `buildFormatArray()` - Generates format array for wpdb operations

**How It Works:**
```php
// Data to insert
$insert_data = [
    'code' => 'CUST001',
    'name' => 'John Doe',
    'user_id' => 123
];

// buildFormatArray() generates:
$format = ['%s', '%s', '%d'];  // Based on keys and format map

// wpdb uses it:
$wpdb->insert($table, $insert_data, $format);
```

**Format String Reference:**

| Format | Data Type | Examples |
|--------|-----------|----------|
| `%d` | Integer | `id`, `user_id`, `parent_id`, `count` |
| `%s` | String | `name`, `email`, `code`, `status`, `created_at` |
| `%f` | Float | `price`, `tax_rate`, `latitude`, `longitude` |

---

## Concrete Methods

These methods are **FREE** - inherited from `AbstractCrudModel`:

---

### 1. find()

**Purpose**: Retrieve entity by ID with caching.

**Signature:**
```php
public function find(int $id): ?object
```

**Flow:**
```
1. Check cache: $this->cache->get{CacheKey}($id)
2. If cached: return cached data
3. If not cached:
   - Query database
   - Store in cache
   - Return result
```

**Usage:**
```php
// Basic usage
$customer = $this->model->find(123);

if ($customer) {
    echo $customer->name;
    echo $customer->email;
} else {
    // Not found
}
```

**Return Value:**
- `object|null` - Database row as object, or `null` if not found

**Performance:**
- First call: Database query + cache set
- Subsequent calls: Cache hit (fast!)

**Example with Error Handling:**
```php
try {
    $customer = $this->model->find($id);

    if (!$customer) {
        throw new \Exception('Customer not found');
    }

    // Use customer data
    return $customer;

} catch (\Exception $e) {
    error_log($e->getMessage());
    return null;
}
```

---

### 2. create()

**Purpose**: Create new entity with hooks and cache management.

**Signature:**
```php
public function create(array $data): ?int
```

**Flow:**
```
1. prepareInsertData($data)
2. apply_filters('{plugin}_{entity}_before_insert', ...)
3. Handle static ID injection (if present)
4. buildFormatArray()
5. $wpdb->insert()
6. invalidateCache()
7. do_action('{plugin}_{entity}_created', ...)
8. Return new ID
```

**Usage:**
```php
// Basic usage
$data = [
    'code' => 'CUST001',
    'name' => 'John Doe',
    'email' => 'john@example.com'
];

$new_id = $this->model->create($data);

if ($new_id) {
    echo "Customer created with ID: $new_id";
} else {
    echo "Failed to create customer";
}
```

**Return Value:**
- `int|null` - New entity ID on success, `null` on failure

**Hooks Triggered:**

**1. Before Insert Filter:**
```php
add_filter('wp_customer_before_insert', function($insert_data, $original_data) {
    // Modify data before insert
    $insert_data['code'] = strtoupper($insert_data['code']);
    return $insert_data;
}, 10, 2);
```

**2. After Create Action:**
```php
add_action('wp_customer_created', function($customer_id, $insert_data) {
    // Auto-create "Pusat" branch
    $branch_model = new BranchModel();
    $branch_model->create([
        'customer_id' => $customer_id,
        'name' => 'Pusat',
        'code' => 'PST'
    ]);
}, 10, 2);
```

**Static ID Injection Example:**
```php
// In demo data plugin
add_filter('wp_customer_before_insert', function($insert_data, $original_data) {
    if (defined('WP_DEMO_MODE')) {
        $insert_data['id'] = 999;  // Force specific ID
    }
    return $insert_data;
}, 10, 2);

$new_id = $this->model->create(['name' => 'Demo Customer']);
// $new_id will be 999
```

---

### 3. update()

**Purpose**: Update entity with field filtering and cache invalidation.

**Signature:**
```php
public function update(int $id, array $data): bool
```

**Flow:**
```
1. find($id) - Verify exists
2. Filter data to getAllowedFields() only
3. buildFormatArray()
4. $wpdb->update()
5. invalidateCache()
```

**Usage:**
```php
// Basic usage
$data = [
    'name' => 'Jane Doe',
    'email' => 'jane@example.com'
];

$success = $this->model->update(123, $data);

if ($success) {
    echo "Customer updated";
} else {
    echo "Update failed or no changes";
}
```

**Return Value:**
- `bool` - `true` on success, `false` on failure or if entity not found

**Field Filtering:**
```php
// Allowed fields (from getAllowedFields())
['code', 'name', 'email', 'phone', 'address', 'status']

// Request data
$data = [
    'name' => 'Jane Doe',    // ✅ Allowed
    'email' => 'jane@...',   // ✅ Allowed
    'id' => 999,             // ❌ Filtered out
    'user_id' => 123,        // ❌ Filtered out
    'hack' => 'value'        // ❌ Filtered out
];

// Actually updates:
// UPDATE wp_app_customers
// SET name = 'Jane Doe', email = 'jane@example.com'
// WHERE id = 123
```

**Edge Cases:**

```php
// Empty data (no allowed fields)
$result = $this->model->update(123, []);
// Returns: false

// Entity not found
$result = $this->model->update(999, ['name' => 'Test']);
// Returns: false

// No actual changes (wpdb returns 0)
$result = $this->model->update(123, ['name' => 'Same Name']);
// Returns: false (wpdb update returns 0 for no changes)
```

---

### 4. delete()

**Purpose**: Delete entity with cache invalidation.

**Signature:**
```php
public function delete(int $id): bool
```

**Flow:**
```
1. find($id) - Verify exists
2. $wpdb->delete()
3. invalidateCache()
```

**Usage:**
```php
// Basic usage
$success = $this->model->delete(123);

if ($success) {
    echo "Customer deleted";
} else {
    echo "Delete failed or not found";
}
```

**Return Value:**
- `bool` - `true` on success, `false` on failure or if entity not found

**Important Notes:**

⚠️ **Hard Delete:**
- This is a HARD delete (permanent removal)
- No soft delete support (implement in child class if needed)

⚠️ **Foreign Key Constraints:**
```php
// If customer has branches, delete may fail
$success = $this->model->delete($customer_id);
// May fail if foreign key constraints exist

// Solution: Delete related entities first
$this->branch_model->deleteByCustomer($customer_id);
$this->model->delete($customer_id);
```

**Soft Delete Example (Child Class):**
```php
class CustomerModel extends AbstractCrudModel {

    public function softDelete(int $id): bool {
        return $this->update($id, [
            'status' => 'deleted',
            'deleted_at' => current_time('mysql')
        ]);
    }

    // Override hard delete to prevent accidental use
    public function delete(int $id): bool {
        throw new \Exception('Use softDelete() instead');
    }
}
```

---

### 5. buildFormatArray()

**Purpose**: Generate wpdb format array based on data keys.

**Signature:**
```php
protected function buildFormatArray(array $data): array
```

**Usage:**
```php
// Internal use only (called by create() and update())
// You don't need to call this directly

// Example for understanding:
$data = [
    'code' => 'CUST001',  // %s
    'name' => 'John',     // %s
    'user_id' => 123      // %d
];

$format = $this->buildFormatArray($data);
// Result: ['%s', '%s', '%d']
```

**How It Works:**
```php
protected function buildFormatArray(array $data): array {
    $format = [];
    $format_map = $this->getFormatMap();  // Your implementation

    foreach (array_keys($data) as $key) {
        $format[] = $format_map[$key] ?? '%s';  // Default to %s
    }

    return $format;
}
```

**Dynamic Format Generation:**

When hooks inject new fields (like static IDs), format array adjusts automatically:

```php
// Before hook
$insert_data = ['name' => 'John', 'email' => 'john@...'];
$format = ['%s', '%s'];

// Hook adds 'id' field
add_filter('wp_customer_before_insert', function($data) {
    $data['id'] = 999;
    return $data;
});

// After hook
$insert_data = ['id' => 999, 'name' => 'John', 'email' => 'john@...'];
$format = ['%d', '%s', '%s'];  // ✅ Automatically includes %d for 'id'
```

---

### 6. invalidateCache()

**Purpose**: Clear entity cache and any additional cache keys.

**Signature:**
```php
protected function invalidateCache(int $id, ...$additional_keys): void
```

**Usage:**
```php
// Internal use only (called by create(), update(), delete())
// You can call it manually if needed

// Basic usage
$this->invalidateCache($customer_id);

// With additional keys (e.g., invalidate related branch)
$this->invalidateCache($customer_id, $branch_id);
```

**How It Works:**
```php
protected function invalidateCache(int $id, ...$additional_keys): void {
    $cache_key = $this->getCacheKey();  // e.g., 'Customer'
    $invalidate_method = 'invalidate' . $cache_key . 'Cache';
    // Results in: 'invalidateCustomerCache'

    if (method_exists($this->cache, $invalidate_method)) {
        $this->cache->$invalidate_method($id, ...$additional_keys);
    }
}
```

**Cache Handler Example:**
```php
class CustomerCache {

    public function invalidateCustomerCache(int $id, ...$additional_keys): void {
        // Clear customer cache
        wp_cache_delete("customer_{$id}", 'wp_customer');

        // Clear list cache
        wp_cache_delete('customer_list', 'wp_customer');

        // Clear additional keys (if provided)
        foreach ($additional_keys as $key) {
            wp_cache_delete("customer_related_{$key}", 'wp_customer');
        }
    }
}
```

---

## Hook System

AbstractCrudModel provides standardized hooks for all entities:

---

### Before Insert Filter

**Hook Pattern:**
```
{plugin}_{entity}_before_insert
```

**Examples:**
- `wp_customer_before_insert`
- `wp_agency_before_insert`
- `wp_app_core_platform_staff_before_insert`

**Purpose:**
- Modify data before database insertion
- Add static IDs (demo data, migrations)
- Data sync (multi-plugin integration)
- Auto-populate fields

**Signature:**
```php
apply_filters('{plugin}_{entity}_before_insert', array $insert_data, array $original_data)
```

**Parameters:**
- `$insert_data`: Prepared data from `prepareInsertData()` (modify this)
- `$original_data`: Original request data (read-only)

**Return:**
- `array` Modified insert data

---

### Use Case 1: Static ID Injection (Demo Data)

```php
/**
 * Demo data plugin: Force specific IDs
 */
add_filter('wp_customer_before_insert', function($insert_data, $original_data) {
    if (defined('WP_DEMO_MODE')) {
        // Static IDs for demo data
        static $demo_id = 1000;
        $insert_data['id'] = $demo_id++;
    }
    return $insert_data;
}, 10, 2);

// Usage
$this->model->create(['name' => 'Demo Customer 1']);  // ID: 1000
$this->model->create(['name' => 'Demo Customer 2']);  // ID: 1001
```

---

### Use Case 2: Data Transformation

```php
/**
 * Auto-uppercase customer code
 */
add_filter('wp_customer_before_insert', function($insert_data, $original_data) {
    if (isset($insert_data['code'])) {
        $insert_data['code'] = strtoupper($insert_data['code']);
    }
    return $insert_data;
}, 10, 2);

// Usage
$this->model->create(['code' => 'cust001']);
// Saved as: 'CUST001'
```

---

### Use Case 3: Cross-Plugin Data Sync

```php
/**
 * wp-customer plugin: Inject agency_id from user meta
 */
add_filter('wp_customer_before_insert', function($insert_data, $original_data) {
    $user_id = $insert_data['user_id'] ?? get_current_user_id();
    $agency_id = get_user_meta($user_id, 'agency_id', true);

    if ($agency_id) {
        $insert_data['agency_id'] = $agency_id;
    }

    return $insert_data;
}, 10, 2);
```

---

### After Create Action

**Hook Pattern:**
```
{plugin}_{entity}_created
```

**Examples:**
- `wp_customer_created`
- `wp_agency_created`
- `wp_app_core_platform_staff_created`

**Purpose:**
- Auto-create related entities
- Send notifications
- Logging and audit trails
- Trigger external services

**Signature:**
```php
do_action('{plugin}_{entity}_created', int $entity_id, array $insert_data)
```

**Parameters:**
- `$entity_id`: New entity ID
- `$insert_data`: Data that was inserted (including hooked modifications)

---

### Use Case 1: Auto-Create Related Entities

```php
/**
 * Auto-create "Pusat" branch when customer is created
 */
add_action('wp_customer_created', function($customer_id, $insert_data) {
    $branch_model = new BranchModel();
    $branch_model->create([
        'customer_id' => $customer_id,
        'name' => 'Pusat',
        'code' => 'PST',
        'phone' => $insert_data['phone'] ?? null,
        'email' => $insert_data['email'] ?? null,
        'address' => $insert_data['address'] ?? null
    ]);
}, 10, 2);
```

---

### Use Case 2: Auto-Create Admin Employee

```php
/**
 * Auto-create admin employee when agency is created
 */
add_action('wp_agency_created', function($agency_id, $insert_data) {
    $employee_model = new AgencyEmployeeModel();
    $employee_model->create([
        'agency_id' => $agency_id,
        'user_id' => $insert_data['user_id'],
        'role' => 'admin_dinas',
        'status' => 'aktif'
    ]);
}, 10, 2);
```

---

### Use Case 3: Send Welcome Email

```php
/**
 * Send welcome email to new customer
 */
add_action('wp_customer_created', function($customer_id, $insert_data) {
    $email = $insert_data['email'] ?? null;

    if ($email) {
        wp_mail(
            $email,
            'Welcome to Our Platform',
            sprintf(
                'Hello %s, your account has been created.',
                $insert_data['name']
            )
        );
    }
}, 10, 2);
```

---

### Use Case 4: Audit Logging

```php
/**
 * Log all customer creations
 */
add_action('wp_customer_created', function($customer_id, $insert_data) {
    error_log(sprintf(
        '[AUDIT] Customer created: ID=%d, Code=%s, User=%d, Time=%s',
        $customer_id,
        $insert_data['code'],
        $insert_data['user_id'],
        current_time('mysql')
    ));
}, 10, 2);
```

---

## Static ID Injection

Static ID injection allows you to specify exact IDs when creating entities. This is useful for:

- **Demo data**: Consistent IDs across environments
- **Migrations**: Preserve IDs when moving data
- **Testing**: Predictable IDs for assertions
- **Data sync**: Match IDs from external systems

---

### How It Works

**1. Normal Creation (Auto-Increment):**
```php
$id = $this->model->create(['name' => 'John']);
// ID: 1, 2, 3, ... (auto-increment)
```

**2. With Static ID Injection:**
```php
add_filter('wp_customer_before_insert', function($insert_data) {
    $insert_data['id'] = 999;  // Force specific ID
    return $insert_data;
});

$id = $this->model->create(['name' => 'John']);
// ID: 999 (static)
```

---

### Detection Logic

AbstractCrudModel detects static IDs automatically:

```php
// In create() method:

// 1. Prepare data (without 'id')
$insert_data = $this->prepareInsertData($data);
// Result: ['name' => 'John', ...]

// 2. Apply hook (may add 'id')
$insert_data = apply_filters('...before_insert', $insert_data, $data);
// Result: ['id' => 999, 'name' => 'John', ...]

// 3. Detect static ID
if (isset($insert_data['id']) && !isset($data['id'])) {
    // Hook added 'id' -> static ID injection
    $static_id = $insert_data['id'];
}

// 4. Use static ID instead of insert_id
$new_id = $static_id ?? $wpdb->insert_id;
```

**Key Detection:**
- `isset($insert_data['id'])` - Hook added 'id'
- `!isset($data['id'])` - Not in original request
- **Conclusion**: Static ID injection

---

### Demo Data Example

```php
/**
 * Demo Data Installer
 */
class CustomerDemoData {

    private $demo_customers = [
        ['id' => 1000, 'code' => 'DEMO001', 'name' => 'Demo Customer 1'],
        ['id' => 1001, 'code' => 'DEMO002', 'name' => 'Demo Customer 2'],
        ['id' => 1002, 'code' => 'DEMO003', 'name' => 'Demo Customer 3'],
    ];

    public function install(): void {
        // Add static ID injection hook
        add_filter('wp_customer_before_insert', [$this, 'inject_static_id'], 10, 2);

        $model = new CustomerModel();

        foreach ($this->demo_customers as $customer) {
            $this->current_static_id = $customer['id'];
            $model->create($customer);
        }

        // Remove hook
        remove_filter('wp_customer_before_insert', [$this, 'inject_static_id']);
    }

    public function inject_static_id($insert_data, $original_data) {
        if (isset($this->current_static_id)) {
            $insert_data['id'] = $this->current_static_id;
        }
        return $insert_data;
    }
}

// Usage
$installer = new CustomerDemoData();
$installer->install();
// Creates customers with IDs: 1000, 1001, 1002
```

---

### Migration Example

```php
/**
 * Migrate customers from old system
 */
class CustomerMigration {

    public function migrate_from_old_system(): void {
        global $wpdb;

        // Get customers from old table
        $old_customers = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}old_customers"
        );

        // Add static ID injection hook
        add_filter('wp_customer_before_insert', [$this, 'preserve_old_id'], 10, 2);

        $model = new CustomerModel();

        foreach ($old_customers as $old) {
            $this->old_id = $old->id;  // Preserve old ID

            $model->create([
                'code' => $old->code,
                'name' => $old->name,
                'email' => $old->email
            ]);
        }

        remove_filter('wp_customer_before_insert', [$this, 'preserve_old_id']);
    }

    public function preserve_old_id($insert_data, $original_data) {
        if (isset($this->old_id)) {
            $insert_data['id'] = $this->old_id;
        }
        return $insert_data;
    }
}
```

---

## Cache Management

AbstractCrudModel integrates with entity-specific cache handlers:

---

### Cache Methods Required

Your cache handler must implement:

```php
class CustomerCache {

    /**
     * Get customer from cache
     */
    public function getCustomer(int $id) {
        return wp_cache_get("customer_{$id}", 'wp_customer');
    }

    /**
     * Set customer in cache
     */
    public function setCustomer(int $id, $data): void {
        wp_cache_set("customer_{$id}", $data, 'wp_customer', 3600);
    }

    /**
     * Invalidate customer cache
     */
    public function invalidateCustomerCache(int $id, ...$additional_keys): void {
        wp_cache_delete("customer_{$id}", 'wp_customer');
        wp_cache_delete('customer_list', 'wp_customer');

        // Additional keys (e.g., branch IDs)
        foreach ($additional_keys as $key) {
            wp_cache_delete("customer_branch_{$key}", 'wp_customer');
        }
    }
}
```

---

### Cache Flow

**1. find() - Read with Cache:**
```
find($id)
    ↓
getCustomer($id)  ← Check cache
    ↓
[Cache Hit] → Return cached data ✅
    ↓
[Cache Miss] → Query database
    ↓
setCustomer($id, $data)  ← Store in cache
    ↓
Return data
```

**2. create() - Write with Invalidation:**
```
create($data)
    ↓
... insert to database ...
    ↓
invalidateCustomerCache($id)  ← Clear cache
    ↓
Return $id
```

**3. update() - Write with Invalidation:**
```
update($id, $data)
    ↓
... update database ...
    ↓
invalidateCustomerCache($id)  ← Clear cache
    ↓
Return true
```

**4. delete() - Write with Invalidation:**
```
delete($id)
    ↓
... delete from database ...
    ↓
invalidateCustomerCache($id)  ← Clear cache
    ↓
Return true
```

---

### Cache Key Naming Convention

| Entity | Cache Key | Get Method | Set Method | Invalidate Method |
|--------|-----------|-----------|-----------|-------------------|
| Customer | `Customer` | `getCustomer()` | `setCustomer()` | `invalidateCustomerCache()` |
| Agency | `Agency` | `getAgency()` | `setAgency()` | `invalidateAgencyCache()` |
| Branch | `Branch` | `getBranch()` | `setBranch()` | `invalidateBranchCache()` |
| Platform Staff | `Staff` | `getStaff()` | `setStaff()` | `invalidateStaffCache()` |

---

## Real-World Examples

### Example 1: CustomerModel

```php
<?php
namespace WPCustomer\Models\Customer;

use WPAppCore\Models\Crud\AbstractCrudModel;
use WPCustomer\Cache\CustomerCache;

class CustomerModel extends AbstractCrudModel {

    public function __construct() {
        parent::__construct(CustomerCache::getInstance());
    }

    // ========================================
    // ABSTRACT METHOD IMPLEMENTATIONS
    // ========================================

    protected function getTableName(): string {
        global $wpdb;
        return $wpdb->prefix . 'app_customers';
    }

    protected function getCacheKey(): string {
        return 'Customer';
    }

    protected function getEntityName(): string {
        return 'customer';
    }

    protected function getPluginPrefix(): string {
        return 'wp_customer';
    }

    protected function getAllowedFields(): array {
        return [
            'code',
            'name',
            'npwp',
            'email',
            'phone',
            'address',
            'provinsi',
            'kabupaten',
            'kecamatan',
            'kelurahan',
            'kodepos',
            'status'
        ];
    }

    protected function prepareInsertData(array $data): array {
        return [
            'code' => $data['code'],
            'name' => $data['name'],
            'npwp' => $data['npwp'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'provinsi' => $data['provinsi'] ?? null,
            'kabupaten' => $data['kabupaten'] ?? null,
            'kecamatan' => $data['kecamatan'] ?? null,
            'kelurahan' => $data['kelurahan'] ?? null,
            'kodepos' => $data['kodepos'] ?? null,
            'status' => $data['status'] ?? 'aktif',
            'user_id' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ];
    }

    protected function getFormatMap(): array {
        return [
            'id' => '%d',
            'code' => '%s',
            'name' => '%s',
            'npwp' => '%s',
            'email' => '%s',
            'phone' => '%s',
            'address' => '%s',
            'provinsi' => '%s',
            'kabupaten' => '%s',
            'kecamatan' => '%s',
            'kelurahan' => '%s',
            'kodepos' => '%s',
            'status' => '%s',
            'user_id' => '%d',
            'created_at' => '%s',
            'updated_at' => '%s'
        ];
    }

    // ========================================
    // CUSTOM METHODS (Business Logic)
    // ========================================

    /**
     * Get customer by code
     */
    public function findByCode(string $code): ?object {
        global $wpdb;
        $table = $this->getTableName();

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE code = %s",
            $code
        ));
    }

    /**
     * Get customers by user ID
     */
    public function getByUserId(int $user_id): array {
        global $wpdb;
        $table = $this->getTableName();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));
    }

    /**
     * Check if code exists
     */
    public function codeExists(string $code, ?int $exclude_id = null): bool {
        global $wpdb;
        $table = $this->getTableName();

        $sql = "SELECT COUNT(*) FROM {$table} WHERE code = %s";
        $params = [$code];

        if ($exclude_id) {
            $sql .= " AND id != %d";
            $params[] = $exclude_id;
        }

        $count = $wpdb->get_var($wpdb->prepare($sql, ...$params));
        return $count > 0;
    }
}
```

**Hooks for CustomerModel:**

```php
/**
 * Auto-create "Pusat" branch when customer is created
 */
add_action('wp_customer_created', function($customer_id, $insert_data) {
    $branch_model = new BranchModel();
    $branch_model->create([
        'customer_id' => $customer_id,
        'name' => 'Pusat',
        'code' => 'PST',
        'phone' => $insert_data['phone'] ?? null,
        'email' => $insert_data['email'] ?? null,
        'address' => $insert_data['address'] ?? null,
        'provinsi' => $insert_data['provinsi'] ?? null,
        'kabupaten' => $insert_data['kabupaten'] ?? null,
        'kecamatan' => $insert_data['kecamatan'] ?? null,
        'kelurahan' => $insert_data['kelurahan'] ?? null,
        'kodepos' => $insert_data['kodepos'] ?? null
    ]);
}, 10, 2);
```

---

### Example 2: PlatformStaffModel

```php
<?php
namespace WPAppCore\Models\Platform;

use WPAppCore\Models\Crud\AbstractCrudModel;
use WPAppCore\Cache\PlatformStaffCache;

class PlatformStaffModel extends AbstractCrudModel {

    public function __construct() {
        parent::__construct(PlatformStaffCache::getInstance());
    }

    // ========================================
    // ABSTRACT METHOD IMPLEMENTATIONS
    // ========================================

    protected function getTableName(): string {
        global $wpdb;
        return $wpdb->prefix . 'app_platform_staff';
    }

    protected function getCacheKey(): string {
        return 'Staff';
    }

    protected function getEntityName(): string {
        return 'platform_staff';
    }

    protected function getPluginPrefix(): string {
        return 'wp_app_core';
    }

    protected function getAllowedFields(): array {
        return ['name', 'email', 'phone', 'position', 'status'];
    }

    protected function prepareInsertData(array $data): array {
        return [
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'position' => $data['position'] ?? null,
            'status' => $data['status'] ?? 'active',
            'created_at' => current_time('mysql')
        ];
    }

    protected function getFormatMap(): array {
        return [
            'id' => '%d',
            'user_id' => '%d',
            'name' => '%s',
            'email' => '%s',
            'phone' => '%s',
            'position' => '%s',
            'status' => '%s',
            'created_at' => '%s',
            'updated_at' => '%s'
        ];
    }

    // ========================================
    // CUSTOM METHODS
    // ========================================

    public function findByUserId(int $user_id): ?object {
        global $wpdb;
        $table = $this->getTableName();

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d",
            $user_id
        ));
    }
}
```

---

## Best Practices

### DO ✅

**1. Keep configuration methods simple:**
```php
protected function getTableName(): string {
    global $wpdb;
    return $wpdb->prefix . 'app_customers';  // Simple, direct
}
```

**2. Set sensible defaults in prepareInsertData():**
```php
protected function prepareInsertData(array $data): array {
    return [
        'status' => $data['status'] ?? 'aktif',  // Default status
        'user_id' => get_current_user_id(),      // Auto-populate
        'created_at' => current_time('mysql')    // Auto-timestamp
    ];
}
```

**3. Include all possible fields in getFormatMap():**
```php
protected function getFormatMap(): array {
    return [
        'id' => '%d',           // Even if not in prepareInsertData()
        'user_id' => '%d',      // (hooks might add it)
        'created_at' => '%s',
        'updated_at' => '%s'    // Include all table columns
    ];
}
```

**4. Validate in controller, not model:**
```php
// ✅ Controller
public function store(): void {
    $this->validate($data);  // Validate here
    $result = $this->model->create($data);
}

// ❌ Model (don't do this)
public function create(array $data): ?int {
    if (empty($data['email'])) {
        throw new \Exception('Email required');  // Wrong place
    }
}
```

**5. Use hooks for side effects:**
```php
// ✅ Use hook for creating related entities
add_action('wp_customer_created', function($id, $data) {
    $branch_model->create([...]);
});

// ❌ Don't do it in model
public function create(array $data): ?int {
    $id = parent::create($data);
    $branch_model->create([...]);  // Wrong place
    return $id;
}
```

---

### DON'T ❌

**1. Don't add business logic to configuration methods:**
```php
// ❌ Too complex
protected function prepareInsertData(array $data): array {
    $code = $this->generateCustomerCode();  // Wrong place
    $email = $this->validateAndNormalizeEmail($data['email']);  // Wrong place

    return [
        'code' => $code,
        'email' => $email
    ];
}

// ✅ Keep it simple
protected function prepareInsertData(array $data): array {
    return [
        'code' => $data['code'],  // Just map fields
        'email' => $data['email'] ?? null
    ];
}
```

**2. Don't validate in prepareInsertData():**
```php
// ❌ Validation in model
protected function prepareInsertData(array $data): array {
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new \Exception('Invalid email');  // Wrong place
    }
    return [...]
}

// ✅ Validate in controller
public function store(): void {
    $this->validate($data);  // Right place
    $this->model->create($data);
}
```

**3. Don't manually add 'id' in prepareInsertData():**
```php
// ❌ Don't do this
protected function prepareInsertData(array $data): array {
    return [
        'id' => $data['id'],  // Will break auto-increment
        'name' => $data['name']
    ];
}

// ✅ Use hook for static IDs
add_filter('wp_customer_before_insert', function($insert_data) {
    $insert_data['id'] = 999;  // Right way
    return $insert_data;
});
```

**4. Don't override CRUD methods without calling parent:**
```php
// ❌ Breaks hook system
public function create(array $data): ?int {
    global $wpdb;
    $wpdb->insert($this->getTableName(), $data);  // No hooks!
    return $wpdb->insert_id;
}

// ✅ Extend, don't replace
public function create(array $data): ?int {
    // Add pre-processing if needed
    $data['custom_field'] = $this->processCustomField($data);

    // Call parent (includes hooks)
    return parent::create($data);
}
```

**5. Don't use raw SQL in child classes when CRUD methods exist:**
```php
// ❌ Duplicate code
public function update(int $id, array $data): bool {
    global $wpdb;
    $wpdb->update($this->getTableName(), $data, ['id' => $id]);
    $this->cache->invalidateCustomerCache($id);
    return true;
}

// ✅ Use parent method
// (It's already implemented in AbstractCrudModel!)
```

---

## Migration Guide

### Step 1: Backup Your Model

```bash
cp src/Models/Customer/CustomerModel.php src/Models/Customer/CustomerModel.php.backup
```

---

### Step 2: Add AbstractCrudModel Import

```php
// Before
namespace WPCustomer\Models\Customer;

class CustomerModel {
    // ...
}

// After
namespace WPCustomer\Models\Customer;

use WPAppCore\Models\Crud\AbstractCrudModel;  // ← Add this
use WPCustomer\Cache\CustomerCache;            // ← Add this

class CustomerModel extends AbstractCrudModel {  // ← Extend
    // ...
}
```

---

### Step 3: Add Constructor

```php
public function __construct() {
    parent::__construct(CustomerCache::getInstance());
}
```

---

### Step 4: Implement 7 Abstract Methods

Copy-paste this template and fill in your values:

```php
// ========================================
// ABSTRACT METHOD IMPLEMENTATIONS
// ========================================

protected function getTableName(): string {
    global $wpdb;
    return $wpdb->prefix . 'app_customers';  // ← Your table
}

protected function getCacheKey(): string {
    return 'Customer';  // ← Your cache key
}

protected function getEntityName(): string {
    return 'customer';  // ← Your entity name
}

protected function getPluginPrefix(): string {
    return 'wp_customer';  // ← Your plugin prefix
}

protected function getAllowedFields(): array {
    return [
        // ← List updateable fields
    ];
}

protected function prepareInsertData(array $data): array {
    return [
        // ← Map request data to database fields
    ];
}

protected function getFormatMap(): array {
    return [
        // ← Map fields to %d or %s
    ];
}
```

---

### Step 5: Remove Old CRUD Methods

**Delete these methods** (they're in AbstractCrudModel now):

```php
// ❌ Remove these:
public function find(int $id): ?object { ... }
public function create(array $data): ?int { ... }
public function update(int $id, array $data): bool { ... }
public function delete(int $id): bool { ... }
```

**Keep these methods** (entity-specific):

```php
// ✅ Keep these:
public function findByCode(string $code): ?object { ... }
public function getByUserId(int $user_id): array { ... }
public function codeExists(string $code): bool { ... }
```

---

### Step 6: Test

```php
// Test find()
$customer = $this->model->find(1);
var_dump($customer);

// Test create()
$new_id = $this->model->create([
    'code' => 'TEST001',
    'name' => 'Test Customer'
]);
echo "Created: $new_id\n";

// Test update()
$success = $this->model->update($new_id, ['name' => 'Updated Name']);
echo "Updated: " . ($success ? 'Yes' : 'No') . "\n";

// Test delete()
$success = $this->model->delete($new_id);
echo "Deleted: " . ($success ? 'Yes' : 'No') . "\n";
```

---

### Step 7: Verify Hooks

Test that hooks still work:

```php
// Add test hook
add_action('wp_customer_created', function($id, $data) {
    error_log("Customer created: ID=$id, Name={$data['name']}");
}, 10, 2);

// Create customer
$this->model->create(['code' => 'TEST', 'name' => 'Hook Test']);

// Check error log
// Should see: "Customer created: ID=123, Name=Hook Test"
```

---

## Troubleshooting

### Error: "Call to undefined method"

**Problem:**
```
Fatal error: Call to undefined method CustomerCache::getCustomer()
```

**Cause:** Cache handler doesn't implement required method.

**Solution:** Implement cache methods:
```php
class CustomerCache {
    public function getCustomer(int $id) {
        return wp_cache_get("customer_{$id}", 'wp_customer');
    }

    public function setCustomer(int $id, $data): void {
        wp_cache_set("customer_{$id}", $data, 'wp_customer', 3600);
    }

    public function invalidateCustomerCache(int $id): void {
        wp_cache_delete("customer_{$id}", 'wp_customer');
    }
}
```

---

### Error: "Undefined index 'field_name'"

**Problem:**
```
Notice: Undefined index: email in prepareInsertData()
```

**Cause:** Missing null coalescing operator.

**Solution:**
```php
// ❌ Before
'email' => $data['email'],

// ✅ After
'email' => $data['email'] ?? null,
```

---

### Hook Not Firing

**Problem:** `wp_customer_created` action not firing.

**Checklist:**
1. ✅ Hook name matches pattern: `{plugin}_{entity}_created`
2. ✅ `getPluginPrefix()` returns `'wp_customer'`
3. ✅ `getEntityName()` returns `'customer'`
4. ✅ Hook added before `create()` is called

**Debug:**
```php
// Add debug hook
add_action('wp_customer_created', function($id, $data) {
    error_log("HOOK FIRED: ID=$id");
}, 1, 2);  // Priority 1 (runs first)

// Create customer
$this->model->create(['name' => 'Test']);

// Check error log
```

---

### Cache Not Invalidating

**Problem:** Old data still in cache after update.

**Checklist:**
1. ✅ `getCacheKey()` returns correct value
2. ✅ Cache handler implements `invalidate{CacheKey}Cache()`
3. ✅ Method signature matches: `public function invalidateCustomerCache(int $id, ...$additional_keys): void`

**Debug:**
```php
// Test cache invalidation
$this->model->update(123, ['name' => 'Updated']);

// Manually check
wp_cache_delete('customer_123', 'wp_customer');

// Try find() again
$customer = $this->model->find(123);
```

---

### Static ID Not Working

**Problem:** Static ID injection not preserving ID.

**Checklist:**
1. ✅ Hook priority is correct (10 or lower)
2. ✅ Hook returns modified `$insert_data`
3. ✅ 'id' is NOT in original request data

**Debug:**
```php
add_filter('wp_customer_before_insert', function($insert_data, $original_data) {
    error_log("BEFORE: " . json_encode($insert_data));
    $insert_data['id'] = 999;
    error_log("AFTER: " . json_encode($insert_data));
    return $insert_data;
}, 10, 2);
```

---

## Summary

### What You Get FREE

By extending `AbstractCrudModel`:

- ✅ **find()** - 20 lines → 0 lines (100% saved)
- ✅ **create()** - 80 lines → 0 lines (100% saved)
- ✅ **update()** - 50 lines → 0 lines (100% saved)
- ✅ **delete()** - 30 lines → 0 lines (100% saved)
- ✅ **Hook system** - Standardized before/after hooks
- ✅ **Cache management** - Automatic invalidation
- ✅ **Static ID support** - Demo data, migrations
- ✅ **Format arrays** - Dynamic generation

**Total savings per model: ~180 lines (60-80% of CRUD code)**

---

### What You Still Write

- 📝 7 abstract methods (~50-80 lines of configuration)
- 📝 Custom business logic methods
- 📝 Complex queries
- 📝 Entity-specific validation

---

### Expected Code Reduction

**Across 7 models:**
- Before: ~1,180 lines of CRUD code
- After: ~260 lines of configuration
- **Savings: ~920 lines (78% reduction)**

---

## Quick Reference

### Abstract Methods (Required)

```php
protected function getTableName(): string;
protected function getCacheKey(): string;
protected function getEntityName(): string;
protected function getPluginPrefix(): string;
protected function getAllowedFields(): array;
protected function prepareInsertData(array $data): array;
protected function getFormatMap(): array;
```

### Concrete Methods (FREE)

```php
public function find(int $id): ?object;
public function create(array $data): ?int;
public function update(int $id, array $data): bool;
public function delete(int $id): bool;
protected function buildFormatArray(array $data): array;
protected function invalidateCache(int $id, ...$additional_keys): void;
```

### Hook Patterns

```php
// Before insert filter
apply_filters('{plugin}_{entity}_before_insert', $insert_data, $original_data);

// After create action
do_action('{plugin}_{entity}_created', $entity_id, $insert_data);
```

---

**Last Updated:** 2025-01-02
**Version:** 1.0.0
**Author:** arisciwek
