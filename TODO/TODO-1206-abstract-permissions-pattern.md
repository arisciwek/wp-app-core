# TODO-1206: Abstract Permissions Pattern for 20 Plugins

**Created:** 2025-01-12
**Status:** Phase 1 Complete (wp-app-core implemented)
**Completed:** 2025-11-12
**Priority:** High
**Related:** task-1206.md, TODO-1205

---

## Problem Statement

**Current Issue:**
- Setiap plugin (wp-app-core, wp-customer, wp-agency, dll) punya permission tab sendiri
- Code hampir identik tapi harus copy-paste setiap kali buat plugin baru
- Setiap kali ada bug di save/reset, harus fix di 20 tempat
- CSS/JS duplicated di setiap plugin
- Template duplicated dengan minor differences
- No server-side validation untuk permission changes

**Pain Points:**
- 24+ hours wasted debugging permission save/reset
- Inconsistent AJAX vs Form POST patterns (user frustration)
- Manual testing required untuk setiap plugin baru
- No reusability across plugins

**Goal:**
Create minimalist but consistent abstract pattern where:
- ✅ Plugin baru tinggal define capabilities array
- ✅ Save/Reset automatically works (no debugging needed)
- ✅ CSS/JS shared from wp-app-core
- ✅ Template shared with extensibility hooks
- ✅ Server-side validation included
- ✅ Consistent AJAX pattern (no mixing)

---

## Solution Architecture

### Level 1: Abstract Foundation (wp-app-core)

```
/wp-app-core/src/Controllers/Abstract/AbstractPermissionsController.php
/wp-app-core/src/Models/Abstract/AbstractPermissionsModel.php
/wp-app-core/src/Validators/Abstract/AbstractPermissionsValidator.php
/wp-app-core/src/Views/templates/permissions/permission-matrix.php (shared template)
/wp-app-core/assets/css/permissions/permission-matrix.css (shared CSS)
/wp-app-core/assets/js/permissions/permission-matrix.js (shared JS with AJAX)
```

**Why Separate from AbstractSettingsController?**
- Different data storage: WordPress Roles API (wp_user_roles option) vs Settings API
- Different save pattern: AJAX per-role vs Form POST batch
- Different validation: Role capability checks vs Settings field validation

---

## Implementation Plan

### Phase 1: Abstract Foundation

#### 1.1. AbstractPermissionsController.php

**Location:** `/wp-app-core/src/Controllers/Abstract/AbstractPermissionsController.php`

**Purpose:** Base controller providing standard permission management flow

**Abstract Methods (MUST implement):**
```php
abstract protected function getPluginSlug(): string;
abstract protected function getPluginPrefix(): string;
abstract protected function getRoleManagerClass(): string;
abstract protected function getCapabilityGroups(): array;
abstract protected function getAllCapabilities(): array;
abstract protected function getCapabilityDescriptions(): array;
abstract protected function getDefaultCapabilitiesForRole(string $role_slug): array;
```

**Concrete Methods (provided by abstract):**
```php
public function init(): void {
    $this->registerAjaxHandlers();
}

private function registerAjaxHandlers(): void {
    $prefix = $this->getPluginPrefix();
    add_action("wp_ajax_{$prefix}_save_permissions", [$this, 'handleSavePermissions']);
    add_action("wp_ajax_{$prefix}_reset_permissions", [$this, 'handleResetPermissions']);
}

public function handleSavePermissions(): void {
    // Nonce check
    // Capability check
    // Validate via validator
    // Call model->updateRoleCapabilities()
    // Send JSON response
}

public function handleResetPermissions(): void {
    // Nonce check
    // Capability check
    // Call model->resetToDefault()
    // Send JSON response
}

public function enqueueAssets(): void {
    // Enqueue shared CSS from wp-app-core
    // Enqueue shared JS from wp-app-core
    // Localize script with plugin-specific data
}

public function getViewModel(): array {
    // Prepare data for template
    // Get capability groups
    // Get role matrix
    // Get descriptions
    return $view_data;
}
```

#### 1.2. AbstractPermissionsModel.php

**Location:** `/wp-app-core/src/Models/Abstract/AbstractPermissionsModel.php`

