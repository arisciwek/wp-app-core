<?php
/**
 * Abstract Settings Controller
 *
 * Base controller class for plugin settings pages.
 * Eliminates code duplication across plugin settings controllers.
 *
 * @package     WPAppCore
 * @subpackage  Controllers
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/AbstractSettingsController.php
 *
 * Description: Abstract base class for all plugin settings controllers.
 *              Provides standardized settings page rendering, tab system,
 *              AJAX handlers, and asset management. Child classes only need
 *              to implement plugin-specific configuration.
 *
 * Dependencies:
 * - AbstractSettingsModel (for data management)
 * - AbstractSettingsValidator (for validation)
 * - WordPress Settings API
 *
 * Changelog:
 * 1.0.0 - 2025-01-09
 * - Initial implementation
 * - Tab system with hooks
 * - AJAX handlers for save/reset
 * - Asset management
 * - Plugin-specific hook integration
 */

namespace WPAppCore\Controllers;

use WPAppCore\Models\AbstractSettingsModel;
use WPAppCore\Validators\AbstractSettingsValidator;

defined('ABSPATH') || exit;

abstract class AbstractSettingsController {

    /**
     * @var AbstractSettingsModel Settings model instance
     */
    protected AbstractSettingsModel $model;

    /**
     * @var AbstractSettingsValidator Settings validator instance
     */
    protected AbstractSettingsValidator $validator;

    // ========================================
    // ABSTRACT METHODS (Must be implemented by child classes)
    // ========================================

    /**
     * Get plugin slug
     *
     * @return string Plugin slug, e.g., 'wp-customer', 'wp-agency'
     */
    abstract protected function getPluginSlug(): string;

    /**
     * Get plugin prefix for hooks and options
     *
     * @return string Plugin prefix, e.g., 'wpc', 'wpa', 'wpapp'
     */
    abstract protected function getPluginPrefix(): string;

    /**
     * Get settings page slug
     *
     * @return string Settings page slug
     */
    abstract protected function getSettingsPageSlug(): string;

    /**
     * Get capability required to manage settings
     *
     * @return string WordPress capability
     */
    abstract protected function getSettingsCapability(): string;

    /**
     * Get default tabs for this plugin
     *
     * @return array Tab slug => Tab label
     */
    abstract protected function getDefaultTabs(): array;

    /**
     * Get model instance
     *
     * @return AbstractSettingsModel
     */
    abstract protected function getModel(): AbstractSettingsModel;

    /**
     * Get validator instance
     *
     * @return AbstractSettingsValidator
     */
    abstract protected function getValidator(): AbstractSettingsValidator;

    // ========================================
    // CONCRETE METHODS (Shared implementation)
    // ========================================

    /**
     * Constructor
     */
    public function __construct() {
        $this->model = $this->getModel();
        $this->validator = $this->getValidator();
        $this->init();
    }

    /**
     * Initialize controller
     */
    public function init(): void {
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        $this->registerAjaxHandlers();
    }

    /**
     * Public getter for model instance
     * Allows external classes to access the model
     *
     * @return AbstractSettingsModel
     */
    public function getModelInstance(): AbstractSettingsModel {
        return $this->model;
    }

    /**
     * Register settings with WordPress
     */
    public function registerSettings(): void {
        // Get option name from model to ensure consistency
        $optionName = $this->getOptionName();

        register_setting(
            $optionName,
            $optionName,
            [
                'sanitize_callback' => [$this->model, 'sanitizeSettings'],
                'default' => $this->model->getDefaults()
            ]
        );
    }

    /**
     * Get option name from model
     *
     * @return string Option name
     */
    protected function getOptionName(): string {
        // Use reflection to call protected method
        $reflection = new \ReflectionClass($this->model);
        $method = $reflection->getMethod('getOptionName');
        $method->setAccessible(true);
        return $method->invoke($this->model);
    }

    /**
     * Register AJAX handlers
     */
    protected function registerAjaxHandlers(): void {
        $prefix = $this->getPluginPrefix();
        add_action('wp_ajax_' . $prefix . '_save_settings', [$this, 'handleSaveSettings']);
        add_action('wp_ajax_' . $prefix . '_reset_settings', [$this, 'handleResetSettings']);
    }

    /**
     * Render settings page
     */
    public function renderPage(): void {
        if (!current_user_can($this->getSettingsCapability())) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wp-app-core'));
        }

        $tabs = $this->getTabs();
        $current_tab = $this->getCurrentTab();
        $page_slug = $this->getSettingsPageSlug();
        $plugin_prefix = $this->getPluginPrefix();

