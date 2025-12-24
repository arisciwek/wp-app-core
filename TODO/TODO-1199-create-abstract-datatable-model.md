# TODO-1199: Create AbstractDataTableModel

**Priority:** High
**Status:** In Progress
**Created:** 2025-01-02
**Related:** TODO-1198 (AbstractCrudController, AbstractDashboardController)

---

## Problem Statement

Terdapat **duplikasi kode yang signifikan** di antara DataTable models:

1. **CustomerDataTableModel.php** (293 lines)
2. **AgencyDataTableModel.php** (367 lines)
3. **PlatformStaffDataTableModel.php** (226 lines)

**Total Lines:** 886 lines
**Estimated Duplication:** 60-70% (530-620 lines)

Similar methods across all models:
- `format_status_badge()` - 100% identical
- `generate_action_buttons()` - 95% similar
- `get_total_count()` - 95% similar
- `get_where()` - 80% similar (status filtering)

---

## Code Duplication Analysis

### Part 1: Identical Methods (100% Duplication)

#### 1.1 format_status_badge()

**Location:** All 3 models
**Lines:** ~15 lines per model × 3 = 45 lines
**Duplication:** 100%

**CustomerDataTableModel.php:163-174**
```php
private function format_status_badge(string $status): string {
    $badge_class = $status === 'aktif' ? 'success' : 'error';
    $status_text = $status === 'aktif'
        ? __('Active', 'wp-customer')
        : __('Inactive', 'wp-customer');

    return sprintf(
        '<span class="wpapp-badge wpapp-badge-%s">%s</span>',
        esc_attr($badge_class),
        esc_html($status_text)
    );
}
```

**AgencyDataTableModel.php:219-230**
```php
private function format_status_badge(string $status): string {
    $badge_class = $status === 'active' ? 'success' : 'error';
    $status_text = $status === 'active'
        ? __('Active', 'wp-agency')
        : __('Inactive', 'wp-agency');

    return sprintf(
        '<span class="wpapp-badge wpapp-badge-%s">%s</span>',
        esc_attr($badge_class),
        esc_html($status_text)
    );
}
```

**PlatformStaffDataTableModel.php:128-139**
```php
private function format_status_badge(string $status): string {
    $badge_class = $status === 'aktif' ? 'success' : 'error';
    $status_text = $status === 'aktif'
        ? __('Active', 'wp-app-core')
        : __('Inactive', 'wp-app-core');

    return sprintf(
        '<span class="wpapp-badge wpapp-badge-%s">%s</span>',
        esc_attr($badge_class),
        esc_html($status_text)
    );
}
```

**Issues:**
- Exact same logic across all models
- Only differences: text domain, status value names (aktif vs active)

---

### Part 2: Nearly Identical Methods (95% Duplication)

#### 2.1 generate_action_buttons()

**Location:** All 3 models
**Lines:** ~35 lines per model × 3 = 105 lines
**Duplication:** 95%

**Pattern Across All Models:**
```php
private function generate_action_buttons($row): string {
    $buttons = [];

    // 1. View button (always shown)
    $buttons[] = sprintf(
        '<button type="button" class="button button-small wpapp-panel-trigger"
                data-id="%d" data-entity="{ENTITY}" title="%s">
            <span class="dashicons dashicons-visibility"></span>
        </button>',
        esc_attr($row->id),
        esc_attr__('View Details', '{TEXT_DOMAIN}')
    );

    // 2. Edit button (permission check)
    if (current_user_can('{EDIT_CAP}')) {
        $buttons[] = sprintf(
            '<button type="button" class="button button-small {ENTITY}-edit-btn"
                    data-id="%d" title="%s">
                <span class="dashicons dashicons-edit"></span>
            </button>',
            esc_attr($row->id),
            esc_attr__('Edit', '{TEXT_DOMAIN}')
        );
    }

    // 3. Delete button (permission check)
    if (current_user_can('{DELETE_CAP}')) {
        $buttons[] = sprintf(
            '<button type="button" class="button button-small {ENTITY}-delete-btn"
                    data-id="%d" title="%s">
                <span class="dashicons dashicons-trash"></span>
            </button>',
            esc_attr($row->id),
            esc_attr__('Delete', '{TEXT_DOMAIN}')
        );
    }

    return implode(' ', $buttons);
}
```

**Variables Per Entity:**

