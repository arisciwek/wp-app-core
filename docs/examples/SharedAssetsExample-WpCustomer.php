<?php
/**
 * EXAMPLE: Using Shared Assets from wp-customer plugin
 *
 * Path: This is an EXAMPLE file showing how wp-customer plugin
 *       can use shared assets from wp-app-core
 *
 * Real implementation would be in:
 * /wp-customer/src/Controllers/Assets/Strategies/SettingsPageAssets.php
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Assets/Strategies
 * @version     1.0.0
 * @author      arisciwek
 */

namespace WPCustomer\Controllers\Assets\Strategies;

use WPCustomer\Controllers\Assets\AssetStrategyInterface;
use WPAppCore\Controllers\Assets\Strategies\SharedSettingsAssets;

class SettingsPageAssets implements AssetStrategyInterface {

    /**
     * Check if should load
     */
    public function should_load(): bool {
        // Load on customer settings page
        return isset($_GET['page']) && $_GET['page'] === 'wp-customer-settings';
    }

    /**
     * Enqueue styles
     */
    public function enqueue_styles(): void {
        $current_tab = $this->get_current_tab();

        // Base settings CSS
        wp_enqueue_style(
            'wpc-settings-base',
            WP_CUSTOMER_PLUGIN_URL . 'assets/css/settings/settings-base.css',
            [],
            WP_CUSTOMER_VERSION
        );

        // ========================================
        // USE SHARED ASSETS FROM wp-app-core
        // ========================================

        switch ($current_tab) {
            case 'security-policy':
            case 'security-session':
            case 'security-authentication':
            case 'demo-data':
                // These tabs share same styling with wp-app-core
                // ✅ NO DUPLICATION - Use shared CSS!
                if (SharedSettingsAssets::has_shared_asset($current_tab, 'style')) {
                    SharedSettingsAssets::enqueue_shared_tab_style(
                        $current_tab,
                        'wpc-shared-' . $current_tab
                    );
                    error_log('[WP Customer] Using shared CSS for tab: ' . $current_tab);
                }
                break;

            case 'customer-profile':
            case 'customer-settings':
                // Customer-specific tabs - load plugin-specific CSS
                wp_enqueue_style(
                    'wpc-' . $current_tab,
                    WP_CUSTOMER_PLUGIN_URL . 'assets/css/settings/' . $current_tab . '-tab-style.css',
                    [],
                    WP_CUSTOMER_VERSION
                );
                error_log('[WP Customer] Using plugin-specific CSS for tab: ' . $current_tab);
                break;

            default:
                // Try shared first, fallback to plugin-specific
                if (SharedSettingsAssets::has_shared_asset($current_tab, 'style')) {
                    SharedSettingsAssets::enqueue_shared_tab_style(
                        $current_tab,
                        'wpc-shared-' . $current_tab
                    );
                } else {
                    $this->enqueue_custom_tab_style($current_tab);
                }
        }
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts(): void {
        $current_tab = $this->get_current_tab();

        // Base settings JS
        wp_enqueue_script(
            'wpc-settings-base',
            WP_CUSTOMER_PLUGIN_URL . 'assets/js/settings/settings-script.js',
            ['jquery'],
            WP_CUSTOMER_VERSION,
            true
        );

        // Localize for AJAX
        wp_localize_script('wpc-settings-base', 'wpcCustomerSettings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpc_nonce'),
            'currentTab' => $current_tab,
        ]);

        // ========================================
        // USE SHARED SCRIPTS FROM wp-app-core
        // ========================================

        if (in_array($current_tab, ['security-policy', 'security-session', 'demo-data'])) {
            // Use shared reset handler script
            if (SharedSettingsAssets::has_shared_asset($current_tab, 'script')) {
                SharedSettingsAssets::enqueue_shared_tab_script(
                    $current_tab,
                    'wpc-shared-' . $current_tab . '-script',
                    ['jquery', 'wpc-settings-base']
                );
                error_log('[WP Customer] Using shared JS for tab: ' . $current_tab);
            }
        } else {
            // Plugin-specific scripts
            $this->enqueue_custom_tab_script($current_tab);
        }
    }

    /**
     * Get current tab
     */
    private function get_current_tab(): string {
        return isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
    }

    /**
     * Enqueue custom tab style (plugin-specific)
     */
    private function enqueue_custom_tab_style(string $tab): void {
        $file = WP_CUSTOMER_PLUGIN_DIR . 'assets/css/settings/' . $tab . '-tab-style.css';

        if (file_exists($file)) {
            wp_enqueue_style(
                'wpc-' . $tab,
                WP_CUSTOMER_PLUGIN_URL . 'assets/css/settings/' . $tab . '-tab-style.css',
                [],
                WP_CUSTOMER_VERSION
            );
        }
    }

    /**
     * Enqueue custom tab script (plugin-specific)
     */
    private function enqueue_custom_tab_script(string $tab): void {
        $file = WP_CUSTOMER_PLUGIN_DIR . 'assets/js/settings/' . $tab . '-tab-script.js';

        if (file_exists($file)) {
            wp_enqueue_script(
                'wpc-' . $tab . '-script',
                WP_CUSTOMER_PLUGIN_URL . 'assets/js/settings/' . $tab . '-tab-script.js',
                ['jquery', 'wpc-settings-base'],
                WP_CUSTOMER_VERSION,
                true
            );
        }
    }

    /**
     * Get strategy name
     */
    public function get_strategy_name(): string {
        return 'customer_settings_page';
    }
}

/**
 * ============================================
 * BENEFITS COMPARISON
 * ============================================
 *
 * WITHOUT SHARED ASSETS (Old Way):
 * ---------------------------------
 * wp-customer/assets/css/settings/security-policy-tab-style.css      (5KB)
 * wp-customer/assets/css/settings/security-session-tab-style.css     (4KB)
 * wp-customer/assets/css/settings/demo-data-tab-style.css            (3.5KB)
 * Total: 12.5KB + maintenance burden
 *
 * WITH SHARED ASSETS (New Way):
 * ------------------------------
 * Uses: WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/*.css'
 * Total: 0KB duplication + auto-updates
 *
 * CODE REDUCTION: 100% for shared tabs
 * MAINTENANCE: 1 place to update vs 4+ places
 * CONSISTENCY: Guaranteed same styling across all plugins
 *
 * ============================================
 * USAGE PATTERN SUMMARY
 * ============================================
 *
 * 1. Check if shared asset exists:
 *    SharedSettingsAssets::has_shared_asset($tab, 'style')
 *
 * 2. If yes, use shared asset:
 *    SharedSettingsAssets::enqueue_shared_tab_style($tab, $handle)
 *
 * 3. If no, use plugin-specific:
 *    wp_enqueue_style($handle, WP_CUSTOMER_PLUGIN_URL . 'assets/css/...')
 *
 * This pattern ensures:
 * ✅ Zero duplication for common tabs
 * ✅ Plugin-specific assets for custom tabs
 * ✅ Automatic fallback mechanism
 * ✅ Easy to maintain and update
 */
