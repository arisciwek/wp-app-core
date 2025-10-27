# TODO-1182: Adopt Anti-Flicker Panel Pattern from Platform Staff

**Status**: ✅ COMPLETED
**Plugin**: wp-app-core
**Created**: 2025-10-26
**Completed**: 2025-10-26
**Related**: Platform Staff implementation

## 📋 Description

Adopsi pattern anti-flicker dari platform-staff-script.js ke wpapp-panel-manager.js untuk smooth panel transitions tanpa flicker. Implementasi 45%/55% width split pattern dengan double setTimeout untuk DataTable adjustment.

## 🎯 Problem Statement

Panel kanan saat dibuka menyebabkan:
1. DataTable flicker karena width adjustment tidak smooth
2. Layout jump saat panel transition
3. DataTable tidak ter-adjust dengan baik saat panel width berubah

## ✅ What Was Done

### 1. Update wpapp-panel-manager.js

**File**: `assets/js/datatable/wpapp-panel-manager.js`

**Changes**:

**Line 51**: Add dataTable property
```javascript
this.dataTable = null;
```

**Line 75**: Get DataTable instance from DOM
```javascript
this.getDataTableInstance();
```

**Line 92-104**: New method `getDataTableInstance()`
```javascript
getDataTableInstance() {
    const $table = $('.wpapp-datatable');

    if ($table.length > 0 && $.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
        this.dataTable = $table.DataTable();
        console.log('[WPApp Panel] DataTable instance found');
    } else {
        console.log('[WPApp Panel] No DataTable instance found');
    }
}
```

**Line 250-299**: Refactored `showPanel()` with anti-flicker pattern
```javascript
showPanel() {
    // 1. Scroll to top FIRST (prevent flicker)
    window.scrollTo(0, scrollTarget);

    // 2. Add classes to trigger CSS transition (300ms)
    this.layout.addClass('with-right-panel');
    this.rightPanel.removeClass('hidden').addClass('visible');

    // 3. Force reflow
    this.layout[0].offsetHeight;

    // 4. Wait 350ms (300ms CSS transition + 50ms buffer)
    setTimeout(function() {
        if (self.dataTable) {
            // Adjust DataTable columns
            self.dataTable.columns.adjust();

            // Wait 50ms then redraw
            setTimeout(function() {
                self.dataTable.draw(false);
            }, 50);
        }
    }, 350);
}
```

**Line 305-334**: Refactored `hidePanel()` with anti-flicker pattern
```javascript
hidePanel() {
    // 1. Remove visible class
    this.rightPanel.removeClass('visible');

    // 2. Wait 300ms for CSS transition
    setTimeout(function() {
        self.rightPanel.addClass('hidden');
        self.layout.removeClass('with-right-panel');

        // Adjust DataTable back to full width
        if (self.dataTable) {
            self.dataTable.columns.adjust();

            setTimeout(function() {
                self.dataTable.draw(false);
            }, 50);
        }
    }, 300);
}
```

### 2. Update wpapp-datatable.css

**File**: `assets/css/datatable/wpapp-datatable.css`

**Changes**:

**Line 176-197**: Add wpapp-left-panel class with 45%/55% pattern
```css
/* Left panel - with transition */
.wpapp-left-panel,
.wpapp-datatable-layout .wpapp-col-md-12 {
    width: 100%;
    transition: width 0.3s ease;
    flex: 1 1 100%;
    max-width: 100%;
}

/* When right panel visible, shrink to 45% */
.wpapp-datatable-layout.with-right-panel .wpapp-left-panel,
.wpapp-datatable-layout.with-right-panel .wpapp-col-md-12 {
    width: 45%;
    flex: 1 1 45%;
    max-width: 45%;
}
```

**Line 200-217**: Add wpapp-right-panel class with 55% width
```css
/* Right panel - 55% width when visible */
.wpapp-right-panel,
.wpapp-datatable-layout .wpapp-col-md-5 {
    transition: all 0.3s ease, opacity 0.2s ease;
    width: 55%;
    flex: 0 0 55%;
    max-width: 55%;
}
```

**Line 220-231**: Hidden state
```css
.wpapp-right-panel.hidden,
.wpapp-datatable-layout .wpapp-col-md-5.wpapp-hidden {
    width: 0%;
    flex: 0 0 0%;
    opacity: 0;
    display: none;
}
```

