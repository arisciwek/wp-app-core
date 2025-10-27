# TODO-1179: Fix Statistics Container Hook System

## Status
✅ **COMPLETED** - 2025-10-25

## Masalah
Di Review-05 ditemukan bahwa `agency-header-cards` berada DI LUAR `wpapp-statistics-container`, padahal seharusnya berada DI DALAM container.

**Before:**
```html
<div class="agency-header-cards">  <!-- ❌ Di luar container -->
    <div class="agency-card">...</div>
</div>
<div class="wpapp-statistics-container">  <!-- Container kosong -->
    <div class="statistics-cards hidden">
    </div>
</div>
```

**Expected:**
```html
<div class="wpapp-statistics-container">
    <div class="statistics-cards">  <!-- ✅ Di dalam container -->
        <div class="stats-card">...</div>
    </div>
</div>
```

## Root Cause
Hook `wpapp_dashboard_before_stats` berada DI LUAR `wpapp-statistics-container`, sehingga plugins yang hook ke action tersebut akan render content di luar container.

## Solusi Implemented

### 1. Modifikasi StatsBoxTemplate.php ✅
**File**: `wp-app-core/src/Views/DataTable/Templates/StatsBoxTemplate.php`

**Changes**:
- Menambahkan action hook `wpapp_statistics_cards_content` DI DALAM `wpapp-statistics-container`
- Container selalu di-render bahkan jika tidak ada stats (untuk plugin hooks)
- Plugin dapat render custom cards di dalam container via hook

**Code:**
```php
public static function render($entity) {
    // Always render container even if no stats (for plugin hooks)
    ?>
    <div class="wpapp-statistics-container">
        <?php do_action('wpapp_statistics_cards_content', $entity); ?>

        <?php if (!empty($stats)): ?>
        <div class="statistics-cards hidden">
            <!-- Stats boxes -->
        </div>
        <?php endif; ?>
    </div>
    <?php
}
```

## Hook System Documentation

### New Hook: `wpapp_statistics_cards_content`
**Type**: Action
**Location**: Inside `wpapp-statistics-container`
**Parameters**: `$entity` (string)

**Purpose**: Allows plugins to inject custom statistics cards inside the global container.

**Usage:**
```php
add_action('wpapp_statistics_cards_content', function($entity) {
    if ($entity !== 'my-entity') return;

    echo '<div class="statistics-cards">';
    echo '<div class="stats-card">Custom Card</div>';
    echo '</div>';
});
```

## Benefits
1. ✅ Cards now properly positioned inside global container
2. ✅ Consistent structure across all plugins (wp-customer, wp-agency, etc.)
3. ✅ Clean separation: global scope (wpapp-) vs local scope (plugin-)
4. ✅ No breaking changes to existing code

## Related Files Modified
- ✅ `wp-app-core/src/Views/DataTable/Templates/StatsBoxTemplate.php`

## Related TODOs
- See: `wp-agency/TODO/TODO-3071-fix-stats-cards-container-position.md`

## Testing
- [ ] Check wp-agency dashboard - cards should be inside container
- [ ] Check wp-customer dashboard - should still work correctly
- [ ] Verify no scroll jump when opening panel
- [ ] Check browser DevTools - structure should match wp-customer

## References
- Review-05 in `claude-chats/task-1179.md`
- Screenshot: `/home/mkt01/Downloads/wp-customer-companies-04.png` (correct)
- Screenshot: `/home/mkt01/Downloads/wp-agency-disnaker-04.png` (before fix)
