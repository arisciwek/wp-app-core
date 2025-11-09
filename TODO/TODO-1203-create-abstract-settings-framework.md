# TODO-1203: Create Abstract Settings Framework

**Created:** 2025-11-09
**Version:** 1.0.0
**Status:** Pending
**Priority:** HIGH
**Context:** Code reusability & standardization across plugins
**Dependencies:**
- TODO-1201 (AbstractValidator - completed) ❌ NOT USED - Entity validator not suitable for settings
- TODO-1202 (AbstractCacheManager - completed) ✅ WILL BE USED in AbstractSettingsModel

---

## 🎯 Objective

Create abstract framework untuk Settings functionality yang bisa digunakan oleh semua plugins:
- **AbstractSettingsController.php** - Base controller untuk settings pages
- **AbstractSettingsModel.php** - Base model untuk settings data management
- **AbstractSettingsValidator.php** - Base validator untuk settings validation

**Benefit:**
- ✅ Eliminate code duplication across wp-customer, wp-agency, wp-app-core
- ✅ Standardized tab system dengan hook-based extension
- ✅ Shared templates & assets (CSS/JS)
- ✅ Consistent validation & sanitization patterns
- ✅ Plugin-specific tabs via hooks

---

## 📋 Current State Analysis

### Duplicate Settings Files Across Plugins

#### WP Customer (13 files)
```
/wp-customer/src/Controllers/SettingsController.php          (~800 lines)
/wp-customer/src/Models/Settings/SettingsModel.php
/wp-customer/src/Views/templates/settings/settings_page.php
/wp-customer/src/Views/templates/settings/tab-demo-data.php
/wp-customer/src/Views/templates/settings/tab-general.php
/wp-customer/src/Views/templates/settings/tab-permissions.php
/wp-customer/assets/css/settings/demo-data-tab-style.css
/wp-customer/assets/css/settings/general-tab-style.css
/wp-customer/assets/css/settings/permissions-tab-style.css
/wp-customer/assets/css/settings/settings-style.css
/wp-customer/assets/js/settings/customer-demo-data-tab-script.js
/wp-customer/assets/js/settings/customer-permissions-tab-script.js
/wp-customer/assets/js/settings/settings-script.js
```

#### WP Agency (13 files)
```
/wp-agency/src/Controllers/SettingsController.php            (~800 lines - DUPLICATE!)
/wp-agency/src/Models/Settings/SettingsModel.php
/wp-agency/src/Views/templates/settings/settings_page.php
/wp-agency/src/Views/templates/settings/tab-demo-data.php
/wp-agency/src/Views/templates/settings/tab-general.php
/wp-agency/src/Views/templates/settings/tab-permissions.php
/wp-agency/assets/css/settings/demo-data-tab-style.css
/wp-agency/assets/css/settings/general-tab-style.css
/wp-agency/assets/css/settings/permissions-tab-style.css
/wp-agency/assets/css/settings/settings-style.css
/wp-agency/assets/js/settings/agency-demo-data-tab-script.js
/wp-agency/assets/js/settings/agency-permissions-tab-script.js
/wp-agency/assets/js/settings/settings-script.js
```

#### WP App Core (13 files)
```
/wp-app-core/src/Controllers/PlatformSettingsController.php  (~870 lines - BASE)
/wp-app-core/src/Models/Settings/PlatformSettingsModel.php
/wp-app-core/src/Views/templates/settings/settings-page.php
/wp-app-core/src/Views/templates/settings/tab-demo-data.php
/wp-app-core/src/Views/templates/settings/tab-general.php
/wp-app-core/src/Views/templates/settings/tab-permissions.php
/wp-app-core/assets/css/settings/demo-data-tab-style.css
/wp-app-core/assets/css/settings/permissions-tab-style.css
/wp-app-core/assets/css/settings/settings-style.css
/wp-app-core/assets/js/settings/general-tab-script.js
/wp-app-core/assets/js/settings/permissions-tab-script.js
/wp-app-core/assets/js/settings/settings-script.js
```

**Problem:**
- ~39 files duplicated across 3 plugins
- ~2,400+ lines of duplicated code (controllers only)
- Same tab structure repeated (General, Permissions, Demo Data)
- Same CSS/JS patterns repeated

---

## 🏗️ Proposed Solution

### Architecture

