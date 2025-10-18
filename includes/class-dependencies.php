<?php
/**
 * Dependencies Handler Class
 *
 * @package     WP_App_Core
 * @subpackage  Includes
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/includes/class-dependencies.php
 *
 * Description: Menangani dependencies plugin seperti CSS dan JavaScript
 *              untuk admin bar dan komponen core lainnya
 *
 * Changelog:
 * 1.0.0 - 2025-01-18
 * - Initial creation
 * - Added admin bar CSS enqueue
 * - Organized assets following wp-agency pattern
 * - Support for feature-based folder structure
 */

defined('ABSPATH') || exit;

class WP_App_Core_Dependencies {

    /**
     * Plugin name
     * @var string
     */
    private $plugin_name;

    /**
     * Plugin version
     * @var string
     */
    private $version;

    /**
     * Constructor
     *
     * @param string $plugin_name Plugin name
     * @param string $version Plugin version
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Register hooks with priority 20 to load after WordPress core (default 10)
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles'], 20);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts'], 20);
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles() {
        // Admin Bar styles - load with dependency on WP core admin-bar
        // This ensures our styles override core styles properly
        wp_enqueue_style(
            'wp-app-core-admin-bar',
            WP_APP_CORE_PLUGIN_URL . 'assets/css/admin-bar/admin-bar-style.css',
            ['admin-bar'], // Depend on WordPress core admin-bar CSS
            $this->version
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts() {
        // Admin Bar scripts - basic interactive functionality
        wp_enqueue_script(
            'wp-app-core-admin-bar',
            WP_APP_CORE_PLUGIN_URL . 'assets/js/admin-bar/admin-bar-script.js',
            ['jquery'],
            $this->version,
            true
        );

        // Localize script for future AJAX use
        wp_localize_script(
            'wp-app-core-admin-bar',
            'wpAppCoreData',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_app_core_nonce'),
                'debug' => (defined('WP_DEBUG') && WP_DEBUG)
            ]
        );
    }
}
