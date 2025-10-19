/**
 * Platform Staff DataTable Configuration
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Platform
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/platform/platform-staff-datatable-script.js
 *
 * Description: DataTable configuration untuk platform staff listing.
 *              Handles server-side processing, columns, dan pagination.
 *
 * Dependencies:
 * - jQuery
 * - DataTables
 * - WordPress AJAX
 * - PlatformStaff object (dari platform-staff-script.js)
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial implementation
 * - Server-side processing
 * - Column definitions
 * - Language localization
 */

(function($) {
    'use strict';

    const PlatformStaffDataTable = {
        initDataTable() {
            const self = window.PlatformStaff || this;
            const $table = $('#platform-staff-table');

            // Check if table exists
            if ($table.length === 0) {
                console.warn('Platform Staff table element not found.');
                return null;
            }

            // Check if DataTable is available
            if (typeof $.fn.DataTable === 'undefined') {
                console.error('DataTables library not loaded.');
                return null;
            }

            const dataTable = $table.DataTable({
                serverSide: true,
                processing: false,
                autoWidth: false,
                ajax: {
                    url: wpAppCoreStaffData.ajaxUrl,
                    type: 'POST',
                    data: function(d) {
                        return $.extend({}, d, {
                            action: 'handle_platform_staff_datatable',
                            nonce: wpAppCoreStaffData.nonce,
                            filter_department: $('#filter-department').val() || '',
                            filter_status: $('#filter-status').val() || ''
                        });
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTable error:', error, thrown);
                        if (self.showToast) {
                            self.showToast('error', 'Gagal memuat data staff');
                        }
                    }
                },
                columns: [
                    {
                        data: 'employee_id',
                        title: 'Employee ID',
                        render: function(data, type, row) {
                            return '<strong>' + data + '</strong>';
                        }
                    },
                    {
                        data: 'full_name',
                        title: 'Nama Lengkap',
                        render: function(data, type, row) {
                            return '<a href="#" class="view-staff-btn" data-id="' + row.id + '">' +
                                   data + '</a>';
                        }
                    },
                    {
                        data: 'department',
                        title: 'Department'
                    },
                    {
                        data: 'status_label',
                        title: 'Status',
                        render: function(data, type, row) {
                            const statusClass = row.status === 'aktif' ? 'status-aktif' : 'status-tidak-aktif';
                            return '<span class="staff-status-badge ' + statusClass + '">' + data + '</span>';
                        }
                    },
                    {
                        data: 'hire_date',
                        title: 'Tanggal Bergabung'
                    },
                    {
                        data: 'phone',
                        title: 'Telepon'
                    },
                    {
                        data: 'user_email',
                        title: 'Email'
                    },
                    {
                        data: null,
                        title: 'Aksi',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return '<div class="staff-action-buttons">' +
                                   '<button class="button button-small view-staff-btn" data-id="' + row.id + '" title="Lihat Detail">' +
                                   '<span class="dashicons dashicons-visibility"></span>' +
                                   '</button>' +
                                   '<button class="button button-small button-primary edit-staff-btn" data-id="' + row.id + '" title="Edit">' +
                                   '<span class="dashicons dashicons-edit"></span>' +
                                   '</button>' +
                                   '</div>';
                        }
                    }
                ],
                order: [[3, 'desc']], // Order by hire date
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                language: {
                    processing: 'Memproses...',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    zeroRecords: 'Tidak ada data staff',
                    emptyTable: 'Tidak ada data staff tersedia',
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                    infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                    infoFiltered: '(disaring dari _MAX_ total data)',
                    search: 'Cari:',
                    paginate: {
                        first: 'Pertama',
                        last: 'Terakhir',
                        next: 'Selanjutnya',
                        previous: 'Sebelumnya'
                    }
                },
                dom: '<"table-header"lf>rt<"table-footer"ip>'
            });

            return dataTable;
        }
    };

    // Export to window
    window.PlatformStaffDataTable = PlatformStaffDataTable;

})(jQuery);
