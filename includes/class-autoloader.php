<?php
/**
 * Autoloader Class
 *
 * @package     WP_App_Core
 * @subpackage  Includes
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/includes/class-autoloader.php
 *
 * Description: PSR-4 autoloader untuk WP App Core plugin.
 *              Mengatur autoloading untuk semua class di namespace WPAppCore.
 *
 * Changelog:
 * 1.0.0 - 2025-01-18
 * - Initial creation
 * - Support PSR-4 autoloading
 */

defined('ABSPATH') || exit;

/**
 * Autoloader class
 */
class WP_App_Core_Autoloader {

    /**
     * Register autoloader
     */
    public static function register() {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    /**
     * Autoload classes
     *
     * @param string $class_name
     */
    public static function autoload($class_name) {
        // Check if class belongs to our namespace
        if (strpos($class_name, 'WPAppCore\\') !== 0) {
            return;
        }

        // Remove namespace prefix
        $class_name = str_replace('WPAppCore\\', '', $class_name);

        // Convert namespace separators to directory separators
        $class_name = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);

        // Build file path
        $file_path = WP_APP_CORE_PLUGIN_DIR . 'src' . DIRECTORY_SEPARATOR . $class_name . '.php';

        // Load file if exists
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
}
