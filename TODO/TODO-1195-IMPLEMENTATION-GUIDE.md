# TODO-1195: Entity Completeness System - Implementation Guide

**Status**: ✅ Base Components Complete
**Next**: Plugin Implementation
**Updated**: 2025-11-01

## 🎯 Overview

Complete base system untuk mengukur dan menampilkan kelengkapan data entity (Customer, Surveyor, Association, dll). Sistem ini **sudah lengkap di wp-app-core** dan siap digunakan oleh plugin.

## ✅ What wp-app-core Provides (COMPLETED)

### 1. Core Models
```
/wp-app-core/src/Models/Completeness/
├── AbstractCompletenessCalculator.php  ← Base class
├── CompletenessResult.php              ← Value object
└── CompletenessManager.php             ← Central registry (Singleton)
```

### 2. UI Components
```
/wp-app-core/src/Components/Completeness/
└── completeness-bar.php                ← Reusable HTML template
```

### 3. Assets
```
/wp-app-core/assets/css/components/
└── completeness-bar.css                ← Responsive styling

/wp-app-core/assets/js/components/
└── completeness-bar.js                 ← AJAX refresh, animations
```

## 📝 What Plugins Must Provide

Setiap plugin (wp-customer, wp-surveyor, wp-association) harus provide:

### 1. Calculator Class (extends AbstractCompletenessCalculator)
### 2. Field Definitions (required + optional dengan weights)
### 3. Minimum Threshold (percentage untuk can_transact)
### 4. Registration (via hook)

---

## 🚀 Step-by-Step Implementation Guide

### Example: wp-customer Plugin

#### Step 1: Create Calculator Class

**File**: `/wp-customer/src/Models/Completeness/CustomerCompletenessCalculator.php`

```php
<?php
namespace WPCustomer\Models\Completeness;

use WPAppCore\Models\Completeness\AbstractCompletenessCalculator;
use WPAppCore\Models\Completeness\CompletenessResult;
use WPCustomer\Models\Customer\CustomerModel;
use WPCustomer\Models\Branch\BranchModel;

class CustomerCompletenessCalculator extends AbstractCompletenessCalculator {

    private CustomerModel $customerModel;
    private BranchModel $branchModel;

    public function __construct() {
        $this->customerModel = new CustomerModel();
        $this->branchModel = new BranchModel();
    }

    /**
     * Define required fields with weights
     */
    protected function getRequiredFields(): array {
        return [
            // Customer basic info (40 points total)
            'customer.name' => 10,          // Auto-filled
            'customer.npwp' => 15,          // IMPORTANT
            'customer.provinsi_id' => 7,
            'customer.regency_id' => 8,

            // Branch pusat (30 points total)
            'branch.exists' => 10,          // Auto-created
            'branch.email' => 10,           // MUST fill
            'branch.phone' => 10,           // MUST fill

            // Employee (30 points total)
            'employee.exists' => 10,        // Auto-created
            'employee.email' => 10,         // MUST fill
            'employee.phone' => 10,         // MUST fill
        ];
    }

    /**
     * Define optional fields (bonus points)
     */
    protected function getOptionalFields(): array {
        return [
            'customer.nib' => 10,           // NIB (nice to have)
            'branch.address' => 5,          // Address details
            'branch.postal_code' => 3,      // Postal code
            'branch.latitude' => 1,         // GPS coordinates
            'branch.longitude' => 1,        // GPS coordinates
        ];
    }

    /**
     * Minimum threshold to transact
     */
    public function getMinimumThreshold(): int {
        return 80; // 80% required (80 points dari required fields)
    }

    /**
     * Calculate completeness
     */
    public function calculate($entity): CompletenessResult {
        // Get customer data
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
            'categories' => $categories,
            'minimum_threshold' => $this->getMinimumThreshold()
        ]);
    }

    /**
     * Check if field is filled
     */
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
                return $branch && $this->isFilled($branch->{$property});

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
                $employee = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}app_customer_employees
                     WHERE customer_id = %d AND status = 'active' LIMIT 1",
                    $customer->id
                ));
                return $employee && $this->isFilled($employee->{$property});
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
            'customer.npwp' => __('NPWP', 'wp-customer'),
            'customer.nib' => __('NIB', 'wp-customer'),
            'customer.provinsi_id' => __('Province', 'wp-customer'),
            'customer.regency_id' => __('City/Regency', 'wp-customer'),
            'branch.email' => __('Branch Email', 'wp-customer'),
            'branch.phone' => __('Branch Phone', 'wp-customer'),
            'branch.address' => __('Branch Address', 'wp-customer'),
            'employee.email' => __('Employee Email', 'wp-customer'),
            'employee.phone' => __('Employee Phone', 'wp-customer'),
        ];
        return $labels[$field] ?? $field;
    }
}
```

