# Step-by-Step Implementation Guide

**Complete walkthrough for creating a DataTable page from scratch**

Version: 1.0.0
Created: 2025-11-01
Estimated Time: 30-45 minutes

---

## Prerequisites

- Plugin structure already created
- Database table exists
- Basic understanding of WordPress hooks

---

## Step 1: Create DataTableModel (5 min)

**File:** `src/Models/Entity/EntityDataTableModel.php`

```php
<?php
namespace YourPlugin\Models\Entity;

use WPAppCore\Models\DataTable\DataTableModel;

class EntityDataTableModel extends DataTableModel {

    public function __construct() {
        parent::__construct();
        global $wpdb;

        $this->table = $wpdb->prefix . 'app_entities e';
        $this->index_column = 'e.id';

        // IMPORTANT: If email is in another table, add JOIN here
        $this->base_joins = [
            "LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID"
        ];

        $this->searchable_columns = [
            'e.code',
            'e.name',
            'u.user_email'  // Use JOIN alias
        ];
    }

    protected function get_columns(): array {
        return [
            'e.id as id',
            'e.code as code',
            'e.name as name',
            'u.user_email as email'  // Use JOIN alias
        ];
    }

    protected function format_row($row): array {
        return [
            'DT_RowId' => 'entity-' . $row->id,
            'DT_RowData' => [
                'id' => $row->id,
                'entity' => 'entity'  // MUST match your entity name
            ],
            'code' => esc_html($row->code),
            'name' => esc_html($row->name),
            'email' => esc_html($row->email ?? '-'),
            'actions' => sprintf(
                '<button class="button button-small" data-id="%d">View</button>',
                $row->id
            )
        ];
    }

    protected function get_where(): array {
        global $wpdb;
        $where = [];

        $status = $_POST['status_filter'] ?? 'active';
        if ($status !== 'all') {
            $where[] = $wpdb->prepare('e.status = %s', $status);
        }

        return $where;
    }

    public function get_total_count(string $status = 'all'): int {
        global $wpdb;
        $sql = "SELECT COUNT(e.id) FROM {$this->table}";

        foreach ($this->base_joins as $join) {
            $sql .= " {$join}";
        }

        if ($status !== 'all') {
            $sql .= $wpdb->prepare(' WHERE e.status = %s', $status);
        }

        return (int) $wpdb->get_var($sql);
    }
}
```

**✅ Checklist:**
- [ ] File created
- [ ] Namespace matches your plugin
- [ ] Table name correct
- [ ] Email JOIN added (if needed)
- [ ] Entity name in `DT_RowData` matches everywhere

---

## Step 2: Create DashboardController (10 min)

**File:** `src/Controllers/Entity/EntityDashboardController.php`

