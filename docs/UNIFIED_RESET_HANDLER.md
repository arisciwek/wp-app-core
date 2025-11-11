# Unified Reset Handler Architecture

**Date**: 2025-01-09
**Version**: 1.0.0
**Author**: arisciwek
**Related**: ASSET_CONTROLLER_REFACTORING.md, SETTINGS_MODEL_CHECKLIST.md

## 📋 Overview

Dokumentasi lengkap arsitektur **Unified Reset Handler** - satu global handler method untuk semua plugin, tapi dengan local identification per controller/tab.

## 🎯 Design Philosophy

**Konsep Utama**:
> "Satu kode reset untuk semua plugin, tetapi mengenali plugin apa yang minta reset dan page apa dari plugin itu yang minta reset"

### Prinsip:
1. ✅ **Global Scope**: Handler method ada di AbstractSettingsController (reusable)
2. ✅ **Local Identification**: Controller slug menentukan AJAX action
3. ✅ **Auto Model Detection**: `$this->model` sudah specific per controller
4. ✅ **DRY Principle**: Tidak ada code duplication
5. ✅ **Scalable**: Tinggal tambah controller baru dengan slug

## 🏗️ Architecture

### 3-Level Structure

```
┌─────────────────────────────────────────────────────────────┐
│ Level 1: LOCAL PAGE (Button Identification)                 │
│ ────────────────────────────────────────────────────────────│
│ File: tab-security-policy.php                                │
│                                                              │
│ <button id="reset-security-policy">Reset</button>           │
│                                                              │
│ Purpose: Menunjukkan TAB mana yang melakukan reset          │
│ Convention: id="reset-{controller-slug}"                    │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│ Level 2: GLOBAL SCOPE (Single Handler Method)               │
│ ────────────────────────────────────────────────────────────│
│ File: AbstractSettingsController.php                        │
│ Method: handleResetSettings() (line 382-406)                │
│                                                              │
│ public function handleResetSettings(): void {                │
│     check_ajax_referer($prefix . '_nonce', 'nonce');        │
│                                                              │
│     if (!current_user_can($this->getSettingsCapability())) {│
│         wp_send_json_error(['message' => 'Permission denied']);│
│     }                                                        │
│                                                              │
│     // ✅ SATU method untuk SEMUA controller                │
│     $defaults = $this->model->getDefaults();                 │
│     $saved = $this->model->saveSettings($defaults);          │
│                                                              │
│     if ($saved) {                                            │
│         wp_send_json_success([                               │
│             'message' => 'Settings reset to defaults.'       │
│         ]);                                                  │
│     }                                                        │
│ }                                                            │
│                                                              │
│ Key: $this->model sudah specific per controller instance!   │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│ Level 3: AJAX ACTION REGISTRATION (Auto per Controller)     │
│ ────────────────────────────────────────────────────────────│
│ File: AbstractSettingsController.php (line 190-201)         │
│                                                              │
│ protected function registerAjaxHandlers(): void {            │
│     $prefix = $this->getPluginPrefix();                      │
│     $controller = $this->getControllerSlug();                │
│                                                              │
│     // Register controller-specific action                   │
│     add_action(                                              │
│         'wp_ajax_reset_' . str_replace('-', '_', $controller),│
│         [$this, 'handleResetSettings']                       │
│     );                                                       │
│ }                                                            │
│                                                              │
│ Results in multiple unique actions:                         │
│ - wp_ajax_reset_security_policy                             │
│ - wp_ajax_reset_security_session                            │
│ - wp_ajax_reset_security_authentication                     │
│ - wp_ajax_reset_general_settings                            │
│                                                              │
│ All pointing to SAME method, but different instances!       │
└─────────────────────────────────────────────────────────────┘
```

## 🔧 Implementation Details

### 1. Abstract Method: getControllerSlug()

**Purpose**: Unique identifier untuk setiap controller

**Location**: AbstractSettingsController.php (line 115)

```php
/**
 * Get controller/tab slug for this controller
 * Used for unique AJAX action registration
 *
 * @return string Controller slug, e.g., 'general', 'security-policy'
 */
abstract protected function getControllerSlug(): string;
```

**Convention**:
- Use kebab-case: `security-policy` (bukan `security_policy`)
- Will be converted to snake_case for action: `reset_security_policy`
- Match dengan button ID di template (tanpa `reset-` prefix)

### 2. AJAX Handler Registration

**Location**: AbstractSettingsController.php (line 190-201)

```php
protected function registerAjaxHandlers(): void {
    $prefix = $this->getPluginPrefix();
    $controller = $this->getControllerSlug();

    // Register controller-specific reset handler
    // Example: wp_ajax_reset_security_policy
    add_action(
        'wp_ajax_reset_' . str_replace('-', '_', $controller),
        [$this, 'handleResetSettings']
    );

    // Also register generic handler for backward compatibility
    add_action('wp_ajax_' . $prefix . '_save_settings', [$this, 'handleSaveSettings']);
    add_action('wp_ajax_' . $prefix . '_reset_settings', [$this, 'handleResetSettings']);
}
```