```
wp-app-core (Framework)
├── AbstractSettingsController.php    (Base controller logic)
├── AbstractSettingsModel.php         (Base model logic)
├── AbstractSettingsValidator.php     (Base validation logic)
├── Templates/Settings/               (Shared templates)
│   ├── settings-page.php            (Main page wrapper)
│   ├── tab-general.php              (General tab template)
│   ├── tab-permissions.php          (Permissions tab template)
│   └── tab-demo-data.php            (Demo data tab template)
└── assets/                           (Shared assets)
    ├── css/settings/
    │   ├── settings-style.css       (Base styles)
    │   ├── general-tab-style.css
    │   ├── permissions-tab-style.css
    │   └── demo-data-tab-style.css
    └── js/settings/
        ├── settings-script.js       (Base scripts)
        ├── general-tab-script.js
        ├── permissions-tab-script.js
        └── demo-data-tab-script.js

wp-customer (Plugin - extends core)
├── SettingsController.php            (extends AbstractSettingsController)
│   └── registerCustomTabs()         (Add customer-specific tabs)
├── SettingsModel.php                 (extends AbstractSettingsModel)
└── Views/settings/                   (Plugin-specific tab content)
    ├── tab-membership-levels.php    (Custom tab)
    ├── tab-membership-features.php  (Custom tab)
    └── tab-invoice-payment.php      (Custom tab)

wp-agency (Plugin - extends core)
├── SettingsController.php            (extends AbstractSettingsController)
│   └── registerCustomTabs()         (Add agency-specific tabs)
├── SettingsModel.php                 (extends AbstractSettingsModel)
└── Views/settings/                   (Plugin-specific tab content)
    └── tab-commission.php           (Custom tab)
```

### Hook System

**Naming Convention:**
- **Plugin-specific hooks** using `{plugin_prefix}` (e.g., `wpc_`, `wpa_`, `wpapp_`)
- Each plugin defines its own tabs via `getDefaultTabs()` (no global hook needed)
- Plugin-specific hooks for extending within same plugin only

**Plugin provides its own hooks:**
```php
// In WP Customer SettingsController
// Each plugin registers its own tabs in getDefaultTabs()

protected function getDefaultTabs(): array {
    $tabs = [
        'general' => __('General', 'wp-customer'),
        'permissions' => __('Permissions', 'wp-customer'),
        'demo-data' => __('Demo Data', 'wp-customer'),
    ];

    // Hook for extending WP Customer settings tabs
    // Other code can add tabs to wp-customer settings page
    return apply_filters('wpc_settings_tabs', $tabs);
}

// Tab content rendering uses plugin-specific action
// wpc_settings_tab_content_{tab_slug}
add_action('wpc_settings_tab_content_membership-levels', function() {
    include WP_CUSTOMER_PATH . 'src/Views/settings/tab-membership-levels.php';
});
```

**Why plugin-specific hooks (not global)?**
- Each plugin has its own settings page
- No need to filter by plugin_slug - the hook itself is plugin-specific
- Cleaner: `wpc_settings_tabs` vs `wpapp_settings_tabs` + checking plugin slug
- Consistent with WordPress standards (each plugin controls its own hooks)

---

## 📝 Implementation Plan

### Phase 1: Create Abstract Classes

#### 1.1 AbstractSettingsController

**File:** `/wp-app-core/src/Controllers/AbstractSettingsController.php`

**Key Methods:**
```php
<?php
namespace WPAppCore\Controllers;

abstract class AbstractSettingsController {

    // Abstract methods - must be implemented by child
    abstract protected function getPluginSlug(): string;
    abstract protected function getPluginPrefix(): string;
    abstract protected function getSettingsPageSlug(): string;
    abstract protected function getSettingsCapability(): string;
    abstract protected function getDefaultTabs(): array;

    // Concrete methods - inherited FREE
    public function __construct() {
        $this->init();
    }

    public function init(): void {
        add_action('admin_init', [$this, 'registerSettings']);
        $this->registerAjaxHandlers();
    }

    public function renderPage(): void {
        // Load shared template from wp-app-core
        $tabs = $this->getTabs();
        $current_tab = $this->getCurrentTab();

        include WPAPP_CORE_PATH . 'src/Templates/Settings/settings-page.php';
    }

    public function loadTabView(string $tab): void {
        // Try plugin-specific tab first
        $custom_tab = apply_filters(
            $this->getPluginPrefix() . '_settings_tab_path',
            '',
            $tab
        );

        if ($custom_tab && file_exists($custom_tab)) {
            include $custom_tab;
            return;
        }

        // Fallback to core tab
        $core_tab = WPAPP_CORE_PATH . "src/Templates/Settings/tab-{$tab}.php";
        if (file_exists($core_tab)) {
            include $core_tab;
            return;
        }

        // Hook for custom tab content
        do_action(
            $this->getPluginPrefix() . '_settings_tab_content_' . $tab
        );
    }

    public function getTabs(): array {
        $tabs = $this->getDefaultTabs();

        // Allow extending tabs via plugin-specific hook
        // Example: apply_filters('wpc_settings_tabs', $tabs) for wp-customer
        return apply_filters(
            $this->getPluginPrefix() . '_settings_tabs',
            $tabs
        );
    }

    protected function registerSettings(): void {
        // Base implementation
        register_setting(
            $this->getPluginPrefix() . '_settings',
            $this->getPluginPrefix() . '_settings',
            [
                'sanitize_callback' => [$this, 'sanitizeSettings'],
                'default' => $this->getModel()->getDefaultSettings()
            ]
        );
    }

    protected function registerAjaxHandlers(): void {
        // Base AJAX handlers
        add_action('wp_ajax_' . $this->getPluginPrefix() . '_save_settings', [$this, 'handleSaveSettings']);
        add_action('wp_ajax_' . $this->getPluginPrefix() . '_reset_settings', [$this, 'handleResetSettings']);
    }

    public function handleSaveSettings(): void {
        check_ajax_referer($this->getPluginPrefix() . '_nonce', 'nonce');

        if (!current_user_can($this->getSettingsCapability())) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-app-core')]);
        }

        $data = $_POST['settings'] ?? [];

        // Validate
        $validator = $this->getValidator();
        if (!$validator->validate($data)) {
            wp_send_json_error(['errors' => $validator->getErrors()]);
        }

        // Save
        $saved = $this->getModel()->saveSettings($data);

        if ($saved) {
            wp_send_json_success(['message' => __('Settings saved', 'wp-app-core')]);
        } else {
            wp_send_json_error(['message' => __('Failed to save', 'wp-app-core')]);
        }
    }

    abstract protected function getModel(): AbstractSettingsModel;
    abstract protected function getValidator(): AbstractSettingsValidator;
}
```

