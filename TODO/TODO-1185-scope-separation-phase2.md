# TODO-005: Scope Separation Phase 2 - Tabs & Components

**Status:** ⏳ PENDING
**Date Created:** 2025-10-27
**Date Started:** -
**Date Completed:** -
**Priority:** HIGH
**Category:** Architecture, Refactoring

---

## 📋 Description

Continue scope separation untuk komponen yang tersisa:
- Tab templates
- DataTable structure
- Filter components
- Modal forms

**Follow Pattern dari Phase 1:**
- `wpapp-*` → Global scope (wp-app-core)
- `agency-*` → Local scope (wp-agency)

---

## 🎯 Current Problems

### A. Tab Templates

**Files Found with `wpapp-*` classes:**
```
wp-agency/src/Views/agency/tabs/
├── info.php       - Uses wpapp-info-*, wpapp-badge
├── divisions.php  - Uses wpapp-tab-*, wpapp-tab-loading
└── employees.php  - Uses wpapp-tab-*, wpapp-tab-error
```

**Analysis:**

**Option 1: Tabs Stay (HYBRID) - RECOMMENDED** ✅
```
Reason: Tab content is agency-specific
Decision: Tabs CAN use wpapp-* classes (reusable structure)
Action: Keep as-is (wpapp-* for structure, agency-* for theme)
```

**Option 2: Pure Refactor (EXTREME)**
```
Reason: 100% separation
Decision: Move all templates to wp-app-core
Action: wp-agency pass data only via hooks
```

**DECISION NEEDED:** Which option? (Recommend Option 1)

---

### B. DataTable Structure

**Current:**
```php
// wp-agency/src/Views/DataTable/Templates/datatable.php
<table id="agency-list-table" class="wpapp-datatable display">
```

**Question:** Is `wpapp-datatable` correct here?

**Analysis:**
- `wpapp-datatable` = Base DataTable styling (global)
- Content inside = Agency-specific (local)

**Answer:** ✅ **CORRECT** - This is proper hybrid usage
- `wpapp-datatable` provides base structure
- `agency-*` provides content styling

**Action:** Keep as-is ✅

---

### C. Filter Components

**Current:**
```php
// wp-agency renders filter directly
public function render_filters($config, $entity) {
    include 'partials/status-filter.php';
}
```

**Status Filter Partial:**
```php
<select id="status-filter" class="agency-status-filter">
```

**Analysis:** ✅ **CORRECT** - Uses `agency-*` prefix

**Action:** Keep as-is ✅

---

### D. Header Section

**Current:**
```php
// AgencyDashboardController.php:179
public function render_header_title($config, $entity) {
    ?>
    <h1 class="agency-page-title">Daftar Disnaker</h1>
    <p class="agency-page-subtitle">Kelola data dinas tenaga kerja</p>
    <?php
}
```

**Analysis:** ✅ **CORRECT** - Uses `agency-*` prefix

**Action:** Keep as-is ✅

---

## 📋 Tasks Breakdown

### Task 1: Audit All `wpapp-*` Usage in wp-agency ⏳

**Command:**
```bash
grep -r "wpapp-" wp-agency/src --include="*.php" | grep class=
```

**Create Matrix:**
| File | Class | Scope | Action |
|------|-------|-------|--------|
| info.php | wpapp-info-container | Structure | KEEP/CHANGE? |
| info.php | wpapp-badge | Component | KEEP/CHANGE? |
| divisions.php | wpapp-tab-content | Structure | KEEP/CHANGE? |
| employees.php | wpapp-tab-loading | State | KEEP/CHANGE? |
| datatable.php | wpapp-datatable | Base | KEEP ✅ |

---

### Task 2: Decide on Tab Template Strategy ⏳

**Option A: Hybrid (Tabs use wpapp-* structure)**

**Pros:**
- ✅ Quick (no refactor needed)
- ✅ Reusable structure
- ✅ Pragmatic approach

**Cons:**
- ⚠️ Not "pure" separation
- ⚠️ Mixed prefixes in file

**Pattern:**
```php
// info.php - Mix of global structure + local content
<div class="wpapp-info-container">           <!-- Global structure -->
    <div class="wpapp-info-section">         <!-- Global structure -->
        <span class="wpapp-info-label">:</span>  <!-- Global structure -->
        <span class="wpapp-info-value agency-accent"> <!-- Local theme -->
            <?php echo $agency->name; ?>     <!-- Local data -->
        </span>
    </div>
</div>
```

---

**Option B: Pure Separation (All tabs to wp-app-core)**

**Pros:**
- ✅ 100% separation
- ✅ True architecture purity

