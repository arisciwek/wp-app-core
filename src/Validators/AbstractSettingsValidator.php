<?php
/**
 * Abstract Settings Validator
 *
 * Base validator class for plugin settings validation.
 * Provides rule-based validation with extensible rule system.
 *
 * @package     WPAppCore
 * @subpackage  Validators
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Validators/AbstractSettingsValidator.php
 *
 * Description: Abstract base class for settings validators.
 *              Provides standardized validation rules and error handling.
 *              DOES NOT extend AbstractValidator (different purpose).
 *              AbstractValidator = Entity CRUD (requires entity ID, relations)
 *              AbstractSettingsValidator = Configuration validation (no entity ID)
 *
 * Dependencies:
 * - WordPress validation functions (is_email, sanitize_text_field, etc.)
 *
 * Changelog:
 * 1.0.0 - 2025-01-09
 * - Initial implementation
 * - Rule-based validation system
 * - Support: required, email, url, numeric, boolean, min, max, in
 * - Custom error messages
 * - Multiple error handlers per field
 */

namespace WPAppCore\Validators;

defined('ABSPATH') || exit;

abstract class AbstractSettingsValidator {

    /**
     * @var array Validation errors
     */
    protected array $errors = [];

    // ========================================
    // ABSTRACT METHODS (Must be implemented by child classes)
    // ========================================

    /**
     * Get validation rules for settings
     *
     * Returns array of field => rules mapping.
     *
     * @return array Rules per field
     *
     * @example
     * return [
     *     'site_name' => ['required', 'max:255'],
     *     'admin_email' => ['required', 'email'],
     *     'items_per_page' => ['required', 'numeric', 'min:1', 'max:100'],
     *     'enable_feature' => ['boolean']
     * ];
     */
    abstract protected function getRules(): array;

    /**
     * Get text domain for translations
     *
     * @return string Text domain, e.g., 'wp-customer', 'wp-agency'
     */
    abstract protected function getTextDomain(): string;

    /**
     * Get custom validation messages (optional)
     *
     * Override to provide custom error messages per field.rule.
     *
     * @return array Custom messages per field.rule
     *
     * @example
     * return [
     *     'site_name.required' => 'Please enter a site name',
     *     'items_per_page.min' => 'Must show at least 1 item'
     * ];
     */
    protected function getMessages(): array {
        return [];
    }

    // ========================================
    // CONCRETE METHODS (Shared implementation)
    // ========================================

    /**
     * Validate settings data
     *
     * @param array $data Settings data to validate
     * @return bool True if valid, false otherwise
     */
    public function validate(array $data): bool {
        $this->errors = [];
        $rules = $this->getRules();

        foreach ($rules as $field => $rule_set) {
            $value = $data[$field] ?? null;
            $this->validateField($field, $value, $rule_set);
        }

        return empty($this->errors);
    }

    /**
     * Validate single field against its rules
     *
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $rules Validation rules
     */
    protected function validateField(string $field, $value, array $rules): void {
        foreach ($rules as $rule) {
            // Required
            if ($rule === 'required' && $this->isEmpty($value)) {
                $this->addError($field, 'required');
                continue;
            }

            // Skip other validations if empty and not required
            if ($this->isEmpty($value)) {
                continue;
            }

            // Email
            if ($rule === 'email' && !is_email($value)) {
                $this->addError($field, 'email');
            }

            // URL
            if ($rule === 'url' && !filter_var($value, FILTER_VALIDATE_URL)) {
                $this->addError($field, 'url');
            }

            // Numeric
            if ($rule === 'numeric' && !is_numeric($value)) {
                $this->addError($field, 'numeric');
            }

            // Boolean
            if ($rule === 'boolean' && !is_bool($value) && !in_array($value, [0, 1, '0', '1', true, false], true)) {
                $this->addError($field, 'boolean');
            }

            // Min length/value
            if (strpos($rule, 'min:') === 0) {
                $min = (int) str_replace('min:', '', $rule);
                if (is_numeric($value) && $value < $min) {
                    $this->addError($field, 'min', ['min' => $min]);
                } elseif (is_string($value) && mb_strlen($value) < $min) {
                    $this->addError($field, 'min_length', ['min' => $min]);
                }
            }

            // Max length/value
            if (strpos($rule, 'max:') === 0) {
                $max = (int) str_replace('max:', '', $rule);
                if (is_numeric($value) && $value > $max) {
                    $this->addError($field, 'max', ['max' => $max]);
                } elseif (is_string($value) && mb_strlen($value) > $max) {
                    $this->addError($field, 'max_length', ['max' => $max]);
                }
            }

            // In array
            if (strpos($rule, 'in:') === 0) {
                $allowed = explode(',', str_replace('in:', '', $rule));
                if (!in_array($value, $allowed, true)) {
                    $this->addError($field, 'in', ['values' => implode(', ', $allowed)]);
                }
            }
        }
    }

    /**
     * Check if value is empty
     *
     * @param mixed $value Value to check
     * @return bool True if empty
     */
    protected function isEmpty($value): bool {
        return $value === null || $value === '' || $value === [];
    }

    /**
     * Add validation error
     *
     * @param string $field Field name
     * @param string $rule Rule that failed
     * @param array $params Parameters for error message
     */
    protected function addError(string $field, string $rule, array $params = []): void {
        $messages = $this->getMessages();
        $custom_key = "{$field}.{$rule}";

        // Check for custom message
        if (isset($messages[$custom_key])) {
            $message = $messages[$custom_key];
        } else {
            // Use default message
            $message = $this->getDefaultMessage($field, $rule, $params);
        }

        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Get default error message
     *
     * @param string $field Field name
     * @param string $rule Rule that failed
     * @param array $params Parameters for message
     * @return string Error message
     */
    protected function getDefaultMessage(string $field, string $rule, array $params = []): string {
        $field_label = ucfirst(str_replace('_', ' ', $field));
        $domain = $this->getTextDomain();

        $messages = [
            'required' => sprintf(__('%s is required.', $domain), $field_label),
            'email' => sprintf(__('%s must be a valid email address.', $domain), $field_label),
            'url' => sprintf(__('%s must be a valid URL.', $domain), $field_label),
            'numeric' => sprintf(__('%s must be a number.', $domain), $field_label),
            'boolean' => sprintf(__('%s must be true or false.', $domain), $field_label),
            'min' => sprintf(__('%s must be at least %d.', $domain), $field_label, $params['min'] ?? 0),
            'max' => sprintf(__('%s must not exceed %d.', $domain), $field_label, $params['max'] ?? 0),
            'min_length' => sprintf(__('%s must be at least %d characters.', $domain), $field_label, $params['min'] ?? 0),
            'max_length' => sprintf(__('%s must not exceed %d characters.', $domain), $field_label, $params['max'] ?? 0),
            'in' => sprintf(__('%s must be one of: %s.', $domain), $field_label, $params['values'] ?? ''),
        ];

        return $messages[$rule] ?? sprintf(__('Invalid value for %s.', $domain), $field_label);
    }

    /**
     * Get all validation errors
     *
     * @return array All errors
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Get first error for field
     *
     * @param string $field Field name
     * @return string|null First error message or null
     */
    public function getFirstError(string $field): ?string {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Check if field has error
     *
     * @param string $field Field name
     * @return bool True if has error
     */
    public function hasError(string $field): bool {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }

    /**
     * Check if any errors exist
     *
     * @return bool True if has any errors
     */
    public function hasErrors(): bool {
        return !empty($this->errors);
    }

    /**
     * Clear all errors
     */
    public function clearErrors(): void {
        $this->errors = [];
    }
}