**Benefits:**
- ~600 lines inherited FREE by each plugin
- Consistent tab system
- Hook-based extensibility
- Automatic AJAX registration

#### 1.2 AbstractSettingsModel

**File:** `/wp-app-core/src/Models/AbstractSettingsModel.php`

**Dependencies:** Uses `AbstractCacheManager` from TODO-1202

**Key Methods:**
```php
<?php
namespace WPAppCore\Models;

use WPAppCore\Cache\Abstract\AbstractCacheManager;

abstract class AbstractSettingsModel {

    protected AbstractCacheManager $cacheManager;

    // Abstract methods
    abstract protected function getOptionName(): string;
    abstract protected function getCacheManager(): AbstractCacheManager;
    abstract protected function getDefaultSettings(): array;

    // Concrete methods - inherited FREE
    public function getSettings(): array {
        $cacheManager = $this->getCacheManager();

        // Try cache first (using AbstractCacheManager)
        $settings = $cacheManager->get('settings');

        if ($settings === null) {
            $settings = get_option(
                $this->getOptionName(),
                $this->getDefaultSettings()
            );

            // Merge with defaults (for new settings)
            $settings = wp_parse_args($settings, $this->getDefaultSettings());

            // Cache it (using AbstractCacheManager)
            $cacheManager->set('settings', $settings);
        }

        return $settings;
    }

    public function getSetting(string $key, $default = null) {
        $settings = $this->getSettings();
        return $settings[$key] ?? $default;
    }

    public function saveSettings(array $settings): bool {
        $result = update_option($this->getOptionName(), $settings);

        if ($result) {
            // Clear cache using AbstractCacheManager
            $this->getCacheManager()->delete('settings');

            do_action(
                str_replace('_settings', '', $this->getOptionName()) . '_settings_updated',
                $settings
            );
        }

        return $result;
    }

    public function clearCache(): void {
        $this->getCacheManager()->delete('settings');
    }

    public function sanitizeSettings(array $input): array {
        // Base sanitization
        $sanitized = [];

        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeSettings($value);
            } elseif (is_bool($value)) {
                $sanitized[$key] = (bool) $value;
            } elseif (is_numeric($value)) {
                $sanitized[$key] = $value + 0; // Preserve type
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }

        return $sanitized;
    }
}
```

**Benefits:**
- ~150 lines inherited FREE
- Automatic caching via AbstractCacheManager (from TODO-1202)
- Consistent data access
- Hook system integration
- Centralized cache invalidation

#### 1.3 AbstractSettingsValidator

**File:** `/wp-app-core/src/Validators/AbstractSettingsValidator.php`

**Note:** Does NOT extend `AbstractValidator` (TODO-1201) because:
- `AbstractValidator` is for Entity CRUD (requires entity ID, user relations)
- `AbstractSettingsValidator` is for configuration validation (no entity ID, pure data validation)
- Different concerns, different implementations

