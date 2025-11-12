<?php
/**
 * Security Session Controller
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Settings
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Settings/SecuritySessionController.php
 *
 * Description: Controller untuk session & login management settings.
 *              REFACTORED: Now extends AbstractSettingsController.
 *              Extracted from monolithic PlatformSettingsController.
 *
 * Changelog:
 * 2.0.0 - 2025-01-09 (TODO-1203)
 * - BREAKING: Extracted from PlatformSettingsController
 * - Now extends AbstractSettingsController
 * - Single responsibility: Session settings only
 * 1.0.0 - 2025-10-19
 * - Was part of PlatformSettingsController
 */

namespace WPAppCore\Controllers\Settings;

use WPAppCore\Controllers\Abstract\AbstractSettingsController;
use WPAppCore\Models\Abstract\AbstractSettingsModel;
use WPAppCore\Models\Settings\SecuritySessionModel;
use WPAppCore\Validators\Abstract\AbstractSettingsValidator;
use WPAppCore\Validators\Settings\SecuritySessionValidator;

class SecuritySessionController extends AbstractSettingsController {

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
        return new SecuritySessionModel();
    }

    protected function getValidator(): AbstractSettingsValidator {
        return new SecuritySessionValidator();
    }

    protected function getControllerSlug(): string {
        return 'security-session';
    }

    /**
     * Register notification messages via hook
     */
    public function init(): void {
        parent::init();
        add_filter('wpapp_settings_notification_messages', [$this, 'registerNotificationMessages']);
    }

    /**
     * Register notification messages for this controller
     */
    public function registerNotificationMessages(array $messages): array {
        $messages['save_messages']['security-session'] = __('Security session settings have been saved successfully.', 'wp-app-core');
        $messages['reset_messages']['security-session'] = __('Security session settings have been reset to default values successfully.', 'wp-app-core');
        return $messages;
    }
}