```php
<?php
namespace YourPlugin\Controllers\Entity;

use YourPlugin\Models\Entity\EntityDataTableModel;
use YourPlugin\Models\Entity\EntityModel;
use WPAppCore\Views\DataTable\Templates\DashboardTemplate;

class EntityDashboardController {

    private $datatable_model;
    private $model;

    public function __construct() {
        $this->datatable_model = new EntityDataTableModel();
        $this->model = new EntityModel();
        $this->register_hooks();
    }

    private function register_hooks(): void {
        // Panel content
        add_action('wpapp_left_panel_content', [$this, 'render_datatable'], 10, 1);

        // Page header
        add_action('wpapp_page_header_left', [$this, 'render_header_title'], 10, 2);
        add_action('wpapp_page_header_right', [$this, 'render_header_buttons'], 10, 2);

        // Stats
        add_action('wpapp_statistics_cards_content', [$this, 'render_header_cards'], 10, 1);

        // Filters
        add_action('wpapp_dashboard_filters', [$this, 'render_filters'], 10, 2);

        // Tabs
        add_filter('wpapp_datatable_tabs', [$this, 'register_tabs'], 10, 2);
        add_action('wpapp_tab_view_content', [$this, 'render_info_tab'], 10, 3);

        // AJAX
        add_action('wp_ajax_get_entity_datatable', [$this, 'handle_datatable_ajax']);
        add_action('wp_ajax_get_entity_details', [$this, 'handle_get_details']);
        add_action('wp_ajax_get_entity_stats', [$this, 'handle_get_stats']);
    }

    public function renderDashboard(): void {
        DashboardTemplate::render([
            'entity' => 'entity',  // MUST match everywhere
            'title' => __('Entities', 'your-plugin'),
            'ajax_action' => 'get_entity_details',
            'has_stats' => true,
            'has_tabs' => true,
        ]);
    }

    public function render_datatable($config): void {
        if ($config['entity'] !== 'entity') return;

        $datatable_file = WP_APP_CORE_PATH . 'src/Views/DataTable/Templates/datatable.php';
        if (file_exists($datatable_file)) {
            include $datatable_file;
        }
    }

    public function render_header_title($config, $entity): void {
        if ($entity !== 'entity') return;
        $this->render_partial('header-title', [], 'entity');
    }

    public function render_header_buttons($config, $entity): void {
        if ($entity !== 'entity') return;
        $this->render_partial('header-buttons', [], 'entity');
    }

    public function render_header_cards($entity): void {
        if ($entity !== 'entity') return;

        $total = $this->datatable_model->get_total_count('all');
        $active = $this->datatable_model->get_total_count('active');
        $inactive = $this->datatable_model->get_total_count('inactive');

        $this->render_partial('stat-cards', compact('total', 'active', 'inactive'), 'entity');
    }

    public function render_filters($config, $entity): void {
        if ($entity !== 'entity') return;

        $filter_file = WP_APP_CORE_PATH . 'src/Views/DataTable/Templates/partials/status-filter.php';
        if (file_exists($filter_file)) {
            include $filter_file;
        }
    }

    public function register_tabs($tabs, $entity) {
        if ($entity !== 'entity') return $tabs;

        return [
            'info' => [
                'title' => __('Information', 'your-plugin'),
                'priority' => 10,
                'lazy_load' => false
            ]
        ];
    }

    public function render_info_tab($tab_id, $entity, $data): void {
        if ($entity !== 'entity' || $tab_id !== 'info') return;

        $tab_file = YOUR_PLUGIN_PATH . 'src/Views/entity/tabs/info.php';
        if (file_exists($tab_file)) {
            include $tab_file;
        }
    }

    public function handle_datatable_ajax(): void {
        check_ajax_referer('wpapp_panel_nonce', 'nonce');

        if (!current_user_can('view_entities')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $response = $this->datatable_model->get_datatable_data($_POST);
        wp_send_json($response);
    }

    public function handle_get_details(): void {
        try {
            check_ajax_referer('wpapp_panel_nonce', 'nonce');

            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $entity = $this->model->find($id);

            if (!$entity) {
                throw new \Exception('Entity not found');
            }

            // IMPORTANT: Make data available as $data
            $data = $entity;

            $tabs = [];

            ob_start();
            include YOUR_PLUGIN_PATH . 'src/Views/entity/tabs/info.php';
            $tabs['info'] = ob_get_clean();

            wp_send_json_success([
                'title' => $entity->name,
                'tabs' => $tabs,
                'data' => $entity
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function handle_get_stats(): void {
        check_ajax_referer('wpapp_panel_nonce', 'nonce');

        wp_send_json_success([
            'total' => $this->datatable_model->get_total_count('all'),
            'active' => $this->datatable_model->get_total_count('active'),
            'inactive' => $this->datatable_model->get_total_count('inactive')
        ]);
    }

    private function render_partial(string $partial, array $data = [], string $context = ''): void {
        extract($data);
        $partial_path = YOUR_PLUGIN_PATH . 'src/Views/' . $context . '/partials/' . $partial . '.php';
        if (file_exists($partial_path)) {
            include $partial_path;
        }
    }
}
```

**✅ Checklist:**
- [ ] File created
- [ ] All entity names match ('entity')
- [ ] YOUR_PLUGIN_PATH constant defined
- [ ] EntityModel exists

---

## Step 3: Create View Files (5 min)

**Create directories:**
```bash
mkdir -p src/Views/entity/partials
mkdir -p src/Views/entity/tabs
chmod 755 src/Views/entity
chmod 755 src/Views/entity/partials
chmod 755 src/Views/entity/tabs
```

**File:** `src/Views/entity/partials/header-title.php`
```php
<h1 class="entity-title">Entities</h1>
<div class="entity-subtitle">Manage all entities</div>
```