#### Step 2: Register Calculator (in plugin bootstrap)

**File**: `/wp-customer/includes/class-wp-customer.php` (or main plugin file)

```php
<?php
use WPAppCore\Models\Completeness\CompletenessManager;
use WPCustomer\Models\Completeness\CustomerCompletenessCalculator;

class WP_Customer {

    public function __construct() {
        // ... other initializations

        // Register completeness calculator
        add_action('wpapp_register_completeness_calculators', [$this, 'register_completeness']);
    }

    /**
     * Register completeness calculator
     */
    public function register_completeness($manager) {
        $manager->register('customer', new CustomerCompletenessCalculator(), [
            'label' => __('Customer', 'wp-customer'),
            'icon' => 'dashicons-businessman',
            'description' => __('Customer profile completeness tracking', 'wp-customer')
        ]);
    }
}
```

#### Step 3: Display Progress Bar (in customer detail view)

**File**: `/wp-customer/src/Views/customer/detail.php` (or dashboard)

```php
<?php
use WPAppCore\Models\Completeness\CompletenessManager;

// Get completeness manager
$manager = CompletenessManager::getInstance();

// Render progress bar
$manager->renderProgressBar('customer', $customer->id, [
    'show_details' => true,
    'show_missing' => true,
    'class' => 'customer-completeness-widget'
]);
```

#### Step 4: Validate Before Transaction

**File**: `/wp-customer/src/Controllers/Transaction/TransactionController.php`

```php
<?php
use WPAppCore\Models\Completeness\CompletenessManager;

public function create_transaction() {
    $customer_id = $_POST['customer_id'];

    // Check completeness
    $manager = CompletenessManager::getInstance();

    if (!$manager->canTransact('customer', $customer_id)) {
        $missing = $manager->getMissingRequiredFields('customer', $customer_id);

        wp_send_json_error([
            'message' => __('Please complete your profile before creating transactions.', 'wp-customer'),
            'missing_fields' => $missing
        ]);
        return;
    }

    // Proceed with transaction creation...
}
```

#### Step 5: AJAX Endpoint (optional - for real-time updates)

**File**: `/wp-customer/includes/class-ajax-handlers.php`

```php
<?php
add_action('wp_ajax_wpapp_get_completeness', 'handle_get_completeness');

function handle_get_completeness() {
    check_ajax_referer('wpapp_panel_nonce', 'nonce');

    $entity_type = sanitize_text_field($_POST['entity_type'] ?? '');
    $entity_id = intval($_POST['entity_id'] ?? 0);

    if (!$entity_type || !$entity_id) {
        wp_send_json_error(['message' => 'Invalid parameters']);
        return;
    }

    $manager = CompletenessManager::getInstance();
    $data = $manager->getCompletenessData($entity_type, $entity_id);

    if ($data) {
        wp_send_json_success($data);
    } else {
        wp_send_json_error(['message' => 'Unable to calculate completeness']);
    }
}
```

---

## 📊 Field Weighting Guidelines

**How to distribute 100 points:**

### Customer Entity Example:
- **Required Fields (70-80 points)**:
  - Critical business data (NPWP, NIB): 10-15 points each
  - Location data (province, regency): 5-10 points each
  - Branch office exists: 10 points
  - Contact info (email, phone): 10 points each
  - Employee exists: 10 points