**Key Methods:**
```php
<?php
namespace WPAppCore\Validators;

abstract class AbstractSettingsValidator {

    protected array $errors = [];

    // ========================================
    // ABSTRACT METHODS
    // ========================================

    /**
     * Get validation rules for settings
     *
     * @return array Rules per field
     *
     * @example
     * return [
     *     'site_name' => ['required', 'max:255'],
     *     'admin_email' => ['required', 'email'],
     *     'items_per_page' => ['required', 'numeric', 'min:1', 'max:100']
     * ];
     */
    abstract protected function getRules(): array;

    /**
     * Get text domain for translations
     */
    abstract protected function getTextDomain(): string;

    /**
     * Get custom validation messages (optional)
     *
     * @return array Custom messages per field.rule
     */
    protected function getMessages(): array {
        return [];
    }

    // ========================================
    // CONCRETE METHODS
    // ========================================

    /**
     * Validate settings data
     */
    public function validate(array $data): bool {
        $this->errors = [];
        $rules = $this->getRules();

        foreach ($rules as $field => $rule_set) {
            $value = $data[$field] ?? null;
            $this->validateField($field, $value, $rule_set);
        }

        return empty($this->errors);
    }

    /**
     * Validate single field against its rules
     */
    protected function validateField(string $field, $value, array $rules): void {
        foreach ($rules as $rule) {
            // Required
            if ($rule === 'required' && $this->isEmpty($value)) {
                $this->addError($field, 'required');
                continue;
            }

            // Skip other validations if empty and not required
            if ($this->isEmpty($value)) {
                continue;
            }

            // Email
            if ($rule === 'email' && !is_email($value)) {
                $this->addError($field, 'email');
            }

            // URL
            if ($rule === 'url' && !filter_var($value, FILTER_VALIDATE_URL)) {
                $this->addError($field, 'url');
            }

            // Numeric
            if ($rule === 'numeric' && !is_numeric($value)) {
                $this->addError($field, 'numeric');
            }

            // Boolean
            if ($rule === 'boolean' && !is_bool($value) && !in_array($value, [0, 1, '0', '1', true, false], true)) {
                $this->addError($field, 'boolean');
            }

            // Min length/value
            if (strpos($rule, 'min:') === 0) {
                $min = (int) str_replace('min:', '', $rule);
                if (is_numeric($value) && $value < $min) {
                    $this->addError($field, 'min', ['min' => $min]);
                } elseif (is_string($value) && strlen($value) < $min) {
                    $this->addError($field, 'min_length', ['min' => $min]);
                }
            }

            // Max length/value
            if (strpos($rule, 'max:') === 0) {
                $max = (int) str_replace('max:', '', $rule);
                if (is_numeric($value) && $value > $max) {
                    $this->addError($field, 'max', ['max' => $max]);
                } elseif (is_string($value) && strlen($value) > $max) {
                    $this->addError($field, 'max_length', ['max' => $max]);
                }
            }

            // In array
            if (strpos($rule, 'in:') === 0) {
                $allowed = explode(',', str_replace('in:', '', $rule));
                if (!in_array($value, $allowed, true)) {
                    $this->addError($field, 'in', ['values' => implode(', ', $allowed)]);
                }
            }
        }
    }

    /**
     * Check if value is empty
     */
    protected function isEmpty($value): bool {
        return $value === null || $value === '' || $value === [];
    }

    /**
     * Add validation error
     */
    protected function addError(string $field, string $rule, array $params = []): void {
        $messages = $this->getMessages();
        $custom_key = "{$field}.{$rule}";

        // Check for custom message
        if (isset($messages[$custom_key])) {
            $message = $messages[$custom_key];
        } else {
            // Use default message
            $message = $this->getDefaultMessage($field, $rule, $params);
        }

        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Get default error message
     */
    protected function getDefaultMessage(string $field, string $rule, array $params = []): string {
        $field_label = ucfirst(str_replace('_', ' ', $field));
        $domain = $this->getTextDomain();

        $messages = [
            'required' => sprintf(__('%s is required.', $domain), $field_label),
            'email' => sprintf(__('%s must be a valid email address.', $domain), $field_label),
            'url' => sprintf(__('%s must be a valid URL.', $domain), $field_label),
            'numeric' => sprintf(__('%s must be a number.', $domain), $field_label),
            'boolean' => sprintf(__('%s must be true or false.', $domain), $field_label),
            'min' => sprintf(__('%s must be at least %d.', $domain), $field_label, $params['min'] ?? 0),
            'max' => sprintf(__('%s must not exceed %d.', $domain), $field_label, $params['max'] ?? 0),
            'min_length' => sprintf(__('%s must be at least %d characters.', $domain), $field_label, $params['min'] ?? 0),
            'max_length' => sprintf(__('%s must not exceed %d characters.', $domain), $field_label, $params['max'] ?? 0),
            'in' => sprintf(__('%s must be one of: %s.', $domain), $field_label, $params['values'] ?? ''),
        ];

        return $messages[$rule] ?? sprintf(__('Invalid value for %s.', $domain), $field_label);
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function getFirstError(string $field): ?string {
        return $this->errors[$field][0] ?? null;
    }

    public function hasError(string $field): bool {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }
}
```

**Benefits:**
- ~200 lines validation logic FREE (expanded from basic version)
- Extensible rule system with multiple validation types
- Consistent error handling with custom messages
- Support for: required, email, url, numeric, boolean, min, max, in
- Custom error messages per field
- Helper methods: getFirstError(), hasError()

