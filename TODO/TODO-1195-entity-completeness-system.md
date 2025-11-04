# TODO-1195: Generic Entity Completeness System

**Status**: 🚧 In Progress
**Priority**: High
**Assignee**: arisciwek
**Created**: 2025-11-01
**Updated**: 2025-11-01

## Overview

Create a generic, reusable system for calculating and displaying entity data completeness across multiple plugins (wp-customer, wp-surveyor, wp-association, etc.).

## Business Requirements

### Purpose
- Show users how complete their profile/data is
- Block transactions/actions until minimum completeness threshold is met
- Provide visual feedback (progress bar) on what's missing
- Standardize completeness calculation across all plugins

### Use Cases

**WP Customer:**
- Customer must be 80% complete before creating transactions
- Required: NPWP, NIB, Province, Regency, Branch with email/phone, Employee

**WP Surveyor (future):**
- Surveyor must be 100% complete before assignment
- Required: License number, Certification, Province coverage, Bank account

**WP Association (future):**
- Association must be 90% complete before member registration
- Required: Registration number, Address, Contact person, Members list

## Technical Design

### 1. Base Architecture (wp-app-core)

```
/wp-app-core/
├── src/
│   ├── Models/
│   │   └── Completeness/
│   │       ├── AbstractCompletenessCalculator.php  ← Base class
│   │       └── CompletenessResult.php              ← Value object
│   ├── Components/
│   │   └── Completeness/
│   │       └── completeness-bar.php                ← Reusable template
│   └── Traits/
│       └── HasCompleteness.php                     ← Optional trait
├── assets/
│   ├── css/
│   │   └── components/
│   │       └── completeness-bar.css
│   └── js/
│       └── components/
│           └── completeness-bar.js
└── TODO/
    └── TODO-1195-entity-completeness-system.md
```

### 2. Core Components

#### A. AbstractCompletenessCalculator (Base Class)

```php
<?php
namespace WPAppCore\Models\Completeness;

abstract class AbstractCompletenessCalculator {

    /**
     * Calculate completeness for an entity
     *
     * @param int|object $entity Entity ID or object
     * @return CompletenessResult
     */
    abstract public function calculate($entity): CompletenessResult;

    /**
     * Define required fields with weights
     * Format: ['field_name' => weight, ...]
     *
     * @return array<string, int>
     */
    abstract protected function getRequiredFields(): array;

    /**
     * Define optional fields with weights
     *
     * @return array<string, int>
     */
    abstract protected function getOptionalFields(): array;

    /**
     * Get minimum threshold percentage to allow transactions
     *
     * @return int Percentage (0-100)
     */
    abstract public function getMinimumThreshold(): int;

    /**
     * Check if entity can perform transactions
     *
     * @param int|object $entity
     * @return bool
     */
    public function canTransact($entity): bool {
        $result = $this->calculate($entity);
        return $result->percentage >= $this->getMinimumThreshold();
    }

    /**
     * Get missing fields that prevent transactions
     *
     * @param int|object $entity
     * @return array
     */
    public function getMissingRequiredFields($entity): array {
        $result = $this->calculate($entity);
        return $result->missing_required;
    }
}
```

#### B. CompletenessResult (Value Object)

```php
<?php
namespace WPAppCore\Models\Completeness;

class CompletenessResult {

    /** @var int Completion percentage (0-100) */
    public int $percentage;

    /** @var int Total possible points */
    public int $total_points;

    /** @var int Earned points */
    public int $earned_points;

    /** @var array Completed fields */
    public array $completed_fields;

    /** @var array Missing required fields */
    public array $missing_required;

    /** @var array Missing optional fields */
    public array $missing_optional;

    /** @var bool Can entity transact? */
    public bool $can_transact;

    /** @var array Breakdown by category */
    public array $categories;

    public function __construct(array $data) {
        $this->percentage = $data['percentage'];
        $this->total_points = $data['total_points'];
        $this->earned_points = $data['earned_points'];
        $this->completed_fields = $data['completed_fields'] ?? [];
        $this->missing_required = $data['missing_required'] ?? [];
        $this->missing_optional = $data['missing_optional'] ?? [];
        $this->can_transact = $data['can_transact'] ?? false;
        $this->categories = $data['categories'] ?? [];
    }

    public function toArray(): array {
        return get_object_vars($this);
    }
}
```

