<?php
/**
 * Settings Page Assets Strategy
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Assets/Strategies
 * @version     1.2.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Assets/Strategies/SettingsPageAssets.php
 *
 * Description: Asset loading strategy for Platform Settings pages.
 *              Loads tab-specific CSS and JS with proper localization.
 *              Separates platform-specific scripts from shared global scripts.
 *
 * Responsibilities:
 * - Detect if on wp-app-core settings page
 * - Load base settings CSS/JS
 * - Load platform-specific tab scripts from /settings/ directory
 * - Load shared tab scripts from /settings/ directory
 * - Provide wpAppCoreSettings localization
 *
 * Tab Assets Loaded (Platform-specific):
 * - general: platform-general-tab-script.js
 * - email: platform-email-tab-script.js
 * - security-authentication: platform-security-authentication-tab-script.js
 * - security-session: platform-security-session-tab-script.js
 * - security-policy: platform-security-policy-tab-script.js
 * - permissions: platform-permissions-tab-script.js
 * - error-logger: platform-error-logger.js
 *
 * Tab Assets Loaded (Shared):
 * - demo-data: wpapp-demo-data.js (can be reused by other plugins)
 *
 * Changelog:
 * 1.2.0 - 2025-11-14 (Task-1210)
 * - Moved platform-specific tab scripts back from /platform/ to /settings/
 * - Keeping platform- prefix for clarity
 * - Consolidated all scripts in /settings/ directory
 *
 * 1.1.0 - 2025-11-14
 * - Moved platform-specific tab scripts from /settings/ to /platform/
 * - Added platform- prefix to tab scripts
 * - Separated platform scripts from shared scripts
 * - Updated handle names: wpapp-settings-* → platform-settings-*
 * - Updated action hooks: wpapp_after_shared_tab_script → wpapp_after_platform_tab_script
 *
 * 1.0.0 - 2025-01-09
 * - Initial implementation
 * - Extracted from class-dependencies.php
 */

namespace WPAppCore\Controllers\Assets\Strategies;

use WPAppCore\Controllers\Assets\AssetStrategyInterface;

defined('ABSPATH') || exit;

class SettingsPageAssets implements AssetStrategyInterface {

    /**
     * Detect if we're on settings page
     *
     * @return bool
     */
    public function should_load(): bool {
        // get_current_screen() only exists in admin
        if (!\is_admin()) {
            return false;
        }

        $screen = \get_current_screen();
        if (!$screen) {
            return false;
        }

        // Load on Platform Settings page
        return strpos($screen->id, 'wp-app-core-settings') !== false;
    }

    /**
     * Enqueue settings page styles
     *
     * @return void
     */
    public function enqueue_styles(): void {
        $current_tab = $this->get_current_tab();

        // Base settings styles
        \wp_enqueue_style(
            'wpapp-settings-base',
            WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/wpapp-settings-style.css',
            [],
            WP_APP_CORE_VERSION
        );

        // Tab-specific styles
        $this->enqueue_tab_style($current_tab);
    }

    /**
     * Enqueue settings page scripts
     *
     * @return void
     */
    public function enqueue_scripts(): void {
        $current_tab = $this->get_current_tab();

        // Base settings script
        \wp_enqueue_script(
            'wpapp-settings-base',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/wpapp-settings-script.js',
            ['jquery'],
            WP_APP_CORE_VERSION,
            true
        );

        // PLATFORM: Error logger for debugging fast-disappearing errors (Platform-specific)
        \wp_enqueue_script(
            'platform-error-logger',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/platform-error-logger.js',
            [],
            \filemtime(WP_APP_CORE_PLUGIN_DIR . 'assets/js/settings/platform-error-logger.js'),
            false // Load in head to catch early errors
        );

        // Localize script with wpAppCoreSettings
        // This is what JavaScript expects!
        \wp_localize_script('wpapp-settings-base', 'wpAppCoreSettings', [
            'ajaxUrl' => \admin_url('admin-ajax.php'),
            'nonce' => \wp_create_nonce('wpapp_nonce'),
            'currentTab' => $current_tab,
            'i18n' => [
                'saving' => \__('Saving...', 'wp-app-core'),
                'saved' => \__('Settings saved successfully.', 'wp-app-core'),
                'error' => \__('Error saving settings.', 'wp-app-core'),
                'confirmReset' => \__('Are you sure you want to reset settings to defaults?', 'wp-app-core'),
            ]
        ]);

        // SHARED: Settings Reset Script - Global for ALL plugins
        // Handles Reset button with WPModal confirmation + Form POST (no AJAX)
        // Used by: wp-customer, wp-agency, wp-disnaker, etc.
        \wp_enqueue_script(
            'wpapp-settings-reset-script',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/wpapp-settings-reset-script.js',
            ['jquery', 'wp-modal'], // Depend on WPModal
            \filemtime(WP_APP_CORE_PLUGIN_DIR . 'assets/js/settings/wpapp-settings-reset-script.js'),
            true
        );

        // Tab-specific scripts
        $this->enqueue_tab_script($current_tab);
    }