| Model | Entity | Text Domain | Edit Cap | Delete Cap |
|-------|--------|-------------|----------|------------|
| Customer | customer | wp-customer | edit_all_customers | delete_customers |
| Agency | agency | wp-agency | edit_agency | delete_agency |
| Platform Staff | platform_staff | wp-app-core | edit_platform_users | delete_platform_users |

**Issues:**
- 95% identical logic
- Only entity name, capabilities, and text domains differ
- Perfect candidate for abstraction

---

#### 2.2 get_total_count()

**Location:** All 3 models
**Lines:** ~50 lines per model × 3 = 150 lines
**Duplication:** 95%

**Pattern Across All Models:**
```php
public function get_total_count(string $status_filter = 'aktif'): int {
    global $wpdb;

    // 1. Prepare minimal request data
    $request_data = [
        'start' => 0,
        'length' => 1,
        'search' => ['value' => ''],
        'order' => [['column' => 0, 'dir' => 'asc']],
        'status_filter' => $status_filter
    ];

    // 2. Temporarily set POST
    $original_post = $_POST;
    $_POST['status_filter'] = $status_filter;

    // 3. Get WHERE conditions
    $where_conditions = $this->get_where();

    // 4. Apply filter hook
    $where_conditions = apply_filters(
        'wpapp_datatable_{table}_where',  // ← Only difference!
        $where_conditions,
        $request_data,
        $this
    );

    // 5. Restore POST
    $_POST = $original_post;

    // 6. Build query
    $where_sql = '';
    if (!empty($where_conditions)) {
        $where_sql = ' WHERE ' . implode(' AND ', $where_conditions);
    }

    // 7. Execute count query
    $count_sql = "SELECT COUNT(DISTINCT {alias}.id) as total
                  FROM {$this->table}
                  {$where_sql}";

    return (int) $wpdb->get_var($count_sql);
}
```

**Variables Per Entity:**

| Model | Filter Hook | Default Status |
|-------|-------------|----------------|
| Customer | wpapp_datatable_customers_where | aktif |
| Agency | wpapp_datatable_app_agencies_where | active |
| Platform Staff | (none used) | aktif |

**Issues:**
- 95% identical implementation
- Only filter hook name and default status differ
- Can be abstracted with entity configuration

---

### Part 3: Similar Methods (80% Duplication)

#### 3.1 get_where()

**Location:** All 3 models
**Lines:** ~45 lines per model × 3 = 135 lines
**Duplication:** 80% (status filtering logic)

**Common Pattern - Status Filtering:**
```php
public function get_where(): array {
    global $wpdb;
    $where = [];

    // Status filter (IDENTICAL across all)
    $status_filter = isset($_POST['status_filter'])
        ? sanitize_text_field($_POST['status_filter'])
        : 'aktif';  // or 'active'

    if ($status_filter !== 'all') {
        $where[] = $wpdb->prepare('{alias}.status = %s', $status_filter);
    }

    // Permission filtering (ENTITY-SPECIFIC - varies per model)
    // ... custom logic here ...

    return $where;
}
```

**Status Filtering:** 100% identical
**Permission Filtering:** Entity-specific

**Issues:**
- Status filtering logic 100% duplicate
- Permission logic varies per entity (cannot abstract)
- Can provide base implementation with overridable permission method

---

### Part 4: Constructor Pattern (70% Duplication)

**Location:** All 3 models
**Lines:** ~25 lines per model × 3 = 75 lines
**Duplication:** 70%

**Pattern:**
```php
public function __construct() {
    parent::__construct();

    global $wpdb;

    // 1. Table setup (entity-specific)
    $this->table = $wpdb->prefix . 'app_{entities} {alias}';
    $this->index_column = '{alias}.id';

    // 2. Searchable columns (entity-specific)
    $this->searchable_columns = [
        '{alias}.field1',
        '{alias}.field2',
        // ...
    ];

    // 3. Base JOINs (entity-specific)
    $this->base_joins = [
        // ... entity-specific joins ...
    ];
}
```

**Issues:**
- Pattern is consistent but values are entity-specific
- Can provide template method with abstract configuration methods

---

### Part 5: format_row() Pattern (50% Duplication)

**Location:** All 3 models
**Lines:** ~15 lines per model × 3 = 45 lines
**Duplication:** 50%

