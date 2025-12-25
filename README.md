# WP App Core

Plugin inti untuk mengelola fitur global aplikasi marketplace WordPress. Plugin ini menyediakan User Profile Management, Membership System, dan fitur-fitur shared lainnya yang dapat digunakan oleh berbagai plugin (customer, agency, dll).

## Deskripsi

WP App Core adalah plugin utama yang mengelola aturan global untuk seluruh sistem yang tidak diatur pada masing-masing plugin. Plugin ini dibuat untuk menghindari duplikasi kode dan menyediakan fitur shared yang dapat diakses oleh plugin lain seperti wp-customer, wp-agency, dan plugin marketplace lainnya.

## Versi

- **Current Version:** 1.1.0 (2025-10-28)
- **Requires WordPress:** 5.0 or higher
- **PHP Version:** 7.4 or higher

**Changelog v1.1.0:**
- ✅ Container Structure Improvements (TODO-1187)
- ✅ Full width pattern using negative margins
- ✅ Perfect alignment across all dashboard containers
- ✅ Foundation for all dashboard pages in the application

## Fitur - Phase 1

### 1. User Profile Management ✅

Plugin ini menyediakan sistem user profile management yang generic dan dapat digunakan oleh berbagai plugin:

- **Profile Fields Template**: Template generic untuk menampilkan profile fields yang dapat di-extend oleh plugin lain

> **Note**: Admin Bar Info Display sudah dipindahkan ke plugin standalone **wp-admin-bar** untuk modularitas yang lebih baik.

### 2. Plugin Integration System

Sistem yang memungkinkan plugin lain untuk integrate dengan wp-app-core.

### 3. DataTable Panel System ✅ (v1.1.0)

Sistem reusable untuk membuat halaman dashboard admin dengan panel kiri-kanan (Perfex CRM style) yang konsisten di seluruh aplikasi:

**Fitur Utama:**
- **Left/Right Panel Layout**: DataTable di kiri, detail panel di kanan dengan smooth transitions
- **WordPress-style Tab System**: Tab navigation dengan priority-based sorting
- **Statistics Cards**: Reusable stat boxes dengan loading states
- **Smooth Animations**: Anti-flicker pattern dengan smooth transitions
- **Hash Navigation**: Deep linking support (#entity-123&tab=details)
- **Full Hook System**: Extensible via WordPress filters/actions
- **JavaScript Event System**: Custom events untuk integrasi
- **Consistent Container Structure**: Unified spacing dan alignment

**Full Width Pattern (v1.1.0 - TODO-1187):**

Semua container dashboard menggunakan **negative margins** untuk memanfaatkan lebar penuh yang tersedia:

```css
/* Pattern untuk semua container */
.wpapp-page-header-container   { margin: 0 -15px 0 -15px; }
.wpapp-statistics-container    { margin: 20px -15px 20px -15px; }
.wpapp-filters-container       { margin: 0 -15px 20px -15px; }
.wpapp-datatable-container     { margin: 0 -15px 20px -15px; }
```

**Mengapa -15px margin?**
- WordPress `.wrap` class memiliki 20px padding kiri-kanan secara default
- Negative 15px margin memperluas container untuk memanfaatkan lebar penuh
- Menciptakan batas yang konsisten di semua section dashboard
- Menghilangkan ruang terbuang di sisi kiri dan kanan

**Benefit:**
- ✅ Perfect alignment di semua container boundaries
- ✅ Tidak ada ruang horizontal terbuang
- ✅ Foundation konsisten untuk semua halaman dashboard dalam aplikasi
- ✅ Mudah dimaintain (fix once, apply everywhere)
- ✅ UX konsisten di semua plugin (wp-customer, wp-agency, dll)

**Quick Start:**

```php
use WPAppCore\Views\DataTable\Templates\DashboardTemplate;

// Render dashboard dengan semua fitur
DashboardTemplate::render([
    'entity' => 'customer',
    'title' => __('Customers', 'wp-customer'),
    'ajax_action' => 'get_customer_details',
    'has_stats' => true,
    'has_tabs' => true,
]);
```

**Dokumentasi Lengkap:**
- [Panel System README](src/Views/DataTable/README.md) - Complete guide (1100+ lines)
- [Quick Reference](src/Views/DataTable/QUICK-REFERENCE.md) - Cheatsheet (400+ lines)
- [Documentation Index](docs/INDEX.md) - Complete documentation map

## Struktur File

```
wp-app-core/
├── wp-app-core.php                 # Main plugin file
├── includes/
│   └── class-autoloader.php        # PSR-4 autoloader
├── src/
│   ├── Views/
│   │   └── templates/
│   │       └── user/
│   │           └── _user_profile_fields.php
│   ├── API/                        # API untuk plugin lain (future)
│   ├── Models/                     # Data models (future)
│   ├── Controllers/                # Controllers (future)
│   ├── Database/
│   │   └── Tables/                 # Database table classes (future)
│   └── Validators/                 # Data validators (future)
├── assets/
│   ├── css/                        # CSS files
│   └── js/                        # JavaScript files (future)
├── cron/                          # Cron jobs (future)
└── languages/                     # Translation files
```

## Instalasi

1. Upload folder `wp-app-core` ke direktori `/wp-content/plugins/`
2. Aktivasi plugin melalui menu 'Plugins' di WordPress
3. Plugin akan otomatis mendeteksi plugin lain yang terintegrasi

## Integrasi dengan Plugin Lain

### Untuk wp-customer Plugin

File integrasi sudah dibuat: `/wp-customer/includes/class-app-core-integration.php`

Plugin wp-customer akan otomatis terintegrasi dengan wp-app-core jika kedua plugin aktif.

#### Cara Kerja Integrasi:

1. **Registrasi Plugin**
   - wp-customer mendaftarkan diri ke wp-app-core
   - Menyediakan callback untuk mendapatkan user info

2. **User Info Callback**
   - Mengambil data dari database customer
   - Menyediakan informasi branch, position, department
   - Menggunakan cache untuk performa

3. **Role Name Mapping**
   - Mapping role slugs ke display names
   - Support untuk semua customer roles

### Untuk Plugin Lain (Agency, Customer, dll)

Plugin lain dapat menggunakan hooks dan filters yang disediakan oleh wp-app-core untuk custom functionality.

> **Note**: Untuk admin bar integration, gunakan plugin **wp-admin-bar** yang menyediakan hook system yang lebih modular.

## Filter dan Action Hooks

### Actions

- `wp_app_core_init` - Dipanggil saat plugin diinisialisasi
- `wp_app_core_before_profile_fields` - Sebelum menampilkan profile fields
- `wp_app_core_after_profile_fields` - Setelah menampilkan profile fields
- `wp_app_core_after_profile_section` - Setelah seluruh profile section

### Filters

- `wp_app_core_role_name_{role_slug}` - Get role display name
- `wp_app_core_user_profile_data` - Memodifikasi user profile data sebelum display
- `wp_app_core_profile_additional_info_title` - Judul section additional info
- `wp_app_core_profile_entity_label` - Label untuk entity field
- `wp_app_core_profile_branch_label` - Label untuk branch field
- `wp_app_core_profile_position_label` - Label untuk position field
- `wp_app_core_profile_department_label` - Label untuk department field
- `wp_app_core_profile_roles_title` - Judul section roles & capabilities

## Roadmap - Phase Selanjutnya

### Phase 2 - Membership Management
- ✅ Membership Levels/Tiers
- ✅ Membership Assignment
- ✅ Feature Access Control
- ✅ Migration dari wp-customer

### Phase 3 - Payment & Invoice
- ⏳ Centralized Invoice System
- ⏳ Payment Gateway Integration
- ⏳ Automatic Membership Renewal

### Phase 4 - Downgrade System
- ⏳ Cron Job untuk Auto-Downgrade
- ⏳ Grace Period Handling
- ⏳ Notification Integration

### Phase 5 - Advanced Features
- ⏳ API Management
- ⏳ Reporting & Analytics
- ⏳ Marketplace Features

## Testing

### Manual Testing Checklist

1. **Profile Fields**
   - [ ] Buka user profile page
   - [ ] Pastikan additional fields ditampilkan
   - [ ] Verifikasi roles dan capabilities

3. **Plugin Integration**
   - [ ] Aktivasi wp-app-core dan wp-customer
   - [ ] Verifikasi tidak ada error
   - [ ] Test dengan user berbeda roles

## Changelog

### Version 1.0.0 (2025-01-18)
- Initial release
- User Profile Management (Phase 1)
- Profile Fields Template (generic version)
- Integration system untuk plugin lain
- PSR-4 autoloader
- **Note**: Admin Bar functionality dipindahkan ke plugin standalone wp-admin-bar

## Credits

- **Author:** arisciwek
- **License:** GPL v2 or later

## Support

Untuk bug reports dan feature requests, silakan buka issue di repository project.