**Purpose:** Base model providing standard capability management operations

**Abstract Methods:**
```php
abstract protected function getRoleManagerClass(): string;
abstract protected function getAllCapabilities(): array;
abstract protected function getCapabilityGroups(): array;
abstract protected function getDefaultCapabilitiesForRole(string $role_slug): array;
```

**Concrete Methods:**
```php
public function getAllCapabilities(): array;
public function getCapabilityGroups(): array;
public function getCapabilityDescriptions(): array;
public function roleHasCapability(string $role_name, string $capability): bool;
public function addCapabilities(): void;
public function resetToDefault(): bool;
public function updateRoleCapabilities(string $role_name, array $capabilities): bool;
public function getRoleCapabilitiesMatrix(): array;

// Helper method (private)
private function getPluginRoles(): array {
    $role_manager = $this->getRoleManagerClass();
    return $role_manager::getRoleSlugs();
}
```

**Key Implementation:**
```php
public function resetToDefault(): bool {
    global $wpdb;

    try {
        // Get role manager
        $role_manager = $this->getRoleManagerClass();

        // Get WordPress roles from DB
        $wp_user_roles = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = '{$wpdb->prefix}user_roles'");
        $roles = maybe_unserialize($wp_user_roles);

        $modified = false;

        foreach ($roles as $role_name => $role_data) {
            // Only process plugin roles + administrator
            $is_plugin_role = $role_manager::isPluginRole($role_name);
            $is_admin = $role_name === 'administrator';

            if (!$is_plugin_role && !$is_admin) {
                continue;
            }

            // Remove all plugin capabilities
            foreach (array_keys($this->getAllCapabilities()) as $cap) {
                if (isset($roles[$role_name]['capabilities'][$cap])) {
                    unset($roles[$role_name]['capabilities'][$cap]);
                    $modified = true;
                }
            }

            // Add capabilities back
            if ($role_name === 'administrator') {
                foreach (array_keys($this->getAllCapabilities()) as $cap) {
                    $roles[$role_name]['capabilities'][$cap] = true;
                    $modified = true;
                }
            } else if ($is_plugin_role) {
                $roles[$role_name]['capabilities']['read'] = true;

                $default_caps = $this->getDefaultCapabilitiesForRole($role_name);
                foreach ($default_caps as $cap => $enabled) {
                    if ($enabled && isset($this->getAllCapabilities()[$cap])) {
                        $roles[$role_name]['capabilities'][$cap] = true;
                        $modified = true;
                    }
                }
            }
        }

        // Save back to database
        if ($modified) {
            $updated = update_option($wpdb->prefix . 'user_roles', $roles);
        }

        return true;

    } catch (\Exception $e) {
        error_log('[PermissionsModel] resetToDefault() ERROR: ' . $e->getMessage());
        return false;
    }
}
```

#### 1.3. AbstractPermissionsValidator.php

**Location:** `/wp-app-core/src/Validators/Abstract/AbstractPermissionsValidator.php`

**Purpose:** Server-side validation for permission changes

**Methods:**
```php
public function validateSaveRequest(array $data): array {
    $errors = [];

    // Validate role exists
    if (empty($data['role']) || !get_role($data['role'])) {
        $errors[] = 'Invalid role';
    }

    // Prevent modifying administrator
    if ($data['role'] === 'administrator') {
        $errors[] = 'Cannot modify administrator role';
    }

    // Validate capabilities array
    if (!isset($data['capabilities']) || !is_array($data['capabilities'])) {
        $errors[] = 'Invalid capabilities data';
    }

    // Check if role belongs to this plugin
    $role_manager = $this->getRoleManagerClass();
    if (!$role_manager::isPluginRole($data['role'])) {
        $errors[] = 'Role does not belong to this plugin';
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

public function validateResetRequest(): array {
    // Validate user has permission
    if (!current_user_can('manage_options')) {
        return [
            'valid' => false,
            'errors' => ['Permission denied']
        ];
    }

    return ['valid' => true, 'errors' => []];
}
```

#### 1.4. Shared Template: permission-matrix.php

