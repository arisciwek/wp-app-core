/**
 * WP App Core - Tab Manager
 *
 * Manages tab navigation within right panel detail views.
 * Implements WordPress-style tab switching with smooth transitions.
 *
 * @package WPAppCore
 * @since 1.0.0
 * @version 1.1.0
 * @author arisciwek
 *
 * Changelog:
 * 1.1.0 - 2025-11-01 (Generic Entity Support)
 * - Made entity ID detection generic (not hardcoded to 'agency')
 * - Reads data-entity-type from panel (defaults to 'agency' for backward compatibility)
 * - Dynamically builds entity ID attribute name (data-{entity}-id)
 * - Dynamically sends {entity}_id parameter in AJAX requests
 * - Supports multiple entities: agency, customer, or any future entity
 * - Backward compatible: existing wp-agency code works without changes
 *
 * Features:
 * - Tab switching without page reload
 * - Hash-based tab state (preserves tab on refresh)
 * - Smooth fade animations
 * - Event system for extensibility
 * - Keyboard navigation support
 * - Generic entity support (not tied to specific entity type)
 *
 * Entity Configuration:
 * - Set data-entity-type on .wpapp-panel element
 * - Tab views must have data-{entity}-id attribute
 * - AJAX handlers receive {entity}_id parameter
 *
 * Example:
 * ```html
 * <div class="wpapp-panel" data-entity-type="customer">
 *   <div class="wpapp-tab-content wpapp-tab-autoload"
 *        data-customer-id="123"
 *        data-load-action="load_customer_branches_tab">
 * ```
 *
 * Events Triggered:
 * - wpapp:tab-switching - Before tab switches
 * - wpapp:tab-switched - After tab switched
 *
 * Usage:
 * ```javascript
 * jQuery(document).on('wpapp:tab-switched', function(e, data) {
 *     console.log('Tab switched to:', data.tabId);
 * });
 * ```
 */

