<?php
/**
 * Completeness Result Value Object
 *
 * @package     WPAppCore
 * @subpackage  Models/Completeness
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Models/Completeness/CompletenessResult.php
 *
 * Description: Immutable value object that holds completeness calculation results.
 *              Contains all data needed to display progress, validate transactions,
 *              and show detailed breakdown.
 *
 * Properties:
 * - percentage: Overall completion (0-100)
 * - total_points: Maximum possible points
 * - earned_points: Points actually earned
 * - completed_fields: List of filled fields
 * - missing_required: Required fields still empty
 * - missing_optional: Optional fields still empty
 * - can_transact: Boolean if meets minimum threshold
 * - categories: Breakdown by category (Customer Info, Branch Info, etc.)
 *
 * Usage:
 * ```php
 * $result = new CompletenessResult([
 *     'percentage' => 75,
 *     'total_points' => 100,
 *     'earned_points' => 75,
 *     'can_transact' => false,
 *     // ... other data
 * ]);
 *
 * echo $result->percentage; // 75
 * if ($result->can_transact) { }
 * $array = $result->toArray(); // For JSON/REST API
 * ```
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-1195)
 * - Initial implementation
 * - Immutable value object pattern
 * - Support for categories breakdown
 * - JSON serializable
 */

namespace WPAppCore\Models\Completeness;

defined('ABSPATH') || exit;

class CompletenessResult {

    /**
     * Completion percentage (0-100)
     *
     * Calculated as: (earned_points / total_points) * 100
     * Rounded to nearest integer.
     *
     * @var int
     */
    public int $percentage;

    /**
     * Total possible points
     *
     * Sum of all required + optional field weights.
     * Example: If required=70 and optional=30, total=100
     *
     * @var int
     */
    public int $total_points;

    /**
     * Earned points
     *
     * Sum of weights for all completed fields (required + optional).
     * Example: If 50 points worth of fields are filled, earned=50
     *
     * @var int
     */
    public int $earned_points;

    /**
     * Completed fields
     *
     * Array of field names that are filled.
     * Format: ['customer.npwp', 'branch.email', ...]
     *
     * @var array
     */
    public array $completed_fields;

    /**
     * Missing required fields
     *
     * Array of human-readable labels for required fields that are empty.
     * These prevent transactions if threshold not met.
     * Format: ['NPWP', 'Branch Email', 'Employee']
     *
     * @var array
     */
    public array $missing_required;

    /**
     * Missing optional fields
     *
     * Array of human-readable labels for optional fields that are empty.
     * These don't block transactions but lower completion percentage.
     * Format: ['NIB', 'Postal Code', 'Coordinates']
     *
     * @var array
     */
    public array $missing_optional;

    /**
     * Can entity transact?
     *
     * Boolean indicating if percentage meets minimum threshold.
     * True = can create invoices, assignments, etc.
     * False = must complete profile first
     *
     * @var bool
     */
    public bool $can_transact;

    /**
     * Breakdown by category
     *
     * Organized points by category for detailed display.
     * Format:
     * ```php
     * [
     *     'Customer Info' => ['total' => 30, 'earned' => 20],
     *     'Branch Info' => ['total' => 35, 'earned' => 35],
     *     'Employee Info' => ['total' => 35, 'earned' => 0],
     * ]
     * ```
     *
     * @var array
     */
    public array $categories;

    /**
     * Minimum threshold required (for reference)
     *
     * Optional - can be set to show user the target.
     * Not used in calculations, just for display.
     *
     * @var int|null
     */
    public ?int $minimum_threshold;

    /**
     * Constructor
     *
     * Creates immutable result object from calculation data.
     *
     * @param array $data Associative array with all result data
     *
     * Required keys:
     * - percentage (int)
     * - total_points (int)
     * - earned_points (int)
     * - can_transact (bool)
     *
     * Optional keys:
     * - completed_fields (array) - defaults to []
     * - missing_required (array) - defaults to []
     * - missing_optional (array) - defaults to []
     * - categories (array) - defaults to []
     * - minimum_threshold (int) - defaults to null
     *
     * @throws \InvalidArgumentException If required keys missing
     */
    public function __construct(array $data) {
        // Validate required keys
        $required_keys = ['percentage', 'total_points', 'earned_points', 'can_transact'];
        foreach ($required_keys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new \InvalidArgumentException("Missing required key: {$key}");
            }
        }

