<?php
/**
 * Admin Bar Assets Strategy
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Assets/Strategies
 * @version     1.0.0
 */

namespace WPAppCore\Controllers\Assets\Strategies;

use WPAppCore\Controllers\Assets\AssetStrategyInterface;

defined('ABSPATH') || exit;

class AdminBarAssets implements AssetStrategyInterface {

    public function should_load(): bool {
        return is_admin_bar_showing();
    }

    public function enqueue_styles(): void {
        wp_enqueue_style(
            'wp-app-core-admin-bar',
            WP_APP_CORE_PLUGIN_URL . 'assets/css/admin-bar/admin-bar-style.css',
            ['admin-bar'],
            WP_APP_CORE_VERSION
        );
    }

    public function enqueue_scripts(): void {
        wp_enqueue_script(
            'wp-app-core-admin-bar',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/admin-bar/admin-bar-script.js',
            ['jquery'],
            WP_APP_CORE_VERSION,
            true
        );
    }

    public function get_strategy_name(): string {
        return 'admin_bar';
    }
}
