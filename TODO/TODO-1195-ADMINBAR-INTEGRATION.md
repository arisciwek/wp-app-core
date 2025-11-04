# TODO-1195: Completeness System - AdminBar Integration

**Status**: ✅ Ready for Plugin Implementation
**Created**: 2025-11-01
**Context**: Display completeness bar in AdminBar dropdown (always visible across all pages)

---

## 🎯 Goal

Display customer/surveyor/association completeness progress in **AdminBar dropdown** so user always sees their profile status without going to specific dashboard page.

---

## ✅ What's Already Complete (wp-app-core)

### 1. **AdminBar Hook** ✅
Added action hook in AdminBar dropdown template:

**File**: `/wp-app-core/src/Views/templates/admin-bar/dropdown.php`

```php
/**
 * Hook: wp_app_core_admin_bar_dropdown_content
 *
 * Allows plugins to inject content into admin bar dropdown.
 *
 * @param int $user_id WordPress user ID
 * @param WP_User $user WordPress user object
 * @param array|null $user_info User entity information
 */
do_action('wp_app_core_admin_bar_dropdown_content', $user_id, $user, $user_info);
```

### 2. **Compact Template Variant** ✅
Created space-efficient version for AdminBar display:

**File**: `/wp-app-core/src/Components/Completeness/completeness-bar-compact.php`

Features:
- Minimal padding (optimized for dropdown)
- Single-line status display
- Small progress bar (6px height)
- No detailed breakdown
- No missing fields list (just count)
- Inline CSS (no external dependency)

### 3. **Variant Support in Manager** ✅
Updated `CompletenessManager::renderProgressBar()` to support variants:

```php
$manager->renderProgressBar('customer', $customer_id, [
    'variant' => 'compact',  // or 'default'
    'show_details' => false,
    'show_missing' => false
]);
```

---

## 📝 Plugin Implementation (wp-customer example)

### Step 1: Hook into AdminBar dropdown

**File**: `/wp-customer/includes/class-wp-customer.php` (or integration file)

```php
<?php
use WPAppCore\Models\Completeness\CompletenessManager;

class WP_Customer {

    public function __construct() {
        // ... other hooks

        // Add completeness to AdminBar dropdown
        add_action('wp_app_core_admin_bar_dropdown_content', [$this, 'render_completeness_in_admin_bar'], 10, 3);
    }

    /**
     * Render completeness bar in AdminBar dropdown
     *
     * @param int $user_id WordPress user ID
     * @param WP_User $user WordPress user object
     * @param array|null $user_info User entity data
     */
    public function render_completeness_in_admin_bar($user_id, $user, $user_info) {
        // Only show for customer role users
        if (!in_array('customer', (array) $user->roles)) {
            return;
        }

        // Get customer ID from user meta or entity relation
        $customer_id = get_user_meta($user_id, 'customer_id', true);

        if (!$customer_id) {
            // Try from user_info if available
            $customer_id = $user_info['entity_id'] ?? null;
        }

        if (!$customer_id) {
            return; // No customer associated
        }

        // Render compact completeness bar
        echo '<div class="info-section">';
        echo '<strong>' . __('Profile Completeness:', 'wp-customer') . '</strong><br>';

        $manager = CompletenessManager::getInstance();
        $manager->renderProgressBar('customer', $customer_id, [
            'variant' => 'compact',
            'show_details' => false,
            'show_missing' => false
        ]);

        echo '</div>';
    }
}
```

---

## 🎨 Visual Preview

### AdminBar Dropdown with Completeness:

```
┌─────────────────────────────────────┐
│ User Information:                   │
│ ID: 123                             │
│ Username: johndoe                   │
│ Email: john@example.com             │
├─────────────────────────────────────┤
│ Entity Information:                 │
│ Entity: PT Maju Bersama             │
│ Code: 2025AA12                      │
├─────────────────────────────────────┤
│ Roles:                              │
│ • Customer                          │
├─────────────────────────────────────┤
│ Key Capabilities:                   │
│ ✓ View Customer List                │
│ ✓ Edit Own Customer                 │
├─────────────────────────────────────┤
│ Profile Completeness:               │ ← NEW SECTION
│ Customer Status: 75% Good           │
│ ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓░░░░░ (75%)         │
│ ⚠ 80% required to transact          │
│ ℹ 3 fields missing                  │
└─────────────────────────────────────┘
```

---

## 🔄 Alternative Implementations

### Option A: Show for All Users (not just specific role)

```php
public function render_completeness_in_admin_bar($user_id, $user, $user_info) {
    // Determine entity type from user_info
    $entity_type = $user_info['plugin_id'] ?? null; // 'customer', 'surveyor', etc.

    if (!$entity_type) {
        return; // No entity associated
    }

    $entity_id = $user_info['entity_id'] ?? null;

    if (!$entity_id) {
        return;
    }

    $manager = CompletenessManager::getInstance();

    if (!$manager->isRegistered($entity_type)) {
        return; // Calculator not registered for this entity type
    }

    // Render completeness for ANY entity type
    echo '<div class="info-section">';
    echo '<strong>' . __('Profile Completeness:', 'wp-customer') . '</strong><br>';
    $manager->renderProgressBar($entity_type, $entity_id, ['variant' => 'compact']);
    echo '</div>';
}
```