    /**
     * Get current active tab
     *
     * @return string
     */
    private function get_current_tab(): string {
        return isset($_GET['tab']) ? \sanitize_key($_GET['tab']) : 'general';
    }

    /**
     * Enqueue tab-specific CSS
     *
     * Loads shared assets yang bisa digunakan plugin lain.
     * Plugin lain dapat reuse assets ini tanpa duplikasi.
     *
     * @param string $tab Tab slug
     * @return void
     */
    private function enqueue_tab_style(string $tab): void {
        $tab_styles = [
            'general' => 'general-tab-style.css',
            'email' => 'email-tab-style.css',
            'security-authentication' => 'security-authentication-tab-style.css',
            'security-session' => 'security-session-tab-style.css',
            'security-policy' => 'security-policy-tab-style.css',
            'permissions' => 'permissions-tab-style.css',
            'demo-data' => '../demo-data/wpapp-demo-data.css', // TODO-1207: Shared asset (renamed for global scope)
        ];

        if (isset($tab_styles[$tab])) {
            $file_path = WP_APP_CORE_PLUGIN_DIR . 'assets/css/settings/' . $tab_styles[$tab];

            if (\file_exists($file_path)) {
                \wp_enqueue_style(
                    'wpapp-settings-' . $tab,
                    WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/' . $tab_styles[$tab],
                    ['wpapp-settings-base'],
                    WP_APP_CORE_VERSION
                );

                /**
                 * Action: After shared tab style enqueued
                 * Allows plugins to add overrides or additional styles
                 *
                 * @param string $tab Current tab slug
                 * @param string $handle CSS handle that was enqueued
                 */
                \do_action('wpapp_after_shared_tab_style', $tab, 'wpapp-settings-' . $tab);
            }
        }
    }

    /**
     * Enqueue tab-specific JavaScript
     *
     * Platform-specific tab scripts (local scope).
     * Consolidated in /settings/ directory (Task-1210).
     *
     * @param string $tab Tab slug
     * @return void
     */
    private function enqueue_tab_script(string $tab): void {
        // Platform-specific tab scripts (not shared with other plugins)
        $platform_tab_scripts = [
            'general' => 'platform-general-tab-script.js',
            'email' => 'platform-email-tab-script.js',
            'security-authentication' => 'platform-security-authentication-tab-script.js',
            'security-session' => 'platform-security-session-tab-script.js',
            'security-policy' => 'platform-security-policy-tab-script.js',
            'permissions' => 'platform-permissions-tab-script.js',
        ];

        // Shared tab scripts (can be reused by other plugins)
        $shared_tab_scripts = [
            'demo-data' => '../demo-data/wpapp-demo-data.js', // TODO-1207: Shared asset
        ];

        // Check platform-specific scripts first
        if (isset($platform_tab_scripts[$tab])) {
            $file_path = WP_APP_CORE_PLUGIN_DIR . 'assets/js/settings/' . $platform_tab_scripts[$tab];

            if (\file_exists($file_path)) {
                $dependencies = ['jquery', 'wpapp-settings-base', 'wpapp-settings-reset-script'];

                \wp_enqueue_script(
                    'platform-settings-' . $tab,
                    WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/' . $platform_tab_scripts[$tab],
                    $dependencies,
                    \filemtime($file_path), // Use filemtime for cache busting
                    true
                );

                /**
                 * Action: After platform tab script enqueued
                 *
                 * @param string $tab Current tab slug
                 * @param string $handle JS handle that was enqueued
                 */
                \do_action('wpapp_after_platform_tab_script', $tab, 'platform-settings-' . $tab);
            }
        }
        // Check shared scripts
        elseif (isset($shared_tab_scripts[$tab])) {
            $file_path = WP_APP_CORE_PLUGIN_DIR . 'assets/js/settings/' . $shared_tab_scripts[$tab];

            if (\file_exists($file_path)) {
                $dependencies = ['jquery', 'wpapp-settings-base', 'wpapp-settings-reset-script'];

                // Add wp-modal dependency for demo-data tab
                if ($tab === 'demo-data') {
                    $dependencies[] = 'wp-modal';
                }

                \wp_enqueue_script(
                    'wpapp-settings-' . $tab,
                    WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/' . $shared_tab_scripts[$tab],
                    $dependencies,
                    \filemtime($file_path),
                    true
                );

                \do_action('wpapp_after_shared_tab_script', $tab, 'wpapp-settings-' . $tab);
            }
        }
    }

    /**
     * Get strategy name
     *
     * @return string
     */
    public function get_strategy_name(): string {
        return 'settings_page';
    }
}
