<?php
/**
 * Admin Bar Info Class
 *
 * @package     WP_App_Core
 * @subpackage  Includes
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/includes/class-admin-bar-info.php
 *
 * Description: Display user information in WordPress admin bar.
 *              Generic version that works with multiple plugins (customer, agency, etc).
 *              Shows entity name, branch/office, and roles for any registered plugin.
 *
 * Changelog:
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

        // Get user roles
        $role_names = self::get_user_role_names($user);
        $roles_text = !empty($role_names) ? implode(', ', $role_names) : 'No Roles';

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
        $wp_admin_bar->add_node([
            'parent' => 'wp-app-core-user-info',
            'id'     => 'wp-app-core-user-details',
            'title'  => self::get_detailed_info_html($user_id, $user_info),
            'meta'   => [
                'class' => 'wp-app-core-user-details'
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

        // Try each registered plugin until we find user info
        foreach (self::$registered_plugins as $plugin_id => $plugin) {
            if (is_callable($plugin['get_user_info'])) {
                $info = call_user_func($plugin['get_user_info'], $user_id);
                if ($info && is_array($info)) {
                    $user_info = $info;
                    $user_info['plugin_id'] = $plugin_id;
                    break;
                }
            }
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

        $html = '<div class="wp-app-core-detailed-info">';

        // User Info Section
        $html .= '<div class="info-section">';
        $html .= '<strong>User Information:</strong><br>';
        $html .= 'ID: ' . $user_id . '<br>';
        $html .= 'Username: ' . esc_html($user->user_login) . '<br>';
        $html .= 'Email: ' . esc_html($user->user_email) . '<br>';
        $html .= '</div>';

        // Entity Info Section
        if ($user_info) {
            $html .= '<div class="info-section">';
            $html .= '<strong>Entity Information:</strong><br>';

            if (isset($user_info['entity_name'])) {
                $html .= 'Entity: ' . esc_html($user_info['entity_name']) . '<br>';
            }
            if (isset($user_info['entity_code'])) {
                $html .= 'Code: ' . esc_html($user_info['entity_code']) . '<br>';
            }
            if (isset($user_info['branch_name'])) {
                $html .= 'Branch: ' . esc_html($user_info['branch_name']) . '<br>';
            }
            if (isset($user_info['branch_type'])) {
                $html .= 'Type: ' . ucfirst($user_info['branch_type']) . '<br>';
            }
            if (isset($user_info['relation_type'])) {
                $html .= 'Relation: ' . ucfirst(str_replace('_', ' ', $user_info['relation_type'])) . '<br>';
            }
            if (isset($user_info['position'])) {
                $html .= 'Position: ' . esc_html($user_info['position']) . '<br>';
            }
            if (isset($user_info['department'])) {
                $html .= 'Department: ' . esc_html($user_info['department']) . '<br>';
            }

            $html .= '</div>';
        }

        // Roles Section
        $html .= '<div class="info-section">';
        $html .= '<strong>Roles:</strong><br>';
        foreach ((array) $user->roles as $role) {
            $html .= '• ' . esc_html($role) . '<br>';
        }
        $html .= '</div>';

        // Capabilities Section
        $html .= '<div class="info-section">';
        $html .= '<strong>Key Capabilities:</strong><br>';

        // Get capabilities to display from filter
        $key_caps = apply_filters('wp_app_core_admin_bar_key_capabilities', [
            'view_customer_list',
            'view_customer_branch_list',
            'view_customer_employee_list',
            'edit_all_customers',
            'edit_own_customer',
            'manage_options'
        ]);

        $has_caps = false;
        foreach ($key_caps as $cap) {
            if (user_can($user_id, $cap)) {
                $html .= '✓ ' . $cap . '<br>';
                $has_caps = true;
            }
        }

        if (!$has_caps) {
            $html .= 'No key capabilities found<br>';
        }

        $html .= '</div>';

        $html .= '</div>';

        return apply_filters('wp_app_core_admin_bar_detailed_info_html', $html, $user_id, $user_info);
    }
}
