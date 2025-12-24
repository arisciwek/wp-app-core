/**
 * Platform Staff DataTable Handler - Base Panel System Integration
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Platform
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/platform/platform-staff-datatable.js
 *
 * Description: Komponen untuk mengelola DataTables platform staff.
 *              Terintegrasi dengan base panel system dari wp-app-core.
 *              Menangani server-side processing dan event handling.
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-1192)
 * - Initial creation following wp-agency pattern
 * - Integrated with wp-app-core base panel system
 * - Table ID: #platform-staff-list-table
 * - AJAX action: get_platform_staff_datatable
 *
 * Dependencies:
 * - jQuery
 * - DataTables library
 * - wp-app-core base panel system
 * - wpAppCorePlatformStaff localized object (translations, ajaxurl, nonce)
 */

(function($) {
    'use strict';

    /**
     * Platform Staff DataTable Module
     */
    const PlatformStaffDataTable = {

        /**
         * DataTable instance
         */
        table: null,

        /**
         * Initialization flag
         */
        initialized: false,

        /**
         * Initialize DataTable
         */
        init() {
            if (this.initialized) {
                console.log('[PlatformStaffDataTable] Already initialized');
                return;
            }

            // Check if table element exists
            const tableId = '#platform-staff-list-table';
            if ($(tableId).length === 0) {
                console.log('[PlatformStaffDataTable] Table element not found: ' + tableId);
                return;
            }

            console.log('[PlatformStaffDataTable] Table found: ' + tableId);

            // Check dependencies
            if (typeof wpAppCorePlatformStaff === 'undefined') {
                console.error('[PlatformStaffDataTable] wpAppCorePlatformStaff object not found.');
                return;
            }

            console.log('[PlatformStaffDataTable] Initializing...');

            this.initDataTable();
            this.bindEvents();

            this.initialized = true;
            console.log('[PlatformStaffDataTable] Initialized successfully');
        },

        /**
         * Initialize DataTable with server-side processing
         */
        initDataTable() {
            const statusFilter = $('#platform-staff-status-filter').val() || 'aktif';

            this.table = $('#platform-staff-list-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: wpAppCorePlatformStaff.ajaxurl,
                    type: 'POST',
                    data: function(d) {
                        d.action = 'get_platform_staff_datatable';
                        d.nonce = wpAppCorePlatformStaff.nonce;
                        d.status_filter = statusFilter;
                    }
                },
                columns: [
                    { data: 'employee_id', title: wpAppCorePlatformStaff.i18n.employee_id || 'Employee ID' },
                    { data: 'full_name', title: wpAppCorePlatformStaff.i18n.full_name || 'Full Name' },
                    { data: 'department', title: wpAppCorePlatformStaff.i18n.department || 'Department' },
                    { data: 'hire_date', title: wpAppCorePlatformStaff.i18n.hire_date || 'Hire Date' },
                    {
                        data: 'actions',
                        title: wpAppCorePlatformStaff.i18n.actions || 'Actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [[0, 'asc']],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                language: {
                    processing: wpAppCorePlatformStaff.i18n.processing || 'Processing...',
                    search: wpAppCorePlatformStaff.i18n.search || 'Search:',
                    lengthMenu: wpAppCorePlatformStaff.i18n.lengthMenu || 'Show _MENU_ entries',
                    info: wpAppCorePlatformStaff.i18n.info || 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: wpAppCorePlatformStaff.i18n.infoEmpty || 'Showing 0 to 0 of 0 entries',
                    infoFiltered: wpAppCorePlatformStaff.i18n.infoFiltered || '(filtered from _MAX_ total entries)',
                    zeroRecords: wpAppCorePlatformStaff.i18n.zeroRecords || 'No matching records found',
                    emptyTable: wpAppCorePlatformStaff.i18n.emptyTable || 'No data available in table',
                    paginate: {
                        first: wpAppCorePlatformStaff.i18n.first || 'First',
                        previous: wpAppCorePlatformStaff.i18n.previous || 'Previous',
                        next: wpAppCorePlatformStaff.i18n.next || 'Next',
                        last: wpAppCorePlatformStaff.i18n.last || 'Last'
                    }
                },
                dom: '<"datatable-header"f>t<"datatable-footer"lip>',
                drawCallback: function() {
                    console.log('[PlatformStaffDataTable] Table redrawn');
                }
            });

            console.log('[PlatformStaffDataTable] DataTable initialized');
        },

        /**
         * Bind event handlers
         */
        bindEvents() {
            // Status filter change
            $(document).on('change', '#platform-staff-status-filter', () => {
                console.log('[PlatformStaffDataTable] Status filter changed');
                if (this.table) {
                    this.table.ajax.reload();
                }
            });

            // Row click for panel integration
            $(document).on('click', '#platform-staff-list-table tbody tr', function(e) {
                // Prevent opening panel if clicking action buttons
                if ($(e.target).closest('button').length || $(e.target).closest('a').length) {
                    return;
                }

                const rowData = PlatformStaffDataTable.table.row(this).data();
                if (rowData && rowData.DT_RowData) {
                    $(document).trigger('wpapp:open-panel', [{
                        id: rowData.DT_RowData.id,
                        entity: rowData.DT_RowData.entity
                    }]);
                }
            });

            console.log('[PlatformStaffDataTable] Events bound');
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        PlatformStaffDataTable.init();
    });

})(jQuery);
