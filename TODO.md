# TODO List for WP App Core Plugin

## TODO-1207: Platform Staff CRUD & DataTable ✅ COMPLETED

**Status**: ✅ COMPLETED (Ready for testing)
**Created**: 2025-10-19
**Completed**: 2025-10-19

**Summary**: Complete CRUD and DataTable system for Platform Staff management dengan full MVC architecture.

**Key Deliverables**:
- ✅ PlatformCacheManager (generic cache untuk semua platform entities)
- ✅ PlatformStaffModel (CRUD, DataTable, Statistics, Employee ID generation)
- ✅ PlatformStaffValidator (permission checks, data validation)
- ✅ PlatformStaffController (AJAX handlers, menu registration, assets)
- ✅ 4 View Templates (dashboard, left panel, right panel, no access)
- ✅ 2 JavaScript files (main + datatable with server-side processing)
- ✅ 2 CSS files (main + datatable styling)
- ✅ Integration dengan wp-app-core.php

**Features**:
- DataTable dengan server-side processing (pagination, search, sorting, filtering)
- Add/Edit/Delete staff dengan modal form
- Right panel untuk detail staff
- Statistics dashboard (total staff, recent hires, departments)
- Department-based filtering
- Role-based permission checks (view, create, edit, delete)
- Cache management untuk optimasi performa
- Toast notifications untuk user feedback
- Responsive design (mobile-friendly)

**Files Created**: 12 new files + 1 update
- 1 Cache Manager (generic untuk semua platform)
- 3 MVC components (Model, Validator, Controller)
- 4 View templates
- 2 JavaScript files
- 2 CSS files

**Database Structure**:
Table `wp_app_platform_staff` dengan fields:
- id, user_id, employee_id (unique), full_name
- department, hire_date, phone
- created_at, updated_at

**Next Steps**: Testing CRUD operations, DataTable functionality, dan permission checks

---

## TODO-1206: Platform Settings Implementation ✅ COMPLETED

**Status**: ✅ COMPLETED (Ready for production)

**Summary**: Complete platform settings system with 7 tabs for managing platform-level configurations (company info, email, permissions, security, demo data).

**Key Deliverables**:
- ✅ 6 Settings Models (Platform, Email, Permission, 3x Security)
- ✅ 7 Tab Views (General, Email, Permissions, Demo Data, 3x Security tabs)
- ✅ AJAX Handlers (save, reset, demo data generation)
- ✅ Platform Staff Demo Data System (20 users, ID 230-249)
- ✅ Reset to Default functionality for all security tabs
- ✅ Comprehensive validation & user feedback
- ✅ Conditional field display & real-time validation
- ✅ MVC compliance & code optimization

**Bug Fixes**:
- ✅ Fixed checkbox not saving (3 issues: boolean handling, model re-instantiation, cache not cleared)
- ✅ Fixed settings group name mismatch
- ✅ Fixed permission matrix fatal error

**Files Created**: 40+ files (models, views, controllers, assets, database, documentation)

**Description**: Implementasi lengkap Platform Settings untuk wp-app-core plugin. Settings ini untuk mengatur hal-hal platform-level (company info, email, permissions, security) bukan operational marketplace yang sudah diatur di plugin lain.

**Key Features**:
- General/Company Settings (info, contact, branding, regional)
- Email & Notifications (SMTP, templates, notification preferences)
- Platform Permissions (7 groups untuk platform staff)
- Security Settings (Authentication, Session, Policy & Audit)

**Platform Permission Groups** (7 Groups):
1. Platform Management - Dashboard, settings, system config
2. User & Role Management - Platform staff users
3. Tenant Management - Customer/branch approval & management
4. Financial & Billing - Pricing, payments, invoices
5. Support & Helpdesk - Tickets, FAQ, announcements
6. Reports & Analytics - Analytics, audit logs
7. Content & Resources - Documentation, templates

**Important: WordPress Administrator vs Platform Super Admin**

TIDAK SAMA! Dua role yang berbeda:

| Aspek | WordPress Administrator | Platform Super Admin |
|-------|------------------------|---------------------|
| **Source** | WordPress Core (built-in) | wp-app-core Plugin (custom) |
| **Role Slug** | `administrator` | `platform_super_admin` |
| **User ID 1** | ✅ Biasanya Ya | ❌ Tidak otomatis |
| **Di Permission Matrix** | ❌ TIDAK muncul | ✅ MUNCUL (editable) |
| **Auto Full Access** | ✅ Ya (WordPress core) | ❌ Harus di-set via matrix |
| **Icon** | - | 🔧 dashicons-admin-generic |
| **Capabilities** | Tidak bisa diubah | Bisa dikonfigurasi |

**WordPress Administrator**:
- Role bawaan WordPress, biasanya user ID 1 (site owner/developer)
- Sudah memiliki ALL capabilities otomatis (WordPress core behavior)
- TIDAK ditampilkan di permission matrix karena sudah punya akses penuh
- Tidak bisa dimodifikasi permission-nya melalui matrix
- Untuk site owner, developer, full admin WordPress

**Platform Super Admin** (7 Platform Roles):
1. `platform_super_admin` - Full platform access (perlu di-set)
2. `platform_admin` - Platform operations & tenant management
3. `platform_manager` - Operations oversight & analytics
4. `platform_support` - Customer support & helpdesk
5. `platform_finance` - Financial operations & billing
6. `platform_analyst` - Analytics & reports
7. `platform_viewer` - View-only access

- Role custom yang dibuat oleh plugin ini
- Harus di-assign capabilities melalui permission matrix
- DITAMPILKAN di permission matrix untuk konfigurasi
- Bisa dimodifikasi permission-nya sesuai kebutuhan
- Untuk platform staff yang bukan WordPress site owner
- Segregation of duties - pisahkan WordPress admin dari platform staff

**Use Case Example**:
```
Website Owner (User ID 1):
├── WordPress Role: administrator
└── Full access otomatis, tidak perlu setting matrix

Platform Staff (User ID 125):
├── WordPress Role: platform_super_admin (atau role lainnya)
├── Harus di-set capabilities via permission matrix
└── Bisa dibatasi sesuai kebutuhan bisnis
```

**Files Created**:
- `/wp-app-core/src/Models/Settings/PlatformSettingsModel.php`
- `/wp-app-core/src/Models/Settings/PlatformPermissionModel.php`
- `/wp-app-core/src/Models/Settings/EmailSettingsModel.php`
- `/wp-app-core/src/Models/Settings/SecurityAuthenticationModel.php`
- `/wp-app-core/src/Models/Settings/SecuritySessionModel.php`
- `/wp-app-core/src/Models/Settings/SecurityPolicyModel.php`
- `/wp-app-core/src/Controllers/PlatformSettingsController.php`
- `/wp-app-core/includes/class-role-manager.php`
- `/wp-app-core/includes/class-activator.php` (updated)
- `/wp-app-core/includes/class-deactivator.php` (updated)
- `/wp-app-core/src/Views/templates/settings/settings-page.php`
- `/wp-app-core/src/Views/templates/settings/tab-general.php`
- `/wp-app-core/src/Views/templates/settings/tab-email.php`
- `/wp-app-core/src/Views/templates/settings/tab-permissions.php`
- `/wp-app-core/src/Views/templates/settings/tab-demo-data.php`
- `/wp-app-core/src/Views/templates/settings/tab-security-authentication.php`
- `/wp-app-core/src/Views/templates/settings/tab-security-session.php`
- `/wp-app-core/src/Views/templates/settings/tab-security-policy.php`
- `/wp-app-core/assets/css/settings/settings.css`
- `/wp-app-core/assets/js/settings/settings.js`
- `/wp-app-core/assets/css/settings/permissions-tab-style.css`
- `/wp-app-core/assets/js/settings/permissions-tab-script.js`
- `/wp-app-core/assets/css/settings/demo-data-tab-style.css`
- `/wp-app-core/assets/js/settings/platform-demo-data-tab-script.js`
- `/wp-app-core/assets/css/settings/security-authentication-tab-style.css`
- `/wp-app-core/assets/js/settings/security-authentication-tab-script.js`
- `/wp-app-core/assets/css/settings/security-session-tab-style.css`
- `/wp-app-core/assets/js/settings/security-session-tab-script.js`
- `/wp-app-core/assets/css/settings/security-policy-tab-style.css`
- `/wp-app-core/assets/js/settings/security-policy-tab-script.js`
- `/wp-app-core/src/Database/Tables/PlatformStaffDB.php` (Review-01)
- `/wp-app-core/src/Database/Installer.php` (Review-01)
- `/wp-app-core/src/Database/Demo/Data/PlatformUsersData.php` (Review-01)
- `/wp-app-core/src/Database/Demo/Data/PlatformDemoData.php` (Review-01)
- `/wp-app-core/src/Database/Demo/WPUserGenerator.php` (Review-01)
- `/wp-app-core/TODO/TODO-1206-platform-settings-implementation.md`
- `/wp-app-core/TODO/REVIEW-01-platform-user-generation.md` (Review-01)

