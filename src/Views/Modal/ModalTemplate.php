<?php
/**
 * Centralized Modal Template
 *
 * @package     WPAppCore
 * @subpackage  Views/Modal
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/Modal/ModalTemplate.php
 *
 * Description: Single flexible modal template for all modal types.
 *              Supports form modals, confirmation modals, and info modals.
 *              Content injected via AJAX or hooks.
 *              Buttons configured dynamically in footer.
 *
 * Modal Types:
 * - form: Create/Edit forms with Cancel + Submit buttons
 * - confirmation: Delete/Action confirmations with Cancel + Confirm buttons
 * - info: Success/Error/Warning messages with OK button
 *
 * Features:
 * - Single template for all types
 * - Hook-based content injection
 * - AJAX content loading
 * - Dynamic footer buttons
 * - Size options (small/medium/large)
 * - ESC key to close
 * - Click overlay to close
 * - Loading state
 * - Accessibility (ARIA)
 *
 * Usage:
 * Call render() method to output modal HTML (hidden by default)
 * JavaScript API (wpAppModal) controls visibility and content
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-1194)
 * - Initial implementation
 * - Single flexible template
 * - Hook system for content
 * - JavaScript API integration
 */

namespace WPAppCore\Views\Modal;

defined('ABSPATH') || exit;

class ModalTemplate {

    /**
     * Render modal template
     *
     * Outputs hidden modal HTML structure.
     * Modal is shown/hidden and populated via JavaScript.
     *
     * @return void
     */
    public static function render(): void {
        ?>
        <!-- WP App Core - Centralized Modal Template -->
        <div id="wpapp-modal"
             class="wpapp-modal"
             style="display:none"
             data-modal-type=""
             role="dialog"
             aria-modal="true"
             aria-labelledby="wpapp-modal-title"
             aria-hidden="true">

            <!-- Overlay/Backdrop -->
            <div class="wpapp-modal-overlay" aria-hidden="true"></div>

            <!-- Modal Container -->
            <div class="wpapp-modal-container" role="document">

                <!-- Header -->
                <div class="wpapp-modal-header">
                    <h2 id="wpapp-modal-title" class="wpapp-modal-title"></h2>
                    <button type="button"
                            class="wpapp-modal-close"
                            aria-label="<?php esc_attr_e('Close modal', 'wp-app-core'); ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <!-- Body -->
                <div class="wpapp-modal-body">
                    <!-- Loading state -->
                    <div class="wpapp-modal-loading" style="display:none">
                        <div class="wpapp-spinner"></div>
                        <p><?php esc_html_e('Loading...', 'wp-app-core'); ?></p>
                    </div>

                    <!-- Content injected here via AJAX or JavaScript -->
                    <div class="wpapp-modal-content">
                        <!-- Dynamic content -->
                    </div>
                </div>

                <!-- Footer -->
                <div class="wpapp-modal-footer">
                    <!-- Buttons injected here via JavaScript -->
                </div>

            </div><!-- .wpapp-modal-container -->

        </div><!-- #wpapp-modal -->
        <?php
    }

    /**
     * Render button HTML
     *
     * Helper method to render a single button.
     * Used by JavaScript or can be called directly.
     *
     * @param array $button Button configuration
     * @return string Button HTML
     */
    public static function render_button(array $button): string {
        $defaults = [
            'id' => '',
            'label' => 'Button',
            'class' => 'button',
            'type' => 'button',
            'disabled' => false,
            'data' => []
        ];

        $button = array_merge($defaults, $button);

        $classes = ['wpapp-modal-btn', $button['class']];
        $attrs = [];

        if ($button['id']) {
            $attrs[] = sprintf('id="%s"', esc_attr($button['id']));
        }

        $attrs[] = sprintf('type="%s"', esc_attr($button['type']));
        $attrs[] = sprintf('class="%s"', esc_attr(implode(' ', $classes)));

        if ($button['disabled']) {
            $attrs[] = 'disabled';
        }

        foreach ($button['data'] as $key => $value) {
            $attrs[] = sprintf('data-%s="%s"', esc_attr($key), esc_attr($value));
        }

        return sprintf(
            '<button %s>%s</button>',
            implode(' ', $attrs),
            esc_html($button['label'])
        );
    }

    /**
     * Get default buttons for modal type
     *
     * Returns default button configuration for each modal type.
     *
     * @param string $type Modal type (form|confirmation|info)
     * @return array Button configuration
     */
    public static function get_default_buttons(string $type): array {
        $buttons = [];

        switch ($type) {
            case 'form':
                $buttons = [
                    'cancel' => [
                        'id' => 'wpapp-modal-cancel',
                        'label' => __('Cancel', 'wp-app-core'),
                        'class' => 'button',
                        'data' => ['action' => 'cancel']
                    ],
                    'submit' => [
                        'id' => 'wpapp-modal-submit',
                        'label' => __('Save', 'wp-app-core'),
                        'class' => 'button button-primary',
                        'type' => 'submit',
                        'data' => ['action' => 'submit']
                    ]
                ];
                break;

            case 'confirmation':
                $buttons = [
                    'cancel' => [
                        'id' => 'wpapp-modal-cancel',
                        'label' => __('Cancel', 'wp-app-core'),
                        'class' => 'button',
                        'data' => ['action' => 'cancel']
                    ],
                    'confirm' => [
                        'id' => 'wpapp-modal-confirm',
                        'label' => __('Confirm', 'wp-app-core'),
                        'class' => 'button button-primary',
                        'data' => ['action' => 'confirm']
                    ]
                ];
                break;

            case 'info':
                $buttons = [
                    'ok' => [
                        'id' => 'wpapp-modal-ok',
                        'label' => __('OK', 'wp-app-core'),
                        'class' => 'button button-primary',
                        'data' => ['action' => 'close']
                    ]
                ];
                break;

            default:
                // Generic close button
                $buttons = [
                    'close' => [
                        'id' => 'wpapp-modal-close-btn',
                        'label' => __('Close', 'wp-app-core'),
                        'class' => 'button',
                        'data' => ['action' => 'close']
                    ]
                ];
        }

        /**
         * Filter default buttons for modal type
         *
         * @param array $buttons Button configuration
         * @param string $type Modal type
         */
        return apply_filters('wpapp_modal_default_buttons', $buttons, $type);
    }
}
