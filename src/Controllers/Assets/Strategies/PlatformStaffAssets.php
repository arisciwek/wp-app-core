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
            return false;
        }

        $screen = \get_current_screen();
        if (!$screen) {
            return false;
        }
        return strpos($screen->id, 'platform-staff') !== false;
    }

    public function enqueue_styles(): void {
        // Platform staff styles if needed
    }

    public function enqueue_scripts(): void {
        // Platform staff scripts if needed
    }

    public function get_strategy_name(): string {
        return 'platform_staff';
    }
}
