# WP App Core Documentation Index

Complete documentation index for wp-app-core plugin system.

---

## 📚 Documentation Structure

### DataTable System (Perfex CRM Pattern)

Complete implementation of server-side DataTables inspired by Perfex CRM.

**Two-Part System:**
1. **Backend (Server-Side Processing)** - This documentation (`docs/datatable/`)
2. **Frontend (Panel System & UI)** - See [`src/Views/DataTable/README.md`](../src/Views/DataTable/README.md)

---

## 🚀 Getting Started

**Start here if you're new:**

1. **[Main README](README.md)** - Overview and introduction
2. **[Architecture](datatable/ARCHITECTURE.md)** - System architecture explained
3. **[Quick Examples](datatable/examples/CODE-EXAMPLES.md)** - Jump into code

---

## 📖 Core Documentation

### Architecture & Design

| Document | Description | Audience |
|----------|-------------|----------|
| **[Architecture](datatable/ARCHITECTURE.md)** | Complete system architecture, data flow, component design | All Developers |
| **[Best Practices](datatable/BEST-PRACTICES.md)** | Guidelines for security, performance, code quality | All Developers |

### Implementation Guides

| Document | Description | Audience |
|----------|-------------|----------|
| **[Core Implementation](datatable/core/IMPLEMENTATION.md)** | Base classes in wp-app-core (Model, Controller, QueryBuilder) | Core Developers |
| **[Module Extension Guide](datatable/modules/EXTENSION-GUIDE.md)** | How to extend DataTables from module plugins | Plugin Developers |

### API Reference

| Document | Description | Audience |
|----------|-------------|----------|
| **[API Reference](datatable/api/REFERENCE.md)** | Complete API documentation for all classes and methods | All Developers |
| **[Hooks Reference](datatable/api/HOOKS.md)** | Detailed filter hooks documentation with examples | Plugin Developers |
| **[Filter Hooks Quick Ref](datatable/modules/FILTER-HOOKS.md)** | Quick reference card for available hooks | Plugin Developers |

### Examples & Use Cases

| Document | Description | Audience |
|----------|-------------|----------|
| **[Code Examples](datatable/examples/CODE-EXAMPLES.md)** | Real-world implementations and patterns | All Developers |

### Frontend Panel System (NEW)

| Document | Description | Audience |
|----------|-------------|----------|
| **[Panel System README](../src/Views/DataTable/README.md)** | Complete guide: Dashboard, Panel Layout, Tabs, Statistics | Frontend Developers |
| **[Quick Reference](../src/Views/DataTable/QUICK-REFERENCE.md)** | Cheatsheet for common tasks and hooks | All Developers |
| **[Migration Example](../src/Views/DataTable/MIGRATION-EXAMPLE.md)** | Real-world migration: wp-customer Companies (1154→427 lines, 63% reduction) | All Developers |