**Cons:**
- ❌ Massive refactoring
- ❌ Complexity increase
- ❌ Over-engineering?

**Pattern:**
```php
// wp-app-core: Provide template
class InfoTabTemplate {
    public static function render($data) {
        ?>
        <div class="wpapp-info-container">
            <?php do_action('wpapp_info_content', $data); ?>
        </div>
        <?php
    }
}

// wp-agency: Hook into template
add_action('wpapp_info_content', function($data) {
    ?>
    <div class="agency-info-data">
        <!-- Agency-specific rendering -->
    </div>
    <?php
});
```

**RECOMMENDATION:** Start with Option A (Hybrid), refactor to Option B if needed later.

---

### Task 3: Create Guidelines Document ⏳

**Create:** `NAMING-CONVENTION.md`

**Content:**
```markdown
# Naming Convention Guidelines

## Prefix Rules

### Global Scope (wp-app-core)
- Prefix: wpapp-*
- Usage: Reusable structure, layout, components
- Examples:
  - wpapp-statistics-container (container)
  - wpapp-datatable (base table)
  - wpapp-tab-content (tab structure)
  - wpapp-badge (generic badge)

### Local Scope (wp-agency)
- Prefix: agency-*
- Usage: Agency-specific styling, content, themes
- Examples:
  - agency-stat-card (custom card)
  - agency-theme-blue (theme color)
  - agency-page-title (local typography)

## Hybrid Pattern (Acceptable)

When to mix:
- HTML structure uses wpapp-* (reusable)
- Content/theme uses agency-* (specific)

Example:
<div class="wpapp-tab-content">        <!-- Global structure -->
    <span class="agency-accent">       <!-- Local theme -->
        Agency Name
    </span>
</div>
```

---

### Task 4: Update Tab Templates (If needed) ⏳

**Only if Option B chosen:**

**Files to Update:**
- wp-agency/src/Views/agency/tabs/info.php
- wp-agency/src/Views/agency/tabs/divisions.php
- wp-agency/src/Views/agency/tabs/employees.php

**Changes:**
- Move structure to wp-app-core templates
- wp-agency hooks into templates
- All agency-specific uses agency-* prefix

---

### Task 5: CSS Audit & Cleanup ⏳

**Check for:**
- Unused classes
- Duplicate styles
- Mixed scope styles

**Files to Check:**
```
wp-agency/assets/css/agency/
├── agency-style.css  - Should ONLY have agency-* classes
└── (other css files) - Audit prefix usage
```

**Create Report:**
```
Class Name          | Scope  | Used? | Action
--------------------|--------|-------|----------
agency-stat-card    | Local  | Yes   | Keep
wpapp-datatable     | Global | Yes   | Document
old-mixed-class     | Mixed  | No    | Remove
```

---

### Task 6: Testing Strategy ⏳

**Test Cases:**

**1. Visual Testing**
- [ ] All tabs display correctly
- [ ] Styling intact after refactor
- [ ] No broken layouts
- [ ] Responsive works

**2. Functional Testing**
- [ ] AJAX loading works
- [ ] Error states work
- [ ] Tab caching works
- [ ] DataTable works

**3. Code Quality**
- [ ] No mixed scopes (unless documented as hybrid)
- [ ] All classes follow naming convention
- [ ] CSS organized by scope
- [ ] No inline styles

**4. Performance Testing**
- [ ] No additional load time
- [ ] No flicker
- [ ] Smooth transitions

---

## 📊 Estimated Effort

**Option A (Hybrid - Recommended):**
- Audit: 2 hours
- Documentation: 1 hour
- Minor fixes: 1 hour
- Testing: 1 hour
- **Total: 5 hours** ✅

**Option B (Pure Separation):**
- Audit: 2 hours
- Template refactoring: 8 hours
- Hook implementation: 4 hours
- CSS reorganization: 3 hours
- Testing: 2 hours
- **Total: 19 hours** ⚠️

---

## 🎯 Success Criteria

### Must Have:
- [ ] Clear documentation of naming convention
- [ ] No unintended mixed scopes
- [ ] All tests passing
- [ ] No visual regressions

### Nice to Have:
- [ ] 100% pure separation (Option B)
- [ ] Automated scope validation script
- [ ] Guidelines document published

---

## 📝 Decision Log

**To Be Filled:**

**Date:** -
**Decision:** Option A / Option B?
**Reasoning:** -
**Approved By:** -

---

## 🔗 Related

- TODO-004: Scope separation Phase 1 (COMPLETED)
- TODO-006: Automated scope validation (FUTURE)
