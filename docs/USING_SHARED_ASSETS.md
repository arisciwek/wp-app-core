# Using Shared Assets from Other Plugins

**Date**: 2025-01-09
**Version**: 1.0.0

## Overview

wp-app-core provides **reusable/shared assets** (CSS & JS) yang dapat digunakan oleh plugin lain tanpa duplikasi code. Ini mengikuti **DRY Principle** - Don't Repeat Yourself.

## Available Shared Assets

| Tab Slug | CSS File | Description |
|----------|----------|-------------|
| `general` | `general-tab-style.css` | Company info, regional settings |
| `security-policy` | `security-policy-tab-style.css` | Security policies, audit logs |
| `security-session` | `security-session-tab-style.css` | Session management, login protection |
| `security-authentication` | `security-authentication-tab-style.css` | 2FA, IP whitelist, password policy |
| `email` | `email-tab-style.css` | SMTP, notification settings |
| `demo-data` | `demo-data-tab-style.css` | Demo data generation & management |

## Method 1: Direct Access (Recommended)

### From wp-customer plugin:

```php
<?php
/**
 * Customer Settings Page Assets Strategy
 *
 * File: /wp-customer/src/Controllers/Assets/Strategies/SettingsPageAssets.php
 */

namespace WPCustomer\Controllers\Assets\Strategies;

use WPCustomer\Controllers\Assets\AssetStrategyInterface;

class SettingsPageAssets implements AssetStrategyInterface {

    public function enqueue_styles(): void {
        $current_tab = $this->get_current_tab();

        // Load SHARED CSS from wp-app-core for common tabs
        if (in_array($current_tab, ['security-policy', 'security-session', 'demo-data'])) {
            wp_enqueue_style(
                'wpc-shared-' . $current_tab,
                WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/' . $current_tab . '-tab-style.css',
                [],
                WP_APP_CORE_VERSION
            );
        }

        // Load customer-specific CSS for custom tabs
        if ($current_tab === 'customer-profile') {
            wp_enqueue_style(
                'wpc-customer-profile',
                WP_CUSTOMER_PLUGIN_URL . 'assets/css/settings/customer-profile-tab-style.css',
                [],
                WP_CUSTOMER_VERSION
            );
        }
    }
}
```

### From wp-agency plugin:

```php
<?php
/**
 * Agency Settings Page Assets Strategy
 *
 * File: /wp-agency/src/Controllers/Assets/Strategies/SettingsPageAssets.php
 */

namespace WPAgency\Controllers\Assets\Strategies;

use WPAgency\Controllers\Assets\AssetStrategyInterface;

class SettingsPageAssets implements AssetStrategyInterface {

    public function enqueue_styles(): void {
        $current_tab = $this->get_current_tab();

        // Reuse wp-app-core's general tab CSS
        if ($current_tab === 'general') {
            wp_enqueue_style(
                'wpa-shared-general',
                WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/general-tab-style.css',
                [],
                WP_APP_CORE_VERSION
            );
        }

        // Reuse demo-data CSS
        if ($current_tab === 'demo-data') {
            wp_enqueue_style(
                'wpa-shared-demo-data',
                WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/demo-data-tab-style.css',
                [],
                WP_APP_CORE_VERSION
            );
        }
    }
}
```

## Method 2: Using SharedSettingsAssets Helper (Alternative)