**Common Pattern:**
```php
protected function format_row($row): array {
    return [
        // Panel integration (IDENTICAL)
        'DT_RowId' => '{entity}-' . $row->id,
        'DT_RowData' => [
            'id' => $row->id,
            'entity' => '{entity}'
        ],

        // Entity-specific columns
        'field1' => esc_html($row->field1),
        'field2' => esc_html($row->field2 ?? '-'),
        // ...

        // Actions (calls generate_action_buttons)
        'actions' => $this->generate_action_buttons($row)
    ];
}
```

**Common Elements:**
- DT_RowId pattern: `{entity}-{id}`
- DT_RowData structure
- Actions column

**Issues:**
- Structure is consistent but field mapping is entity-specific
- Can provide utility methods for common patterns

---

## Duplication Summary

| Method | Lines × Models | Duplication % | Potential Savings |
|--------|---------------|---------------|-------------------|
| format_status_badge() | 15 × 3 = 45 | 100% | ~45 lines |
| generate_action_buttons() | 35 × 3 = 105 | 95% | ~95 lines |
| get_total_count() | 50 × 3 = 150 | 95% | ~140 lines |
| get_where() (status part) | 15 × 3 = 45 | 80% | ~30 lines |
| Constructor pattern | 25 × 3 = 75 | 70% | ~50 lines |
| format_row() (structure) | 15 × 3 = 45 | 50% | ~20 lines |
| **TOTAL** | **465 lines** | **82% avg** | **~380 lines** |

**Expected Code Reduction: ~380 lines (82%)**

---

## Proposed Solution: AbstractDataTableModel

### Design Goals

1. ✅ Eliminate duplicate code (format_status_badge, generate_action_buttons, get_total_count)
2. ✅ Provide consistent patterns (DT_RowId, DT_RowData, action buttons)
3. ✅ Maintain flexibility (entity-specific logic via abstract methods)
4. ✅ Type safety (method signatures, return types)
5. ✅ Backward compatibility (extends existing DataTableModel)

---

### Architecture

```
WPAppCore\Models\DataTable\DataTableModel (existing base)
    ↓
WPAppCore\Models\DataTable\AbstractDataTableModel (NEW)
    ↓
├── CustomerDataTableModel
├── AgencyDataTableModel
└── PlatformStaffDataTableModel
```

**AbstractDataTableModel** will:
- Extend existing `DataTableModel`
- Define abstract methods for entity-specific configuration
- Provide concrete implementations of duplicate methods
- Offer utility methods for common patterns

---

### Abstract Methods (Entity Configuration)

Child classes MUST implement these methods:

#### 1. getEntityName(): string

Entity name (singular, lowercase).

**Example:**
```php
protected function getEntityName(): string {
    return 'customer';  // or 'agency', 'platform_staff'
}
```

**Usage:**
- DT_RowId: `{entity}-{id}`
- DT_RowData entity field
- Action button classes
- Filter hook names

---

#### 2. getEntityDisplayName(): string

Entity display name for UI.

**Example:**
```php
protected function getEntityDisplayName(): string {
    return 'Customer';
}
```

**Usage:**
- Button titles
- UI messages

---

#### 3. getTextDomain(): string

Translation text domain.

**Example:**
```php
protected function getTextDomain(): string {
    return 'wp-customer';
}
```

**Usage:**
- __() translations
- Status badge text
- Button labels

---

#### 4. getTableAlias(): string

Database table alias (single letter).

**Example:**
```php
protected function getTableAlias(): string {
    return 'c';  // or 'a', 's'
}
```

**Usage:**
- Column references
- WHERE clauses
- JOIN clauses

---

#### 5. getStatusActiveValue(): string

Active status value for this entity.

**Example:**
```php
protected function getStatusActiveValue(): string {
    return 'aktif';  // or 'active'
}
```

**Usage:**
- Status filtering
- Badge generation
- get_total_count() default

---

#### 6. getEditCapability(): string

WordPress capability for editing.

**Example:**
```php
protected function getEditCapability(): string {
    return 'edit_all_customers';
}
```

**Usage:**
- Edit button permission check

---

#### 7. getDeleteCapability(): string

WordPress capability for deleting.

**Example:**
```php
protected function getDeleteCapability(): string {
    return 'delete_customers';
}
```

**Usage:**
- Delete button permission check

---

#### 8. getFilterHookName(): string

Filter hook name for WHERE conditions.

