# TODO-1197: Remove Tab fadeIn Animation to Prevent Flicker

**Status**: ✅ COMPLETED
**Priority**: High
**Assignee**: arisciwek
**Created**: 2025-11-02
**Completed**: 2025-11-02
**Related Plugin**: wp-customer (TODO-2189)

## Problem

Tab switching in wp-app-core DataTable panel system has visual flicker/berdenyut (pulsing) effect when switching between tabs. This affects all plugins using the centralized tab system (wp-customer, wp-agency, etc).

### Root Cause

In `/assets/css/datatable/wpapp-datatable.css`:

```css
.wpapp-tab-content {
    display: none;
    animation: fadeIn 0.3s ease;  /* ← Problem */
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
```

**Why This Causes Flicker:**
1. Animation doesn't work with `display: none` → `display: block` transition
2. Content appears first (height changes), then animation runs
3. Layout shift + animation = visual "berdenyut" effect
4. Especially noticeable with lazy-loaded DataTables

### User Report
> "masih sangat kuat, tinggi dari teks dafar cabang dan daftar staff itu sama tombol juga sama, saat pindah tab terlihat berdenyut turun sedikit lalu naik di posisinya"

## Solution

Remove fadeIn animation completely for instant, smooth tab switching.

### Changes Made

**File**: `/wp-app-core/assets/css/datatable/wpapp-datatable.css`

**Before:**
```css
.wpapp-tab-content {
    display: none;
    animation: fadeIn 0.3s ease;
}

.wpapp-tab-content.active {
    display: block;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
```

**After:**
```css
.wpapp-tab-content {
    display: none;
}

.wpapp-tab-content.active {
    display: block;
}
```

## Impact

### Positive
- ✅ Eliminates flicker/berdenyut on all plugins
- ✅ Instant tab switching (better UX)
- ✅ No more workarounds needed in individual plugins
- ✅ Consistent behavior across all plugins

### Plugins Affected
- wp-customer
- wp-agency
- Any plugin using wpapp-tab-content

### Migration for Existing Plugins

Plugins that added workarounds can remove them:

```css
/* Can be removed now */
.wpapp-tab-content {
    animation: none !important;
}
```

## Testing

### Test Cases
1. ✅ Switch between tabs rapidly → No flicker
2. ✅ Lazy-loaded tabs → No berdenyut on first load
3. ✅ Direct-rendered tabs → Instant switch
4. ✅ wp-customer: Info ↔ Cabang ↔ Staff tabs → Smooth
5. ✅ wp-agency: Data ↔ Unit Kerja ↔ Staff tabs → Smooth

### Tested On
- wp-customer plugin (TODO-2189)
- Browser: Chrome, Firefox
- Screen sizes: Desktop, Mobile

## Notes

- Simple CSS solution (display toggle only)
- If animation is desired in future, use opacity transition instead:
  ```css
  .wpapp-tab-content {
      display: none;
      opacity: 0;
      transition: opacity 0.15s ease-in;
  }

  .wpapp-tab-content.active {
      display: block;
      opacity: 1;
  }
  ```
  But current instant switch is preferred for best UX.

## Files Changed

```
wp-app-core/
└── assets/
    └── css/
        └── datatable/
            └── wpapp-datatable.css (lines 687-706 removed)
```

## Related Issues

- wp-customer TODO-2189: Implement customer tabs
- User feedback: "style yang lama" tidak punya masalah ini karena tidak pakai animation

## Conclusion

Core framework issue resolved. All plugins using tab system now have smooth, flicker-free tab switching without needing individual workarounds.