        // Set properties
        $this->percentage = (int) $data['percentage'];
        $this->total_points = (int) $data['total_points'];
        $this->earned_points = (int) $data['earned_points'];
        $this->can_transact = (bool) $data['can_transact'];

        // Optional properties with defaults
        $this->completed_fields = $data['completed_fields'] ?? [];
        $this->missing_required = $data['missing_required'] ?? [];
        $this->missing_optional = $data['missing_optional'] ?? [];
        $this->categories = $data['categories'] ?? [];
        $this->minimum_threshold = $data['minimum_threshold'] ?? null;
    }

    /**
     * Convert to array
     *
     * Useful for JSON encoding, REST API responses, or caching.
     *
     * @return array All properties as associative array
     */
    public function toArray(): array {
        return [
            'percentage' => $this->percentage,
            'total_points' => $this->total_points,
            'earned_points' => $this->earned_points,
            'completed_fields' => $this->completed_fields,
            'missing_required' => $this->missing_required,
            'missing_optional' => $this->missing_optional,
            'can_transact' => $this->can_transact,
            'categories' => $this->categories,
            'minimum_threshold' => $this->minimum_threshold,
        ];
    }

    /**
     * Convert to JSON
     *
     * @param int $flags JSON encoding flags
     * @return string JSON representation
     */
    public function toJson(int $flags = 0): string {
        return json_encode($this->toArray(), $flags);
    }

    /**
     * Get completion status text
     *
     * Returns human-readable status based on percentage.
     *
     * @return string Status text
     */
    public function getStatusText(): string {
        if ($this->percentage >= 90) {
            return __('Excellent', 'wp-app-core');
        } elseif ($this->percentage >= 70) {
            return __('Good', 'wp-app-core');
        } elseif ($this->percentage >= 50) {
            return __('Fair', 'wp-app-core');
        } else {
            return __('Incomplete', 'wp-app-core');
        }
    }

    /**
     * Get progress bar color class
     *
     * Returns CSS class name based on percentage.
     *
     * @return string CSS class (success|warning|danger)
     */
    public function getColorClass(): string {
        if ($this->percentage >= 80) {
            return 'progress-success';
        } elseif ($this->percentage >= 50) {
            return 'progress-warning';
        } else {
            return 'progress-danger';
        }
    }

    /**
     * Get category completion percentage
     *
     * Calculate completion for a specific category.
     *
     * @param string $category Category name
     * @return int Percentage (0-100), or 0 if category not found
     */
    public function getCategoryPercentage(string $category): int {
        if (!isset($this->categories[$category])) {
            return 0;
        }

        $cat = $this->categories[$category];
        $total = $cat['total'] ?? 0;
        $earned = $cat['earned'] ?? 0;

        if ($total === 0) {
            return 0;
        }

        return round(($earned / $total) * 100);
    }

    /**
     * Get list of all missing fields (required + optional)
     *
     * @return array Combined list of missing field labels
     */
    public function getAllMissingFields(): array {
        return array_merge($this->missing_required, $this->missing_optional);
    }

    /**
     * Count completed fields
     *
     * @return int Number of fields completed
     */
    public function countCompletedFields(): int {
        return count($this->completed_fields);
    }

    /**
     * Count missing required fields
     *
     * @return int Number of required fields missing
     */
    public function countMissingRequired(): int {
        return count($this->missing_required);
    }

    /**
     * Check if specific category is complete
     *
     * @param string $category Category name
     * @param int $threshold Minimum percentage for "complete" (default 100)
     * @return bool True if category meets threshold
     */
    public function isCategoryComplete(string $category, int $threshold = 100): bool {
        return $this->getCategoryPercentage($category) >= $threshold;
    }

    /**
     * Get human-readable summary
     *
     * Returns a summary string for logging or display.
     * Example: "75% complete (75/100 points), can transact: No"
     *
     * @return string Summary text
     */
    public function getSummary(): string {
        return sprintf(
            '%d%% complete (%d/%d points), can transact: %s',
            $this->percentage,
            $this->earned_points,
            $this->total_points,
            $this->can_transact ? 'Yes' : 'No'
        );
    }

    /**
     * Magic method for string conversion
     *
     * @return string Summary text
     */
    public function __toString(): string {
        return $this->getSummary();
    }
}
