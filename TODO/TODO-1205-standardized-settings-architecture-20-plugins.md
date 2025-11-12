# TODO-1205: Central Save & Reset Dispatcher Architecture

**Status:** In Progress
**Priority:** CRITICAL
**Created:** 2025-11-12
**Plugin:** wp-app-core
**Affected Plugins:** ALL 20 plugins in marketplace platform

---

## Problem Statement

### What Happened (24 Hours Wasted)
After implementing settings architecture (TODO-1204), save and reset functionality has SEVERE issues:

**Issues:**
1. ❌ **Reset tidak benar-benar reset data** - notification benar tapi data tidak berubah
2. ❌ **Save notification tidak custom per tab** - semua tab tampil "Settings saved" generic
3. ❌ **Multiple controllers process satu request** - inefficient, semua controller check "apakah ini form saya?"
4. ❌ **Complex debugging** - susah trace mana controller yang handle
5. ❌ **Tidak reusable** - pattern ini harus di-copy ke 20 plugin dengan risk error tiap plugin

### Root Cause Analysis

**Current Flow (BROKEN):**
```
User click Reset
  ↓
Form POST to options.php with reset_to_defaults=1
  ↓
WordPress admin_init fired
  ↓
ALL 5 controllers run handleResetViaPost()
  - PlatformGeneralSettingsController::handleResetViaPost()
      → check: option_page === 'platform_settings'? NO → return
  - EmailSettingsController::handleResetViaPost()
      → check: option_page === 'platform_email_settings'? YES
      → update_option(defaults) ✅
      → add_filter('wp_redirect') ✅
      → exit
  ↓
BUT WordPress continue process form POST! ❌
  ↓
WordPress sanitize_callback() called
  ↓
update_option(OLD DATA from form fields) ← OVERWRITE defaults!
  ↓
Result: Notification benar, data salah ❌
```

**Why This Pattern Fails:**
1. Each controller tries to handle ALL requests
2. No centralized control flow
3. WordPress form processing conflicts with custom reset
4. Wasted checks (4 controllers return, 1 processes)

---

## Solution: Abstract + Hook Pattern (Option B)

### Architecture Decision

**REJECTED - Option A (Orchestrator Extend Abstract):**
- 1 controller per plugin
- Orchestrator extends Abstract
- Has switch/case routing logic
- More debugging points
- Tab handlers are plain classes

**ACCEPTED - Option B (Tab Controllers Extend Abstract):**
- 1 controller per tab
- Each tab controller extends Abstract
- No routing logic in controller
- Minimal debugging
- Orchestrator is plain dispatcher class

### Why Option B?

| Criteria | Option A | Option B |
|----------|----------|----------|
| Debugging complexity | High (routing bugs) | Low (isolated) |
| Code per tab | 10-15 lines | 2-3 lines |
| Bug isolation | Bug affects all tabs | Bug per tab only |
| Hook registration | Manual | Auto by parent |
| Reusability | Complex | Simple copy |
| WordPress Way | Custom routing | Hook system |

---

## Implementation Plan

### Level 1: AbstractSettingsController (Foundation)

**File:** `src/Controllers/Abstract/AbstractSettingsController.php`

**Changes:**

1. **Add abstract methods** (ENFORCE standardization):
```php
/**
 * Save settings
 * Called via hook by central dispatcher
 *
 * @param array $data POST data to save
 * @return bool True if saved successfully
 */
abstract protected function doSave(array $data): bool;

/**
 * Reset settings to defaults
 * Called via hook by central dispatcher
 *
 * @return array Default settings
 */
abstract protected function doReset(): array;
```

2. **Auto-register hooks in init()**:
```php
public function init(): void {
    // Get option name for this controller
    $option_name = $this->getOptionName();

    // AUTO-REGISTER save hook
    add_filter("wpapp_save_{$option_name}", [$this, 'handleSaveHook'], 10, 2);

    // AUTO-REGISTER reset hook
    add_filter("wpapp_reset_{$option_name}", [$this, 'handleResetHook'], 10, 2);

    // Continue with other registrations
    add_action('admin_init', [$this, 'registerSettings']);
    add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
}
```

