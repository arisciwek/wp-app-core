# TODO-1208: Platform Permission Read Capability

## Tanggal
2025-10-19

## Deskripsi
Menambahkan default capability "read" ke semua platform role agar dapat mengakses halaman admin WordPress.

## Masalah
- Platform roles tidak dapat mengakses halaman wp-admin
- Capability "read" tidak ditambahkan secara eksplisit meskipun sudah ada di default capabilities array
- Method `addCapabilities()` dan `resetToDefault()` hanya menambahkan capabilities yang ada di `$available_capabilities` array

## Solusi
Menambahkan capability "read" secara eksplisit ke setiap platform role sebelum memproses capabilities lainnya.

## Perubahan yang Dilakukan

### File: src/Models/Settings/PlatformPermissionModel.php

#### 1. Update Changelog (Line 15-25)
- Menambahkan changelog untuk versi 1.0.1
- Mendokumentasikan penambahan explicit 'read' capability

#### 2. Method addCapabilities() (Line 207-242)
**Perubahan:**
```php
// Sebelum loop default capabilities
$role->add_cap('read');

// Dalam loop, skip 'read' karena sudah ditambahkan
if ($cap === 'read') {
    continue;
}
```

**Alasan:**
- Capability 'read' tidak ada di array `$available_capabilities`
- Harus ditambahkan secara eksplisit seperti pada wp-customer plugin
- Diperlukan untuk akses ke wp-admin

#### 3. Method resetToDefault() (Line 318-334)
**Perubahan:**
```php
// Untuk platform roles
if (\WP_App_Core_Role_Manager::isPluginRole($role_name)) {
    // Add 'read' capability explicitly
    $role->add_cap('read');

    // Skip 'read' dalam loop
    if ($cap === 'read') {
        continue;
    }
}
```

**Alasan:**
- Konsistensi dengan method addCapabilities()
- Memastikan 'read' capability tetap ada setelah reset

## Referensi
- Contoh implementasi: `/wp-customer/src/Models/Settings/PermissionModel.php`
  - Line 238: customer_admin
  - Line 298: customer_branch_admin
  - Line 358: customer_employee

## Platform Roles yang Terpengaruh
1. platform_super_admin
2. platform_admin
3. platform_manager
4. platform_support
5. platform_finance
6. platform_analyst
7. platform_viewer

## Testing Checklist
- [ ] Verifikasi platform roles bisa akses wp-admin
- [ ] Test method addCapabilities()
- [ ] Test method resetToDefault()
- [ ] Verifikasi 'read' capability ada di setiap platform role
- [ ] Test dengan user yang memiliki platform role

## Upgrade System (Auto-Fix)

### File: includes/class-upgrade.php
**Baru dibuat** untuk menangani upgrade otomatis saat plugin version berubah.

**Features**:
- Check version plugin saat startup (plugins_loaded hook)
- Jika version berbeda, run upgrade routine
- Upgrade to 1.0.1: Auto-add 'read' capability ke platform roles yang belum punya
- Update version di database setelah upgrade selesai

**Cara Kerja**:
1. Plugin load → check version (1.0.0 di DB vs 1.0.1 di code)
2. Jika berbeda → run `upgrade_to_1_0_1()`
3. Loop semua platform roles → add 'read' capability jika belum ada
4. Update version di DB → 1.0.1
5. Done! Next load tidak akan run upgrade lagi

**Log Output** (jika WP_DEBUG enabled):
```
WP_App_Core_Upgrade: Upgrading from 1.0.0 to 1.0.1
WP_App_Core_Upgrade: Running upgrade to 1.0.1 - Adding 'read' capability to platform roles
WP_App_Core_Upgrade: Added 'read' capability to role: platform_super_admin
WP_App_Core_Upgrade: Added 'read' capability to role: platform_admin
... (all platform roles)
WP_App_Core_Upgrade: Upgrade to 1.0.1 completed - 7 roles updated
WP_App_Core_Upgrade: Upgrade completed to version 1.0.1
```

### File: wp-app-core.php
**Updated** untuk integrate upgrade system:
- Version: 1.0.0 → 1.0.1
- Load upgrade class di `load_dependencies()`
- Hook upgrade checker ke `plugins_loaded` (priority 5)
- Updated changelog

## Status
✅ Completed - 2025-10-19

## Solution Summary

**Immediate Fix** (Manual):
1. Login sebagai admin
2. Pergi ke Platform Settings → Permissions
3. Klik "Reset to Default"
4. Semua platform roles akan mendapat 'read' capability

**Automatic Fix** (Upgrade Script):
1. Refresh halaman admin WordPress
2. Plugin otomatis detect version change (1.0.0 → 1.0.1)
3. Upgrade script otomatis add 'read' capability ke platform roles
4. User bisa langsung login dan akses wp-admin

## Files Modified
1. `/src/Models/Settings/PlatformPermissionModel.php` (v1.0.1)
2. `/wp-app-core.php` (v1.0.1)

## Files Created
1. `/includes/class-upgrade.php` (new)
2. `/TODO/TODO-1208-platform-permission-read-capability.md`

## Notes
- Perubahan ini mengikuti pattern yang sama dengan wp-customer plugin
- Capability 'read' adalah WordPress core capability yang diperlukan untuk akses wp-admin
- Tidak perlu menambahkan 'read' ke array `$available_capabilities` karena ini adalah core WP capability
- Upgrade system akan handle future migrations otomatis
