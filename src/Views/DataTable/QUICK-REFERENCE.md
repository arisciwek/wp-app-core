# DataTable Panel System - Quick Reference

**Quick lookup for common tasks**

## File Reference

**Template Classes:**
- `src/Views/DataTable/Templates/DashboardTemplate.php` - Main dashboard container
- `src/Views/DataTable/Templates/PanelLayoutTemplate.php` - Panel layout renderer
- `src/Views/DataTable/Templates/TabSystemTemplate.php` - Tab system renderer
- `src/Views/DataTable/Templates/StatsBoxTemplate.php` - Stats box renderer

**Related Documentation:**
- `src/Views/DataTable/README.md` - Full panel system documentation
- `docs/datatable/ARCHITECTURE.md` - System architecture overview

---

## Basic Setup

### Minimal Dashboard
```php
use WPAppCore\Views\DataTable\Templates\DashboardTemplate;

DashboardTemplate::render([
    'entity' => 'customer',
    'title' => 'Customers'
]);
```

### Full-Featured Dashboard
```php
DashboardTemplate::render([
    'entity' => 'customer',
    'title' => 'Customers',
    'ajax_action' => 'get_customer_details',
    'has_stats' => true,
    'has_tabs' => true
]);
```

---

## Hook Into System

### Render DataTable HTML
```php
add_action('wpapp_left_panel_content', function($config) {
    if ($config['entity'] !== 'customer') return;
    include __DIR__ . '/datatable.php';
}, 10, 1);
```

### Register Statistics
```php
add_filter('wpapp_datatable_stats', function($stats, $entity) {
    if ($entity !== 'customer') return $stats;

    return [
        [
            'id' => 'total-customers',
            'label' => 'Total Customers',
            'icon' => 'dashicons-groups',
            'class' => 'primary'
        ]
    ];
}, 10, 2);
```

### Register Tabs
```php
add_filter('wpapp_datatable_tabs', function($tabs, $entity) {
    if ($entity !== 'customer') return $tabs;

    return [
        'details' => [
            'title' => 'Details',
            'template' => __DIR__ . '/tabs/details.php',
            'priority' => 10
        ]
    ];
}, 10, 2);
```

---

## AJAX Handlers

### Load Panel Data
```php
add_action('wp_ajax_get_customer_details', function() {
    check_ajax_referer('wpapp_panel_nonce', 'nonce');

    $id = intval($_POST['id']);
    $customer = Customer::find($id);

    wp_send_json_success([
        'title' => $customer->name,
        'tabs' => [
            'details' => render_template('tabs/details.php'),
            'membership' => render_template('tabs/membership.php')
        ]
    ]);
});
```

### Load Statistics
```php
add_action('wp_ajax_get_customer_stats', function() {
    wp_send_json_success([
        'total-customers' => Customer::count(),
        'active-customers' => Customer::active()->count()
    ]);
});
```

---

## JavaScript Events

### Panel Events
```javascript
// Panel opened
jQuery(document).on('wpapp:panel-data-loaded', function(e, data) {
    console.log('Loaded:', data.entity, data.id);
});

// Panel closing
jQuery(document).on('wpapp:panel-closing', function(e, data) {
    if (hasUnsavedChanges) e.preventDefault();
});
```

### Tab Events
```javascript
// Tab switched
jQuery(document).on('wpapp:tab-switched', function(e, data) {
    console.log('Active tab:', data.tabId);
});
```

---

## Public API

### Panel Manager
```javascript
// Open panel
wpAppPanelManager.open(123);

// Close panel
wpAppPanelManager.close();

// Refresh
wpAppPanelManager.refresh();
```

### Tab Manager
```javascript
// Switch tab
wpAppTabManager.goTo('details');

// Get current
const current = wpAppTabManager.getCurrent();
```

---

## Hash Navigation

```
# Open panel
URL: #customer-123

# Open panel + specific tab
URL: #customer-123&tab=membership
```

---

## CSS Classes

### Layout
```css
.wpapp-datatable-layout         /* Main container */
.wpapp-left-panel              /* Left panel (DataTable) */
.wpapp-right-panel             /* Right panel (details) */
.with-right-panel              /* Layout when right open */
```

### Panels
```css
.wpapp-panel-header            /* Panel header */
.wpapp-panel-content           /* Panel content */
.wpapp-panel-close             /* Close button */
```

### Statistics
```css
.wpapp-stats-container         /* Stats grid */
.wpapp-stat-box                /* Single stat card */
.wpapp-stat-icon               /* Icon container */
.wpapp-stat-number             /* Number display */
.wpapp-stat-label              /* Label text */
```

### Tabs
```css
.wpapp-tab-wrapper             /* Tab navigation */
.wpapp-tab-content             /* Tab content container */
.wpapp-tab-content.active      /* Active tab */
```

### State Classes
```css
.hidden                        /* Hidden element */
.visible                       /* Visible element */
.wpapp-loading                 /* Loading state */
```

---

## Color Variants

Stats box colors:

```php
'class' => 'primary'   // Blue
'class' => 'success'   // Green
'class' => 'warning'   // Yellow
'class' => 'danger'    // Red
'class' => 'info'      // Cyan
```

---

## DataTable Row Attributes

```php
<tr data-id="<?php echo $customer->id; ?>">
    <td><?php echo $customer->name; ?></td>
</tr>
```