---

### Phase 2: Migrate Shared Templates & Assets

#### 2.1 Move Core Templates to wp-app-core

**From:** `/wp-app-core/src/Views/templates/settings/`
**To:** `/wp-app-core/src/Templates/Settings/`

**Files:**
- `settings-page.php` - Main wrapper (tab navigation)
- `tab-general.php` - General settings tab
- `tab-permissions.php` - Permissions tab
- `tab-demo-data.php` - Demo data tab

**Update template to use plugin-specific hooks:**
```php
<!-- settings-page.php -->
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <h2 class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab_key => $tab_label): ?>
            <a href="?page=<?php echo $page_slug; ?>&tab=<?php echo $tab_key; ?>"
               class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($tab_label); ?>
            </a>
        <?php endforeach; ?>
    </h2>

    <div class="tab-content">
        <?php
        // Plugin-specific hook before tab content
        // Example: do_action('wpc_settings_before_tab_content', $current_tab)
        do_action($plugin_prefix . '_settings_before_tab_content', $current_tab);

        // Load tab content
        $controller->loadTabView($current_tab);

        // Plugin-specific hook after tab content
        // Example: do_action('wpc_settings_after_tab_content', $current_tab)
        do_action($plugin_prefix . '_settings_after_tab_content', $current_tab);
        ?>
    </div>
</div>
```

#### 2.2 Move Shared Assets to wp-app-core

**CSS:**
- `/wp-app-core/assets/css/settings/settings-style.css` (keep)
- `/wp-app-core/assets/css/settings/general-tab-style.css` (keep)
- `/wp-app-core/assets/css/settings/permissions-tab-style.css` (keep)
- `/wp-app-core/assets/css/settings/demo-data-tab-style.css` (keep)

**JS:**
- `/wp-app-core/assets/js/settings/settings-script.js` (keep)
- `/wp-app-core/assets/js/settings/general-tab-script.js` (keep)
- `/wp-app-core/assets/js/settings/permissions-tab-script.js` (keep)

**Update AbstractSettingsController to enqueue assets:**
```php
// In AbstractSettingsController
protected function enqueueAssets(): void {
    $current_tab = $this->getCurrentTab();
    $plugin_prefix = $this->getPluginPrefix();

    // Enqueue base settings styles from wp-app-core
    wp_enqueue_style(
        'wpapp-settings-base',
        WPAPP_CORE_URL . 'assets/css/settings/settings-style.css',
        [],
        WPAPP_CORE_VERSION
    );

    // Enqueue base settings scripts from wp-app-core
    wp_enqueue_script(
        'wpapp-settings-base',
        WPAPP_CORE_URL . 'assets/js/settings/settings-script.js',
        ['jquery'],
        WPAPP_CORE_VERSION,
        true
    );

    // Allow child classes to enqueue plugin-specific assets
    // Example: do_action('wpc_settings_enqueue_assets', 'membership-levels')
    do_action("{$plugin_prefix}_settings_enqueue_assets", $current_tab);
}
```

---

### Phase 3: Refactor Plugin Controllers

#### 3.1 WP Customer - SettingsController

**Before:**
```php
class SettingsController {
    // ~800 lines of duplicated code
}
```

**After:**
```php
namespace WPCustomer\Controllers;

use WPAppCore\Controllers\AbstractSettingsController;
use WPCustomer\Models\Settings\SettingsModel;
use WPCustomer\Validators\SettingsValidator;

class SettingsController extends AbstractSettingsController {

    protected function getPluginSlug(): string {
        return 'wp-customer';
    }

    protected function getPluginPrefix(): string {
        return 'wpc';
    }

    protected function getSettingsPageSlug(): string {
        return 'wp-customer-settings';
    }

    protected function getSettingsCapability(): string {
        return 'manage_customer_settings';
    }

    protected function getDefaultTabs(): array {
        return [
            'general' => __('General', 'wp-customer'),
            'permissions' => __('Permissions', 'wp-customer'),
            'membership-levels' => __('Membership Levels', 'wp-customer'),
            'membership-features' => __('Membership Features', 'wp-customer'),
            'invoice-payment' => __('Invoice & Payment', 'wp-customer'),
            'demo-data' => __('Demo Data', 'wp-customer')
        ];
    }

    protected function getModel(): AbstractSettingsModel {
        return new SettingsModel();
    }

    protected function getValidator(): AbstractSettingsValidator {
        return new SettingsValidator();
    }

    // Custom AJAX handlers (plugin-specific)
    protected function registerAjaxHandlers(): void {
        parent::registerAjaxHandlers(); // Get base handlers

        // Add custom handlers
        add_action('wp_ajax_wpc_reset_membership_levels', [$this, 'handleResetMembershipLevels']);
        add_action('wp_ajax_wpc_generate_demo_data', [$this, 'handleGenerateDemoData']);
    }

    public function handleResetMembershipLevels(): void {
        // Custom logic
    }

    public function handleGenerateDemoData(): void {
        // Custom logic
    }
}
```

