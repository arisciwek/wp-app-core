<?php
/**
 * Completeness Manager - Central Registry
 *
 * @package     WPAppCore
 * @subpackage  Models/Completeness
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Models/Completeness/CompletenessManager.php
 *
 * Description: Singleton registry for managing completeness calculators across plugins.
 *              Acts as central container where plugins register their calculators.
 *              Provides unified API for calculating completeness and rendering progress bars.
 *
 * Architecture Pattern:
 * ```
 * wp-app-core provides:
 * - CompletenessManager (this class) - Container/Registry
 * - AbstractCompletenessCalculator - Base class
 * - CompletenessResult - Value object
 * - Progress bar component - HTML/CSS/JS
 *
 * Plugins provide:
 * - Calculator implementation (extends Abstract)
 * - Field definitions (required/optional)
 * - Threshold configuration
 * - Registration via hook
 * ```
 *
 * Registration Example (in plugin):
 * ```php
 * add_action('wpapp_register_completeness_calculators', function($manager) {
 *     $manager->register('customer', new CustomerCompletenessCalculator());
 * });
 * ```
 *
 * Usage Example:
 * ```php
 * $manager = CompletenessManager::getInstance();
 * $result = $manager->calculate('customer', $customer_id);
 * echo $result->percentage; // 75
 *
 * // Render progress bar
 * $manager->renderProgressBar('customer', $customer_id);
 * ```
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-1195)
 * - Initial implementation
 * - Singleton pattern
 * - Calculator registration system
 * - Unified calculate/render API
 * - Hook-based registration
 */

namespace WPAppCore\Models\Completeness;

defined('ABSPATH') || exit;

class CompletenessManager {

    /**
     * Singleton instance
     *
     * @var CompletenessManager|null
     */
    private static ?CompletenessManager $instance = null;

    /**
     * Registered calculators
     *
     * Format: ['entity_type' => AbstractCompletenessCalculator instance]
     * Example: ['customer' => CustomerCompletenessCalculator, ...]
     *
     * @var array<string, AbstractCompletenessCalculator>
     */
    private array $calculators = [];

    /**
     * Calculator configurations
     *
     * Store metadata about each calculator for display purposes.
     * Format: ['entity_type' => ['label' => 'Customer', 'icon' => 'dashicons-businessman']]
     *
     * @var array<string, array>
     */
    private array $configs = [];

    /**
     * Private constructor (Singleton)
     */
    private function __construct() {
        // Trigger registration hook
        do_action('wpapp_register_completeness_calculators', $this);
    }

    /**
     * Get singleton instance
     *
     * @return self
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a completeness calculator
     *
     * Plugins call this method (via hook) to register their calculator.
     *
     * Example:
     * ```php
     * add_action('wpapp_register_completeness_calculators', function($manager) {
     *     $manager->register('customer', new CustomerCompletenessCalculator(), [
     *         'label' => __('Customer', 'wp-customer'),
     *         'icon' => 'dashicons-businessman',
     *         'description' => __('Customer profile completeness', 'wp-customer')
     *     ]);
     * });
     * ```
     *
     * @param string $entity_type Unique identifier (customer, surveyor, association)
     * @param AbstractCompletenessCalculator $calculator Calculator instance
     * @param array $config Optional configuration
     *   - label: Display name (default: ucfirst entity_type)
     *   - icon: Dashicon class (default: dashicons-admin-generic)
     *   - description: Help text
     * @return void
     * @throws \InvalidArgumentException If entity_type already registered
     */
    public function register(string $entity_type, AbstractCompletenessCalculator $calculator, array $config = []): void {
        if (isset($this->calculators[$entity_type])) {
            throw new \InvalidArgumentException("Calculator for '{$entity_type}' already registered");
        }

        $this->calculators[$entity_type] = $calculator;
        $this->configs[$entity_type] = wp_parse_args($config, [
            'label' => ucfirst($entity_type),
            'icon' => 'dashicons-admin-generic',
            'description' => ''
        ]);

        $this->log("Registered calculator for: {$entity_type}");
    }

    /**
     * Check if calculator is registered
     *
     * @param string $entity_type Entity type to check
     * @return bool True if registered
     */
    public function isRegistered(string $entity_type): bool {
        return isset($this->calculators[$entity_type]);
    }

    /**
     * Get calculator for entity type
     *
     * @param string $entity_type Entity type
     * @return AbstractCompletenessCalculator|null Calculator or null if not found
     */
    public function getCalculator(string $entity_type): ?AbstractCompletenessCalculator {
        return $this->calculators[$entity_type] ?? null;
    }

    /**
     * Get all registered entity types
     *
     * @return array List of entity types
     */
    public function getRegisteredTypes(): array {
        return array_keys($this->calculators);
    }

