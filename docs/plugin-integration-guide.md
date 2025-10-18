# WP App Core - Plugin Integration Guide

## Daftar Isi

1. [Pendahuluan](#pendahuluan)
2. [Arsitektur Sistem](#arsitektur-sistem)
3. [Persyaratan Plugin](#persyaratan-plugin)
4. [Implementasi Step-by-Step](#implementasi-step-by-step)
5. [API Reference](#api-reference)
6. [Struktur Data User Info](#struktur-data-user-info)
7. [Contoh Implementasi Lengkap](#contoh-implementasi-lengkap)
8. [Best Practices](#best-practices)
9. [Testing & Debugging](#testing--debugging)
10. [Troubleshooting](#troubleshooting)

---

## Pendahuluan

### Apa itu WP App Core?

**WP App Core** adalah plugin WordPress yang menyediakan sistem admin bar generic untuk menampilkan informasi user dari berbagai plugin (wp-customer, wp-agency, dll) di WordPress admin bar.

### Mengapa Menggunakan WP App Core?

- ✅ **Centralized**: Satu sistem untuk semua plugin, hindari duplikasi kode
- ✅ **Generic**: Dapat digunakan oleh berbagai jenis plugin (customer, agency, company, dll)
- ✅ **Extensible**: Plugin dapat menambahkan fitur custom melalui hooks dan filters
- ✅ **Performance**: Optimized dengan caching dan single query pattern
- ✅ **User-Friendly**: Display role names yang readable (bukan slugs)

### Plugin yang Sudah Terintegrasi

1. **wp-agency**: Menampilkan informasi agency, division, dan jurisdiction
2. **wp-customer**: Menampilkan informasi customer, branch, dan employee

---

## Arsitektur Sistem

### Plugin Registration Pattern

WP App Core menggunakan **plugin registration pattern** di mana setiap plugin mendaftar dirinya dengan menyediakan:
- **Plugin ID**: Identifier unik (e.g., `'agency'`, `'customer'`)
- **Role Slugs**: Daftar role slugs yang dimiliki plugin
- **Callback Function**: Function untuk mengambil user info

### Data Flow

```
1. User loads WordPress admin
   ↓
2. wp-app-core triggers 'wp_app_core_register_admin_bar_plugins' action
   ↓
3. Plugins register themselves with WP_App_Core_Admin_Bar_Info::register_plugin()
   ↓
4. When admin bar renders, wp-app-core calls each plugin's callback
   ↓
5. First plugin that returns data wins (break loop)
   ↓
6. wp-app-core displays the data in standardized format
   ↓
7. Admin bar shows: "🏛️ Entity Name | 👤 Role Name"
```

### Separation of Concerns

- **Plugin (e.g., wp-agency)**: Knows HOW to fetch entity-specific data
- **WP App Core**: Knows HOW to display user info in admin bar

---

## Persyaratan Plugin

Untuk dapat berinteraksi dengan wp-app-core, plugin Anda harus menyediakan:

### 1. Integration Class

Class khusus untuk integrasi dengan wp-app-core (contoh: `class-app-core-integration.php`)

**Lokasi Recommended**: `/your-plugin/includes/class-app-core-integration.php`

### 2. Role Manager Class

Class yang menyediakan daftar roles dan display names.

**Required Static Methods**:
```php
public static function getRoleSlugs(): array  // Returns all role slugs
public static function getRoleName($slug): ?string  // Returns display name for a role
```

### 3. User Info Model/Method

Method atau model class yang dapat mengambil informasi user dari database.

**Required**:
- Parameter: `$user_id` (int)
- Return: Array dengan struktur user info yang lengkap (lihat [Struktur Data](#struktur-data-user-info))

### 4. Database Tables (Optional)

Jika plugin Anda menyimpan entity data (company, agency, dll), pastikan:
- Ada relasi ke `wp_users` (via `user_id`)
- Ada meta info yang diperlukan (entity name, code, division/branch, etc)

---

## Implementasi Step-by-Step

### Step 1: Check Dependency

Pastikan wp-app-core terinstall dan aktif:

```php
if (!class_exists('WP_App_Core_Admin_Bar_Info')) {
    return; // Skip integration
}
```

### Step 2: Buat Integration Class

**File**: `/your-plugin/includes/class-app-core-integration.php`

```php
<?php
/**
 * Integration with WP App Core
 */
class Your_Plugin_App_Core_Integration {

    /**
     * Initialize integration
     */
    public static function init() {
        // Check if wp-app-core is active
        if (!class_exists('WP_App_Core_Admin_Bar_Info')) {
            return;
        }

        // Register plugin with wp-app-core
        add_action('wp_app_core_register_admin_bar_plugins', [__CLASS__, 'register_with_app_core']);
    }

    /**
     * Register plugin with wp-app-core admin bar system
     */
    public static function register_with_app_core() {
        WP_App_Core_Admin_Bar_Info::register_plugin('your_plugin', [
            'roles' => Your_Plugin_Role_Manager::getRoleSlugs(),
            'get_user_info' => [__CLASS__, 'get_user_info'],
        ]);
    }

    /**
     * Get user information for admin bar display
     *
     * @param int $user_id WordPress user ID
     * @return array|null User info array or null if not found
     */
    public static function get_user_info($user_id) {
        // Delegate to your model for data retrieval
        $model = new Your_Plugin_Model();
        return $model->getUserInfo($user_id);
    }
}
```

### Step 3: Buat Role Manager

**File**: `/your-plugin/includes/class-role-manager.php`

```php
<?php
/**
 * Role Manager
 */
class Your_Plugin_Role_Manager {

    /**
     * Role definitions
     */
    private static $roles = [
        'your_plugin_role_1' => 'Display Name 1',
        'your_plugin_role_2' => 'Display Name 2',
        'your_plugin_admin' => 'Admin',
        // ... add all your roles
    ];

    /**
     * Get all role slugs
     *
     * @return array
     */
    public static function getRoleSlugs() {
        return array_keys(self::$roles);
    }

    /**
     * Get display name for a role slug
     *
     * @param string $slug Role slug
     * @return string|null Display name or null if not found
     */
    public static function getRoleName($slug) {
        return self::$roles[$slug] ?? null;
    }
}
```

### Step 4: Implementasi User Info Retrieval

**File**: `/your-plugin/src/Models/YourPluginModel.php` (atau di class lain)

```php
<?php
namespace YourPlugin\Models;

class YourPluginModel {

    /**
     * Get user information for admin bar
     *
     * @param int $user_id
     * @return array|null
     */
    public function getUserInfo($user_id) {
        global $wpdb;

        // IMPORTANT: Use single comprehensive query with JOINs
        // untuk optimal performance
        $query = $wpdb->prepare("
            SELECT
                e.*,
                entity.name AS entity_name,
                entity.code AS entity_code,
                division.name AS division_name,
                division.code AS division_code,
                u.user_email,
                MAX(um.meta_value) AS capabilities
            FROM {$wpdb->prefix}your_plugin_employees e
            INNER JOIN {$wpdb->prefix}your_plugin_entities entity ON e.entity_id = entity.id
            INNER JOIN {$wpdb->prefix}your_plugin_divisions division ON e.division_id = division.id
            INNER JOIN {$wpdb->users} u ON e.user_id = u.ID
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'wp_capabilities'
            WHERE e.user_id = %d
            GROUP BY e.id, u.user_email
            LIMIT 1
        ", $user_id);

        $user_data = $wpdb->get_row($query);

        if (!$user_data) {
            // Fallback for users with role but no entity link
            return $this->getFallbackUserInfo($user_id);
        }

        // Parse role names from capabilities
        $admin_bar_model = new \WPAppCore\Models\AdminBarModel();
        $role_names = $admin_bar_model->getRoleNamesFromCapabilities(
            $user_data->capabilities,
            call_user_func(['Your_Plugin_Role_Manager', 'getRoleSlugs']),
            ['Your_Plugin_Role_Manager', 'getRoleName']
        );

        // Parse permission names
        $permission_names = $admin_bar_model->getPermissionNamesFromUserId(
            $user_id,
            call_user_func(['Your_Plugin_Role_Manager', 'getRoleSlugs']),
            $this->getPermissionLabels() // Your permission definitions
        );

        // Return standardized structure
        return [
            'entity_name' => $user_data->entity_name,
            'entity_code' => $user_data->entity_code,
            'division_name' => $user_data->division_name,
            'division_code' => $user_data->division_code,
            'position' => $user_data->position ?? '',
            'user_email' => $user_data->user_email,
            'capabilities' => $user_data->capabilities,
            'relation_type' => 'employee',
            'icon' => '🏢', // Choose appropriate icon
            'role_names' => $role_names,
            'permission_names' => $permission_names,
        ];
    }

    /**
     * Fallback for users with role but no entity link
     *
     * @param int $user_id
     * @return array|null
     */
    private function getFallbackUserInfo($user_id) {
        $user = get_userdata($user_id);

        if (!$user) {
            return null;
        }

        $user_roles = $user->roles;
        $plugin_roles = call_user_func(['Your_Plugin_Role_Manager', 'getRoleSlugs']);
        $has_plugin_role = !empty(array_intersect($user_roles, $plugin_roles));

        if (!$has_plugin_role) {
            return null;
        }

        // Get first matching role's display name
        $role_name = null;
        foreach ($user_roles as $role) {
            $role_name = call_user_func(['Your_Plugin_Role_Manager', 'getRoleName'], $role);
            if ($role_name) break;
        }

        return [
            'entity_name' => 'Your Plugin System',
            'entity_code' => 'PLUGIN',
            'division_name' => $role_name ?? 'Staff',
            'relation_type' => 'role_only',
            'icon' => '🏢',
        ];
    }

    /**
     * Get permission labels for your plugin
     *
     * @return array
     */
    private function getPermissionLabels() {
        // Return array of permission slug => display name
        return [
            'your_plugin_view_reports' => 'View Reports',
            'your_plugin_manage_users' => 'Manage Users',
            // ... etc
        ];
    }
}
```

### Step 5: Initialize Integration di Main Plugin File

**File**: `/your-plugin/your-plugin.php`

```php
<?php
/**
 * Plugin Name: Your Plugin
 * ...
 */

// Include integration class
require_once plugin_dir_path(__FILE__) . 'includes/class-app-core-integration.php';

// Initialize integration on WordPress init
add_action('init', ['Your_Plugin_App_Core_Integration', 'init'], 10);
```

### Step 6: Tambahkan Caching (Recommended)

Untuk performance, tambahkan caching pada user info retrieval:

```php
public function getUserInfo($user_id) {
    $cache_key = 'your_plugin_user_info_' . $user_id;
    $cached = wp_cache_get($cache_key, 'your_plugin');

    if ($cached !== false) {
        return $cached;
    }

    // ... query database ...

    // Cache for 5 minutes
    wp_cache_set($cache_key, $result, 'your_plugin', 300);

    return $result;
}
```

---

## API Reference

### Classes

#### WP_App_Core_Admin_Bar_Info

**Location**: `/wp-app-core/includes/class-admin-bar-info.php`

**Static Methods**:

##### register_plugin($plugin_id, $config)

Register your plugin with wp-app-core admin bar system.

**Parameters**:
- `$plugin_id` (string): Unique identifier for your plugin (e.g., 'agency', 'customer')
- `$config` (array): Configuration array with:
  - `roles` (array): Array of role slugs from your Role Manager
  - `get_user_info` (callable): Callback function to get user info

**Example**:
```php
WP_App_Core_Admin_Bar_Info::register_plugin('your_plugin', [
    'roles' => Your_Plugin_Role_Manager::getRoleSlugs(),
    'get_user_info' => [__CLASS__, 'get_user_info'],
]);
```

---

#### AdminBarModel

**Location**: `/wp-app-core/src/Models/AdminBarModel.php`

**Namespace**: `WPAppCore\Models`

**Methods**:

##### getRoleNamesFromCapabilities($capabilities_string, $role_slugs_filter, $role_name_resolver)

Parse role names from serialized capabilities string (from wp_usermeta).

**Parameters**:
- `$capabilities_string` (string): Serialized capabilities from wp_usermeta
- `$role_slugs_filter` (array): Array of role slugs to filter (only include these)
- `$role_name_resolver` (callable): Callback to resolve role slug to display name

**Returns**: `array` - Array of role display names

**Example**:
```php
$admin_bar_model = new \WPAppCore\Models\AdminBarModel();
$role_names = $admin_bar_model->getRoleNamesFromCapabilities(
    $user_data->capabilities,
    ['agency_admin', 'agency_staff'],
    ['WP_Agency_Role_Manager', 'getRoleName']
);
// Returns: ['Admin Dinas', 'Staff']
```

##### getPermissionNamesFromUserId($user_id, $role_slugs_to_skip, $permission_labels)

Get user's actual permission names (excluding role slugs).

**Parameters**:
- `$user_id` (int): WordPress user ID
- `$role_slugs_to_skip` (array): Role slugs to exclude from results
- `$permission_labels` (array): Map of permission slug => display name

**Returns**: `array` - Array of permission display names

**Example**:
```php
$admin_bar_model = new \WPAppCore\Models\AdminBarModel();
$permission_names = $admin_bar_model->getPermissionNamesFromUserId(
    $user_id,
    ['agency_admin', 'agency_staff'], // Skip these role slugs
    [
        'view_reports' => 'View Reports',
        'manage_users' => 'Manage Users',
    ]
);
// Returns: ['View Reports', 'Manage Users']
```

---

### Hooks & Filters

#### Actions

##### wp_app_core_register_admin_bar_plugins

Triggered when wp-app-core is ready for plugins to register.

**Priority**: 100
**Parameters**: None

**Usage**:
```php
add_action('wp_app_core_register_admin_bar_plugins', [__CLASS__, 'register_with_app_core']);
```

##### wp_app_core_init

General initialization hook for wp-app-core.

**Priority**: 10
**Parameters**: None

##### wp_app_core_before_profile_fields

Hook before profile fields section on user profile page.

**Parameters**:
- `$userData` (array): User data array
- `$user` (WP_User): WordPress user object

##### wp_app_core_after_profile_fields

Hook after profile fields section on user profile page.

**Parameters**:
- `$userData` (array): User data array
- `$user` (WP_User): WordPress user object

##### wp_app_core_after_profile_section

Hook after entire profile section on user profile page.

**Parameters**:
- `$userData` (array): User data array
- `$user` (WP_User): WordPress user object

---

#### Filters

##### wp_app_core_should_display_admin_bar

Control whether admin bar should be displayed for a user.

**Parameters**:
- `$should_display` (bool): Default value
- `$user` (WP_User): WordPress user object

**Returns**: `bool`

**Usage**:
```php
add_filter('wp_app_core_should_display_admin_bar', function($should_display, $user) {
    // Custom logic
    return $should_display;
}, 10, 2);
```

##### wp_app_core_admin_bar_user_info

Modify user info array before display in admin bar.

**Parameters**:
- `$user_info` (array): User info array
- `$user_id` (int): WordPress user ID

**Returns**: `array`

**Usage**:
```php
add_filter('wp_app_core_admin_bar_user_info', function($user_info, $user_id) {
    // Modify $user_info
    return $user_info;
}, 10, 2);
```

##### wp_app_core_admin_bar_key_capabilities

Customize which capabilities are shown in admin bar dropdown.

**Parameters**:
- `$key_caps` (array): Array of capability names

**Returns**: `array`

##### wp_app_core_role_name_{$role_slug}

Dynamic filter to get display name for a specific role.

**Parameters**:
- `$role_name` (string|null): Default role name

**Returns**: `string|null`

**Example**:
```php
// In your integration init():
add_filter('wp_app_core_role_name_your_plugin_admin', [__CLASS__, 'get_role_name']);
add_filter('wp_app_core_role_name_your_plugin_staff', [__CLASS__, 'get_role_name']);

// Your get_role_name method:
public static function get_role_name($default) {
    $role_slug = str_replace('wp_app_core_role_name_', '', current_filter());
    return Your_Plugin_Role_Manager::getRoleName($role_slug) ?? $default;
}
```

##### wp_app_core_user_profile_data

Modify user profile data before display on user profile page.

**Parameters**:
- `$userData` (array): User data array
- `$user_id` (int): WordPress user ID

**Returns**: `array`

##### wp_app_core_profile_*_label

Customize individual profile field labels.

Available filters:
- `wp_app_core_profile_entity_label`
- `wp_app_core_profile_code_label`
- `wp_app_core_profile_division_label`
- `wp_app_core_profile_role_label`
- `wp_app_core_profile_permissions_label`

**Parameters**:
- `$label` (string): Default label text

**Returns**: `string`

---

## Struktur Data User Info

Array yang dikembalikan oleh callback `get_user_info` harus mengikuti struktur berikut:

### Required Fields

```php
[
    'entity_name' => string,      // Nama entity (e.g., "PT. Example", "Disnaker Jatim")
    'entity_code' => string,      // Kode entity (e.g., "COMPANY123", "DISNAKER_JTM")
    'relation_type' => string,    // Type relasi (e.g., 'employee', 'owner', 'role_only')
    'icon' => string,             // Emoji icon untuk display (e.g., '🏛️', '🏢', '🏪')
]
```

### Optional but Recommended Fields

```php
[
    'division_name' => string,         // Nama division/branch (e.g., "Divisi IT", "Cabang Jakarta")
    'division_code' => string,         // Kode division/branch
    'division_type' => string,         // Type division (e.g., 'pusat', 'cabang', 'unit')
    'position' => string,              // Jabatan user (e.g., "Manager", "Staff")
    'user_email' => string,            // Email user
    'capabilities' => string,          // Serialized capabilities dari wp_usermeta
    'role_names' => array,             // Array of role display names
    'permission_names' => array,       // Array of permission display names
]
```

### Plugin-Specific Optional Fields

Anda dapat menambahkan fields custom sesuai kebutuhan plugin:

```php
[
    // Agency-specific
    'jurisdiction_codes' => string,        // Multi-value (e.g., "JKT,BDG,SBY")
    'is_primary_jurisdiction' => bool,

    // Customer-specific
    'branch_id' => int,
    'company_type' => string,

    // Custom fields
    'custom_field_1' => mixed,
    'custom_field_2' => mixed,
]
```

### Field Display Priority

wp-app-core akan menampilkan fields dengan prioritas berikut:

1. **Main Display** (Admin Bar Top):
   - `icon` + `entity_name` | `role_names[0]` atau `division_name`

2. **Dropdown Details**:
   - Entity Name & Code
   - Division Name & Code (if available)
   - Division Type (if available)
   - Position (if available)
   - Role Names (if available)
   - Permission Names (if available)
   - Custom fields (via filters)

---

## Contoh Implementasi Lengkap

Berikut adalah contoh lengkap implementasi dari **wp-agency**:

### 1. Integration Class

**File**: `/wp-agency/includes/class-app-core-integration.php`

```php
<?php
/**
 * Integration with WP App Core for Agency Plugin
 *
 * @package WP_Agency
 * @version 1.5.0
 */

class WP_Agency_App_Core_Integration {

    /**
     * Initialize integration with wp-app-core
     */
    public static function init() {
        // Check if wp-app-core is active
        if (!class_exists('WP_App_Core_Admin_Bar_Info')) {
            return;
        }

        // Register agency plugin with app core admin bar system
        add_action('wp_app_core_register_admin_bar_plugins', [__CLASS__, 'register_with_app_core']);
    }

    /**
     * Register agency plugin with wp-app-core
     */
    public static function register_with_app_core() {
        WP_App_Core_Admin_Bar_Info::register_plugin('agency', [
            'roles' => WP_Agency_Role_Manager::getRoleSlugs(),
            'get_user_info' => [__CLASS__, 'get_user_info'],
        ]);
    }

    /**
     * Get agency user information for admin bar display
     *
     * @param int $user_id WordPress user ID
     * @return array|null User info array or null if not found
     */
    public static function get_user_info($user_id) {
        // Delegate to AgencyEmployeeModel for data retrieval
        // This model handles caching, comprehensive queries, and fallbacks
        $employee_model = new \WPAgency\Models\Employee\AgencyEmployeeModel();
        return $employee_model->getUserInfo($user_id);
    }
}
```

### 2. Model Implementation (Simplified)

**File**: `/wp-agency/src/Models/Employee/AgencyEmployeeModel.php`

```php
<?php
namespace WPAgency\Models\Employee;

class AgencyEmployeeModel {

    /**
     * Get user information with caching
     *
     * @param int $user_id
     * @return array|null
     */
    public function getUserInfo($user_id) {
        // Check cache first
        $cache_key = 'agency_user_info_' . $user_id;
        $cached = wp_cache_get($cache_key, 'wp-agency');

        if ($cached !== false) {
            return $cached;
        }

        // Query database with comprehensive JOINs
        global $wpdb;

        $query = $wpdb->prepare("
            SELECT * FROM (
                SELECT
                    e.*,
                    MAX(d.code) AS division_code,
                    MAX(d.name) AS division_name,
                    MAX(d.type) AS division_type,
                    GROUP_CONCAT(j.jurisdiction_code SEPARATOR ',') AS jurisdiction_codes,
                    MAX(j.is_primary) AS is_primary_jurisdiction,
                    MAX(a.code) AS agency_code,
                    MAX(a.name) AS agency_name,
                    u.user_email,
                    MAX(um.meta_value) AS capabilities
                FROM {$wpdb->prefix}app_agency_employees e
                INNER JOIN {$wpdb->prefix}app_agency_divisions d ON e.division_id = d.id
                INNER JOIN {$wpdb->prefix}app_agency_jurisdictions j ON d.id = j.division_id
                INNER JOIN {$wpdb->prefix}app_agencies a ON e.agency_id = a.id
                INNER JOIN {$wpdb->users} u ON e.user_id = u.ID
                INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'wp_capabilities'
                WHERE e.user_id = %d
                GROUP BY e.id, e.user_id, u.user_email
            ) AS subquery
            GROUP BY subquery.id
            LIMIT 1
        ", $user_id);

        $user_data = $wpdb->get_row($query);

        if (!$user_data) {
            // Try fallback for users with role but no entity link
            $result = $this->getFallbackUserInfo($user_id);
            if ($result) {
                wp_cache_set($cache_key, $result, 'wp-agency', 300);
            }
            return $result;
        }

        // Parse role names from capabilities
        $admin_bar_model = new \WPAppCore\Models\AdminBarModel();
        $role_names = $admin_bar_model->getRoleNamesFromCapabilities(
            $user_data->capabilities,
            call_user_func(['WP_Agency_Role_Manager', 'getRoleSlugs']),
            ['WP_Agency_Role_Manager', 'getRoleName']
        );

        // Parse permission names
        $permission_model = new \WPAgency\Models\PermissionModel();
        $permission_names = $admin_bar_model->getPermissionNamesFromUserId(
            $user_id,
            call_user_func(['WP_Agency_Role_Manager', 'getRoleSlugs']),
            $permission_model->getAllPermissions()
        );

        // Build result array
        $result = [
            'entity_name' => $user_data->agency_name,
            'entity_code' => $user_data->agency_code,
            'division_id' => $user_data->division_id,
            'division_code' => $user_data->division_code,
            'division_name' => $user_data->division_name,
            'division_type' => $user_data->division_type,
            'jurisdiction_codes' => $user_data->jurisdiction_codes,
            'is_primary_jurisdiction' => (bool) $user_data->is_primary_jurisdiction,
            'position' => $user_data->position ?? '',
            'user_email' => $user_data->user_email,
            'capabilities' => $user_data->capabilities,
            'relation_type' => 'agency_employee',
            'icon' => '🏛️',
            'role_names' => $role_names,
            'permission_names' => $permission_names,
        ];

        // Cache for 5 minutes
        wp_cache_set($cache_key, $result, 'wp-agency', 300);

        return $result;
    }

    /**
     * Fallback for users with agency role but no entity link
     *
     * @param int $user_id
     * @return array|null
     */
    private function getFallbackUserInfo($user_id) {
        $user = get_userdata($user_id);

        if (!$user) {
            return null;
        }

        $user_roles = $user->roles;
        $agency_roles = call_user_func(['WP_Agency_Role_Manager', 'getRoleSlugs']);
        $has_agency_role = !empty(array_intersect($user_roles, $agency_roles));

        if (!$has_agency_role) {
            return null;
        }

        // Get first matching role's display name
        $role_name = null;
        foreach ($user_roles as $role) {
            $role_name = call_user_func(['WP_Agency_Role_Manager', 'getRoleName'], $role);
            if ($role_name) break;
        }

        return [
            'entity_name' => 'Agency System',
            'entity_code' => 'AGENCY',
            'division_name' => $role_name ?? 'Staff',
            'division_type' => 'admin',
            'relation_type' => 'role_only',
            'icon' => '🏛️',
        ];
    }
}
```

### 3. Main Plugin File

**File**: `/wp-agency/wp-agency.php`

```php
<?php
/**
 * Plugin Name: WP Agency
 * Plugin URI: https://example.com/wp-agency
 * Description: Agency management system
 * Version: 1.5.0
 * Author: Your Name
 * Text Domain: wp-agency
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Include integration class
require_once plugin_dir_path(__FILE__) . 'includes/class-app-core-integration.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-role-manager.php';

// Initialize integration on WordPress init
add_action('init', ['WP_Agency_App_Core_Integration', 'init'], 10);

// Rest of your plugin initialization...
```

---

## Best Practices

### 1. Query Optimization

✅ **DO**: Use single comprehensive query dengan JOINs
```php
// Single query with all JOINs
SELECT e.*, d.name, a.name, u.user_email, um.meta_value
FROM employees e
INNER JOIN divisions d ON e.division_id = d.id
INNER JOIN agencies a ON e.agency_id = a.id
INNER JOIN users u ON e.user_id = u.ID
INNER JOIN usermeta um ON u.ID = um.user_id
WHERE e.user_id = %d
```

❌ **DON'T**: Multiple separate queries
```php
// Multiple queries (SLOW!)
$employee = get_employee($user_id);
$division = get_division($employee->division_id);
$agency = get_agency($employee->agency_id);
```

### 2. Caching

✅ **DO**: Implement caching dengan reasonable TTL
```php
$cache_key = 'plugin_user_info_' . $user_id;
$cached = wp_cache_get($cache_key, 'your-plugin');

if ($cached !== false) {
    return $cached;
}

// ... query database ...

wp_cache_set($cache_key, $result, 'your-plugin', 300); // 5 minutes
```

### 3. Fallback Logic

✅ **DO**: Provide fallback untuk users dengan role tapi tanpa entity link
```php
if (!$user_data) {
    return $this->getFallbackUserInfo($user_id);
}
```

### 4. Role Name Parsing

✅ **DO**: Use AdminBarModel helper methods
```php
$admin_bar_model = new \WPAppCore\Models\AdminBarModel();
$role_names = $admin_bar_model->getRoleNamesFromCapabilities(
    $capabilities,
    $role_slugs_filter,
    $role_name_resolver
);
```

❌ **DON'T**: Parse manually dengan regex atau string operations

### 5. Error Handling

✅ **DO**: Return null jika data tidak ditemukan
```php
public static function get_user_info($user_id) {
    // ... query ...

    if (!$result) {
        return null; // Let wp-app-core try next plugin
    }

    return $result;
}
```

### 6. Separation of Concerns

✅ **DO**: Delegate ke Model classes
```php
public static function get_user_info($user_id) {
    $model = new YourPluginModel();
    return $model->getUserInfo($user_id);
}
```

❌ **DON'T**: Put all logic di integration class

### 7. Debug Logging

✅ **DO**: Add debug logging untuk troubleshooting
```php
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log("=== YourPlugin get_user_info START for user_id: {$user_id} ===");
    error_log("Query Result: " . print_r($result, true));
    error_log("=== YourPlugin get_user_info END ===");
}
```

### 8. Dependency Check

✅ **DO**: Always check if wp-app-core is available
```php
if (!class_exists('WP_App_Core_Admin_Bar_Info')) {
    return; // Graceful degradation
}
```

### 9. Data Validation

✅ **DO**: Validate user input dan database results
```php
if (!is_numeric($user_id) || $user_id <= 0) {
    return null;
}

if (!isset($user_data->entity_name) || empty($user_data->entity_name)) {
    return $this->getFallbackUserInfo($user_id);
}
```

### 10. Version Documentation

✅ **DO**: Document version changes dan improvements
```php
/**
 * Integration with WP App Core
 *
 * @package Your_Plugin
 * @version 1.5.0
 *
 * Changelog:
 * - v1.5.0: Optimized to single comprehensive query
 * - v1.4.0: Added jurisdiction support
 * - v1.3.0: Fixed query priority
 * - v1.2.0: Added debug logging
 * - v1.1.0: Fixed hardcoded values
 * - v1.0.0: Initial release
 */
```

---

## Testing & Debugging

### Testing Checklist

Gunakan checklist ini untuk memastikan integrasi berfungsi dengan baik:

#### Functionality Tests

- [ ] Admin bar muncul untuk users dengan entity link
- [ ] Admin bar muncul untuk users dengan role tapi tanpa entity link (fallback)
- [ ] Admin bar menampilkan entity name dan code dengan benar
- [ ] Admin bar menampilkan division/branch name dengan benar
- [ ] Role names ditampilkan sebagai display names (bukan slugs)
- [ ] Dropdown menampilkan detail lengkap user info
- [ ] Tidak ada konflik dengan plugin lain
- [ ] Permission names ditampilkan dengan benar

#### Performance Tests

- [ ] Query count optimal (ideally 1 query per user info)
- [ ] Caching berfungsi dengan baik
- [ ] No N+1 query problems
- [ ] Page load time tidak terpengaruh signifikan

#### Edge Cases

- [ ] User dengan multiple roles
- [ ] User dengan role tapi tanpa entity
- [ ] User tanpa role dari plugin Anda
- [ ] wp-app-core tidak aktif (graceful degradation)
- [ ] Database connection error handling

### Debug Logging

Enable debug logging untuk troubleshooting:

**1. Enable WordPress Debug Mode**

Edit `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**2. Add Debug Logging di Integration Class**

```php
public static function get_user_info($user_id) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("=== YourPlugin get_user_info START for user_id: {$user_id} ===");
    }

    $result = $model->getUserInfo($user_id);

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("Query Result: " . print_r($result, true));
        error_log("=== YourPlugin get_user_info END ===");
    }

    return $result;
}
```

**3. Check Debug Log**

Log file location: `/wp-content/debug.log`

```bash
tail -f /path/to/wp-content/debug.log
```

### Common Debug Patterns

**Check if plugin registered**:
```php
error_log("Registered Plugins: " . print_r(array_keys(self::$registered_plugins), true));
```

**Check user roles**:
```php
$user = get_userdata($user_id);
error_log("User Roles: " . print_r($user->roles, true));
```

**Check query result**:
```php
error_log("SQL Query: " . $wpdb->last_query);
error_log("Query Result: " . print_r($result, true));
```

**Check capabilities parsing**:
```php
error_log("Raw Capabilities: " . $capabilities_string);
error_log("Parsed Role Names: " . print_r($role_names, true));
```

### Testing Tools

**1. Query Monitor Plugin**

Install Query Monitor untuk melihat:
- Database queries
- Query execution time
- Hook callbacks
- Cache hits/misses

**2. User Switching Plugin**

Install User Switching untuk:
- Quickly switch between different users
- Test admin bar for different roles
- Verify fallback logic

**3. Custom Test Script**

Buat test script untuk automated testing:

```php
// File: test-integration.php

require_once 'wp-load.php';

$test_users = [
    ['id' => 10, 'expected_entity' => 'Agency 1'],
    ['id' => 20, 'expected_entity' => 'Agency 2'],
    ['id' => 30, 'expected_entity' => 'Agency System'], // Fallback case
];

foreach ($test_users as $test) {
    $result = Your_Plugin_App_Core_Integration::get_user_info($test['id']);

    if (!$result) {
        echo "FAIL: User {$test['id']} - No result\n";
        continue;
    }

    if ($result['entity_name'] !== $test['expected_entity']) {
        echo "FAIL: User {$test['id']} - Expected {$test['expected_entity']}, got {$result['entity_name']}\n";
    } else {
        echo "PASS: User {$test['id']}\n";
    }
}
```

---

## Troubleshooting

### Issue: Admin Bar Tidak Muncul

**Possible Causes**:

1. **wp-app-core tidak aktif**
   - Solution: Pastikan wp-app-core plugin terinstall dan aktif

2. **Plugin tidak terdaftar**
   - Check: Apakah `register_with_app_core()` dipanggil?
   - Check: Apakah hook `wp_app_core_register_admin_bar_plugins` terpasang?
   - Debug:
     ```php
     error_log("Registered plugins: " . print_r(WP_App_Core_Admin_Bar_Info::get_registered_plugins(), true));
     ```

3. **get_user_info mengembalikan null**
   - Check: Apakah query database berhasil?
   - Check: Apakah fallback logic berfungsi?
   - Debug: Tambahkan logging di get_user_info()

4. **User tidak memiliki role yang terdaftar**
   - Check: Apakah user memiliki role dari plugin Anda?
   - Debug:
     ```php
     $user = get_userdata($user_id);
     error_log("User roles: " . print_r($user->roles, true));
     ```

### Issue: Role Names Menampilkan Slugs

**Possible Causes**:

1. **Filter tidak terdaftar**
   - Check: Apakah filter `wp_app_core_role_name_{$role_slug}` terdaftar?
   - Solution: Tambahkan filter di `init()` method

2. **getRoleName() mengembalikan null**
   - Check: Apakah role slug ada di Role Manager?
   - Debug:
     ```php
     error_log("Trying to get name for: " . $role_slug);
     error_log("Result: " . Your_Plugin_Role_Manager::getRoleName($role_slug));
     ```

### Issue: Query Terlalu Lambat

**Solutions**:

1. **Optimize query dengan JOINs**
   - Gunakan INNER JOIN atau LEFT JOIN sesuai kebutuhan
   - Gunakan single query instead of multiple queries

2. **Add database indexes**
   ```sql
   ALTER TABLE wp_app_your_employees ADD INDEX idx_user_id (user_id);
   ALTER TABLE wp_app_your_divisions ADD INDEX idx_id (id);
   ```

3. **Implement caching**
   - Cache hasil query untuk 5-10 menit
   - Clear cache saat data berubah

4. **Use LIMIT 1**
   - Jika hanya butuh 1 row, tambahkan LIMIT 1

### Issue: Data Tidak Lengkap

**Possible Causes**:

1. **Query tidak mencakup semua JOINs**
   - Solution: Pastikan semua tabel yang diperlukan di-JOIN

2. **GROUP BY tanpa agregasi**
   - Solution: Gunakan MAX() atau GROUP_CONCAT() untuk fields yang di-aggregate
   - Example:
     ```sql
     MAX(d.name) AS division_name,
     GROUP_CONCAT(j.code SEPARATOR ',') AS jurisdiction_codes
     ```

3. **Fields tidak direturn**
   - Check: Apakah semua fields yang diperlukan ada di SELECT clause?
   - Check: Apakah field names sesuai dengan expected structure?

### Issue: Cache Tidak Ter-update

**Solutions**:

1. **Clear cache saat data berubah**
   ```php
   public function updateEmployee($user_id, $data) {
       // ... update database ...

       // Clear cache
       wp_cache_delete('plugin_user_info_' . $user_id, 'your-plugin');
   }
   ```

2. **Reduce cache TTL**
   - Jika data sering berubah, kurangi TTL (e.g., 60 seconds instead of 300)

3. **Use transients untuk persistent cache**
   ```php
   set_transient('plugin_user_info_' . $user_id, $result, 300);
   $cached = get_transient('plugin_user_info_' . $user_id);
   ```

### Issue: Multiple Plugins Conflict

**Possible Causes**:

1. **Role slugs overlap**
   - Solution: Gunakan unique prefixes untuk role slugs (e.g., `your_plugin_role`)

2. **Same plugin ID registered twice**
   - Solution: Gunakan unique plugin ID saat register

3. **Filter priority conflicts**
   - Solution: Adjust filter priorities:
     ```php
     add_filter('wp_app_core_admin_bar_user_info', [__CLASS__, 'modify_info'], 20, 2);
     ```

### Issue: Fallback Logic Tidak Berfungsi

**Check**:

1. Apakah role check benar?
   ```php
   $has_plugin_role = !empty(array_intersect($user_roles, $plugin_roles));
   ```

2. Apakah fallback return structure lengkap?
   - Minimal fields: entity_name, entity_code, icon, relation_type

3. Apakah return value di-cache?
   - Jangan lupa cache fallback result juga

---

## Referensi Tambahan

### Documentation Files

- `/wp-app-core/claude-chats/debug-logging-guide.md` - Guide untuk debug logging
- `/wp-app-core/TODO/TODO-1201-wp-app-core-admin-bar-integration.md` - Development history

### Example Plugins

- **wp-agency**: Full implementation dengan jurisdiction support
- **wp-customer**: Implementation dengan branch support

### File Locations

**wp-app-core**:
- Core class: `/includes/class-admin-bar-info.php`
- Helper model: `/src/Models/AdminBarModel.php`

**wp-agency** (reference):
- Integration: `/includes/class-app-core-integration.php`
- Model: `/src/Models/Employee/AgencyEmployeeModel.php`
- Role Manager: `/includes/class-role-manager.php`

**wp-customer** (reference):
- Integration: `/includes/class-app-core-integration.php`
- Model: `/src/Models/CustomerEmployeeModel.php`

---

## Changelog

### Version 1.0.0 (2025-01-18)

- Initial documentation
- Comprehensive integration guide
- API reference
- Best practices
- Troubleshooting guide

---

## Support

Jika Anda mengalami masalah atau memiliki pertanyaan:

1. **Check documentation**: Baca guide ini dengan teliti
2. **Enable debug logging**: Gunakan WP_DEBUG untuk troubleshooting
3. **Check existing implementations**: Lihat contoh dari wp-agency atau wp-customer
4. **Review TODO files**: Lihat development history di `/TODO/` folder

---

## Kesimpulan

Dengan mengikuti guide ini, plugin Anda dapat:

✅ Terintegrasi dengan wp-app-core admin bar system
✅ Menampilkan user information di WordPress admin bar
✅ Share functionality dengan plugin lain tanpa konflik
✅ Maintain optimal performance dengan caching dan query optimization
✅ Provide user-friendly display dengan role name translations

Selamat mengintegrasikan plugin Anda dengan wp-app-core! 🚀