**Files Modified**:
- `/wp-app-core/src/Controllers/MenuManager.php` (migrated to WPAppCore namespace)
- `/wp-app-core/src/Controllers/PlatformSettingsController.php` (added tab-specific asset loading, demo-data AJAX handlers, dev settings, platform staff AJAX - Review-01)
- `/wp-app-core/src/Views/templates/settings/settings-page.php` (added demo-data tab to navigation)
- `/wp-app-core/src/Views/templates/settings/tab-permissions.php` (H2 context header, separated sections, MVC compliance)
- `/wp-app-core/src/Views/templates/settings/tab-demo-data.php` (added Platform Staff Demo Data section - Review-01)
- `/wp-app-core/src/Models/Settings/PlatformPermissionModel.php` (added getDefaultCapabilitiesForRole, getCapabilityDescriptions methods)
- `/wp-app-core/includes/class-role-manager.php` (removed getDefaultCapabilities and initializeRoleCapabilities methods)
- `/wp-app-core/includes/class-activator.php` (added database installation call - Review-01)
- `/wp-app-core/includes/class-deactivator.php` (updated to use development settings option)
- `/wp-app-core/assets/js/settings/platform-demo-data-tab-script.js` (added platform staff handlers - Review-01)
- `/wp-app-core/wp-app-core.php` (use MenuManager)

See: [TODO/TODO-1206-platform-settings-implementation.md](TODO/TODO-1206-platform-settings-implementation.md)

---

## TODO-1203 CSS Loading Fix
- [x] Analyze CSS loading issue (wp-includes instead of plugin CSS)
- [x] Add priority 20 to admin_enqueue_scripts hooks
- [x] Add 'admin-bar' dependency to wp_enqueue_style
- [x] Document load order and cascade strategy
- [x] Create TODO-1203-review-08-css-loading-fix.md
- [x] Sync to TODO.md

**Status**: ✅ COMPLETED (needs browser testing)

**Description**: Fix masalah CSS plugin tidak ter-load. Browser masih menggunakan `wp-includes/css/admin-bar.css` (WordPress core) instead of plugin's custom CSS.

**Problem**:
- Plugin CSS tidak ter-load di browser
- Yang muncul hanya wp-includes/css/admin-bar.css
- Plugin CSS file exists tapi tidak di-enqueue dengan benar

**Solution**:
1. **Priority 20**: Load after WordPress core (default 10)
2. **Dependency**: Declare dependency on core 'admin-bar' CSS
3. **Cascade**: Our styles override core via proper load order

**Technical Details**:
```php
// Before: Priority 10 (default), no dependency
add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
wp_enqueue_style('wp-app-core-admin-bar', $url, [], $version);

// After: Priority 20, with dependency
add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles'], 20);
wp_enqueue_style('wp-app-core-admin-bar', $url, ['admin-bar'], $version);
```

**Expected Result**:
Browser should load BOTH CSS files:
1. wp-includes/css/admin-bar.css (core)
2. wp-content/plugins/wp-app-core/assets/css/admin-bar/admin-bar-style.css (plugin)

**Files Modified**:
- `/wp-app-core/includes/class-dependencies.php`

**Files Created**:
- `/wp-app-core/TODO/TODO-1203-review-08-css-loading-fix.md`

See: [TODO/TODO-1203-review-08-css-loading-fix.md](TODO/TODO-1203-review-08-css-loading-fix.md)

---