**Location:** `/wp-app-core/src/Views/templates/permissions/permission-matrix.php`

**Purpose:** Reusable template dengan extensibility hooks

**Key Features:**
- Nested tab navigation untuk capability groups
- Permission matrix table (roles × capabilities)
- Reset button dengan WPModal confirmation
- Extensibility hooks untuk customization

**Hooks Provided:**
```php
// Before permission matrix
do_action("{$plugin_prefix}_before_permission_matrix", $view_data);

// Custom capability groups (filter)
$capability_groups = apply_filters("{$plugin_prefix}_capability_groups", $capability_groups);

// After permission matrix
do_action("{$plugin_prefix}_after_permission_matrix", $view_data);

// Custom role display filter
$displayed_roles = apply_filters("{$plugin_prefix}_displayed_roles", $displayed_roles);
```

**Template Structure:**
```php
<div class="wrap permission-matrix-wrapper">
    <!-- Header Section -->
    <div class="permission-header">
        <h2><?php echo esc_html($page_title); ?></h2>
        <p class="description"><?php echo esc_html($page_description); ?></p>
    </div>

    <!-- Nested Tab Navigation -->
    <h2 class="nav-tab-wrapper">
        <?php foreach ($capability_groups as $group_key => $group): ?>
            <a href="<?php echo add_query_arg('permission_tab', $group_key); ?>"
               class="nav-tab <?php echo $current_tab === $group_key ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($group['title']); ?>
            </a>
        <?php endforeach; ?>
    </h2>

    <!-- Reset Section -->
    <div class="permission-reset-section">
        <button type="button"
                class="button button-secondary btn-reset-permissions"
                data-plugin-prefix="<?php echo esc_attr($plugin_prefix); ?>"
                data-nonce="<?php echo wp_create_nonce($plugin_prefix . '_reset_permissions'); ?>">
            <span class="dashicons dashicons-image-rotate"></span>
            <?php _e('Reset All Permissions to Default', $text_domain); ?>
        </button>
        <p class="description"><?php _e('Warning: This will reset ALL permissions across all tabs.', $text_domain); ?></p>
    </div>

    <!-- Permission Matrix Table -->
    <div class="permission-matrix-container">
        <table class="widefat fixed striped permission-matrix-table">
            <thead>
                <tr>
                    <th class="column-role"><?php _e('Role', $text_domain); ?></th>
                    <?php foreach ($current_group['caps'] as $cap): ?>
                        <th class="column-permission">
                            <?php echo esc_html($capability_labels[$cap]); ?>
                            <span class="dashicons dashicons-info"
                                  title="<?php echo esc_attr($capability_descriptions[$cap]); ?>"></span>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($displayed_roles as $role_name => $role_info):
                    $role = get_role($role_name);
                ?>
                    <tr>
                        <td class="column-role">
                            <strong><?php echo translate_user_role($role_info['name']); ?></strong>
                        </td>
                        <?php foreach ($current_group['caps'] as $cap): ?>
                            <td class="column-permission">
                                <input type="checkbox"
                                       class="permission-checkbox"
                                       data-role="<?php echo esc_attr($role_name); ?>"
                                       data-capability="<?php echo esc_attr($cap); ?>"
                                       data-plugin-prefix="<?php echo esc_attr($plugin_prefix); ?>"
                                       data-nonce="<?php echo wp_create_nonce($plugin_prefix . '_nonce'); ?>"
                                       <?php checked($role->has_cap($cap)); ?>>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
```

#### 1.5. Shared Assets

**CSS:** `/wp-app-core/assets/css/permissions/permission-matrix.css`
```css
/* Permission Matrix Table Styling */
.permission-matrix-wrapper {
    margin-top: 20px;
}

.permission-matrix-table {
    margin-top: 20px;
}

.permission-matrix-table .column-role {
    width: 200px;
    font-weight: 600;
}

.permission-matrix-table .column-permission {
    text-align: center;
    width: 120px;
}

.permission-matrix-table .column-permission .dashicons-info {
    color: #999;
    font-size: 14px;
    cursor: help;
}

.permission-reset-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin-top: 20px;
}

.permission-reset-section .description {
    color: #d63638;
    font-weight: 500;
}
```