### 3. Update PanelLayoutTemplate.php

**File**: `src/Views/DataTable/Templates/PanelLayoutTemplate.php`

**Changes**:

**Line 50**: Add `wpapp-left-panel` class
```php
<div class="wpapp-col-md-12 wpapp-left-panel" id="wpapp-<?php echo esc_attr($entity); ?>-table-container">
```

**Line 60**: Add `wpapp-right-panel` class
```php
<div class="wpapp-col-md-5 wpapp-right-panel wpapp-detail-panel wpapp-hidden hidden"
```

## 🔄 Anti-Flicker Pattern Explained

### Platform Staff Pattern (Adopted)

```javascript
// 1. CSS Transition (300ms)
.left-panel {
    transition: width 0.3s ease;
}

// 2. Double setTimeout Pattern
// Open panel:
this.layout.addClass('with-right-panel'); // Trigger CSS

setTimeout(() => {
    // Wait for CSS transition (300ms) + buffer (50ms)
    dataTable.columns.adjust(); // Recalculate widths

    setTimeout(() => {
        dataTable.draw(false); // Smooth redraw
    }, 50);
}, 350);
```

### Why This Works

1. **Scroll First**: Prevent layout jump by scrolling to top before panel opens
2. **CSS Transition**: Smooth width animation (300ms)
3. **Force Reflow**: `layout[0].offsetHeight` forces browser to calculate layout
4. **Wait for Transition**: setTimeout 350ms (300ms transition + 50ms buffer)
5. **Adjust DataTable**: Recalculate column widths after transition complete
6. **Smooth Redraw**: setTimeout 50ms before final draw prevents flicker

## 📊 Width Split

| State | Left Panel | Right Panel |
|-------|------------|-------------|
| Closed | 100% | 0% (hidden) |
| Open | 45% | 55% |

## 🎨 CSS Classes

**Global Scope** (wp-app-core):
- `.wpapp-left-panel` - Left panel (DataTable)
- `.wpapp-right-panel` - Right panel (Details)
- `.with-right-panel` - State class on layout container
- `.visible` - Panel is visible
- `.hidden` - Panel is hidden

## ✨ Benefits

1. ✅ **No Flicker**: Smooth transitions without layout jump
2. ✅ **Consistent Pattern**: Same as platform-staff (proven stable)
3. ✅ **Better UX**: 45%/55% split gives more space for details
4. ✅ **DataTable Aware**: Automatically adjusts columns during transition
5. ✅ **Backward Compatible**: Keeps existing col-md-12/col-md-5 classes

## 🔗 Related Files

- **Platform Staff JS**: `assets/js/platform/platform-staff-script.js`
- **Platform Staff CSS**: `assets/css/platform/platform-staff-style.css`
- **Panel Manager**: `assets/js/datatable/wpapp-panel-manager.js`
- **DataTable CSS**: `assets/css/datatable/wpapp-datatable.css`
- **Panel Template**: `src/Views/DataTable/Templates/PanelLayoutTemplate.php`

## 🔄 Next Steps

### Phase 2: Agency Implementation (TODO-3079)

1. Test panel opening for agency dashboard
2. Verify DataTable adjusts smoothly
3. Check all tabs load correctly in panel
4. Ensure no flicker on open/close

## 📝 Testing Checklist

- ✅ Panel opens smoothly without flicker
- ✅ Left panel shrinks to 45% with smooth transition
- ✅ Right panel appears at 55% width
- ✅ DataTable columns adjust correctly
- ✅ No layout jump during transition
- ✅ Panel closes smoothly back to 100%
- ✅ No scroll jump when opening panel

## 💡 Key Learnings

1. **Double setTimeout**: 350ms wait for CSS, 50ms for smooth render
2. **Force Reflow**: `element.offsetHeight` forces layout calculation
3. **Scroll First**: Prevent jump by scrolling before animation
4. **CSS Transition**: Let CSS handle animation, JS handles timing
5. **DataTable.draw(false)**: Parameter false keeps current page

## 📌 Notes

- Platform staff pattern has been proven stable over multiple iterations
- 45%/55% split provides better balance than 58%/42%
- Keeping both col-md-* and wpapp-* classes ensures backward compatibility
- DataTable auto-adjust only works if DataTable instance is available
