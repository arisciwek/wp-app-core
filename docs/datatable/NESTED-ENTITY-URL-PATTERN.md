# Nested Entity URL Pattern Prevention

## Problem Statement

Ketika DataTable berada di dalam tab panel (nested context), tombol view/edit pada nested entity dapat menyebabkan URL collision dengan parent entity panel.

### Scenario:
```
1. User buka customer panel: #customer-123
2. Switch ke tab branches: #customer-123&tab=branches
3. Tab branches menampilkan DataTable dengan list branches
4. User klik view button pada branch-5
5. ❌ URL berubah ke: #customer-5 (SALAH!)
   ✅ Seharusnya: Tidak mengubah panel URL atau buka modal
```

## Root Cause

`wpapp-panel-manager.js` tidak distinguish antara:
- Parent entity button (customer view button) → should open panel
- Nested entity button (branch view button in tab) → should NOT open panel

Kedua button menggunakan class yang sama: `.wpapp-panel-trigger`

## Solution Options

### Option 1: Use Different Class for Nested Entity (RECOMMENDED ✅)

**For Nested Entity Buttons:**
```html
<!-- ❌ JANGAN ini (akan trigger panel manager) -->
<button class="wpapp-panel-trigger" data-id="5" data-entity="branch">
    View Branch
</button>

<!-- ✅ GUNAKAN ini (tidak trigger panel manager) -->
<button class="wpapp-nested-trigger" data-id="5" data-entity="branch">
    View Branch
</button>
```

**Implementation:**
```php
// Dalam format_row() untuk nested entity DataTable
protected function format_row($row) {
    return [
        $row->id,
        $row->name,
        // Actions untuk nested entity
        sprintf(
            '<button class="wpapp-nested-trigger" data-id="%d" data-entity="branch">
                <i class="dashicons dashicons-visibility"></i> View
            </button>',
            $row->id
        )
    ];
}
```

**Handle dengan Modal:**
```javascript
// Dalam branch-specific JS file
$(document).on('click', '.wpapp-nested-trigger[data-entity="branch"]', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const branchId = $(this).data('id');

    // Open modal instead of panel
    BranchModal.open(branchId);

    // DO NOT update URL hash
});
```

---

### Option 2: Check Context in Panel Manager

Modify `wpapp-panel-manager.js` to validate context before opening panel.

**Add Context Check (Line 128-139):**
```javascript
// Panel trigger button click (View button)
$(document).on('click', '.wpapp-panel-trigger', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const entityId = $(this).data('id');
    const entity = $(this).data('entity');

    // ✅ NEW: Check if button is inside a tab (nested context)
    const isNested = $(this).closest('.wpapp-tab-content').length > 0;

    if (isNested) {
        console.log('[WPApp Panel] Nested entity button clicked - ignoring');
        return; // Don't trigger panel for nested entities
    }

    // Verify entity matches current panel entity
    if (entity === self.currentEntity && entityId) {
        self.openPanel(entityId);
    }
});
```

---

### Option 3: Use Data Attribute Flag

Add explicit flag to prevent panel trigger.

**HTML:**
```html
<!-- For parent entity (opens panel) -->
<button class="wpapp-panel-trigger"
        data-id="123"
        data-entity="customer">
    View Customer
</button>

<!-- For nested entity (does NOT open panel) -->
<button class="wpapp-panel-trigger"
        data-id="5"
        data-entity="branch"
        data-nested="true">
    View Branch
</button>
```

**JS Check:**
```javascript
$(document).on('click', '.wpapp-panel-trigger', function(e) {
    e.preventDefault();
    e.stopPropagation();

    // ✅ Check nested flag
    if ($(this).data('nested') === true) {
        console.log('[WPApp Panel] Nested entity - skipping panel trigger');
        return;
    }

    const entityId = $(this).data('id');
    const entity = $(this).data('entity');

    if (entity === self.currentEntity && entityId) {
        self.openPanel(entityId);
    }
});
```

---

## Recommended Implementation (Best Practice)

### Step 1: Define Class Conventions

```
.wpapp-panel-trigger        → Opens right panel (parent entity only)
.wpapp-nested-trigger       → Opens modal (nested entity)
.wpapp-modal-trigger        → Opens modal (any context)
```

### Step 2: Update Core wpapp-panel-manager.js (v1.1.1+)

✅ **Already Fixed in wp-app-core v1.1.1+**

Both button clicks AND row clicks now have nested context check:

```javascript
// ROW CLICK HANDLER (v1.1.1+)
$(document).on('click', '.wpapp-datatable tbody tr', function(e) {
    // Ignore if clicking on action buttons
    if ($(e.target).closest('.wpapp-actions').length > 0) {
        return;
    }

    // ✅ NESTED ENTITY PREVENTION (v1.1.1+)
    const $row = $(this);
    const isNested = $row.closest('.wpapp-tab-content').length > 0;

    if (isNested) {
        console.warn('[WPApp Panel] Nested entity row clicked - ignoring panel trigger');
        return; // Don't trigger panel for nested entities
    }

    const entityId = $row.data('id');
    if (entityId) {
        self.openPanel(entityId);
    }
});

// BUTTON CLICK HANDLER
$(document).on('click', '.wpapp-panel-trigger', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const entityId = $(this).data('id');
    const entity = $(this).data('entity');

    // ✅ Prevent nested entity from triggering panel
    const isNested = $(this).closest('.wpapp-tab-content').length > 0;

    if (isNested) {
        console.warn('[WPApp Panel] Nested entity button detected - use .wpapp-nested-trigger instead');
        return;
    }

    // Verify entity matches current panel entity
    if (entity === self.currentEntity && entityId) {
        self.openPanel(entityId);
    }
});
```

