# TODO-1209: Implement Generic Demo Data Dependency Checking

**Status**: PENDING
**Priority**: HIGH
**Created**: 2025-01-13
**Plugin**: wp-app-core
**Type**: Feature - Dependency Management
**Related**: TODO-1207 (Abstract Demo Data Pattern)

## 📋 Overview

Implement generic dependency checking untuk demo data buttons di shared assets. Restore functionality yang hilang saat refactor dari plugin-specific ke shared assets. Ensure buttons disabled jika dependency belum di-generate.

## 🎯 Goals

1. **Restore Lost Feature**: Dependency checking yang ada di old customer-demo-data-tab-script.js
2. **Generic Pattern**: Work untuk semua plugins (wp-customer, wp-agency, 17 others)
3. **Flexible Configuration**: Via data attributes (no hardcoded entity names)
4. **Auto Re-check**: After successful generation
5. **User Friendly**: Clear feedback why button disabled

## ❌ Problem Statement

### **Current State** (wpapp-demo-data.js v2.0.0):
- ❌ No dependency checking
- ❌ All buttons always enabled
- ❌ User bisa generate out of order
- ❌ No validation untuk prerequisite data

### **Old State** (customer-demo-data-tab-script.js.old):
- ✅ Had `checkDependencies()` function
- ✅ Buttons disabled until dependency met
- ✅ Auto re-check after success
- ✅ AJAX check via `customer_check_demo_data`

### **Impact**:
```
User bisa klik "Generate Branches" sebelum "Generate Customers"
  → Backend error: "No customers found"
  → Bad UX, confusing error messages
```

## 🎨 Proposed Solution: Attribute-Based Pattern

### **Why Attribute-Based?**
1. ✅ **Fully Generic**: No hardcoded entity names
2. ✅ **Self-Documenting**: Dependencies visible in HTML
3. ✅ **Plugin-Agnostic**: Works untuk wp-customer, wp-agency, all plugins
4. ✅ **Easy Debug**: Inspect element shows all info
5. ✅ **Flexible**: Each plugin defines own dependencies

### **Architecture**:

```
┌─────────────────────────────────────────────┐
│ Shared JS (wp-app-core)                    │
│ - checkDependencies() generic function     │
│ - Read data-requires, data-check-action    │
│ - Call plugin-specific AJAX                │
│ - Enable/disable buttons                   │
└─────────────────────────────────────────────┘
                    ↓
    ┌───────────────┴───────────────┐
    ↓                                ↓
┌─────────────────────┐   ┌─────────────────────┐
│ wp-customer         │   │ wp-agency           │
│ Backend Handler:    │   │ Backend Handler:    │
│ - customer_check_   │   │ - agency_check_     │
│   demo_data         │   │   demo_data         │
│ - Count customers   │   │ - Count agencies    │
│ - Count branches    │   │ - Count divisions   │
└─────────────────────┘   └─────────────────────┘
```

## 📁 Files to Modify

### **1. Shared JS** (NEW FEATURE)
**File**: `/wp-app-core/assets/js/demo-data/wpapp-demo-data.js`
**Action**: Add `checkDependencies()` function

### **2. Templates** (ADD ATTRIBUTES)
**File**: `/wp-customer/src/Views/templates/settings/tab-demo-data.php`
**Action**: Add data-requires and data-check-action attributes

### **3. Backend Handler** (ALREADY EXISTS)
**File**: `/wp-customer/src/Controllers/SettingsController.php`
**Action**: Verify `handle_check_demo_data()` works (line 359-411)

## 🔧 Implementation Details

### **Step 1: Update Shared JS (wpapp-demo-data.js)**

Add after line 83 (after showMessage function):

```javascript
/**
 * Check dependencies for demo data buttons
 * Buttons with data-requires will be checked and enabled/disabled
 *
 * Required attributes:
 * - data-requires: Entity type that must exist (e.g., 'customer', 'agency')
 * - data-check-action: AJAX action for checking (e.g., 'customer_check_demo_data')
 * - data-check-nonce: Nonce for check action
 */
function checkDependencies() {
    console.log('[DemoData] Checking dependencies...');

    $('.demo-data-button[data-requires]').each(function() {
        const $button = $(this);
        const requiredType = $button.data('requires');
        const checkAction = $button.data('check-action');
        const checkNonce = $button.data('check-nonce');

        // Skip if no check action configured
        if (!checkAction || !checkNonce) {
            console.log('[DemoData] Button has data-requires but no check-action:', $button);
            return;
        }

        // Show checking state
        $button.prop('disabled', true)
               .attr('title', `Checking ${requiredType} data...`);

        // AJAX check
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: checkAction,
                type: requiredType,
                nonce: checkNonce
            },
            success: function(response) {
                if (response.success) {
                    const hasData = response.data.has_data;
                    const count = response.data.count || 0;

                    // Enable/disable based on data existence
                    $button.prop('disabled', !hasData)
                           .toggleClass('dependency-met', hasData)
                           .toggleClass('dependency-missing', !hasData);

                    // Update title/tooltip
                    if (hasData) {
                        $button.attr('title', `${requiredType} data exists (${count} records)`);
                    } else {
                        $button.attr('title', `Requires ${requiredType} data to be generated first`);
                    }

                    console.log(`[DemoData] Dependency check for ${requiredType}:`, hasData ? 'MET' : 'MISSING');
                } else {
                    console.error('[DemoData] Dependency check failed:', response);
                    $button.prop('disabled', true)
                           .attr('title', 'Error checking dependency status');
                }
            },
            error: function(xhr, status, error) {
                console.error('[DemoData] Dependency check AJAX error:', {xhr, status, error});
                $button.prop('disabled', true)
                       .attr('title', 'Error checking dependency status');
            }
        });
    });
}
```

