# DataTable System Architecture

## Overview

Sistem DataTable dirancang dengan arsitektur modular yang memisahkan core functionality dari business logic. Terinspirasi dari Perfex CRM, sistem ini menggunakan WordPress hooks untuk extensibility.

## File Reference

**Core Files:**
- `src/Models/DataTable/DataTableModel.php` - Base model class
- `src/Models/DataTable/DataTableQueryBuilder.php` - SQL query builder
- `src/Controllers/DataTable/DataTableController.php` - AJAX controller
- `src/Controllers/DataTable/DataTableAssetsController.php` - Asset management
- `src/Views/DataTable/Templates/*.php` - View templates

**Documentation:**
- `docs/datatable/ARCHITECTURE.md` - This file (architecture overview)
- `docs/datatable/core/IMPLEMENTATION.md` - Implementation details
- `docs/datatable/modules/EXTENSION-GUIDE.md` - Module extension guide

---

## Architecture Principles

### 1. **Separation of Concerns**

```
┌─────────────────────────────────────────────┐
│           WP-APP-CORE (Core)                │
│  Responsibility: Base functionality         │
│  - DataTable base class                     │
│  - Query builder                            │
│  - AJAX controller                          │
│  - Hook points                              │
└─────────────────────────────────────────────┘
              ▲         ▲          ▲
              │         │          │
    ┌─────────┘    ┌────┴─────┐    └─────────┐
    │              │          │              │
┌───┴────┐    ┌────┴────┐  ┌──┴────┐   ┌─────┴───┐
│ WP-    │    │ WP-     │  │ WP-   │   │ Other   │
│CUSTOMER│    │ AGENCY  │  │STAFF  │   │ Modules │
└────────┘    └─────────┘  └───────┘   └─────────┘
Business Logic Modules
- Custom filters
- Table-specific logic
- Permissions
- UI customizations
```

### 2. **MVC Pattern**

```
┌──────────────────────────────────────────────────────┐
│                    VIEW LAYER                        │
│  ┌────────────────────────────────────────────────┐  │
│  │  HTML Template + DataTables.js                 │  │
│  │  - Table structure                             │  │
│  │  - Column definitions                          │  │
│  │  - AJAX configuration                          │  │
│  └────────────────────────────────────────────────┘  │
└─────────────────────┬────────────────────────────────┘
                      │ AJAX Request
                      ▼
┌──────────────────────────────────────────────────────┐
│                 CONTROLLER LAYER                      │
│  ┌────────────────────────────────────────────────┐  │
│  │  DataTableController                            │  │
│  │  - Route AJAX requests                          │  │
│  │  - Security validation                          │  │
│  │  - Permission checks                            │  │
│  │  - Response formatting                          │  │
│  └────────────────────────────────────────────────┘  │
└─────────────────────┬────────────────────────────────┘
                      │
                      ▼
┌──────────────────────────────────────────────────────┐
│                   MODEL LAYER                         │
│  ┌────────────────────────────────────────────────┐  │
│  │  DataTableModel (Base)                          │  │
│  │  - Data retrieval                               │  │
│  │  - Query building                               │  │
│  │  - Filtering & sorting                          │  │
│  │  - Data formatting                              │  │
│  │                                                  │  │
│  │  Extended by:                                   │  │
│  │  - CustomerDataTableModel                       │  │
│  │  - AgencyDataTableModel                         │  │
│  │  - StaffDataTableModel                          │  │
│  └────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────┘
```

### 3. **Hook-Based Extension System**

```
Core Model Execute Flow:

1. get_datatable_data()
   │
   ├─► Get Columns
   │   └─► apply_filters('wpapp_datatable_{table}_columns')
   │       ├─ wp-customer adds custom columns
   │       ├─ wp-agency adds custom columns
   │       └─ Result: merged columns
   │
   ├─► Build WHERE Conditions
   │   └─► apply_filters('wpapp_datatable_{table}_where')
   │       ├─ wp-customer adds status filter
   │       ├─ wp-agency adds agency_id filter
   │       └─ Result: combined WHERE clauses
   │
   ├─► Add JOINs
   │   └─► apply_filters('wpapp_datatable_{table}_joins')
   │       ├─ wp-customer joins user table
   │       ├─ wp-agency joins agency table
   │       └─ Result: all necessary JOINs
   │
   ├─► Execute Query
   │
   └─► Format Each Row
       └─► apply_filters('wpapp_datatable_{table}_row_data')
           ├─ wp-customer adds action buttons
           ├─ wp-agency modifies display based on permissions
           └─ Result: fully formatted row
```

---

## Component Architecture

### 1. **Core Components (wp-app-core)**

#### A. DataTableModel (Base Class)

**Location**: `src/Models/DataTable/DataTableModel.php`

**Responsibilities**:
- Define base structure
- Provide hook points
- Handle server-side processing logic
- Manage pagination, sorting, searching