**JS:** `/wp-app-core/assets/js/permissions/permission-matrix.js`
```javascript
jQuery(document).ready(function($) {
    'use strict';

    // Handle checkbox change - instant AJAX save
    $('.permission-checkbox').on('change', function() {
        const $checkbox = $(this);
        const role = $checkbox.data('role');
        const capability = $checkbox.data('capability');
        const pluginPrefix = $checkbox.data('plugin-prefix');
        const nonce = $checkbox.data('nonce');
        const checked = $checkbox.is(':checked');

        // Disable checkbox during save
        $checkbox.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: pluginPrefix + '_save_permissions',
                nonce: nonce,
                role: role,
                capability: capability,
                enabled: checked ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    // Show success indicator (optional)
                    showNotification('success', response.data.message);
                } else {
                    // Revert checkbox on error
                    $checkbox.prop('checked', !checked);
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                // Revert checkbox on error
                $checkbox.prop('checked', !checked);
                showNotification('error', 'Failed to save permission');
            },
            complete: function() {
                // Re-enable checkbox
                $checkbox.prop('disabled', false);
            }
        });
    });

    // Handle reset button
    $('.btn-reset-permissions').on('click', function() {
        const $button = $(this);
        const pluginPrefix = $button.data('plugin-prefix');
        const nonce = $button.data('nonce');

        // WPModal confirmation
        WPModal.confirm({
            title: 'Reset All Permissions?',
            message: 'This will reset ALL permissions across all tabs to default values. This action cannot be undone.',
            danger: true,
            confirmLabel: 'Reset to Default',
            onConfirm: function() {
                $button.prop('disabled', true).text('Resetting...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: pluginPrefix + '_reset_permissions',
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Reload page to show updated permissions
                            window.location.reload();
                        } else {
                            showNotification('error', response.data.message);
                            $button.prop('disabled', false).text('Reset All Permissions to Default');
                        }
                    },
                    error: function() {
                        showNotification('error', 'Failed to reset permissions');
                        $button.prop('disabled', false).text('Reset All Permissions to Default');
                    }
                });
            }
        });
    });

    // Helper: Show notification
    function showNotification(type, message) {
        // Use WordPress admin notices or custom notification system
        const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap').prepend($notice);
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
});
```

---

### Phase 2: Plugin Implementation (wp-app-core example)

#### 2.1. PlatformPermissionsController.php

**Location:** `/wp-app-core/src/Controllers/Settings/PlatformPermissionsController.php`

```php
<?php
namespace WPAppCore\Controllers\Settings;

use WPAppCore\Controllers\Abstract\AbstractPermissionsController;
use WPAppCore\Models\Settings\PlatformPermissionModel;
use WPAppCore\Validators\Settings\PlatformPermissionValidator;

class PlatformPermissionsController extends AbstractPermissionsController {

    protected function getPluginSlug(): string {
        return 'wp-app-core';
    }

    protected function getPluginPrefix(): string {
        return 'wpapp';
    }

    protected function getRoleManagerClass(): string {
        return 'WP_App_Core_Role_Manager';
    }

    protected function getModel(): AbstractPermissionsModel {
        return new PlatformPermissionModel();
    }

    protected function getValidator(): AbstractPermissionsValidator {
        return new PlatformPermissionValidator();
    }

    protected function getCapabilityGroups(): array {
        return $this->model->getCapabilityGroups();
    }

    protected function getAllCapabilities(): array {
        return $this->model->getAllCapabilities();
    }

    protected function getCapabilityDescriptions(): array {
        return $this->model->getCapabilityDescriptions();
    }

    protected function getDefaultCapabilitiesForRole(string $role_slug): array {
        return $this->model->getDefaultCapabilitiesForRole($role_slug);
    }
}
```

**That's it!** Controller selesai - hanya 40 baris, no AJAX logic, no complexity.

#### 2.2. PlatformPermissionModel.php

**Location:** `/wp-app-core/src/Models/Settings/PlatformPermissionModel.php`