    /**
     * Calculate completeness for an entity
     *
     * Central method to calculate completeness.
     * Delegates to registered calculator.
     *
     * @param string $entity_type Entity type (customer, surveyor, etc.)
     * @param int|object $entity Entity ID or object
     * @return CompletenessResult|null Result or null if calculator not found
     * @throws \Exception If calculator throws error
     */
    public function calculate(string $entity_type, $entity): ?CompletenessResult {
        $calculator = $this->getCalculator($entity_type);

        if (!$calculator) {
            $this->log("Calculator not found for: {$entity_type}", 'error');
            return null;
        }

        try {
            return $calculator->calculate($entity);
        } catch (\Exception $e) {
            $this->log("Error calculating completeness for {$entity_type}: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Check if entity can transact
     *
     * Convenience method for transaction validation.
     *
     * @param string $entity_type Entity type
     * @param int|object $entity Entity ID or object
     * @return bool True if can transact, false otherwise
     */
    public function canTransact(string $entity_type, $entity): bool {
        $calculator = $this->getCalculator($entity_type);

        if (!$calculator) {
            $this->log("Calculator not found for canTransact: {$entity_type}", 'error');
            return false;
        }

        return $calculator->canTransact($entity);
    }

    /**
     * Render progress bar component
     *
     * Renders the reusable progress bar HTML.
     * Calculates completeness and includes template.
     *
     * @param string $entity_type Entity type
     * @param int|object $entity Entity ID or object
     * @param array $args Optional display arguments
     *   - variant: Template variant (default|compact)
     *   - show_details: Show breakdown (default: true)
     *   - show_missing: Show missing fields (default: true)
     *   - class: Additional CSS classes
     * @return void Outputs HTML
     */
    public function renderProgressBar(string $entity_type, $entity, array $args = []): void {
        $completeness = $this->calculate($entity_type, $entity);

        if (!$completeness) {
            echo '<p class="wpapp-error">' . __('Unable to calculate completeness.', 'wp-app-core') . '</p>';
            return;
        }

        // Get config for this entity type
        $config = $this->configs[$entity_type] ?? [];

        // Merge args with defaults
        $args = wp_parse_args($args, [
            'variant' => 'default',
            'show_details' => true,
            'show_missing' => true,
            'class' => '',
            'entity_label' => $config['label'] ?? ucfirst($entity_type)
        ]);

        // Determine template based on variant
        $template_file = $args['variant'] === 'compact'
            ? 'completeness-bar-compact.php'
            : 'completeness-bar.php';

        // Include template
        $template_path = WP_APP_CORE_PATH . 'src/Components/Completeness/' . $template_file;

        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->log("Template not found: {$template_path}", 'error');
            echo '<p class="wpapp-error">' . __('Progress bar template not found.', 'wp-app-core') . '</p>';
        }
    }

    /**
     * Get completeness data as array (for AJAX/REST API)
     *
     * @param string $entity_type Entity type
     * @param int|object $entity Entity ID or object
     * @return array|null Array data or null if error
     */
    public function getCompletenessData(string $entity_type, $entity): ?array {
        $result = $this->calculate($entity_type, $entity);

        if (!$result) {
            return null;
        }

        return array_merge(
            $result->toArray(),
            [
                'entity_type' => $entity_type,
                'config' => $this->configs[$entity_type] ?? []
            ]
        );
    }

    /**
     * Get missing required fields for an entity
     *
     * @param string $entity_type Entity type
     * @param int|object $entity Entity ID or object
     * @return array List of missing field labels
     */
    public function getMissingRequiredFields(string $entity_type, $entity): array {
        $calculator = $this->getCalculator($entity_type);

        if (!$calculator) {
            return [];
        }

        return $calculator->getMissingRequiredFields($entity);
    }

    /**
     * Bulk calculate completeness for multiple entities
     *
     * Useful for dashboard statistics showing average completeness.
     *
     * @param string $entity_type Entity type
     * @param array $entity_ids List of entity IDs
     * @return array Results indexed by entity ID
     */
    public function bulkCalculate(string $entity_type, array $entity_ids): array {
        $results = [];

        foreach ($entity_ids as $id) {
            try {
                $results[$id] = $this->calculate($entity_type, $id);
            } catch (\Exception $e) {
                $this->log("Bulk calculate error for {$entity_type} ID {$id}: " . $e->getMessage(), 'error');
                $results[$id] = null;
            }
        }

        return $results;
    }

    /**
     * Get average completeness percentage
     *
     * Calculate average across multiple entities.
     * Useful for dashboard widgets.
     *
     * @param string $entity_type Entity type
     * @param array $entity_ids List of entity IDs
     * @return int Average percentage (0-100)
     */
    public function getAverageCompleteness(string $entity_type, array $entity_ids): int {
        if (empty($entity_ids)) {
            return 0;
        }

        $results = $this->bulkCalculate($entity_type, $entity_ids);
        $total = 0;
        $count = 0;

        foreach ($results as $result) {
            if ($result instanceof CompletenessResult) {
                $total += $result->percentage;
                $count++;
            }
        }

        return $count > 0 ? round($total / $count) : 0;
    }

    /**
     * Log message
     *
     * @param string $message Log message
     * @param string $level Log level (info|error)
     * @return void
     */
    private function log(string $message, string $level = 'info'): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $prefix = '[CompletenessManager]';
            if ($level === 'error') {
                error_log("ERROR: {$prefix} {$message}");
            } else {
                error_log("{$prefix} {$message}");
            }
        }
    }

    /**
     * Prevent cloning (Singleton)
     */
    private function __clone() {}

    /**
     * Prevent unserialization (Singleton)
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}
