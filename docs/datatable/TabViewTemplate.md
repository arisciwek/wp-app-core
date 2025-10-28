# TabViewTemplate - Tab Content Container System

**Version**: 1.0.0
**Created**: 2025-10-27
**Author**: arisciwek

## Overview

`TabViewTemplate` provides a **generic container system** for tab content across all plugins. It follows the proven **hook-based pattern** similar to `wpapp_page_header_right` (see TODO-3078).

### Key Concept

```
┌────────────────────────────────────────────────────────┐
│ wp-app-core: TabViewTemplate (CONTAINER)               │
│                                                         │
│ <div class="wpapp-tab-view-container">  ← GLOBAL      │
│                                                         │
│   <?php do_action('wpapp_tab_view_content', ...); ?>  │
│         │                                               │
│         └─> HOOK POINT                                 │
│                                                         │
│   ┌──────────────────────────────────────────────┐    │
│   │ wp-agency: Content (LOCAL SCOPE)             │    │
│   │                                               │    │
│   │ <div class="agency-tab-section">  ← LOCAL    │    │
│   │   <div class="agency-tab-grid">              │    │
│   │     <div class="agency-tab-item">            │    │
│   │       ...                                    │    │
│   │     </div>                                    │    │
│   │   </div>                                      │    │
│   │ </div>                                        │    │
│   └──────────────────────────────────────────────┘    │
│                                                         │
│ </div>                                                  │
└────────────────────────────────────────────────────────┘
```

---

## Architecture Principles

### 1. Scope Separation

| Scope | Prefix | Responsibility | Provider |
|-------|--------|----------------|----------|
| **Global** | `wpapp-*` | Container wrapper | wp-app-core |
| **Local** | `plugin-*` | Content structure & styling | Plugin (agency-*, customer-*, etc) |

### 2. Hook-Based Content Injection

- ✅ **Container**: Provided by wp-app-core
- ✅ **Content**: Injected via hook by plugins
- ✅ **Flexibility**: Each plugin defines own structure

### 3. No Mixed Scopes

```php
// ❌ WRONG: Mixed scopes
<div class="wpapp-tab-view-container">
    <div class="wpapp-tab-section">  <!-- Global in local content -->
        ...
    </div>
</div>

// ✅ CORRECT: Strict separation
<div class="wpapp-tab-view-container">  <!-- Global container only -->
    <div class="agency-tab-section">    <!-- Local content -->
        ...
    </div>
</div>
```

---

## File Reference

**Template Class:**
- `src/Views/DataTable/Templates/TabViewTemplate.php`

**CSS Styles:**
- `assets/css/datatable/wpapp-datatable.css` (container styles)
- Plugin-specific CSS for content (e.g., `wp-agency/assets/css/agency-tabs.css`)

**Related Documentation:**
- `docs/datatable/README.md` - Main DataTable system
- `TODO/TODO-1185-scope-separation-phase2.md` - Phase 2 implementation

---

## Usage Guide

### Step 1: Use Template in Tab File

**File**: `wp-agency/src/Views/agency/tabs/info.php`

```php
<?php
/**
 * Agency Info Tab
 */

use WPAppCore\Views\DataTable\Templates\TabViewTemplate;

defined('ABSPATH') || exit;

// Get data (passed from AJAX response or controller)
$agency = $data['agency'] ?? null;

if (!$agency) {
    echo '<p>' . __('Data not available', 'wp-agency') . '</p>';
    return;
}

// Render using TabViewTemplate
TabViewTemplate::render('agency', 'info', ['agency' => $agency]);
```

### Step 2: Register Hook in Controller

**File**: `wp-agency/src/Controllers/Agency/AgencyDashboardController.php`