**Code Reduction:** ~600 lines → ~100 lines (83% reduction!)

#### 3.2 WP Customer - SettingsModel (with AbstractCacheManager)

**After:**
```php
namespace WPCustomer\Models\Settings;

use WPAppCore\Models\AbstractSettingsModel;
use WPAppCore\Cache\Abstract\AbstractCacheManager;
use WPCustomer\Cache\CustomerCacheManager; // From TODO-1202

class SettingsModel extends AbstractSettingsModel {

    private CustomerCacheManager $cacheManager;

    public function __construct() {
        $this->cacheManager = new CustomerCacheManager();
    }

    protected function getOptionName(): string {
        return 'wpc_settings';
    }

    protected function getCacheManager(): AbstractCacheManager {
        return $this->cacheManager;
    }

    protected function getDefaultSettings(): array {
        return [
            'site_name' => get_bloginfo('name'),
            'items_per_page' => 10,
            'enable_notifications' => true,
            // ... other defaults
        ];
    }

    // ✅ getSettings() - inherited FREE!
    // ✅ getSetting() - inherited FREE!
    // ✅ saveSettings() - inherited FREE!
    // ✅ clearCache() - inherited FREE!
    // ✅ sanitizeSettings() - inherited FREE!
}
```

**Code Reduction:** ~200 lines → ~30 lines (85% reduction!)

#### 3.3 WP Customer - SettingsValidator

**After:**
```php
namespace WPCustomer\Validators;

use WPAppCore\Validators\AbstractSettingsValidator;

class SettingsValidator extends AbstractSettingsValidator {

    protected function getTextDomain(): string {
        return 'wp-customer';
    }

    protected function getRules(): array {
        return [
            'site_name' => ['required', 'max:255'],
            'admin_email' => ['required', 'email'],
            'items_per_page' => ['required', 'numeric', 'min:1', 'max:100'],
            'enable_notifications' => ['boolean'],
        ];
    }

    // Optional: Custom error messages
    protected function getMessages(): array {
        return [
            'site_name.required' => __('Site name cannot be empty.', 'wp-customer'),
            'items_per_page.min' => __('Must show at least 1 item per page.', 'wp-customer'),
        ];
    }

    // ✅ validate() - inherited FREE!
    // ✅ getErrors() - inherited FREE!
    // ✅ hasError() - inherited FREE!
    // ✅ getFirstError() - inherited FREE!
}
```

**Code Reduction:** ~150 lines → ~25 lines (83% reduction!)

#### 3.4 WP Agency - SettingsController

Same pattern as wp-customer, just different plugin slug/prefix:
- `getPluginSlug()` returns `'wp-agency'`
- `getPluginPrefix()` returns `'wpa'`
- Custom tabs for agency (e.g., 'commission')

---

## ✅ Acceptance Criteria

### Abstract Classes
- [x] AbstractSettingsController created in `/wp-app-core/src/Controllers/AbstractSettingsController.php`
- [x] AbstractSettingsModel created in `/wp-app-core/src/Models/AbstractSettingsModel.php`
  - [x] Integrates with AbstractCacheManager (from TODO-1202)
  - [x] Uses `getCacheManager()` instead of direct `wp_cache_*` calls
- [x] AbstractSettingsValidator created in `/wp-app-core/src/Validators/AbstractSettingsValidator.php`
  - [x] Does NOT extend AbstractValidator (different purpose)
  - [x] Independent implementation for settings validation
- [x] All abstract methods documented with PHPDoc
- [x] Plugin-specific hook system implemented (not global hooks)

### Templates
- [x] Shared templates moved to `/wp-app-core/src/Templates/Settings/`
  - [x] `settings-page.php` - Main wrapper with tab navigation
  - [x] `tab-general.php` - General settings tab
  - [x] `tab-permissions.php` - Permissions tab
  - [x] `tab-demo-data.php` - Demo data tab
- [x] Templates use plugin-specific hooks (`{prefix}_settings_*`)
- [x] Tab content loading supports both core and custom plugin tabs

### Assets
- [x] Shared CSS/JS remain in `/wp-app-core/assets/`
  - [x] `settings-style.css` - Base styles (already exists)
  - [x] `settings-script.js` - Base scripts (already exists)
  - [x] Tab-specific CSS/JS for core tabs (already exists)
- [x] `enqueueAssets()` method in AbstractSettingsController
- [x] Plugin-specific hook for custom assets: `{prefix}_settings_enqueue_assets`

### Plugin Refactoring
- [ ] WP Customer refactored:
  - [ ] SettingsController extends AbstractSettingsController (~83% reduction)
  - [ ] SettingsModel extends AbstractSettingsModel (~85% reduction)
  - [ ] SettingsValidator extends AbstractSettingsValidator (~83% reduction)
