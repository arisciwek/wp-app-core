<?php
/**
 * Asset Strategy Interface
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Assets
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Assets/AssetStrategyInterface.php
 *
 * Description: Interface for asset loading strategies.
 *              Defines contract for conditional asset loading.
 *
 * Strategy Pattern Contract:
 * - should_load(): Detection logic
 * - enqueue_styles(): Load CSS
 * - enqueue_scripts(): Load JS
 * - get_strategy_name(): Unique identifier
 *
 * Changelog:
 * 1.0.0 - 2025-01-09
 * - Initial implementation
 * - Ported from WP_DataTable pattern
 */

namespace WPAppCore\Controllers\Assets;

defined('ABSPATH') || exit;

interface AssetStrategyInterface {
    /**
     * Determine if this strategy should load assets
     *
     * @return bool True if assets should be loaded
     */
    public function should_load(): bool;

    /**
     * Enqueue CSS styles
     *
     * @return void
     */
    public function enqueue_styles(): void;

    /**
     * Enqueue JavaScript files
     *
     * @return void
     */
    public function enqueue_scripts(): void;

    /**
     * Get unique strategy name
     *
     * @return string Strategy identifier
     */
    public function get_strategy_name(): string;
}
