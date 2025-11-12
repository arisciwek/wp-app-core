<?php
/**
 * Abstract Base Class for Demo Data Generation
 *
 * @package     WP_App_Core
 * @subpackage  Database/Demo
 * @version     2.0.1
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Database/Demo/AbstractDemoData.php
 *
 * Description: Generic base abstract class for demo data generation.
 *              REFACTORED: Now truly plugin-agnostic.
 *              No hardcoded models or cache managers.
 *              Used by ALL plugins: wp-app-core, wp-customer, wp-agency, etc.
 *
 * Pattern:
 * - Child plugins implement initModels() to initialize their specific models
 * - Generic transaction wrapper (START/COMMIT/ROLLBACK)
 * - Memory limit management
 * - Debug logging
 *
 * Usage in Child Plugin:
 * ```php
 * class CustomerDemoData extends AbstractDemoData {
 *     protected $customerModel;
 *     protected $branchModel;
 *     protected $cache;
 *
 *     protected function initModels(): void {
 *         $this->cache = new CustomerCacheManager();
 *         $this->customerModel = new CustomerModel();
 *         $this->branchModel = new BranchModel();
 *     }
 *
 *     protected function validate(): bool {
 *         return current_user_can('manage_options');
 *     }
 *
 *     protected function generate(): void {
 *         // Generate demo data...
 *     }
 * }
 * ```
 *
 * Changelog:
 * 2.0.1 - 2025-01-12 (TODO-1207)
 * - Fixed: Constructor now calls initModels() immediately if plugins_loaded already fired
 * - Prevents null model errors when instantiated during AJAX calls
 * - Uses did_action('plugins_loaded') to detect if hook already ran
 * 2.0.0 - 2025-01-12 (TODO-1207)
 * - BREAKING: Made truly generic - removed all hardcoded models
 * - Removed CustomerModel, BranchModel, CustomerMembershipModel properties
 * - Removed CustomerCacheManager dependency
 * - Child plugins now implement initModels() to inject their models
 * - Same run() transaction logic, now reusable across all plugins
 * - Reduced from 120 lines to ~90 lines (25% reduction)
 * - Pattern reusable for 20 plugins
 * 1.0.0 - 2024-01-27
 * - Initial version (plugin-specific, deprecated)
 */

namespace WPAppCore\Database\Demo;

defined('ABSPATH') || exit;

abstract class AbstractDemoData {
    /**
     * WordPress database instance
     * @var \wpdb
     */
    protected $wpdb;

    /**
     * Constructor
     * Initializes database and hooks model initialization
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;

        // If plugins_loaded hook already fired, initialize models immediately
        // Otherwise, hook to plugins_loaded to prevent memory issues
        if (did_action('plugins_loaded')) {
            $this->initModels();
        } else {
            add_action('plugins_loaded', [$this, 'initModels'], 30);
        }
    }

    /**
     * Initialize plugin-specific models and cache managers
     * MUST be implemented by child class
     *
     * Example:
     * ```php
     * protected function initModels(): void {
     *     $this->cache = new CustomerCacheManager();
     *     $this->customerModel = new CustomerModel();
     * }
     * ```
     *
     * @return void
     */
    abstract public function initModels(): void;

    /**
     * Generate demo data
     * MUST be implemented by child class
     *
     * @return void
     */
    abstract protected function generate(): void;

    /**
     * Validate conditions before generation
     * MUST be implemented by child class
     *
     * Example:
     * ```php
     * protected function validate(): bool {
     *     return current_user_can('manage_options');
     * }
     * ```
     *
     * @return bool True if validation passes, false otherwise
     */
    abstract protected function validate(): bool;

    /**
     * Run demo data generation with transaction wrapper
     *
     * Flow:
     * 1. Ensure models are initialized
     * 2. Increase memory limit
     * 3. Start transaction
     * 4. Validate conditions
     * 5. Generate data
     * 6. Commit transaction
     * 7. Rollback on error
     *
     * @return bool True on success, false on failure
     */
    public function run(): bool {
        try {
            // Ensure models are initialized
            $this->initModels();

            // Increase memory limit for demo data generation
            wp_raise_memory_limit('admin');

            // Start transaction
            $this->wpdb->query('START TRANSACTION');

            // Validate before generation
            if (!$this->validate()) {
                throw new \Exception("Validation failed in " . get_class($this));
            }

            // Generate demo data
            $this->generate();

            // Commit transaction
            $this->wpdb->query('COMMIT');

            $this->debug("Demo data generation completed successfully");
            return true;

        } catch (\Exception $e) {
            // Rollback on error
            $this->wpdb->query('ROLLBACK');
            $this->debug("Demo data generation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Debug logging helper
     * Only logs when WP_DEBUG is enabled
     *
     * @param string $message Debug message
     * @return void
     */
    protected function debug(string $message): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[" . get_class($this) . "] {$message}");
        }
    }
}