### Step 3: Update DataTable Models

For parent entity (opens panel):
```php
class CustomerDataTableModel extends DataTableModel {
    protected function format_row($row) {
        return [
            $row->id,
            $row->name,
            sprintf(
                '<button class="wpapp-panel-trigger" data-id="%d" data-entity="customer">
                    View
                </button>',
                $row->id
            )
        ];
    }
}
```

For nested entity (opens modal):
```php
class BranchDataTableModel extends DataTableModel {
    protected function format_row($row) {
        return [
            $row->id,
            $row->name,
            sprintf(
                '<button class="wpapp-nested-trigger edit-branch" data-id="%d">
                    Edit
                </button>',
                $row->id
            )
        ];
    }
}
```

### Step 4: Handle Nested Entity Events

```javascript
// In branch-specific JS
$(document).on('click', '.wpapp-nested-trigger[data-entity="branch"]', function(e) {
    e.preventDefault();
    const branchId = $(this).data('id');

    // Option A: Open modal
    BranchModal.open(branchId);

    // Option B: Expand inline
    BranchInlineView.show(branchId);

    // ❌ DO NOT update URL hash
});
```

---

## Testing Checklist

- [ ] Parent entity view button opens panel correctly
- [ ] Parent entity row click opens panel correctly
- [ ] Nested entity button does NOT trigger panel
- [ ] **Nested entity row click does NOT trigger panel** (v1.1.1+)
- [ ] URL remains stable when clicking nested entity button
- [ ] **URL remains stable when clicking nested entity row** (v1.1.1+)
- [ ] Modal/inline view works for nested entity
- [ ] Browser back/forward works correctly
- [ ] No console errors about panel manager
- [ ] Tab state preserved when interacting with nested entity

---

## Migration Guide

If you have existing code using `.wpapp-panel-trigger` for nested entities:

### Before (Wrong):
```php
// ❌ Branch button in customer panel tab
sprintf(
    '<button class="wpapp-panel-trigger" data-id="%d" data-entity="branch">View</button>',
    $row->id
)
```

### After (Correct):
```php
// ✅ Use different class for nested entity
sprintf(
    '<button class="wpapp-nested-trigger edit-branch" data-id="%d">Edit</button>',
    $row->id
)

// Or add nested flag
sprintf(
    '<button class="wpapp-panel-trigger" data-id="%d" data-entity="branch" data-nested="true">View</button>',
    $row->id
)
```

---

## Debug Tips

### Debug Nested Entity Buttons:

```javascript
$(document).on('click', '.wpapp-panel-trigger', function(e) {
    const isNested = $(this).closest('.wpapp-tab-content').length > 0;

    if (isNested) {
        console.group('⚠️ Nested Entity Button Detected');
        console.log('Button:', this);
        console.log('Entity:', $(this).data('entity'));
        console.log('ID:', $(this).data('id'));
        console.log('Parent Tab:', $(this).closest('.wpapp-tab-content').attr('id'));
        console.log('👉 Suggestion: Use .wpapp-nested-trigger class instead');
        console.groupEnd();
    }
});
```

### Debug Nested Entity Row Clicks (v1.1.1+):

```javascript
$(document).on('click', '.wpapp-datatable tbody tr', function(e) {
    const $row = $(this);
    const isNested = $row.closest('.wpapp-tab-content').length > 0;

    if (isNested) {
        console.group('⚠️ Nested Entity Row Clicked');
        console.log('Row:', this);
        console.log('Row ID:', $row.attr('id'));
        console.log('Data ID:', $row.data('id'));
        console.log('Parent Tab:', $row.closest('.wpapp-tab-content').attr('id'));
        console.log('👉 Row clicks disabled for nested entities');
        console.groupEnd();
    }
});
```

---

## Summary

✅ **DO:**
- Use `.wpapp-panel-trigger` for parent entity buttons
- Use `.wpapp-nested-trigger` for nested entity buttons
- Handle nested entities with modals or inline views
- Keep URL stable for nested entity interactions
- **Let wp-app-core v1.1.1+ handle nested row clicks automatically**

❌ **DON'T:**
- Use same class for both parent and nested entities
- Update URL hash for nested entity actions
- Assume panel manager knows about nested context
- **Click rows in nested DataTables expecting panel to open** (disabled in v1.1.1+)

---

## Changelog

**v1.1.1 (2025-01-02):**
- ✅ Fixed: Row clicks in nested DataTables (employee/branch tabs)
- ✅ Added: Nested entity prevention for row click handler
- ✅ Impact: Clicking nested entity row no longer changes URL

**v1.1.0 (2025-01-02):**
- ✅ Fixed: Button clicks in nested DataTables
- ✅ Added: Nested entity prevention for button click handler
- ✅ Added: Support for data-nested="true" flag

---

**Last Updated:** 2025-01-02
**Version:** 1.1.1
**Related Files:**
- `/assets/js/datatable/wpapp-panel-manager.js` (v1.1.1)
- `/assets/js/datatable/wpapp-tab-manager.js`
