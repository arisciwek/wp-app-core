# Settings Model Implementation Checklist

Panduan ini membantu Anda menghindari masalah cache invalidation saat membuat plugin baru dengan settings model.

## 📋 Checklist Wajib

### 0. Controller Configuration (NEW!)

#### ✅ Implement getControllerSlug()
**CRITICAL**: Diperlukan untuk AJAX handler registration!

```php
class YourSettingsController extends AbstractSettingsController {
    protected function getControllerSlug(): string {
        return 'your-controller'; // e.g., 'general', 'security-policy'
    }
}
```

**Kenapa penting?**
- Digunakan untuk membuat unique AJAX action per controller
- Format: `wp_ajax_reset_{controller_slug}`
- Contoh: `reset_security_policy`, `reset_general_settings`

**Slug Convention**:
- Use kebab-case: `security-policy` (bukan `security_policy`)
- Will be converted to snake_case for action: `reset_security_policy`
- Match with button ID di template (tanpa `reset-` prefix)

### 1. Model Configuration

#### ✅ Extend AbstractSettingsModel
```php
class YourSettingsModel extends AbstractSettingsModel {
    // ...
}
```

#### ✅ Call parent::__construct()
**CRITICAL**: Ini yang paling sering dilupakan!

```php
private YourCacheManager $cacheManager;

public function __construct() {
    $this->cacheManager = new YourCacheManager();
    parent::__construct(); // ⚠️ WAJIB! Registers cache invalidation hooks
}
```

**Kenapa penting?**
- `parent::__construct()` mendaftarkan WordPress hook `update_option_{option_name}`
- Hook ini secara otomatis membersihkan cache ketika WordPress menyimpan settings
- Tanpa ini, cache tidak akan pernah di-clear saat save via Settings API

#### ✅ Implement Required Methods
```php
protected function getOptionName(): string {
    return 'your_plugin_settings'; // Unique option name
}

protected function getCacheManager() {
    return $this->cacheManager;
}

protected function getDefaultSettings(): array {
    return [
        'setting_key' => 'default_value',
        // ... all your settings with defaults
    ];
}
```

### 2. Option Name Consistency

Option name HARUS konsisten di 4 tempat:

#### ✅ Model Class
```php
protected function getOptionName(): string {
    return 'platform_settings'; // ← Ini harus sama
}
```

#### ✅ Template File
```php
<?php settings_fields('platform_settings'); ?> // ← dengan yang ini

<input name="platform_settings[field_name]" ... /> // ← dan ini
```

#### ✅ JavaScript File
```javascript
$('input[name="platform_settings[field_name]"]').on('change', ...); // ← dan ini
```

#### ✅ Controller Whitelist
```php
$our_settings = [
    'platform_settings', // ← dan ini juga
    // ...
];
```

### 3. Cache Key Uniqueness

Cache key otomatis dibuat dari option name:
```php
// AbstractSettingsModel sudah handle ini
protected function getCacheKey(): string {
    return $this->getOptionName() . '_data';
}
```

Contoh cache keys:
- `platform_settings` → cache key: `platform_settings_data`
- `platform_email_settings` → cache key: `platform_email_settings_data`
- `your_plugin_settings` → cache key: `your_plugin_settings_data`

## 🧪 Testing Cache Invalidation

### Test Scenario 1: Manual Save
```php
$model = new YourSettingsModel();
$settings = ['field' => 'value'];
$model->saveSettings($settings);

// Verify cache cleared and data refreshed
$fresh = $model->getSettings();
// Should return latest data from DB
```

### Test Scenario 2: WordPress Settings API Save
1. Buka settings page di browser
2. Isi form dan klik "Save"
3. Check apakah data tampil langsung setelah save
4. **Jika data tidak tampil → parent::__construct() belum dipanggil!**

### Debug Commands
```bash
# Monitor cache invalidation
tail -f wp-content/debug.log | grep "onOptionUpdated"

# Check WordPress options
wp option get your_plugin_settings

# Clear all cache manually
wp cache flush
```

## ❌ Common Mistakes