#### C. Progress Bar Component (Reusable Template)

**Template: `/wp-app-core/src/Components/Completeness/completeness-bar.php`**

```php
<?php
/**
 * Completeness Progress Bar Component
 *
 * @var CompletenessResult $completeness
 * @var string $entity_type (customer|surveyor|association)
 */

$color_class = 'progress-danger';
if ($completeness->percentage >= 80) {
    $color_class = 'progress-success';
} elseif ($completeness->percentage >= 50) {
    $color_class = 'progress-warning';
}
?>

<div class="wpapp-completeness-container">
    <div class="wpapp-completeness-header">
        <h4><?php _e('Profile Completeness', 'wp-app-core'); ?></h4>
        <span class="wpapp-completeness-percentage"><?php echo $completeness->percentage; ?>%</span>
    </div>

    <div class="wpapp-progress-bar-container">
        <div class="wpapp-progress-bar <?php echo $color_class; ?>"
             style="width: <?php echo $completeness->percentage; ?>%"
             role="progressbar"
             aria-valuenow="<?php echo $completeness->percentage; ?>"
             aria-valuemin="0"
             aria-valuemax="100">
        </div>
    </div>

    <?php if (!$completeness->can_transact): ?>
    <div class="wpapp-completeness-warning">
        <span class="dashicons dashicons-warning"></span>
        <?php printf(
            __('Complete at least %d%% of your profile to start transactions.', 'wp-app-core'),
            $completeness->getMinimumThreshold()
        ); ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($completeness->missing_required)): ?>
    <div class="wpapp-completeness-missing">
        <h5><?php _e('Required Information Missing:', 'wp-app-core'); ?></h5>
        <ul>
            <?php foreach ($completeness->missing_required as $field): ?>
            <li><?php echo esc_html($field); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <details class="wpapp-completeness-details">
        <summary><?php _e('View Detailed Breakdown', 'wp-app-core'); ?></summary>
        <div class="wpapp-completeness-breakdown">
            <?php foreach ($completeness->categories as $category => $data): ?>
            <div class="category-item">
                <strong><?php echo esc_html($category); ?>:</strong>
                <span><?php echo $data['earned']; ?>/<?php echo $data['total']; ?> points</span>
            </div>
            <?php endforeach; ?>
        </div>
    </details>
</div>
```

### 3. Plugin Implementation (wp-customer)

#### CustomerCompletenessCalculator