        // Load main settings page template
        include WPAPP_CORE_PATH . 'src/Templates/Settings/settings-page.php';
    }

    /**
     * Load tab view
     *
     * @param string $tab Tab slug
     */
    public function loadTabView(string $tab): void {
        $prefix = $this->getPluginPrefix();

        // Hook: Allow plugins to provide custom tab template path
        $custom_tab_path = apply_filters(
            $prefix . '_settings_tab_path',
            '',
            $tab
        );

        if ($custom_tab_path && file_exists($custom_tab_path)) {
            include $custom_tab_path;
            return;
        }

        // Try core tab template
        $core_tab = WPAPP_CORE_PATH . "src/Templates/Settings/tab-{$tab}.php";
        if (file_exists($core_tab)) {
            include $core_tab;
            return;
        }

        // Hook: Custom tab content via action
        do_action($prefix . '_settings_tab_content_' . $tab);
    }

    /**
     * Get all tabs (default + custom via hooks)
     *
     * @return array Tab slug => Tab label
     */
    public function getTabs(): array {
        $tabs = $this->getDefaultTabs();

        // Hook: Allow extending tabs
        return apply_filters(
            $this->getPluginPrefix() . '_settings_tabs',
            $tabs
        );
    }

    /**
     * Get current active tab
     *
     * @return string Current tab slug
     */
    public function getCurrentTab(): string {
        $tabs = $this->getTabs();
        $tab = $_GET['tab'] ?? '';

        // Return first tab if current tab is invalid
        if (!isset($tabs[$tab])) {
            $firstTab = array_key_first($tabs);
            return $firstTab !== null ? $firstTab : 'general';
        }

        return $tab;
    }

    /**
     * Enqueue assets for settings page
     */
    public function enqueueAssets(): void {
        // Only enqueue on settings page
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, $this->getSettingsPageSlug()) === false) {
            return;
        }

        $current_tab = $this->getCurrentTab();
        $plugin_prefix = $this->getPluginPrefix();

        // Enqueue base settings styles from wp-app-core
        wp_enqueue_style(
            'wpapp-settings-base',
            WP_APP_CORE_PLUGIN_URL . 'assets/css/settings/settings-style.css',
            [],
            WP_APP_CORE_VERSION
        );

        // Enqueue base settings scripts from wp-app-core
        wp_enqueue_script(
            'wpapp-settings-base',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/settings/settings-script.js',
            ['jquery'],
            WP_APP_CORE_VERSION,
            true
        );

        // Localize script with plugin-specific data
        wp_localize_script('wpapp-settings-base', 'wpappSettings', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce($plugin_prefix . '_nonce'),
            'prefix' => $plugin_prefix,
            'current_tab' => $current_tab,
            'i18n' => [
                'saving' => __('Saving...', 'wp-app-core'),
                'saved' => __('Settings saved successfully.', 'wp-app-core'),
                'error' => __('Error saving settings.', 'wp-app-core'),
                'confirm_reset' => __('Are you sure you want to reset settings to defaults?', 'wp-app-core'),
            ]
        ]);

        // Hook: Allow plugins to enqueue custom assets
        do_action("{$plugin_prefix}_settings_enqueue_assets", $current_tab);
    }

    /**
     * Handle AJAX save settings
     */
    public function handleSaveSettings(): void {
        $prefix = $this->getPluginPrefix();

        check_ajax_referer($prefix . '_nonce', 'nonce');

        if (!current_user_can($this->getSettingsCapability())) {
            wp_send_json_error([
                'message' => __('Permission denied.', 'wp-app-core')
            ]);
        }

        $data = $_POST['settings'] ?? [];

        // Validate
        if (!$this->validator->validate($data)) {
            wp_send_json_error([
                'message' => __('Validation failed.', 'wp-app-core'),
                'errors' => $this->validator->getErrors()
            ]);
        }

        // Save
        $saved = $this->model->saveSettings($data);

        if ($saved) {
            wp_send_json_success([
                'message' => __('Settings saved successfully.', 'wp-app-core')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Failed to save settings.', 'wp-app-core')
            ]);
        }
    }

    /**
     * Handle AJAX reset settings
     */
    public function handleResetSettings(): void {
        $prefix = $this->getPluginPrefix();

        check_ajax_referer($prefix . '_nonce', 'nonce');

        if (!current_user_can($this->getSettingsCapability())) {
            wp_send_json_error([
                'message' => __('Permission denied.', 'wp-app-core')
            ]);
        }

        // Reset to defaults
        $defaults = $this->model->getDefaults();
        $saved = $this->model->saveSettings($defaults);

        if ($saved) {
            wp_send_json_success([
                'message' => __('Settings reset to defaults.', 'wp-app-core')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Failed to reset settings.', 'wp-app-core')
            ]);
        }
    }

    /**
     * Get nonce name for forms
     *
     * @return string Nonce name
     */
    protected function getNonceName(): string {
        return $this->getPluginPrefix() . '_nonce';
    }

    /**
     * Get nonce action for forms
     *
     * @return string Nonce action
     */
    protected function getNonceAction(): string {
        return $this->getPluginPrefix() . '_save_settings';
    }
}