### Mistake #1: Lupa parent::__construct()
```php
// ❌ WRONG
public function __construct() {
    $this->cacheManager = new YourCacheManager();
    // Missing parent::__construct()!
}

// ✅ CORRECT
public function __construct() {
    $this->cacheManager = new YourCacheManager();
    parent::__construct(); // Must call this!
}
```

### Mistake #2: Inconsistent Option Names
```php
// ❌ WRONG
// Model uses 'your_plugin_settings'
protected function getOptionName(): string {
    return 'your_plugin_settings';
}

// But template uses different name!
<?php settings_fields('your_settings'); ?>
```

### Mistake #3: Old Option Name in JavaScript
```javascript
// ❌ WRONG: JavaScript still using old name
$('input[name="old_plugin_settings[field]"]')

// ✅ CORRECT: Must match model option name
$('input[name="your_plugin_settings[field]"]')
```

## 🔍 Verification Steps

Setelah implementasi, lakukan verifikasi ini:

1. **Check Model Constructor**
   ```bash
   grep -n "parent::__construct()" your-model.php
   ```
   Harus ada hasil!

2. **Check Option Name Consistency**
   ```bash
   # Search in model
   grep "getOptionName" your-model.php

   # Search in template
   grep "settings_fields\|name=" your-template.php

   # Search in JavaScript
   grep "name=" your-script.js
   ```
   Semua harus sama!

3. **Test Save & Display**
   - Save settings via admin page
   - Refresh page (F5)
   - Data harus langsung tampil
   - Jika perlu Ctrl+Shift+R (hard refresh) → JavaScript cache issue

## 📝 Example Implementation

Contoh lengkap implementasi yang benar:

```php
<?php
namespace YourPlugin\Models\Settings;

use WPAppCore\Models\AbstractSettingsModel;
use YourPlugin\Cache\YourCacheManager;

class YourSettingsModel extends AbstractSettingsModel {

    private YourCacheManager $cacheManager;

    public function __construct() {
        $this->cacheManager = new YourCacheManager();
        parent::__construct(); // ⚠️ CRITICAL!
    }

    protected function getOptionName(): string {
        return 'your_plugin_settings';
    }

    protected function getCacheManager() {
        return $this->cacheManager;
    }

    protected function getDefaultSettings(): array {
        return [
            'setting_one' => 'default_value',
            'setting_two' => true,
            'setting_three' => 100,
        ];
    }

    public function sanitizeSettings(?array $settings = []): array {
        if ($settings === null) {
            $settings = [];
        }

        $sanitized = [];
        $sanitized['setting_one'] = sanitize_text_field($settings['setting_one'] ?? '');
        $sanitized['setting_two'] = isset($settings['setting_two']) ? (bool) $settings['setting_two'] : false;
        $sanitized['setting_three'] = absint($settings['setting_three'] ?? 0);

        return wp_parse_args($sanitized, $this->getDefaultSettings());
    }
}
```

## 🚀 Quick Start for New Plugin

1. Copy AbstractSettingsModel to your plugin
2. Create your model class extending AbstractSettingsModel
3. **CALL parent::__construct()** in your constructor
4. Use consistent option name everywhere
5. Test save & display
6. Done! Cache invalidation works automatically!

## 🆘 Troubleshooting

### Problem: Data saved but not displaying
**Solution**: Check if `parent::__construct()` is called in model constructor

### Problem: JavaScript validation error with checkboxes
**Solution**: Update JavaScript selectors to match new option name

### Problem: "Option not in whitelist" error
**Solution**: Add option name to controller's whitelist array

### Problem: Need hard refresh (Ctrl+Shift+R) to see changes
**Solution**: JavaScript file has old option names, update with sed or manually

## 📚 Related Files

- `/src/Models/AbstractSettingsModel.php` - Base model implementation
- `/src/Controllers/AbstractSettingsController.php` - Base controller
- `/src/Controllers/PlatformSettingsPageController.php` - Example usage

## 🔗 References

- WordPress Settings API: https://developer.wordpress.org/plugins/settings/
- WordPress Options API: https://developer.wordpress.org/apis/options/
- WordPress Hooks: https://developer.wordpress.org/plugins/hooks/
