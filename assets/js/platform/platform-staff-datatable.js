/**
 * Platform Staff DataTable
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Platform
 * @version     3.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/platform/platform-staff-datatable.js
 *
 * Description: Minimal DataTable initialization for Platform Staff dashboard.
 *              Compatible with wp-datatable dual-panel system.
 *              DELEGATES all panel interactions to wp-datatable framework.
 *
 * Dependencies:
 * - jQuery
 * - DataTables library
 * - wp-datatable panel-manager.js (handles all row/button clicks automatically)
 *
 * How it works:
 * 1. Initialize DataTable with server-side processing
 * 2. Server returns DT_RowData with staff ID
 * 3. DataTables automatically converts DT_RowData to data-* attributes on <tr>
 * 4. wp-datatable panel-manager.js detects clicks on .wpdt-datatable rows
 * 5. Panel opens automatically - NO custom code needed!
 *
 * Changelog:
 * 3.0.0 - 2025-12-25
 * - BREAKING: Complete rewrite for wp-datatable compatibility
 * - Migrated from wpapp-datatable to wpdt-datatable
 * - Following customer-datatable.js pattern
 * - TRUE minimal implementation - delegates everything to framework
 */

(function($) {
    'use strict';

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        console.log('[Platform Staff DataTable] ========================================');
        console.log('[Platform Staff DataTable] Script loaded and executing');
        console.log('[Platform Staff DataTable] jQuery version:', $.fn.jquery);
        console.log('[Platform Staff DataTable] DataTables available:', typeof $.fn.DataTable);
        console.log('[Platform Staff DataTable] ajaxurl:', typeof ajaxurl !== 'undefined' ? ajaxurl : 'UNDEFINED');

        var $table = $('#platform-staff-datatable');
        console.log('[Platform Staff DataTable] Table selector:', $table.length > 0 ? 'FOUND' : 'NOT FOUND');

        if ($table.length === 0) {
            console.error('[Platform Staff DataTable] Table element #platform-staff-datatable not found!');
            console.log('[Platform Staff DataTable] Available tables:', $('table').map(function() { return this.id; }).get());
            return;
        }

        // Get nonce from wpdtConfig
        var nonce = '';
        if (typeof wpdtConfig !== 'undefined' && wpdtConfig.nonce) {
            nonce = wpdtConfig.nonce;
            console.log('[Platform Staff DataTable] Using wpdtConfig.nonce');
        } else {
            console.error('[Platform Staff DataTable] No nonce available!');
        }

        // Initialize DataTable with server-side processing
        console.log('[Platform Staff DataTable] Initializing DataTable with nonce:', nonce);

        try {
            var staffTable = $table.DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: ajaxurl,
                type: 'POST',
                data: function(d) {
                    d.action = 'get_platform_staff_datatable';
                    d.nonce = nonce;
                }
            },
            columns: [
                { data: 'name', name: 'name' },
                { data: 'email', name: 'email' },
                { data: 'phone', name: 'phone' },
                {
                    data: 'status',
                    name: 'status'
                    // No render function - Model sends HTML badge
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
            pageLength: 10,
            order: [[0, 'asc']],
            language: {
                processing: 'Processing...',
                search: 'Search:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: 'Showing 0 to 0 of 0 entries',
                infoFiltered: '(filtered from _MAX_ total entries)',
                zeroRecords: 'No matching records found',
                emptyTable: 'No data available in table',
                paginate: {
                    first: 'First',
                    previous: 'Previous',
                    next: 'Next',
                    last: 'Last'
                }
            },
            initComplete: function() {
                console.log('[Platform Staff DataTable] Initialized successfully');
                console.log('[Platform Staff DataTable] wp-datatable will handle row clicks automatically');
            }
        });

        // Store table instance globally for panel-manager.js
        window.platformStaffDataTableInstance = staffTable;

        console.log('[Platform Staff DataTable] Setup complete');

        } catch (error) {
            console.error('[Platform Staff DataTable] ERROR during initialization:', error);
            console.error('[Platform Staff DataTable] Error stack:', error.stack);
        }

        console.log('[Platform Staff DataTable] ========================================');
    });

})(jQuery);
