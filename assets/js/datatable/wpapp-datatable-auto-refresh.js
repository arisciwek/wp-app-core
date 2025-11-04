/**
 * WPApp DataTable Auto-Refresh System
 *
 * @package     WPAppCore
 * @subpackage  Assets/JS/DataTable
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/datatable/wpapp-datatable-auto-refresh.js
 *
 * Description: Generic DataTable auto-refresh system.
 *              Automatically refreshes DataTables when entity is updated/created/deleted.
 *              Plugins only need to configure table selector and event names.
 *              Eliminates need for manual refresh code in every plugin.
 *
 * Usage Example (in plugin):
 * ```javascript
 * $(document).ready(function() {
 *     // Initialize your DataTable first
 *     initCustomerDataTable();
 *
 *     // Register for auto-refresh (ONLY 3 LINES!)
 *     if (window.WPAppDataTableAutoRefresh) {
 *         WPAppDataTableAutoRefresh.register('customer', {
 *             tableSelector: '#customer-list-table',
 *             events: ['customer:updated', 'customer:created', 'customer:deleted']
 *         });
 *     }
 * });
 * ```
 *
 * Benefits:
 * - DRY Principle: No duplicated refresh code
 * - Centralized: One place to fix bugs
 * - Consistent: Same behavior across all plugins
 * - Flexible: Support custom reload callbacks if needed
 * - Plugin-agnostic: Works with customer, agency, surveyor, association
 * - Easy to use: Only 3 lines of configuration code
 * - Nested entity aware: Won't refresh nested tables in tabs
 * - Debounced: Prevents excessive refreshes
 *
 * Changelog:
 * 1.1.0 - 2025-01-02
 * - Added: Nested entity context awareness (skip nested tables)
 * - Added: Event debouncing (300ms delay for rapid events)
 * - Added: Namespace for event unbinding (prevents unbinding other listeners)
 * - Fixed: unregister() now only unbinds specific entity events
 * - Enhanced: Better error messages with suggestions
 * - Performance: Debouncing prevents multiple refreshes
 *
 * 1.0.0 - 2025-11-01
 * - Initial implementation
 * - Registration system for DataTables
 * - Event-based auto-refresh
 * - Support for custom reload callbacks
 * - Debug logging
 */