```php
<?php
namespace WPAgency\Controllers\Agency;

class AgencyDashboardController {

    public function __construct() {
        // Register hook for tab content
        add_action('wpapp_tab_view_content', [$this, 'render_tab_content'], 10, 3);
    }

    /**
     * Render tab content via hook
     *
     * @param string $entity Entity identifier
     * @param string $tab_id Tab identifier
     * @param array  $data   Tab data
     */
    public function render_tab_content($entity, $tab_id, $data) {
        // Only handle agency entity
        if ($entity !== 'agency') {
            return;
        }

        // Route to specific tab renderer
        switch ($tab_id) {
            case 'info':
                $this->render_info_tab($data);
                break;

            case 'details':
                $this->render_details_tab($data);
                break;

            case 'divisions':
                $this->render_divisions_tab($data);
                break;

            default:
                // Tab not handled by this plugin
                break;
        }
    }

    /**
     * Render info tab content
     *
     * @param array $data
     */
    private function render_info_tab($data) {
        $agency = $data['agency'] ?? null;

        if (!$agency) {
            echo '<p>' . __('Agency data not available', 'wp-agency') . '</p>';
            return;
        }

        ?>
        <!-- LOCAL SCOPE: Use agency-* prefix -->
        <div class="agency-tab-section">
            <h3><?php _e('Informasi Umum', 'wp-agency'); ?></h3>

            <div class="agency-tab-grid">
                <div class="agency-tab-item">
                    <span class="agency-tab-label"><?php _e('Kode', 'wp-agency'); ?>:</span>
                    <span class="agency-tab-value"><?php echo esc_html($agency->code); ?></span>
                </div>

                <div class="agency-tab-item">
                    <span class="agency-tab-label"><?php _e('Nama', 'wp-agency'); ?>:</span>
                    <span class="agency-tab-value"><?php echo esc_html($agency->name); ?></span>
                </div>

                <div class="agency-tab-item">
                    <span class="agency-tab-label"><?php _e('Status', 'wp-agency'); ?>:</span>
                    <span class="agency-tab-value">
                        <?php
                        $badge_class = $agency->status === 'active' ? 'success' : 'secondary';
                        ?>
                        <span class="wpapp-badge wpapp-badge-<?php echo esc_attr($badge_class); ?>">
                            <?php echo esc_html(ucfirst($agency->status)); ?>
                        </span>
                    </span>
                </div>
            </div>
        </div>

        <div class="agency-tab-section">
            <h3><?php _e('Lokasi', 'wp-agency'); ?></h3>

            <div class="agency-tab-grid">
                <div class="agency-tab-item">
                    <span class="agency-tab-label"><?php _e('Provinsi', 'wp-agency'); ?>:</span>
                    <span class="agency-tab-value"><?php echo esc_html($agency->provinsi_name ?? '-'); ?></span>
                </div>

                <div class="agency-tab-item">
                    <span class="agency-tab-label"><?php _e('Kabupaten/Kota', 'wp-agency'); ?>:</span>
                    <span class="agency-tab-value"><?php echo esc_html($agency->regency_name ?? '-'); ?></span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render details tab content
     *
     * @param array $data
     */
    private function render_details_tab($data) {
        $agency = $data['agency'] ?? null;

        if (!$agency) {
            echo '<p>' . __('Agency data not available', 'wp-agency') . '</p>';
            return;
        }

        ?>
        <!-- LOCAL SCOPE: Use agency-* prefix -->
        <div class="agency-tab-section">
            <h3><?php _e('Complete Details', 'wp-agency'); ?></h3>

            <div class="agency-tab-grid agency-tab-grid-cols-2">
                <!-- Grid with 2 columns -->
                <div class="agency-tab-item">
                    <span class="agency-tab-label"><?php _e('Registration Date', 'wp-agency'); ?>:</span>
                    <span class="agency-tab-value">
                        <?php echo date_i18n(get_option('date_format'), strtotime($agency->created_at)); ?>
                    </span>
                </div>

                <!-- More items... -->
            </div>
        </div>
        <?php
    }
}
```

### Step 3: Add CSS Styling

**File**: `wp-agency/assets/css/agency-tabs.css`

```css
/**
 * Agency Tab Content Styles (LOCAL SCOPE)
 *
 * All classes use agency-* prefix
 */

/* Section Container */
.agency-tab-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.agency-tab-section:last-child {
    margin-bottom: 0;
}

.agency-tab-section h3 {
    margin: 0 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #0073aa;
    font-size: 16px;
    font-weight: 600;
    color: #0073aa;
}

/* Grid Layout */
.agency-tab-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.agency-tab-grid-cols-2 {
    grid-template-columns: repeat(2, 1fr);
}

/* Item (Field) */
.agency-tab-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.agency-tab-label {
    font-weight: 600;
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.agency-tab-value {
    font-size: 14px;
    color: #333;
    padding: 5px 0;
}

/* Responsive */
@media screen and (max-width: 768px) {
    .agency-tab-grid,
    .agency-tab-grid-cols-2 {
        grid-template-columns: 1fr;
    }

    .agency-tab-section {
        padding: 15px;
    }
}
```

