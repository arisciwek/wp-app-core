<?php
/**
 * Platform Permissions Controller
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Settings
 * @version     3.1.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Settings/PlatformPermissionsController.php
 *
 * Description: Controller untuk platform permission management.
 *              REFACTORED: Now extends AbstractPermissionsController.
 *              Minimal implementation - just define plugin-specific details.
 *
 * Changelog:
 * 3.1.0 - 2025-01-12 (TODO-1206)
 * - Added customizeFooterForPermissionsTab() using wpapp_settings_footer_content hook
 * - Shows "Changes are saved automatically" info message instead of buttons
 * - Constructor now loads RoleManager and calls parent::__construct()
 * - Footer customized via PHP (not JavaScript) for cleaner approach
 * 3.0.0 - 2025-01-12 (TODO-1206)
 * - BREAKING: Now extends AbstractPermissionsController
 * - Reduced from 160 lines to ~80 lines (50% reduction)
 * - AJAX handlers auto-registered by abstract
 * - Server-side validation included by abstract
 * - All logic moved to abstract classes
 * 2.0.0 - 2025-01-09 (TODO-1203)
 * - BREAKING: Extracted from PlatformSettingsController
 * - Standalone controller for permission management
 * 1.0.0 - 2025-10-19
 * - Was part of PlatformSettingsController
 */

namespace WPAppCore\Controllers\Settings;

use WPAppCore\Controllers\Abstract\AbstractPermissionsController;
use WPAppCore\Models\Abstract\AbstractPermissionsModel;
use WPAppCore\Models\Settings\PlatformPermissionModel;
use WPAppCore\Validators\Abstract\AbstractPermissionsValidator;
use WPAppCore\Validators\Settings\PlatformPermissionValidator;

class PlatformPermissionsController extends AbstractPermissionsController {

    /**
     * Constructor
     * Ensures RoleManager is loaded before any operations
     */
    public function __construct() {
        // Load RoleManager class (required by controller, model, and validator)
        require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-role-manager.php';

        // Call parent constructor to initialize model and validator
        parent::__construct();
    }

    /**
     * Get plugin slug
     */
    protected function getPluginSlug(): string {
        return 'wp-app-core';
    }

    /**
     * Get plugin prefix for AJAX actions
     */
    protected function getPluginPrefix(): string {
        return 'wpapp';
    }

    /**
     * Get role manager class name
     */
    protected function getRoleManagerClass(): string {
        return 'WP_App_Core_Role_Manager';
    }

    /**
     * Get model instance
     */
    protected function getModel(): AbstractPermissionsModel {
        return new PlatformPermissionModel();
    }

    /**
     * Get validator instance
     */
    protected function getValidator(): AbstractPermissionsValidator {
        return new PlatformPermissionValidator();
    }

    /**
     * Initialize controller
     * Registers AJAX handlers AND asset enqueuing
     */
    public function init(): void {
        // Register AJAX handlers via parent
        parent::init();

        // Register asset enqueuing for permissions tab
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);

        // Customize footer for permissions tab (show info message instead of buttons)
        add_filter('wpapp_settings_footer_content', [$this, 'customizeFooterForPermissionsTab'], 10, 3);
    }

    /**
     * Customize footer content for permissions tab
     * Shows "Changes are saved automatically" message instead of Save/Reset buttons
     *
     * @param string $footer_html Default footer HTML
     * @param string $tab Current tab
     * @param array $config Current tab config
     * @return string Custom footer HTML
     */
    public function customizeFooterForPermissionsTab(string $footer_html, string $tab, array $config): string {
        if ($tab === 'permissions') {
            return '<div class="notice notice-info inline" style="margin: 0;">' .
                   '<p style="margin: 0.5em 0;">' .
                   '<span class="dashicons dashicons-info" style="color: #2271b1;"></span> ' .
                   '<strong>' . __('Changes are saved automatically', 'wp-app-core') . '</strong> ' .
                   __('— Each permission change is saved instantly via AJAX.', 'wp-app-core') .
                   '</p>' .
                   '</div>';
        }
        return $footer_html;
    }

    /**
     * Enqueue assets for permissions tab
     * Only loads on correct page and tab
     */
    public function enqueueAssets(string $hook): void {
        // Only on wp-app-core settings page
        if ($hook !== 'toplevel_page_wp-app-core-settings') {
            return;
        }

        // Only on permissions tab
        $tab = $_GET['tab'] ?? '';
        if ($tab !== 'permissions') {
            return;
        }

        // Call parent to load shared assets
        parent::enqueueAssets($hook);
    }

    /**
     * Get page title for permission matrix
     */
    protected function getPageTitle(): string {
        return __('Platform Permission Management', 'wp-app-core');
    }

    /**
     * Get page description for permission matrix
     */
    protected function getPageDescription(): string {
        return __('Configure role capabilities for platform staff. Changes take effect immediately.', 'wp-app-core');
    }
}
