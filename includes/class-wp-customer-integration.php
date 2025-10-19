<?php
/**
 * WP Customer Integration Class
 *
 * @package     WP_App_Core
 * @subpackage  Integrations
 * @version     1.0.4
 * @author      arisciwek
 *
 * Path: /wp-app-core/includes/class-wp-customer-integration.php
 *
 * Description: Extends WP Customer plugin functionality to support platform roles.
 *              Uses hook filters to grant access to platform staff.
 *              Implements caching for optimal performance.
 *
 * Dependencies:
 * - WP Customer plugin (active)
 * - Platform roles created (TODO-1208)
 * - Platform capabilities set (TODO-1209)
 * - WP Customer hooks refactored (wp-customer TODO-2165)
 *
 * Changelog:
 * 1.0.4 - 2025-10-19 (TODO-1210)
 * - Initial implementation
 * - Added platform role access filters for customer entity
 * - Added caching mechanism (5 minutes expiry)
 * - Support for view, edit, delete permissions
 * - Future: Branch, Employee, Invoice entity support
 */

namespace WPAppCore\Integrations;

class WPCustomerIntegration {

    /**
     * Cache group for platform access checks
     */
    private const CACHE_GROUP = 'wp_app_core_platform_access';

    /**
     * Cache expiry time (5 minutes)
     */
    private const CACHE_EXPIRY = 300;

    /**
     * Constructor
     */
    public function __construct() {
        // Register filters after all plugins are loaded
        add_action('plugins_loaded', [$this, 'registerFilters'], 20);
    }