**File:** `src/Views/entity/partials/header-buttons.php`
```php
<a href="#" class="button button-primary entity-add-btn">
    <span class="dashicons dashicons-plus-alt"></span>
    Add New Entity
</a>
```

**File:** `src/Views/entity/partials/stat-cards.php`
```php
<div class="entity-statistics-cards">
    <div class="entity-stat-card entity-theme-blue">
        <div class="entity-stat-icon">
            <span class="dashicons dashicons-groups"></span>
        </div>
        <div class="entity-stat-content">
            <div class="entity-stat-number" id="stat-total-entities"><?php echo esc_html($total ?? 0); ?></div>
            <div class="entity-stat-label">Total</div>
        </div>
    </div>

    <div class="entity-stat-card entity-theme-green">
        <div class="entity-stat-icon">
            <span class="dashicons dashicons-yes-alt"></span>
        </div>
        <div class="entity-stat-content">
            <div class="entity-stat-number" id="stat-active-entities"><?php echo esc_html($active ?? 0); ?></div>
            <div class="entity-stat-label">Active</div>
        </div>
    </div>

    <div class="entity-stat-card entity-theme-orange">
        <div class="entity-stat-icon">
            <span class="dashicons dashicons-dismiss"></span>
        </div>
        <div class="entity-stat-content">
            <div class="entity-stat-number" id="stat-inactive-entities"><?php echo esc_html($inactive ?? 0); ?></div>
            <div class="entity-stat-label">Inactive</div>
        </div>
    </div>
</div>
```

**File:** `src/Views/entity/tabs/info.php`
```php
<?php
defined('ABSPATH') || exit;

if (!isset($data) || !is_object($data)) {
    echo '<p>Data not available</p>';
    return;
}

$entity = $data;
?>

<div class="entity-info-tab">
    <div class="entity-info-section">
        <h3>Details</h3>

        <div class="entity-info-row">
            <label>Code:</label>
            <span><?php echo esc_html($entity->code ?? '-'); ?></span>
        </div>

        <div class="entity-info-row">
            <label>Name:</label>
            <span><?php echo esc_html($entity->name ?? '-'); ?></span>
        </div>

        <div class="entity-info-row">
            <label>Email:</label>
            <span><?php echo esc_html($entity->email ?? '-'); ?></span>
        </div>
    </div>
</div>
```

**✅ Checklist:**
- [ ] All partials created
- [ ] Tab template created
- [ ] Permissions set to 755
- [ ] Entity name in classes (entity-*)

---

## Step 4: Create CSS Files (5 min)

**Create directory:**
```bash
mkdir -p assets/css/entity
```

**File:** `assets/css/entity/entity-header-cards.css`
```css
/* Page Header */
.entity-title {
    font-size: 23px;
    font-weight: 400;
    margin: 0;
    padding: 0;
    line-height: 1.3;
}

.entity-subtitle {
    color: #646970;
    font-size: 13px;
    margin-top: 4px;
}

/* Statistics Cards */
.entity-statistics-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    padding: 20px 15px;
}

.entity-stat-card {
    display: flex;
    align-items: center;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    transition: all 0.2s;
}

.entity-stat-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.entity-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.entity-stat-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

/* Theme Colors */
.entity-theme-blue .entity-stat-icon {
    background: #2271b1;
    color: #fff;
}

.entity-theme-green .entity-stat-icon {
    background: #00a32a;
    color: #fff;
}

.entity-theme-orange .entity-stat-icon {
    background: #f0b849;
    color: #fff;
}

.entity-stat-number {
    font-size: 32px;
    font-weight: 600;
    line-height: 1;
    color: #1d2327;
}

.entity-stat-label {
    font-size: 13px;
    color: #646970;
    margin-top: 4px;
}
```

**File:** `assets/css/entity/entity-filter.css`
```css
/* Filter Controls */
.entity-status-filter-group {
    margin-bottom: 15px;
}

/* Tab Content */
.entity-info-tab {
    padding: 20px;
}

.entity-info-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.entity-info-section h3 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #c3c4c7;
}

.entity-info-row {
    display: flex;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f1;
}

.entity-info-row:last-child {
    border-bottom: none;
}

.entity-info-row label {
    width: 150px;
    font-weight: 600;
    color: #1d2327;
}

.entity-info-row span {
    flex: 1;
    color: #646970;
}
```