```php
<?php
/**
 * Using SharedSettingsAssets helper methods
 *
 * File: /wp-customer/src/Controllers/Assets/Strategies/SettingsPageAssets.php
 */

namespace WPCustomer\Controllers\Assets\Strategies;

use WPCustomer\Controllers\Assets\AssetStrategyInterface;
use WPAppCore\Controllers\Assets\Strategies\SharedSettingsAssets;

class SettingsPageAssets implements AssetStrategyInterface {

    public function enqueue_styles(): void {
        $current_tab = $this->get_current_tab();

        // Check if shared asset exists
        if (SharedSettingsAssets::has_shared_asset($current_tab, 'style')) {
            // Enqueue shared style
            SharedSettingsAssets::enqueue_shared_tab_style(
                $current_tab,
                'wpc-shared-' . $current_tab,
                [] // dependencies
            );
        } else {
            // Load plugin-specific asset
            $this->enqueue_custom_tab_style($current_tab);
        }
    }

    public function enqueue_scripts(): void {
        $current_tab = $this->get_current_tab();

        // Check if shared script exists
        if (SharedSettingsAssets::has_shared_asset($current_tab, 'script')) {
            // Enqueue shared script with localization
            SharedSettingsAssets::enqueue_shared_tab_script(
                $current_tab,
                'wpc-shared-' . $current_tab . '-script',
                ['jquery'], // dependencies
                [
                    'object_name' => 'wpcCustomerSettings',
                    'data' => [
                        'ajaxUrl' => admin_url('admin-ajax.php'),
                        'nonce' => wp_create_nonce('wpc_nonce'),
                    ]
                ]
            );
        }
    }
}
```

## Benefits

### ✅ Code Reduction
**Before (Each plugin has its own):**
```
wp-app-core/assets/css/settings/security-policy-tab-style.css   (5KB)
wp-customer/assets/css/settings/security-policy-tab-style.css   (5KB)
wp-agency/assets/css/settings/security-policy-tab-style.css     (5KB)
wp-staff/assets/css/settings/security-policy-tab-style.css      (5KB)
Total: 20KB + maintenance nightmare
```

**After (Shared from wp-app-core):**
```
wp-app-core/assets/css/settings/security-policy-tab-style.css   (5KB)
Total: 5KB + single source of truth
```

### ✅ Consistency
- All plugins use same styling for common tabs
- Updates di wp-app-core otomatis apply ke semua plugins
- No styling conflicts between plugins

### ✅ Maintainability
- Bug fixes hanya di 1 tempat
- Feature additions benefit all plugins
- Easier to update and test

## Testing Shared Assets

### Test dari wp-customer:

```bash
# Check if CSS accessible
curl -I http://wppm.local/wp-content/plugins/wp-app-core/assets/css/settings/demo-data-tab-style.css

# Should return: 200 OK
```

### Test dari wp-agency:

```php
// In settings page controller
$demo_data_css_url = WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/demo-data-tab-style.css';
$demo_data_css_path = WP_APP_CORE_PLUGIN_DIR . 'assets/css/settings/demo-data-tab-style.css';

// Verify file exists
if (file_exists($demo_data_css_path)) {
    echo "✅ Shared asset accessible!";
    wp_enqueue_style('wpa-demo-data', $demo_data_css_url, [], WP_APP_CORE_VERSION);
}
```

## Plugin Constants Required

Make sure these constants are defined in each plugin:

```php
// In wp-customer/wp-customer.php
if (!defined('WP_APP_CORE_PLUGIN_URL')) {
    define('WP_APP_CORE_PLUGIN_URL', plugin_dir_url(__FILE__) . '../wp-app-core/');
}

if (!defined('WP_APP_CORE_VERSION')) {
    define('WP_APP_CORE_VERSION', '2.1.0');
}
```

## Real-World Example

### Scenario: wp-customer plugin needs Security Policy tab

**Option A: Old Way (Duplicate)**
```
❌ Create /wp-customer/assets/css/settings/security-policy-tab-style.css
❌ Duplicate 5KB of CSS
❌ Maintain separately
```

**Option B: New Way (Shared)**
```
✅ Use WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/security-policy-tab-style.css'
✅ Zero duplication
✅ Auto-updated when wp-app-core updates
```

## Summary

| Aspect | Without Sharing | With Shared Assets |
|--------|----------------|-------------------|
| **Code Duplication** | ❌ High (4-5x) | ✅ None |
| **Maintenance** | ❌ Complex | ✅ Simple |
| **Consistency** | ❌ Risk of drift | ✅ Guaranteed |
| **File Size** | ❌ 20KB+ | ✅ 5KB |
| **Updates** | ❌ Update all plugins | ✅ Update once |

**Recommendation**: Always check `SharedSettingsAssets::has_shared_asset()` first before creating plugin-specific assets for common tabs!
