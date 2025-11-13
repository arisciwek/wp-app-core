<?php
/**
 * Abstract Permissions Controller
 *
 * @package     WP_App_Core
 * @subpackage  Controllers/Abstract
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Abstract/AbstractPermissionsController.php
 *
 * Description: Base controller untuk permission management across all plugins.
 *              Provides standard AJAX-based permission save/reset functionality.
 *              Child plugins only need to define capabilities and role mappings.
 *
 * Usage in Child Plugin:
 * ```php
 * class CustomerPermissionsController extends AbstractPermissionsController {
 *     protected function getPluginSlug(): string { return 'wp-customer'; }
 *     protected function getPluginPrefix(): string { return 'customer'; }
 *     protected function getRoleManagerClass(): string { return 'WP_Customer_Role_Manager'; }
 *     protected function getModel(): AbstractPermissionsModel { return new CustomerPermissionModel(); }
 *     protected function getValidator(): AbstractPermissionsValidator { return new CustomerPermissionValidator(); }
 * }
 * ```
 *
 * Changelog:
 * 1.0.0 - 2025-01-12 (TODO-1206)
 * - Initial creation
 * - AJAX-based save/reset handlers
 * - Shared asset enqueuing
 * - View model preparation
 */

namespace WPAppCore\Controllers\Abstract;

use WPAppCore\Models\Abstract\AbstractPermissionsModel;
use WPAppCore\Validators\Abstract\AbstractPermissionsValidator;

abstract class AbstractPermissionsController {

    protected AbstractPermissionsModel $model;
    protected AbstractPermissionsValidator $validator;

    /**
     * Constructor - initialize model and validator
     */
    public function __construct() {
        $this->model = $this->getModel();
        $this->validator = $this->getValidator();
    }

    /**
     * Abstract methods - MUST be implemented by child class
     */
    abstract protected function getPluginSlug(): string;
    abstract protected function getPluginPrefix(): string;
    abstract protected function getRoleManagerClass(): string;
    abstract protected function getModel(): AbstractPermissionsModel;
    abstract protected function getValidator(): AbstractPermissionsValidator;

    /**
     * Initialize controller
     * Called from plugin's main initialization
     * Registers AJAX handlers only - child plugin handles asset enqueuing
     */
    public function init(): void {
        $this->registerAjaxHandlers();
    }

    /**
     * Register AJAX handlers for save and reset
     * Pattern: {prefix}_save_permissions and {prefix}_reset_permissions
     */
    private function registerAjaxHandlers(): void {
        $prefix = $this->getPluginPrefix();
        add_action("wp_ajax_{$prefix}_save_permissions", [$this, 'handleSavePermissions']);
        add_action("wp_ajax_{$prefix}_reset_permissions", [$this, 'handleResetPermissions']);
    }

