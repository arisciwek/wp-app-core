<?php
/**
 * Asset Controller
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Assets
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Assets/AssetController.php
 *
 * Description: Central orchestrator untuk asset loading strategies.
 *              REFACTORED: Replaces class-dependencies.php (old pattern).
 *              Implements Strategy Pattern untuk flexible asset loading.
 *
 * Responsibilities:
 * - Register asset strategies (Settings, AdminBar, PlatformStaff)
 * - Execute strategies based on should_load() detection
 * - Hook into WordPress asset enqueue system
 * - Provide global wpAppCoreSettings localization
 *
 * Changelog:
 * 1.0.0 - 2025-01-09
 * - Initial implementation
 * - Ported from WP_DataTable pattern
 * - Replaces class-dependencies.php
 */

namespace WPAppCore\Controllers\Assets;

defined('ABSPATH') || exit;

class AssetController {
    /**
     * Singleton instance
     *
     * @var AssetController|null
     */
    private static $instance = null;

    /**
     * Registered asset strategies
     *
     * @var array<string, AssetStrategyInterface>
     */
    private $strategies = [];

    /**
     * Get singleton instance
     *
     * @return AssetController
     */
    public static function get_instance(): AssetController {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - private untuk Singleton
     */
    private function __construct() {
        // Hook into WordPress asset enqueue
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']); // For admin bar on frontend

        /**
         * Action: After AssetController initialized
         * Plugins can register custom strategies here
         *
         * @param AssetController $this Controller instance
         */
        do_action('wpapp_asset_controller_init', $this);
    }

    /**
     * Initialize controller and register default strategies
     *
     * @return void
     */
    public function init(): void {
        // Register default strategies
        $this->register_default_strategies();

        /**
         * Action: Plugins can register additional strategies
         *
         * @param AssetController $this Controller instance
         */
        do_action('wpapp_register_asset_strategies', $this);
    }

    /**
     * Register default asset strategies
     *
     * @return void
     */
    private function register_default_strategies(): void {
        // Register Settings Page Assets
        $this->register_strategy(new Strategies\SettingsPageAssets());

        // Register Admin Bar Assets
        $this->register_strategy(new Strategies\AdminBarAssets());

        // Register Platform Staff Assets
        $this->register_strategy(new Strategies\PlatformStaffAssets());
    }

    /**
     * Register asset strategy
     *
     * @param AssetStrategyInterface $strategy Strategy instance
     * @return void
     */
    public function register_strategy(AssetStrategyInterface $strategy): void {
        $name = $strategy->get_strategy_name();
        $this->strategies[$name] = $strategy;
    }

    /**
     * Enqueue assets via registered strategies
     *
     * Loops through all registered strategies and executes
     * those whose should_load() returns true.
     *
     * @return void
     */
    public function enqueue_assets(): void {
        foreach ($this->strategies as $strategy) {
            if ($strategy->should_load()) {
                $strategy->enqueue_styles();
                $strategy->enqueue_scripts();
            }
        }
    }

    /**
     * Get registered strategy by name
     *
     * @param string $name Strategy name
     * @return AssetStrategyInterface|null
     */
    public function get_strategy(string $name): ?AssetStrategyInterface {
        return $this->strategies[$name] ?? null;
    }

    /**
     * Get all registered strategies
     *
     * @return array<string, AssetStrategyInterface>
     */
    public function get_all_strategies(): array {
        return $this->strategies;
    }
}