3. **Add hook wrapper methods**:
```php
/**
 * Handle save via hook (wrapper for doSave)
 * This is called by central dispatcher via apply_filters
 */
public function handleSaveHook($result, $post_data) {
    // Permission check
    if (!current_user_can($this->getSettingsCapability())) {
        return false;
    }

    // Validate
    if (!$this->validator->validate($post_data)) {
        return false;
    }

    // Call child implementation
    return $this->doSave($post_data);
}

/**
 * Handle reset via hook (wrapper for doReset)
 * This is called by central dispatcher via apply_filters
 */
public function handleResetHook($result, $option_page) {
    // Permission check
    if (!current_user_can($this->getSettingsCapability())) {
        return [];
    }

    // Call child implementation
    return $this->doReset();
}
```

4. **Remove OLD broken methods**:
- ❌ Remove `handleResetViaPost()` - conflict dengan WordPress flow
- ❌ Remove `handleSaveSettings()` - tidak dipakai lagi
- ❌ Remove `registerAjaxHandlers()` - tidak pakai AJAX

---

### Level 2: PlatformSettingsPageController (Central Dispatcher)

**File:** `src/Controllers/Settings/PlatformSettingsPageController.php`

**Changes:**

1. **Add central POST detector in init()**:
```php
public function init(): void {
    // Initialize all specialized controllers FIRST
    foreach ($this->controllers as $tab => $controller) {
        $controller->init(); // This registers hooks
    }

    // Register settings
    add_action('admin_init', [$this, 'registerSettings']);

    // CRITICAL: Handle save/reset BEFORE WordPress processes form
    add_action('admin_init', [$this, 'handleFormSubmission'], 1); // Priority 1 - very early
}
```

2. **Add handleFormSubmission() method**:
```php
/**
 * Central dispatcher for save & reset
 * Priority 1 - runs BEFORE WordPress Settings API processes form
 */
public function handleFormSubmission(): void {
    // Only handle our forms
    if (!isset($_POST['option_page'])) {
        return;
    }

    $option_page = $_POST['option_page'] ?? '';

    // Only handle our settings
    $our_settings = [
        'platform_settings',
        'platform_email_settings',
        'platform_security_authentication',
        'platform_security_session',
        'platform_security_policy'
    ];

    if (!in_array($option_page, $our_settings)) {
        return;
    }

    // Verify nonce
    check_admin_referer($option_page . '-options');

    // DISPATCH: Reset request?
    if (isset($_POST['reset_to_defaults']) && $_POST['reset_to_defaults'] === '1') {
        $this->dispatchReset($option_page);
        return; // exit handled in dispatchReset
    }

    // DISPATCH: Save request?
    $this->dispatchSave($option_page);
    // Let WordPress continue to handle redirect
}
```

3. **Add dispatchReset() method**:
```php
/**
 * Dispatch reset request via hook
 */
private function dispatchReset(string $option_page): void {
    // Trigger hook - controller yang match option_page akan respond
    $defaults = apply_filters("wpapp_reset_{$option_page}", [], $option_page);

    if (empty($defaults)) {
        // No controller handled this
        wp_die('Invalid reset request');
    }

    // Update option with defaults
    update_option($option_page, $defaults);

    // Build redirect URL
    $current_tab = $_POST['current_tab'] ?? '';
    $redirect_url = add_query_arg([
        'page' => 'wp-app-core-settings',
        'tab' => $current_tab,
        'reset' => 'success',
        'reset_tab' => $current_tab
    ], admin_url('admin.php'));

    // CRITICAL: Redirect and exit to prevent WordPress from processing form
    wp_redirect($redirect_url);
    exit;
}
```

