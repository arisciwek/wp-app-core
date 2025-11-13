<?php
/**
 * Settings Page Assets Strategy
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Assets/Strategies
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Assets/Strategies/SettingsPageAssets.php
 *
 * Description: Asset loading strategy for Platform Settings pages.
 *              Loads tab-specific CSS and JS with proper localization.
 *
 * Responsibilities:
 * - Detect if on wp-app-core settings page
 * - Load base settings CSS/JS
 * - Load tab-specific assets based on active tab
 * - Provide wpAppCoreSettings localization
 *
 * Tab Assets Loaded:
 * - general: general-tab-*.css/js
 * - email: email-tab-*.css/js
 * - security-authentication: security-authentication-tab-*.css/js
 * - security-session: security-session-tab-*.css/js
 * - security-policy: security-policy-tab-*.css/js
 *
 * Changelog:
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

        // TEMP: Error logger for debugging fast-disappearing errors
        \wp_enqueue_script(
            'wpapp-error-logger',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/error-logger.js',
            [],
            \filemtime(WP_APP_CORE_PLUGIN_DIR . 'assets/js/settings/error-logger.js'),
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

        // Settings Reset Helper - Using WPModal with Form POST (no AJAX)
        \wp_enqueue_script(
            'wpapp-settings-reset-helper',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/settings-reset-helper-post.js',
            ['jquery', 'wp-modal'], // Depend on WPModal
            \filemtime(WP_APP_CORE_PLUGIN_DIR . 'assets/js/settings/settings-reset-helper-post.js'),
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
            'demo-data' => '../demo-data/demo-data.css', // TODO-1207: Shared asset
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
     * Loads shared assets yang bisa digunakan plugin lain.
     * Plugin lain dapat reuse assets ini tanpa duplikasi.
     *
     * @param string $tab Tab slug
     * @return void
     */
    private function enqueue_tab_script(string $tab): void {
        $tab_scripts = [
            'general' => 'general-tab-script.js',
            'email' => 'email-tab-script.js',
            'security-authentication' => 'security-authentication-tab-script.js',
            'security-session' => 'security-session-tab-script.js',
            'security-policy' => 'security-policy-tab-script.js',
            'permissions' => 'permissions-tab-script.js',
            'demo-data' => '../demo-data/demo-data.js', // TODO-1207: Shared asset
        ];

        if (isset($tab_scripts[$tab])) {
            $file_path = WP_APP_CORE_PLUGIN_DIR . 'assets/js/settings/' . $tab_scripts[$tab];

            if (\file_exists($file_path)) {
                // Base dependencies for all tabs
                $dependencies = ['jquery', 'wpapp-settings-base', 'wpapp-settings-reset-helper'];

                // Add wp-modal dependency for demo-data tab (TODO-1207)
                if ($tab === 'demo-data') {
                    $dependencies[] = 'wp-modal';
                }

                \wp_enqueue_script(
                    'wpapp-settings-' . $tab,
                    WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/' . $tab_scripts[$tab],
                    $dependencies,
                    \filemtime($file_path), // Use filemtime for cache busting
                    true
                );

                /**
                 * Action: After shared tab script enqueued
                 * Allows plugins to add overrides or additional scripts
                 *
                 * @param string $tab Current tab slug
                 * @param string $handle JS handle that was enqueued
                 */
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
