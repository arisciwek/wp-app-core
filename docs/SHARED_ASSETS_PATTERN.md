# Shared Assets Pattern

**Date**: 2025-01-09
**Version**: 1.0.0
**Author**: arisciwek
**Related**: ASSET_CONTROLLER_REFACTORING.md

## 📋 Overview

Pattern untuk **reusable CSS/JS assets** yang dapat digunakan oleh multiple plugins tanpa duplikasi code. wp-app-core menyediakan shared assets yang dapat digunakan oleh wp-customer, wp-agency, dan plugin lainnya.

## 🎯 Problem Statement

### Sebelum Shared Assets:

```
wp-app-core/
└── assets/css/settings/
    ├── security-policy-tab-style.css  (200 lines)
    └── security-policy-tab-script.js  (300 lines)

wp-customer/
└── assets/css/settings/
    ├── security-policy-tab-style.css  (200 lines) ← DUPLICATE!
    └── security-policy-tab-script.js  (300 lines) ← DUPLICATE!

wp-agency/
└── assets/css/settings/
    ├── security-policy-tab-style.css  (200 lines) ← DUPLICATE!
    └── security-policy-tab-script.js  (300 lines) ← DUPLICATE!

Total: 600 lines × 3 = 1800 lines duplicated code!
```

### Problems:
1. ❌ **Code Duplication**: Same CSS/JS di multiple plugins
2. ❌ **Maintenance Nightmare**: Bug fix harus di 3 tempat
3. ❌ **Version Conflicts**: Different versions di different plugins
4. ❌ **File Size**: Unnecessary load pada production
5. ❌ **Inconsistency**: UI/UX berbeda antar plugins

## ✅ Solution: Shared Assets in wp-app-core

### Setelah Shared Assets:

```
wp-app-core/                           ← SOURCE OF TRUTH
└── assets/
    ├── css/settings/
    │   ├── security-policy-tab-style.css  (200 lines) ← SINGLE SOURCE
    │   ├── security-session-tab-style.css
    │   ├── general-tab-style.css
    │   └── email-tab-style.css
    └── js/settings/
        ├── security-policy-tab-script.js  (300 lines) ← SINGLE SOURCE
        ├── security-session-tab-script.js
        ├── general-tab-script.js
        └── email-tab-script.js

wp-customer/
└── SettingsPageAssets.php
    └── Uses wp-app-core shared assets  ← NO DUPLICATION!

wp-agency/
└── SettingsPageAssets.php
    └── Uses wp-app-core shared assets  ← NO DUPLICATION!

Total: 500 lines (shared) + minimal plugin-specific overrides
Reduction: 73% less code!
```

## 🏗️ Architecture

### Pattern: Provider-Consumer

```
┌──────────────────────────────────────────────────────┐
│ wp-app-core (PROVIDER)                               │
│ ────────────────────────────────────────────────────│
│ SharedSettingsAssets                                 │
│ ├── get_shared_styles()                              │
│ ├── get_shared_scripts()                             │
│ ├── enqueue_shared_tab_style()                       │
│ ├── enqueue_shared_tab_script()                      │
│ └── has_shared_asset()                               │
└──────────────────────────────────────────────────────┘
                        ↓ provides
    ┌───────────────────────────────────────┐
    │         Shared Tab Assets             │
    │ ────────────────────────────────────│
    │ - security-policy-tab                 │
    │ - security-session-tab                │
    │ - security-authentication-tab         │
    │ - general-tab                         │
    │ - email-tab                           │
    └───────────────────────────────────────┘
                        ↓ consumed by
┌──────────────────────────────────────────────────────┐
│ wp-customer, wp-agency, etc (CONSUMERS)              │
│ ────────────────────────────────────────────────────│
│ SettingsPageAssets Strategy                          │
│ └── Uses SharedSettingsAssets helpers                │
│     OR direct URL to wp-app-core assets               │
└──────────────────────────────────────────────────────┘
```

## 📁 File Structure

### wp-app-core (Provider):