---

## Hook Reference

### `wpapp_tab_view_content`

**Type**: Action
**Parameters**: `($entity, $tab_id, $data)`

Fired inside `TabViewTemplate::render()` to allow plugins to inject content.

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$entity` | string | Entity identifier (agency, customer, company) |
| `$tab_id` | string | Tab identifier (info, details, divisions) |
| `$data` | array | Optional data for rendering (from AJAX response) |

**Usage:**

```php
add_action('wpapp_tab_view_content', function($entity, $tab_id, $data) {
    // Check if this plugin should handle this entity/tab
    if ($entity !== 'customer' || $tab_id !== 'membership') {
        return;
    }

    // Render content with LOCAL scope classes (customer-*)
    ?>
    <div class="customer-tab-section">
        <h3>Membership Information</h3>
        <div class="customer-tab-grid">
            ...
        </div>
    </div>
    <?php
}, 10, 3);
```

---

## Class Naming Convention

### Global Container (wp-app-core)

```css
.wpapp-tab-view-container { }      /* Main container */
.wpapp-tab-view-empty { }          /* Empty state modifier */
.wpapp-empty-state { }             /* Empty state content */
```

### Local Content (Plugins)

**Pattern**: `{plugin}-tab-{component}`

**wp-agency:**
```css
.agency-tab-section { }            /* Section wrapper */
.agency-tab-grid { }               /* Grid layout */
.agency-tab-item { }               /* Field item */
.agency-tab-label { }              /* Field label */
.agency-tab-value { }              /* Field value */
```

**wp-customer:**
```css
.customer-tab-section { }
.customer-tab-grid { }
.customer-tab-item { }
.customer-tab-label { }
.customer-tab-value { }
```

**wp-company:**
```css
.company-tab-section { }
.company-tab-grid { }
.company-tab-item { }
.company-tab-label { }
.company-tab-value { }
```

---

## Pattern Comparison

### Header Buttons Pattern (TODO-3078)

```php
// wp-app-core provides container + hook
<div class="wpapp-header-buttons">
    <?php do_action('wpapp_page_header_right', $config, $entity); ?>
</div>

// wp-agency hooks content
add_action('wpapp_page_header_right', function($config, $entity) {
    if ($entity !== 'agency') return;
    ?>
    <button class="button agency-print-btn">Print</button>
    <?php
});
```

### Tab View Pattern (THIS)

```php
// wp-app-core provides container + hook
<div class="wpapp-tab-view-container">
    <?php do_action('wpapp_tab_view_content', $entity, $tab_id, $data); ?>
</div>

// wp-agency hooks content
add_action('wpapp_tab_view_content', function($entity, $tab_id, $data) {
    if ($entity !== 'agency' || $tab_id !== 'info') return;
    ?>
    <div class="agency-tab-section">...</div>
    <?php
});
```

**Same Pattern, Different Context!** ✅

---

## Benefits

### 1. No Code Duplication

```
Before (without TabViewTemplate):
- wp-agency/tabs/info.php: 102 lines (full HTML structure)
- wp-customer/tabs/info.php: 98 lines (duplicate structure)
- wp-company/tabs/info.php: 105 lines (duplicate structure)

After (with TabViewTemplate):
- wp-app-core: TabViewTemplate.php (shared container)
- wp-agency: Hook content only (data rendering)
- wp-customer: Hook content only (data rendering)
- wp-company: Hook content only (data rendering)
```

### 2. Consistent UX

All plugins use same container system → Consistent look & feel

### 3. Easy Maintenance

- Fix container bug once in wp-app-core → All plugins benefit
- Update plugin content → No affect on other plugins

### 4. Flexible Styling

Each plugin can style content independently:
- wp-agency: Blue theme
- wp-customer: Green theme
- wp-company: Orange theme

### 5. Testable

```php
// Unit test: Check if hook is registered
$this->assertTrue(has_action('wpapp_tab_view_content'));

