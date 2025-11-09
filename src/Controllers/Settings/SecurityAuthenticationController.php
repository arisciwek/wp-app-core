<?php
/**
 * Security Authentication Controller
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Settings
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Settings/SecurityAuthenticationController.php
 *
 * Description: Controller untuk authentication & access control settings.
 *              REFACTORED: Now extends AbstractSettingsController.
 *              Extracted from monolithic PlatformSettingsController.
 *
 * Changelog:
 * 2.0.0 - 2025-01-09 (TODO-1203)
 * - BREAKING: Extracted from PlatformSettingsController
 * - Now extends AbstractSettingsController
 * - Single responsibility: Authentication settings only
 * 1.0.0 - 2025-10-19
 * - Was part of PlatformSettingsController
 */

namespace WPAppCore\Controllers\Settings;

use WPAppCore\Controllers\AbstractSettingsController;
use WPAppCore\Models\AbstractSettingsModel;
use WPAppCore\Models\Settings\SecurityAuthenticationModel;
use WPAppCore\Validators\AbstractSettingsValidator;
use WPAppCore\Validators\SecurityAuthenticationValidator;

class SecurityAuthenticationController extends AbstractSettingsController {

    protected function getPluginSlug(): string {
        return 'wp-app-core';
    }

    protected function getPluginPrefix(): string {
        return 'wpapp';
    }

    protected function getSettingsPageSlug(): string {
        return 'wp-app-core-settings';
    }

    protected function getSettingsCapability(): string {
        return 'manage_options';
    }

    protected function getDefaultTabs(): array {
        // No tabs - this controller handles single tab content
        return [];
    }

    protected function getModel(): AbstractSettingsModel {
        return new SecurityAuthenticationModel();
    }

    protected function getValidator(): AbstractSettingsValidator {
        return new SecurityAuthenticationValidator();
    }
}