**Example:**
```php
protected function getFilterHookName(): string {
    return 'wpapp_datatable_customers_where';
}
```

**Usage:**
- get_total_count() filter application
- Cross-plugin integration

---

### Concrete Methods (Provided FREE)

#### 1. format_status_badge(string $status): string

Generates HTML status badge.

**Implementation:**
```php
protected function format_status_badge(string $status): string {
    $active_value = $this->getStatusActiveValue();
    $badge_class = $status === $active_value ? 'success' : 'error';
    $status_text = $status === $active_value
        ? __('Active', $this->getTextDomain())
        : __('Inactive', $this->getTextDomain());

    return sprintf(
        '<span class="wpapp-badge wpapp-badge-%s">%s</span>',
        esc_attr($badge_class),
        esc_html($status_text)
    );
}
```

**Benefits:**
- 100% code reduction in child classes
- Consistent badge rendering
- Configurable via getStatusActiveValue()

---

#### 2. generate_action_buttons($row, array $custom_buttons = []): string

Generates standard action buttons.

**Implementation:**
```php
protected function generate_action_buttons($row, array $custom_buttons = []): string {
    $buttons = [];
    $entity = $this->getEntityName();
    $text_domain = $this->getTextDomain();

    // View button (always shown)
    $buttons[] = sprintf(
        '<button type="button" class="button button-small wpapp-panel-trigger"
                data-id="%d" data-entity="%s" title="%s">
            <span class="dashicons dashicons-visibility"></span>
        </button>',
        esc_attr($row->id),
        esc_attr($entity),
        esc_attr__('View Details', $text_domain)
    );

    // Edit button (if permitted)
    if (current_user_can($this->getEditCapability())) {
        $buttons[] = sprintf(
            '<button type="button" class="button button-small %s-edit-btn"
                    data-id="%d" title="%s">
                <span class="dashicons dashicons-edit"></span>
            </button>',
            esc_attr($entity),
            esc_attr($row->id),
            esc_attr__('Edit', $text_domain)
        );
    }

    // Delete button (if permitted)
    if (current_user_can($this->getDeleteCapability())) {
        $buttons[] = sprintf(
            '<button type="button" class="button button-small %s-delete-btn"
                    data-id="%d" title="%s">
                <span class="dashicons dashicons-trash"></span>
            </button>',
            esc_attr($entity),
            esc_attr($row->id),
            esc_attr__('Delete', $text_domain)
        );
    }

    // Add custom buttons (if provided)
    $buttons = array_merge($buttons, $custom_buttons);

    return implode(' ', $buttons);
}
```

**Benefits:**
- 95% code reduction in child classes
- Consistent button structure
- Extensible via $custom_buttons parameter
- Permission checks built-in

---

#### 3. get_total_count(string $status_filter = null): int

Gets total count with filtering.

**Implementation:**
```php
public function get_total_count(string $status_filter = null): int {
    global $wpdb;

    // Use entity's default status if not provided
    if ($status_filter === null) {
        $status_filter = $this->getStatusActiveValue();
    }

    // Prepare minimal request data
    $request_data = [
        'start' => 0,
        'length' => 1,
        'search' => ['value' => ''],
        'order' => [['column' => 0, 'dir' => 'asc']],
        'status_filter' => $status_filter
    ];

    // Temporarily set POST
    $original_post = $_POST;
    $_POST['status_filter'] = $status_filter;

    // Get WHERE conditions from child class
    $where_conditions = $this->get_where();

    // Apply filter hook (if defined)
    if ($filter_hook = $this->getFilterHookName()) {
        $where_conditions = apply_filters(
            $filter_hook,
            $where_conditions,
            $request_data,
            $this
        );
    }

    // Restore POST
    $_POST = $original_post;

    // Build query
    $where_sql = '';
    if (!empty($where_conditions)) {
        $where_sql = ' WHERE ' . implode(' AND ', $where_conditions);
    }

    $alias = $this->getTableAlias();
    $count_sql = "SELECT COUNT(DISTINCT {$alias}.id) as total
                  FROM {$this->table}
                  {$where_sql}";

    return (int) $wpdb->get_var($count_sql);
}
```

**Benefits:**
- 95% code reduction in child classes
- Consistent counting logic
- Filter hook support
- Uses entity configuration

---

#### 4. getBaseStatusWhere(): array

Gets base WHERE conditions for status filtering.

