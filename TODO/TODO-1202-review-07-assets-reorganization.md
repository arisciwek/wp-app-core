# TODO-1203 Review-07: Assets Reorganization wp-app-core

## Deskripsi
Reorganisasi struktur assets plugin wp-app-core untuk mengikuti pola yang sama dengan wp-agency.

## Tanggal
2025-01-18

## Status
✅ Selesai

## Perubahan yang Dilakukan

### 1. Struktur Assets (Sebelum)
```
/wp-app-core/assets/
├── css/
│   └── admin-bar/
│       └── admin-bar-style.css
└── js/
    └── admin-bar/
        (kosong)
```

### 2. Struktur Assets (Sesudah)
```
/wp-app-core/assets/
├── css/
│   └── admin-bar/
│       └── admin-bar.css          ← Renamed dari admin-bar-style.css
└── js/
    └── admin-bar/
        └── admin-bar.js            ← File baru
```

### 3. Pola Penamaan
Mengikuti pola wp-agency:
- **wp-agency**: `agency/agency-style.css` + `agency/agency-script.js`
- **wp-app-core**: `admin-bar/admin-bar.css` + `admin-bar/admin-bar.js`

Format: `[feature-name]/[feature-name].[css|js]`

## File yang Dimodifikasi

### 1. includes/class-dependencies.php
**Path:** `/wp-app-core/includes/class-dependencies.php`

**Perubahan:**
```php
// CSS Enqueue - Updated path
wp_enqueue_style(
    'wp-app-core-admin-bar',
    WP_APP_CORE_PLUGIN_URL . 'assets/css/admin-bar/admin-bar.css',  // ← Changed
    [],
    $this->version
);

// JS Enqueue - Activated (was commented)
wp_enqueue_script(
    'wp-app-core-admin-bar',
    WP_APP_CORE_PLUGIN_URL . 'assets/js/admin-bar/admin-bar.js',    // ← Added
    ['jquery'],
    $this->version,
    true
);

// Localize script - Added for future AJAX
wp_localize_script(
    'wp-app-core-admin-bar',
    'wpAppCoreData',
    [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wp_app_core_nonce'),
        'debug' => (defined('WP_DEBUG') && WP_DEBUG)
    ]
);
```

### 2. assets/css/admin-bar/admin-bar.css
**Path:** `/wp-app-core/assets/css/admin-bar/admin-bar.css`

**Perubahan:**
- File direname dari `admin-bar-style.css` menjadi `admin-bar.css`
- Konten tidak berubah

### 3. assets/js/admin-bar/admin-bar.js
**Path:** `/wp-app-core/assets/js/admin-bar/admin-bar.js`

**Status:** File baru

**Fitur:**
- Basic structure dengan jQuery wrapper
- Prepared untuk interactive features
- Event binding placeholder
- Dropdown behavior enhancement
- Reserved method untuk future AJAX refresh

## Referensi

### File Referensi wp-agency
- `/wp-agency/includes/class-dependencies.php`
- `/wp-agency/assets/css/agency/agency-style.css`
- `/wp-agency/assets/js/agency/agency-script.js`

### File Plugin Utama
- `/wp-app-core/wp-app-core.php` (sudah benar, tidak perlu diubah)

## Manfaat Reorganisasi

1. **Konsistensi**: Struktur assets seragam dengan wp-agency
2. **Maintainability**: Lebih mudah maintain dan extend
3. **Scalability**: Mudah menambah feature baru dengan pola yang sama
4. **Naming Convention**: Penamaan file lebih konsisten dan predictable
5. **Future Ready**: JavaScript structure siap untuk fitur interactive

## Testing Checklist

- [x] File CSS ter-rename dengan benar
- [x] File JS terbuat dengan struktur yang benar
- [x] class-dependencies.php updated
- [x] CSS path updated di enqueue
- [x] JS enqueue activated
- [x] Localize script added
- [ ] Browser testing - verify CSS loaded
- [ ] Browser testing - verify JS loaded
- [ ] Console check - no errors
- [ ] Admin bar display - masih berfungsi

## Notes untuk Developer

1. **Cache Clearing**: Setelah perubahan ini, clear cache WordPress:
   - Object cache
   - Browser cache
   - Plugin cache (jika ada)

2. **Backward Compatibility**:
   - Tidak ada breaking changes
   - File hanya di-rename, konten tidak berubah
   - Dependencies tetap sama

3. **Future Enhancement**:
   - JavaScript file siap untuk AJAX functionality
   - wpAppCoreData object tersedia untuk JS
   - Nonce sudah di-generate

## Related Tasks
- Task-2063: Menambah role pada agency employee
- Review-01 s/d Review-06: Improvements lainnya

## Verification Commands

```bash
# Check file structure
tree /wp-app-core/assets/

# Verify CSS file
ls -l /wp-app-core/assets/css/admin-bar/

# Verify JS file
ls -l /wp-app-core/assets/js/admin-bar/

# Check dependencies class
cat /wp-app-core/includes/class-dependencies.php
```

## Completion Criteria
✅ Semua file assets mengikuti pola wp-agency
✅ Dependencies class updated
✅ File structure verified
✅ Documentation completed
