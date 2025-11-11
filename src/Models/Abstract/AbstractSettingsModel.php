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
 * @version     1.3.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Models/Abstract/AbstractSettingsModel.php
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
 * 1.3.0 - 2025-01-09
 * - FIXED: Cache not invalidated when option updated via WordPress Settings API
 * - Added constructor with update_option_{option_name} hook
 * - Added onOptionUpdated() callback for automatic cache clearing
 * - Cache now auto-clears on both manual save and WordPress Settings API
 * 1.2.0 - 2025-01-09
 * - FIXED: Cache key collision between plugins
 * - Added getCacheKey() method to generate unique cache key per plugin
 * - Cache key now uses option name: {option_name}_data
 * - Example: 'wpapp_platform_settings_data', 'wpc_settings_data', etc.
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

namespace WPAppCore\Models\Abstract;

use WPAppCore\Cache\Abstract\AbstractCacheManager;

defined('ABSPATH') || exit;

abstract class AbstractSettingsModel {

    /**
     * Constructor
     * Registers hooks for cache invalidation
     */
    public function __construct() {
        // Register hooks to clear cache when option is updated/added via WordPress Settings API
        $optionName = $this->getOptionName();

        // Hook for update_option (when option already exists)
        add_action('update_option_' . $optionName, [$this, 'onOptionUpdated'], 10, 3);

        // Hook for add_option (when option doesn't exist yet)
        // Note: add_option only passes 2 params ($option, $value), not ($old, $new, $option)
        add_action('add_option_' . $optionName, [$this, 'onOptionAdded'], 10, 2);
    }

    /**
     * Hook callback when option is updated
     * Clears cache automatically when WordPress updates the option
     *
     * @param mixed $old_value Old option value
     * @param mixed $new_value New option value
     * @param string $option Option name
     */
    public function onOptionUpdated($old_value, $new_value, $option): void {
        error_log('[AbstractSettingsModel] onOptionUpdated() called for option: ' . $option);
        error_log('[AbstractSettingsModel] Old company_name: ' . (is_array($old_value) ? ($old_value['company_name'] ?? 'NOT SET') : 'NOT ARRAY'));
        error_log('[AbstractSettingsModel] New company_name: ' . (is_array($new_value) ? ($new_value['company_name'] ?? 'NOT SET') : 'NOT ARRAY'));
        error_log('[AbstractSettingsModel] Backtrace: ' . wp_debug_backtrace_summary());
        $this->clearCache();
        error_log('[AbstractSettingsModel] Cache cleared for option: ' . $option);
    }

    /**
     * Hook callback when option is added (first time)
     * Clears cache automatically when WordPress adds the option
     *
     * @param string $option Option name
     * @param mixed $value Option value
     */
    public function onOptionAdded($option, $value): void {
        error_log('[AbstractSettingsModel] onOptionAdded() called for option: ' . $option);
        error_log('[AbstractSettingsModel] New company_name: ' . (is_array($value) ? ($value['company_name'] ?? 'NOT SET') : 'NOT ARRAY'));
        error_log('[AbstractSettingsModel] Backtrace: ' . wp_debug_backtrace_summary());
        $this->clearCache();
        error_log('[AbstractSettingsModel] Cache cleared for option: ' . $option);
    }

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
     * Get cache key for settings
     * Uses option name to create unique cache key per plugin
     *
     * @return string Unique cache key
     */
    protected function getCacheKey(): string {
        return $this->getOptionName() . '_data';
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
        $cacheKey = $this->getCacheKey();

        error_log('[AbstractSettingsModel] getSettings() called');
        error_log('[AbstractSettingsModel] Cache key: ' . $cacheKey);

        // Try cache first (using cache manager)
        $settings = $cacheManager->get($cacheKey);

        // Check if cache returned valid data (not null and not false)
        if ($settings === null || $settings === false) {
            error_log('[AbstractSettingsModel] Cache miss - loading from DB');
            $optionName = $this->getOptionName();

            // Get from database
            $settings = get_option(
                $optionName,
                $this->getDefaultSettings()
            );

            error_log('[AbstractSettingsModel] Raw DB company_name: ' . (is_array($settings) ? ($settings['company_name'] ?? 'NOT SET') : 'NOT ARRAY'));

            // Ensure we have an array
            if (!is_array($settings)) {
                $settings = $this->getDefaultSettings();
            }

            // Merge with defaults to ensure all keys exist
            // This handles new settings added in updates
            $settings = wp_parse_args($settings, $this->getDefaultSettings());

            error_log('[AbstractSettingsModel] After merge company_name: ' . ($settings['company_name'] ?? 'NOT SET'));

            // Cache it (using cache manager)
            $cacheManager->set($cacheKey, $settings);
        } else {
            error_log('[AbstractSettingsModel] Cache hit - using cached data');
            error_log('[AbstractSettingsModel] Cached company_name: ' . (is_array($settings) ? ($settings['company_name'] ?? 'NOT SET') : 'NOT ARRAY'));
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
        $cacheKey = $this->getCacheKey();
        $result = update_option($this->getOptionName(), $settings);

        if ($result) {
            // Clear cache using AbstractCacheManager with specific key
            $this->getCacheManager()->delete($cacheKey);

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
        $cacheKey = $this->getCacheKey();
        $this->getCacheManager()->delete($cacheKey);
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