**Key Methods**:
```php
- get_datatable_data($request_data)    // Main entry point
- format_row($row)                      // Override in child classes
- get_filter_hook($type)                // Generate hook names
```

#### B. DataTableQueryBuilder

**Location**: `src/Models/DataTable/DataTableQueryBuilder.php`

**Responsibilities**:
- Build SQL queries
- Handle SELECT, WHERE, JOIN, ORDER BY
- Implement search functionality
- Count records for pagination

**Key Methods**:
```php
- set_columns($columns)
- add_where_conditions($where)
- add_joins($joins)
- handle_search($request_data)
- handle_ordering($request_data)
- get_results()
- count_total()
- count_filtered()
```

#### C. DataTableController

**Location**: `src/Controllers/DataTable/DataTableController.php`

**Responsibilities**:
- Handle AJAX requests
- Validate security (nonce, permissions)
- Instantiate models
- Return JSON responses

**Key Methods**:
```php
- handle_ajax_request($model_class)
```

### 2. **Module Components**

#### A. Module-Specific Models

**Example**: `wp-customer/src/Models/Customer/CustomerDataTableModel.php`

**Responsibilities**:
- Extend base DataTableModel
- Define table-specific columns
- Implement custom formatting
- Override methods as needed

#### B. Module Filter Classes

**Example**: `wp-customer/src/Filters/CustomerDataTableFilters.php`

**Responsibilities**:
- Register WordPress filters
- Implement business logic
- Add WHERE conditions
- Modify output

---

## Data Flow Architecture

### Request Flow

```
1. USER ACTION
   │
   └─► Browser: DataTables.js sends AJAX request
       │
       │ POST /wp-admin/admin-ajax.php
       │ {
       │   action: 'customer_datatable',
       │   draw: 1,
       │   start: 0,
       │   length: 10,
       │   search: { value: 'john' }
       │ }
       │
       ▼

2. WP AJAX HANDLER
   │
   └─► WordPress routes to registered action
       │ do_action('wp_ajax_customer_datatable')
       │
       ▼

3. CONTROLLER
   │
   ├─► Check nonce (security)
   ├─► Check permissions
   ├─► Validate request
   │
   └─► Instantiate Model
       │
       ▼

4. MODEL (Core Logic)
   │
   ├─► Get base columns
   │   └─► apply_filters('wpapp_datatable_customers_columns', $columns)
   │       Result: [id, name, email, phone, custom_col_1, custom_col_2]
   │
   ├─► Build WHERE conditions
   │   └─► apply_filters('wpapp_datatable_customers_where', $where)
   │       Result: ["status='active'", "agency_id=5"]
   │
   ├─► Build JOINs
   │   └─► apply_filters('wpapp_datatable_customers_joins', $joins)
   │       Result: ["LEFT JOIN wp_users...", "LEFT JOIN wp_agencies..."]
   │
   ├─► QueryBuilder executes query with all modifications
   │
   └─► Format each row
       └─► apply_filters('wpapp_datatable_customers_row_data', $row)
           Result: Fully formatted row with actions, styling, etc.
       │
       ▼

5. RESPONSE
   │
   └─► JSON Response to DataTables
       {
         "draw": 1,
         "recordsTotal": 1000,
         "recordsFiltered": 50,
         "data": [
           [1, "John Doe", "john@example.com", "123-456", "<actions>"],
           [2, "Jane Smith", "jane@example.com", "789-012", "<actions>"]
         ]
       }
       │
       ▼

6. CLIENT UPDATE
   │
   └─► DataTables.js renders the table with new data
```

### Filter Hook Execution Order

```
Priority System (WordPress Standard):

add_filter('hook_name', 'callback', PRIORITY, ARGS);

Lower number = Earlier execution

Example:

1. Core might set defaults (priority 5)
   add_filter('wpapp_datatable_customers_where', 'core_defaults', 5);

2. wp-customer adds logic (priority 10 - default)
   add_filter('wpapp_datatable_customers_where', 'customer_filters', 10);

3. wp-agency adds logic (priority 10)
   add_filter('wpapp_datatable_customers_where', 'agency_filters', 10);

4. Admin override (priority 20)
   add_filter('wpapp_datatable_customers_where', 'admin_override', 20);

Result: All filters execute in order, each receiving output of previous
```

---

## Database Query Architecture

### Query Building Process

```sql
-- Step 1: Base Query (from Model)
SELECT id, name, email, phone
FROM wp_customers
WHERE 1=1

-- Step 2: Apply module filters (WHERE conditions)
-- wp-customer adds:
AND status = 'active'
-- wp-agency adds:
AND agency_id = 5

-- Step 3: Apply JOINs (if needed)
-- wp-customer adds:
LEFT JOIN wp_users ON wp_customers.user_id = wp_users.ID

-- Step 4: Apply Search (if user searched)
AND (
  name LIKE '%john%' OR
  email LIKE '%john%'
)

-- Step 5: Apply Ordering
ORDER BY name ASC

-- Step 6: Apply Pagination
LIMIT 0, 10

-- Final Query:
SELECT id, name, email, phone
FROM wp_customers
LEFT JOIN wp_users ON wp_customers.user_id = wp_users.ID
WHERE 1=1
  AND status = 'active'
  AND agency_id = 5
  AND (name LIKE '%john%' OR email LIKE '%john%')
ORDER BY name ASC
LIMIT 0, 10
```

