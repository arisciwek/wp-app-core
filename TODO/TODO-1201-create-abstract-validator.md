# TODO-1201: Create AbstractValidator

**Priority:** High
**Status:** Implementation Complete (Phase 1)
**Created:** 2025-01-08
**Completed:** 2025-01-08
**Related:** TODO-1198 (AbstractCrudController), TODO-1200 (AbstractCrudModel)

---

## Problem Statement

**Massive code duplication** di Validator classes across plugins:

**Platform Staff:**
- PlatformStaffValidator.php

**Agency:**
- AgencyValidator.php
- DivisionValidator.php
- AgencyEmployeeValidator.php

**Customer:**
- CustomerValidator.php
- BranchValidator.php
- CustomerEmployeeValidator.php

**Total:** 7 validators with **60-70% duplication**

**Example Duplication:**
- `validatePermission()` method: ~100% sama across validators
- `validateBasicPermission()` method: ~100% sama
- `clearCache()` method: ~100% sama
- `canView()`, `canUpdate()`, `canDelete()`: ~95% sama
- `getUserRelation()` caching logic: ~90% sama

---

## Common Patterns Identified

### 1. Properties (100% Duplication)

**Pattern:**
```php
class CustomerValidator {
    private CustomerModel $model;
    private array $relationCache = [];

    private array $action_capabilities = [
        'create' => 'add_customer',
        'update' => ['edit_all_customers', 'edit_own_customer'],
        'view' => ['view_customer_detail', 'view_own_customer'],
        'delete' => 'delete_customer',
        'list' => 'view_customer_list'
    ];
}
```

**Same in:** AgencyValidator, StaffValidator, BranchValidator, etc.

---

### 2. validatePermission() Method (100% Duplication)

**Pattern:**
```php
public function validatePermission(string $action, ?int $id = null): array {
    $errors = [];

    if (!$id) {
        // Untuk action yang tidak memerlukan ID (misal: create)
        return $this->validateBasicPermission($action);
    }

    // Dapatkan relasi user dengan entity
    $relation = $this->getUserRelation($id);

    // Validasi berdasarkan relasi dan action
    switch ($action) {
        case 'view':
            if (!$this->canView($relation)) {
                $errors['permission'] = __('No access to view', $this->textDomain);
            }
            break;

        case 'update':
            if (!$this->canUpdate($relation)) {
                $errors['permission'] = __('No access to update', $this->textDomain);
            }
            break;

        case 'delete':
            if (!$this->canDelete($relation)) {
                $errors['permission'] = __('No access to delete', $this->textDomain);
            }
            break;

        default:
            throw new \Exception('Invalid action specified');
    }

    return $errors;
}
```

**Duplication:** 100% (only entity name & text domain differ)

---

### 3. validateBasicPermission() Method (100% Duplication)

**Pattern:**
```php
private function validateBasicPermission(string $action): array {
    $errors = [];
    $required_cap = $this->action_capabilities[$action] ?? null;

    if (!$required_cap) {
        throw new \Exception('Invalid action specified');
    }

    if (!current_user_can($required_cap)) {
        $errors['permission'] = __('No permission for this operation', $this->textDomain);
    }

    return $errors;
}
```

**Duplication:** 100%

---

### 4. clearCache() Method (100% Duplication)

**Pattern:**
```php
public function clearCache(?int $id = null): void {
    if ($id) {
        unset($this->relationCache[$id]);
    } else {
        $this->relationCache = [];
    }
}
```

**Duplication:** 100%

---

### 5. canView() / canUpdate() / canDelete() Methods (95% Duplication)

**Pattern:**
```php
public function canView(array $relation): bool {
    // Standard role checks
    if ($relation['is_admin']) return true;
    if ($relation['is_owner'] && current_user_can('view_own_entity')) return true;
    if ($relation['is_employee'] && current_user_can('view_own_entity')) return true;

    // Platform role check (wp-app-core integration)
    if (current_user_can('view_entity_detail')) return true;

    return false;
}

public function canUpdate(array $relation): bool {
    if ($relation['is_admin']) return true;
    if ($relation['is_owner'] && current_user_can('edit_own_entity')) return true;
    if (current_user_can('edit_all_entities')) return true;

    return false;
}

public function canDelete(array $relation): bool {
    if ($relation['is_admin'] && current_user_can('delete_entity')) {
        return true;
    }
    if (current_user_can('delete_entity')) {
        return true;
    }
    return false;
}
```

**Duplication:** ~95% (only capability names differ)

---