**Required:** `data-id` attribute for panel to open on click.

---

## Conditional Asset Loading

### Allow Specific Page
```php
add_filter('wpapp_datatable_allowed_hooks', function($hooks) {
    $hooks[] = 'toplevel_page_my-page';
    return $hooks;
});
```

### Force Enqueue
```php
\WPAppCore\Controllers\DataTable\DataTableAssetsController::force_enqueue();
```

---

## Common Patterns

### Add Button to Header
```php
add_action('wpapp_left_panel_header', function($config) {
    if ($config['entity'] !== 'customer') return;
    ?>
    <button class="page-title-action">Add New</button>
    <?php
});
```

### Custom Panel Title
```php
add_action('wpapp_right_panel_header', function($config) {
    if ($config['entity'] !== 'customer') return;
    ?>
    <h2 class="wpapp-panel-title">
        <span class="dashicons dashicons-businessman"></span>
        <span class="wpapp-entity-name"></span>
    </h2>
    <button type="button" class="wpapp-panel-close">×</button>
    <?php
});
```

### Lazy Load Tab
```javascript
jQuery(document).on('wpapp:tab-switched', function(e, data) {
    const $tab = jQuery('#' + data.tabId);

    if ($tab.data('loaded')) return;

    jQuery.ajax({
        url: wpAppConfig.ajaxUrl,
        data: {
            action: 'load_' + data.tabId,
            id: data.id
        },
        success: function(response) {
            $tab.html(response.data.html);
            $tab.data('loaded', true);
        }
    });
});
```

---

## Troubleshooting

### Assets Not Loading
```php
// Check page hook
add_filter('wpapp_datatable_allowed_hooks', function($hooks) {
    error_log('Current hook: ' . current_filter());
    return $hooks;
});
```

### Panel Not Opening
```javascript
// Check data-id attribute
jQuery('.wpapp-datatable tbody tr').each(function() {
    console.log('Row ID:', jQuery(this).data('id'));
});
```

### Tabs Not Showing
```php
// Verify tabs registered
add_filter('wpapp_datatable_tabs', function($tabs, $entity) {
    error_log('Tabs for ' . $entity . ': ' . print_r($tabs, true));
    return $tabs;
}, 10, 2);
```

---

## File Structure Example

```
wp-customer/
├── src/
│   ├── Controllers/
│   │   └── CustomerDashboard.php       # Hook registration
│   └── Views/
│       └── customers/
│           ├── dashboard.php           # Main dashboard (7 lines)
│           ├── datatable.php          # DataTable HTML
│           └── tabs/
│               ├── details.php        # Tab 1
│               ├── membership.php     # Tab 2
│               └── invoices.php       # Tab 3
└── assets/
    ├── css/
    │   └── customer-custom.css        # Optional overrides
    └── js/
        └── customer-extension.js      # Optional extensions
```

---

## All Available Hooks

### Actions (19)
- `wpapp_dashboard_before_content`
- `wpapp_dashboard_before_stats`
- `wpapp_dashboard_after_stats`
- `wpapp_dashboard_after_content`
- `wpapp_left_panel_header`
- `wpapp_left_panel_content` ⭐ (main DataTable)
- `wpapp_left_panel_footer`
- `wpapp_right_panel_header`
- `wpapp_right_panel_content`
- `wpapp_right_panel_footer`
- `wpapp_before_tab_template`
- `wpapp_after_tab_template`
- `wpapp_no_tabs_content`
- `wpapp_datatable_after_enqueue_styles`
- `wpapp_datatable_after_enqueue_scripts`

### Filters (6)
- `wpapp_datatable_stats` ⭐ (register stats)
- `wpapp_datatable_tabs` ⭐ (register tabs)
- `wpapp_datatable_tab_template`
- `wpapp_datatable_allowed_hooks`
- `wpapp_datatable_should_load_assets`
- `wpapp_datatable_localize_data`
- `wpapp_datatable_i18n_strings`

### JavaScript Events (8)
- `wpapp:panel-opening`
- `wpapp:panel-opened`
- `wpapp:panel-closing`
- `wpapp:panel-closed`
- `wpapp:panel-loading`
- `wpapp:panel-data-loaded` ⭐
- `wpapp:panel-error`
- `wpapp:tab-switching`
- `wpapp:tab-switched` ⭐

⭐ = Most commonly used

---

## Performance Tips

1. **Cache stats:** `wp_cache_set('stats', $data, '', 300)`
2. **Lazy load tabs:** Load content on tab switch
3. **Debounce search:** Wait 300ms before AJAX
4. **Virtual scrolling:** For 1000+ rows
5. **Minimize DOM:** Use DocumentFragment

---

## Security Checklist

- ✅ `check_ajax_referer('wpapp_panel_nonce', 'nonce')`
- ✅ `intval()` or `absint()` for IDs
- ✅ `esc_html()`, `esc_attr()`, `esc_url()` for output
- ✅ `wp_send_json_success()` / `wp_send_json_error()`
- ✅ Check `current_user_can()` permissions

---

## Support

**Full Documentation:** `wp-app-core/src/Views/DataTable/README.md`

**Related:**
- Backend DataTable: `wp-app-core/docs/datatable/`
- TODO-2179: Panel system implementation
- TODO-2178: Backend DataTable system
