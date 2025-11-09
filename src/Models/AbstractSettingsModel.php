<?php
/**
 * Abstract Settings Model
 *
 * Base class for settings models providing shared implementation
 * for common settings operations: get, save, cache management.
 * Eliminates code duplication across plugin settings models.
 *
 * @package     WPAppCore
 * @subpackage  Models
 * @version     1.2.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Models/AbstractSettingsModel.php
 *
 * Description: Abstract base class for all settings models.
 *              Provides concrete implementations of commonly duplicated
 *              methods like getSettings(), saveSettings(), and cache management.
 *              Includes hook system integration for settings updates.
 *              UPDATED: Now uses AbstractCacheManager (TODO-1202) instead of wp_cache_*.
 *
 * Dependencies:
 * - AbstractCacheManager (from TODO-1202) - Centralized cache management
 * - WordPress functions (get_option, update_option)
 * - WordPress hooks (do_action)
 *
 * Usage:
 * ```php
 * use WPCustomer\Cache\CustomerCacheManager;
 *
 * class CustomerSettingsModel extends AbstractSettingsModel {
 *     private CustomerCacheManager $cacheManager;
 *
 *     public function __construct() {
 *         $this->cacheManager = new CustomerCacheManager();
 *     }
 *
 *     protected function getOptionName(): string {
 *         return 'wpc_settings';
 *     }
 *
 *     protected function getCacheManager(): AbstractCacheManager {
 *         return $this->cacheManager;
 *     }
 *
 *     protected function getDefaultSettings(): array {
 *         return [
 *             'site_name' => '',
 *             'items_per_page' => 10,
 *             // ... other defaults
 *         ];
 *     }
 *
 *     // ✅ getSettings() - inherited FREE with AbstractCacheManager!
 *     // ✅ saveSettings() - inherited FREE with hooks!
 *     // ✅ clearCache() - inherited FREE!
 *     // ✅ getSetting() - inherited FREE!
 * }
 * ```
 *
 * Benefits:
 * - ~150 lines code reduction per settings model
 * - Centralized caching via AbstractCacheManager
 * - Standardized hook patterns
 * - Type-safe method signatures
 * - Single source of truth for settings operations
 *
 * Hook Patterns:
 * - After save: do_action('{plugin}_settings_updated', $settings)
 *   Example: do_action('wpc_settings_updated', $settings)
 *
 * Changelog:
 * 1.1.0 - 2025-01-09 (TODO-1203)
 * - BREAKING: Changed from wp_cache_* to AbstractCacheManager
 * - Removed getCacheKey() and getCacheGroup() abstract methods
 * - Added getCacheManager() abstract method
 * - Cache now managed via AbstractCacheManager->get/set/delete
 * 1.0.0 - 2025-11-09 (TODO-1203)
 * - Initial implementation
 * - Core methods: getSettings(), saveSettings(), getSetting()
 * - Cache management: clearCache()
 * - Hook system integration
 * - Default sanitization: sanitizeSettings()
 */

namespace WPAppCore\Models;

use WPAppCore\Cache\Abstract\AbstractCacheManager;

defined('ABSPATH') || exit;

abstract class AbstractSettingsModel {

    /**
     * Get option name for WordPress options table
     *
     * @return string Option name (e.g., 'wpc_settings', 'wpa_settings')
     */
    abstract protected function getOptionName(): string;

    /**
     * Get cache manager instance
     *
     * Must return plugin-specific cache manager with get/set/delete methods.
     * Can extend AbstractCacheManager or provide compatible interface.
     *
     * @return object Cache manager instance with get/set/delete methods
     */
    abstract protected function getCacheManager();

    /**
     * Get default settings array
     *
     * @return array Default settings with all keys and default values
     */
    abstract protected function getDefaultSettings(): array;

    /**
     * Public wrapper to get default settings
     * Allows external classes (like controllers) to access defaults
     *
     * @return array Default settings array
     */
    public function getDefaults(): array {
        return $this->getDefaultSettings();
    }

