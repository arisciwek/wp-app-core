# TODO-1185: Remove Inline CSS & JavaScript from PHP Files

**Status:** ✅ COMPLETED
**Date Created:** 2025-10-27
**Date Completed:** 2025-10-27
**Priority:** HIGH
**Category:** Code Quality, Separation of Concerns

---

## 📋 Description

Remove ALL inline `<style>` and `<script>` tags from PHP files.

**Goal:** 100% separation of concerns
- PHP: HTML structure only
- CSS: External CSS files
- JS: External JS files

---

## 🎯 Problems Found

### Files with Inline Scripts:

**1. divisions.php (81 lines)**
- ❌ Inline `style="..."` attributes (3 places)
- ❌ Inline `<script>` tag with jQuery ready (34 lines)
- ❌ AJAX logic hardcoded in template

**2. employees.php (81 lines)**
- ❌ Inline `style="..."` attributes (3 places)
- ❌ Inline `<script>` tag with jQuery ready (34 lines)
- ❌ AJAX logic hardcoded in template

---

## ✅ Solutions Implemented

### A. Remove Inline Styles

**BEFORE:**
```php
<div class="wpapp-loading" style="text-align: center; padding: 20px; color: #666;">
<div class="wpapp-divisions-content" style="display: none;">
<div class="wpapp-error" style="display: none;">
```

**AFTER:**
```php
<div class="wpapp-tab-loading">
<div class="wpapp-divisions-content wpapp-tab-loaded-content">
<div class="wpapp-tab-error">
```

**CSS Added:** `wpapp-datatable.css:440-481`

---

### B. Remove Inline Scripts - Use Event-Driven

**BEFORE (❌ Bad):**
```php
<script>
jQuery(document).ready(function($) {
    // 30+ lines of AJAX code inline
    $.ajax({ ... });
});
</script>
```

**AFTER (✅ Good):**

**1. Add Data Attributes (Configuration):**
```php
<div class="wpapp-tab-content wpapp-divisions-tab wpapp-tab-autoload"
     data-agency-id="<?php echo esc_attr($agency_id); ?>"
     data-load-action="load_divisions_tab"
     data-content-target=".wpapp-divisions-content"
     data-error-message="<?php echo esc_attr(__('Failed to load', 'wp-agency')); ?>">
```

**2. External JS Handles It:** `wpapp-tab-manager.js:200-264`

```javascript
autoLoadTabContent($tab) {
    // Read configuration from data attributes
    const agencyId = $tab.data('agency-id');
    const loadAction = $tab.data('load-action');

    // Make AJAX request
    $.ajax({
        action: loadAction,
        agency_id: agencyId
    });
}
```

---

## 📁 Files Modified

### wp-agency:
1. `src/Views/agency/tabs/divisions.php` (81 → 57 lines, -30%)
2. `src/Views/agency/tabs/employees.php` (81 → 57 lines, -30%)

### wp-app-core:
3. `assets/css/datatable/wpapp-datatable.css` (+42 lines for tab states)
4. `assets/js/datatable/wpapp-tab-manager.js` (+65 lines for auto-load)

---

## 🎨 CSS Classes Added

```css
/* Tab Loading State */
.wpapp-tab-loading {
    display: block;
    text-align: center;
    padding: 40px 20px;
}

/* Tab Loaded Content */
.wpapp-tab-loaded-content {
    display: none;
}

.wpapp-tab-loaded-content.loaded {
    display: block;
}

/* Tab Error State */
.wpapp-tab-error {
    display: none;
    background: #f8d7da;
    border: 1px solid #f5c6cb;
}

.wpapp-tab-error.visible {
    display: block;
}
```

---

## 🔄 New Pattern: Event-Driven Tab Loading

**Flow:**
1. User clicks tab
2. `wpapp-tab-manager.js` detects `wpapp-tab-autoload` class
3. Reads configuration from `data-*` attributes
4. Makes AJAX request
5. Loads content into target element
6. Marks tab as `loaded` (cached)
7. Next click: instant display (no AJAX)

**Benefits:**
- ✅ No inline scripts
- ✅ Automatic caching
- ✅ Reusable pattern
- ✅ Easy to debug
- ✅ Clean HTML

---

## 📊 Code Metrics

**PHP Files Cleaned:**
- divisions.php: 81 → 57 lines (-30%)
- employees.php: 81 → 57 lines (-30%)
- **Total reduction:** 48 lines

**Moved to Proper Files:**
- CSS: +42 lines (wpapp-datatable.css)
- JS: +65 lines (wpapp-tab-manager.js)

**Net Result:**
- Better organized ✅
- Reusable code ✅
- Maintainable ✅

---

## 🧪 Test Results

- ✅ Divisions tab loads via AJAX
- ✅ Employees tab loads via AJAX
- ✅ Loading states work
- ✅ Error handling works
- ✅ Caching works (second click instant)
- ✅ No inline script execution
- ✅ View source: Clean HTML

---

## 🔗 Related

- TODO-1185: Scope separation Phase 1