```
wp-app-core/
├── src/Controllers/Assets/Strategies/
│   └── SharedSettingsAssets.php      ← Helper class
└── assets/
    ├── css/settings/                  ← SHARED CSS
    │   ├── general-tab-style.css
    │   ├── email-tab-style.css
    │   ├── security-policy-tab-style.css
    │   ├── security-session-tab-style.css
    │   └── security-authentication-tab-style.css
    └── js/settings/                   ← SHARED JS
        ├── general-tab-script.js
        ├── email-tab-script.js
        ├── security-policy-tab-script.js
        ├── security-session-tab-script.js
        └── security-authentication-tab-script.js
```

## 🔧 Implementation

### Method 1: Using Helper Methods (Recommended)

**wp-customer SettingsPageAssets.php**:

```php
<?php
namespace WPCustomer\Controllers\Assets\Strategies;

use WPAppCore\Controllers\Assets\Strategies\SharedSettingsAssets;

class SettingsPageAssets implements AssetStrategyInterface {

    private function enqueue_tab_style(string $tab): void {
        // ✅ Use shared asset dari wp-app-core
        $enqueued = SharedSettingsAssets::enqueue_shared_tab_style(
            $tab,
            'wpc-settings-' . $tab,  // Custom handle for wp-customer
            ['wpc-settings-base']     // Dependencies
        );

        if (!$enqueued) {
            // Fallback: Load plugin-specific asset jika shared tidak ada
            $this->enqueue_plugin_specific_style($tab);
        }
    }

    private function enqueue_tab_script(string $tab): void {
        // ✅ Use shared asset dari wp-app-core
        $enqueued = SharedSettingsAssets::enqueue_shared_tab_script(
            $tab,
            'wpc-settings-' . $tab,  // Custom handle
            ['jquery', 'wpc-settings-base'],  // Dependencies
            [
                'object_name' => 'wpcSettings',  // Custom localization
                'data' => [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wpc_nonce'),
                ]
            ]
        );

        if (!$enqueued) {
            // Fallback: Load plugin-specific asset jika shared tidak ada
            $this->enqueue_plugin_specific_script($tab);
        }
    }
}
```

### Method 2: Direct URL (Simple)

**wp-agency SettingsPageAssets.php**:

```php
<?php
namespace WPAgency\Controllers\Assets\Strategies;

class SettingsPageAssets implements AssetStrategyInterface {

    private function enqueue_tab_style(string $tab): void {
        // ✅ Direct URL ke wp-app-core shared asset
        if (defined('WP_APP_CORE_PLUGIN_URL')) {
            wp_enqueue_style(
                'wpa-settings-' . $tab,
                WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/' . $tab . '-tab-style.css',
                ['wpa-settings-base'],
                WP_APP_CORE_VERSION
            );
        }
    }

    private function enqueue_tab_script(string $tab): void {
        // ✅ Direct URL ke wp-app-core shared asset
        if (defined('WP_APP_CORE_PLUGIN_URL')) {
            wp_enqueue_script(
                'wpa-settings-' . $tab,
                WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/' . $tab . '-tab-script.js',
                ['jquery', 'wpa-settings-base'],
                WP_APP_CORE_VERSION,
                true
            );

            // Custom localization untuk wp-agency
            wp_localize_script('wpa-settings-' . $tab, 'wpaSettings', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpa_nonce'),
            ]);
        }
    }
}
```

### Method 3: Check Availability First

```php
private function enqueue_tab_style(string $tab): void {
    // ✅ Check jika shared asset available
    if (SharedSettingsAssets::has_shared_asset($tab, 'style')) {
        SharedSettingsAssets::enqueue_shared_tab_style(
            $tab,
            'wpc-settings-' . $tab
        );
    } else {
        // Load plugin-specific
        $this->load_own_style($tab);
    }
}
```

## 🎨 Override Pattern

Plugin dapat override atau extend shared assets:

### CSS Override:

```php
// Load shared base style
SharedSettingsAssets::enqueue_shared_tab_style(
    'security-policy',
    'wpc-security-policy-base'
);

// Add plugin-specific overrides
wp_enqueue_style(
    'wpc-security-policy-override',
    WPC_PLUGIN_URL . 'assets/css/security-policy-override.css',
    ['wpc-security-policy-base'],  // Depend on shared
    WPC_VERSION
);
```

**security-policy-override.css**:
```css
/* Override hanya yang perlu diubah */
.security-policy-tab .custom-section {
    background: var(--wpc-primary-color); /* Plugin-specific color */
}

/* Semua base styles dari shared tetap digunakan */
```

### JavaScript Extension:

```php
// Load shared base script
SharedSettingsAssets::enqueue_shared_tab_script(
    'security-policy',
    'wpc-security-policy-base'
);

// Add plugin-specific extensions
wp_enqueue_script(
    'wpc-security-policy-extension',
    WPC_PLUGIN_URL . 'assets/js/security-policy-extension.js',
    ['wpc-security-policy-base'],  // Depend on shared
    WPC_VERSION,
    true
);
```

**security-policy-extension.js**:
```javascript
// Extend base functionality
jQuery(document).ready(function($) {
    // Shared functionality already loaded

    // Add customer-specific features
    $('.wpc-custom-feature').on('click', function() {
        // Plugin-specific logic
    });
});
```

## 🔌 WordPress Hooks

wp-app-core provides hooks untuk plugins customize loading:

### Hook 1: After Shared Style Loaded

```php
// In wp-customer
add_action('wpapp_after_shared_tab_style', function($tab, $handle) {
    if ($tab === 'security-policy') {
        // Add customer-specific style
        wp_enqueue_style(
            'wpc-security-policy-custom',
            WPC_PLUGIN_URL . 'assets/css/security-policy-custom.css',
            [$handle],  // Depend on shared
            WPC_VERSION
        );
    }
}, 10, 2);
```

### Hook 2: After Shared Script Loaded

```php
// In wp-agency
add_action('wpapp_after_shared_tab_script', function($tab, $handle) {
    if ($tab === 'email') {
        // Add agency-specific script
        wp_enqueue_script(
            'wpa-email-custom',
            WPA_PLUGIN_URL . 'assets/js/email-custom.js',
            [$handle],  // Depend on shared
            WPA_VERSION,
            true
        );

        // Custom localization
        wp_localize_script('wpa-email-custom', 'wpaEmailSettings', [
            'smtpProvider' => 'custom-provider',
        ]);
    }
}, 10, 2);
```

## 📊 Available Shared Assets

### Shared Tab Styles:

| Tab Slug | Filename | Use Case |
|----------|----------|----------|
| `general` | general-tab-style.css | Company info, regional settings |
| `email` | email-tab-style.css | SMTP, notification settings |
| `security-policy` | security-policy-tab-style.css | Security policies, audit |
| `security-session` | security-session-tab-style.css | Session management, login protection |
| `security-authentication` | security-authentication-tab-style.css | 2FA, IP whitelist, password policy |

### Shared Tab Scripts:

| Tab Slug | Filename | Features |
|----------|----------|----------|
| `general` | general-tab-script.js | Form validation, regional settings |
| `email` | email-tab-script.js | SMTP testing, preview emails |
| `security-policy` | security-policy-tab-script.js | Policy validation, file type checks |
| `security-session` | security-session-tab-script.js | Session monitoring, login history |
| `security-authentication` | security-authentication-tab-script.js | 2FA setup, IP validation, password strength |

## ✅ Benefits

### Code Reuse:
- ✅ Single source of truth untuk shared functionality
- ✅ DRY principle - no duplication
- ✅ Consistent UI/UX across plugins

### Maintenance:
- ✅ Bug fix di satu tempat, semua plugins benefit
- ✅ Feature enhancement otomatis available untuk semua
- ✅ Version management terpusat

### Performance:
- ✅ Browser cache shared assets across plugins
- ✅ Smaller total file size
- ✅ Faster page loads