**Key Features:**
- ✅ Left/Right panel layout (Perfex CRM style)
- ✅ WordPress-style tab system
- ✅ Statistics cards with loading states
- ✅ Smooth animations (no flicker)
- ✅ Hash-based navigation (#entity-123)
- ✅ Full hook system for extensibility
- ✅ JavaScript event system
- ✅ **NEW (v1.1.0)**: Consistent container structure (TODO-1187)
- ✅ **NEW (v1.1.0)**: Full width utilization with negative margins

**Quick Start:**
```php
use WPAppCore\Views\DataTable\Templates\DashboardTemplate;

DashboardTemplate::render([
    'entity' => 'customer',
    'title' => 'Customers',
    'has_stats' => true,
    'has_tabs' => true
]);
```

**HTML Structure (v1.1.0 - Simplified):**
```html
<div class="wpapp-datatable-page">
    <div class="wpapp-page-header-container">...</div>
    <div class="wpapp-statistics-container">...</div>
    <div class="wpapp-filters-container">...</div>
    <div class="wpapp-datatable-container">
        <div class="wpapp-datatable-layout">...</div>
    </div>
</div>
```

**Full Width Pattern (v1.1.0):**
All containers use negative margins to extend to full available width:
```css
.wpapp-page-header-container   { margin: 0 -15px 0 -15px; }
.wpapp-statistics-container    { margin: 20px -15px 20px -15px; }
.wpapp-filters-container       { margin: 0 -15px 20px -15px; }
.wpapp-datatable-container     { margin: 0 -15px 20px -15px; }
```

**Benefits:**
- Perfect alignment of all container boundaries
- No wasted horizontal space
- Consistent foundation for all dashboard pages in the application

---

## 🎯 By Use Case

### "I want to create a new DataTable in wp-app-core"

1. Read: [Core Implementation](datatable/core/IMPLEMENTATION.md)
2. Follow: Model → Controller → View pattern
3. Reference: [API Reference](datatable/api/REFERENCE.md)
4. Review: [Code Examples](datatable/examples/CODE-EXAMPLES.md)

### "I want to extend a DataTable from my plugin (wp-customer, wp-agency)"

1. Read: [Module Extension Guide](datatable/modules/EXTENSION-GUIDE.md)
2. Reference: [Filter Hooks](datatable/modules/FILTER-HOOKS.md)
3. Review: [Code Examples - Multi-Module Integration](datatable/examples/CODE-EXAMPLES.md#multi-module-integration)
4. Follow: [Best Practices](datatable/BEST-PRACTICES.md)

### "I want to add filters to an existing DataTable"

1. Read: [Filter Hooks Quick Ref](datatable/modules/FILTER-HOOKS.md)
2. Review: [Hooks Reference](datatable/api/HOOKS.md) for detailed examples
3. Check: [Code Examples - Advanced Filtering](datatable/examples/CODE-EXAMPLES.md#advanced-filtering)

### "I want to understand the system architecture"

1. Read: [Architecture](datatable/ARCHITECTURE.md)
2. Understand: [Core Implementation](datatable/core/IMPLEMENTATION.md)
3. Review: Data flow and component interactions

### "I'm debugging a DataTable issue"

1. Check: [Best Practices - Debugging](datatable/BEST-PRACTICES.md#debugging-hooks)
2. Reference: [Hooks Reference - Debugging](datatable/api/HOOKS.md#debugging-hooks)
3. Review: [Common Pitfalls](datatable/BEST-PRACTICES.md#common-pitfalls-to-avoid)

### "I want to create a dashboard with panel/tab system" (NEW)

1. Read: [Panel System README](../src/Views/DataTable/README.md)
2. Quick Start: [Quick Reference](../src/Views/DataTable/QUICK-REFERENCE.md)
3. Register hooks for DataTable, Stats, Tabs
4. Implement AJAX handler for panel data

### "I want to migrate old panel code to new system" (NEW)

1. Read: [Migration Example](../src/Views/DataTable/MIGRATION-EXAMPLE.md) ⭐ Start here!
2. Follow: 10-step migration process
3. Reference: [Migration Guide](../src/Views/DataTable/README.md#migration-guide)
4. Test: Use 30+ item testing checklist
5. Review: Common migration issues & solutions

---

## 🔍 Quick Lookup

### Class Reference

| Class | File Path | Documentation |
|-------|-----------|---------------|
| **DataTableModel** | `src/Models/DataTable/DataTableModel.php` | [Core Implementation](datatable/core/IMPLEMENTATION.md#1-datatablemodel-base-class) |
| **DataTableQueryBuilder** | `src/Models/DataTable/DataTableQueryBuilder.php` | [Core Implementation](datatable/core/IMPLEMENTATION.md#2-datatablequerybuilder) |
| **DataTableController** | `src/Controllers/DataTable/DataTableController.php` | [Core Implementation](datatable/core/IMPLEMENTATION.md#3-datatablecontroller) |
| **DataTableAssetsController** | `src/Controllers/DataTable/DataTableAssetsController.php` | Asset management |
| **DashboardTemplate** | `src/Views/DataTable/Templates/DashboardTemplate.php` | [Panel System README](../src/Views/DataTable/README.md) |
| **PanelLayoutTemplate** | `src/Views/DataTable/Templates/PanelLayoutTemplate.php` | [Panel System README](../src/Views/DataTable/README.md) |
| **TabSystemTemplate** | `src/Views/DataTable/Templates/TabSystemTemplate.php` | [Panel System README](../src/Views/DataTable/README.md) |
| **StatsBoxTemplate** | `src/Views/DataTable/Templates/StatsBoxTemplate.php` | [Panel System README](../src/Views/DataTable/README.md) |

### Hook Reference

All hooks follow pattern: `wpapp_datatable_{table}_{type}`

- **Columns Hook** → [Hooks](datatable/api/HOOKS.md#1-columns-hook)
- **WHERE Hook** → [Hooks](datatable/api/HOOKS.md#2-where-hook)
- **JOIN Hook** → [Hooks](datatable/api/HOOKS.md#3-joins-hook)
- **Row Data Hook** → [Hooks](datatable/api/HOOKS.md#5-row-data-hook)
- **Permission Hook** → [Hooks](datatable/api/HOOKS.md#7-permission-hook)

### Common Tasks

- **Create DataTable** → [Extension Guide](datatable/modules/EXTENSION-GUIDE.md#pattern-1-create-module-specific-datatable)
- **Add Filter** → [Filter Hooks](datatable/modules/FILTER-HOOKS.md#2-where-hooks)
- **Add Column** → [Filter Hooks](datatable/modules/FILTER-HOOKS.md#1-column-hooks)
- **Add JOIN** → [Filter Hooks](datatable/modules/FILTER-HOOKS.md#3-join-hooks)
- **Customize Row** → [Filter Hooks](datatable/modules/FILTER-HOOKS.md#5-row-data-hooks)
- **Check Permission** → [Filter Hooks](datatable/modules/FILTER-HOOKS.md#7-permission-hooks)

---

## 📊 Documentation Map

```
docs/
├── README.md                          # Main overview
├── INDEX.md                           # This file (documentation index)
│
└── datatable/                         # DataTable System (Backend)
    │
    ├── ARCHITECTURE.md                # System architecture
    ├── BEST-PRACTICES.md              # Guidelines & best practices
    │
    ├── core/                          # Core implementation
    │   └── IMPLEMENTATION.md          # Base classes (wp-app-core)
    │
    ├── modules/                       # Module/plugin extension
    │   ├── EXTENSION-GUIDE.md         # How to extend from modules
    │   └── FILTER-HOOKS.md            # Quick filter reference
    │
    ├── api/                           # API reference
    │   ├── REFERENCE.md               # Complete API docs
    │   └── HOOKS.md                   # Detailed hooks reference
    │
    └── examples/                      # Examples & use cases
        └── CODE-EXAMPLES.md           # Real-world examples

src/Views/DataTable/                   # Panel System (Frontend) - NEW
├── README.md                          # Complete panel system guide (1100+ lines)
├── QUICK-REFERENCE.md                 # Quick lookup cheatsheet (400+ lines)
├── MIGRATION-EXAMPLE.md               # Real migration example (900+ lines) ⭐
│
└── Templates/                         # Base template classes
    ├── DashboardTemplate.php          # Main dashboard container
    ├── PanelLayoutTemplate.php        # Left/right panel layout
    ├── TabSystemTemplate.php          # WordPress-style tabs
    └── StatsBoxTemplate.php           # Statistics cards

assets/
├── css/datatable/
│   └── wpapp-datatable.css            # Global panel styles
└── js/datatable/
    ├── wpapp-panel-manager.js         # Panel open/close, AJAX
    └── wpapp-tab-manager.js           # Tab switching
```

---

## 🎓 Learning Path

### Beginner

1. ✅ Read [Main README](README.md)
2. ✅ Understand [Architecture Overview](datatable/ARCHITECTURE.md)
3. ✅ Follow [Basic Example](datatable/examples/CODE-EXAMPLES.md#basic-customer-datatable)
4. ✅ Create your first DataTable

### Intermediate

1. ✅ Study [Module Extension Guide](datatable/modules/EXTENSION-GUIDE.md)
2. ✅ Learn [Filter Hooks](datatable/modules/FILTER-HOOKS.md)
3. ✅ Implement [Advanced Filtering](datatable/examples/CODE-EXAMPLES.md#advanced-filtering)
4. ✅ Add multi-module integration

### Advanced

1. ✅ Master [API Reference](datatable/api/REFERENCE.md)
2. ✅ Optimize using [Best Practices](datatable/BEST-PRACTICES.md)
3. ✅ Implement [Complex JOINs](datatable/examples/CODE-EXAMPLES.md#complex-joins)
4. ✅ Build custom query builders

---

## 🔧 Development Workflow

### Creating a New DataTable

```
1. Define Model (extend DataTableModel)
   ↓
2. Register AJAX Handler (DataTableController)
   ↓
3. Create View (HTML + DataTables.js)
   ↓
4. Add Filters (if needed)
   ↓
5. Test & Optimize
```

**Reference:**
- Model: [Core Implementation](datatable/core/IMPLEMENTATION.md#usage-in-core)
- Controller: [Core Implementation](datatable/core/IMPLEMENTATION.md#3-datatablecontroller)
- View: [Code Examples](datatable/examples/CODE-EXAMPLES.md#basic-customer-datatable)

### Extending Existing DataTable

```
1. Identify Hook Points
   ↓
2. Create Filter Class
   ↓
3. Register Filters (add_filter)
   ↓
4. Test Integration
```

**Reference:**
- Hook Points: [Filter Hooks](datatable/modules/FILTER-HOOKS.md)
- Filter Class: [Extension Guide](datatable/modules/EXTENSION-GUIDE.md#pattern-2-hook-into-existing-datatables)
- Examples: [Multi-Module Integration](datatable/examples/CODE-EXAMPLES.md#multi-module-integration)

---

## 📋 Checklists

### Pre-Development

- [ ] Read architecture documentation
- [ ] Understand MVC pattern
- [ ] Review code examples
- [ ] Check existing implementations

### During Development

- [ ] Follow naming conventions
- [ ] Sanitize all inputs
- [ ] Escape all outputs
- [ ] Add proper comments
- [ ] Handle errors gracefully

### Post-Development

- [ ] Write unit tests
- [ ] Test with large datasets
- [ ] Test different user roles
- [ ] Check browser compatibility
- [ ] Update documentation

**Full Checklist:** [Best Practices](datatable/BEST-PRACTICES.md#maintenance-checklist)

---

## 🆘 Getting Help

### Common Issues

| Issue | Solution | Reference |
|-------|----------|-----------|
| SQL Injection | Use `esc_sql()` or prepared statements | [Security Best Practices](datatable/BEST-PRACTICES.md#security) |
| Slow queries | Add indexes, optimize JOINs | [Performance](datatable/BEST-PRACTICES.md#performance) |
| Filters not working | Check priority, verify hook name | [Debugging Hooks](datatable/api/HOOKS.md#debugging-hooks) |
| Permission denied | Check capabilities, review filter | [Permission Hook](datatable/api/HOOKS.md#7-permission-hook) |

### Debug Steps

1. Check browser console for JS errors
2. Check PHP error logs
3. Verify nonce
4. Test with admin user
5. Review [Debugging Guide](datatable/BEST-PRACTICES.md#error-handling)

---

## 📦 Version History

- **v1.1.0 (2025-10-28)** - Container Structure Improvements (TODO-1187)
  - Simplified container structure (removed nested wrappers)
  - Full width pattern using negative margins (-15px left/right)
  - Fixed header flexbox alignment
  - Fixed container boundaries alignment
  - Added panel spacing (20px top/bottom, 15px gap)
  - Perfect alignment across all dashboard containers
  - Foundation for all dashboard pages in the application

- **v1.0.0 (2025-10-23)** - Initial DataTable system implementation
  - Base classes (Model, QueryBuilder, Controller)
  - Filter hooks system
  - Complete documentation

---

## 🔗 External Resources

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [DataTables.net Documentation](https://datatables.net/manual/)
- [Perfex CRM](https://codecanyon.net/item/perfex-powerful-open-source-crm/14013737)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)

---

## 📝 Contributing

When adding new documentation:

1. Follow existing structure
2. Use consistent formatting
3. Add to this index
4. Update relevant cross-references
5. Include code examples
6. Add to learning path if applicable

---

## 🎉 Summary

This documentation provides:

✅ **Complete Architecture** - Understand how everything works
✅ **Implementation Guides** - Step-by-step instructions
✅ **API Reference** - Detailed technical documentation
✅ **Code Examples** - Real-world implementations
✅ **Best Practices** - Security, performance, maintainability
✅ **Quick Reference** - Fast lookups for common tasks

---

**Happy Coding!** 🚀

*Last Updated: 2025-10-28*