### 6. getUserRelation() Caching Logic (90% Duplication)

**Pattern:**
```php
public function getUserRelation(int $entity_id): array {
    $current_user_id = get_current_user_id();

    // Check class memory cache first
    if (isset($this->relationCache[$entity_id])) {
        return $this->relationCache[$entity_id];
    }

    // Get relation from model (with persistent cache)
    $relation = $this->model->getUserRelation($entity_id, $current_user_id);

    // Store in class memory cache
    $this->relationCache[$entity_id] = $relation;

    return $relation;
}
```

**Duplication:** ~90% (only model call differs)

---

## Entity-Specific Differences

**What differs per entity:**

1. **Field Validation** (`validateForm()`)
   - Each entity has different fields to validate
   - Different validation rules (NPWP for customer, KBLI for agency, etc.)
   - Different uniqueness checks

2. **Database Query** (`getUserRelation()`)
   - Different tables (customer_employees, agency_employees, etc.)
   - Different relation types (owner, branch_admin, etc.)

3. **Capability Names**
   - `add_customer` vs `add_agency` vs `add_staff`
   - `edit_all_customers` vs `edit_all_agencies`, etc.

4. **Text Domain**
   - `'wp-customer'` vs `'wp-agency'` vs `'wp-app-core'`

5. **Error Messages**
   - Language-specific messages per plugin

---

## Proposed Solution: AbstractValidator

### Abstract Methods (Entity Configuration)

```php
abstract class AbstractValidator {
    /**
     * Get entity name (singular, lowercase)
     * @return string 'customer', 'agency', 'staff'
     */
    abstract protected function getEntityName(): string;

    /**
     * Get entity display name (for error messages)
     * @return string 'Customer', 'Agency', 'Platform Staff'
     */
    abstract protected function getEntityDisplayName(): string;

    /**
     * Get text domain for translations
     * @return string 'wp-customer', 'wp-agency', 'wp-app-core'
     */
    abstract protected function getTextDomain(): string;

    /**
     * Get model instance
     * @return object Entity model with getUserRelation() method
     */
    abstract protected function getModel();

    /**
     * Get capability for create action
     * @return string 'add_customer', 'add_agency', etc.
     */
    abstract protected function getCreateCapability(): string;

    /**
     * Get capabilities for view action
     * @return array ['view_customer_detail', 'view_own_customer']
     */
    abstract protected function getViewCapabilities(): array;

    /**
     * Get capabilities for update action
     * @return array ['edit_all_customers', 'edit_own_customer']
     */
    abstract protected function getUpdateCapabilities(): array;

    /**
     * Get capability for delete action
     * @return string 'delete_customer', 'delete_agency', etc.
     */
    abstract protected function getDeleteCapability(): string;

    /**
     * Get capability for list action
     * @return string 'view_customer_list', 'view_agency_list', etc.
     */
    abstract protected function getListCapability(): string;

    /**
     * Validate entity-specific form fields
     *
     * @param array $data Form data to validate
     * @param int|null $id Entity ID (for update validation)
     * @return array Validation errors
     */
    abstract protected function validateFormFields(array $data, ?int $id = null): array;

    /**
     * Check if user can view based on relation
     * Child class can override for custom logic
     *
     * @param array $relation User relation data
     * @return bool
     */
    abstract protected function checkViewPermission(array $relation): bool;

    /**
     * Check if user can update based on relation
     * Child class can override for custom logic
     *
     * @param array $relation User relation data
     * @return bool
     */
    abstract protected function checkUpdatePermission(array $relation): bool;

    /**
     * Check if user can delete based on relation
     * Child class can override for custom logic
     *
     * @param array $relation User relation data
     * @return bool
     */
    abstract protected function checkDeletePermission(array $relation): bool;
}
```

---

### Concrete Methods (Inherited FREE)