(function($) {
    'use strict';

    const WPAppDataTableAutoRefresh = {
        /**
         * Registered tables configuration
         *
         * Format: {
         *   'customer': {
         *      tableSelector: '#customer-list-table',
         *      events: ['customer:updated', 'customer:created', 'customer:deleted'],
         *      reloadCallback: function($table) { ... },  // Optional
         *      debounceTimers: {}  // Debounce timers per event
         *   },
         *   'agency': {
         *      tableSelector: '#agency-list-table',
         *      events: ['agency:updated', 'agency:created', 'agency:deleted']
         *   }
         * }
         *
         * @var {object}
         */
        registeredTables: {},

        /**
         * Enable debug logging
         *
         * @var {boolean}
         */
        debug: false,

        /**
         * Debounce delay in milliseconds
         * Prevents excessive refreshes when multiple events trigger rapidly
         *
         * @var {number}
         */
        debounceDelay: 300,

        /**
         * Register a DataTable for auto-refresh
         *
         * This is the main method plugins use to enable auto-refresh.
         * Call this AFTER initializing your DataTable.
         *
         * @param {string} entity - Entity type (customer, agency, branch, etc.)
         * @param {object} config - Configuration object
         *   @param {string} config.tableSelector - jQuery selector for table (e.g., '#customer-list-table')
         *   @param {array} config.events - Array of event names to listen for
         *   @param {function} config.reloadCallback - Optional custom reload function (receives $table as parameter)
         *   @param {boolean} config.resetPaging - Reset pagination on reload (default: false)
         *
         * @return {boolean} Success status
         *
         * @example
         * WPAppDataTableAutoRefresh.register('customer', {
         *     tableSelector: '#customer-list-table',
         *     events: ['customer:updated', 'customer:created', 'customer:deleted'],
         *     resetPaging: false  // Keep current page after refresh
         * });
         *
         * @example With custom callback
         * WPAppDataTableAutoRefresh.register('invoice', {
         *     tableSelector: '#invoice-table',
         *     events: ['invoice:paid', 'invoice:cancelled'],
         *     reloadCallback: function($table) {
         *         // Custom reload logic
         *         $table.DataTable().ajax.reload();
         *         updateInvoiceStats();
         *     }
         * });
         */
        register(entity, config) {
            // Validation: tableSelector required
            if (!config.tableSelector) {
                console.error('[DataTableAutoRefresh] Registration failed: tableSelector required for entity "' + entity + '"');
                return false;
            }

            // Validation: events array required
            if (!config.events || !Array.isArray(config.events) || config.events.length === 0) {
                console.error('[DataTableAutoRefresh] Registration failed: events array required for entity "' + entity + '"');
                return false;
            }

            // Store configuration
            this.registeredTables[entity] = {
                tableSelector: config.tableSelector,
                events: config.events,
                reloadCallback: config.reloadCallback || null,
                resetPaging: config.resetPaging !== undefined ? config.resetPaging : false,
                debounceTimers: {}  // Initialize debounce timers object
            };

            // Setup event listeners
            this.setupListeners(entity);

            // Log success
            this.log('Registered entity: ' + entity, config);

            return true;
        },

        /**
         * Setup event listeners for entity
         *
         * Binds document-level event listeners for all events specified in config.
         * When event is triggered, calls refreshTable() method with debouncing.
         * Uses namespaced events for proper cleanup on unregister.
         *
         * @param {string} entity - Entity type
         * @private
         */
        setupListeners(entity) {
            const config = this.registeredTables[entity];
            const self = this;

            config.events.forEach(function(eventName) {
                // Use namespaced events for proper unbinding
                // Format: 'customer:updated.wpapp-autorefresh-customer'
                const namespacedEvent = eventName + '.wpapp-autorefresh-' + entity;

                $(document).on(namespacedEvent, function(e, data) {
                    self.log('Event triggered: ' + eventName, data);

                    // Clear existing debounce timer for this event
                    if (config.debounceTimers[eventName]) {
                        clearTimeout(config.debounceTimers[eventName]);
                    }

                    // Set new debounce timer
                    config.debounceTimers[eventName] = setTimeout(function() {
                        self.refreshTable(entity);
                        delete config.debounceTimers[eventName];
                    }, self.debounceDelay);

                    self.log('Debounced refresh scheduled for: ' + entity + ' (' + self.debounceDelay + 'ms)');
                });
            });

            this.log('Setup listeners for: ' + entity, config.events);
        },

        /**
         * Refresh DataTable for entity
         *
         * Handles the actual DataTable refresh operation.
         * - Checks if table exists in DOM
         * - Checks if table is in nested context (skip if nested)
         * - Checks if DataTable is initialized
         * - Calls custom callback if provided
         * - Otherwise, performs standard AJAX reload
         *
         * @param {string} entity - Entity type
         *
         * @example
         * // Manual refresh trigger
         * WPAppDataTableAutoRefresh.refreshTable('customer');
         */
        refreshTable(entity) {
            const config = this.registeredTables[entity];

            // Check if entity is registered
            if (!config) {
                console.warn('[DataTableAutoRefresh] Entity not registered: ' + entity);
                return;
            }

            const $table = $(config.tableSelector);

            // Check if table exists in DOM
            if ($table.length === 0) {
                this.log('Table not found in DOM (might be lazy-loaded): ' + config.tableSelector);
                return;
            }

            // ✅ NEW v1.1.0: Check if table is in nested context
            // Skip refresh for nested tables (e.g., employee/branch tables inside customer tabs)
            const isNested = $table.closest('.wpapp-tab-content').length > 0;
            if (isNested) {
                this.log('Skipping refresh for nested table: ' + config.tableSelector + ' (inside .wpapp-tab-content)');
                return;
            }

            // Check if DataTable is initialized on this table
            if (!$.fn.DataTable.isDataTable($table)) {
                console.warn('[DataTableAutoRefresh] DataTable not initialized on: ' + config.tableSelector);
                return;
            }

            // Custom callback if provided
            if (config.reloadCallback && typeof config.reloadCallback === 'function') {
                this.log('Using custom reload callback for: ' + entity);
                config.reloadCallback($table);
                return;
            }

            // Default: reload without resetting pagination (unless resetPaging is true)
            const dataTable = $table.DataTable();
            dataTable.ajax.reload(null, config.resetPaging);

            this.log('DataTable refreshed: ' + entity + ' (resetPaging: ' + config.resetPaging + ')');
        },

        /**
         * Unregister an entity
         *
         * Removes entity from registered tables and unbinds event listeners.
         * Uses namespaced events so only removes listeners for this specific entity.
         * Useful for cleanup when destroying components.
         *
         * @param {string} entity - Entity type to unregister
         *
         * @example
         * WPAppDataTableAutoRefresh.unregister('customer');
         */
        unregister(entity) {
            const config = this.registeredTables[entity];

            if (!config) {
                console.warn('[DataTableAutoRefresh] Cannot unregister - entity not found: ' + entity);
                return;
            }

            // Clear any pending debounce timers
            Object.keys(config.debounceTimers).forEach(function(eventName) {
                if (config.debounceTimers[eventName]) {
                    clearTimeout(config.debounceTimers[eventName]);
                }
            });

            // Unbind namespaced event listeners (only for this entity)
            // This won't affect other entities listening to the same events
            config.events.forEach(function(eventName) {
                const namespacedEvent = eventName + '.wpapp-autorefresh-' + entity;
                $(document).off(namespacedEvent);
            });

            // Remove from registry
            delete this.registeredTables[entity];

            this.log('Unregistered entity: ' + entity);
        },

        /**
         * Get list of registered entities
         *
         * @return {array} Array of entity names
         */
        getRegisteredEntities() {
            return Object.keys(this.registeredTables);
        },

        /**
         * Get configuration for entity
         *
         * @param {string} entity - Entity type
         * @return {object|null} Configuration object or null if not found
         */
        getConfig(entity) {
            return this.registeredTables[entity] || null;
        },

        /**
         * Enable debug logging
         */
        enableDebug() {
            this.debug = true;
            console.log('[DataTableAutoRefresh] Debug mode enabled');
        },

        /**
         * Disable debug logging
         */
        disableDebug() {
            this.debug = false;
        },

        /**
         * Log debug message
         *
         * @param {string} message - Log message
         * @param {*} data - Optional data to log
         * @private
         */
        log(message, data) {
            if (!this.debug) return;

            if (data !== undefined) {
                console.log('[DataTableAutoRefresh] ' + message, data);
            } else {
                console.log('[DataTableAutoRefresh] ' + message);
            }
        }
    };

    // Expose to global scope
    window.WPAppDataTableAutoRefresh = WPAppDataTableAutoRefresh;

    // Log initialization
    if (typeof console !== 'undefined' && console.log) {
        console.log('[DataTableAutoRefresh] System loaded and ready');
    }

})(jQuery);