**Registration Flow**:
```
Controller Init
    ↓
AbstractSettingsController::init()
    ↓
registerAjaxHandlers()
    ↓
getControllerSlug() → 'security-policy'
    ↓
str_replace('-', '_', 'security-policy') → 'security_policy'
    ↓
add_action('wp_ajax_reset_security_policy', ...)
```

### 3. Handler Method

**Location**: AbstractSettingsController.php (line 382-406)

```php
public function handleResetSettings(): void {
    $prefix = $this->getPluginPrefix();

    check_ajax_referer($prefix . '_nonce', 'nonce');

    if (!current_user_can($this->getSettingsCapability())) {
        wp_send_json_error([
            'message' => __('Permission denied.', 'wp-app-core')
        ]);
    }

    // Reset to defaults
    $defaults = $this->model->getDefaults();
    $saved = $this->model->saveSettings($defaults);

    if ($saved) {
        wp_send_json_success([
            'message' => __('Settings reset to defaults.', 'wp-app-core')
        ]);
    } else {
        wp_send_json_error([
            'message' => __('Failed to reset settings.', 'wp-app-core')
        ]);
    }
}
```

**Key Points**:
- ✅ Nonce verification
- ✅ Permission check
- ✅ Uses `$this->model` (already controller-specific!)
- ✅ Standardized response format
- ✅ Cache auto-cleared via model's `saveSettings()`

## 📝 Controller Implementation

### Example: SecurityPolicyController

**File**: src/Controllers/Settings/SecurityPolicyController.php

```php
class SecurityPolicyController extends AbstractSettingsController {

    protected function getPluginSlug(): string {
        return 'wp-app-core';
    }

    protected function getPluginPrefix(): string {
        return 'wpapp';
    }

    protected function getSettingsPageSlug(): string {
        return 'wp-app-core-settings';
    }

    protected function getSettingsCapability(): string {
        return 'manage_options';
    }

    protected function getDefaultTabs(): array {
        return [];
    }

    protected function getModel(): AbstractSettingsModel {
        return new SecurityPolicyModel();  // ← Model specific!
    }

    protected function getValidator(): AbstractSettingsValidator {
        return new SecurityPolicyValidator();
    }

    protected function getControllerSlug(): string {
        return 'security-policy';  // ← CRITICAL! Creates unique action
    }
}
```

## 🔄 Data Flow

### Complete Reset Flow

```
1. User clicks button
   ↓
   Button: id="reset-security-policy"

2. JavaScript sends AJAX
   ↓
   $.ajax({
       action: 'reset_security_policy',  // ← From button ID
       nonce: wpAppCoreSettings.nonce
   })

3. WordPress routes request
   ↓
   do_action('wp_ajax_reset_security_policy')

4. Handler executed
   ↓
   SecurityPolicyController->handleResetSettings()

5. Model reset
   ↓
   $this->model->getDefaults()  // $this->model = SecurityPolicyModel
   $this->model->saveSettings($defaults)

6. Cache cleared (automatic)
   ↓
   AbstractSettingsModel::saveSettings()
       ↓
       update_option()
       ↓
       Cache cleared via onOptionUpdated() hook

7. Response sent
   ↓
   wp_send_json_success(['message' => '...'])

8. JavaScript handles response
   ↓
   showMessage(response.data.message, 'success')
   window.location.reload()
```

## ✨ Key Features

### 1. Auto Model Detection

**Tidak perlu manual routing!**

```php
// ❌ OLD way (manual routing):
public function handleResetSettings() {
    $controller = $_POST['controller'];

    switch($controller) {
        case 'security-policy':
            $model = new SecurityPolicyModel();
            break;
        case 'security-session':
            $model = new SecuritySessionModel();
            break;
        // ... more cases
    }

    $model->resetToDefaults();
}

// ✅ NEW way (automatic):
public function handleResetSettings() {
    // $this->model already correct model!
    $this->model->resetToDefaults();
}
```

**Kenapa bisa?**
Karena setiap controller instance punya model sendiri:
- `SecurityPolicyController` → `$this->model` = `SecurityPolicyModel`
- `SecuritySessionController` → `$this->model` = `SecuritySessionModel`
- Dst...

### 2. Multiple Actions, One Method

**Efficient!**

```
AJAX Actions Registered:
- wp_ajax_reset_security_policy      → handleResetSettings()
- wp_ajax_reset_security_session     → handleResetSettings()
- wp_ajax_reset_security_authentication → handleResetSettings()
- wp_ajax_reset_general_settings     → handleResetSettings()

Same method, different instances!
```

### 3. Type Safety

**Compile-time checks**

