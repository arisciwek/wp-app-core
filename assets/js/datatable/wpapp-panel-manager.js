/**
 * WP App Core - Panel Manager
 *
 * Manages left/right panel interactions for DataTable dashboards.
 * Implements Perfex CRM-style smooth panel transitions.
 *
 * @package WPAppCore
 * @since 1.0.0
 * @author arisciwek
 *
 * Features:
 * - Smooth panel open/close animations
 * - AJAX data loading
 * - Hash-based navigation (#entity-123)
 * - Event system for extensibility
 * - Close button handling
 *
 * Events Triggered:
 * - wpapp:panel-opening - Before panel opens
 * - wpapp:panel-opened - After panel fully opened
 * - wpapp:panel-closing - Before panel closes
 * - wpapp:panel-closed - After panel fully closed
 * - wpapp:panel-loading - Data loading started
 * - wpapp:panel-data-loaded - Data loaded successfully
 * - wpapp:panel-error - Error occurred
 *
 * Usage:
 * ```javascript
 * jQuery(document).on('wpapp:panel-data-loaded', function(e, data) {
 *     console.log('Panel loaded:', data.entity, data.id);
 * });
 * ```
 */

(function($) {
    'use strict';

    /**
     * Panel Manager Class
     */
    class WPAppPanelManager {
        constructor() {
            this.layout = null;
            this.leftPanel = null;
            this.rightPanel = null;
            this.currentEntity = null;
            this.currentId = null;
            this.isOpen = false;
            this.ajaxRequest = null;
            this.loadingTimeout = null;

            this.init();
        }

        /**
         * Initialize panel manager
         */
        init() {
            this.layout = $('.wpapp-datatable-layout');

            if (this.layout.length === 0) {
                // No DataTable layout found
                return;
            }

            this.leftPanel = this.layout.find('.wpapp-left-panel');
            this.rightPanel = this.layout.find('.wpapp-right-panel');
            this.currentEntity = this.layout.data('entity');

            // Bind events
            this.bindEvents();

            // Check hash on load
            this.checkHashOnLoad();

            // Debug mode
            if (typeof wpAppConfig !== 'undefined' && wpAppConfig.debug) {
                console.log('[WPApp Panel] Initialized', {
                    entity: this.currentEntity,
                    hasLayout: this.layout.length > 0,
                    hasLeftPanel: this.leftPanel.length > 0,
                    hasRightPanel: this.rightPanel.length > 0
                });
            }
        }

        /**
         * Bind event handlers
         */
        bindEvents() {
            const self = this;

            // DataTable row click
            $(document).on('click', '.wpapp-datatable tbody tr', function(e) {
                // Ignore if clicking on action buttons
                if ($(e.target).closest('.wpapp-actions').length > 0) {
                    return;
                }

                const $row = $(this);
                const entityId = $row.data('id');

                if (entityId) {
                    self.openPanel(entityId);
                }
            });

            // Panel trigger button click (View button)
            $(document).on('click', '.wpapp-panel-trigger', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const entityId = $(this).data('id');
                const entity = $(this).data('entity');

                // Verify entity matches current panel entity
                if (entity === self.currentEntity && entityId) {
                    self.openPanel(entityId);
                }
            });

            // Close button click
            this.rightPanel.on('click', '.wpapp-panel-close', function(e) {
                e.preventDefault();
                self.closePanel();
            });

            // Hash change (browser back/forward)
            $(window).on('hashchange', function() {
                self.checkHashChange();
            });

            // Escape key to close
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.isOpen) {
                    self.closePanel();
                }
            });

            // Click outside to close (optional)
            // Uncomment if you want this behavior
            // $(document).on('click', function(e) {
            //     if (self.isOpen &&
            //         !$(e.target).closest('.wpapp-right-panel').length &&
            //         !$(e.target).closest('.wpapp-datatable tbody tr').length) {
            //         self.closePanel();
            //     }
            // });
        }

        /**
         * Open right panel
         *
         * @param {number} entityId Entity ID
         */
        openPanel(entityId) {
            if (this.currentId === entityId && this.isOpen) {
                // Already open with same ID
                return;
            }

            // Trigger opening event
            const openingEvent = $.Event('wpapp:panel-opening', {
                entity: this.currentEntity,
                id: entityId
            });
            $(document).trigger(openingEvent);

            // If event prevented, stop
            if (openingEvent.isDefaultPrevented()) {
                return;
            }

            this.currentId = entityId;

            // Update hash
            this.updateHash(entityId);

            // Show panel with animation
            this.showPanel();

            // Load data via AJAX
            this.loadPanelData(entityId);
        }

        /**
         * Close right panel
         */
        closePanel() {
            if (!this.isOpen) {
                return;
            }

            // Trigger closing event
            const closingEvent = $.Event('wpapp:panel-closing', {
                entity: this.currentEntity,
                id: this.currentId
            });
            $(document).trigger(closingEvent);

            // If event prevented, stop
            if (closingEvent.isDefaultPrevented()) {
                return;
            }

            // Abort any pending AJAX
            if (this.ajaxRequest) {
                this.ajaxRequest.abort();
                this.ajaxRequest = null;
            }

            // Hide panel with animation
            this.hidePanel();

            // Clear hash
            this.clearHash();

            // Reset current ID
            this.currentId = null;

            // Trigger closed event
            $(document).trigger('wpapp:panel-closed', {
                entity: this.currentEntity
            });
        }

        /**
         * Show panel with animation
         */
        showPanel() {
            console.group('🔍 DEBUG: Panel Opening');

            // === BEFORE MEASUREMENTS ===
            const before = {
                scrollTop: window.pageYOffset || document.documentElement.scrollTop,
                scrollLeft: window.pageXOffset || document.documentElement.scrollLeft,
                viewportHeight: window.innerHeight,
                documentHeight: document.documentElement.scrollHeight,
                layoutHeight: this.layout.outerHeight(),
                navigationTop: $('.wpapp-navigation-container').offset()?.top || 0,
                navigationHeight: $('.wpapp-navigation-container').outerHeight() || 0,
                pageHeaderHeight: $('.wpapp-page-header').outerHeight() || 0,
                timestamp: Date.now()
            };

            console.log('📊 BEFORE Panel Open:', before);
            console.log('📍 Navigation Container Position:', {
                top: before.navigationTop,
                height: before.navigationHeight,
                'visible in viewport': before.navigationTop < (before.scrollTop + before.viewportHeight)
            });

            // Scroll to page header FIRST (before panel opens) to prevent flicker
            const pageHeader = document.querySelector('.wpapp-dashboard-wrap');
            if (pageHeader) {
                const headerTop = pageHeader.getBoundingClientRect().top + window.pageYOffset;
                const adminBarHeight = $('#wpadminbar').outerHeight() || 32;
                const scrollTarget = Math.max(0, headerTop - adminBarHeight);

                // INSTANT scroll (no animation) to eliminate flicker
                window.scrollTo(0, scrollTarget);
            }

            // Simple approach: just add classes, let CSS handle everything
            this.layout.addClass('with-right-panel');
            this.rightPanel.removeClass('hidden').addClass('visible');
            this.isOpen = true;

            // Force immediate layout recalculation
            this.layout[0].offsetHeight; // Force reflow

            // === IMMEDIATE AFTER MEASUREMENTS ===
            requestAnimationFrame(() => {
                const after = {
                    scrollTop: window.pageYOffset || document.documentElement.scrollTop,
                    scrollLeft: window.pageXOffset || document.documentElement.scrollLeft,
                    viewportHeight: window.innerHeight,
                    documentHeight: document.documentElement.scrollHeight,
                    layoutHeight: this.layout.outerHeight(),
                    navigationTop: $('.wpapp-navigation-container').offset()?.top || 0,
                    navigationHeight: $('.wpapp-navigation-container').outerHeight() || 0,
                    pageHeaderHeight: $('.wpapp-page-header').outerHeight() || 0,
                    timestamp: Date.now()
                };

                console.log('📊 AFTER Panel Open:', after);

                // === DELTA ANALYSIS ===
                const delta = {
                    scrollTop: after.scrollTop - before.scrollTop,
                    documentHeight: after.documentHeight - before.documentHeight,
                    layoutHeight: after.layoutHeight - before.layoutHeight,
                    navigationTop: after.navigationTop - before.navigationTop,
                    navigationHeight: after.navigationHeight - before.navigationHeight,
                    pageHeaderHeight: after.pageHeaderHeight - before.pageHeaderHeight,
                    elapsed: after.timestamp - before.timestamp
                };

                console.log('📈 DELTA (Changes):', delta);

                if (delta.scrollTop !== 0) {
                    console.warn('⚠️ SCROLL JUMP DETECTED!', {
                        'jumped by': delta.scrollTop + 'px',
                        'direction': delta.scrollTop > 0 ? '⬇️ DOWN' : '⬆️ UP'
                    });
                }

                if (delta.navigationTop !== 0) {
                    console.warn('⚠️ NAVIGATION MOVED!', {
                        'moved by': delta.navigationTop + 'px',
                        'direction': delta.navigationTop > 0 ? '⬇️ DOWN' : '⬆️ UP'
                    });
                }

                console.groupEnd();
            });

            // Trigger opened event after animation
            setTimeout(() => {
                $(document).trigger('wpapp:panel-opened', {
                    entity: this.currentEntity,
                    id: this.currentId
                });
            }, 300); // Match CSS transition duration
        }

        /**
         * Hide panel with animation
         */
        hidePanel() {
            // Remove visible class
            this.rightPanel.removeClass('visible');

            // After animation, add hidden and remove layout class
            setTimeout(() => {
                this.rightPanel.addClass('hidden');
                this.layout.removeClass('with-right-panel');
                this.isOpen = false;
            }, 300); // Match CSS transition duration
        }

        /**
         * Load panel data via AJAX
         *
         * @param {number} entityId Entity ID
         */
        loadPanelData(entityId) {
            const ajaxAction = this.layout.data('ajax-action');

            if (!ajaxAction) {
                console.warn('[WPApp Panel] No AJAX action defined');
                return;
            }

            // Show loading state
            this.showLoading();

            // Trigger loading event
            $(document).trigger('wpapp:panel-loading', {
                entity: this.currentEntity,
                id: entityId
            });

            // Abort previous request
            if (this.ajaxRequest) {
                this.ajaxRequest.abort();
            }

            // Make AJAX request
            this.ajaxRequest = $.ajax({
                url: wpAppConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: ajaxAction,
                    entity: this.currentEntity,
                    id: entityId,
                    nonce: wpAppConfig.nonce
                },
                success: (response) => {
                    this.handleAjaxSuccess(response, entityId);
                },
                error: (jqXHR, textStatus, errorThrown) => {
                    this.handleAjaxError(jqXHR, textStatus, errorThrown, entityId);
                },
                complete: () => {
                    this.ajaxRequest = null;
                    this.hideLoading();
                }
            });
        }

        /**
         * Handle AJAX success
         *
         * @param {Object} response AJAX response
         * @param {number} entityId Entity ID
         */
        handleAjaxSuccess(response, entityId) {
            console.log('[WPApp Panel] AJAX Response:', response);
            console.log('[WPApp Panel] Response success:', response.success);
            console.log('[WPApp Panel] Response data:', response.data);

            if (response.success && response.data) {
                console.log('[WPApp Panel] Processing successful response');
                console.log('[WPApp Panel] Data title:', response.data.title);
                console.log('[WPApp Panel] Data tabs:', response.data.tabs);
                console.log('[WPApp Panel] Tabs count:', response.data.tabs ? Object.keys(response.data.tabs).length : 0);

                // Update panel content
                this.updatePanelContent(response.data);

                // Trigger data loaded event
                $(document).trigger('wpapp:panel-data-loaded', {
                    entity: this.currentEntity,
                    id: entityId,
                    data: response.data
                });

                console.log('[WPApp Panel] Panel content updated successfully');
            } else {
                console.error('[WPApp Panel] Response error or no data');
                console.error('[WPApp Panel] Response:', response);

                // Error in response
                const errorMessage = response.data ? response.data.message : 'Unknown error';
                console.error('[WPApp Panel] Error message:', errorMessage);

                this.showError(errorMessage);

                // Trigger error event
                $(document).trigger('wpapp:panel-error', {
                    entity: this.currentEntity,
                    id: entityId,
                    message: errorMessage
                });
            }
        }

        /**
         * Handle AJAX error
         *
         * @param {Object} jqXHR jQuery XHR object
         * @param {string} textStatus Status text
         * @param {string} errorThrown Error message
         * @param {number} entityId Entity ID
         */
        handleAjaxError(jqXHR, textStatus, errorThrown, entityId) {
            // Don't show error if request was aborted
            if (textStatus === 'abort') {
                return;
            }

            const errorMessage = errorThrown || 'Network error';
            this.showError(errorMessage);

            // Trigger error event
            $(document).trigger('wpapp:panel-error', {
                entity: this.currentEntity,
                id: entityId,
                message: errorMessage,
                status: jqXHR.status
            });
        }

        /**
         * Update panel content
         *
         * @param {Object} data Response data
         */
        updatePanelContent(data) {
            console.log('[WPApp Panel] updatePanelContent called with:', data);

            // Update title if provided
            if (data.title) {
                console.log('[WPApp Panel] Updating title to:', data.title);
                const $titleEl = this.rightPanel.find('.wpapp-entity-name');
                console.log('[WPApp Panel] Title element found:', $titleEl.length);
                $titleEl.text(data.title);
            }

            // Update tab content if provided
            if (data.tabs) {
                console.log('[WPApp Panel] Updating tabs:', Object.keys(data.tabs));
                let updatedCount = 0;

                $.each(data.tabs, function(tabId, content) {
                    console.log('[WPApp Panel] Looking for tab #' + tabId);
                    const $tab = $(`#${tabId}`);
                    console.log('[WPApp Panel] Tab element found:', $tab.length);

                    if ($tab.length > 0) {
                        console.log('[WPApp Panel] Updating tab #' + tabId + ' with content length:', content.length);
                        $tab.html(content);
                        updatedCount++;
                    } else {
                        console.warn('[WPApp Panel] Tab not found: #' + tabId);
                    }
                });

                console.log('[WPApp Panel] Total tabs updated:', updatedCount);
            }

            // Update simple content if provided (no tabs)
            if (data.content) {
                console.log('[WPApp Panel] Updating simple content');
                this.rightPanel.find('.wpapp-panel-content').html(data.content);
            }

            // Update entire HTML if provided (full control)
            if (data.html) {
                console.log('[WPApp Panel] Updating with full HTML');
                this.rightPanel.find('.wpapp-panel-content').html(data.html);
            }

            console.log('[WPApp Panel] Content update complete');
        }

        /**
         * Show loading state
         */
        showLoading() {
            // Don't show loading for fast requests (< 300ms)
            // This prevents flicker for cached/fast responses
            this.loadingTimeout = setTimeout(() => {
                this.rightPanel.addClass('wpapp-loading');

                // Add loading indicator if not exists
                if (this.rightPanel.find('.wpapp-panel-loading').length === 0) {
                    this.rightPanel.find('.wpapp-panel-content').prepend(
                        '<div class="wpapp-panel-loading" style="opacity: 0; transition: opacity 0.3s;">' +
                            '<p style="text-align: center; padding: 20px; color: #666;">Loading...</p>' +
                        '</div>'
                    );

                    // Fade in smoothly
                    setTimeout(() => {
                        this.rightPanel.find('.wpapp-panel-loading').css('opacity', '1');
                    }, 10);
                }
            }, 300); // Delay loading indicator
        }

        /**
         * Hide loading state
         */
        hideLoading() {
            // Clear loading timeout to prevent flicker on fast responses
            if (this.loadingTimeout) {
                clearTimeout(this.loadingTimeout);
                this.loadingTimeout = null;
            }

            this.rightPanel.removeClass('wpapp-loading');
            this.rightPanel.find('.wpapp-panel-loading').remove();
        }

        /**
         * Show error message
         *
         * @param {string} message Error message
         */
        showError(message) {
            const errorHtml = `
                <div class="notice notice-error wpapp-panel-error">
                    <p><strong>Error:</strong> ${message}</p>
                </div>
            `;

            this.rightPanel.find('.wpapp-panel-content').prepend(errorHtml);

            // Auto-hide after 5 seconds
            setTimeout(() => {
                this.rightPanel.find('.wpapp-panel-error').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }

        /**
         * Update URL hash
         *
         * @param {number} entityId Entity ID
         */
        updateHash(entityId) {
            if (this.currentEntity && entityId) {
                window.location.hash = `${this.currentEntity}-${entityId}`;
            }
        }

        /**
         * Clear URL hash
         */
        clearHash() {
            // Remove hash without triggering hashchange
            history.pushState('', document.title, window.location.pathname + window.location.search);
        }

        /**
         * Check hash on page load
         */
        checkHashOnLoad() {
            const hash = window.location.hash.substring(1); // Remove #
            if (hash) {
                this.parseAndOpenHash(hash);
            }
        }

        /**
         * Check hash change (browser back/forward)
         */
        checkHashChange() {
            const hash = window.location.hash.substring(1); // Remove #

            if (hash) {
                this.parseAndOpenHash(hash);
            } else {
                // Hash cleared, close panel
                if (this.isOpen) {
                    this.hidePanel(); // Direct hide, no hash update
                    this.currentId = null;
                }
            }
        }

        /**
         * Parse hash and open panel
         *
         * @param {string} hash Hash string (e.g., "customer-123")
         */
        parseAndOpenHash(hash) {
            const parts = hash.split('-');

            if (parts.length >= 2) {
                const entity = parts[0];
                const id = parseInt(parts[parts.length - 1], 10);

                // Only open if entity matches current context
                if (entity === this.currentEntity && id > 0) {
                    this.openPanel(id);
                }
            }
        }

        /**
         * Refresh current panel
         */
        refresh() {
            if (this.isOpen && this.currentId) {
                this.loadPanelData(this.currentId);
            }
        }

        /**
         * Public API: Open panel programmatically
         *
         * @param {number} entityId Entity ID
         */
        open(entityId) {
            this.openPanel(entityId);
        }

        /**
         * Public API: Close panel programmatically
         */
        close() {
            this.closePanel();
        }
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Create global instance
        window.wpAppPanelManager = new WPAppPanelManager();
    });

})(jQuery);