### Option B: Show Multiple Entity Types (if user has multiple roles)

```php
public function render_completeness_in_admin_bar($user_id, $user, $user_info) {
    $manager = CompletenessManager::getInstance();

    // Check if user is customer
    if (in_array('customer', (array) $user->roles)) {
        $customer_id = get_user_meta($user_id, 'customer_id', true);
        if ($customer_id) {
            echo '<div class="info-section">';
            echo '<strong>' . __('Customer Profile:', 'wp-customer') . '</strong><br>';
            $manager->renderProgressBar('customer', $customer_id, ['variant' => 'compact']);
            echo '</div>';
        }
    }

    // Check if user is surveyor (from wp-surveyor plugin)
    if (in_array('surveyor', (array) $user->roles)) {
        $surveyor_id = get_user_meta($user_id, 'surveyor_id', true);
        if ($surveyor_id) {
            echo '<div class="info-section">';
            echo '<strong>' . __('Surveyor Profile:', 'wp-surveyor') . '</strong><br>';
            $manager->renderProgressBar('surveyor', $surveyor_id, ['variant' => 'compact']);
            echo '</div>';
        }
    }
}
```

### Option C: Show with Quick Edit Link

```php
public function render_completeness_in_admin_bar($user_id, $user, $user_info) {
    $customer_id = get_user_meta($user_id, 'customer_id', true);

    if (!$customer_id) {
        return;
    }

    $manager = CompletenessManager::getInstance();
    $completeness = $manager->calculate('customer', $customer_id);

    echo '<div class="info-section">';
    echo '<strong>' . __('Profile Completeness:', 'wp-customer') . '</strong><br>';

    $manager->renderProgressBar('customer', $customer_id, ['variant' => 'compact']);

    // Add quick action link if incomplete
    if (!$completeness->can_transact) {
        $edit_url = admin_url('admin.php?page=wp-customer&tab=profile');
        echo '<a href="' . esc_url($edit_url) . '" class="button button-small" style="margin-top:8px;">';
        echo __('Complete Profile', 'wp-customer');
        echo '</a>';
    }

    echo '</div>';
}
```

---

## 🎯 Best Practices

### 1. **Performance Consideration**
Completeness calculation is cached in CompletenessManager, but still:
- Don't run heavy queries in the hook
- Use cached customer data if available
- Consider transient caching for expensive calculations

### 2. **User Experience**
- Keep it compact - AdminBar dropdown is small space
- Show actionable info only
- Provide quick link to complete profile if needed

### 3. **Multi-Plugin Support**
If multiple plugins use completeness:
- Each plugin hooks in independently
- Each renders their own section
- No conflicts because hooks are additive

### 4. **Conditional Display**
Only show if relevant:
```php
// Don't show if user is admin (they don't need it)
if (current_user_can('manage_options')) {
    return;
}

// Don't show if already 100% complete
if ($completeness->percentage >= 100) {
    return; // or show congratulations message
}
```

---

## 🚀 Ready to Implement!

**wp-app-core provides:**
- ✅ Hook: `wp_app_core_admin_bar_dropdown_content`
- ✅ Template: `completeness-bar-compact.php`
- ✅ Manager: Support for `variant => 'compact'`

**Plugin (wp-customer) just needs:**
1. Hook into `wp_app_core_admin_bar_dropdown_content`
2. Get customer ID for current user
3. Call `$manager->renderProgressBar('customer', $customer_id, ['variant' => 'compact'])`

**Done! 🎉**

---

## 📊 Comparison: AdminBar vs Dashboard Display

| Feature | AdminBar (Compact) | Dashboard (Full) |
|---------|-------------------|------------------|
| Always visible | ✅ Yes (all pages) | ❌ Only on dashboard |
| Space required | Minimal (< 80px) | Large (200px+) |
| Details shown | Status + % only | Full breakdown |
| Missing fields | Count only | Full list |
| Categories | No | Yes |
| Best for | Quick status check | Detailed review |

**Recommendation**: Use **both**!
- AdminBar: Quick awareness, always visible
- Dashboard: Detailed review, action items

---

## 🔗 Related Files

**wp-app-core:**
- `/src/Views/templates/admin-bar/dropdown.php` - Hook location
- `/src/Components/Completeness/completeness-bar-compact.php` - Compact template
- `/src/Models/Completeness/CompletenessManager.php` - Manager with variant support

**Documentation:**
- `TODO-1195-entity-completeness-system.md` - Main spec
- `TODO-1195-IMPLEMENTATION-GUIDE.md` - Full implementation guide
- `TODO-1195-ADMINBAR-INTEGRATION.md` - This file

---

**Status**: ✅ **Ready for wp-customer implementation!**