4. **Add dispatchSave() method**:
```php
/**
 * Dispatch save request via hook
 */
private function dispatchSave(string $option_page): void {
    // Trigger hook - controller yang match option_page akan respond
    $saved = apply_filters("wpapp_save_{$option_page}", false, $_POST);

    if (!$saved) {
        // Validation failed or no controller handled this
        add_settings_error(
            $option_page,
            'save_failed',
            __('Failed to save settings. Please check your input.', 'wp-app-core')
        );
    }

    // Let WordPress continue to process form and redirect
    // This allows WordPress Settings API to handle redirect with settings-updated parameter
}
```

5. **Update addSettingsSavedMessage() to skip reset**:
```php
public function addSettingsSavedMessage(string $location, int $status): string {
    // Only handle redirects from options.php for our settings
    if (strpos($location, 'page=wp-app-core-settings') === false) {
        return $location;
    }

    // SKIP if this is a RESET request (handled by dispatchReset)
    if (isset($_POST['reset_to_defaults']) && $_POST['reset_to_defaults'] === '1') {
        return $location;
    }

    // ... rest of save redirect handling
}
```

---

### Level 3: Child Controllers Implementation

**File:** `src/Controllers/Settings/PlatformGeneralSettingsController.php`

**Changes:**

```php
class PlatformGeneralSettingsController extends AbstractSettingsController {

    // ... existing abstract method implementations ...

    /**
     * Save settings (called by hook)
     *
     * @param array $data POST data
     * @return bool True if saved
     */
    protected function doSave(array $data): bool {
        // Get settings from POST
        $settings = $data['platform_settings'] ?? [];

        // Save via model
        return $this->model->saveSettings($settings);
    }

    /**
     * Reset settings (called by hook)
     *
     * @return array Default settings
     */
    protected function doReset(): array {
        return $this->model->getDefaults();
    }
}
```

**That's it!** 2-3 lines per method. No routing. No complexity.

---

## Testing Checklist

### Test: General Tab Save
- [ ] Ubah company_name dari "PT Digital Marketplace Indonesia" ke "Test Company"
- [ ] Klik Save General Settings
- [ ] Expected:
  - Halaman reload
  - Notification: "General settings have been saved successfully."
  - company_name di database = "Test Company"
  - `wp option get platform_settings --format=json | grep company_name`

### Test: General Tab Reset
- [ ] Pastikan company_name = "Test Company" (bukan default)
- [ ] Klik Reset to Default
- [ ] Expected:
  - Modal WPModal muncul dengan message kontekstual
  - Klik "Reset Settings"
  - Halaman reload
  - Notification: "General settings have been reset to default values successfully."
  - company_name kembali ke "PT Digital Marketplace Indonesia"
  - `wp option get platform_settings --format=json | grep company_name`

### Test: Hook System
```bash
# Verify hooks registered
wp eval "
global \$wp_filter;
echo isset(\$wp_filter['wpapp_save_platform_settings']) ? 'SAVE HOOK: YES' : 'SAVE HOOK: NO';
echo \"\\n\";
echo isset(\$wp_filter['wpapp_reset_platform_settings']) ? 'RESET HOOK: YES' : 'RESET HOOK: NO';
"
```

Expected:
```
SAVE HOOK: YES
RESET HOOK: YES
```

---

## Reusability for 20 Plugins

### Pattern for Other Plugins (e.g., wp-customer)

**1. Create CustomerSettingsPageController (Orchestrator)**
```php
class CustomerSettingsPageController {
    private array $controllers = [];

    public function __construct() {
        $this->controllers = [
            'customer-general' => new CustomerGeneralSettingsController(),
            'customer-billing' => new CustomerBillingSettingsController(),
        ];
    }

    public function init(): void {
        foreach ($this->controllers as $controller) {
            $controller->init(); // Auto-registers hooks!
        }

        add_action('admin_init', [$this, 'handleFormSubmission'], 1);
    }

    public function handleFormSubmission(): void {
        // EXACT SAME CODE as PlatformSettingsPageController
        // Copy-paste dari wp-app-core
    }
}
```