- **Optional Fields (20-30 points)**:
  - Nice-to-have data: 3-5 points each
  - GPS coordinates: 1-2 points each
  - Additional details: 2-3 points each

### Threshold Recommendations:
- **80%**: Standard business entities (can transact with 80/100 points)
- **90%**: Critical roles (surveyors, inspectors)
- **70%**: Less critical (association members)

---

## 🎨 Usage Examples

### Display in Dashboard Widget

```php
// Customer dashboard
$manager = CompletenessManager::getInstance();
$completeness = $manager->calculate('customer', get_current_user_id());

echo '<div class="dashboard-widget">';
echo '<h3>Profile Status</h3>';
$manager->renderProgressBar('customer', get_current_user_id(), [
    'show_details' => false,
    'show_missing' => true
]);
echo '</div>';
```

### REST API Endpoint

```php
register_rest_route('wp-customer/v1', '/customers/(?P<id>\d+)/completeness', [
    'methods' => 'GET',
    'callback' => function($request) {
        $manager = CompletenessManager::getInstance();
        $data = $manager->getCompletenessData('customer', $request['id']);
        return rest_ensure_response($data);
    },
    'permission_callback' => function($request) {
        return current_user_can('view_customer', $request['id']);
    }
]);
```

### Bulk Statistics

```php
// Get average completeness for all customers
$customer_ids = [1, 2, 3, 4, 5];
$manager = CompletenessManager::getInstance();
$average = $manager->getAverageCompleteness('customer', $customer_ids);

echo "Average customer completeness: {$average}%";
```

---

## 🔧 Customization via Filters

### Override Threshold

```php
add_filter('wpapp_can_transact_customer', function($can_transact, $result, $entity, $calculator) {
    // Admin can always transact regardless of completeness
    if (current_user_can('manage_options')) {
        return true;
    }
    return $can_transact;
}, 10, 4);
```

### Modify Field Definitions

```php
// Add custom field check
add_filter('wpapp_completeness_fields_customer', function($fields) {
    $fields['customer.custom_field'] = 5; // Add custom field worth 5 points
    return $fields;
});
```

---

## ✅ Implementation Checklist

### For wp-customer:
- [ ] Create CustomerCompletenessCalculator class
- [ ] Define required fields (total 70-80 points)
- [ ] Define optional fields (total 20-30 points)
- [ ] Set minimum threshold (80%)
- [ ] Register calculator via hook
- [ ] Add progress bar to customer detail page
- [ ] Add validation before transaction creation
- [ ] Add AJAX endpoint for real-time updates
- [ ] Test with incomplete customer data
- [ ] Test with complete customer data (100%)

### For wp-surveyor (future):
- [ ] Create SurveyorCompletenessCalculator
- [ ] Define surveyor-specific fields (license, certification, coverage area)
- [ ] Set higher threshold (90-100%)
- [ ] Register calculator
- [ ] Display in surveyor profile

### For wp-association (future):
- [ ] Create AssociationCompletenessCalculator
- [ ] Define association fields (registration, members, contact)
- [ ] Set threshold (85%)
- [ ] Register calculator
- [ ] Display in association dashboard

---

## 📈 Next Steps

1. **Implement in wp-customer** (Priority 1)
   - Create CustomerCompletenessCalculator
   - Add to customer detail page
   - Add transaction validation

2. **Test thoroughly**
   - New customer (minimal data)
   - Partial completion
   - Full completion
   - Edge cases

3. **Replicate pattern** to other plugins
   - Copy CustomerCompletenessCalculator as template
   - Adjust fields for entity type
   - Register and display

---

## 🎯 Success Criteria

✅ Progress bar displays correctly
✅ Percentage calculation accurate
✅ Color coding works (red/yellow/green)
✅ Missing fields list shows correctly
✅ Transaction validation blocks when < threshold
✅ AJAX refresh works
✅ Responsive on mobile
✅ Accessible (keyboard navigation, ARIA labels)

---

## 📝 Notes

- Base system is **complete and tested**
- Plugins just need to **provide calculator and register**
- **No need to modify wp-app-core** files
- Pattern is **repeatable** across all entity types
- **Extensible** via WordPress filters/actions