```php
abstract class AbstractValidator {
    protected array $relationCache = [];

    /**
     * Validate form input
     * Calls child's validateFormFields()
     */
    public function validateForm(array $data, ?int $id = null): array {
        return $this->validateFormFields($data, $id);
    }

    /**
     * Validate permission for action
     * 100% shared implementation
     */
    public function validatePermission(string $action, ?int $id = null): array {
        // Implementation from pattern above
    }

    /**
     * Validate basic permission (no ID required)
     * 100% shared implementation
     */
    protected function validateBasicPermission(string $action): array {
        // Implementation from pattern above
    }

    /**
     * Get user relation with entity (with caching)
     * 90% shared implementation
     */
    public function getUserRelation(int $entity_id): array {
        // Implementation from pattern above
    }

    /**
     * Check if user can view
     * Calls child's checkViewPermission()
     */
    public function canView(array $relation): bool {
        return $this->checkViewPermission($relation);
    }

    /**
     * Check if user can update
     * Calls child's checkUpdatePermission()
     */
    public function canUpdate(array $relation): bool {
        return $this->checkUpdatePermission($relation);
    }

    /**
     * Check if user can delete
     * Calls child's checkDeletePermission()
     */
    public function canDelete(array $relation): bool {
        return $this->checkDeletePermission($relation);
    }

    /**
     * Clear relation cache
     * 100% shared implementation
     */
    public function clearCache(?int $id = null): void {
        // Implementation from pattern above
    }

    /**
     * Validate access for given entity
     * 100% shared implementation
     */
    public function validateAccess(int $entity_id): array {
        // Implementation from pattern above
    }

    /**
     * Build action capabilities array
     * Auto-generated from abstract methods
     */
    protected function getActionCapabilities(): array {
        return [
            'create' => $this->getCreateCapability(),
            'update' => $this->getUpdateCapabilities(),
            'view' => $this->getViewCapabilities(),
            'delete' => $this->getDeleteCapability(),
            'list' => $this->getListCapability()
        ];
    }
}
```

---

## Usage Example: CustomerValidator

**Before (463 lines with duplication):**
```php
class CustomerValidator {
    private CustomerModel $model;
    private array $relationCache = [];
    private array $action_capabilities = [...];

    public function __construct() {
        $this->model = new CustomerModel();
    }

    public function validateForm(array $data, ?int $id = null): array {
        // 80 lines of field validation
    }

    public function validatePermission(string $action, ?int $id = null): array {
        // 40 lines - 100% duplicated
    }

    public function getUserRelation(int $customer_id): array {
        // 20 lines - 90% duplicated
    }

    public function canView(array $relation): bool {
        // 15 lines - 95% duplicated
    }

    public function canUpdate(array $relation): bool {
        // 15 lines - 95% duplicated
    }

    public function canDelete(array $relation): bool {
        // 15 lines - 95% duplicated
    }

    private function validateBasicPermission(string $action): array {
        // 15 lines - 100% duplicated
    }

    public function clearCache(?int $id = null): void {
        // 7 lines - 100% duplicated
    }

    // ... more methods
}
```

**After (180 lines, 61% reduction):**
```php
use WPAppCore\Validators\Abstract\AbstractValidator;

class CustomerValidator extends AbstractValidator {
    private CustomerModel $model;

    public function __construct() {
        $this->model = new CustomerModel();
    }

    // ========================================
    // ABSTRACT METHODS (13 methods)
    // ========================================

    protected function getEntityName(): string {
        return 'customer';
    }

    protected function getEntityDisplayName(): string {
        return 'Customer';
    }

    protected function getTextDomain(): string {
        return 'wp-customer';
    }

    protected function getModel() {
        return $this->model;
    }

    protected function getCreateCapability(): string {
        return 'add_customer';
    }

    protected function getViewCapabilities(): array {
        return ['view_customer_detail', 'view_own_customer'];
    }

    protected function getUpdateCapabilities(): array {
        return ['edit_all_customers', 'edit_own_customer'];
    }

    protected function getDeleteCapability(): string {
        return 'delete_customer';
    }

    protected function getListCapability(): string {
        return 'view_customer_list';
    }

    protected function validateFormFields(array $data, ?int $id = null): array {
        $errors = [];

        // Name validation
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            $errors['name'] = __('Nama customer wajib diisi.', 'wp-customer');
        }
        // ... more field validations (80 lines)

        return $errors;
    }

    protected function checkViewPermission(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_customer_admin'] && current_user_can('view_own_customer')) return true;
        if ($relation['is_customer_branch_admin'] && current_user_can('view_own_customer')) return true;
        if ($relation['is_customer_employee'] && current_user_can('view_own_customer')) return true;
        if (current_user_can('view_customer_detail')) return true;
        return false;
    }

    protected function checkUpdatePermission(array $relation): bool {
        if ($relation['is_admin']) return true;
        if ($relation['is_customer_admin'] && current_user_can('edit_own_customer')) return true;
        if (current_user_can('edit_all_customers')) return true;
        return false;
    }

    protected function checkDeletePermission(array $relation): bool {
        if ($relation['is_admin'] && current_user_can('delete_customer')) {
            return true;
        }
        if (current_user_can('delete_customer')) {
            return true;
        }
        return false;
    }

    // ✅ validateForm() - inherited FREE!
    // ✅ validatePermission() - inherited FREE!
    // ✅ getUserRelation() - inherited FREE!
    // ✅ canView() - inherited FREE!
    // ✅ canUpdate() - inherited FREE!
    // ✅ canDelete() - inherited FREE!
    // ✅ validateBasicPermission() - inherited FREE!
    // ✅ clearCache() - inherited FREE!
    // ✅ validateAccess() - inherited FREE!

    // Custom methods (entity-specific)
    public function validateDelete(int $id): array {
        // Custom delete validation logic
    }

    public function formatNpwp(string $npwp): string {
        // Customer-specific NPWP formatting
    }

    public function validateNpwpFormat(string $npwp): bool {
        // Customer-specific NPWP validation
    }
}
```