    /**
     * Register hook filters for WP Customer integration
     *
     * @return void
     */
    public function registerFilters(): void {
        // Only register if wp-customer is active
        if (!$this->isWPCustomerActive()) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('WP App Core: WP Customer plugin not active, skipping integration');
            }
            return;
        }

        // Customer entity filters (TODO-1210 Phase 1)
        add_filter('wp_customer_user_can_view_customer', [$this, 'checkPlatformViewAccess'], 10, 2);
        add_filter('wp_customer_user_can_edit_customer', [$this, 'checkPlatformEditAccess'], 10, 2);
        add_filter('wp_customer_user_can_delete_customer', [$this, 'checkPlatformDeleteAccess'], 10, 2);

        // Future: Branch, Employee, Invoice filters (TODO-1210 Phase 2-4)
        // add_filter('wp_customer_user_can_view_branch', [$this, 'checkPlatformViewAccess'], 10, 2);
        // add_filter('wp_customer_user_can_view_employee', [$this, 'checkPlatformViewAccess'], 10, 2);
        // add_filter('wp_customer_user_can_view_invoice', [$this, 'checkPlatformViewAccess'], 10, 2);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WP App Core: WP Customer integration filters registered');
        }
    }

    /**
     * Check if platform user has view access to customer
     *
     * @param bool $has_access Current access status from wp-customer
     * @param array $relation User relation data from CustomerValidator
     * @return bool True if platform user has access, original value otherwise
     */
    public function checkPlatformViewAccess(bool $has_access, array $relation): bool {
        // If already has access via standard checks, return true
        if ($has_access) {
            return true;
        }

        // Check if user has platform role with view capability
        $platform_access = $this->hasPlatformCapability('view_customer_detail');

        if (defined('WP_DEBUG') && WP_DEBUG && $platform_access) {
            error_log(sprintf(
                'WP App Core: Platform user %d granted view access to customer',
                get_current_user_id()
            ));
        }

        return $platform_access;
    }

    /**
     * Check if platform user has edit access to customer
     *
     * @param bool $has_access Current access status from wp-customer
     * @param array $relation User relation data from CustomerValidator
     * @return bool True if platform user has access, original value otherwise
     */
    public function checkPlatformEditAccess(bool $has_access, array $relation): bool {
        // If already has access via standard checks, return true
        if ($has_access) {
            return true;
        }

        // Check if user has platform role with edit capability
        $platform_access = $this->hasPlatformCapability('edit_all_customers');

        if (defined('WP_DEBUG') && WP_DEBUG && $platform_access) {
            error_log(sprintf(
                'WP App Core: Platform user %d granted edit access to customer',
                get_current_user_id()
            ));
        }

        return $platform_access;
    }

    /**
     * Check if platform user has delete access to customer
     *
     * @param bool $has_access Current access status from wp-customer
     * @param array $relation User relation data from CustomerValidator
     * @return bool True if platform user has access, original value otherwise
     */
    public function checkPlatformDeleteAccess(bool $has_access, array $relation): bool {
        // If already has access via standard checks, return true
        if ($has_access) {
            return true;
        }

        // Check if user has platform role with delete capability
        $platform_access = $this->hasPlatformCapability('delete_customer');

        if (defined('WP_DEBUG') && WP_DEBUG && $platform_access) {
            error_log(sprintf(
                'WP App Core: Platform user %d granted delete access to customer',
                get_current_user_id()
            ));
        }

        return $platform_access;
    }

    /**
     * Check if current user has platform capability (with caching)
     *
     * @param string $capability WordPress capability to check
     * @return bool True if user has capability and is platform user
     */
    private function hasPlatformCapability(string $capability): bool {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return false;
        }

        // Check cache first
        $cache_key = "user_{$user_id}_cap_{$capability}";
        $cached = wp_cache_get($cache_key, self::CACHE_GROUP);

        if ($cached !== false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    'WP App Core: Cache HIT for user %d capability %s = %s',
                    $user_id,
                    $capability,
                    $cached ? 'true' : 'false'
                ));
            }
            return (bool) $cached;
        }

        // Cache miss - perform checks
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'WP App Core: Cache MISS for user %d capability %s',
                $user_id,
                $capability
            ));
        }

        // Step 1: Check if user has the required capability
        $has_capability = current_user_can($capability);

        // Step 2: Verify user is actually a platform user
        if ($has_capability) {
            $has_capability = $this->isPlatformUser($user_id);
        }

        // Cache the result
        wp_cache_set($cache_key, $has_capability, self::CACHE_GROUP, self::CACHE_EXPIRY);

        return $has_capability;
    }

    /**
     * Check if user has platform role
     *
     * @param int $user_id User ID to check
     * @return bool True if user has any platform_* role
     */
    private function isPlatformUser(int $user_id): bool {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        // Platform roles pattern: platform_*
        $platform_roles = array_filter($user->roles, function($role) {
            return strpos($role, 'platform_') === 0;
        });

        $is_platform = !empty($platform_roles);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'WP App Core: User %d is platform user: %s (roles: %s)',
                $user_id,
                $is_platform ? 'YES' : 'NO',
                implode(', ', $user->roles)
            ));
        }

        return $is_platform;
    }

    /**
     * Check if WP Customer plugin is active
     *
     * @return bool True if wp-customer plugin is active
     */
    private function isWPCustomerActive(): bool {
        // Check if CustomerModel class exists (wp-customer is loaded)
        return class_exists('WPCustomer\\Models\\Customer\\CustomerModel');
    }

    /**
     * Clear cache for specific user
     *
     * @param int $user_id User ID
     * @return void
     */
    public static function clearUserCache(int $user_id): void {
        // Clear all capability caches for this user
        $capabilities = [
            'view_customer_detail',
            'view_customer_list',
            'edit_all_customers',
            'edit_own_customer',
            'delete_customer',
            'add_customer',
            // Add more as needed
        ];

        foreach ($capabilities as $cap) {
            wp_cache_delete("user_{$user_id}_cap_{$cap}", self::CACHE_GROUP);
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'WP App Core: Cleared capability cache for user %d',
                $user_id
            ));
        }
    }

    /**
     * Clear all platform access cache
     *
     * @return void
     */
    public static function clearAllCache(): void {
        // WordPress object cache doesn't support group flush
        // Cache will expire after 5 minutes automatically
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WP App Core: Cache clear requested (will expire automatically)');
        }
    }
}
