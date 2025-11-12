<?php
/**
 * Platform General Settings Controller
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Settings
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Settings/PlatformGeneralSettingsController.php
 *
 * Description: Controller untuk general platform settings.
 *              REFACTORED: Now extends AbstractSettingsController.
 *              Extracted from monolithic PlatformSettingsController.
 *
 * Changelog:
 * 2.0.0 - 2025-01-09 (TODO-1203)
 * - BREAKING: Extracted from PlatformSettingsController
 * - Now extends AbstractSettingsController
 * - Single responsibility: General platform settings only
 * 1.0.0 - 2025-10-19
 * - Was part of PlatformSettingsController
 */

namespace WPAppCore\Controllers\Settings;

use WPAppCore\Controllers\Abstract\AbstractSettingsController;
use WPAppCore\Models\Abstract\AbstractSettingsModel;
use WPAppCore\Models\Settings\PlatformSettingsModel;
use WPAppCore\Validators\Abstract\AbstractSettingsValidator;
use WPAppCore\Validators\Settings\PlatformSettingsValidator;

class PlatformGeneralSettingsController extends AbstractSettingsController {

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
        return new PlatformSettingsModel();
    }

    protected function getValidator(): AbstractSettingsValidator {
        return new PlatformSettingsValidator();
    }

    protected function getControllerSlug(): string {
        return 'general';
    }

    /**
     * Register notification messages via hook
     *
     * ABSTRACT PATTERN: Each controller registers their own messages
     */
    public function init(): void {
        parent::init();

        // Register notification messages
        add_filter('wpapp_settings_notification_messages', [$this, 'registerNotificationMessages']);
    }

    /**
     * Register notification messages for this controller
     *
     * @param array $messages Existing messages from other controllers
     * @return array Modified messages with this controller's messages added
     */
    public function registerNotificationMessages(array $messages): array {
        // Save message - SEPARATED from reset
        $messages['save_messages']['general'] = __('General settings have been saved successfully.', 'wp-app-core');

        // Reset message - SEPARATED from save
        $messages['reset_messages']['general'] = __('General settings have been reset to default values successfully.', 'wp-app-core');

        return $messages;
    }
}
