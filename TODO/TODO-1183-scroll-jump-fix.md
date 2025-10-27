# TODO-1183: Fix Scroll Jump on Panel Open

**Status:** ✅ COMPLETED
**Date Created:** 2025-10-27
**Date Completed:** 2025-10-27
**Priority:** HIGH
**Category:** Bug Fix

---

## 📋 Description

Fix scroll jump yang terjadi saat user membuka detail panel. Browser otomatis scroll karena direct assignment `window.location.hash`.

---

## 🎯 Root Cause

**File Affected:**
1. `wp-app-core/assets/js/datatable/panel-handler.js:449`
2. `wp-app-core/assets/js/datatable/wpapp-panel-manager.js:666`

**Problem:**
```javascript
// ❌ CAUSES SCROLL JUMP
window.location.hash = newHash;
```

Browser will attempt to scroll to element with matching ID.

---

## ✅ Solution Implemented

**Changed to:**
```javascript
// ✅ NO SCROLL JUMP
history.pushState(null, document.title, pathname + search + '#' + newHash);
```

**Files Modified:**
1. `panel-handler.js:443-451` - Removed fallback to `window.location.hash`
2. `wpapp-panel-manager.js:664-670` - Changed to `history.pushState()`

**Already Correct:**
- `wpapp-tab-manager.js:208` - Already using `history.replaceState()`

---

## 🧪 Test Results

- ✅ Open panel - No scroll jump
- ✅ Switch rows - No scroll jump
- ✅ Switch tabs - No scroll jump
- ✅ Browser back/forward - Hash works correctly
- ✅ URL hash updates properly for bookmarking

---

## 📝 Notes

- `history.pushState()` supported by all modern browsers (IE10+)
- Removed fallback code for legacy browsers
- Hash updates WITHOUT triggering scroll

---

## 🔗 Related

- TODO-1184: Flicker fix (loading states)