### **Step 2: Call checkDependencies() on Page Load**

Add after line 310 (after auto-load statistics):

```javascript
/**
 * Check dependencies on page load
 */
checkDependencies();
```

### **Step 3: Re-check After Successful Generation**

Update executeAjaxRequest() success handler (around line 181):

```javascript
// After line 198 (after reload page)
if (successReload) {
    setTimeout(function() {
        window.location.reload();
    }, 1500);
} else {
    // Re-check dependencies if no reload
    checkDependencies();
}
```

### **Step 4: Update wp-customer Template**

**File**: `/wp-customer/src/Views/templates/settings/tab-demo-data.php`

**Add attributes** to buttons with dependencies:

```php
<!-- Membership Features (depends on membership-groups) -->
<button type="button"
        class="button button-primary demo-data-button"
        data-action="customer_generate_membership_features"
        data-nonce="<?php echo wp_create_nonce('customer_generate_membership_features'); ?>"
        data-confirm="<?php esc_attr_e('Generate membership features?', 'wp-customer'); ?>"
        data-requires="membership-groups"
        data-check-action="customer_check_demo_data"
        data-check-nonce="<?php echo wp_create_nonce('customer_check_demo_data'); ?>">
    <?php _e('Generate Membership Features', 'wp-customer'); ?>
</button>

<!-- Membership Levels (depends on membership-features) -->
<button ...
        data-requires="membership-features"
        data-check-action="customer_check_demo_data"
        data-check-nonce="<?php echo wp_create_nonce('customer_check_demo_data'); ?>">

<!-- Branches (depends on customer) -->
<button ...
        data-requires="customer"
        data-check-action="customer_check_demo_data"
        data-check-nonce="<?php echo wp_create_nonce('customer_check_demo_data'); ?>">

<!-- Employees (depends on branch) -->
<button ...
        data-requires="branch"
        data-check-action="customer_check_demo_data"
        data-check-nonce="<?php echo wp_create_nonce('customer_check_demo_data'); ?>">

<!-- Customer Memberships (depends on branch) -->
<button ...
        data-requires="branch"
        data-check-action="customer_check_demo_data"
        data-check-nonce="<?php echo wp_create_nonce('customer_check_demo_data'); ?>">

<!-- Company Invoices (depends on memberships) -->
<button ...
        data-requires="memberships"
        data-check-action="customer_check_demo_data"
        data-check-nonce="<?php echo wp_create_nonce('customer_check_demo_data'); ?>">
```

### **Step 5: Verify Backend Handler**

**File**: `/wp-customer/src/Controllers/SettingsController.php`

Verify `handle_check_demo_data()` exists (line 359-411):

```php
public function handle_check_demo_data() {
    // Already implemented and working ✓
    // Checks: branch, customer, membership-groups, membership-features, memberships
}
```

### **Step 6: Add CSS for Visual Feedback**

**File**: `/wp-app-core/assets/css/demo-data/wpapp-demo-data.css`

Add after line 117 (after button:disabled):

```css
/* ====== Dependency States ====== */
.demo-data-card button.dependency-missing {
    opacity: 0.6;
    cursor: not-allowed;
    background-color: #9ea3a8;
    border-color: #8c8f94;
}

.demo-data-card button.dependency-missing:hover {
    background-color: #9ea3a8;
    border-color: #8c8f94;
}

.demo-data-card button.dependency-met {
    /* Normal button styling (already defined) */
}
```

## 📊 Dependency Map

### **wp-customer Dependencies**:
```
membership-groups (no deps)
  └─→ membership-features
       └─→ membership-levels

customer (no deps)
  └─→ branches
       ├─→ employees
       ├─→ memberships
       │    └─→ invoices
       └─→ (any branch-dependent entities)
```

### **wp-agency Dependencies** (example):
```
agencies (no deps)
  └─→ divisions
       └─→ employees

jurisdictions (no deps)
  └─→ agencies
```

### **Generic Pattern**:
```
ANY PLUGIN:
  entity-without-deps (no data-requires)
    └─→ dependent-entity (data-requires="entity-without-deps")
         └─→ sub-dependent (data-requires="dependent-entity")
```