- [ ] WP Agency refactored:
  - [ ] SettingsController extends AbstractSettingsController
  - [ ] SettingsModel extends AbstractSettingsModel
  - [ ] SettingsValidator extends AbstractSettingsValidator
- [x] WP App Core - Platform Settings Models Refactored:
  - [x] PlatformSettingsModel v1.0.0 → v2.0.0 (51% reduction, 140 lines)
  - [x] EmailSettingsModel v1.0.0 → v2.0.0 (42% reduction, ~195 lines)
  - [x] SecurityAuthenticationModel v1.0.0 → v2.0.0 (42% reduction, ~145 lines)
  - [x] SecuritySessionModel v1.0.0 → v2.0.0 (~100 lines eliminated)
  - [x] SecurityPolicyModel v1.0.0 → v2.0.0 (43% reduction, ~142 lines)
  - [x] PlatformSettingsValidator created (new validator)
  - [x] All models use PlatformCacheManager via AbstractCacheManager
  - [x] Total: ~700+ lines eliminated across 5 models
- [x] WP App Core - Platform Settings Controllers Refactored:
  - [x] PlatformSettingsController (871 lines) split into 8 specialized controllers
  - [x] PlatformGeneralSettingsController (extends AbstractSettingsController)
  - [x] EmailSettingsController (extends AbstractSettingsController)
  - [x] SecurityAuthenticationController (extends AbstractSettingsController)
  - [x] SecuritySessionController (extends AbstractSettingsController)
  - [x] SecurityPolicyController (extends AbstractSettingsController)
  - [x] PlatformPermissionsController (standalone - permission management)
  - [x] PlatformDemoDataController (standalone - demo data management)
  - [x] PlatformSettingsPageController (orchestrator - 220 lines)
  - [x] Created 5 new validators (Email, SecurityAuth, SecuritySession, SecurityPolicy)
  - [x] MenuManager.php updated to use PlatformSettingsPageController
  - [x] Total: 871 lines → ~1,200 lines (more modular, better SRP)
- [ ] All existing functionality works identically
- [ ] Custom tabs render correctly
- [ ] Plugin-specific AJAX handlers work

### Testing
- [ ] Settings save/load works in all plugins:
  - [ ] WP Customer settings page
  - [ ] WP Agency settings page
  - [ ] WP App Core platform settings
- [ ] Cache invalidation works correctly (AbstractCacheManager integration)
- [ ] Tab navigation works in all plugins
- [ ] Core tabs display properly (General, Permissions, Demo Data)
- [ ] Custom plugin tabs display properly:
  - [ ] WP Customer: Membership Levels, Invoice & Payment
  - [ ] WP Agency: Commission
- [ ] AJAX handlers work correctly:
  - [ ] Base handlers (save, reset)
  - [ ] Plugin-specific handlers
- [ ] Permission checks enforced (via capabilities)
- [ ] Validation works correctly:
  - [ ] Required fields
  - [ ] Email validation
  - [ ] Numeric validation
  - [ ] Custom rules per plugin

### Documentation
- [ ] Abstract classes fully documented with PHPDoc:
  - [ ] All abstract methods explained
  - [ ] All concrete methods explained
  - [ ] Usage examples in comments
- [ ] Hook system documented:
  - [ ] All available hooks listed
  - [ ] Hook parameters documented
  - [ ] Code examples for each hook
- [ ] Create: `/wp-app-core/docs/abstracts/settings-framework.md`
  - [ ] How to create settings controller
  - [ ] How to create settings model (with cache manager)
  - [ ] How to create settings validator
  - [ ] How to add custom tabs
- [ ] Create: `/wp-app-core/docs/hooks/settings-hooks.md`
  - [ ] Complete hook reference
  - [ ] Real-world examples from wp-customer/wp-agency

---

## 📊 Impact Analysis

### Code Reduction Per Plugin

**WP Customer:**
- Controller: ~800 lines → ~100 lines (83% reduction)
- Model: ~200 lines → ~30 lines (85% reduction)
- Validator: ~150 lines → ~25 lines (83% reduction)
- **Total: ~1,150 lines → ~155 lines (86.5% reduction)**

**WP Agency:**
- Controller: ~800 lines → ~100 lines (83% reduction)
- Model: ~200 lines → ~30 lines (85% reduction)
- Validator: ~150 lines → ~25 lines (83% reduction)
- **Total: ~1,150 lines → ~155 lines (86.5% reduction)**

**WP App Core:**
- Controller: ~870 lines → ~120 lines (86% reduction)
- Model: ~200 lines → ~30 lines (85% reduction)
- **Total: ~1,070 lines → ~150 lines (86% reduction)**

