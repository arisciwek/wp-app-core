# Review-01: Platform User Generation

**Date**: 2025-10-19
**Task**: TODO-1206 Platform Settings Implementation
**Subtask**: Generate Platform Demo Users

## Overview

Create demo user generation system for Platform staff users. This will generate WordPress users with platform roles (platform_super_admin, platform_admin, etc.) for testing and development purposes.

## Analysis of Existing System

### Current User ID Ranges (All Plugins)
| Plugin | Type | ID Range | Count | File |
|--------|------|----------|-------|------|
| wp-customer | Customer Owners | 2-11 | 10 | CustomerUsersData.php |
| wp-customer | Branch Admins | 12-69 | 58 | BranchUsersData.php |
| wp-customer | Employees | 70-129 | 60 | CustomerEmployeeUsersData.php |
| wp-agency | Agency Owners | 130-139 | 10 | AgencyUsersData.php |
| wp-agency | Division Admins | 140-169 | 30 | DivisionUsersData.php |
| wp-agency | Agency Employees | 170-229 | 60 | AgencyEmployeeUsersData.php |
| **wp-app-core** | **Platform Staff** | **230-249** | **20** | **PlatformUsersData.php** ⭐ NEW |

**Next Available ID**: 230 ✅ (as specified in task)

### Existing Name Collections (to avoid conflicts)

**CustomerUsersData**: Andi, Budi, Citra, Dewi, Eko, Fajar, Gita, Hari, Indra, Joko, Kartika, Lestari, Mawar, Nina, Omar, Putri, Qori, Rini, Sari, Tono, Umar, Vina, Wati, Yanto

**BranchUsersData**: Agus, Bayu, Dedi, Eka, Feri, Hadi, Imam, Jaka, Kiki, Lina, Maya, Nita, Oki, Pandu, Ratna, Sinta, Taufik, Udin, Vera, Wawan, Yudi, Zahra, Arif, Bella, Candra, Dika, Elsa, Faisal, Gani, Hilda, Irwan, Jihan, Kirana, Lukman, Mira, Nadia, Putra, Rani

**CustomerEmployeeUsersData**: Abdul, Amir, Anwar, Asep, Bambang, Bagas, Cahya, Cindy, Danu, Dimas, Erna, Erik, Farhan, Fitria, Galuh, Gema, Halim, Hendra, Indah, Iwan, Joko, Jenni, Khalid, Kania, Laras, Lutfi, Mulyadi, Marina, Novianti, Nur, Oky, Olivia, Prabu, Priska, Qomar, Qonita, Reza, Riana, Salim, Silvia, Teguh, Tiara, Usman, Umi, Vikri, Vivi, Wahyu, Widya, Yayan, Yesi, Zulkifli, Zainal, Ayu, Bima, Doni, Evi, Fitra, Gunawan, Hani

**AgencyUsersData**: Ahmad, Bambang, Cahyo, Darmawan, Edi, Farid, Guntur, Hasan, Irfan, Jaya, Kurnia, Lukman, Mahendra, Noval, Okta, Prasetyo, Qodir, Rahman, Setya, Teguh, Ujang, Vivian, Wibowo, Xavier

**DivisionUsersData**: Budi, Citra, Dani, Eko, Fajar, Gita, Hendra, Indah, Joko, Kartika, Lina, Mira, Nando, Omar, Putri, Raka, Siti, Tono, Usman, Vina, Winda, Yani, Zainal, Anton

**AgencyEmployeeUsersData**: Ade, Andra, Bintang, Bayu, Chandra, Dewi, Doni, Endang, Fikri, Gandi, Haryo, Haris, Ismail, Jaya, Krisna, Lestari, Maulana, Melati, Naufal, Nurul, Prima, Permata, Rini, Rizal, Santoso, Septian, Tari, Wulan, Yusuf, Zahra, Alam, Bunga, Dini, Erlangga, Farah, Gilang, Hani, Ilham, Jasmine, Khoirul, Liza, Manda, Nova, Panca, Qori, Sari, Ulfa, Vicky, Wira, Yoga, Zain, Bagus, Cici

### NEW Platform Users Name Collection (UNIQUE)

Created a completely unique name collection for Platform Users:

**PlatformUsersData**: Arman, Benny, Clara, Diana, Edwin, Felix, Grace, Helen, Ivan, Julia, Kevin, Laura, Marco, Nita, Oscar, Paula, Quinn, Rita, Steven, Tina, Victor, Wendy, Xavier, Yolanda, Zack

- 25 unique names, all different from existing collections
- Mix of international and Indonesian names for platform staff
- Easy to distinguish from customer/agency users
- Professional naming for platform administrators

## Platform Roles Distribution

Total: 20 users (ID 230-249)

| Role | Slug | Count | IDs |
|------|------|-------|-----|
| Platform Super Admin | platform_super_admin | 2 | 230-231 |
| Platform Admin | platform_admin | 3 | 232-234 |
| Platform Manager | platform_manager | 3 | 235-237 |
| Platform Support | platform_support | 4 | 238-241 |
| Platform Finance | platform_finance | 3 | 242-244 |
| Platform Analyst | platform_analyst | 3 | 245-247 |
| Platform Viewer | platform_viewer | 2 | 248-249 |

**Rationale**:
- More Support staff (customer-facing)
- Fewer Super Admins (high-privilege role)
- Balanced distribution across other roles
- Total 20 users fits well in ID range 230-249

## Database Schema

### New Table: `wp_app_platform_staff`

Since wp-app-core doesn't currently have a plugin-specific database (unlike wp-customer and wp-agency), we have two options:

