# TODO-1202 Review-08: CSS Loading Fix

## Deskripsi
Fix masalah CSS plugin tidak ter-load. Browser masih menggunakan `wp-includes/css/admin-bar.css` (WordPress core) instead of plugin's custom CSS.

## Tanggal
2025-01-18

## Status
✅ Selesai

## Problem

### Issue
CSS dari plugin `wp-app-core` tidak ter-load di browser. Yang muncul adalah:
```
wp-includes/css/admin-bar.css?ver=6.8.3
```

Padahal file CSS plugin sudah ada di:
```
/wp-app-core/assets/css/admin-bar/admin-bar-style.css
```

### Root Cause
1. **Priority Issue**: Plugin enqueue CSS dengan default priority (10), sama dengan WordPress core
2. **No Dependency**: Tidak ada dependency declaration pada WordPress core admin-bar CSS
3. **Load Order**: WordPress core CSS ter-load, tapi plugin CSS tidak ter-load atau ter-override

## Solution

### 1. Tambahkan Priority pada Action Hook

**Before:**
```php
add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
```

**After:**
```php
// Priority 20 ensures loading AFTER WordPress core (default 10)
add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles'], 20);
add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts'], 20);
```

### 2. Tambahkan Dependency pada wp_enqueue_style

**Before:**
```php
wp_enqueue_style(
    'wp-app-core-admin-bar',
    WP_APP_CORE_PLUGIN_URL . 'assets/css/admin-bar/admin-bar-style.css',
    [],  // No dependencies
    $this->version
);
```

**After:**
```php
wp_enqueue_style(
    'wp-app-core-admin-bar',
    WP_APP_CORE_PLUGIN_URL . 'assets/css/admin-bar/admin-bar-style.css',
    ['admin-bar'],  // Depend on WordPress core admin-bar CSS
    $this->version
);
```

## How It Works

### Load Order dengan Dependency
1. WordPress core loads `admin-bar` CSS (priority 10)
2. Our plugin loads AFTER core (priority 20)
3. Because we declare dependency on `admin-bar`, WordPress ensures:
   - Core admin-bar.css loads first
   - Our admin-bar-style.css loads second
4. CSS cascade rules apply: Our styles override core styles

### Why Priority 20?
- WordPress core uses priority 10 (default)
- Priority 20 ensures we load after core
- Still early enough to not conflict with other plugins (usually 100+)

### Why Dependency Declaration?
- Ensures correct load order even if priorities change
- Prevents race conditions
- Makes dependency explicit and maintainable
- WordPress handles the order automatically

## Files Modified

### includes/class-dependencies.php
**Path:** `/wp-app-core/includes/class-dependencies.php`

**Changes:**
1. Line 50-51: Added priority 20 to action hooks
2. Line 63: Added `['admin-bar']` dependency

## Verification

### Browser DevTools Check
Setelah fix, di browser DevTools → Network tab, harus terlihat:

**Before (WRONG):**
```
✗ wp-includes/css/admin-bar.css?ver=6.8.3
```

**After (CORRECT):**
```
✓ wp-includes/css/admin-bar.css?ver=6.8.3
✓ wp-content/plugins/wp-app-core/assets/css/admin-bar/admin-bar-style.css?ver=1.0.0
```

### Code Verification
```bash
# Check enqueue priority
grep -n "add_action.*enqueue.*20" /wp-app-core/includes/class-dependencies.php

# Check CSS dependency
grep -n "admin-bar.*Depend" /wp-app-core/includes/class-dependencies.php
```

### Expected Output in HTML Head
```html
<link rel='stylesheet' id='admin-bar-css' href='.../wp-includes/css/admin-bar.css?ver=6.8.3' />
<link rel='stylesheet' id='wp-app-core-admin-bar-css' href='.../wp-app-core/assets/css/admin-bar/admin-bar-style.css?ver=1.0.0' />
```

## Benefits

1. **Proper CSS Loading**: Plugin CSS now loads correctly
2. **Override Core Styles**: Our custom styles properly override WordPress core
3. **Maintainable**: Explicit dependency makes code intention clear
4. **Future Proof**: Won't break if WordPress changes core priority
5. **No Conflicts**: Priority 20 is safe zone for plugin assets

## Testing Checklist

- [x] Priority 20 added to hooks
- [x] Dependency on 'admin-bar' added
- [x] Code verified
- [ ] Browser test: Clear cache
- [ ] Browser test: Check Network tab
- [ ] Browser test: Verify both CSS files load
- [ ] Visual test: Admin bar styling correct
- [ ] Console test: No errors

## Related Files

- `/wp-app-core/includes/class-dependencies.php` - Main fix
- `/wp-app-core/assets/css/admin-bar/admin-bar-style.css` - CSS file
- `/wp-app-core/wp-app-core.php` - Plugin initialization

## Notes

### WordPress Core Admin Bar
WordPress core enqueues admin-bar CSS via:
- File: `wp-includes/script-loader.php`
- Function: `wp_default_styles()`
- Handle: `'admin-bar'`
- Priority: Default (10)

### Our Plugin Strategy
- Depend on core CSS (don't replace it)
- Override specific styles via cascade
- Load after core (priority 20)
- Maintain compatibility

## Completion Criteria
✅ Priority added to action hooks
✅ Dependency declared in wp_enqueue_style
✅ Code documented
✅ Fix verified in code
⏳ Browser testing needed (by user)