```php
// All controllers MUST implement:
abstract protected function getControllerSlug(): string;

// PHP will error if missing:
// Fatal error: Class X must implement abstract method getControllerSlug()
```

## 🧪 Testing Guide

### Test Case 1: Security Policy Reset

```bash
# 1. Open browser console
# 2. Navigate to Platform Settings → Security Policy
# 3. Run in console:
wpAppCoreSettings

# Expected output:
{
  ajaxUrl: "http://example.com/wp-admin/admin-ajax.php",
  nonce: "abc123...",
  currentTab: "security-policy",
  i18n: {...}
}

# 4. Click "Reset to Default" button
# 5. Monitor network tab:
# Request URL: admin-ajax.php
# Request Method: POST
# Form Data:
#   action: reset_security_policy
#   nonce: abc123...

# Expected response (200 OK):
{
  success: true,
  data: {
    message: "Settings reset to defaults."
  }
}
```

### Test Case 2: Verify Unique Actions

```bash
# Check registered actions in WordPress
wp eval "
global \$wp_filter;
\$actions = \$wp_filter['wp_ajax_reset_security_policy'] ?? null;
if (\$actions) {
    echo 'wp_ajax_reset_security_policy is registered\n';
} else {
    echo 'NOT registered\n';
}
"

# Should output: wp_ajax_reset_security_policy is registered
```

### Test Case 3: Model Instance Verification

```php
// Add to SecurityPolicyController temporarily
public function handleResetSettings(): void {
    error_log('DEBUG: Model class = ' . get_class($this->model));
    parent::handleResetSettings();
}

// After reset, check debug.log:
// Should show: DEBUG: Model class = WPAppCore\Models\Settings\SecurityPolicyModel
```

## 📊 Comparison Table

| Aspect | Monolithic Approach | Unified Handler (Current) |
|--------|-------------------|--------------------------|
| **Code Location** | One file with all handlers | Abstract base class |
| **Registration** | Manual per tab | Automatic via controller slug |
| **Model Selection** | if/switch statements | Automatic via $this->model |
| **Extensibility** | Hard - edit monolithic file | Easy - add new controller |
| **Maintainability** | Low - many similar methods | High - DRY principle |
| **Testability** | Hard - mock entire class | Easy - mock individual controllers |
| **Code Lines** | ~100 lines per handler | ~15 lines (abstract) |

## 🚀 Best Practices

### 1. Controller Slug Naming

```php
// ✅ GOOD - kebab-case
protected function getControllerSlug(): string {
    return 'security-policy';
}

// ❌ BAD - snake_case
protected function getControllerSlug(): string {
    return 'security_policy';
}

// ❌ BAD - camelCase
protected function getControllerSlug(): string {
    return 'securityPolicy';
}
```

### 2. Button ID Convention

```php
// Template: tab-security-policy.php
<button id="reset-security-policy">  ← Matches controller slug!

// Controller:
protected function getControllerSlug(): string {
    return 'security-policy';  ← Same!
}

// JavaScript:
$('#reset-security-policy').click(...)  ← Consistent!

// AJAX action registered:
wp_ajax_reset_security_policy  ← Auto-converted!
```

### 3. Error Handling

```javascript
// Always handle both success and error
$.ajax({
    action: 'reset_security_policy',
    nonce: wpAppCoreSettings.nonce,
    success: function(response) {
        if (response.success) {
            showMessage(response.data.message, 'success');
            window.location.reload();
        } else {
            showMessage(response.data.message || 'Error', 'error');
        }
    },
    error: function(jqXHR, textStatus, errorThrown) {
        console.error('AJAX error:', textStatus, errorThrown);
        showMessage('AJAX error occurred while resetting settings.', 'error');
    }
});
```

## 🔗 Related Patterns

### 1. Cache Invalidation

Reset handler automatically clears cache via:
```
handleResetSettings()
    ↓
$this->model->saveSettings($defaults)
    ↓
AbstractSettingsModel::saveSettings()
    ↓
update_option()
    ↓
Hook: update_option_{option_name}
    ↓
AbstractSettingsModel::onOptionUpdated()
    ↓
$this->clearCache()
```

See: [SETTINGS_MODEL_CHECKLIST.md](SETTINGS_MODEL_CHECKLIST.md)

### 2. Asset Loading

JavaScript `wpAppCoreSettings` provided by:
```
SettingsPageAssets strategy
    ↓
wp_localize_script('wpapp-settings-base', 'wpAppCoreSettings', [...])
```

See: [ASSET_CONTROLLER_REFACTORING.md](ASSET_CONTROLLER_REFACTORING.md)

## 📝 Changelog

### 1.0.0 - 2025-01-09
- Initial implementation
- Added getControllerSlug() abstract method
- Implemented unique AJAX action registration
- Single handleResetSettings() method for all controllers
- Automatic model detection via $this->model
- Full documentation created
