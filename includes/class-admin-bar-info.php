<?php
/**
 * Admin Bar Info Class
 *
 * @package     WP_App_Core
 * @subpackage  Includes
 * @version     1.3.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/includes/class-admin-bar-info.php
 *
 * Description: Display user information in WordPress admin bar.
 *              Generic version that works with multiple plugins (customer, agency, etc).
 *              Shows entity name, branch/office, and roles for any registered plugin.
 *
 * Changelog:
 * 1.3.0 - 2025-01-18
 * - ENHANCEMENT: Display permissions from user_info['permission_names'] in Key Capabilities
 * - Reason: Was showing "No key capabilities found" because checking hardcoded customer caps
 * - Fixed: Prefer permission_names from user_info if available
 * - Fallback: Still checks hardcoded capabilities for backward compatibility
 * - Added: Debug logging for permissions section
 * - Benefits: Shows actual user permissions (e.g., "Lihat Daftar Agency", "Edit Division")
 *
 * 1.2.0 - 2025-01-18
 * - FIX: Detailed info dropdown now shows role_names instead of role slugs
 * - Reason: Was showing 'agency', 'agency_admin_unit' instead of 'Agency', 'Admin Unit'
 * - Fixed: Roles Section in get_detailed_info_html() now prefers user_info['role_names']
 * - Added: Debug logging for detailed info roles section
 * - Benefits: Consistent role display in both admin bar and dropdown
 *
 * 1.1.0 - 2025-01-18
 * - ENHANCEMENT: Prefer role_names from user_info array if available
 * - Reason: Plugins can now provide pre-computed role names (more efficient)
 * - Fallback: Still uses filter system if role_names not in user_info
 * - Benefits: No need for hardcoded filters, dynamic role handling
 * - Added: Debug logging for role names
 *
 * 1.0.0 - 2025-01-18
 * - Initial creation (migrated from wp-customer)
 * - Made generic to support multiple plugins
 * - Added filter system for extensibility
 * - Support for customer, agency, and other future plugins
 */

defined('ABSPATH') || exit;

class WP_App_Core_Admin_Bar_Info {

    /**
     * Registered plugins that can provide user info
     * @var array
     */
    private static $registered_plugins = [];

    /**
     * Initialize the admin bar info display
     */
    public static function init() {
        // Only add for logged in users
        if (!is_user_logged_in()) {
            return;
        }

        // Allow plugins to register themselves
        do_action('wp_app_core_register_admin_bar_plugins');

        // Check if user has any registered role
        $user = wp_get_current_user();
        $should_display = false;

        foreach (self::$registered_plugins as $plugin) {
            if (isset($plugin['roles']) && is_array($plugin['roles'])) {
                foreach ($plugin['roles'] as $role_slug) {
                    if (in_array($role_slug, (array) $user->roles)) {
                        $should_display = true;
                        break 2;
                    }
                }
            }
        }

        // Allow manual override via filter
        $should_display = apply_filters('wp_app_core_should_display_admin_bar', $should_display, $user);

        // If user has any registered role, add admin bar info
        if ($should_display) {
            add_action('admin_bar_menu', [__CLASS__, 'add_admin_bar_info'], 100);
        }
    }

    /**
     * Register a plugin to provide admin bar info
     *
     * @param string $plugin_id Unique plugin identifier (e.g., 'customer', 'agency')
     * @param array $args {
     *     @type array $roles Array of role slugs managed by this plugin
     *     @type callable $get_user_info Callback to get user info array
     * }
     */
    public static function register_plugin($plugin_id, $args) {
        self::$registered_plugins[$plugin_id] = wp_parse_args($args, [
            'roles' => [],
            'get_user_info' => null,
        ]);
    }

    /**
     * Add user info to admin bar
     *
     * @param WP_Admin_Bar $wp_admin_bar
     */
    public static function add_admin_bar_info($wp_admin_bar) {
        $user = wp_get_current_user();
        $user_id = $user->ID;

        // Get user information from registered plugins
        $user_info = self::get_user_info($user_id);

        if (!$user_info) {
            return;
        }

        // Build display text
        $entity_text = isset($user_info['entity_name']) ? esc_html($user_info['entity_name']) : 'No Entity';
        $entity_icon = isset($user_info['icon']) ? $user_info['icon'] : '🏢';

        // Get user roles - prefer role_names from user_info if available
        $role_names = isset($user_info['role_names']) && is_array($user_info['role_names']) && !empty($user_info['role_names'])
            ? $user_info['role_names']
            : self::get_user_role_names($user);
        $roles_text = !empty($role_names) ? implode(', ', $role_names) : 'No Roles';

        // DEBUG: Log role names used
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("=== Admin Bar Role Names ===");
            error_log("role_names from user_info: " . print_r($user_info['role_names'] ?? 'NOT SET', true));
            error_log("Final role_names used: " . print_r($role_names, true));
            error_log("Roles text displayed: " . $roles_text);
        }

        // Build the display HTML
        $info_html = '<span class="wp-app-core-admin-bar-info">';

        // Entity Info
        $info_html .= '<span class="wp-app-core-entity-info">';
        $info_html .= $entity_icon . ' ' . $entity_text;
        $info_html .= '</span>';

        $info_html .= '<span class="wp-app-core-separator"> | </span>';

        // Roles Info
        $info_html .= '<span class="wp-app-core-roles-info">';
        $info_html .= '👤 ' . $roles_text;
        $info_html .= '</span>';

        $info_html .= '</span>';