**Implementation:**
```php
protected function getBaseStatusWhere(): array {
    global $wpdb;
    $where = [];

    $status_filter = isset($_POST['status_filter'])
        ? sanitize_text_field($_POST['status_filter'])
        : $this->getStatusActiveValue();

    if ($status_filter !== 'all') {
        $alias = $this->getTableAlias();
        $where[] = $wpdb->prepare("{$alias}.status = %s", $status_filter);
    }

    return $where;
}
```

**Benefits:**
- Eliminates duplicate status filtering logic
- Child classes can call this in their get_where()
- Consistent filtering across all entities

---

#### 5. formatPanelRowData($row): array

Formats DT_RowId and DT_RowData for panel integration.

**Implementation:**
```php
protected function formatPanelRowData($row): array {
    $entity = $this->getEntityName();

    return [
        'DT_RowId' => $entity . '-' . $row->id,
        'DT_RowData' => [
            'id' => $row->id,
            'entity' => $entity
        ]
    ];
}
```

**Benefits:**
- Consistent panel integration
- DRY principle
- Can be used in format_row()

---

### Usage Example

#### Before (CustomerDataTableModel - 293 lines)

```php
class CustomerDataTableModel extends DataTableModel {

    public function __construct() {
        parent::__construct();
        global $wpdb;
        $this->table = $wpdb->prefix . 'app_customers c';
        $this->index_column = 'c.id';
        $this->searchable_columns = ['c.code', 'c.name', 'c.npwp', 'c.nib', 'b.email'];
        $this->base_joins = [/* ... */];
    }

    protected function get_columns(): array {
        return ['c.id as id', 'c.code as code', /* ... */];
    }

    protected function format_row($row): array {
        return [
            'DT_RowId' => 'customer-' . $row->id,
            'DT_RowData' => [
                'id' => $row->id,
                'entity' => 'customer'
            ],
            'code' => esc_html($row->code),
            'name' => esc_html($row->name),
            // ...
            'actions' => $this->generate_action_buttons($row)
        ];
    }

    public function get_where(): array {
        global $wpdb;
        $where = [];

        // Status filtering (DUPLICATE CODE!)
        $status_filter = isset($_POST['status_filter'])
            ? sanitize_text_field($_POST['status_filter'])
            : 'aktif';

        if ($status_filter !== 'all') {
            $where[] = $wpdb->prepare('c.status = %s', $status_filter);
        }

        // Entity-specific permission filtering
        // ...

        return $where;
    }

    // 45 lines of duplicate code!
    private function format_status_badge(string $status): string {
        $badge_class = $status === 'aktif' ? 'success' : 'error';
        $status_text = $status === 'aktif'
            ? __('Active', 'wp-customer')
            : __('Inactive', 'wp-customer');

        return sprintf(
            '<span class="wpapp-badge wpapp-badge-%s">%s</span>',
            esc_attr($badge_class),
            esc_html($status_text)
        );
    }

    // 105 lines of duplicate code!
    private function generate_action_buttons($row): string {
        $buttons = [];

        $buttons[] = sprintf(/* view button */);
        if (current_user_can('edit_all_customers')) {
            $buttons[] = sprintf(/* edit button */);
        }
        if (current_user_can('delete_customers')) {
            $buttons[] = sprintf(/* delete button */);
        }

        return implode(' ', $buttons);
    }

    protected function get_table_alias(): string {
        return 'c';
    }

    // 150 lines of duplicate code!
    public function get_total_count(string $status_filter = 'aktif'): int {
        global $wpdb;

        $request_data = [/* ... */];
        $original_post = $_POST;
        $_POST['status_filter'] = $status_filter;
        $where_conditions = $this->get_where();
        $where_conditions = apply_filters(
            'wpapp_datatable_customers_where',
            $where_conditions,
            $request_data,
            $this
        );
        $_POST = $original_post;
        $where_sql = /* ... */;
        $count_sql = "SELECT COUNT(DISTINCT c.id) ...";
        return (int) $wpdb->get_var($count_sql);
    }
}
```

---

#### After (CustomerDataTableModel - ~100 lines)

