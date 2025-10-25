<?php
/**
 * DataTable Controller
 *
 * Handles AJAX requests for DataTables with security checks.
 *
 * @package WPAppCore
 * @subpackage Controllers\DataTable
 * @since 1.0.0
 * @author arisciwek
 */

namespace WPAppCore\Controllers\DataTable;

/**
 * DataTableController class
 *
 * Centralized controller for all DataTable AJAX requests.
 * Handles security validation and delegates to appropriate model.
 *
 * @example
 * // Register AJAX handler
 * DataTableController::register_ajax_action(
 *     'customer_datatable',
 *     CustomerDataTableModel::class
 * );
 */
class DataTableController {

    /**
     * Handle AJAX request for DataTable
     *
     * Performs security checks and delegates to model class
     *
     * @param string $model_class Fully qualified model class name
     * @return void Sends JSON response
     * @since 1.0.0
     */
    public function handle_ajax_request($model_class) {

        // 1. Validate AJAX request
        if (!wp_doing_ajax()) {
            wp_send_json_error([
                'message' => __('Invalid request', 'wp-app-core')
            ]);
            return;
        }

        // 2. Verify nonce
        if (!check_ajax_referer('wpapp_datatable_nonce', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Security check failed', 'wp-app-core')
            ]);
            return;
        }

        // 3. Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error([
                'message' => __('You must be logged in', 'wp-app-core')
            ]);
            return;
        }

        // 4. Check permissions (allow modules to override via filter)
        /**
         * Filter to control access to DataTable
         *
         * Modules can implement custom permission logic
         *
         * @param bool $can_access Default access (read capability)
         * @param string $model_class Model class name
         */
        $can_access = apply_filters(
            'wpapp_datatable_can_access',
            current_user_can('read'),
            $model_class
        );

        if (!$can_access) {
            wp_send_json_error([
                'message' => __('Permission denied', 'wp-app-core')
            ]);
            return;
        }

        // 5. Validate model class exists
        if (!class_exists($model_class)) {
            // Log error for debugging
            if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log("DataTable Error: Model class not found - {$model_class}");
            }

            wp_send_json_error([
                'message' => __('Invalid model class', 'wp-app-core')
            ]);
            return;
        }

        try {
            // 6. Instantiate model
            $model = new $model_class();

            // 7. Validate model extends DataTableModel
            if (!method_exists($model, 'get_datatable_data')) {
                throw new \Exception('Model must extend DataTableModel and implement get_datatable_data()');
            }

            // 8. Get DataTable data
            $data = $model->get_datatable_data($_POST);

            /**
             * Filter final output before sending to client
             *
             * Modules can modify or add to the response
             *
             * @param array $data DataTables response data
             * @param string $model_class Model class name
             */
            $data = apply_filters('wpapp_datatable_output', $data, $model_class);

            // 9. Send success response
            wp_send_json_success($data);

        } catch (\Exception $e) {
            // Log error for debugging
            if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('DataTable Error: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
            }

            // Send error response
            wp_send_json_error([
                'message' => __('An error occurred while loading data', 'wp-app-core'),
                'debug' => (defined('WP_DEBUG') && WP_DEBUG) ? $e->getMessage() : null
            ]);
        }
    }

    /**
     * Register AJAX action for DataTable
     *
     * Helper method to easily register DataTable AJAX handlers
     *
     * @param string $action Action name (without 'wp_ajax_' prefix)
     * @param string $model_class Fully qualified model class name
     * @return void
     * @since 1.0.0
     *
     * @example
     * DataTableController::register_ajax_action(
     *     'customer_datatable',
     *     'WPCustomer\\Models\\CustomerDataTableModel'
     * );
     */
    public static function register_ajax_action($action, $model_class) {
        add_action("wp_ajax_{$action}", function() use ($model_class) {
            $controller = new self();
            $controller->handle_ajax_request($model_class);
        });

        // Log registration in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log("DataTable AJAX registered: wp_ajax_{$action} -> {$model_class}");
        }
    }

    /**
     * Enqueue DataTable scripts and styles
     *
     * Call this method in admin_enqueue_scripts to load DataTables assets
     *
     * @param string $handle Optional script handle to localize
     * @return void
     * @since 1.0.0
     */
    public static function enqueue_scripts($handle = 'jquery') {
        // Localize script with nonce and ajax URL
        wp_localize_script($handle, 'wpapp_datatable', [
            'nonce' => wp_create_nonce('wpapp_datatable_nonce'),
            'ajax_url' => admin_url('admin-ajax.php')
        ]);

        // Note: DataTables.js library should be enqueued by the page that needs it
        // This method only provides the WordPress-specific config
    }
}
