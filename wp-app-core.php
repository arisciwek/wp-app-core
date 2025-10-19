<?php
/**
 * Plugin Name: WP App Core
 * Plugin URI: https://example.com/wp-app-core
 * Description: Core plugin untuk mengelola fitur global aplikasi marketplace. Menyediakan user profile management, membership system, dan fitur shared lainnya.
 * Version: 1.0.0
 * Author: arisciwek
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-app-core
 * Domain Path: /languages
 *
 * @package WP_App_Core
 *
 * Path: /wp-app-core/wp-app-core.php
 *
 * Description: Main plugin file yang menginisialisasi semua komponen
 *              dan fitur dari WP App Core plugin.
 *
 * Changelog:
 * 1.0.0 - 2025-01-18
 * - Initial release
 * - User profile management (migrated from wp-customer)
 * - Admin bar info display (migrated from wp-customer)
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('WP_APP_CORE_VERSION', '1.0.0');
define('WP_APP_CORE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_APP_CORE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_APP_CORE_PLUGIN_FILE', __FILE__);

/**
 * Main plugin class
 */
class WP_App_Core {

    /**
     * Plugin instance
     * @var WP_App_Core
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return WP_App_Core
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Load autoloader
        require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-autoloader.php';
        WP_App_Core_Autoloader::register();

        // Load core classes
        require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-admin-bar-info.php';

        // Load dependencies handler
        require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-dependencies.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Init hook
        add_action('plugins_loaded', [$this, 'init']);
        add_action('init', [$this, 'load_textdomain']);

        // Initialize dependencies handler
        new WP_App_Core_Dependencies('wp-app-core', WP_APP_CORE_VERSION);

        // Initialize admin bar info
        add_action('init', ['WP_App_Core_Admin_Bar_Info', 'init']);
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Load activator class
        require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-activator.php';

        // Run activation
        WP_App_Core_Activator::activate();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Load deactivator class
        require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-deactivator.php';

        // Run deactivation
        WP_App_Core_Deactivator::deactivate();
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize Menu Manager
        if (is_admin()) {
            $menu_manager = new \WPAppCore\Controllers\MenuManager('wp-app-core', WP_APP_CORE_VERSION);
            $menu_manager->init();
        }

        // Initialize components here
        do_action('wp_app_core_init');
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wp-app-core',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
}

/**
 * Initialize plugin
 */
function wp_app_core() {
    return WP_App_Core::instance();
}

// Start the plugin
wp_app_core();