## TODO-1202 Review-07: Assets Reorganization
- [x] Rename CSS dari admin-bar-style.css ke admin-bar.css
- [x] Buat file JS admin-bar/admin-bar.js
- [x] Update class-dependencies.php (CSS path & JS enqueue)
- [x] Tambahkan wp_localize_script untuk future AJAX
- [x] Create TODO-1202-review-07-assets-reorganization.md
- [x] Sync to TODO.md

**Status**: ✅ COMPLETED

**Description**: Reorganisasi struktur assets plugin wp-app-core untuk mengikuti pola yang sama dengan wp-agency. Menerapkan naming convention yang konsisten dan mempersiapkan struktur untuk future interactive features.

**Perubahan**:
- File CSS: `admin-bar-style.css` → `admin-bar.css`
- File JS: Created `admin-bar.js` dengan basic structure
- Dependencies: Updated enqueue paths dan activated JS
- Localization: Added wpAppCoreData object untuk AJAX support

**Pola yang Diikuti**:
- wp-agency: `agency/agency-style.css` + `agency/agency-script.js`
- wp-app-core: `admin-bar/admin-bar.css` + `admin-bar/admin-bar.js`

**Files Modified**:
- `/wp-app-core/includes/class-dependencies.php`
- `/wp-app-core/assets/css/admin-bar/admin-bar-style.css` → `admin-bar.css`

**Files Created**:
- `/wp-app-core/assets/js/admin-bar/admin-bar.js`
- `/wp-app-core/TODO/TODO-1202-review-07-assets-reorganization.md`

See: [TODO/TODO-1202-review-07-assets-reorganization.md](TODO/TODO-1202-review-07-assets-reorganization.md)

---

## TODO-1201: WP App Core Admin Bar Integration
- [x] Create class-admin-bar-info.php for generic admin bar system
- [x] Create integration layer for wp-agency (class-app-core-integration.php)
- [x] Update wp-customer integration (already exists)
- [x] **Review-02**: Add fallback for users with agency role but no entity link
- [x] **Review-03**: False alarm - customer users admin bar (reverted)
- [x] **Review-04**: Revert Review-03 changes
- [x] **Review-05**: Fix hardcoded values and wrong terminology (branch � division)
- [x] **Review-06**: Update init() pattern and add comprehensive debug logging
- [x] **Review-07**: CRITICAL FIX - Reorder query priority (check employee FIRST)
- [x] **Review-08**: MAJOR IMPROVEMENT - Complete query rewrite with jurisdictions
- [x] **Review-09**: CRITICAL OPTIMIZATION - Replace 3 queries with 1 single query
- [x] **Review-10**: Filter function explanation and documentation
- [x] Create TODO-1201-wp-app-core-admin-bar-integration.md
- [x] Sync to TODO.md

**Status**:  COMPLETED

**Description**: Generic admin bar integration system untuk wp-app-core yang dapat digunakan oleh multiple plugins (wp-customer, wp-agency, dll) untuk menampilkan user information di WordPress admin bar.

**Key Features**:
- Generic plugin registration system
- Dynamic filter for role name translation
- Support for multiple plugins without conflicts
- Comprehensive debug logging
- Optimal database performance (1 query instead of 3)
- Complete data retrieval (jurisdictions, email, capabilities)

**Performance**:
- Query reduction: 3 queries � 1 query (67% reduction)
- Execution speed: ~3x faster for common case (employees)
- Code reduction: Removed ~150 lines of redundant code

**Fixes Applied**:

- **Review-02**: Added fallback logic untuk user dengan agency role tanpa entity link 

- **Review-05**: Fixed hardcoded values and terminology
  - Removed hardcoded "Dinas Tenaga Kerja" dan "DISNAKER"
  - Changed `branch_name`/`branch_type` to `division_name`/`division_type`
  - Agency owner now shows actual first division from database
  - Fallback uses generic "Agency System" instead of hardcoded name 

- **Review-06**: Updated init() method pattern
  - Changed from loop-based to explicit add_filter for each role
  - Matches pattern with wp-customer
  - Added comprehensive debug logging for all queries
  - Created debug-logging-guide.md 