```php
class CustomerDataTableModel extends AbstractDataTableModel {

    // ========================================
    // IMPLEMENT 8 ABSTRACT METHODS (~40 lines)
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

    protected function getTableAlias(): string {
        return 'c';
    }

    protected function getStatusActiveValue(): string {
        return 'aktif';
    }

    protected function getEditCapability(): string {
        return 'edit_all_customers';
    }

    protected function getDeleteCapability(): string {
        return 'delete_customers';
    }

    protected function getFilterHookName(): string {
        return 'wpapp_datatable_customers_where';
    }

    // ========================================
    // ENTITY-SPECIFIC IMPLEMENTATION (~60 lines)
    // ========================================

    public function __construct() {
        parent::__construct();
        global $wpdb;
        $this->table = $wpdb->prefix . 'app_customers c';
        $this->index_column = 'c.id';
        $this->searchable_columns = ['c.code', 'c.name', 'c.npwp', 'c.nib', 'b.email'];
        $this->base_joins = [/* ... */];
    }

    protected function get_columns(): array {
        return ['c.id as id', 'c.code as code', /* ... */];
    }

    protected function format_row($row): array {
        return array_merge(
            // Use helper for panel data
            $this->formatPanelRowData($row),
            [
                'code' => esc_html($row->code),
                'name' => esc_html($row->name),
                // ...
                'actions' => $this->generate_action_buttons($row)  // Inherited!
            ]
        );
    }

    public function get_where(): array {
        // Use helper for status filtering
        $where = $this->getBaseStatusWhere();  // Inherited!

        // Add entity-specific permission filtering
        // ...

        return $where;
    }

    // ✅ format_status_badge() - INHERITED FREE!
    // ✅ generate_action_buttons() - INHERITED FREE!
    // ✅ get_total_count() - INHERITED FREE!
}
```

**Code Reduction:**
- Before: 293 lines
- After: ~100 lines
- **Saved: 193 lines (66%)**

---

## Implementation Plan

### Phase 1: Create AbstractDataTableModel (Estimated: 3-4 hours)

**Tasks:**
1. Create `/wp-app-core/src/Models/DataTable/AbstractDataTableModel.php`
2. Define 8 abstract methods
3. Implement 5 concrete methods
4. Add comprehensive PHPDoc

**Deliverables:**
- AbstractDataTableModel.php (400-500 lines with PHPDoc)
- Unit tests (if applicable)

---

### Phase 2: Create Documentation (Estimated: 2-3 hours)

**Tasks:**
1. Create `/wp-app-core/docs/models/ABSTRACT-DATATABLE-MODEL.md`
2. Quick start guide
3. All abstract methods documented
4. All concrete methods explained
5. Real-world examples
6. Migration guide

**Deliverables:**
- Complete documentation (1,000+ lines)

---

### Phase 3: Refactor PlatformStaffDataTableModel (Estimated: 1-2 hours)

**Tasks:**
1. Extend AbstractDataTableModel
2. Implement 8 abstract methods
3. Remove duplicate code
4. Test all functionality

**Expected Reduction:**
- Before: 226 lines
- After: ~80 lines
- **Saved: ~146 lines**

---

### Phase 4: Refactor CustomerDataTableModel (Estimated: 2-3 hours)

**Tasks:**
1. Extend AbstractDataTableModel
2. Implement 8 abstract methods
3. Remove duplicate code
4. Keep custom JOIN logic
5. Test all functionality

**Expected Reduction:**
- Before: 293 lines
- After: ~100 lines
- **Saved: ~193 lines**

---

### Phase 5: Refactor AgencyDataTableModel (Estimated: 2-3 hours)

**Tasks:**
1. Extend AbstractDataTableModel
2. Implement 8 abstract methods
3. Remove duplicate code
4. Keep complex permission logic
5. Test all functionality

**Expected Reduction:**
- Before: 367 lines
- After: ~120 lines
- **Saved: ~247 lines**

---

## Expected Benefits

### Code Reduction

| Model | Before | After | Saved | % |
|-------|--------|-------|-------|---|
| PlatformStaff | 226 | 80 | 146 | 65% |
| Customer | 293 | 100 | 193 | 66% |
| Agency | 367 | 120 | 247 | 67% |
| **TOTAL** | **886** | **300** | **586** | **66%** |

**Total Potential Savings: ~586 lines (66%)**

---

### Consistency

✅ **All models follow same patterns:**
- Same status badge rendering
- Same action button generation
- Same total count logic
- Same panel integration

✅ **Easier to understand:**
- New developers see consistent structure
- Less cognitive overhead
- Clear separation: abstract vs concrete