---

## Expected Benefits

### Code Reduction

**Per Validator:**
- CustomerValidator: 463 lines → ~180 lines (**61% reduction**)
- AgencyValidator: 420 lines → ~160 lines (**62% reduction**)
- StaffValidator: 380 lines → ~140 lines (**63% reduction**)
- BranchValidator: 350 lines → ~130 lines (**63% reduction**)
- EmployeeValidator: 340 lines → ~125 lines (**63% reduction**)

**Total Savings:** ~1,400 lines across 7 validators

---

### Consistency

✅ **Standardized permission checks** across all entities
✅ **Consistent error messages** (translatable)
✅ **Uniform cache management**
✅ **Type-safe method signatures**
✅ **Single source of truth** for permission logic

---

### Maintainability

✅ **Fix once, benefit everywhere** - bug fixes in base class apply to all
✅ **Easier testing** - test base class thoroughly once
✅ **Clear separation** - permission logic vs field validation
✅ **Extensible** - easy to add new validators

---

## Integration with AbstractCrudController

AbstractValidator is designed to work seamlessly with AbstractCrudController:

**AbstractCrudController needs:**
```php
abstract protected function getValidator();
```

**Validator must provide:**
```php
public function validatePermission(string $action, ?int $id = null): array;
public function validate(array $data, ?int $id = null): array; // alias for validateForm
```

**AbstractValidator provides both:**
```php
✅ validatePermission() - inherited from base
✅ validate() - can be alias or direct call to validateForm()
```

---

## Implementation Plan

### Phase 1: Create AbstractValidator (3-4 hours)

**Files to Create:**
1. `/wp-app-core/src/Validators/Abstract/AbstractValidator.php` (~450 lines)
2. `/wp-app-core/docs/validators/ABSTRACT-VALIDATOR.md` (~2,000 lines)

**Features:**
- 13 abstract methods for entity configuration
- 9 concrete methods (shared implementation)
- Memory caching for relations
- Error message localization
- Comprehensive PHPDoc

---

### Phase 2: Refactor Validators (8-10 hours)

**Priority Order:**

1. **PlatformStaffValidator** (2 hours)
   - Simple entity, good test case
   - Verify all methods work correctly

2. **CustomerValidator** (2 hours)
   - Complex entity with custom methods
   - NPWP/NIB formatting preservation

3. **AgencyValidator** (2 hours)
   - Similar to CustomerValidator
   - Division relations

4. **BranchValidator** (1.5 hours)
   - Nested entity under Customer
   - Branch-specific permissions

5. **CustomerEmployeeValidator** (1.5 hours)
   - Employee relations
   - Department/position validation

6. **AgencyEmployeeValidator** (1.5 hours)
   - Similar to CustomerEmployeeValidator

7. **DivisionValidator** (1.5 hours)
   - Nested entity under Agency

---

### Phase 3: Testing & Documentation (2-3 hours)

1. **Unit Tests**
   - Test AbstractValidator methods
   - Test permission logic
   - Test cache management

2. **Integration Tests**
   - Test with AbstractCrudController
   - Test cross-plugin scenarios

3. **Documentation**
   - Update plugin docs
   - Migration guide
   - Best practices

---

## Success Criteria

✅ **All 7 validators** extend AbstractValidator
✅ **60%+ code reduction** achieved
✅ **All tests passing** (existing + new)
✅ **Zero regression** - all features work as before
✅ **Documentation complete** - guide + examples
✅ **Type-safe** - no mixed types, proper PHPDoc

---

## Risk Mitigation

**Risk:** Breaking existing functionality
**Mitigation:**
- Implement one validator at a time
- Test thoroughly before moving to next
- Keep old code commented for rollback