## ✅ Testing Checklist

### **Unit Tests** (Manual):
- [ ] checkDependencies() called on page load
- [ ] Buttons with data-requires are checked via AJAX
- [ ] Buttons disabled when dependency missing
- [ ] Buttons enabled when dependency met
- [ ] Tooltip shows correct message
- [ ] CSS classes applied correctly

### **Integration Tests**:
- [ ] Generate membership-groups → membership-features button enabled
- [ ] Generate customer → branches button enabled
- [ ] Generate branches → employees, memberships enabled
- [ ] Refresh page → states persisted
- [ ] Re-check after success works

### **Cross-Plugin Tests**:
- [ ] wp-customer: All dependency chains work
- [ ] wp-agency: Pattern works (after implementing attributes)
- [ ] Other plugins: Can adopt pattern easily

### **Edge Cases**:
- [ ] Button without data-check-action → not checked (no error)
- [ ] Backend check fails → button disabled (safe fallback)
- [ ] Backend returns error → button disabled
- [ ] Multiple dependencies on one button (future: data-requires="branch,customer")

## 🚨 Breaking Changes

**None** - This is additive:
- Buttons without data-requires work as before
- Old templates without attributes work (just no dependency checking)
- Backward compatible

## 📈 Version Updates

### **wpapp-demo-data.js**:
- Version: 2.0.0 → 2.1.0
- Changelog: "Added generic dependency checking with data-requires pattern"

### **wpapp-demo-data.css**:
- Version: 2.1.0 → 2.2.0
- Changelog: "Added dependency state styling (.dependency-missing, .dependency-met)"

### **tab-demo-data.php** (wp-customer):
- Version: 2.2.0 → 2.3.0
- Changelog: "Added dependency attributes (data-requires, data-check-action)"

## 🔗 Related TODOs

- **Parent**: TODO-1207 (Abstract Demo Data Pattern) - Phase 2
- **Sibling**: TODO-2201 (wp-customer implementation)
- **Depends On**: TODO-1207 Phase 1.5 ✅ (Completed)

## 📝 Implementation Timeline

### **Phase 1: Core Implementation** (1-2 hours)
- [ ] Add checkDependencies() to wpapp-demo-data.js
- [ ] Add CSS for dependency states
- [ ] Test in wp-app-core demo-data tab (if applicable)

### **Phase 2: wp-customer Integration** (1 hour)
- [ ] Add attributes to all buttons in tab-demo-data.php
- [ ] Test dependency chains
- [ ] Verify backend handler works

### **Phase 3: Documentation** (30 mins)
- [ ] Update wpapp-demo-data.js header comments
- [ ] Document attribute requirements
- [ ] Add example for other plugins

### **Phase 4: wp-agency (Optional)** (1 hour)
- [ ] Apply pattern to wp-agency
- [ ] Verify generic pattern works

**Total Estimated Time**: 3-4 hours

## 🎯 Success Criteria

- [x] Generic dependency checking implemented in shared JS
- [x] wp-customer uses dependency checking
- [x] Buttons disabled appropriately
- [x] Auto re-check after generate success
- [x] Pattern documented for other plugins
- [x] No breaking changes to existing functionality
- [x] Clear visual feedback (disabled state + tooltip)

## 📚 Documentation Needs

### **For Plugin Developers**:
```markdown
## Using Dependency Checking

Add these attributes to buttons with dependencies:

- `data-requires="entity-type"` - Entity that must exist
- `data-check-action="plugin_check_demo_data"` - AJAX action
- `data-check-nonce="<?php echo wp_create_nonce(...); ?>"` - Nonce

Backend must implement AJAX handler that returns:
{
  "success": true,
  "data": {
    "has_data": true/false,
    "count": 123
  }
}
```

## 🔍 Risk Assessment

**Risk Level**: LOW-MEDIUM

**Risks**:
- Additional AJAX calls on page load (mitigated: only for buttons with data-requires)
- Backend handler must exist (mitigated: graceful fallback if missing)
- Multiple plugins calling check simultaneously (mitigated: async handling)

**Mitigation**:
- Buttons without attributes work normally
- Check only happens for configured buttons
- Clear error logging for debugging

## 💡 Future Enhancements

### **Phase 2 (Future)**:
- [ ] Support multiple dependencies: `data-requires="customer,branches"`
- [ ] Support OR dependencies: `data-requires="customer|agency"`
- [ ] Cache check results (avoid redundant AJAX)
- [ ] Visual dependency graph/tree
- [ ] Auto-suggest generate order

### **Phase 3 (Future)**:
- [ ] Generate wizard (step-by-step)
- [ ] Bulk generate (follow dependency order)
- [ ] Dependency validation in backend

---

**Created**: 2025-01-13
**Target**: 3-4 hours for complete implementation
**Dependencies**: TODO-1207 Phase 1.5 ✅
**Blocks**: None (enhancement, not blocker)
**Priority**: HIGH (improves UX significantly)