**File:** `assets/css/entity/entity-datatable.css`
```css
/* Table Layout */
#entity-list-table {
    width: 100% !important;
    table-layout: fixed !important;
    border-collapse: collapse;
}

#entity-list-table thead th,
#entity-list-table tbody td {
    padding: 10px 8px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

#entity-list-table thead th {
    background: #f6f7f7;
    border-bottom: 2px solid #2271b1;
    font-weight: 600;
    font-size: 13px;
}

#entity-list-table tbody td {
    border-bottom: 1px solid #dcdcde;
    font-size: 13px;
}

#entity-list-table tbody tr:hover {
    background-color: #f6f7f7;
    cursor: pointer;
}

/* Column Widths */
#entity-list-table th:nth-child(1),
#entity-list-table td:nth-child(1) {
    width: 10%;
    max-width: 10%;
}

#entity-list-table th:nth-child(2),
#entity-list-table td:nth-child(2) {
    width: 30%;
    max-width: 30%;
}

#entity-list-table th:nth-child(3),
#entity-list-table td:nth-child(3) {
    width: 25%;
    max-width: 25%;
}

#entity-list-table th:nth-child(4),
#entity-list-table td:nth-child(4) {
    width: 15%;
    max-width: 15%;
}
```

**✅ Checklist:**
- [ ] All CSS files created
- [ ] Class names use entity- prefix
- [ ] Column widths total 100%

---

## Step 5: Create JavaScript (5 min)

**File:** `assets/js/entity/entity-datatable.js`
```javascript
(function($) {
    'use strict';

    function initEntityDataTable() {
        var $table = $('#entity-list-table');

        if ($table.length === 0) {
            return;
        }

        var dataTable = $table.DataTable({
            processing: true,
            serverSide: true,
            autoWidth: false,
            ajax: {
                url: wpAppCoreEntity.ajaxurl,
                type: 'POST',
                data: function(d) {
                    d.action = 'get_entity_datatable';
                    d.nonce = wpAppCoreEntity.nonce;
                    d.status_filter = $('#entity-status-filter').val() || 'active';
                }
            },
            columns: [
                { data: 'code', title: wpAppCoreEntity.i18n.code },
                { data: 'name', title: wpAppCoreEntity.i18n.name },
                { data: 'email', title: wpAppCoreEntity.i18n.email },
                { data: 'actions', title: wpAppCoreEntity.i18n.actions, orderable: false, searchable: false }
            ],
            columnDefs: [
                { width: '10%', targets: 0 },
                { width: '30%', targets: 1 },
                { width: '25%', targets: 2 },
                { width: '15%', targets: 3 }
            ],
            order: [[0, 'desc']],
            pageLength: 10,
            language: wpAppCoreEntity.i18n
        });

        $('#entity-status-filter').on('change', function() {
            dataTable.ajax.reload();
        });
    }

    function loadEntityStatistics() {
        $.ajax({
            url: wpAppCoreEntity.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_entity_stats',
                nonce: wpAppCoreEntity.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    $('#stat-total-entities').text(response.data.total);
                    $('#stat-active-entities').text(response.data.active);
                    $('#stat-inactive-entities').text(response.data.inactive);
                }
            }
        });
    }

    $(document).ready(function() {
        initEntityDataTable();
        loadEntityStatistics();
    });

})(jQuery);
```

**✅ Checklist:**
- [ ] File created
- [ ] Column count matches DataTable model
- [ ] Column widths match CSS

---

## Step 6: Enqueue Assets (5 min)

**File:** `includes/class-dependencies.php`

