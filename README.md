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

- **Admin Bar Info Display**: Menampilkan informasi user di WordPress admin bar
  - Entity name (customer/agency)
  - Branch/office information
  - User roles
  - Detailed dropdown dengan informasi lengkap

- **Profile Fields Template**: Template generic untuk menampilkan profile fields yang dapat di-extend oleh plugin lain

### 2. Plugin Integration System

Sistem yang memungkinkan plugin lain untuk mendaftarkan diri dan menyediakan informasi user:

```php
// Di plugin lain (contoh: wp-customer)
WP_App_Core_Admin_Bar_Info::register_plugin('customer', [
    'roles' => ['customer', 'customer_admin', 'customer_branch_admin', 'customer_employee'],
    'get_user_info' => 'callback_function_to_get_user_info',
]);
```

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
│   ├── class-autoloader.php        # PSR-4 autoloader
│   └── class-admin-bar-info.php    # Admin bar info display
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
│   ├── css/
│   │   └── admin-bar.css          # Admin bar styling
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

### Untuk Plugin Lain (Agency, dll)

Buat file integrasi serupa dengan struktur berikut:

```php
<?php
class WP_Agency_App_Core_Integration {
    public static function init() {
        if (!class_exists('WP_App_Core_Admin_Bar_Info')) {
            return;
        }

        add_action('wp_app_core_register_admin_bar_plugins', [__CLASS__, 'register']);
    }

    public static function register() {
        WP_App_Core_Admin_Bar_Info::register_plugin('agency', [
            'roles' => ['agency', 'agency_admin', 'agency_employee'],
            'get_user_info' => [__CLASS__, 'get_user_info'],
        ]);
    }

    public static function get_user_info($user_id) {
        // Return array dengan struktur:
        // [
        //     'entity_name' => 'Agency Name',
        //     'entity_code' => 'AGN001',
        //     'branch_name' => 'Office Name',
        //     'position' => 'Position',
        //     'icon' => '🏢'
        // ]
    }
}
```

## Filter dan Action Hooks

### Actions

- `wp_app_core_init` - Dipanggil saat plugin diinisialisasi
- `wp_app_core_register_admin_bar_plugins` - Untuk mendaftarkan plugin ke admin bar system
- `wp_app_core_before_profile_fields` - Sebelum menampilkan profile fields
- `wp_app_core_after_profile_fields` - Setelah menampilkan profile fields
- `wp_app_core_after_profile_section` - Setelah seluruh profile section

### Filters

- `wp_app_core_should_display_admin_bar` - Override display admin bar
- `wp_app_core_admin_bar_user_info` - Memodifikasi user info untuk admin bar
- `wp_app_core_role_name_{role_slug}` - Get role display name
- `wp_app_core_admin_bar_key_capabilities` - Daftar capabilities yang ditampilkan
- `wp_app_core_admin_bar_detailed_info_html` - Memodifikasi HTML detail info
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

1. **Admin Bar Display**
   - [ ] Login sebagai customer user
   - [ ] Pastikan admin bar menampilkan entity dan branch name
   - [ ] Klik dropdown untuk melihat detailed info
   - [ ] Pastikan styling benar di desktop dan mobile

2. **Profile Fields**
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
- Admin Bar Info Display (migrated dari wp-customer)
- Profile Fields Template (generic version)
- Integration system untuk plugin lain
- CSS styling untuk admin bar
- PSR-4 autoloader

## Credits

- **Author:** arisciwek
- **License:** GPL v2 or later

## Support

Untuk bug reports dan feature requests, silakan buka issue di repository project.