```php
<?php
namespace WPCustomer\Models\Completeness;

use WPAppCore\Models\Completeness\AbstractCompletenessCalculator;
use WPAppCore\Models\Completeness\CompletenessResult;
use WPCustomer\Models\Customer\CustomerModel;
use WPCustomer\Models\Branch\BranchModel;
use WPCustomer\Models\Employee\CustomerEmployeeModel;

class CustomerCompletenessCalculator extends AbstractCompletenessCalculator {

    private CustomerModel $customerModel;
    private BranchModel $branchModel;
    private CustomerEmployeeModel $employeeModel;

    public function __construct() {
        $this->customerModel = new CustomerModel();
        $this->branchModel = new BranchModel();
        $this->employeeModel = new CustomerEmployeeModel();
    }

    protected function getRequiredFields(): array {
        return [
            // Customer basic info (30 points)
            'customer.name' => 10,
            'customer.npwp' => 10,
            'customer.provinsi_id' => 5,
            'customer.regency_id' => 5,

            // Branch pusat (35 points)
            'branch.exists' => 10,
            'branch.email' => 10,
            'branch.phone' => 10,
            'branch.address' => 5,

            // Employee (35 points)
            'employee.exists' => 10,
            'employee.email' => 10,
            'employee.phone' => 10,
            'employee.position' => 5,
        ];
    }

    protected function getOptionalFields(): array {
        return [
            'customer.nib' => 10,
            'branch.postal_code' => 5,
            'branch.latitude' => 5,
            'branch.longitude' => 5,
        ];
    }

    public function getMinimumThreshold(): int {
        return 80; // 80% required for transactions
    }

    public function calculate($entity): CompletenessResult {
        // Get customer
        $customer = is_object($entity) ? $entity : $this->customerModel->find($entity);

        if (!$customer) {
            throw new \Exception('Customer not found');
        }

        $total_points = 0;
        $earned_points = 0;
        $completed = [];
        $missing_required = [];
        $missing_optional = [];
        $categories = [
            'Customer Info' => ['total' => 0, 'earned' => 0],
            'Branch Info' => ['total' => 0, 'earned' => 0],
            'Employee Info' => ['total' => 0, 'earned' => 0],
        ];

        // Check required fields
        foreach ($this->getRequiredFields() as $field => $weight) {
            $total_points += $weight;

            // Determine category
            $category = $this->getCategoryForField($field);
            $categories[$category]['total'] += $weight;

            if ($this->checkField($field, $customer)) {
                $earned_points += $weight;
                $completed[] = $field;
                $categories[$category]['earned'] += $weight;
            } else {
                $missing_required[] = $this->getFieldLabel($field);
            }
        }

        // Check optional fields
        foreach ($this->getOptionalFields() as $field => $weight) {
            $total_points += $weight;

            $category = $this->getCategoryForField($field);
            $categories[$category]['total'] += $weight;

            if ($this->checkField($field, $customer)) {
                $earned_points += $weight;
                $completed[] = $field;
                $categories[$category]['earned'] += $weight;
            } else {
                $missing_optional[] = $this->getFieldLabel($field);
            }
        }

        $percentage = $total_points > 0 ? round(($earned_points / $total_points) * 100) : 0;

        return new CompletenessResult([
            'percentage' => $percentage,
            'total_points' => $total_points,
            'earned_points' => $earned_points,
            'completed_fields' => $completed,
            'missing_required' => $missing_required,
            'missing_optional' => $missing_optional,
            'can_transact' => $percentage >= $this->getMinimumThreshold(),
            'categories' => $categories
        ]);
    }

    private function checkField(string $field, object $customer): bool {
        [$entity, $property] = explode('.', $field);

        switch ($entity) {
            case 'customer':
                return !empty($customer->{$property});

            case 'branch':
                $branch = $this->branchModel->findPusatByCustomer($customer->id);
                if ($property === 'exists') {
                    return $branch !== null;
                }
                return $branch && !empty($branch->{$property});

            case 'employee':
                global $wpdb;
                if ($property === 'exists') {
                    $count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_employees
                         WHERE customer_id = %d AND status = 'active'",
                        $customer->id
                    ));
                    return $count > 0;
                }
                // Check first active employee
                $employee = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}app_customer_employees
                     WHERE customer_id = %d AND status = 'active' LIMIT 1",
                    $customer->id
                ));
                return $employee && !empty($employee->{$property});
        }

        return false;
    }

    private function getCategoryForField(string $field): string {
        if (strpos($field, 'customer.') === 0) return 'Customer Info';
        if (strpos($field, 'branch.') === 0) return 'Branch Info';
        if (strpos($field, 'employee.') === 0) return 'Employee Info';
        return 'Other';
    }

    private function getFieldLabel(string $field): string {
        $labels = [
            'customer.name' => __('Customer Name', 'wp-customer'),
            'customer.npwp' => __('NPWP', 'wp-customer'),
            'customer.nib' => __('NIB', 'wp-customer'),
            'customer.provinsi_id' => __('Province', 'wp-customer'),
            'customer.regency_id' => __('City/Regency', 'wp-customer'),
            'branch.exists' => __('Branch Office', 'wp-customer'),
            'branch.email' => __('Branch Email', 'wp-customer'),
            'branch.phone' => __('Branch Phone', 'wp-customer'),
            'branch.address' => __('Branch Address', 'wp-customer'),
            'branch.postal_code' => __('Postal Code', 'wp-customer'),
            'employee.exists' => __('Employee/PIC', 'wp-customer'),
            'employee.email' => __('Employee Email', 'wp-customer'),
            'employee.phone' => __('Employee Phone', 'wp-customer'),
        ];

        return $labels[$field] ?? $field;
    }
}
```