**Overall Code Reduction:**
- **Before:** ~3,370 lines across 3 plugins
- **After:** ~460 lines across 3 plugins
- **Eliminated:** ~2,910 lines (86.3% reduction!)
- **Plus:** ~950 lines in abstract classes (reusable framework)

### File Consolidation
- **Before:** 39 files across 3 plugins (duplicates)
- **After:**
  - Core: 3 abstract classes + 4 templates + 7 assets = **14 shared files**
  - WP Customer: Controller + Model + Validator + 3 custom tabs = **6 files**
  - WP Agency: Controller + Model + Validator + 1 custom tab = **4 files**
  - WP App Core: Controller + Model = **2 files**
  - **Total:** ~26 files (33% reduction)

### Maintenance Benefits
- ✅ Single source of truth for settings functionality (3 abstract classes)
- ✅ Easier to add new plugins with settings (just extend abstracts)
- ✅ Consistent UX across all plugins (shared templates)
- ✅ Centralized bug fixes (fix once in abstract, all plugins benefit)
- ✅ Easier testing (test abstracts once, less per-plugin testing)
- ✅ Standardized caching via AbstractCacheManager integration
- ✅ Consistent validation patterns across all settings

---

## 🔗 Related

**Dependencies:**
- ✅ TODO-1202 (AbstractCacheManager) - Used by AbstractSettingsModel for caching
- ❌ TODO-1201 (AbstractValidator) - NOT used, different purpose (entity CRUD vs settings validation)

**Related Issues:**
- Code duplication across plugins (~1,700 lines of duplicated controller code)
- Inconsistent settings patterns across wp-customer, wp-agency, wp-app-core

**Documentation:**
- wp-app-core/docs/abstracts/settings-framework.md (to be created)
- wp-app-core/docs/hooks/settings-hooks.md (to be created)

---

## 📌 Notes

### Migration Strategy
1. Create abstract classes in wp-app-core
2. Move shared templates to wp-app-core
3. Test with wp-app-core PlatformSettingsController first
4. Migrate wp-customer SettingsController
5. Migrate wp-agency SettingsController
6. Verify all functionality works
7. Remove duplicate files from plugins

### Hook Naming Convention

**All hooks are plugin-specific using `{plugin_prefix}_`:**

```
{plugin_prefix}_settings_tabs              - Filter: Add/modify tabs for this plugin's settings
                                             Example: apply_filters('wpc_settings_tabs', $tabs)
                                             Used by: wp-customer extensions

{plugin_prefix}_settings_tab_content_{tab} - Action: Render custom tab content
                                             Example: do_action('wpc_settings_tab_content_membership-levels')
                                             Used by: Custom tab templates

{plugin_prefix}_settings_tab_path          - Filter: Override tab template file path
                                             Example: apply_filters('wpc_settings_tab_path', '', $tab)
                                             Used by: Custom tab template locations

{plugin_prefix}_settings_before_tab_content - Action: Before tab content renders
{plugin_prefix}_settings_after_tab_content  - Action: After tab content renders

{plugin_prefix}_settings_enqueue_assets    - Action: Enqueue tab-specific assets
                                             Example: do_action('wpc_settings_enqueue_assets', $tab)

{plugin_prefix}_settings_updated           - Action: After settings saved successfully
                                             Example: do_action('wpc_settings_updated', $settings)
```

**Hook Prefix Examples:**
- `wpc_` = WP Customer
- `wpa_` = WP Agency
- `wpapp_` = WP App Core (Platform)

**Why Plugin-Specific?**
Each plugin has separate settings page, so hooks don't need global namespace.

### Backward Compatibility
**NOT REQUIRED** - Application is still in development phase.
- Direct refactoring without deprecation notices
- No migration path needed (can refactor immediately)
- Breaking changes acceptable during development

---

**Ready for Implementation** ✅

## ⏱️ Estimated Effort

### Phase 1: Create Abstract Classes (3-4 hours)
- AbstractSettingsController: ~2 hours
- AbstractSettingsModel (with AbstractCacheManager): ~1 hour
- AbstractSettingsValidator: ~1 hour

### Phase 2: Move Templates & Assets (1-2 hours)
- Move and update templates: ~1 hour
- Update asset loading: ~1 hour

### Phase 3: Refactor Plugins (3-4 hours)
- WP App Core (PlatformSettings): ~1.5 hours
- WP Customer (Settings): ~1 hour
- WP Agency (Settings): ~1 hour

### Phase 4: Testing (2-3 hours)
- Test all settings pages: ~1 hour
- Test cache integration: ~30 minutes
- Test validation: ~30 minutes
- Test custom tabs & AJAX: ~1 hour

### Phase 5: Documentation (1-2 hours)
- PHPDoc in abstract classes: ~30 minutes
- Create settings-framework.md: ~1 hour
- Create settings-hooks.md: ~30 minutes

**Total Estimated Time:** 10-15 hours

**Breaking Changes:** Direct refactoring acceptable (no backward compatibility needed)