**Option 1**: Store minimal data in wp_usermeta (simpler)
- Use WordPress user meta for platform staff data
- No new database table needed
- Store department, employee_id, etc. as user meta

**Option 2**: Create platform staff table (better for future)
- Create `wp_app_platform_staff` table
- Store structured data: employee_id, department, hire_date, etc.
- Follows pattern used by wp-customer and wp-agency
- Better for complex queries and reporting

**Recommendation**: Option 2 - Create dedicated table for consistency and future extensibility

```sql
CREATE TABLE wp_app_platform_staff (
    id bigint(20) UNSIGNED NOT NULL auto_increment,
    user_id bigint(20) UNSIGNED NOT NULL,
    employee_id varchar(20) NOT NULL,
    department varchar(50) NULL,
    hire_date date NULL,
    phone varchar(20) NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    UNIQUE KEY user_id (user_id),
    UNIQUE KEY employee_id (employee_id),
    KEY department_index (department)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Fields**:
- `id`: Primary key
- `user_id`: Link to WordPress users table
- `employee_id`: Unique employee identifier (e.g., PLT-001, PLT-002)
- `department`: Platform department (IT, Finance, Support, Management, etc.)
- `hire_date`: Date joined platform team
- `phone`: Contact phone number
- `created_at`, `updated_at`: Timestamps

## Implementation Plan

### Files to Create

1. **Database Schema**:
   - `/wp-app-core/src/Database/Tables/PlatformStaffDB.php` (NEW)
   - Update `/wp-app-core/src/Database/Installer.php` (ADD table)

2. **Demo Data**:
   - `/wp-app-core/src/Database/Demo/Data/PlatformUsersData.php` (NEW)
   - `/wp-app-core/src/Database/Demo/Data/PlatformDemoData.php` (NEW)
   - `/wp-app-core/src/Database/Demo/WPUserGenerator.php` (NEW or adapt from wp-customer)

3. **View Update**:
   - Update `/wp-app-core/src/Views/templates/settings/tab-demo-data.php` (ADD button)

4. **Controller Update**:
   - Update `/wp-app-core/src/Controllers/PlatformSettingsController.php` (ADD AJAX handler)

5. **Assets**:
   - Update `/wp-app-core/assets/js/settings/platform-demo-data-tab-script.js` (ADD click handler)

6. **Documentation**:
   - Update `/wp-app-core/TODO/TODO-1206-platform-settings-implementation.md`
   - Update `/wp-app-core/TODO.md`

### Demo User Data Structure

```php
public static $data = [
    // Super Admin (2 users)
    230 => ['id' => 230, 'username' => 'arman_benny', 'display_name' => 'Arman Benny',
            'roles' => ['platform_super_admin'], 'employee_id' => 'PLT-001', 'department' => 'Management'],
    231 => ['id' => 231, 'username' => 'clara_diana', 'display_name' => 'Clara Diana',
            'roles' => ['platform_super_admin'], 'employee_id' => 'PLT-002', 'department' => 'Management'],

    // Admin (3 users)
    232 => ['id' => 232, 'username' => 'edwin_felix', 'display_name' => 'Edwin Felix',
            'roles' => ['platform_admin'], 'employee_id' => 'PLT-003', 'department' => 'Operations'],
    // ... etc
];
```

### AJAX Handler Pattern

```php
public function handle_generate_platform_users() {
    check_ajax_referer('wp_app_core_settings', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    require_once plugin_dir_path(__FILE__) . '../Database/Demo/Data/PlatformDemoData.php';
    $result = PlatformDemoData::generate();

    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}
```

## Questions for User

1. **Department Options**: What departments should we assign platform staff to?
   - Suggested: Management, Operations, IT, Finance, Support, Analytics, HR

2. **Employee ID Format**: Use "PLT-XXX" format for platform staff?
   - Example: PLT-001, PLT-002, etc.

3. **Additional Fields**: Any other fields needed in platform staff profile?
   - Current: employee_id, department, hire_date, phone
   - Possible additions: title, manager_id, location, etc.

4. **Demo Data Button Label**: What should the button say?
   - Option 1: "Generate Platform Users"
   - Option 2: "Generate Platform Staff Demo Data"
   - Option 3: "Create Demo Platform Users"

## Next Steps

1. ✅ Create unique name collection for Platform Users
2. ⏳ Create database table schema (PlatformStaffDB.php)
3. ⏳ Create demo data files (PlatformUsersData.php, PlatformDemoData.php)
4. ⏳ Create WPUserGenerator utility
5. ⏳ Update demo-data tab view with button
6. ⏳ Add AJAX handler in controller
7. ⏳ Update JavaScript for button click handling
8. ⏳ Test generation process
9. ⏳ Update documentation

## Files Modified/Created Summary

### Created:
- `/wp-app-core/src/Database/Tables/PlatformStaffDB.php`
- `/wp-app-core/src/Database/Demo/Data/PlatformUsersData.php`
- `/wp-app-core/src/Database/Demo/Data/PlatformDemoData.php`
- `/wp-app-core/src/Database/Demo/WPUserGenerator.php`
- `/wp-app-core/TODO/REVIEW-01-platform-user-generation.md`

### Modified:
- `/wp-app-core/src/Database/Installer.php`
- `/wp-app-core/src/Views/templates/settings/tab-demo-data.php`
- `/wp-app-core/src/Controllers/PlatformSettingsController.php`
- `/wp-app-core/assets/js/settings/platform-demo-data-tab-script.js`
- `/wp-app-core/TODO/TODO-1206-platform-settings-implementation.md`
- `/wp-app-core/TODO.md`

---

**Status**: ⏳ Ready for Implementation
**Blocked By**: None
**Dependencies**: class-role-manager.php (already exists ✅)
