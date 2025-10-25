# WP App Core - DataTable System Documentation

## Overview

Sistem DataTable di WP App Core terinspirasi dari **Perfex CRM DataTable Pattern**, yang menyediakan implementasi server-side DataTables yang powerful, extensible, dan modular untuk aplikasi WordPress.

## 📚 Table of Contents

1. [Introduction](#introduction)
2. [Quick Start](#quick-start)
3. [Documentation Structure](#documentation-structure)
4. [Key Concepts](#key-concepts)
5. [Architecture Overview](#architecture-overview)

---

## Introduction

### What is This System?

Sistem DataTable ini adalah framework untuk membuat dan mengelola server-side DataTables di WordPress dengan pola yang mirip dengan Perfex CRM. Sistem ini dirancang dengan prinsip:

- **Modular**: Core functionality di `wp-app-core`, modules extend via hooks
- **MVC Pattern**: Separation of concerns dengan Model, View, Controller
- **Extensible**: Plugin-plugin lain (wp-customer, wp-agency) dapat extend tanpa modify core
- **WordPress Native**: Menggunakan WordPress hooks system (`apply_filters`, `do_action`)
- **Reusable**: Satu base class untuk semua DataTables di aplikasi

### Why This Pattern?

✅ **Separation of Concerns**: Core logic terpisah dari business logic modules
✅ **Plugin Independence**: Modules tidak perlu tahu tentang satu sama lain
✅ **Easy Maintenance**: Update core tanpa break modules
✅ **Performance**: Server-side processing untuk dataset besar
✅ **Flexibility**: Multiple modules bisa hook ke DataTable yang sama

---

## Quick Start

### 1. Create a Model (in wp-app-core or module plugin)

```php
<?php
namespace YourNamespace\Models;

use WPAppCore\Models\DataTable\DataTableModel;

class YourDataTableModel extends DataTableModel {

    public function __construct() {
        global $wpdb;

        $this->table = $wpdb->prefix . 'your_table';
        $this->columns = ['id', 'name', 'email'];
        $this->searchable_columns = ['name', 'email'];
        $this->index_column = 'id';
    }

    protected function format_row($row) {
        return [
            $row->id,
            $row->name,
            $row->email
        ];
    }
}
```

### 2. Register AJAX Handler

```php
add_action('wp_ajax_your_datatable', function() {
    $controller = new \WPAppCore\Controllers\DataTable\DataTableController();
    $controller->handle_ajax_request(\YourNamespace\Models\YourDataTableModel::class);
});
```

### 3. Add Filters (in module plugin)

```php
add_filter('wpapp_datatable_your_table_where', function($where, $request, $model) {
    if (isset($_GET['status'])) {
        $where[] = "status = '" . esc_sql($_GET['status']) . "'";
    }
    return $where;
}, 10, 3);
```

### 4. Initialize DataTable (JavaScript)

```javascript
$('#your-datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'your_datatable',
            nonce: wpapp_nonce
        }
    },
    columns: [
        { data: 0 },
        { data: 1 },
        { data: 2 }
    ]
});
```

---

## Documentation Structure

Dokumentasi ini terorganisir dalam beberapa bagian:

### Core Documentation

- **[Architecture](datatable/ARCHITECTURE.md)**: Arsitektur sistem secara keseluruhan
- **[Core Implementation](datatable/core/IMPLEMENTATION.md)**: Implementasi base classes di wp-app-core
- **[Query Builder](datatable/core/QUERY-BUILDER.md)**: DataTable query builder details

### Module Documentation

- **[Module Extension Guide](datatable/modules/EXTENSION-GUIDE.md)**: Cara modules extend DataTable
- **[Filter Hooks](datatable/modules/FILTER-HOOKS.md)**: Daftar lengkap filter hooks yang tersedia
- **[Module Examples](datatable/modules/EXAMPLES.md)**: Contoh implementasi di wp-customer & wp-agency

### API Reference

- **[API Reference](datatable/api/REFERENCE.md)**: Complete API documentation
- **[Hooks Reference](datatable/api/HOOKS.md)**: Semua available hooks dan parameters

### Examples & Best Practices

- **[Use Cases](datatable/examples/USE-CASES.md)**: Real-world use cases
- **[Code Examples](datatable/examples/CODE-EXAMPLES.md)**: Snippets dan contoh kode
- **[Best Practices](datatable/BEST-PRACTICES.md)**: Panduan best practices

---

## File Reference

### Core Files (wp-app-core)

**Models:**
- `src/Models/DataTable/DataTableModel.php` - Base DataTable model class
- `src/Models/DataTable/DataTableQueryBuilder.php` - SQL query builder
- `src/Models/Platform/PlatformStaffDataTableModel.php` - Example implementation

**Controllers:**
- `src/Controllers/DataTable/DataTableController.php` - AJAX request handler
- `src/Controllers/DataTable/DataTableAssetsController.php` - Asset management

**View Templates:**
- `src/Views/DataTable/Templates/DashboardTemplate.php` - Main dashboard container
- `src/Views/DataTable/Templates/PanelLayoutTemplate.php` - Left/right panel layout
- `src/Views/DataTable/Templates/TabSystemTemplate.php` - WordPress-style tabs
- `src/Views/DataTable/Templates/StatsBoxTemplate.php` - Statistics cards
- `src/Views/DataTable/Templates/NavigationTemplate.php` - Navigation components

**Assets:**
- `assets/css/datatable/wpapp-datatable.css` - Global panel styles
- `assets/js/datatable/wpapp-panel-manager.js` - Panel open/close, AJAX
- `assets/js/datatable/wpapp-tab-manager.js` - Tab switching

**Tests/Examples:**
- `src/Tests/DataTable/PlatformStaffDataTableFilters.php` - Example filter implementation

---

## Key Concepts

### 1. **Base Model (wp-app-core)**

📁 `src/Models/DataTable/DataTableModel.php`

Core plugin menyediakan `DataTableModel` sebagai base class yang handle:
- Server-side processing
- Query building
- Pagination, sorting, searching
- Filter hooks untuk extensibility

### 2. **Module Models**

Plugin modules (wp-customer, wp-agency) extend base model untuk table-specific logic:
- Define columns
- Format output
- Custom logic

### 3. **Filter Hooks**

WordPress hooks memungkinkan modules untuk:
- Modify WHERE conditions
- Add JOINs
- Customize columns
- Modify row output
- Add permissions checks

### 4. **Controller Layer**

📁 `src/Controllers/DataTable/DataTableController.php`

Controller handle AJAX requests dan security:
- Nonce verification
- Permission checks
- Model instantiation
- JSON response

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                        CLIENT SIDE                           │
│  ┌────────────────────────────────────────────────────┐     │
│  │  DataTables.js - AJAX Request                       │     │
│  └────────────────────────────────────────────────────┘     │
└───────────────────────────┬─────────────────────────────────┘
                            │ AJAX
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    WP-APP-CORE (Core)                        │
│  ┌────────────────────────────────────────────────────┐     │
│  │  DataTableController                                │     │
│  │  - Security checks                                  │     │
│  │  - Permission validation                            │     │
│  │  - Model instantiation                              │     │
│  └──────────────────┬─────────────────────────────────┘     │
│                     │                                        │
│  ┌──────────────────▼─────────────────────────────────┐     │
│  │  DataTableModel (Base)                              │     │
│  │  - Column definition                                │     │
│  │  - Query building                                   │     │
│  │  - Filter hooks (apply_filters)                     │     │
│  │  - Row formatting                                   │     │
│  └──────────────────┬─────────────────────────────────┘     │
└────────────────────┬┴──────────────────────────────────────┘
                     │
        ┌────────────┼────────────┐
        │            │            │
        ▼            ▼            ▼
┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│ WP-CUSTOMER │ │ WP-AGENCY   │ │ Other Module│
│             │ │             │ │             │
│ add_filter  │ │ add_filter  │ │ add_filter  │
│ - WHERE     │ │ - WHERE     │ │ - COLUMNS   │
│ - COLUMNS   │ │ - JOINS     │ │ - ROW_DATA  │
│ - ROW_DATA  │ │ - ROW_DATA  │ │             │
└─────────────┘ └─────────────┘ └─────────────┘
```

### Data Flow

1. **Client**: DataTables.js sends AJAX request
2. **Controller**: Validates request, checks permissions
3. **Model**: Builds query with base logic
4. **Hooks**: Modules inject their filters/modifications
5. **Query**: Execute final query with all modifications
6. **Format**: Format each row (with module hooks)
7. **Response**: Return JSON to DataTables.js

---

## Getting Help

- **Issues**: Report bugs atau request features
- **Examples**: Lihat folder `examples/` untuk contoh lengkap
- **API Docs**: Baca `api/REFERENCE.md` untuk detail methods

---

## Version History

- **v1.0.0** - Initial DataTable system implementation
  - Base DataTableModel
  - Query Builder
  - Filter hooks system
  - Documentation

---

## Next Steps

1. 📖 Baca [Architecture Documentation](datatable/ARCHITECTURE.md)
2. 💻 Pelajari [Core Implementation](datatable/core/IMPLEMENTATION.md)
3. 🔌 Lihat [Module Extension Guide](datatable/modules/EXTENSION-GUIDE.md)
4. 📝 Review [Examples](datatable/examples/CODE-EXAMPLES.md)

---

**Happy Coding!** 🚀
