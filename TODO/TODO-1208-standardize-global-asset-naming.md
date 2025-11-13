# TODO-1208: Standardize Global Asset Naming Convention

**Status**: IN PROGRESS
**Priority**: MEDIUM
**Created**: 2025-01-13
**Plugin**: wp-app-core
**Type**: Refactoring - Asset Naming Standardization

## 📋 Overview

Standardisasi penamaan file untuk global scope assets dengan prefix `wpapp-` untuk membedakan dengan plugin-specific assets. Ini memudahkan identifikasi file mana yang bersifat shared/reusable across plugins.

## 🎯 Goals

1. **Konsistensi Penamaan**: Semua global assets gunakan prefix `wpapp-`
2. **Clear Scope Identification**: Mudah identify mana shared vs plugin-specific
3. **Better Organization**: File naming mencerminkan fungsi dan scope
4. **Future-proof**: Template untuk asset baru

## 📁 Files to Rename

### **Permissions Assets** (Priority HIGH - Used by multiple plugins)
```
OLD → NEW

CSS:
/assets/css/permissions/permission-matrix.css
→ /assets/css/permissions/wpapp-permission-matrix.css

JS:
/assets/js/permissions/permission-matrix.js
→ /assets/js/permissions/wpapp-permission-matrix.js
```

### **Settings Assets** (Already named correctly ✅)
```
✅ /assets/css/settings/settings-style.css (no prefix needed - plugin-specific)
✅ /assets/js/settings/settings-script.js (no prefix needed - plugin-specific)
```

### **Other Global Assets** (Examples already renamed ✅)
```
✅ wpapp-datatable.css
✅ wpapp-panel-manager.js
✅ wpapp-tab-manager.js
```

## 🔍 Files to Update

### **1. AbstractPermissionsController.php**
**Path**: `/src/Controllers/Abstract/AbstractPermissionsController.php`

**Line ~232-237**: CSS enqueue
```php
// BEFORE:
wp_enqueue_style(
    'wpapp-permission-matrix',
    WP_APP_CORE_PLUGIN_URL . 'assets/css/permissions/permission-matrix.css',
    [],
    WP_APP_CORE_VERSION
);

// AFTER:
wp_enqueue_style(
    'wpapp-permission-matrix',
    WP_APP_CORE_PLUGIN_URL . 'assets/css/permissions/wpapp-permission-matrix.css',
    [],
    WP_APP_CORE_VERSION
);
```

**Line ~240-246**: JS enqueue
```php
// BEFORE:
wp_enqueue_script(
    'wpapp-permission-matrix',
    WP_APP_CORE_PLUGIN_URL . 'assets/js/permissions/permission-matrix.js',
    ['jquery', 'wp-modal'],
    WP_APP_CORE_VERSION,
    true
);

// AFTER:
wp_enqueue_script(
    'wpapp-permission-matrix',
    WP_APP_CORE_PLUGIN_URL . 'assets/js/permissions/wpapp-permission-matrix.js',
    ['jquery', 'wp-modal'],
    WP_APP_CORE_VERSION,
    true
);
```

### **2. Permission Matrix Template**
**Path**: `/src/Views/templates/permissions/permission-matrix.php`

**Check for hardcoded references** (unlikely but verify)

### **3. Child Plugins (No changes needed)**
Child plugins (wp-customer, wp-agency) sudah menggunakan:
- `parent::enqueueAssets()` - otomatis load dari abstract
- Tidak ada hardcoded path references

## 📝 Implementation Steps

### Phase 1: Rename Files ✅
```bash
# Permissions CSS
mv assets/css/permissions/permission-matrix.css \
   assets/css/permissions/wpapp-permission-matrix.css

# Permissions JS
mv assets/js/permissions/permission-matrix.js \
   assets/js/permissions/wpapp-permission-matrix.js
```

### Phase 2: Update References ✅
1. Update `AbstractPermissionsController.php` (2 locations)
2. Grep search untuk any hardcoded references
3. Update documentation if any

### Phase 3: Test ✅
1. Test wp-app-core permissions tab
2. Test wp-customer permissions tab
3. Test wp-agency permissions tab (if exists)
4. Verify assets load correctly in DevTools

### Phase 4: Documentation ✅
1. Update README jika ada section tentang assets
2. Update inline comments
3. Add changelog entry

## 🎨 Naming Convention Standard

### **Global Scope Assets** (Shared across plugins)
```
Format: wpapp-{feature-name}.{ext}

Examples:
✅ wpapp-permission-matrix.css
✅ wpapp-permission-matrix.js
✅ wpapp-datatable.css
✅ wpapp-panel-manager.js
✅ wpapp-tab-manager.js
✅ wpapp-modal.js
```

### **Plugin-Specific Assets** (Not shared)
```
Format: {plugin-prefix}-{feature-name}.{ext}

Examples:
✅ platform-settings-style.css
✅ customer-settings-style.css
✅ agency-settings-style.css
```

### **Abstract/Base Assets** (Base untuk inheritance)
```
Format: settings-{type}.{ext} OR {feature}-base.{ext}

Examples:
✅ settings-style.css (base settings styles)
✅ settings-script.js (base settings behavior)
```

## ✅ Verification Checklist

### Pre-Rename
- [ ] Backup current files
- [ ] Document all reference locations
- [ ] Check child plugins don't have hardcoded paths

### Post-Rename
- [ ] Files renamed successfully
- [ ] AbstractPermissionsController updated
- [ ] No broken references in wp-app-core
- [ ] wp-customer permissions tab works
- [ ] wp-agency permissions tab works (if exists)
- [ ] Assets load in DevTools with correct names
- [ ] Console shows no 404 errors

### Testing
- [ ] Clear browser cache
- [ ] Clear WordPress cache
- [ ] Hard refresh (Ctrl+Shift+R)
- [ ] Test save permission (AJAX)
- [ ] Test reset permission (AJAX)
- [ ] Test all child plugins

## 📊 Impact Analysis

### **Files Affected**: 2 renames + 1 controller update
### **Child Plugins Affected**: 0 (use abstract method)
### **Breaking Changes**: None (internal refactor only)
### **Risk Level**: LOW (isolated to wp-app-core)

## 🔗 Related TODOs

- TODO-1206: Original permissions implementation
- TODO-2200: wp-customer permissions implementation (uses this)
- Future: Asset organization refactor

## 📝 Notes

- Handle naming sudah benar: `wpapp-permission-matrix` ✓
- Hanya filename yang perlu update, bukan handle
- Child plugins tidak perlu update karena menggunakan `parent::enqueueAssets()`
- Browser cache perlu di-clear setelah rename

---

**Created**: 2025-01-13
**Target**: Quick win - dapat diselesaikan dalam 30 menit
**Dependencies**: None
**Blocks**: None (nice-to-have standardization)