---

## Security Architecture

### Multi-Layer Security

```
1. WordPress AJAX Nonce
   └─► Validates request is from legitimate source
       check_ajax_referer('wpapp_datatable_nonce', 'nonce');

2. Permission Checks
   └─► Validates user has required capabilities
       if (!current_user_can('view_customers')) {
           wp_send_json_error('Permission denied');
       }

3. Filter-Based Permissions
   └─► Modules can add permission checks via filters
       add_filter('wpapp_datatable_can_access', function($can_access, $model) {
           return current_user_can('manage_customers');
       }, 10, 2);

4. Data Sanitization
   └─► All user input sanitized before use in queries
       $status = esc_sql($_GET['status']);

5. SQL Injection Prevention
   └─► Use $wpdb->prepare() for dynamic values
       $wpdb->prepare("WHERE id = %d", $id);
```

---

## Scalability Architecture

### Performance Considerations

#### 1. **Server-Side Processing**
- Pagination at database level
- Only fetch required rows
- Efficient for large datasets (10k+ rows)

#### 2. **Query Optimization**
```php
// Index columns used in WHERE, ORDER BY
ALTER TABLE wp_customers ADD INDEX idx_status (status);
ALTER TABLE wp_customers ADD INDEX idx_agency (agency_id);

// Use EXPLAIN to analyze queries
$wpdb->query("EXPLAIN " . $query);
```

#### 3. **Caching Strategy**
```php
// Cache total count (doesn't change often)
$total = wp_cache_get('customers_total_count');
if (false === $total) {
    $total = $wpdb->get_var("SELECT COUNT(*) FROM wp_customers");
    wp_cache_set('customers_total_count', $total, '', 3600);
}
```

#### 4. **Lazy Loading**
- Load only visible columns initially
- Fetch additional data on demand (e.g., on row expand)

---

## Extension Points

### Available Hook Types

1. **Column Hooks**: Modify or add columns
   ```php
   wpapp_datatable_{table}_columns
   ```

2. **WHERE Hooks**: Add conditions
   ```php
   wpapp_datatable_{table}_where
   ```

3. **JOIN Hooks**: Add table joins
   ```php
   wpapp_datatable_{table}_joins
   ```

4. **Row Data Hooks**: Modify output
   ```php
   wpapp_datatable_{table}_row_data
   ```

5. **Permission Hooks**: Control access
   ```php
   wpapp_datatable_can_access
   ```

6. **Query Hooks**: Modify entire query
   ```php
   wpapp_datatable_{table}_query
   ```

---

## Module Independence

### How Modules Stay Independent

```
wp-customer and wp-agency don't know about each other!

┌─────────────┐         ┌─────────────┐
│ WP-CUSTOMER │         │ WP-AGENCY   │
│             │         │             │
│ add_filter( │         │ add_filter( │
│  'customers │         │  'customers │
│   _where')  │         │   _where')  │
└──────┬──────┘         └──────┬──────┘
       │                       │
       └───────┬───────────────┘
               │
               ▼
       ┌───────────────┐
       │  WP-APP-CORE  │
       │  Collects all │
       │  filters and  │
       │  applies them │
       └───────────────┘

Both modules add their filters independently.
Core combines them without either knowing about the other.
```

### Benefits:
- ✅ Can activate/deactivate modules independently
- ✅ No coupling between modules
- ✅ Easy to add new modules
- ✅ Easy to remove modules

---

## Error Handling Architecture

### Error Flow

```php
try {
    // 1. Validate request
    if (!$this->validate_request()) {
        throw new Exception('Invalid request');
    }

    // 2. Check permissions
    if (!$this->check_permissions()) {
        throw new Exception('Permission denied');
    }

    // 3. Execute query
    $results = $model->get_datatable_data($_POST);

    // 4. Return success
    wp_send_json_success($results);

} catch (Exception $e) {
    // Log error
    error_log('DataTable Error: ' . $e->getMessage());

    // Return error to client
    wp_send_json_error([
        'message' => $e->getMessage()
    ]);
}
```

---

## Summary

The architecture provides:

✅ **Modularity**: Core + independent modules
✅ **Extensibility**: Rich hook system
✅ **Maintainability**: Clear separation of concerns
✅ **Performance**: Optimized query building
✅ **Security**: Multi-layer validation
✅ **Scalability**: Designed for large datasets
✅ **Flexibility**: Easy to customize

---

**Next**: [Core Implementation Guide](core/IMPLEMENTATION.md)