    /**
     * Handle AJAX save permissions request
     * Saves single role capability change (instant save on checkbox click)
     */
    public function handleSavePermissions(): void {
        try {
            // Verify nonce
            $nonce = $_POST['nonce'] ?? '';
            if (!wp_verify_nonce($nonce, $this->getPluginPrefix() . '_nonce')) {
                wp_send_json_error([
                    'message' => __('Security check failed', 'wp-app-core')
                ]);
                return;
            }

            // Check permission using validator
            if (!$this->validator->userCanManagePermissions()) {
                wp_send_json_error([
                    'message' => __('Permission denied', 'wp-app-core')
                ]);
                return;
            }

            // Get request data
            $role = sanitize_key($_POST['role'] ?? '');
            $capability = sanitize_key($_POST['capability'] ?? '');
            $enabled = intval($_POST['enabled'] ?? 0);

            // Prepare data for validation
            $data = [
                'role' => $role,
                'capability' => $capability,
                'enabled' => $enabled
            ];

            // Validate request
            $validation = $this->validator->validateSaveRequest($data);
            if (!$validation['valid']) {
                wp_send_json_error([
                    'message' => implode(', ', $validation['errors'])
                ]);
                return;
            }

            // Update role capability via model
            $capabilities = [$capability => $enabled];
            $success = $this->model->updateRoleCapabilities($role, $capabilities);

            if ($success) {
                wp_send_json_success([
                    'message' => __('Permission updated successfully', 'wp-app-core')
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Failed to update permission', 'wp-app-core')
                ]);
            }

        } catch (\Exception $e) {
            error_log('[AbstractPermissionsController] Save error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => __('An error occurred while saving', 'wp-app-core')
            ]);
        }
    }

    /**
     * Handle AJAX reset permissions request
     * Resets all permissions across all roles to default values
     */
    public function handleResetPermissions(): void {
        try {
            // Verify nonce
            $nonce = $_POST['nonce'] ?? '';
            if (!wp_verify_nonce($nonce, $this->getPluginPrefix() . '_reset_permissions')) {
                wp_send_json_error([
                    'message' => __('Security check failed', 'wp-app-core')
                ]);
                return;
            }

            // Check permission using validator
            if (!$this->validator->userCanManagePermissions()) {
                wp_send_json_error([
                    'message' => __('Permission denied', 'wp-app-core')
                ]);
                return;
            }

            // Validate reset request
            $validation = $this->validator->validateResetRequest();
            if (!$validation['valid']) {
                wp_send_json_error([
                    'message' => implode(', ', $validation['errors'])
                ]);
                return;
            }

            // Reset via model
            $success = $this->model->resetToDefault();

            if ($success) {
                wp_send_json_success([
                    'message' => __('Permissions reset to default successfully', 'wp-app-core')
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Failed to reset permissions', 'wp-app-core')
                ]);
            }

        } catch (\Exception $e) {
            error_log('[AbstractPermissionsController] Reset error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => __('An error occurred while resetting', 'wp-app-core')
            ]);
        }
    }

    /**
     * Enqueue shared CSS and JS from wp-app-core
     * Child plugins should call this method in their own enqueue hook
     * with proper page/tab checking
     *
     * Example in child plugin:
     * ```php
     * public function enqueueAssets($hook) {
     *     if ($hook !== 'toplevel_page_wp-customer-settings') return;
     *     if (($_GET['tab'] ?? '') !== 'permissions') return;
     *     parent::enqueueAssets($hook);
     * }
     * ```
     *
     * @param string $hook Current admin page hook
     */
    public function enqueueAssets(string $hook): void {
        $plugin_slug = $this->getPluginSlug();
        $plugin_prefix = $this->getPluginPrefix();

        // Allow child plugin to prevent asset loading via filter
        $should_load = apply_filters(
            "{$plugin_prefix}_should_load_permission_assets",
            true,
            $hook
        );

        if (!$should_load) {
            return;
        }

        // Enqueue shared CSS from wp-app-core
        wp_enqueue_style(
            'wpapp-permission-matrix',
            WP_APP_CORE_PLUGIN_URL . 'assets/css/permissions/wpapp-permission-matrix.css',
            [],
            WP_APP_CORE_VERSION
        );

        // Enqueue shared JS from wp-app-core
        wp_enqueue_script(
            'wpapp-permission-matrix',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/permissions/wpapp-permission-matrix.js',
            ['jquery', 'wp-modal'],
            WP_APP_CORE_VERSION,
            true
        );

        // Localize script with plugin-specific data
        wp_localize_script('wpapp-permission-matrix', 'wpappPermissions', [
            'pluginSlug' => $plugin_slug,
            'pluginPrefix' => $plugin_prefix,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce($plugin_prefix . '_nonce'),
            'resetNonce' => wp_create_nonce($plugin_prefix . '_reset_permissions'),
            'strings' => [
                'saving' => __('Saving...', 'wp-app-core'),
                'saved' => __('Saved', 'wp-app-core'),
                'error' => __('Error', 'wp-app-core'),
                'confirmReset' => __('Are you sure you want to reset all permissions to default?', 'wp-app-core'),
                'resetting' => __('Resetting...', 'wp-app-core')
            ]
        ]);
    }

    /**
     * Get view model data for permission matrix template
     * Prepares all data needed by shared template
     *
     * @return array View data for template
     */
    public function getViewModel(): array {
        $plugin_prefix = $this->getPluginPrefix();
        $plugin_slug = $this->getPluginSlug();

        // Get current permission tab
        $current_tab = isset($_GET['permission_tab']) ? sanitize_key($_GET['permission_tab']) : '';

        // Get capability groups
        $capability_groups = $this->model->getCapabilityGroups();

        // Set default tab if not set or invalid
        if (empty($current_tab) || !isset($capability_groups[$current_tab])) {
            $current_tab = array_key_first($capability_groups);
        }

        // Get current group
        $current_group = $capability_groups[$current_tab];

        // Get all capabilities
        $all_capabilities = $this->model->getAllCapabilities();

        // Get capability descriptions
        $capability_descriptions = $this->model->getCapabilityDescriptions();

        // Get displayed roles (plugin-specific roles)
        $displayed_roles = $this->getDisplayedRoles();

        // Get role capabilities matrix
        $role_matrix = $this->model->getRoleCapabilitiesMatrix();

        return [
            'plugin_prefix' => $plugin_prefix,
            'plugin_slug' => $plugin_slug,
            'text_domain' => $plugin_slug,
            'current_tab' => $current_tab,
            'capability_groups' => $capability_groups,
            'current_group' => $current_group,
            'all_capabilities' => $all_capabilities,
            'capability_labels' => $all_capabilities, // Labels are same as all_capabilities
            'capability_descriptions' => $capability_descriptions,
            'displayed_roles' => $displayed_roles,
            'role_matrix' => $role_matrix,
            'page_title' => $this->getPageTitle(),
            'page_description' => $this->getPageDescription()
        ];
    }

    /**
     * Get displayed roles for permission matrix
     * Filters to show only plugin-specific roles
     *
     * @return array Filtered roles array
     */
    protected function getDisplayedRoles(): array {
        $role_manager = $this->getRoleManagerClass();
        $all_roles = get_editable_roles();
        $plugin_role_slugs = $role_manager::getRoleSlugs();

        $displayed_roles = [];
        foreach ($plugin_role_slugs as $role_slug) {
            if (isset($all_roles[$role_slug])) {
                $displayed_roles[$role_slug] = $all_roles[$role_slug];
            }
        }

        return $displayed_roles;
    }

    /**
     * Get page title for permission matrix
     * Override in child class for custom title
     *
     * @return string Page title
     */
    protected function getPageTitle(): string {
        return __('Permission Management', 'wp-app-core');
    }

    /**
     * Get page description for permission matrix
     * Override in child class for custom description
     *
     * @return string Page description
     */
    protected function getPageDescription(): string {
        return __('Configure role capabilities for this plugin', 'wp-app-core');
    }

    /**
     * Get model instance
     * Accessible for child classes if needed
     *
     * @return AbstractPermissionsModel
     */
    public function getModelInstance(): AbstractPermissionsModel {
        return $this->model;
    }
}