// Integration test: Check content rendered
ob_start();
do_action('wpapp_tab_view_content', 'agency', 'info', $data);
$output = ob_get_clean();
$this->assertStringContainsString('agency-tab-section', $output);
```

---

## Migration Guide

### From Old Pattern to New Pattern

**Old (Direct HTML in tab file):**

```php
// wp-agency/src/Views/agency/tabs/info.php (OLD)
<div class="agency-info-container">
    <div class="agency-info-section">
        <h3>Informasi Umum</h3>
        <div class="agency-info-grid">
            ...
        </div>
    </div>
</div>
```

**New (TabViewTemplate + Hook):**

```php
// wp-agency/src/Views/agency/tabs/info.php (NEW)
<?php
use WPAppCore\Views\DataTable\Templates\TabViewTemplate;

TabViewTemplate::render('agency', 'info', ['agency' => $agency]);
```

```php
// wp-agency/src/Controllers/Agency/AgencyDashboardController.php (NEW)
add_action('wpapp_tab_view_content', [$this, 'render_tab_content'], 10, 3);

public function render_tab_content($entity, $tab_id, $data) {
    if ($entity !== 'agency' || $tab_id !== 'info') return;

    $agency = $data['agency'] ?? null;
    ?>
    <div class="agency-tab-section">  <!-- Same structure, but via hook -->
        <h3>Informasi Umum</h3>
        <div class="agency-tab-grid">
            ...
        </div>
    </div>
    <?php
}
```

**Changes:**
1. ✅ Tab file: Uses `TabViewTemplate::render()`
2. ✅ Controller: Registers hook `wpapp_tab_view_content`
3. ✅ Content: Moved to controller method
4. ✅ Classes: Same (agency-* prefix) - no CSS changes needed

---

## Troubleshooting

### Issue: Tab content not showing

**Symptom**: Blank tab, no content

**Causes:**
1. Hook not registered
2. Entity/tab_id mismatch
3. Early return in hook callback

**Solution:**

```php
// Check if hook registered
if (!has_action('wpapp_tab_view_content')) {
    error_log('No callbacks registered for wpapp_tab_view_content');
}

// Add debug logging
add_action('wpapp_tab_view_content', function($entity, $tab_id, $data) {
    error_log("Hook fired: entity={$entity}, tab_id={$tab_id}");

    if ($entity !== 'agency') {
        error_log("Entity mismatch, returning");
        return;
    }

    // ... render content
}, 10, 3);
```

### Issue: Wrong styling applied

**Symptom**: Content has global wpapp-* styling

**Cause**: Using wpapp-* classes in content (should use plugin-* classes)

**Solution:**

```css
/* ❌ WRONG: Global classes in content */
.wpapp-tab-section { }
.wpapp-tab-grid { }

/* ✅ CORRECT: Local classes in content */
.agency-tab-section { }
.agency-tab-grid { }
```

### Issue: Content from multiple plugins showing

**Symptom**: Tab shows content from multiple plugins

**Cause**: Hook callbacks not checking entity/tab_id

**Solution:**

```php
add_action('wpapp_tab_view_content', function($entity, $tab_id, $data) {
    // ✅ ALWAYS check entity and tab_id
    if ($entity !== 'agency' || $tab_id !== 'info') {
        return;  // Exit if not our context
    }

    // Render content...
}, 10, 3);
```

---

## Related Documentation

- [README.md](README.md) - Main DataTable system
- [DashboardTemplate.md](DashboardTemplate.md) - Dashboard container
- [TabSystemTemplate.md](TabSystemTemplate.md) - Tab navigation
- [IMPLEMENTATION.md](core/IMPLEMENTATION.md) - Core implementation

---

## Changelog

### Version 1.0.0 (2025-10-27)

- ✅ Initial creation
- ✅ Hook-based content injection (`wpapp_tab_view_content`)
- ✅ Global container (wpapp-*) with local content (plugin-*)
- ✅ Inspired by `wpapp_page_header_right` pattern (TODO-3078)
- ✅ Comprehensive documentation with examples

---

**End of Documentation**
