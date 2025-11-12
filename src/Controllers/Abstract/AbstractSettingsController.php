<?php
/**
 * Abstract Settings Controller
 *
 * Base controller class for plugin settings pages.
 * Eliminates code duplication across plugin settings controllers.
 *
 * @package     WPAppCore
 * @subpackage  Controllers
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Controllers/Abstract/AbstractSettingsController.php
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
 * 1.1.0 - 2025-01-09
 * - Added prepareViewData() method to pass settings data to templates
 * - Fixed: Settings data now properly available in tab templates via $settings variable
 * 1.0.0 - 2025-01-09
 * - Initial implementation
 * - Tab system with hooks
 * - AJAX handlers for save/reset
 * - Asset management
 * - Plugin-specific hook integration
 */

namespace WPAppCore\Controllers\Abstract;

use WPAppCore\Models\Abstract\AbstractSettingsModel;
use WPAppCore\Validators\Abstract\AbstractSettingsValidator;

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

    /**
     * Get controller/tab slug for this controller
     * Used for unique AJAX action registration
     *
     * @return string Controller slug, e.g., 'general', 'security-policy'
     */
    abstract protected function getControllerSlug(): string;

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
        add_action('admin_init', [$this, 'handleResetViaPost'], 5); // Priority 5 - before settings registration
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
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
     * Register settings with reset detection
     *
     * Native WordPress Settings API handles both save and reset via POST.
     * Reset is detected via hidden input 'reset_to_defaults' in form.
     */
    public function handleResetViaPost(): void {
        // Check if reset was requested
        if (isset($_POST['reset_to_defaults']) && $_POST['reset_to_defaults'] === '1') {
            // Get option name for this controller
            $option_name = $this->getOptionName();
            $option_page = $_POST['option_page'] ?? '';

            // CRITICAL: Only handle if this is OUR form
            // Each controller must only handle its own option_page
            if ($option_page !== $option_name) {
                return; // Not our form, skip
            }

            // Verify nonce from settings_fields()
            check_admin_referer($option_page . '-options');

            // Reset to defaults
            $defaults = $this->model->getDefaults();
            $updated = update_option($option_name, $defaults);

            // Build redirect URL
            $current_tab = $_POST['current_tab'] ?? '';
            $redirect_url = add_query_arg([
                'page' => 'wp-app-core-settings',
                'tab' => $current_tab,
                'reset' => 'success',
                'reset_tab' => $current_tab
            ], admin_url('admin.php'));

            // CRITICAL: Redirect and DIE to prevent WordPress from processing form POST
            // This prevents form data from overwriting the defaults we just set
            wp_redirect($redirect_url);
            exit;
        }
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

        // Prepare view data
        $view_data = $this->prepareViewData($tab);

        // Hook: Allow plugins to provide custom tab template path
        $custom_tab_path = apply_filters(
            $prefix . '_settings_tab_path',
            '',
            $tab
        );

        if ($custom_tab_path && file_exists($custom_tab_path)) {
            if (!empty($view_data)) {
                extract($view_data);
            }
            include $custom_tab_path;
            return;
        }

        // Try core tab template
        $core_tab = WPAPP_CORE_PATH . "src/Templates/Settings/tab-{$tab}.php";
        if (file_exists($core_tab)) {
            if (!empty($view_data)) {
                extract($view_data);
            }
            include $core_tab;
            return;
        }

        // Hook: Custom tab content via action
        do_action($prefix . '_settings_tab_content_' . $tab);
    }

    /**
     * Prepare view data for tabs
     *
     * Default implementation provides settings data.
     * Child classes can override for custom data.
     *
     * @param string $tab Tab slug
     * @return array View data
     */
    protected function prepareViewData(string $tab): array {
        return [
            'settings' => $this->model->getSettings()
        ];
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
