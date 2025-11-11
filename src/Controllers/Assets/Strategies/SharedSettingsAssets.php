<?php
/**
 * Shared Settings Assets Strategy
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Assets/Strategies
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Assets/Strategies/SharedSettingsAssets.php
 *
 * Description: Provides SHARED/REUSABLE assets untuk settings tabs.
 *              Plugin lain (wp-customer, wp-agency, dll) dapat menggunakan
 *              assets yang sama tanpa duplikasi.
 *
 * Design Philosophy:
 * - wp-app-core provides base/shared assets
 * - Other plugins can use these assets
 * - Plugin-specific overrides possible
 * - DRY principle - no duplication
 *
 * Usage from Other Plugins:
 * ```php
 * // In wp-customer SettingsPageAssets strategy
 * wp_enqueue_style(
 *     'wpc-security-policy',
 *     WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/shared/security-policy-tab-style.css',
 *     [],
 *     WP_APP_CORE_VERSION
 * );
 * ```
 *
 * Shared Tab Assets Available:
 * - general-tab (company info, regional settings)
 * - security-policy-tab (security policies, audit)
 * - security-session-tab (session management, login protection)
 * - security-authentication-tab (2FA, IP whitelist, password policy)
 * - email-tab (SMTP, notification settings)
 * - demo-data-tab (demo data generation & management)
 * - permissions-tab (role & capability management)
 *
 * Changelog:
 * 1.0.0 - 2025-01-09
 * - Initial implementation
 * - Shared assets architecture
 */

namespace WPAppCore\Controllers\Assets\Strategies;

use WPAppCore\Controllers\Assets\AssetStrategyInterface;

defined('ABSPATH') || exit;

class SharedSettingsAssets implements AssetStrategyInterface {

    /**
     * Never auto-load - this is for other plugins to explicitly use
     *
     * @return bool
     */
    public function should_load(): bool {
        return false; // Shared assets loaded explicitly by requesting plugins
    }

    /**
     * Not used - shared assets loaded by other plugins
     *
     * @return void
     */
    public function enqueue_styles(): void {
        // Intentionally empty - shared assets loaded explicitly
    }

    /**
     * Not used - shared assets loaded by other plugins
     *
     * @return void
     */
    public function enqueue_scripts(): void {
        // Intentionally empty - shared assets loaded explicitly
    }

    /**
     * Get strategy name
     *
     * @return string
     */
    public function get_strategy_name(): string {
        return 'shared_settings';
    }

    /**
     * Get available shared tab styles
     *
     * Helper method untuk plugin lain
     *
     * @return array Map of tab slug => CSS filename
     */
    public static function get_shared_styles(): array {
        return [
            'general' => 'general-tab-style.css',
            'security-policy' => 'security-policy-tab-style.css',
            'security-session' => 'security-session-tab-style.css',
            'security-authentication' => 'security-authentication-tab-style.css',
            'email' => 'email-tab-style.css',
            'demo-data' => 'demo-data-tab-style.css',
            'permissions' => 'permissions-tab-style.css',
        ];
    }

    /**
     * Get available shared tab scripts
     *
     * Helper method untuk plugin lain
     *
     * @return array Map of tab slug => JS filename
     */
    public static function get_shared_scripts(): array {
        return [
            'general' => 'general-tab-script.js',
            'security-policy' => 'security-policy-tab-script.js',
            'security-session' => 'security-session-tab-script.js',
            'security-authentication' => 'security-authentication-tab-script.js',
            'email' => 'email-tab-script.js',
        ];
    }

    /**
     * Enqueue shared tab style
     *
     * Static helper untuk plugin lain
     *
     * @param string $tab Tab slug
     * @param string $handle CSS handle
     * @param array $deps Dependencies
     * @return bool True if enqueued
     */
    public static function enqueue_shared_tab_style(string $tab, string $handle, array $deps = []): bool {
        $styles = self::get_shared_styles();

        if (!isset($styles[$tab])) {
            return false;
        }

        $file_path = WP_APP_CORE_PLUGIN_DIR . 'assets/css/settings/' . $styles[$tab];

        if (!file_exists($file_path)) {
            return false;
        }

        wp_enqueue_style(
            $handle,
            WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/' . $styles[$tab],
            $deps,
            WP_APP_CORE_VERSION
        );

        return true;
    }

    /**
     * Enqueue shared tab script
     *
     * Static helper untuk plugin lain
     *
     * @param string $tab Tab slug
     * @param string $handle JS handle
     * @param array $deps Dependencies
     * @param array $localize_data Optional localization data
     * @return bool True if enqueued
     */
    public static function enqueue_shared_tab_script(
        string $tab,
        string $handle,
        array $deps = ['jquery'],
        array $localize_data = []
    ): bool {
        $scripts = self::get_shared_scripts();

        if (!isset($scripts[$tab])) {
            return false;
        }

        $file_path = WP_APP_CORE_PLUGIN_DIR . 'assets/js/settings/' . $scripts[$tab];

        if (!file_exists($file_path)) {
            return false;
        }

        wp_enqueue_script(
            $handle,
            WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/' . $scripts[$tab],
            $deps,
            WP_APP_CORE_VERSION,
            true
        );

        // Optional localization
        if (!empty($localize_data)) {
            wp_localize_script($handle, $localize_data['object_name'], $localize_data['data']);
        }

        return true;
    }

    /**
     * Check if shared asset exists for tab
     *
     * @param string $tab Tab slug
     * @param string $type 'style' or 'script'
     * @return bool
     */
    public static function has_shared_asset(string $tab, string $type = 'style'): bool {
        if ($type === 'style') {
            $assets = self::get_shared_styles();
        } else {
            $assets = self::get_shared_scripts();
        }

        return isset($assets[$tab]);
    }
}
