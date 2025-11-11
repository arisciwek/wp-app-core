# Troubleshooting Guide

**Date**: 2025-01-09
**Version**: 1.0.0

## AJAX Reset Handler Errors

### Error: "400 Bad Request" on Reset

**Symptoms**:
- Click "Reset to Default" button
- JavaScript console shows: `POST admin-ajax.php 400 (Bad Request)`
- Error message: "An error occurred while resetting settings"

**Common Causes**:

#### 1. Action Name Mismatch

**Problem**: JavaScript sends different action name than PHP expects

**Example**:
```javascript
// JavaScript sends:
action: 'reset_general_settings'

// But PHP registered:
wp_ajax_reset_general  // Missing '_settings' suffix!
```

**Solution**: Ensure consistency between JavaScript and PHP

JavaScript should send:
```javascript
action: 'reset_general'          // Match controller slug
action: 'reset_email'            // Match controller slug  
action: 'reset_security_policy'  // Match controller slug (kebab to snake)
```

PHP registration (automatic via getControllerSlug()):
```php
protected function getControllerSlug(): string {
    return 'general';  // → registers wp_ajax_reset_general
}
```

#### 2. Missing Controller Slug

**Problem**: Controller doesn't implement getControllerSlug()

**Error**: `Fatal error: Class must implement abstract method getControllerSlug()`

**Solution**: Add method to controller
```php
protected function getControllerSlug(): string {
    return 'your-controller-slug';
}
```

#### 3. wpAppCoreSettings Not Defined

**Problem**: JavaScript localization not loaded

**Console Error**: `Uncaught ReferenceError: wpAppCoreSettings is not defined`

**Solution**: Ensure SettingsPageAssets strategy is registered
```php
// In AssetController::register_default_strategies()
$this->register_strategy(new Strategies\SettingsPageAssets());
```

#### 4. Invalid Nonce

**Problem**: Nonce expired or incorrect

**Solution**: Hard refresh browser (Ctrl+Shift+R) to clear cached JavaScript

### Debugging Steps

1. **Check Browser Console**:
   ```javascript
   console.log(wpAppCoreSettings);
   // Should show: { ajaxUrl: "...", nonce: "...", ... }
   ```

2. **Check Action Name**:
   ```javascript
   // In browser console, before clicking reset:
   $('#reset-security-policy').data('action', 'reset_security_policy');
   ```

3. **Check Registered Actions** (in PHP):
   ```bash
   wp eval "
   global \$wp_filter;
   print_r(array_keys(\$wp_filter));
   " | grep reset
   ```

4. **Monitor AJAX Request**:
   - Open Network tab in DevTools
   - Click reset button
   - Check request payload:
     - action: should match registered action
     - nonce: should not be empty

### Quick Fixes

#### Fix 1: Update JavaScript Action
```bash
# Fix general tab
sed -i "s/reset_general_settings/reset_general/g" general-tab-script.js

# Fix email tab  
sed -i "s/reset_email_settings/reset_email/g" email-tab-script.js
```

#### Fix 2: Hard Refresh Browser
```
Ctrl + Shift + R (Windows/Linux)
Cmd + Shift + R (Mac)
```

#### Fix 3: Clear WordPress Object Cache
```bash
wp cache flush
```

## Related Documentation

- [UNIFIED_RESET_HANDLER.md](UNIFIED_RESET_HANDLER.md) - Reset handler architecture
- [ASSET_CONTROLLER_REFACTORING.md](ASSET_CONTROLLER_REFACTORING.md) - Asset loading
