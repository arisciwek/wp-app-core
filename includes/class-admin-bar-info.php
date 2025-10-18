<?php
/**
 * Admin Bar Info Class
 *
 * @package     WP_App_Core
 * @subpackage  Includes
 * @version     2.0.1
 * @author      arisciwek
 *
 * Path: /wp-app-core/includes/class-admin-bar-info.php
 *
 * Description: Display user information in WordPress admin bar.
 *              SIMPLIFIED VERSION - wp-app-core handles ALL WordPress queries.
 *              Plugins only provide entity-specific data via simple filter.
 *
 * Architecture:
 * - wp-app-core: Queries WordPress (users, roles, permissions), renders admin bar
 * - Plugins: Provide entity data (company name, branch, etc) via filter
 *
 * Changelog:
 * 2.0.1 - 2025-01-18
 * - FIX: Admin bar now displays for users using new filter approach (v2.0)
 * - ENHANCEMENT: init() now checks BOTH old registration AND new filter for display
 * - BEHAVIOR: Plugins using simplified filter integration no longer need to register
 *
 * 2.0.0 - 2025-01-18 (MAJOR SIMPLIFICATION)
 * - BREAKING: Simplified architecture - wp-app-core handles ALL WordPress queries
 * - ADDED: Direct WordPress user/role/permission queries in get_user_info()
 * - ADDED: New filter 'wp_app_core_user_entity_data' for plugins to provide entity data
 * - ADDED: Helper methods for role/permission display names
 * - REMOVED: Plugin registration requirement (optional now for backward compat)
 * - BENEFIT: Plugins only need ONE filter instead of integration classes
 * - BENEFIT: 97% less code required in plugins (40 lines vs 1300)
 * - BACKWARD COMPATIBLE: Old registration system still works
 *
 * 1.3.0 - 2025-01-18
 * - ENHANCEMENT: Display permissions from user_info['permission_names'] in Key Capabilities
 *
 * 1.2.0 - 2025-01-18
 * - FIX: Detailed info dropdown now shows role_names instead of role slugs
 *
 * 1.1.0 - 2025-01-18
 * - ENHANCEMENT: Prefer role_names from user_info array if available
 *
 * 1.0.0 - 2025-01-18
 * - Initial creation (migrated from wp-customer)
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

        // Allow plugins to register themselves (backward compatibility for v1.x approach)
        do_action('wp_app_core_register_admin_bar_plugins');

        // v2.0: Check if user has registered role OR if filter can provide data
        $user = wp_get_current_user();
        $should_display = false;

        // OLD approach (v1.x): Check registered plugins
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

        // NEW approach (v2.0): Check if filter can provide entity data
        // This allows plugins to integrate without registration
        if (!$should_display) {
            $test_entity_data = apply_filters('wp_app_core_user_entity_data', null, $user->ID, $user);
            if ($test_entity_data !== null) {
                $should_display = true;
            }
        }

        // Allow manual override via filter
        $should_display = apply_filters('wp_app_core_should_display_admin_bar', $should_display, $user);

        // If user has data available (via registration OR filter), add admin bar info
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
     * Get complete user information for admin bar (SIMPLIFIED VERSION)
     *
     * This is the NEW simplified approach:
     * 1. wp-app-core queries ALL WordPress data (user, roles, permissions)
     * 2. wp-app-core applies filter for entity-specific data from plugins
     * 3. wp-app-core merges and returns complete user info
     *
     * Plugins only need to add ONE filter to provide entity data!
     *
     * @param int $user_id WordPress user ID
     * @return array|null Complete user info or null if no entity data
     */
    private static function get_user_info($user_id) {
        // DEBUG: Start
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("=== WP_App_Core get_user_info START (v2.0 SIMPLIFIED) for user_id: {$user_id} ===");
        }

        // Check cache first
        $cache_key = 'wp_app_core_user_info_' . $user_id;
        $cached = wp_cache_get($cache_key, 'wp_app_core');

        if ($cached !== false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Cache HIT for user_id: {$user_id}");
            }
            return $cached;
        }

        // 1. Get WordPress user object
        $user = get_userdata($user_id);
        if (!$user) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("User not found for user_id: {$user_id}");
            }
            return null;
        }

        // 2. Get user roles (slugs)
        $user_roles = (array) $user->roles;

        // 3. Get user permissions (all capabilities)
        $user_permissions = array_keys((array) $user->allcaps);

        // DEBUG: Log WordPress data
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("WordPress data retrieved:");
            error_log("  - User roles: " . print_r($user_roles, true));
            error_log("  - User permissions count: " . count($user_permissions));
        }

        // 4. Try NEW simplified filter first (for plugins using new approach)
        $entity_data = apply_filters('wp_app_core_user_entity_data', null, $user_id, $user);

        // DEBUG: Log entity data from filter
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Entity data from filter 'wp_app_core_user_entity_data': " . print_r($entity_data, true));
        }

        // 5. Fallback to OLD registration system if no entity data from filter (backward compat)
        if (!$entity_data && !empty(self::$registered_plugins)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("No entity data from new filter, trying OLD registration system...");
                error_log("Registered plugins: " . print_r(array_keys(self::$registered_plugins), true));
            }

            $entity_data = self::get_user_info_legacy($user_id);
        }

        // If no entity data from either method, return null
        if (!$entity_data) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("No entity data found (tried both new filter and old registration)");
            }
            // Cache null for short time to prevent repeated queries
            wp_cache_set($cache_key, null, 'wp_app_core', 60);
            return null;
        }

        // 6. Build complete user info by merging WordPress data + entity data
        $user_info = array_merge([
            // WordPress data
            'user_id' => $user_id,
            'user_email' => $user->user_email,
            'user_login' => $user->user_login,
            'display_name' => $user->display_name,
            'roles' => $user_roles,
            'permissions' => $user_permissions,
        ], $entity_data);

        // 7. Get role display names
        $user_info['role_names'] = self::get_role_display_names($user_roles);

        // 8. Get permission display names (filter out role slugs)
        $user_info['permission_names'] = self::get_permission_display_names($user_permissions, $user_roles);

        // DEBUG: Log final merged info
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Complete user info:");
            error_log("  - Entity name: " . ($user_info['entity_name'] ?? 'NOT SET'));
            error_log("  - Role names: " . print_r($user_info['role_names'], true));
            error_log("  - Permission count: " . count($user_info['permission_names']));
        }

        // 9. Cache for 5 minutes
        wp_cache_set($cache_key, $user_info, 'wp_app_core', 300);

        // 10. Allow final filtering
        $user_info = apply_filters('wp_app_core_admin_bar_user_info', $user_info, $user_id);

        // DEBUG: End
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("=== WP_App_Core get_user_info END ===");
        }

        return $user_info;
    }

    /**
     * Get user info using OLD registration system (backward compatibility)
     *
     * @param int $user_id
     * @return array|null
     */
    private static function get_user_info_legacy($user_id) {
        // Try each registered plugin until we find user info
        foreach (self::$registered_plugins as $plugin_id => $plugin) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Checking OLD registered plugin: {$plugin_id}");
            }

            if (is_callable($plugin['get_user_info'])) {
                $info = call_user_func($plugin['get_user_info'], $user_id);

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Plugin '{$plugin_id}' returned: " . print_r($info, true));
                }

                if ($info && is_array($info)) {
                    $info['plugin_id'] = $plugin_id;

                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("INFO FOUND from OLD plugin '{$plugin_id}'");
                    }
                    return $info;
                }
            }
        }

        return null;
    }

    /**
     * Get role display names from role slugs
     *
     * Tries multiple sources in order:
     * 1. Custom filter (for plugin-specific roles)
     * 2. WordPress role object (for WP built-in roles)
     * 3. Humanized slug as fallback
     *
     * @param array $role_slugs Array of role slugs
     * @return array Array of role display names
     */
    private static function get_role_display_names($role_slugs) {
        $role_names = [];
        $wp_roles = wp_roles();

        foreach ($role_slugs as $slug) {
            // Try custom filter first (for plugin-specific role names)
            $name = apply_filters('wp_app_core_role_display_name', null, $slug);

            // Fallback to WordPress role name
            if (!$name && isset($wp_roles->role_names[$slug])) {
                $name = translate_user_role($wp_roles->role_names[$slug]);
            }

            // Last resort: Humanize the slug
            if (!$name) {
                $name = ucwords(str_replace('_', ' ', $slug));
            }

            $role_names[] = $name;
        }

        return $role_names;
    }

    /**
     * Get permission display names from capability keys
     *
     * Filters out role slugs and WordPress core capabilities, showing only
     * custom permissions with friendly names.
     *
     * @param array $capabilities Array of capability keys
     * @param array $role_slugs Array of role slugs to filter out
     * @return array Array of permission display names
     */
    private static function get_permission_display_names($capabilities, $role_slugs = []) {
        $permission_names = [];
        $wp_roles = wp_roles();
        $all_role_slugs = array_keys($wp_roles->roles);

        // Merge with provided role slugs
        $all_role_slugs = array_merge($all_role_slugs, $role_slugs);

        foreach ($capabilities as $cap) {
            // Skip if it's a role slug
            if (in_array($cap, $all_role_slugs)) {
                continue;
            }

            // Skip WordPress core capabilities (read, edit_posts, etc)
            // These are usually inherited from roles
            if (in_array($cap, ['read', 'level_0', 'level_1', 'level_2'])) {
                continue;
            }

            // Try custom filter first (for plugin-specific permission names)
            $name = apply_filters('wp_app_core_permission_display_name', null, $cap);

            // Fallback to humanized capability key
            if (!$name) {
                $name = ucwords(str_replace('_', ' ', $cap));
            }

            $permission_names[] = $name;
        }

        return $permission_names;
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

    /**
     * Invalidate user info cache
     *
     * Plugins should call this when user entity data changes:
     * do_action('wp_app_core_invalidate_user_cache', $user_id);
     *
     * @param int $user_id WordPress user ID
     */
    public static function invalidate_user_cache($user_id) {
        $cache_key = 'wp_app_core_user_info_' . $user_id;
        wp_cache_delete($cache_key, 'wp_app_core');

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("WP_App_Core: Invalidated user cache for user_id: {$user_id}");
        }
    }
}

// Register cache invalidation action hook
add_action('wp_app_core_invalidate_user_cache', ['WP_App_Core_Admin_Bar_Info', 'invalidate_user_cache']);
