<?php
/**
 * Platform Permission Management Tab Template
 *
 * @package     WP_App_Core
 * @subpackage  Views/Templates/Settings
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/templates/settings/tab-permissions.php
 *
 * Description: Template untuk mengelola platform permissions.
 *              REFACTORED: Now uses shared permission-matrix.php template.
 *              Controller handles all logic, template just displays.
 *
 * Changelog:
 * 2.0.0 - 2025-01-12 (TODO-1206)
 * - BREAKING: Complete refactor to use AbstractPermissionsController
 * - Reduced from 305 lines to ~40 lines (87% reduction)
 * - Removed all form handling logic (now via AJAX in controller)
 * - Removed inline role creation (should be in activation)
 * - Uses shared permission-matrix.php template
 * - All data from controller->getViewModel()
 * 1.0.0 - 2025-10-19
 * - Initial creation with form POST handling
 */

if (!defined('ABSPATH')) {
    die;
}

// Get controller instance (RoleManager loaded in constructor)
$permissions_controller = new \WPAppCore\Controllers\Settings\PlatformPermissionsController();

// Get view data from controller
$view_data = $permissions_controller->getViewModel();

// Extract variables for template
extract($view_data);

// Load shared permission matrix template from wp-app-core
require_once WP_APP_CORE_PLUGIN_DIR . 'src/Views/templates/permissions/permission-matrix.php';
