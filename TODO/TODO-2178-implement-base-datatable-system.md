# TODO-2178: Implement Base DataTable System (Perfex CRM Pattern)

**Status**: 🔵 Ready to Start
**Priority**: High
**Assignee**: TBD
**Created**: 2025-10-23
**Related Docs**: [DataTable Documentation](../docs/README.md)

---

## 📋 Overview

Implementasi base DataTable system di wp-app-core yang terinspirasi dari Perfex CRM pattern. System ini akan menyediakan foundation untuk semua DataTables di aplikasi dengan extensibility via WordPress hooks.

**Goal**: Create reusable, modular DataTable system yang dapat di-extend oleh plugin modules (wp-customer, wp-agency, dll).

---

## 🎯 Acceptance Criteria

- [ ] Base classes (DataTableModel, QueryBuilder, Controller) implemented
- [ ] Filter hooks system working
- [ ] AJAX handler dengan security checks
- [ ] Example implementation (test DataTable)
- [ ] Unit tests untuk core classes
- [ ] Integration dengan existing MVC structure
- [ ] Documentation updated (jika ada perubahan)

---

## 📦 Tasks Breakdown

### Phase 1: Core Classes Implementation

#### Task 1.1: Create DataTableModel Base Class
**File**: `src/Models/DataTable/DataTableModel.php`

**Checklist**:
- [ ] Create directory `src/Models/DataTable/`
- [ ] Create `DataTableModel.php` dengan properties:
  - `protected $table`
  - `protected $columns`
  - `protected $searchable_columns`
  - `protected $index_column`
  - `protected $base_where`
  - `protected $base_joins`
- [ ] Implement `get_datatable_data($request_data)` method
- [ ] Implement `format_row($row)` method (abstract)
- [ ] Implement `get_columns()` method
- [ ] Implement `get_filter_hook($type)` method
- [ ] Implement `get_table()` method
- [ ] Add filter hooks di semua key points:
  - `wpapp_datatable_{table}_columns`
  - `wpapp_datatable_{table}_where`
  - `wpapp_datatable_{table}_joins`
  - `wpapp_datatable_{table}_query_builder`
  - `wpapp_datatable_{table}_row_data`
  - `wpapp_datatable_{table}_response`
- [ ] Add PHPDoc comments
- [ ] Add inline documentation

