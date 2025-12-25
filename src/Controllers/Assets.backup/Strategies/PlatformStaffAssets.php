<?php
/**
 * Platform Staff Assets Strategy
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Assets/Strategies
 * @version     1.0.0
 */

namespace WPAppCore\Controllers\Assets\Strategies;

use WPAppCore\Controllers\Assets\AssetStrategyInterface;

defined('ABSPATH') || exit;

class PlatformStaffAssets implements AssetStrategyInterface {

    public function should_load(): bool {
        // get_current_screen() only exists in admin
        if (!\is_admin()) {
            error_log('[PlatformStaffAssets] Not in admin area');
            return false;
        }

        $screen = \get_current_screen();
        if (!$screen) {
            error_log('[PlatformStaffAssets] Screen not available');
            return false;
        }

        $should_load = strpos($screen->id, 'platform-staff') !== false;
        error_log('[PlatformStaffAssets] Screen ID: ' . $screen->id . ' | Should load: ' . ($should_load ? 'YES' : 'NO'));
        return $should_load;
    }

    public function enqueue_styles(): void {
        // Platform staff styles if needed
    }

    public function enqueue_scripts(): void {
        error_log('[PlatformStaffAssets] enqueue_scripts() called');

        // Enqueue platform staff datatable script
        wp_enqueue_script(
            'platform-staff-datatable',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/platform/platform-staff-datatable.js',
            ['jquery', 'datatables'],
            WP_APP_CORE_VERSION,
            true
        );

        error_log('[PlatformStaffAssets] Script enqueued: ' . WP_APP_CORE_PLUGIN_URL . 'assets/js/platform/platform-staff-datatable.js');
    }

    public function get_strategy_name(): string {
        return 'platform_staff';
    }
}
