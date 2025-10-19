<?php
/**
 * Plugin Name: WP App Core
 * Plugin URI: https://example.com/wp-app-core
 * Description: Core plugin untuk mengelola fitur global aplikasi marketplace. Menyediakan user profile management, membership system, dan fitur shared lainnya.
 * Version: 1.0.2
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
 * 1.0.2 - 2025-10-19
 * - Implemented base role system (platform_staff) for wp-admin access
 * - All platform users now get dual roles: platform_staff + platform_xxx
 * - Updated upgrade script to add base role to existing users
 * - Fixed platform roles unable to access wp-admin (FINAL FIX)
 *
 * 1.0.1 - 2025-10-19
 * - Added 'read' capability to all platform roles for wp-admin access
 * - Added upgrade system for version migrations
 * - Fixed platform roles unable to access wp-admin (partial fix)
 *
 * 1.0.0 - 2025-01-18
 * - Initial release
 * - User profile management (migrated from wp-customer)
 * - Admin bar info display (migrated from wp-customer)
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('WP_APP_CORE_VERSION', '1.0.2');
define('WP_APP_CORE_PATH', plugin_dir_path(__FILE__));
define('WP_APP_CORE_PLUGIN_DIR', plugin_dir_path(__FILE__)); // Backward compatibility
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

        // Load upgrade handler
        require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-upgrade.php';

        // Load dependencies handler
        require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-dependencies.php';

        // Load WP Customer integration (TODO-1210)
        // DISABLED: Not needed anymore - using direct capability checks in wp-customer
        // require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-wp-customer-integration.php';

        // Explicitly load Cache manager (fallback for autoloader timing issues)
        if (!class_exists('WPAppCore\Cache\PlatformCacheManager')) {
            require_once WP_APP_CORE_PLUGIN_DIR . 'src/Cache/PlatformCacheManager.php';
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Check and run upgrades on plugins loaded
        add_action('plugins_loaded', ['WP_App_Core_Upgrade', 'check_and_upgrade'], 5);

        // Init hook
        add_action('plugins_loaded', [$this, 'init']);
        add_action('init', [$this, 'load_textdomain']);

        // Initialize dependencies handler
        new WP_App_Core_Dependencies('wp-app-core', WP_APP_CORE_VERSION);

        // Initialize WP Customer integration (TODO-1210)
        // Hook into wp_customer_access_type filter to set platform user access type
        add_filter('wp_customer_access_type', [$this, 'set_platform_access_type'], 10, 2);

        // Hook into wp_branch_access_type filter to set platform user access type for branches
        add_filter('wp_branch_access_type', [$this, 'set_platform_branch_access_type'], 10, 2);

        // Initialize admin bar info
        add_action('init', ['WP_App_Core_Admin_Bar_Info', 'init']);
    }

    /**
     * Set access_type for platform users
     *
     * @param string $access_type Current access type
     * @param array $context Context data (is_admin, user_id, etc)
     * @return string Modified access type
     */
    public function set_platform_access_type($access_type, $context) {
        // If already has access type, return it
        if ($access_type !== 'none') {
            return $access_type;
        }

        // Check if user has platform role with customer view capability
        if (current_user_can('view_customer_detail')) {
            $user_id = $context['user_id'] ?? get_current_user_id();
            $user = get_userdata($user_id);

            if ($user) {
                // Find platform role
                $platform_roles = array_filter($user->roles, function($role) {
                    return strpos($role, 'platform_') === 0;
                });

                if (!empty($platform_roles)) {
                    return 'platform'; // Set access_type to 'platform'
                }
            }
        }

        return $access_type;
    }

    /**
     * Set access_type for platform users (Branch context)
     *
     * @param string $access_type Current access type
     * @param array $context Context data (is_admin, user_id, etc)
     * @return string Modified access type
     */
    public function set_platform_branch_access_type($access_type, $context) {
        // If already has access type, return it
        if ($access_type !== 'none') {
            return $access_type;
        }

        // Check if user has platform role with branch view capability
        if (current_user_can('view_customer_branch_list')) {
            $user_id = $context['user_id'] ?? get_current_user_id();
            $user = get_userdata($user_id);

            if ($user) {
                // Find platform role
                $platform_roles = array_filter($user->roles, function($role) {
                    return strpos($role, 'platform_') === 0;
                });

                if (!empty($platform_roles)) {
                    return 'platform'; // Set access_type to 'platform'
                }
            }
        }

        return $access_type;
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

            // Initialize Platform Settings Controller
            $platform_settings = new \WPAppCore\Controllers\PlatformSettingsController();

            // Initialize Platform Staff Controller
            $platform_staff = new \WPAppCore\Controllers\Platform\PlatformStaffController();
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
