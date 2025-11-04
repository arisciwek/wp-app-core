<?php
/**
 * Abstract Completeness Calculator
 *
 * @package     WPAppCore
 * @subpackage  Models/Completeness
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Models/Completeness/AbstractCompletenessCalculator.php
 *
 * Description: Base class for calculating entity data completeness.
 *              Provides framework for plugins (customer, surveyor, association)
 *              to implement their own completeness logic.
 *              Calculates percentage, tracks missing fields, validates transaction readiness.
 *
 * Architecture:
 * - Abstract methods force implementation in child classes
 * - Weight-based system for flexible field importance
 * - Category support for organized breakdown
 * - Transaction validation with configurable threshold
 * - Extensible via filters and hooks
 *
 * Child Class Requirements:
 * - Define required fields with weights
 * - Define optional fields with weights
 * - Set minimum threshold percentage
 * - Implement field checking logic
 * - Implement calculation method
 *
 * Usage Example:
 * ```php
 * class CustomerCompletenessCalculator extends AbstractCompletenessCalculator {
 *     protected function getRequiredFields(): array {
 *         return ['customer.npwp' => 10, 'branch.email' => 10];
 *     }
 *     // ... implement other methods
 * }
 *
 * $calculator = new CustomerCompletenessCalculator();
 * $result = $calculator->calculate($customer_id);
 * echo $result->percentage; // 75
 * if ($calculator->canTransact($customer_id)) {
 *     // Allow transaction
 * }
 * ```
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-1195)
 * - Initial implementation
 * - Abstract methods for required/optional fields
 * - Threshold-based transaction validation
 * - Missing fields tracking
 * - Category-based calculation support
 */

namespace WPAppCore\Models\Completeness;

defined('ABSPATH') || exit;

abstract class AbstractCompletenessCalculator {

    /**
     * Calculate completeness for an entity
     *
     * This method must be implemented by child classes to:
     * 1. Load entity data (customer, surveyor, etc.)
     * 2. Check each required and optional field
     * 3. Calculate total and earned points
     * 4. Categorize fields for organized display
     * 5. Return CompletenessResult object
     *
     * @param int|object $entity Entity ID or entity object
     * @return CompletenessResult Complete calculation result
     * @throws \Exception If entity not found or invalid
     */
    abstract public function calculate($entity): CompletenessResult;

    /**
     * Define required fields with weights
     *
     * Return array of field names (dot notation) with point values.
     * These fields are CRITICAL - user cannot transact without minimum threshold.
     *
     * Format: ['field_name' => points, ...]
     * Example:
     * ```php
     * return [
     *     'customer.npwp' => 10,      // Customer NPWP (10 points)
     *     'branch.email' => 10,       // Branch email (10 points)
     *     'employee.exists' => 5,     // At least one employee (5 points)
     * ];
     * ```
     *
     * Field naming convention:
     * - Use dot notation: entity.property
     * - Entity: customer, branch, employee, etc.
     * - Property: actual database column or virtual check (like 'exists')
     *
     * @return array<string, int> Associative array of field => weight
     */
    abstract protected function getRequiredFields(): array;

    /**
     * Define optional fields with weights
     *
     * Return array of field names with point values.
     * These fields enhance completeness but are NOT required for transactions.
     *
     * Format: ['field_name' => points, ...]
     * Example:
     * ```php
     * return [
     *     'customer.nib' => 5,           // NIB (nice to have)
     *     'branch.postal_code' => 3,     // Postal code (optional)
     *     'branch.latitude' => 2,        // Location coordinates (bonus)
     * ];
     * ```
     *
     * @return array<string, int> Associative array of field => weight
     */
    abstract protected function getOptionalFields(): array;

    /**
     * Get minimum threshold percentage to allow transactions
     *
     * Return the minimum percentage required for entity to transact.
     * If completeness percentage is below this, canTransact() returns false.
     *
     * Recommended thresholds:
     * - 70-80%: Standard business entities (customer, supplier)
     * - 90-100%: Critical entities (surveyor, inspector)
     * - 50-60%: Less critical entities (association member)
     *
     * @return int Percentage (0-100)
     */
    abstract public function getMinimumThreshold(): int;

