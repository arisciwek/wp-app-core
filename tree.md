.
├── assets
│   ├── css
│   │   ├── admin-bar
│   │   │   └── admin-bar-style.css
│   │   └── settings
│   │       ├── common-style.css
│   │       ├── demo-data-tab-style.css
│   │       ├── general-tab-style.css
│   │       ├── invoice-payment-style.css
│   │       ├── permissions-tab-style.css
│   │       ├── security-authentication-tab-style.css
│   │       ├── security-policy-tab-style.css
│   │       ├── security-session-tab-style.css
│   │       ├── settings.css
│   │       └── settings-style.css
│   └── js
│       ├── admin-bar
│       │   └── admin-bar-script.js
│       └── settings
│           ├── customer-demo-data-tab-script.js
│           ├── customer-membership-features-tab-script.js
│           ├── customer-membership-levels-tab-script.js
│           ├── invoice-payment-script.js
│           ├── permissions-tab-script.js
│           ├── platform-demo-data-tab-script.js
│           ├── security-authentication-tab-script.js
│           ├── security-policy-tab-script.js
│           ├── security-session-tab-script.js
│           ├── settings.js
│           └── settings-script.js
├── claude-chats
│   ├── debug-logging-guide.md
│   ├── implementation-plan-01-summary.md
│   ├── plan-01-status.md
│   ├── review-03-debug-guide.md
│   ├── review-03-fix-summary.md
│   ├── review-04-revert-summary.md
│   ├── task-1201.md
│   ├── task-1202.md
│   ├── task-1205.md
│   └── task-1206.md
├── cron
├── docs
│   ├── example-simple-integration.php
│   ├── plugin-integration-guide.md
│   ├── QUICK-START-SIMPLIFIED-INTEGRATION.md
│   ├── README-SIMPLIFIED-INTEGRATION.md
│   ├── TODO-1205-implementation-summary.md
│   ├── TODO-1205-Review-02-Simplification-Analysis.md
│   ├── TODO-1205-Review-03-Implementation-Summary.md
│   ├── TODO-1205-Review-04-Dependencies-Fix.md
│   ├── TODO-1205-Review-05-Admin-Bar-Visibility-Fix.md
│   └── TODO-1205-wp-customer-integration-checklist.md
├── includes
│   ├── class-activator.php
│   ├── class-admin-bar-info.php
│   ├── class-autoloader.php
│   ├── class-deactivator.php
│   ├── class-dependencies.php
│   └── class-role-manager.php
├── languages
├── LICENSE
├── README.md
├── src
│   ├── API
│   ├── Cache
│   │   └── CustomerCacheManager.php
│   ├── Controllers
│   │   ├── MenuManager.php
│   │   ├── PlatformSettingsController.php
│   │   └── SettingsController.php
│   ├── Database
│   │   ├── Demo
│   │   │   ├── Data
│   │   │   │   ├── AbstractDemoData.php
│   │   │   │   ├── CustomerDemoData.php
│   │   │   │   ├── CustomerUsersData.php
│   │   │   │   ├── PlatformDemoData.php
│   │   │   │   ├── PlatformUsersData.php
│   │   │   │   └── WPUserGenerator.php
│   │   │   └── WPUserGenerator.php
│   │   ├── Installer.php
│   │   └── Tables
│   │       ├── CustomersDB.php
│   │       └── PlatformStaffDB.php
│   ├── Helpers
│   │   └── FileUploadHelper.php
│   ├── Models
│   │   ├── AdminBarModel.php
│   │   └── Settings
│   │       ├── EmailSettingsModel.php
│   │       ├── PermissionModel.php
│   │       ├── PlatformPermissionModel.php
│   │       ├── PlatformSettingsModel.php
│   │       ├── SecurityAuthenticationModel.php
│   │       ├── SecurityPolicyModel.php
│   │       ├── SecuritySessionModel.php
│   │       └── SettingsModel.php
│   ├── Validators
│   └── Views
│       └── templates
│           ├── admin-bar
│           │   └── dropdown.php
│           ├── settings
│           │   ├── settings-page.php
│           │   ├── settings_page.php
│           │   ├── tab-demo-data.php
│           │   ├── tab-email.php
│           │   ├── tab-general.php
│           │   ├── tab-invoice-payment.php
│           │   ├── tab-permissions.php
│           │   ├── tab-security-authentication.php
│           │   ├── tab-security-policy.php
│           │   └── tab-security-session.php
│           └── user
│               └── _user_profile_fields.php
├── TODO
│   ├── ANALYSIS-1204-review-01.md
│   ├── comparison-hover-dropdown-css.md
│   ├── PROPOSAL-1204-method-migration.md
│   ├── REVIEW-01-platform-user-generation.md
│   ├── TODO-1201-wp-app-core-admin-bar-integration.md
│   ├── TODO-1202-review-07-assets-reorganization.md
│   ├── TODO-1203-review-08-css-loading-fix.md
│   ├── TODO-1204-method-migration.md
│   └── TODO-1206-platform-settings-implementation.md
├── TODO.md
├── tree.md
└── wp-app-core.php

30 directories, 99 files