```php
<?php
public function enqueue_styles($hook) {
    $screen = get_current_screen();

    if ($screen && $screen->id === 'toplevel_page_entity') {
        // DataTables
        wp_enqueue_style('datatables', 'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css', [], '1.13.7');

        // Entity styles
        wp_enqueue_style('entity-header-cards', YOUR_PLUGIN_URL . 'assets/css/entity/entity-header-cards.css', [], $this->version);
        wp_enqueue_style('entity-filter', YOUR_PLUGIN_URL . 'assets/css/entity/entity-filter.css', [], $this->version);
        wp_enqueue_style('entity-datatable', YOUR_PLUGIN_URL . 'assets/css/entity/entity-datatable.css', ['datatables'], $this->version);
    }
}

public function enqueue_scripts($hook) {
    $screen = get_current_screen();

    if ($screen && $screen->id === 'toplevel_page_entity') {
        // DataTables
        wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js', ['jquery'], '1.13.7', true);

        // Entity script
        wp_enqueue_script('entity-datatable', YOUR_PLUGIN_URL . 'assets/js/entity/entity-datatable.js', ['jquery', 'datatables'], $this->version, true);

        // Localize script
        wp_localize_script('entity-datatable', 'wpAppCoreEntity', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpapp_panel_nonce'),
            'i18n' => [
                'code' => __('Code', 'your-plugin'),
                'name' => __('Name', 'your-plugin'),
                'email' => __('Email', 'your-plugin'),
                'actions' => __('Actions', 'your-plugin'),
                'processing' => __('Processing...', 'your-plugin'),
                'search' => __('Search:', 'your-plugin'),
                'lengthMenu' => __('Show _MENU_ entries', 'your-plugin'),
                'info' => __('Showing _START_ to _END_ of _TOTAL_ entries', 'your-plugin'),
                'infoEmpty' => __('Showing 0 to 0 of 0 entries', 'your-plugin'),
                'infoFiltered' => __('(filtered from _MAX_ total entries)', 'your-plugin'),
                'zeroRecords' => __('No matching records found', 'your-plugin'),
                'emptyTable' => __('No data available in table', 'your-plugin'),
                'paginate' => [
                    'first' => __('First', 'your-plugin'),
                    'previous' => __('Previous', 'your-plugin'),
                    'next' => __('Next', 'your-plugin'),
                    'last' => __('Last', 'your-plugin')
                ]
            ]
        ]);
    }
}
```

**✅ Checklist:**
- [ ] Styles enqueued
- [ ] Scripts enqueued
- [ ] Localization added
- [ ] Nonce is 'wpapp_panel_nonce'

---

## Step 7: Update MenuManager (2 min)

**File:** `src/Controllers/MenuManager.php`

```php
<?php
use YourPlugin\Controllers\Entity\EntityDashboardController;

class MenuManager {
    private $entity_dashboard_controller;

    public function __construct() {
        $this->entity_dashboard_controller = new EntityDashboardController();
    }

    public function registerMenus() {
        add_menu_page(
            __('Entities', 'your-plugin'),
            __('Entities', 'your-plugin'),
            'view_entities',
            'entity',
            [$this->entity_dashboard_controller, 'renderDashboard'],
            'dashicons-list-view',
            30
        );
    }
}
```

**✅ Checklist:**
- [ ] Controller imported
- [ ] Property added
- [ ] Initialized in constructor
- [ ] Menu registered with callback

---

## Step 8: Test Everything (5 min)

**Clear cache:**
```bash
wp cache flush
```

**Test checklist:**
- [ ] Navigate to menu → Page loads
- [ ] DataTable displays data
- [ ] Search works
- [ ] Filter works
- [ ] Click row → Panel opens
- [ ] Tab shows data (not "Data not available")
- [ ] Statistics load correctly
- [ ] No JavaScript errors in console
- [ ] No 403 errors
- [ ] Table columns don't overlap

---

## Common Issues Quick Fix

**Issue:** "Class not found" error
```bash
# Fix permissions
chmod 755 src/Controllers/Entity
```

**Issue:** Email column error
```php
// Add JOIN in DataTableModel constructor
$this->base_joins = ["LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID"];
```

**Issue:** Tab shows "Data not available"
```php
// In handle_get_details(), before include:
$data = $entity;
```

**Issue:** Table columns overlap
```css
/* Add to entity-datatable.css */
#entity-list-table {
    table-layout: fixed !important;
}
```

**Issue:** 403 Forbidden
```php
// Use wpapp_panel_nonce everywhere
wp_create_nonce('wpapp_panel_nonce')
check_ajax_referer('wpapp_panel_nonce', 'nonce')
```

---

## Done! 🎉

Your DataTable page is now complete and following best practices.

**Total Time:** ~30 minutes
**Files Created:** 12
**Lines of Code:** ~600

---

**Next Steps:**
- Add more tabs
- Implement CRUD operations
- Add export functionality
- Customize styling

---

**Need Help?**
- Check: `COMMON-ISSUES.md` for troubleshooting
- Read: `README.md` for detailed documentation
- Reference: `TODO-2187` for real implementation example
