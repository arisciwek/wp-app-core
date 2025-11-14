# TODO-1210: Consolidate Platform Scripts to Settings Directory

**Status:** ✅ Completed
**Date:** 2025-11-14
**Author:** arisciwek
**Context:** Task-1210

## Objective

Consolidate platform-specific JavaScript files by moving them from `/platform/` directory back to `/settings/` directory, while keeping the `platform-` prefix for clarity.

## Background

Previously in Task-1210 (v1.1.0), platform-specific scripts were moved from `/settings/` to `/platform/` directory to separate them from shared scripts. However, this task reverses that decision to consolidate all settings-related scripts in one location.

## Changes Made

### 1. File Movements (7 files)

Moved the following files from:
```
/wp-app-core/assets/js/platform/
```

To:
```
/wp-app-core/assets/js/settings/
```

Files moved:
1. ✅ `platform-email-tab-script.js`
2. ✅ `platform-error-logger.js`
3. ✅ `platform-general-tab-script.js`
4. ✅ `platform-permissions-tab-script.js`
5. ✅ `platform-security-authentication-tab-script.js`
6. ✅ `platform-security-policy-tab-script.js`
7. ✅ `platform-security-session-tab-script.js`

### 2. Updated References

Updated file: `src/Controllers/Assets/Strategies/SettingsPageAssets.php`

**Version:** 1.1.0 → 1.2.0

Changes made:
- ✅ Updated version number to 1.2.0
- ✅ Added changelog entry for Task-1210
- ✅ Updated path in line 121: `assets/js/platform/platform-error-logger.js` → `assets/js/settings/platform-error-logger.js`
- ✅ Updated path in line 123: `assets/js/platform/platform-error-logger.js` → `assets/js/settings/platform-error-logger.js`
- ✅ Updated path in line 235: `assets/js/platform/` → `assets/js/settings/`
- ✅ Updated path in line 242: `assets/js/platform/` → `assets/js/settings/`
- ✅ Updated comment on line 19: "Load platform-specific tab scripts from /platform/ directory" → "Load platform-specific tab scripts from /settings/ directory"
- ✅ Updated comment on line 212: "Moved from /settings/ to /platform/ directory" → "Consolidated in /settings/ directory"

## Files Structure After Changes

```
wp-app-core/
├── assets/
│   └── js/
│       ├── settings/                          # All settings-related scripts
│       │   ├── platform-email-tab-script.js           ✅ Moved here
│       │   ├── platform-error-logger.js                ✅ Moved here
│       │   ├── platform-general-tab-script.js          ✅ Moved here
│       │   ├── platform-permissions-tab-script.js      ✅ Moved here
│       │   ├── platform-security-authentication-tab-script.js  ✅ Moved here
│       │   ├── platform-security-policy-tab-script.js  ✅ Moved here
│       │   ├── platform-security-session-tab-script.js ✅ Moved here
│       │   ├── wpapp-settings-script.js
│       │   ├── wpapp-settings-reset-script.js
│       │   └── ...
│       └── platform/                          # Staff-related scripts only
│           ├── platform-staff-datatable.js
│           ├── platform-staff-datatable-script.js
│           └── platform-staff-script.js
```

## Testing Checklist

- [ ] Test platform settings page loads correctly
- [ ] Test error logger loads in head
- [ ] Test each settings tab:
  - [ ] General tab
  - [ ] Email tab
  - [ ] Security Authentication tab
  - [ ] Security Session tab
  - [ ] Security Policy tab
  - [ ] Permissions tab
- [ ] Verify no 404 errors in browser console
- [ ] Verify scripts load with correct filemtime cache busting

## Impact Assessment

**Low Risk** - This is a simple file relocation with path updates.

### Affected Components
1. ✅ `SettingsPageAssets.php` - Updated all references
2. ✅ JavaScript files - Moved to new location

### No Changes Required
- Script handles remain: `platform-settings-*`, `platform-error-logger`
- Hook names remain: `wpapp_after_platform_tab_script`
- File names remain with `platform-` prefix
- No other plugins reference these files (they are platform-specific)

## Notes

- The `platform-` prefix is retained to distinguish these scripts from shared scripts
- Staff-related scripts remain in `/platform/` directory as they are separate functionality
- This consolidation simplifies maintenance by keeping all settings scripts in one location

## Related Tasks

- TODO-1207: Shared assets refactoring
- TODO-1191: Platform staff separation

## Commit Message

```
TODO-1210: Consolidate platform scripts to settings directory

- Move 7 platform-specific JavaScript files from /platform/ to /settings/
- Update all path references in SettingsPageAssets.php
- Keep platform- prefix for clarity
- Update version to 1.2.0 with changelog
- Files moved:
  * platform-email-tab-script.js
  * platform-error-logger.js
  * platform-general-tab-script.js
  * platform-permissions-tab-script.js
  * platform-security-authentication-tab-script.js
  * platform-security-policy-tab-script.js
  * platform-security-session-tab-script.js

All settings-related scripts now consolidated in /settings/ directory.
```