**Reference**: [Core Implementation Guide](../docs/datatable/core/IMPLEMENTATION.md#1-datatablemodel-base-class)

---

#### Task 1.2: Create DataTableQueryBuilder
**File**: `src/Models/DataTable/DataTableQueryBuilder.php`

**Checklist**:
- [ ] Create `DataTableQueryBuilder.php` class
- [ ] Add private properties:
  - `$table`, `$columns`, `$searchable_columns`
  - `$where_conditions`, `$joins`
  - `$search_value`, `$order_column`, `$order_dir`
  - `$limit_start`, `$limit_length`
- [ ] Implement setter methods (fluent interface):
  - `set_columns()`
  - `set_searchable_columns()`
  - `set_index_column()`
  - `set_where_conditions()`
  - `set_joins()`
  - `set_search_value()`
  - `set_ordering()`
  - `set_pagination()`
- [ ] Implement query building methods:
  - `build_select()`
  - `build_from()`
  - `build_where()`
  - `build_order()`
  - `build_limit()`
  - `build_query()`
- [ ] Implement execution methods:
  - `get_results()`
  - `count_total()`
  - `count_filtered()`
- [ ] Add SQL injection protection (use `$wpdb->prepare()` dan `$wpdb->esc_like()`)
- [ ] Add PHPDoc comments

**Reference**: [Core Implementation Guide](../docs/datatable/core/IMPLEMENTATION.md#2-datatablequerybuilder)

---

#### Task 1.3: Create DataTableController
**File**: `src/Controllers/DataTable/DataTableController.php`

**Checklist**:
- [ ] Create directory `src/Controllers/DataTable/`
- [ ] Create `DataTableController.php` class
- [ ] Implement `handle_ajax_request($model_class)` method dengan security checks:
  - Check `wp_doing_ajax()`
  - Verify nonce: `check_ajax_referer('wpapp_datatable_nonce', 'nonce')`
  - Check user logged in: `is_user_logged_in()`
  - Check permissions via filter: `wpapp_datatable_can_access`
  - Validate model class exists
  - Try-catch error handling
- [ ] Implement static `register_ajax_action($action, $model_class)` helper
- [ ] Add filter hooks:
  - `wpapp_datatable_can_access` (permission check)
  - `wpapp_datatable_output` (final output modification)
- [ ] Add proper error responses (wp_send_json_error)
- [ ] Add logging untuk errors (error_log)
- [ ] Add PHPDoc comments

**Reference**: [Core Implementation Guide](../docs/datatable/core/IMPLEMENTATION.md#3-datatablecontroller)

---

### Phase 2: Integration & Setup

#### Task 2.1: Autoloader Setup
**File**: `src/autoloader.php` atau `wp-app-core.php`

**Checklist**:
- [ ] Ensure PSR-4 autoloading untuk namespace `WPAppCore\Models\DataTable\`
- [ ] Ensure PSR-4 autoloading untuk namespace `WPAppCore\Controllers\DataTable\`
- [ ] Test autoloading works

**Code**:
```php
spl_autoload_register(function ($class) {
    $prefix = 'WPAppCore\\';
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
```

---

#### Task 2.2: Register DataTable Nonce
**File**: `src/Controllers/DataTable/DataTableController.php` atau init hook

**Checklist**:
- [ ] Register nonce di `admin_enqueue_scripts` action
- [ ] Localize script dengan nonce dan ajax_url
- [ ] Make available globally di admin

**Code**:
```php
add_action('admin_enqueue_scripts', function() {
    wp_localize_script('jquery', 'wpapp_datatable', [
        'nonce' => wp_create_nonce('wpapp_datatable_nonce'),
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
});
```

---

### Phase 3: Test Implementation

#### Task 3.1: Create Test DataTable
**Purpose**: Test base system dengan real implementation

**Files**:
- `src/Models/Platform/PlatformStaffDataTableModel.php`
- Test view: `src/Views/platform/staff-list-test.php`

**Checklist**:
- [ ] Create test model extending `DataTableModel`
- [ ] Use table: `wp_app_platform_staff` (existing table)
- [ ] Define columns: `id, user_id, full_name, email, role, status, created_at`
- [ ] Implement `format_row()` method
- [ ] Register AJAX action: `platform_staff_datatable_test`
- [ ] Create test view dengan DataTables.js initialization
- [ ] Test search functionality
- [ ] Test sorting functionality
- [ ] Test pagination
- [ ] Test dengan data kosong
- [ ] Test dengan banyak data (100+ rows)

**Reference**: [Core Implementation - Usage](../docs/datatable/core/IMPLEMENTATION.md#usage-in-core)

---

#### Task 3.2: Test Filter Hooks
**Purpose**: Verify hook system works

**Checklist**:
- [ ] Create test filter class: `src/Tests/DataTable/TestFilters.php`
- [ ] Test `wpapp_datatable_{table}_columns` hook
- [ ] Test `wpapp_datatable_{table}_where` hook
- [ ] Test `wpapp_datatable_{table}_joins` hook
- [ ] Test `wpapp_datatable_{table}_row_data` hook
- [ ] Test `wpapp_datatable_can_access` hook
- [ ] Verify filters execute in correct order (priority)
- [ ] Verify multiple filters on same hook work together
- [ ] Test filter with different priorities

**Example Test**:
```php
// Add test filter
add_filter('wpapp_datatable_app_platform_staff_where', function($where) {
    error_log('WHERE filter executed: ' . print_r($where, true));
    $where[] = "status = 'active'";
    return $where;
}, 10, 3);

// Check if applied
// Load DataTable and verify only active staff shown
```

---

### Phase 4: Security & Validation

#### Task 4.1: Security Audit

**Checklist**:
- [ ] Verify all user inputs sanitized (esc_sql, intval, sanitize_text_field)
- [ ] Verify all outputs escaped (esc_html, esc_url, esc_attr)
- [ ] Verify nonce checks in place
- [ ] Verify permission checks work
- [ ] Verify SQL injection protection (`$wpdb->prepare()`)
- [ ] Test with non-admin user (should be denied)
- [ ] Test with invalid nonce (should be denied)
- [ ] Test with SQL injection attempts
- [ ] Test XSS attempts in output

**Reference**: [Best Practices - Security](../docs/datatable/BEST-PRACTICES.md#security)

---

#### Task 4.2: Error Handling

**Checklist**:
- [ ] Test invalid model class
- [ ] Test database errors
- [ ] Test invalid request data
- [ ] Test expired nonce
- [ ] Test permission denied
- [ ] Verify user-friendly error messages
- [ ] Verify errors logged (when WP_DEBUG)
- [ ] Test AJAX error handling di frontend

---

### Phase 5: Performance Testing

#### Task 5.1: Query Optimization

**Checklist**:
- [ ] Run EXPLAIN on generated queries
- [ ] Check for N+1 query issues
- [ ] Verify indexes on searchable columns
- [ ] Test with 1000+ records
- [ ] Test with 10000+ records
- [ ] Measure query execution time
- [ ] Optimize slow queries

**Tools**:
```php
// Enable query debugging
add_filter('wpapp_datatable_app_platform_staff_query_builder', function($builder) {
    add_action('shutdown', function() {
        global $wpdb;
        error_log('Last Query: ' . $wpdb->last_query);
        error_log('Query Time: ' . $wpdb->last_query_time);
    });
    return $builder;
}, 999);
```

**Reference**: [Best Practices - Performance](../docs/datatable/BEST-PRACTICES.md#performance)

---

#### Task 5.2: Pagination Testing

**Checklist**:
- [ ] Test pagination dengan berbagai page sizes (10, 25, 50, 100)
- [ ] Test navigation antar pages
- [ ] Test jump to last page
- [ ] Test record count accuracy
- [ ] Test dengan filter applied

---

### Phase 6: Documentation & Cleanup

#### Task 6.1: Code Documentation

**Checklist**:
- [ ] Add PHPDoc untuk semua public methods
- [ ] Add inline comments untuk complex logic
- [ ] Add usage examples di method docs
- [ ] Update class-level documentation

---

#### Task 6.2: Update Documentation

**Checklist**:
- [ ] Update docs jika ada perubahan dari original design
- [ ] Add "Implemented" badge di README
- [ ] Update version history
- [ ] Add migration notes (jika ada breaking changes)

---

#### Task 6.3: Code Review Checklist

**Checklist**:
- [ ] Follows WordPress Coding Standards
- [ ] Follows project naming conventions
- [ ] No hardcoded values
- [ ] Proper error handling
- [ ] Security best practices followed
- [ ] Performance optimized
- [ ] Code is DRY (no duplication)
- [ ] Proper separation of concerns
- [ ] Uses existing utilities when possible

---

### Phase 7: Integration Testing

#### Task 7.1: Integration with Existing System

**Checklist**:
- [ ] Test dengan existing MVC structure
- [ ] Test dengan existing permission system
- [ ] Test dengan existing user roles
- [ ] Test compatibility dengan existing code
- [ ] No conflicts dengan existing DataTables (jika ada)

---

#### Task 7.2: Cross-Browser Testing

**Checklist**:
- [ ] Test di Chrome
- [ ] Test di Firefox
- [ ] Test di Safari
- [ ] Test di Edge
- [ ] Test responsive mode (mobile)

---

## 🔧 Technical Notes

### Database Tables Used
- Test implementation will use: `wp_app_platform_staff`
- Future implementations can use any table

### Dependencies
- WordPress 5.0+
- jQuery (for DataTables.js)
- DataTables.js library (enqueue when needed)

### Namespace Structure
```
WPAppCore\
├── Models\
│   └── DataTable\
│       ├── DataTableModel
│       └── DataTableQueryBuilder
└── Controllers\
    └── DataTable\
        └── DataTableController
```

### Hook Naming Convention
```
wpapp_datatable_{table}_{type}

Examples:
- wpapp_datatable_app_platform_staff_where
- wpapp_datatable_app_platform_staff_columns
- wpapp_datatable_app_platform_staff_row_data
```

---

## 🧪 Testing Strategy

### Unit Tests
- Test DataTableQueryBuilder query building
- Test filter hook registration
- Test permission checks

### Integration Tests
- Test complete AJAX request flow
- Test with real database
- Test filter hooks working together

### Manual Tests
- Test UI functionality
- Test different user roles
- Test with various data scenarios

---

## 📝 Definition of Done

- [ ] All Phase 1-7 tasks completed
- [ ] All tests passing
- [ ] Security audit passed
- [ ] Performance benchmarks met (< 1s for 10k records)
- [ ] Code review completed
- [ ] Documentation updated
- [ ] Zero console errors
- [ ] Works in all major browsers
- [ ] Test implementation verified working
- [ ] Ready for module plugins to extend

---

## 🚀 Next Steps After Completion

1. **TODO-2179**: Implement Customer DataTable in wp-customer using base system
2. **TODO-2180**: Implement Agency DataTable in wp-agency using base system
3. **TODO-2181**: Test multi-module integration (wp-agency extends wp-customer DataTable)

---

## 📚 References

- [DataTable Documentation](../docs/README.md)
- [Architecture Guide](../docs/datatable/ARCHITECTURE.md)
- [Core Implementation Guide](../docs/datatable/core/IMPLEMENTATION.md)
- [API Reference](../docs/datatable/api/REFERENCE.md)
- [Best Practices](../docs/datatable/BEST-PRACTICES.md)

---

## 💡 Tips

1. **Start with Phase 1**: Build core classes first sebelum testing
2. **Test incrementally**: Test setiap class setelah dibuat
3. **Follow docs**: Reference documentation untuk implementation details
4. **Use existing code**: Lihat existing Models/Controllers untuk consistency
5. **Security first**: Always sanitize input, escape output
6. **Log everything**: Use error_log() untuk debugging during development

---

## ⚠️ Potential Issues & Solutions

### Issue: Namespace conflicts
**Solution**: Ensure proper PSR-4 autoloading, use unique namespaces

### Issue: Hook not firing
**Solution**: Check hook name matches pattern, verify registration order

### Issue: SQL errors
**Solution**: Use $wpdb->prepare(), check table prefix, verify column names

### Issue: Permission denied
**Solution**: Check capability checks, verify nonce, test with admin user first

### Issue: Slow queries
**Solution**: Add indexes, use EXPLAIN, optimize JOINs, limit searchable columns

---

**Start Date**: TBD
**Target Completion**: TBD (Estimate: 2-3 days development + 1 day testing)
**Actual Completion**: TBD

---

*Created by: arisciwek*
*Last Updated: 2025-10-23*