**2. Create CustomerGeneralSettingsController (Tab Controller)**
```php
class CustomerGeneralSettingsController extends AbstractSettingsController {
    protected function doSave(array $data): bool {
        return $this->model->saveSettings($data['customer_settings'] ?? []);
    }

    protected function doReset(): array {
        return $this->model->getDefaults();
    }
}
```

**That's it!** No debugging. No complexity. Standard pattern.

---

## Benefits Summary

### For Development
✅ **Minimal debugging** - 2-3 lines per tab controller
✅ **Bug isolation** - Error di 1 tab tidak affect tab lain
✅ **Standard pattern** - Abstract PAKSA implementation yang benar
✅ **WordPress Way** - Hook system, bukan manual routing
✅ **Auto-dispatch** - Tidak perlu manual check "apakah ini form saya?"

### For 20 Plugins
✅ **Reusable** - Copy orchestrator logic, paste ke plugin baru
✅ **No re-debugging** - Pattern sudah proven work
✅ **Forced standard** - Abstract PAKSA doSave/doReset
✅ **Scalable** - Tambah tab baru = buat controller baru, done

### Technical
✅ **No wasted checks** - Only 1 controller responds to 1 request
✅ **No form conflict** - Reset exit sebelum WordPress process form
✅ **Custom notifications** - Per-tab save & reset messages
✅ **Clean separation** - Dispatcher vs Implementation

---

## Implementation Order

**DO NOT START CODING BEFORE COMPLETING EACH LEVEL!**

1. ✅ **Level 1: AbstractSettingsController** - Add abstract methods + hook wrappers
2. ✅ **Level 2: PlatformSettingsPageController** - Add central dispatcher
3. ✅ **Level 3: PlatformGeneralSettingsController** - Implement doSave/doReset
4. ✅ **Test: General tab save** - Verify data saved correctly
5. ✅ **Test: General tab reset** - Verify data reset correctly
6. 🔄 **Document results** - Update this file with test results

**ONLY AFTER GENERAL TAB WORKS:**
7. Copy pattern to EmailSettingsController
8. Copy pattern to Security*Controllers
9. Final integration test

---

## Notes

- **DO NOT implement all tabs at once** - 1 tab proof of concept first
- **DO NOT skip testing** - Test save & reset after each level
- **DO NOT modify multiple files simultaneously** - 1 level at a time
- **DO use git commit** - Commit after each working level

---

## Files Modified

### To Be Modified:
- [ ] `src/Controllers/Abstract/AbstractSettingsController.php`
- [ ] `src/Controllers/Settings/PlatformSettingsPageController.php`
- [ ] `src/Controllers/Settings/PlatformGeneralSettingsController.php`

### Supporting Files (Already Correct):
- ✅ `src/Views/templates/settings/tab-general.php` - Has hidden inputs
- ✅ `src/Views/templates/settings/settings-page.php` - Has reset button
- ✅ `assets/js/settings/settings-reset-helper-post.js` - WPModal + form POST

---

## Success Criteria

**This TODO is DONE when:**
1. ✅ General tab save works - data saved, custom notification
2. ✅ General tab reset works - data reset, custom notification
3. ✅ Hook system verified - `wp eval` shows hooks registered
4. ✅ Pattern documented - Clear steps untuk 19 plugin lainnya
5. ✅ Zero debugging - No issues, no back-and-forth

**Estimated Time:** 1-2 hours (if done carefully, step by step)

**Wasted Time So Far:** 24+ hours (due to tidak ada pattern jelas)

**Time Saved for 19 Plugins:** ~456 hours (24h × 19 plugins)

---

END OF TODO-1205