- **Review-07**: CRITICAL FIX - Query order optimization
  - **Problem**: Was checking agency owner FIRST, but not all employees are owners
  - **Fix**: Reordered to check EMPLOYEE first (most common case)
  - **New Order**: Employee � Division Admin � Agency Owner � Fallback
  - **Benefit**: 3x performance improvement for employees 

- **Review-08**: MAJOR IMPROVEMENT - Complete query rewrite
  - **Problem**: Incomplete data retrieval, missing jurisdiction information
  - **Fix**: Comprehensive query with INNER/LEFT JOIN, GROUP_CONCAT, MAX()
  - **New Fields**: division_code, jurisdiction_codes, is_primary_jurisdiction
  - **Benefit**: Single query gets ALL related data 

- **Review-09**: CRITICAL OPTIMIZATION - Single query replaces all
  - **Problem**: Using 3 separate queries (employee, division admin, owner) inefficiently
  - **Realization**: wp_app_agency_employees table already has user_id, division_id, agency_id
  - **Fix**: Replaced with ONE comprehensive query with all JOINs
  - **Added**: JOIN with wp_users (email) and wp_usermeta (capabilities)
  - **Benefits**:
    - **3 queries � 1 query** (67% reduction)
    - Much faster execution time
    - Less database load
    - Cleaner code (removed ~150 lines)
    - Added user_email and capabilities data 

**Files Created**:
- `/wp-app-core/includes/class-admin-bar-info.php`
- `/wp-agency/includes/class-app-core-integration.php` (v1.5.0)
- `/wp-app-core/claude-chats/debug-logging-guide.md`
- `/wp-app-core/TODO/TODO-1201-wp-app-core-admin-bar-integration.md`

**Files Modified**:
- `/wp-customer/includes/class-app-core-integration.php` (already exists)
- `/wp-agency/TODO.md`

**Files to Keep** (Backward Compatibility):
- `/wp-customer/includes/class-admin-bar-info.php`
- `/wp-customer/src/Views/templates/customer-employee/partials/_customer_employee_profile_fields.php`

See: [TODO/TODO-1201-wp-app-core-admin-bar-integration.md](TODO/TODO-1201-wp-app-core-admin-bar-integration.md)

---

## TODO-1204: Method Migration to AdminBarModel
- [x] Analyze methods in AgencyEmployeeModel
- [x] Create AdminBarModel.php with generic methods
- [x] Update AgencyEmployeeModel to use AdminBarModel
- [x] Remove old helper methods from AgencyEmployeeModel
- [x] Create TODO-1204-method-migration.md
- [x] Sync to TODO.md

**Status**: ✅ COMPLETED (needs browser testing)

**Description**: Memindahkan method yang bersifat global scope dari AgencyEmployeeModel.php ke AdminBarModel.php untuk separation of concerns dan reusability.

**Approach**: Option 2 - Proper Separation
- Keep: `getUserInfo()` in AgencyEmployeeModel (data access layer)
- Move: Helper methods to AdminBarModel as generic utilities

**Methods Migrated**:
1. `getRoleNamesFromCapabilities()` → Made generic with parameters
2. `getPermissionNamesFromUserId()` → Made generic with parameters

**Changes**:
- Created: `/wp-app-core/src/Models/AdminBarModel.php` (256 lines)
- Modified: `/wp-agency/src/Models/Employee/AgencyEmployeeModel.php` (removed 135 lines)
- No change: `/wp-agency/includes/class-app-core-integration.php` (backward compatible)

**Benefits**:
- ✅ Separation of concerns (model vs utilities)
- ✅ Reusable by all plugins (customer, agency, future)
- ✅ Generic implementation (no hardcoded dependencies)
- ✅ Backward compatible (no breaking changes)
- ✅ Better maintainability

**Files Created**:
- `/wp-app-core/src/Models/AdminBarModel.php`
- `/wp-app-core/TODO/TODO-1204-method-migration.md`
- `/wp-app-core/TODO/PROPOSAL-1204-method-migration.md`
- `/wp-app-core/TODO/ANALYSIS-1204-review-01.md`

**Files Modified**:
- `/wp-agency/src/Models/Employee/AgencyEmployeeModel.php`

See: [TODO/TODO-1204-method-migration.md](TODO/TODO-1204-method-migration.md)

---