```php
<?php
namespace WPAppCore\Models\Settings;

use WPAppCore\Models\Abstract\AbstractPermissionsModel;

class PlatformPermissionModel extends AbstractPermissionsModel {

    protected function getRoleManagerClass(): string {
        return 'WP_App_Core_Role_Manager';
    }

    protected function getAllCapabilities(): array {
        return [
            // Platform Management
            'view_platform_dashboard' => 'Lihat Platform Dashboard',
            'manage_platform_settings' => 'Kelola Platform Settings',
            // ... (all 50+ capabilities)
        ];
    }

    protected function getCapabilityGroups(): array {
        return [
            'platform_management' => [
                'title' => 'Platform',
                'description' => 'Platform Management',
                'caps' => [
                    'view_platform_dashboard',
                    'manage_platform_settings',
                    // ...
                ]
            ],
            // ... (7 groups total)
        ];
    }

    protected function getCapabilityDescriptions(): array {
        return [
            'view_platform_dashboard' => __('Memungkinkan melihat dashboard platform', 'wp-app-core'),
            // ... (all descriptions)
        ];
    }

    protected function getDefaultCapabilitiesForRole(string $role_slug): array {
        $defaults = [
            'platform_super_admin' => [
                'read' => true,
                'view_platform_dashboard' => true,
                // ... (all capabilities for super admin)
            ],
            'platform_admin' => [
                'read' => true,
                'view_platform_dashboard' => true,
                // ... (limited capabilities)
            ],
            // ... (7 roles total)
        ];

        return $defaults[$role_slug] ?? [];
    }
}
```

**That's it!** Model selesai - hanya define data, no logic.

#### 2.3. PlatformPermissionValidator.php

**Location:** `/wp-app-core/src/Validators/Settings/PlatformPermissionValidator.php`

```php
<?php
namespace WPAppCore\Validators\Settings;

use WPAppCore\Validators\Abstract\AbstractPermissionsValidator;

class PlatformPermissionValidator extends AbstractPermissionsValidator {

    protected function getRoleManagerClass(): string {
        return 'WP_App_Core_Role_Manager';
    }

    // Optional: Add custom validation rules
    public function validateSaveRequest(array $data): array {
        $result = parent::validateSaveRequest($data);

        // Add custom validation if needed
        // Example: Prevent removing critical capabilities
        if ($result['valid']) {
            if ($data['role'] === 'platform_super_admin'
                && $data['capability'] === 'view_platform_dashboard'
                && !$data['enabled']) {
                $result['valid'] = false;
                $result['errors'][] = 'Cannot remove dashboard access from Super Admin';
            }
        }

        return $result;
    }
}
```

**That's it!** Validator selesai - inherit dari abstract, optional custom rules.

#### 2.4. Load Template in Settings Tab

**In:** `/wp-app-core/src/Views/templates/settings/tab-permissions.php`

```php
<?php
// Get controller instance
$controller = new \WPAppCore\Controllers\Settings\PlatformPermissionsController();
$controller->init();

// Get view data from controller
$view_data = $controller->getViewModel();

// Extract for template
extract($view_data);

// Load shared template from wp-app-core
require_once WP_APP_CORE_PLUGIN_DIR . 'src/Views/templates/permissions/permission-matrix.php';
```

**That's it!** Tab selesai - 8 baris, no HTML duplication.

---

### Phase 3: Replication to Other Plugins

#### For wp-customer:

1. Copy PlatformPermissionsController → CustomerPermissionsController
2. Copy PlatformPermissionModel → CustomerPermissionModel
3. Copy PlatformPermissionValidator → CustomerPermissionValidator
4. Update tab-permissions.php to load shared template

**Total work:** ~30 minutes, no debugging needed.

#### For wp-agency:

Same as wp-customer.

#### For remaining 17 plugins:

Same pattern - just define capabilities array, everything else works automatically.

---

## Benefits

### Before (Current):
- ❌ 871 lines per plugin (controller + model + template)
- ❌ CSS/JS duplicated 20 times
- ❌ Bug fix = 20 plugin updates
- ❌ New plugin = 24+ hours debugging
- ❌ Inconsistent patterns (AJAX vs Form POST)