(function($) {
    'use strict';

    /**
     * Tab Manager Class
     */
    class WPAppTabManager {
        constructor() {
            this.currentTab = null;
            this.currentEntity = null;
            this.tabWrapper = null;
            this.tabContents = null;

            this.init();
        }

        /**
         * Initialize tab manager
         */
        init() {
            this.tabWrapper = $('.wpapp-tab-wrapper');

            if (this.tabWrapper.length === 0) {
                // No tabs found
                return;
            }

            this.tabContents = $('.wpapp-tab-content');
            this.currentEntity = $('.wpapp-datatable-layout').data('entity');

            // Bind events
            this.bindEvents();

            // Check hash/query for active tab
            this.checkUrlForTab();

            // Debug mode
            if (typeof wpAppConfig !== 'undefined' && wpAppConfig.debug) {
                console.log('[WPApp Tab] Initialized', {
                    entity: this.currentEntity,
                    tabCount: this.tabWrapper.find('.nav-tab').length
                });
            }
        }

        /**
         * Bind event handlers
         */
        bindEvents() {
            const self = this;

            // Tab click
            this.tabWrapper.on('click', '.nav-tab', function(e) {
                e.preventDefault();

                const $tab = $(this);
                const tabId = $tab.data('tab');

                if (tabId) {
                    self.switchTab(tabId);
                }
            });

            // Listen to panel data loaded event to reinitialize
            $(document).on('wpapp:panel-data-loaded', function() {
                self.reinit();
            });

            // Keyboard navigation (arrow keys)
            this.tabWrapper.on('keydown', '.nav-tab', function(e) {
                const $tabs = self.tabWrapper.find('.nav-tab');
                const $current = $(this);
                const currentIndex = $tabs.index($current);

                let $next = null;

                // Left arrow or Up arrow
                if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    $next = $tabs.eq(currentIndex - 1);
                    if ($next.length === 0) {
                        $next = $tabs.last(); // Wrap to last
                    }
                }

                // Right arrow or Down arrow
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    $next = $tabs.eq(currentIndex + 1);
                    if ($next.length === 0) {
                        $next = $tabs.first(); // Wrap to first
                    }
                }

                if ($next && $next.length > 0) {
                    $next.focus().click();
                }
            });
        }

        /**
         * Switch to a specific tab
         *
         * @param {string} tabId Tab identifier
         */
        switchTab(tabId) {
            const $targetTab = $(`.nav-tab[data-tab="${tabId}"]`);
            const $targetContent = $(`#${tabId}.wpapp-tab-content`);

            if ($targetTab.length === 0 || $targetContent.length === 0) {
                console.warn('[WPApp Tab] Tab not found:', tabId);
                return;
            }

            // Check if already active
            if ($targetTab.hasClass('nav-tab-active')) {
                return;
            }

            // Trigger switching event
            const switchingEvent = $.Event('wpapp:tab-switching', {
                entity: this.currentEntity,
                fromTab: this.currentTab,
                toTab: tabId
            });
            $(document).trigger(switchingEvent);

            // If event prevented, stop
            if (switchingEvent.isDefaultPrevented()) {
                return;
            }

            // Remove active class from all tabs
            this.tabWrapper.find('.nav-tab').removeClass('nav-tab-active');

            // Add active class to target tab
            $targetTab.addClass('nav-tab-active');

            // Hide all tab contents
            this.tabContents.removeClass('active');

            // Show target content with fade animation
            $targetContent.addClass('active');

            // Update current tab
            this.currentTab = tabId;

            // Update URL hash
            this.updateUrlHash(tabId);

            // Trigger switched event
            $(document).trigger('wpapp:tab-switched', {
                entity: this.currentEntity,
                tabId: tabId
            });

            // Auto-load tab content if needed
            this.autoLoadTabContent($targetContent);

            // Debug
            if (typeof wpAppConfig !== 'undefined' && wpAppConfig.debug) {
                console.log('[WPApp Tab] Switched to:', tabId);
            }
        }

        /**
         * Auto-load tab content via AJAX if tab has wpapp-tab-autoload class
         *
         * @param {jQuery} $tab Tab content element
         */
        autoLoadTabContent($tab) {
            console.log('[WPApp Tab] autoLoadTabContent called');
            console.log('[WPApp Tab] Tab element:', $tab);
            console.log('[WPApp Tab] Has wpapp-tab-autoload:', $tab.hasClass('wpapp-tab-autoload'));
            console.log('[WPApp Tab] Has loaded:', $tab.hasClass('loaded'));

            // Check if tab needs auto-loading
            if (!$tab.hasClass('wpapp-tab-autoload')) {
                console.log('[WPApp Tab] Tab does NOT have wpapp-tab-autoload class - skipping');
                return;
            }

            // Check if already loaded
            if ($tab.hasClass('loaded')) {
                console.log('[WPApp Tab] Tab already loaded - skipping');
                return;
            }

            // Get entity type from panel (default to 'agency' for backward compatibility)
            const $panel = $('.wpapp-panel');
            const entityType = $panel.attr('data-entity-type') || this.currentEntity || 'agency';
            const entityIdAttr = 'data-' + entityType + '-id';

            // Get data attributes (use .attr() to avoid jQuery .data() caching)
            const entityId = $tab.attr(entityIdAttr);
            const loadAction = $tab.attr('data-load-action');
            const contentTarget = $tab.attr('data-content-target');
            const errorMessage = $tab.attr('data-error-message') || 'Failed to load content';

            console.log('[WPApp Tab] Data attributes:', {
                entityType: entityType,
                entityIdAttr: entityIdAttr,
                entityId: entityId,
                loadAction: loadAction,
                contentTarget: contentTarget,
                errorMessage: errorMessage
            });

            if (!loadAction || !entityId) {
                console.error('[WPApp Tab] Missing required data attributes for auto-load');
                console.error('[WPApp Tab] loadAction:', loadAction);
                console.error('[WPApp Tab] ' + entityIdAttr + ':', entityId);
                return;
            }

            console.log('[WPApp Tab] Starting AJAX request for:', loadAction);

            // Show loading state
            $tab.find('.wpapp-tab-loading').show();
            $tab.find('.wpapp-tab-loaded-content').hide();
            $tab.find('.wpapp-tab-error').removeClass('visible');

            // Build AJAX data with dynamic entity ID parameter
            const ajaxData = {
                action: loadAction,
                nonce: wpAppConfig.nonce
            };
            ajaxData[entityType + '_id'] = entityId;

            // Make AJAX request
            $.ajax({
                url: wpAppConfig.ajaxUrl,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    console.log('[WPApp Tab] AJAX Success Response:', response);
                    $tab.find('.wpapp-tab-loading').hide();

                    if (response.success && response.data.html) {
                        // Load content into target
                        console.log('[WPApp Tab] Loading HTML into:', contentTarget);
                        console.log('[WPApp Tab] HTML length:', response.data.html.length);

                        const $content = $tab.find(contentTarget);
                        console.log('[WPApp Tab] Target element found:', $content.length);

                        $content.html(response.data.html).addClass('loaded').show();

                        // Mark tab as loaded
                        $tab.addClass('loaded');

                        console.log('[WPApp Tab] Content loaded successfully for:', loadAction);
                        console.log('[WPApp Tab] HTML preview:', response.data.html.substring(0, 200));
                    } else {
                        // Show error
                        $tab.find('.wpapp-error-message').text(response.data.message || errorMessage);
                        $tab.find('.wpapp-tab-error').addClass('visible');

                        console.error('[WPApp Tab] Load failed:', response);
                    }
                },
                error: function(xhr, status, error) {
                    $tab.find('.wpapp-tab-loading').hide();
                    $tab.find('.wpapp-error-message').text(errorMessage);
                    $tab.find('.wpapp-tab-error').addClass('visible');

                    console.error('[WPApp Tab] AJAX error:', error);
                }
            });
        }

        /**
         * Update URL hash with tab ID
         *
         * @param {string} tabId Tab identifier
         */
        updateUrlHash(tabId) {
            const currentHash = window.location.hash;

            // Parse existing hash (e.g., #customer-123)
            const hashParts = currentHash.substring(1).split('&');
            const entityHash = hashParts[0]; // customer-123

            // Create new hash with tab parameter
            const newHash = entityHash ? `${entityHash}&tab=${tabId}` : `tab=${tabId}`;

            // Update hash without triggering hashchange event
            history.replaceState(null, null, `#${newHash}`);
        }

        /**
         * Check URL for tab parameter
         *
         * Supports both hash (#tab=details) and query string (?tab=details)
         */
        checkUrlForTab() {
            let tabId = null;

            // Check hash parameter (#entity-123&tab=details)
            const hash = window.location.hash.substring(1);
            if (hash) {
                const hashParams = hash.split('&');
                for (let param of hashParams) {
                    if (param.startsWith('tab=')) {
                        tabId = param.split('=')[1];
                        break;
                    }
                }
            }

            // Check query string (?tab=details)
            if (!tabId) {
                const urlParams = new URLSearchParams(window.location.search);
                tabId = urlParams.get('tab');
            }

            // Switch to tab if found
            if (tabId) {
                this.switchTab(tabId);
            } else {
                // Switch to first tab as default
                const $firstTab = this.tabWrapper.find('.nav-tab').first();
                if ($firstTab.length > 0) {
                    this.switchTab($firstTab.data('tab'));
                }
            }
        }

        /**
         * Reinitialize after panel content changes
         *
         * Called after AJAX loads new panel content
         */
        reinit() {
            // Update references
            this.tabWrapper = $('.wpapp-tab-wrapper');
            this.tabContents = $('.wpapp-tab-content');

            if (this.tabWrapper.length === 0) {
                return;
            }

            // Rebind events (using event delegation, so not needed)
            // this.bindEvents();

            // Check for tab in URL
            this.checkUrlForTab();

            // Debug
            if (typeof wpAppConfig !== 'undefined' && wpAppConfig.debug) {
                console.log('[WPApp Tab] Reinitialized after panel load');
            }
        }

        /**
         * Public API: Switch to tab programmatically
         *
         * @param {string} tabId Tab identifier
         */
        goTo(tabId) {
            this.switchTab(tabId);
        }

        /**
         * Public API: Get current active tab
         *
         * @return {string|null} Current tab ID
         */
        getCurrent() {
            return this.currentTab;
        }

        /**
         * Public API: Get all available tabs
         *
         * @return {Array} Array of tab IDs
         */
        getAll() {
            const tabs = [];
            this.tabWrapper.find('.nav-tab').each(function() {
                tabs.push($(this).data('tab'));
            });
            return tabs;
        }
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Create global instance
        window.wpAppTabManager = new WPAppTabManager();
    });

})(jQuery);