---

### Maintainability

✅ **Single source of truth:**
- Bug fixes in one place
- Feature additions inherited by all
- Security improvements propagate

✅ **Type safety:**
- PHP type hints
- Clear method signatures
- IDE autocomplete

---

### Developer Experience

**Before:**
- New DataTable model: 4-5 hours
- Copy-paste from existing model
- Risk of inconsistencies

**After:**
- New DataTable model: 1-2 hours
- Extend AbstractDataTableModel
- Implement 8 methods
- Get all standard methods FREE

**Time Saved: 2-3 hours per new model**

---

## Risk Assessment

### Low Risk ✅

1. **AbstractDataTableModel extends existing DataTableModel**
   - No breaking changes to base class
   - Existing models can continue using DataTableModel

2. **Refactoring is opt-in**
   - Models refactored one at a time
   - Can test each independently
   - Easy rollback if needed

3. **Well-documented patterns**
   - All three existing models follow same pattern
   - Clear duplication identified
   - Proven approach (similar to AbstractCrudController)

---

### Medium Risk ⚠️

1. **Entity-specific permission logic**
   - Agency has complex permission filtering
   - Customer has role-based filtering
   - Solution: Keep entity-specific logic in child get_where()

2. **Different status values**
   - Customer/PlatformStaff use 'aktif'
   - Agency uses 'active'
   - Solution: getStatusActiveValue() abstract method

---

## Success Metrics

### Phase Completion Criteria

**Phase 1 (AbstractDataTableModel):**
- [  ] AbstractDataTableModel.php created
- [  ] All 8 abstract methods defined
- [  ] All 5 concrete methods implemented
- [  ] Comprehensive PHPDoc
- [  ] Autoloader compatible

**Phase 2 (Documentation):**
- [  ] ABSTRACT-DATATABLE-MODEL.md created
- [  ] Quick start guide complete
- [  ] All methods documented
- [  ] Migration guide complete
- [  ] Examples working

**Phase 3 (PlatformStaff Refactor):**
- [  ] Extends AbstractDataTableModel
- [  ] All abstract methods implemented
- [  ] Duplicate code removed
- [  ] DataTable loads correctly
- [  ] Actions work (view, edit, delete)
- [  ] Statistics work
- [  ] ~146 lines saved

**Phase 4 (Customer Refactor):**
- [  ] Extends AbstractDataTableModel
- [  ] All abstract methods implemented
- [  ] Duplicate code removed
- [  ] Custom JOINs preserved
- [  ] Role-based filtering works
- [  ] ~193 lines saved

**Phase 5 (Agency Refactor):**
- [  ] Extends AbstractDataTableModel
- [  ] All abstract methods implemented
- [  ] Duplicate code removed
- [  ] Complex permissions work
- [  ] Cross-plugin filtering works
- [  ] ~247 lines saved

---

## Timeline

**Total Estimated Time:** 10-15 hours

| Phase | Tasks | Estimated Time |
|-------|-------|----------------|
| 1 | Create AbstractDataTableModel | 3-4 hours |
| 2 | Create Documentation | 2-3 hours |
| 3 | Refactor PlatformStaff | 1-2 hours |
| 4 | Refactor Customer | 2-3 hours |
| 5 | Refactor Agency | 2-3 hours |

---

## Related TODOs

- **TODO-1198:** AbstractCrudController, AbstractDashboardController
  - Same refactoring pattern
  - Eliminates controller duplication
  - AbstractDataTableModel completes the "abstract trilogy"

---

## Conclusion

Creating **AbstractDataTableModel** will:

✅ **Eliminate 586 lines of duplicate code (66%)**
✅ **Ensure consistency across all DataTable models**
✅ **Improve maintainability and reduce bugs**
✅ **Speed up new model development by 50%**
✅ **Provide type-safe, well-documented base class**

This refactoring follows the same proven pattern as AbstractCrudController and AbstractDashboardController, completing our "abstract base class trilogy" for a fully DRY, maintainable codebase.

---

**Status:** Ready to implement
**Priority:** High
**Dependencies:** None (can start immediately)
**Risk Level:** Low
**Expected Impact:** High (66% code reduction)

---

**Last Updated:** 2025-01-02
**Created By:** Claude (Sonnet 4.5)
**Status:** ✅ Analysis Complete - Ready for Implementation