### Development:
- ✅ Faster plugin development - reuse existing assets
- ✅ Focus on plugin-specific features
- ✅ Easy to extend or override

## 🧪 Testing Checklist

### For wp-app-core (Provider):
- [ ] All shared assets exist in assets/css/settings/ and assets/js/settings/
- [ ] SharedSettingsAssets class provides all helper methods
- [ ] Hooks `wpapp_after_shared_tab_style` and `wpapp_after_shared_tab_script` fire correctly

### For Consumer Plugins (wp-customer, wp-agency):
- [ ] Can load shared assets from wp-app-core
- [ ] Can override shared assets with plugin-specific styles
- [ ] Custom localization works correctly
- [ ] Hooks can add additional assets
- [ ] Fallback works if shared asset not available

## 📝 Best Practices

### 1. Always Check Availability

```php
// ✅ GOOD - Check first
if (defined('WP_APP_CORE_PLUGIN_URL')) {
    SharedSettingsAssets::enqueue_shared_tab_style($tab, $handle);
} else {
    $this->load_own_style($tab);
}

// ❌ BAD - Assume always available
SharedSettingsAssets::enqueue_shared_tab_style($tab, $handle);
```

### 2. Use Unique Handles

```php
// ✅ GOOD - Plugin-prefixed handle
'wpc-settings-security-policy'  // wp-customer
'wpa-settings-security-policy'  // wp-agency

// ❌ BAD - Generic handle
'settings-security-policy'  // Conflict risk
```

### 3. Proper Dependencies

```php
// ✅ GOOD - Explicit dependencies
wp_enqueue_style(
    'wpc-security-policy',
    $url,
    ['wpapp-settings-security-policy'],  // Depend on shared
    $version
);

// ❌ BAD - No dependencies
wp_enqueue_style('wpc-security-policy', $url, [], $version);
```

### 4. Version Management

```php
// ✅ GOOD - Use wp-app-core version for shared assets
wp_enqueue_style(
    $handle,
    WP_APP_CORE_PLUGIN_URL . $file,
    [],
    WP_APP_CORE_VERSION  // ← Shared asset version
);

// Add plugin-specific with own version
wp_enqueue_style(
    'wpc-override',
    WPC_PLUGIN_URL . $file,
    [],
    WPC_VERSION  // ← Plugin version
);
```

## 🔗 Related Documentation

- [ASSET_CONTROLLER_REFACTORING.md](ASSET_CONTROLLER_REFACTORING.md) - Asset loading architecture
- [SETTINGS_MODEL_CHECKLIST.md](SETTINGS_MODEL_CHECKLIST.md) - Settings implementation

## 📈 Metrics

### Code Reduction Example:

**Before** (Each plugin has own assets):
```
wp-app-core:   500 lines (CSS + JS)
wp-customer:   500 lines (duplicate)
wp-agency:     500 lines (duplicate)
wp-branch:     500 lines (duplicate)
─────────────────────────────────
Total:        2000 lines
```

**After** (Shared assets):
```
wp-app-core:   500 lines (shared)
wp-customer:    50 lines (plugin-specific overrides)
wp-agency:      50 lines (plugin-specific overrides)
wp-branch:      50 lines (plugin-specific overrides)
─────────────────────────────────
Total:         650 lines
Reduction:    67.5% less code!
```

### Maintenance Impact:
- **Before**: Bug fix requires 4 PRs (one per plugin)
- **After**: Bug fix requires 1 PR (wp-app-core only)
- **Time Saved**: 75% reduction in maintenance time

## 🚀 Future Enhancements

### Planned Features:
1. **Asset Versioning API**: Check compatibility between consumer and provider
2. **Dynamic Asset Discovery**: Auto-detect available shared assets
3. **Asset Manifest**: JSON file listing all available shared assets
4. **CDN Support**: Option to serve shared assets from CDN
5. **Build System**: Minified versions of shared assets

## 🔄 Version History

### 1.0.0 - 2025-01-09
- Initial shared assets pattern
- SharedSettingsAssets helper class
- Hook system for extensions
- Documentation created