### 4. Usage Examples

#### In Customer Dashboard

```php
use WPCustomer\Models\Completeness\CustomerCompletenessCalculator;

$calculator = new CustomerCompletenessCalculator();
$completeness = $calculator->calculate($customer_id);

// Display progress bar
include WP_APP_CORE_PATH . 'src/Components/Completeness/completeness-bar.php';

// Check if can transact
if (!$calculator->canTransact($customer_id)) {
    wp_die(__('Please complete your profile before creating transactions.', 'wp-customer'));
}
```

#### As REST API Endpoint

```php
add_action('rest_api_init', function() {
    register_rest_route('wp-customer/v1', '/customers/(?P<id>\d+)/completeness', [
        'methods' => 'GET',
        'callback' => function($request) {
            $calculator = new CustomerCompletenessCalculator();
            $result = $calculator->calculate($request['id']);
            return rest_ensure_response($result->toArray());
        },
        'permission_callback' => function($request) {
            return current_user_can('view_customer', $request['id']);
        }
    ]);
});
```

## Implementation Plan

### Phase 1: Core System (wp-app-core)
- [ ] Create AbstractCompletenessCalculator
- [ ] Create CompletenessResult value object
- [ ] Create progress bar component (HTML/CSS/JS)
- [ ] Add unit tests

### Phase 2: Customer Implementation (wp-customer)
- [ ] Create CustomerCompletenessCalculator
- [ ] Add progress bar to customer detail page
- [ ] Add validation before transaction creation
- [ ] Add AJAX endpoint for real-time updates

### Phase 3: Integration Hooks
- [ ] Filter: `wpapp_completeness_fields_{entity}` - Modify field definitions
- [ ] Filter: `wpapp_completeness_threshold_{entity}` - Modify threshold
- [ ] Action: `wpapp_completeness_calculated` - After calculation
- [ ] Filter: `wpapp_can_transact_{entity}` - Override transaction check

## Files to Create

### wp-app-core
```
src/Models/Completeness/
├── AbstractCompletenessCalculator.php
└── CompletenessResult.php

src/Components/Completeness/
└── completeness-bar.php

assets/css/components/
└── completeness-bar.css

assets/js/components/
└── completeness-bar.js
```

### wp-customer
```
src/Models/Completeness/
└── CustomerCompletenessCalculator.php

src/Views/customer/components/
└── completeness-widget.php
```

## Testing Scenarios

1. **New Customer (0% complete)**
   - Only name and code exist
   - Should show 10-20% complete
   - Cannot transact

2. **Customer with Basic Info (40% complete)**
   - Has NPWP, province, regency
   - Has branch but no email/phone
   - Cannot transact

3. **Customer Ready to Transact (80% complete)**
   - All required fields filled
   - Can create transactions

4. **Fully Complete Customer (100%)**
   - All required + optional fields
   - Can transact freely

## Future Enhancements

1. **Real-time Updates**: WebSocket for live progress updates
2. **Gamification**: Badges/rewards for completion milestones
3. **Smart Suggestions**: AI-powered field completion hints
4. **Bulk Analysis**: Dashboard widget showing avg completeness across all customers
5. **Export**: CSV report of incomplete profiles

## References

- TODO-2188: Customer Modal CRUD (uses completeness check)
- TODO-1194: Modal Template System (used for edit prompts)
- Pattern similar to Laravel's validation system
