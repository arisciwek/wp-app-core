/**
 * WPApp Map Adapter - Global Map Integration Adapter
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Map
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/map/wpapp-map-adapter.js
 *
 * Description: Generic adapter untuk integrate MapPicker dengan berbagai konteks.
 *              Support modal forms, inline forms, tabs, dan konteks lainnya.
 *              Event-driven approach untuk loose coupling.
 *
 * Scope: GLOBAL - Shared across all wp-app-* plugins
 *
 * Dependencies:
 * - jQuery (loaded by WordPress)
 * - Leaflet.js (loaded by wp-app-core)
 * - wpapp-map-picker.js (loaded by wp-app-core)
 *
 * Usage:
 * Automatically integrates map picker when:
 * - WPModal opens with map container (.branch-coordinates-map)
 * - Custom trigger: $(document).trigger('wpapp:init-map')
 * - Custom cleanup: $(document).trigger('wpapp:cleanup-map')
 *
 * Map Container Requirements:
 * - Must have class: .branch-coordinates-map (or configurable)
 * - Must have proper dimensions (width/height)
 * - Coordinate fields: [name="latitude"], [name="longitude"]
 *
 * Changelog:
 * 1.0.1 - 2025-11-11
 * - Migrated to WPModal events (wpmodal:modal-opened, wpmodal:modal-closed)
 * - Replaced wpAppModal with WPModal
 * - Added timeout-based map refresh (replaces modal-fully-open event)
 *
 * 1.0.0 - 2025-11-02 (TODO-2190 Global Adapter)
 * - Initial release as global adapter
 * - Supports WPModal integration
 * - Supports custom trigger events
 * - Generic implementation for all plugins
 * - Eliminates need for plugin-specific adapters
 */
(function($) {
    'use strict';

    const WPAppMapAdapter = {
        /**
         * Configuration
         */
        config: {
            mapContainerSelector: '.branch-coordinates-map',
            modalOpenDelay: 300,        // Delay after modal opens
            mapResizeDelay: 500,        // Delay for map resize
            debug: false
        },

        /**
         * Initialize adapter
         */
        init() {
            this.debugLog('WPAppMapAdapter initializing...');

            // Check dependencies
            if (!this.checkDependencies()) {
                return;
            }

            // Bind events
            this.bindModalEvents();
            this.bindCustomEvents();

            this.debugLog('WPAppMapAdapter initialized successfully');
        },

        /**
         * Check if all dependencies are loaded
         */
        checkDependencies() {
            if (typeof window.L === 'undefined') {
                console.error('[WPAppMapAdapter] Leaflet.js not loaded!');
                return false;
            }

            if (typeof window.MapPicker === 'undefined') {
                console.error('[WPAppMapAdapter] MapPicker not loaded! Make sure wpapp-map-picker.js is enqueued.');
                return false;
            }

            this.debugLog('All dependencies loaded:', {
                leaflet: window.L.version,
                mapPicker: typeof window.MapPicker
            });

            return true;
        },

        /**
         * Bind WPModal lifecycle events
         */
        bindModalEvents() {
            this.debugLog('Binding WPModal events');

            // When modal opens
            $(document).on('wpmodal:modal-opened', (event, config) => {
                this.debugLog('wpmodal:modal-opened event received', config);
                this.initMapInContext('modal');

                // Trigger refresh after a short delay (replace modal-fully-open event)
                setTimeout(() => {
                    this.debugLog('Modal fully opened, refreshing map');
                    this.refreshMap();
                }, 300);
            });

            // When modal closes
            $(document).on('wpmodal:modal-closed', () => {
                this.debugLog('wpmodal:modal-closed event received');
                this.cleanupMap();
            });
        },

        /**
         * Bind custom trigger events
         */
        bindCustomEvents() {
            this.debugLog('Binding custom events');

            // Custom init trigger
            $(document).on('wpapp:init-map', (event, context) => {
                this.debugLog('wpapp:init-map custom event received', context);
                this.initMapInContext(context || 'custom');
            });

            // Custom cleanup trigger
            $(document).on('wpapp:cleanup-map', () => {
                this.debugLog('wpapp:cleanup-map custom event received');
                this.cleanupMap();
            });

            // Custom refresh trigger
            $(document).on('wpapp:refresh-map', () => {
                this.debugLog('wpapp:refresh-map custom event received');
                this.refreshMap();
            });
        },

        /**
         * Initialize map in given context
         *
         * @param {string} context - Context where map is being initialized (modal, inline, tab, etc)
         */
        initMapInContext(context) {
            this.debugLog(`Initializing map in context: ${context}`);

            // Wait for DOM to be ready
            setTimeout(() => {
                const $mapContainer = $(this.config.mapContainerSelector + ':visible');

                if ($mapContainer.length === 0) {
                    this.debugLog('No visible map container found');
                    return;
                }

                this.debugLog('Map container found:', {
                    count: $mapContainer.length,
                    width: $mapContainer.width(),
                    height: $mapContainer.height()
                });

                // Check container dimensions
                const width = $mapContainer.width();
                const height = $mapContainer.height();

                if (width === 0 || height === 0) {
                    this.debugLog('Container has zero dimensions, retrying...');

                    // Retry after delay
                    setTimeout(() => {
                        this.initMapInContext(context);
                    }, 200);
                    return;
                }

                // Initialize map
                try {
                    this.debugLog('Calling MapPicker.init()...');
                    window.MapPicker.init();
                    this.debugLog('MapPicker initialized successfully');

                    // Force resize after initialization
                    setTimeout(() => {
                        this.refreshMap();
                    }, this.config.mapResizeDelay);

                } catch (error) {
                    console.error('[WPAppMapAdapter] Error initializing map:', error);
                }

            }, this.config.modalOpenDelay);
        },

        /**
         * Refresh map size
         */
        refreshMap() {
            if (window.MapPicker && window.MapPicker.map) {
                this.debugLog('Refreshing map size');
                window.MapPicker.map.invalidateSize();
            }
        },

        /**
         * Cleanup map instance
         */
        cleanupMap() {
            if (window.MapPicker) {
                this.debugLog('Cleaning up map');
                window.MapPicker.cleanup();
            }
        },

        /**
         * Debug logging
         */
        debugLog(...args) {
            if (this.config.debug || (window.wpAppCoreData && window.wpAppCoreData.debug)) {
                console.log('[WPAppMapAdapter]', ...args);
            }
        },

        /**
         * Enable debug mode
         */
        enableDebug() {
            this.config.debug = true;
            console.log('[WPAppMapAdapter] Debug mode enabled');
        },

        /**
         * Update configuration
         */
        configure(options) {
            this.config = { ...this.config, ...options };
            this.debugLog('Configuration updated:', this.config);
        }
    };

    // Make adapter globally available
    window.WPAppMapAdapter = WPAppMapAdapter;

    // Auto-initialize on document ready
    $(document).ready(() => {
        WPAppMapAdapter.init();
    });

})(jQuery);