### After (With Abstract):
- ✅ ~150 lines per plugin (just data definitions)
- ✅ CSS/JS shared from wp-app-core (1 place)
- ✅ Bug fix = 1 abstract update, all plugins inherit
- ✅ New plugin = 30 minutes setup, zero debugging
- ✅ Consistent AJAX pattern enforced by abstract

**Code Reduction:** 871 → 150 lines per plugin = **82% reduction**
**Time Savings:** 24 hours → 30 minutes per plugin = **98% time savings**
**Maintenance:** 20 places → 1 place = **95% maintenance reduction**

---

## Testing Checklist

### Phase 1 Testing (wp-app-core)
- [x] Create AbstractPermissionsController
- [x] Create AbstractPermissionsModel
- [x] Create AbstractPermissionsValidator
- [x] Create shared template permission-matrix.php
- [x] Create shared CSS permission-matrix.css
- [x] Create shared JS permission-matrix.js with AJAX
- [x] Refactor PlatformPermissionsController to extend abstract
- [x] Refactor PlatformPermissionModel to extend abstract
- [x] Update tab-permissions.php to use shared template
- [ ] Test checkbox save via AJAX
- [ ] Test reset via AJAX with WPModal
- [ ] Test nested tab navigation
- [ ] Test server-side validation
- [ ] Verify no mixing of AJAX/Form POST patterns

### Phase 2 Testing (wp-customer)
- [ ] Create CustomerPermissionsController extending abstract
- [ ] Create CustomerPermissionModel extending abstract
- [ ] Create CustomerPermissionValidator extending abstract
- [ ] Update tab-permissions.php to use shared template
- [ ] Test all functionality works without debugging
- [ ] Verify CSS/JS loaded from wp-app-core
- [ ] Test cross-plugin capabilities display (WP Agency tab)

### Phase 3 Testing (wp-agency)
- [ ] Same as Phase 2
- [ ] Verify all 3 plugins working consistently

---

## Migration Path

### Step 1: Create Abstract in wp-app-core
Create all abstract classes and shared assets.

### Step 2: Migrate wp-app-core
Refactor PlatformPermissionsController to extend abstract.

### Step 3: Test wp-app-core Thoroughly
Ensure all functionality works before replicating.

### Step 4: Migrate wp-customer
Copy pattern, test.

### Step 5: Migrate wp-agency
Copy pattern, test.

### Step 6: Remaining 17 Plugins
Apply same pattern as needed.

---

## Notes

### Why AJAX (Not Form POST)?
User confirmed: "memang seharusnya menggunakan ajax"
- Per-checkbox instant save (better UX)
- No page reload on each save
- Reset uses AJAX with WPModal confirmation
- **Consistent pattern** (no mixing like TODO-1205 caused frustration)

### Why Minimal Abstract?
User requested: "buat saja Abstract yang minimalis tetapi konsisten"
- Only enforce structure via abstract methods
- Don't over-engineer with complex orchestrators
- CSS/JS sharing for consistency
- Focus on eliminating debugging, not adding complexity

### RoleManager Pattern
User confirmed: Keep in class (static utility), not controller.
- Separation of concerns
- Reusability across plugins
- No need for AbstractRoleManager (already consistent)

---

## Success Criteria

✅ Plugin baru bisa dibuat dalam 30 menit
✅ Zero debugging untuk save/reset
✅ CSS/JS consistent across all plugins
✅ Template shared dengan extensibility
✅ Server-side validation included
✅ AJAX pattern consistent (no mixing)
✅ Bug fix propagates to all plugins instantly
✅ 80%+ code reduction per plugin

---

## Open Questions

1. **Asset Enqueue Strategy:**
   - Should shared assets auto-enqueue when AbstractPermissionsController is used?
   - Or require manual enqueue in child controller?

2. **Cross-Plugin Capability Display:**
   - Should abstract provide helper for merging capability groups from other plugins?
   - Or leave manual via current model array merging?

3. **Backward Compatibility:**
   - Should we keep old PermissionModel methods for plugins that haven't migrated?
   - Or force migration all at once?

**Recommendation:** Start with wp-app-core, test thoroughly, then decide based on real usage.
