/**
 * WP App Core - Modal Manager
 *
 * @package     WPAppCore
 * @subpackage  Assets/JS/Modal
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/modal/wpapp-modal-manager.js
 *
 * Description: JavaScript API for centralized modal template system.
 *              Controls modal visibility, content, and interactions.
 *              Provides convenience methods for different modal types.
 *
 * Features:
 * - Show/hide modal
 * - Dynamic content loading (HTML or AJAX)
 * - Dynamic button configuration
 * - Event system (opened, closed, submit)
 * - ESC key handling
 * - Overlay click handling
 * - Loading states
 * - Size management
 * - Auto-close for info modals
 * - Form submission handling
 *
 * Usage:
 * wpAppModal.show({
 *     type: 'form',
 *     title: 'Add Customer',
 *     bodyUrl: ajaxurl + '?action=get_customer_form',
 *     size: 'medium',
 *     buttons: {
 *         cancel: { label: 'Cancel' },
 *         submit: { label: 'Save', primary: true }
 *     },
 *     onSubmit: function(formData) { ... }
 * });
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-1194)
 * - Initial implementation
 * - Core show/hide/setContent methods
 * - Event system
 * - AJAX content loading
 * - Button management
 * - Convenience methods (confirm, info)
 */

(function($) {
    'use strict';

    /**
     * WPApp Modal Manager
     */
    window.wpAppModal = {

        /**
         * Modal element cache
         */
        $modal: null,
        $overlay: null,
        $container: null,
        $title: null,
        $body: null,
        $content: null,
        $footer: null,
        $loading: null,
        $closeBtn: null,

        /**
         * Current modal configuration
         */
        config: {},

        /**
         * Auto-close timer
         */
        autoCloseTimer: null,

        /**
         * Initialize modal manager
         */
        init: function() {
            this.cacheElements();
            this.bindEvents();
            console.log('[WPApp Modal] Initialized');
        },

        /**
         * Cache modal elements
         */
        cacheElements: function() {
            this.$modal = $('#wpapp-modal');
            this.$overlay = this.$modal.find('.wpapp-modal-overlay');
            this.$container = this.$modal.find('.wpapp-modal-container');
            this.$title = this.$modal.find('.wpapp-modal-title');
            this.$body = this.$modal.find('.wpapp-modal-body');
            this.$content = this.$modal.find('.wpapp-modal-content');
            this.$footer = this.$modal.find('.wpapp-modal-footer');
            this.$loading = this.$modal.find('.wpapp-modal-loading');
            this.$closeBtn = this.$modal.find('.wpapp-modal-close');
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;

            // Close button
            this.$closeBtn.on('click', function(e) {
                e.preventDefault();
                self.hide();
            });

            // Overlay click
            this.$overlay.on('click', function(e) {
                if (!self.config.preventClose) {
                    self.hide();
                }
            });

            // ESC key
            $(document).on('keydown.wpapp-modal', function(e) {
                if (e.key === 'Escape' && self.$modal.is(':visible')) {
                    if (!self.config.preventClose) {
                        self.hide();
                    }
                }
            });

            // Dynamic button clicks (delegated)
            this.$footer.on('click', '.wpapp-modal-btn', function(e) {
                e.preventDefault();
                var action = $(this).data('action');
                self.handleButtonClick(action, $(this));
            });
        },

        /**
         * Show modal
         *
         * @param {Object} config Modal configuration
         */
        show: function(config) {
            var self = this;

            // Store config
            this.config = $.extend({
                type: 'form',              // form|confirmation|info
                title: '',                 // Modal title
                body: '',                  // HTML content (if not using bodyUrl)
                bodyUrl: '',               // AJAX URL to load content
                size: 'medium',            // small|medium|large
                buttons: null,             // Button configuration
                preventClose: false,       // Prevent close on overlay/ESC
                autoClose: 0,              // Auto-close in ms (0 = disabled)
                onSubmit: null,            // Submit callback
                onClose: null,             // Close callback
                onConfirm: null            // Confirm callback
            }, config);

            // Set modal type
            this.$modal.attr('data-modal-type', this.config.type);

            // Set size
            this.$container.removeClass('wpapp-modal-small wpapp-modal-medium wpapp-modal-large');
            this.$container.addClass('wpapp-modal-' + this.config.size);

            // Set title
            this.setTitle(this.config.title);

            // Set buttons
            if (this.config.buttons === null) {
                // Use default buttons for type
                this.setDefaultButtons(this.config.type);
            } else {
                this.setButtons(this.config.buttons);
            }

            // Load content
            if (this.config.bodyUrl) {
                // AJAX load
                this.loadContent(this.config.bodyUrl);
            } else if (this.config.body) {
                // Direct HTML
                this.setContent(this.config.body);
            }

            // Show modal
            this.$modal.fadeIn(200);
            this.$modal.attr('aria-hidden', 'false');

            // Trigger event
            $(document).trigger('wpapp:modal-opened', [this.config]);

            // Auto-close if configured
            if (this.config.autoClose > 0) {
                this.autoCloseTimer = setTimeout(function() {
                    self.hide();
                }, this.config.autoClose);
            }

            return this;
        },

        /**
         * Hide modal
         */
        hide: function() {
            var self = this;

            // Clear auto-close timer
            if (this.autoCloseTimer) {
                clearTimeout(this.autoCloseTimer);
                this.autoCloseTimer = null;
            }

            // Trigger close callback
            if (typeof this.config.onClose === 'function') {
                this.config.onClose();
            }

            // Hide modal
            this.$modal.fadeOut(200, function() {
                self.$modal.attr('aria-hidden', 'true');
                self.clearContent();
            });

            // Trigger event
            $(document).trigger('wpapp:modal-closed');

            return this;
        },

        /**
         * Set modal title
         *
         * @param {string} title
         */
        setTitle: function(title) {
            this.$title.text(title);
            return this;
        },

        /**
         * Set modal content
         *
         * @param {string} html HTML content
         */
        setContent: function(html) {
            this.loading(false);
            this.$content.html(html);
            return this;
        },

        /**
         * Load content via AJAX
         *
         * @param {string} url AJAX URL
         */
        loadContent: function(url) {
            var self = this;

            this.loading(true);

            console.log('[WPApp Modal] Loading content from:', url);

            $.ajax({
                url: url,
                method: 'GET',
                success: function(response) {
                    console.log('[WPApp Modal] Response received, length:', response.length);
                    console.log('[WPApp Modal] Response preview:', response.substring(0, 200));
                    self.setContent(response);
                },
                error: function(xhr, status, error) {
                    console.error('[WPApp Modal] AJAX load failed:', error);
                    console.error('[WPApp Modal] Status:', xhr.status);
                    console.error('[WPApp Modal] Response:', xhr.responseText);
                    self.setContent('<p class="error">Failed to load content.</p>');
                }
            });

            return this;
        },

        /**
         * Show/hide loading state
         *
         * @param {boolean} show
         */
        loading: function(show) {
            if (show) {
                this.$loading.show();
                this.$content.hide();
            } else {
                this.$loading.hide();
                this.$content.show();
            }
            return this;
        },

        /**
         * Set modal buttons
         *
         * @param {Object} buttons Button configuration
         */
        setButtons: function(buttons) {
            var html = '';

            $.each(buttons, function(key, button) {
                var btnClass = 'wpapp-modal-btn ' + (button.class || 'button');
                var btnType = button.type || 'button';
                var btnLabel = button.label || key;
                var btnAction = button.action || key;
                var btnDisabled = button.disabled ? 'disabled' : '';
                var btnId = button.id || 'wpapp-modal-' + key;

                html += '<button type="' + btnType + '" ';
                html += 'id="' + btnId + '" ';
                html += 'class="' + btnClass + '" ';
                html += 'data-action="' + btnAction + '" ';
                html += btnDisabled + '>';
                html += btnLabel;
                html += '</button>';
            });

            this.$footer.html(html);
            return this;
        },

        /**
         * Set default buttons for modal type
         *
         * @param {string} type Modal type
         */
        setDefaultButtons: function(type) {
            var buttons = {};

            switch (type) {
                case 'form':
                    buttons = {
                        cancel: {
                            label: 'Cancel',
                            class: 'button',
                            action: 'cancel'
                        },
                        submit: {
                            label: 'Save',
                            class: 'button button-primary',
                            type: 'submit',
                            action: 'submit'
                        }
                    };
                    break;

                case 'confirmation':
                    buttons = {
                        cancel: {
                            label: 'Cancel',
                            class: 'button',
                            action: 'cancel'
                        },
                        confirm: {
                            label: 'Confirm',
                            class: 'button button-primary',
                            action: 'confirm'
                        }
                    };
                    break;

                case 'info':
                    buttons = {
                        ok: {
                            label: 'OK',
                            class: 'button button-primary',
                            action: 'close'
                        }
                    };
                    break;

                default:
                    buttons = {
                        close: {
                            label: 'Close',
                            class: 'button',
                            action: 'close'
                        }
                    };
            }

            this.setButtons(buttons);
            return this;
        },

        /**
         * Handle button click
         *
         * @param {string} action Button action
         * @param {jQuery} $button Button element
         */
        handleButtonClick: function(action, $button) {
            var self = this;

            console.log('[WPApp Modal] Button clicked:', action);

            switch (action) {
                case 'cancel':
                case 'close':
                    this.hide();
                    break;

                case 'submit':
                    // Find form in modal
                    var $form = this.$content.find('form');
                    if ($form.length) {
                        // Trigger form submission
                        var formData = $form.serialize();

                        // Trigger event
                        $(document).trigger('wpapp:modal-submit', [formData]);

                        // Call submit callback
                        if (typeof this.config.onSubmit === 'function') {
                            this.config.onSubmit(formData, $form);
                        } else {
                            // Default: submit form normally
                            $form.submit();
                        }
                    }
                    break;

                case 'confirm':
                    // Call confirm callback
                    if (typeof this.config.onConfirm === 'function') {
                        this.config.onConfirm();
                    }
                    this.hide();
                    break;

                default:
                    console.warn('[WPApp Modal] Unknown button action:', action);
            }
        },

        /**
         * Clear modal content
         */
        clearContent: function() {
            this.$content.empty();
            this.$footer.empty();
            this.$title.empty();
            this.$modal.attr('data-modal-type', '');
            this.config = {};
            return this;
        },

        /**
         * Convenience method: Show confirmation modal
         *
         * @param {Object} config
         */
        confirm: function(config) {
            var confirmConfig = $.extend({
                type: 'confirmation',
                title: 'Confirm Action',
                body: '<p>Are you sure?</p>',
                size: 'small'
            }, config);

            // Add danger class if specified
            if (config.danger) {
                confirmConfig.buttons = {
                    cancel: {
                        label: 'Cancel',
                        class: 'button'
                    },
                    confirm: {
                        label: config.confirmLabel || 'Confirm',
                        class: 'button button-primary button-danger'
                    }
                };
            }

            return this.show(confirmConfig);
        },

        /**
         * Convenience method: Show info modal
         *
         * @param {Object} config
         */
        info: function(config) {
            var infoConfig = $.extend({
                type: 'info',
                title: 'Information',
                body: '<p>Information message</p>',
                size: 'small',
                autoClose: 3000
            }, config);

            // Add icon based on info type
            if (config.infoType) {
                var iconClass = '';
                switch (config.infoType) {
                    case 'success':
                        iconClass = 'dashicons-yes-alt';
                        break;
                    case 'error':
                        iconClass = 'dashicons-dismiss';
                        break;
                    case 'warning':
                        iconClass = 'dashicons-warning';
                        break;
                    default:
                        iconClass = 'dashicons-info';
                }

                infoConfig.body = '<div class="wpapp-modal-info wpapp-modal-info-' + config.infoType + '">' +
                    '<span class="dashicons ' + iconClass + '"></span>' +
                    '<div class="wpapp-modal-info-message">' + config.message + '</div>' +
                    '</div>';
            }

            return this.show(infoConfig);
        }

    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Check if modal template exists
        if ($('#wpapp-modal').length) {
            wpAppModal.init();
        } else {
            console.warn('[WPApp Modal] Modal template not found in DOM');
        }
    });

})(jQuery);