        // DEBUG: Log user_info fields being used for display
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("=== Admin Bar Display Fields ===");
            error_log("entity_name: " . ($user_info['entity_name'] ?? 'NOT SET'));
            error_log("entity_code: " . ($user_info['entity_code'] ?? 'NOT SET'));
            error_log("branch_name: " . ($user_info['branch_name'] ?? 'NOT SET'));
            error_log("branch_type: " . ($user_info['branch_type'] ?? 'NOT SET'));
            error_log("division_name: " . ($user_info['division_name'] ?? 'NOT SET'));
            error_log("division_type: " . ($user_info['division_type'] ?? 'NOT SET'));
            error_log("relation_type: " . ($user_info['relation_type'] ?? 'NOT SET'));
            error_log("position: " . ($user_info['position'] ?? 'NOT SET'));
            error_log("icon: " . ($user_info['icon'] ?? 'NOT SET'));
        }

        // Add to admin bar (parent: top-secondary for right side)
        $wp_admin_bar->add_node([
            'id'    => 'wp-app-core-user-info',
            'parent' => 'top-secondary',
            'title' => $info_html,
            'meta'  => [
                'class' => 'wp-app-core-admin-bar-item',
                'title' => 'User Information'
            ]
        ]);

        // Add submenu with detailed info
        $detailed_html = self::get_detailed_info_html($user_id, $user_info);

        // DEBUG: Log the HTML that will be added to dropdown
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("=== Admin Bar Dropdown HTML ===");
            error_log("HTML Length: " . strlen($detailed_html));
            error_log("HTML Preview (first 500 chars): " . substr($detailed_html, 0, 500));
        }

        // Add as submenu item
        // IMPORTANT: href is required for WordPress to render HTML in dropdown
        // Use javascript:void(0) to prevent navigation
        $wp_admin_bar->add_node([
            'parent' => 'wp-app-core-user-info',
            'id'     => 'wp-app-core-user-details',
            'title'  => $detailed_html,
            'href'   => 'javascript:void(0);',
            'meta'   => [
                'class' => 'wp-app-core-user-details',
                'tabindex' => '-1'
            ]
        ]);
    }

    /**
     * Get user information from registered plugins
     *
     * @param int $user_id
     * @return array|null
     */
    private static function get_user_info($user_id) {
        $user_info = null;

        // DEBUG: Log registered plugins
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("=== WP_App_Core get_user_info START for user_id: {$user_id} ===");
            error_log("Registered Plugins: " . print_r(array_keys(self::$registered_plugins), true));
        }

        // Try each registered plugin until we find user info
        foreach (self::$registered_plugins as $plugin_id => $plugin) {
            // DEBUG: Log plugin being checked
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Checking plugin: {$plugin_id}");
            }

            if (is_callable($plugin['get_user_info'])) {
                $info = call_user_func($plugin['get_user_info'], $user_id);

                // DEBUG: Log result from plugin
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Plugin '{$plugin_id}' returned: " . print_r($info, true));
                }

                if ($info && is_array($info)) {
                    $user_info = $info;
                    $user_info['plugin_id'] = $plugin_id;

                    // DEBUG: Log found info
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("INFO FOUND from plugin '{$plugin_id}' - breaking loop");
                    }
                    break;
                }
            }
        }

        // DEBUG: Log final result
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("=== WP_App_Core get_user_info END - Final Result: " . print_r($user_info, true) . " ===");
        }

        // Allow filtering of user info
        return apply_filters('wp_app_core_admin_bar_user_info', $user_info, $user_id);
    }

    /**
     * Get user role display names
     *
     * @param WP_User $user
     * @return array
     */
    private static function get_user_role_names($user) {
        $role_names = [];

        foreach ((array) $user->roles as $role_slug) {
            // Try to get from registered plugins first
            $role_name = null;
            foreach (self::$registered_plugins as $plugin) {
                $role_name = apply_filters("wp_app_core_role_name_{$role_slug}", null);
                if ($role_name) {
                    break;
                }
            }

            // Fallback to WordPress role name
            if (!$role_name) {
                $wp_roles = wp_roles();
                $role_name = isset($wp_roles->role_names[$role_slug])
                    ? translate_user_role($wp_roles->role_names[$role_slug])
                    : $role_slug;
            }

            $role_names[] = $role_name;
        }

        return $role_names;
    }

    /**
     * Get detailed info HTML for dropdown
     *
     * @param int $user_id
     * @param array|null $user_info
     * @return string
     */
    private static function get_detailed_info_html($user_id, $user_info) {
        $user = get_user_by('ID', $user_id);

        // DEBUG: Log detailed info generation
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("=== Generating Detailed Info HTML ===");
            error_log("All available fields: " . print_r(array_keys($user_info ?? []), true));
        }

        // Use output buffering to capture template
        ob_start();

        // Include template file - use consistent path structure
        $template_path = dirname(dirname(__FILE__)) . '/src/Views/templates/admin-bar/dropdown.php';

        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Fallback if template not found
            echo '<div>Template not found at: ' . esc_html($template_path) . '</div>';

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("ERROR: Admin bar template not found at: {$template_path}");
            }
        }

        $html = ob_get_clean();

        // DEBUG: Log roles section data (moved from template for logging)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("=== Detailed Info - Roles Section ===");
            error_log("user_info['role_names']: " . print_r($user_info['role_names'] ?? 'NOT SET', true));
            error_log("user->roles (slugs): " . print_r($user->roles, true));
            error_log("=== Detailed Info - Permissions Section ===");
            error_log("user_info['permission_names']: " . print_r($user_info['permission_names'] ?? 'NOT SET', true));
        }

        return $html;
    }
}