    /**
     * Check if entity can perform transactions
     *
     * Validates if entity has met minimum completeness threshold.
     * Use this before allowing critical actions (create invoice, assign surveyor, etc.)
     *
     * Implementation:
     * - Calculates current completeness
     * - Compares to minimum threshold
     * - Returns boolean decision
     *
     * Example:
     * ```php
     * if (!$calculator->canTransact($customer_id)) {
     *     wp_die('Please complete your profile first.');
     * }
     * ```
     *
     * @param int|object $entity Entity ID or object
     * @return bool True if can transact, false otherwise
     */
    public function canTransact($entity): bool {
        try {
            $result = $this->calculate($entity);

            /**
             * Filter: Allow override of transaction permission
             *
             * @param bool $can_transact Default permission based on threshold
             * @param CompletenessResult $result Completeness calculation result
             * @param mixed $entity Entity ID or object
             * @param self $calculator Calculator instance
             */
            return apply_filters(
                'wpapp_can_transact_' . $this->getEntityType(),
                $result->can_transact,
                $result,
                $entity,
                $this
            );
        } catch (\Exception $e) {
            // On error, deny transaction for safety
            error_log('[CompletenessCalculator] Error in canTransact: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get missing fields that prevent transactions
     *
     * Returns list of required fields that are not yet filled.
     * Useful for showing users what they need to complete.
     *
     * Example:
     * ```php
     * $missing = $calculator->getMissingRequiredFields($customer_id);
     * // Returns: ['NPWP', 'Branch Email', 'Employee']
     * ```
     *
     * @param int|object $entity Entity ID or object
     * @return array List of missing field labels (human-readable)
     */
    public function getMissingRequiredFields($entity): array {
        try {
            $result = $this->calculate($entity);
            return $result->missing_required;
        } catch (\Exception $e) {
            error_log('[CompletenessCalculator] Error in getMissingRequiredFields: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get missing optional fields
     *
     * Returns list of optional fields not yet filled.
     * Useful for encouraging users to complete profile for higher score.
     *
     * @param int|object $entity Entity ID or object
     * @return array List of missing optional field labels
     */
    public function getMissingOptionalFields($entity): array {
        try {
            $result = $this->calculate($entity);
            return $result->missing_optional;
        } catch (\Exception $e) {
            error_log('[CompletenessCalculator] Error in getMissingOptionalFields: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get entity type identifier
     *
     * Override this to return entity type name.
     * Used for filter hooks and logging.
     *
     * Example: 'customer', 'surveyor', 'association'
     *
     * @return string Entity type
     */
    protected function getEntityType(): string {
        // Extract from class name: CustomerCompletenessCalculator -> customer
        $class_name = get_class($this);
        $parts = explode('\\', $class_name);
        $short_name = end($parts);
        $type = str_replace('CompletenessCalculator', '', $short_name);
        return strtolower($type);
    }

    /**
     * Calculate total possible points
     *
     * Sums all weights from required + optional fields.
     *
     * @return int Total points
     */
    protected function getTotalPoints(): int {
        $required = array_sum($this->getRequiredFields());
        $optional = array_sum($this->getOptionalFields());
        return $required + $optional;
    }

    /**
     * Helper: Check if a value is considered "filled"
     *
     * A field is filled if:
     * - Not null
     * - Not empty string
     * - Not 0 (except for valid zero values)
     *
     * @param mixed $value Field value to check
     * @return bool True if filled
     */
    protected function isFilled($value): bool {
        if (is_null($value)) {
            return false;
        }
        if (is_string($value) && trim($value) === '') {
            return false;
        }
        // Allow boolean false and numeric 0 as valid values
        return true;
    }

    /**
     * Helper: Log debug message
     *
     * @param string $message Log message
     * @return void
     */
    protected function log(string $message): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[' . get_class($this) . '] ' . $message);
        }
    }
}