    /**
     * Get all settings with defaults merged
     *
     * Uses AbstractCacheManager for caching.
     * Merges saved settings with defaults to ensure all keys exist.
     *
     * @return array Complete settings array
     */
    public function getSettings(): array {
        $cacheManager = $this->getCacheManager();

        // Try cache first (using cache manager)
        $settings = $cacheManager->get('settings');

        // Check if cache returned valid data (not null and not false)
        if ($settings === null || $settings === false) {
            // Get from database
            $settings = get_option(
                $this->getOptionName(),
                $this->getDefaultSettings()
            );

            // Ensure we have an array
            if (!is_array($settings)) {
                $settings = $this->getDefaultSettings();
            }

            // Merge with defaults to ensure all keys exist
            // This handles new settings added in updates
            $settings = wp_parse_args($settings, $this->getDefaultSettings());

            // Cache it (using cache manager)
            $cacheManager->set('settings', $settings);
        }

        return $settings;
    }

    /**
     * Get single setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Setting value or default
     */
    public function getSetting(string $key, $default = null) {
        $settings = $this->getSettings();
        return $settings[$key] ?? $default;
    }

    /**
     * Save settings to database
     *
     * Automatically clears cache via AbstractCacheManager and fires update hook.
     *
     * @param array $settings Settings to save
     * @return bool True on success, false on failure
     */
    public function saveSettings(array $settings): bool {
        $result = update_option($this->getOptionName(), $settings);

        if ($result) {
            // Clear cache using AbstractCacheManager
            $this->getCacheManager()->delete('settings');

            // Fire hook for plugins to react to settings changes
            // Example: 'wpc_settings_updated'
            $hook_name = str_replace('_settings', '', $this->getOptionName()) . '_settings_updated';
            do_action($hook_name, $settings);
        }

        return $result;
    }

    /**
     * Update single setting
     *
     * @param string $key Setting key
     * @param mixed $value New value
     * @return bool True on success, false on failure
     */
    public function updateSetting(string $key, $value): bool {
        $settings = $this->getSettings();
        $settings[$key] = $value;
        return $this->saveSettings($settings);
    }

    /**
     * Clear settings cache
     *
     * Uses AbstractCacheManager to delete cached settings.
     *
     * @return void
     */
    public function clearCache(): void {
        $this->getCacheManager()->delete('settings');
    }

    /**
     * Reset settings to defaults
     *
     * @return bool True on success, false on failure
     */
    public function resetToDefaults(): bool {
        return $this->saveSettings($this->getDefaultSettings());
    }

    /**
     * Sanitize settings array
     *
     * Base implementation provides basic sanitization.
     * Child classes can override for custom sanitization logic.
     *
     * @param array $input Raw input data
     * @return array Sanitized data
     */
    public function sanitizeSettings(array $input): array {
        $sanitized = [];

        foreach ($input as $key => $value) {
            if (is_array($value)) {
                // Recursive sanitization for nested arrays
                $sanitized[$key] = $this->sanitizeSettings($value);
            } elseif (is_bool($value)) {
                // Preserve boolean type
                $sanitized[$key] = (bool) $value;
            } elseif (is_numeric($value)) {
                // Preserve numeric type (int or float)
                $sanitized[$key] = $value + 0;
            } elseif (filter_var($value, FILTER_VALIDATE_URL)) {
                // Sanitize URLs
                $sanitized[$key] = esc_url_raw($value);
            } elseif (is_email($value)) {
                // Sanitize emails
                $sanitized[$key] = sanitize_email($value);
            } else {
                // Default: sanitize as text
                $sanitized[$key] = sanitize_text_field($value);
            }
        }

        return $sanitized;
    }

    /**
     * Check if setting exists
     *
     * @param string $key Setting key
     * @return bool True if exists, false otherwise
     */
    public function hasSetting(string $key): bool {
        $settings = $this->getSettings();
        return isset($settings[$key]);
    }

    /**
     * Get multiple settings by keys
     *
     * @param array $keys Array of setting keys
     * @return array Array of key => value pairs
     */
    public function getSettingsByKeys(array $keys): array {
        $settings = $this->getSettings();
        $result = [];

        foreach ($keys as $key) {
            if (isset($settings[$key])) {
                $result[$key] = $settings[$key];
            }
        }

        return $result;
    }
}