**Risk:** Different permission patterns across entities
**Mitigation:**
- Abstract methods allow customization
- Default implementations can be overridden
- Template method pattern for flexibility

**Risk:** Integration issues with AbstractCrudController
**Mitigation:**
- Design interface based on controller needs
- Test integration early
- Maintain backward compatibility

---

## Alternative Approaches Considered

### Option 1: Trait-based Approach
**Pros:** Multiple inheritance possible
**Cons:** Less type safety, harder to maintain
**Decision:** ❌ Rejected - abstract class better for this case

### Option 2: Interface-based Approach
**Pros:** More flexible
**Cons:** No shared implementation, still duplication
**Decision:** ❌ Rejected - defeats the purpose

### Option 3: Helper Class Approach
**Pros:** No inheritance needed
**Cons:** Composition overhead, less elegant
**Decision:** ❌ Rejected - abstract class is cleaner

### Option 4: Abstract Class (Selected)
**Pros:**
- Shared implementation
- Type safety
- Clear inheritance
- Consistent with AbstractCrudController pattern
**Cons:** Single inheritance limitation (acceptable)
**Decision:** ✅ **Selected**

---

## Related TODOs

- **TODO-1198:** AbstractCrudController ✅ (needs validator)
- **TODO-1200:** AbstractCrudModel ✅ (works with validator)
- **TODO-1202:** AbstractDataTableModel refactor (uses validator for permissions)

---

## Implementation Priority

**Urgency:** High
**Impact:** High (affects 7 validators across 3 plugins)
**Complexity:** Medium
**Estimated Time:** 13-17 hours total

**Recommended Start:** Immediate (blocks wp-customer refactor)

---

## Status

✅ **Phase 1 Complete** - AbstractValidator implemented

---

## Implementation Summary

**Completed:** 2025-01-08

### Phase 1: AbstractValidator Created ✅

**Files Created:**

1. **AbstractValidator.php** (520 lines)
   - Path: `/wp-app-core/src/Validators/Abstract/AbstractValidator.php`
   - 13 abstract methods for entity configuration
   - 9 concrete methods (shared implementation)
   - Memory caching for user relations
   - Integration with AbstractCrudController
   - Comprehensive PHPDoc documentation

2. **ABSTRACT-VALIDATOR.md** (2,500+ lines)
   - Path: `/wp-app-core/docs/validators/ABSTRACT-VALIDATOR.md`
   - Complete documentation with examples
   - Migration guide (before/after comparison)
   - Real-world examples (CustomerValidator, AgencyValidator)
   - Best practices (DO/DON'T)
   - Troubleshooting guide
   - Performance considerations
   - Integration guide with AbstractCrudController

**Features Implemented:**

✅ **Abstract Methods (13):**
- `getEntityName()`
- `getEntityDisplayName()`
- `getTextDomain()`
- `getModel()`
- `getCreateCapability()`
- `getViewCapabilities()`
- `getUpdateCapabilities()`
- `getDeleteCapability()`
- `getListCapability()`
- `validateFormFields()`
- `checkViewPermission()`
- `checkUpdatePermission()`
- `checkDeletePermission()`

✅ **Concrete Methods (9):**
- `validateForm()` - public wrapper
- `validate()` - AbstractCrudController alias
- `validatePermission()` - complete permission validation
- `validateBasicPermission()` - capability-only checks
- `getUserRelation()` - with memory caching
- `canView()` - calls checkViewPermission()
- `canUpdate()` - calls checkUpdatePermission()
- `canDelete()` - calls checkDeletePermission()
- `clearCache()` - cache management
- `validateAccess()` - comprehensive access check

---

## Next Steps

**Phase 2:** Refactor Validators (8-10 hours)

Migrate existing validators to use AbstractValidator:

1. **PlatformStaffValidator** (2 hours) - Simple test case
2. **CustomerValidator** (2 hours) - Complex with custom methods
3. **AgencyValidator** (2 hours) - Cross-plugin integration
4. **BranchValidator** (1.5 hours) - Nested entity
5. **CustomerEmployeeValidator** (1.5 hours) - Employee relations
6. **AgencyEmployeeValidator** (1.5 hours) - Similar to CustomerEmployee
7. **DivisionValidator** (1.5 hours) - Agency nested entity

**Phase 3:** Testing & Documentation (2-3 hours)

---

**Last Updated:** 2025-01-08
**Created By:** Claude (Sonnet 4.5)
**Next Action:** Phase 2 - Migrate validators to AbstractValidator
