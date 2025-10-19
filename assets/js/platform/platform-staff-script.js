/**
 * Platform Staff Management Interface
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Platform
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/platform/platform-staff-script.js
 *
 * Description: Main JavaScript handler untuk halaman Platform Staff.
 *              Mengatur interaksi antar komponen seperti DataTable,
 *              form, panel kanan, dan notifikasi.
 *
 * Dependencies:
 * - jQuery
 * - DataTables
 * - WordPress AJAX
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial implementation
 * - DataTable integration
 * - CRUD operations
 * - Panel navigation
 */

(function($) {
    'use strict';

    const PlatformStaff = {
        currentId: null,
        isLoading: false,
        isEditMode: false,
        components: {
            container: null,
            rightPanel: null,
            dataTable: null,
            modal: null,
            form: null,
            stats: {
                totalStaff: null,
                recentHires: null,
                totalDepartments: null
            }
        },

        init() {
            this.components = {
                container: $('.wp-platform-staff-container'),
                rightPanel: $('.wp-platform-staff-right-panel'),
                modal: $('#staff-modal'),
                form: $('#staff-form'),
                stats: {
                    totalStaff: $('#total-staff'),
                    recentHires: $('#recent-hires'),
                    totalDepartments: $('#total-departments')
                }
            };

            // Log default filter values
            console.log('[Platform Staff] Initial filter - Department:', $('#filter-department').val(), 'Status:', $('#filter-status').val());

            this.initDataTable();
            this.bindEvents();
            this.loadStats();
            this.loadDepartments();
        },

        initDataTable() {
            if (window.PlatformStaffDataTable && window.PlatformStaffDataTable.initDataTable) {
                this.components.dataTable = window.PlatformStaffDataTable.initDataTable();

                if (!this.components.dataTable) {
                    console.warn('Platform Staff DataTable initialization returned null.');
                }
            } else {
                console.error('PlatformStaffDataTable module not loaded.');
            }
        },

        bindEvents() {
            const self = this;

            // Add staff button
            $(document).on('click', '#add-staff-btn', function(e) {
                e.preventDefault();
                self.showAddModal();
            });

            // Refresh button
            $(document).on('click', '#refresh-staff-btn', function(e) {
                e.preventDefault();
                self.refreshTable();
            });

            // View staff details
            $(document).on('click', '.view-staff-btn', function(e) {
                e.preventDefault();
                const staffId = $(this).data('id');
                self.loadStaffDetails(staffId);
            });

            // Edit staff button (in DataTable)
            $(document).on('click', '.edit-staff-btn', function(e) {
                e.preventDefault();
                const staffId = $(this).data('id');
                self.showEditModal(staffId);
            });

            // Edit staff button (in panel)
            $(document).on('click', '#edit-staff-btn', function(e) {
                e.preventDefault();
                if (self.currentId) {
                    self.showEditModal(self.currentId);
                }
            });

            // Delete staff button (in panel)
            $(document).on('click', '#delete-staff-btn', function(e) {
                e.preventDefault();
                if (self.currentId) {
                    self.confirmDelete(self.currentId);
                }
            });

            // Close panel
            $(document).on('click', '#close-panel-btn', function(e) {
                e.preventDefault();
                self.closeRightPanel();
            });

            // Modal close
            $(document).on('click', '.staff-modal-close', function(e) {
                e.preventDefault();
                self.closeModal();
            });

            // Form submit
            this.components.form.on('submit', function(e) {
                e.preventDefault();
                self.handleFormSubmit();
            });

            // Department filter
            $('#filter-department').on('change', function() {
                if (self.components.dataTable) {
                    self.components.dataTable.ajax.reload();
                }
            });

            // Status filter
            $('#filter-status').on('change', function() {
                if (self.components.dataTable) {
                    self.components.dataTable.ajax.reload();
                }
            });

            // Click outside modal to close
            $(window).on('click', function(e) {
                if ($(e.target).is('#staff-modal')) {
                    self.closeModal();
                }
            });
        },

        showAddModal() {
            this.isEditMode = false;
            this.currentId = null;
            $('#staff-modal-title').text('Tambah Staff');
            this.components.form[0].reset();
            $('#staff-id').val('');
            $('#user-id').val('');

            // Enable email & username for create mode
            $('#user-email').prop('readonly', false).prop('required', true);
            $('#user-login').prop('readonly', false).prop('required', true);

            $('#status').val('aktif'); // Default to aktif
            this.components.modal.fadeIn();
        },

        showEditModal(staffId) {
            const self = this;
            this.isEditMode = true;
            this.currentId = staffId;

            // Load staff data
            $.ajax({
                url: wpAppCoreStaffData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_platform_staff_details',
                    nonce: wpAppCoreStaffData.nonce,
                    staff_id: staffId
                },
                success: function(response) {
                    if (response.success && response.data) {
                        $('#staff-modal-title').text('Edit Staff');
                        $('#staff-id').val(response.data.id);
                        $('#user-id').val(response.data.user_id);

                        // Set email & username readonly for edit mode
                        $('#user-email').val(response.data.user_email).prop('readonly', true).prop('required', false);
                        $('#user-login').val(response.data.user_login).prop('readonly', true).prop('required', false);

                        // Populate profile fields
                        $('#full-name').val(response.data.full_name);
                        $('#department').val(response.data.department !== '-' ? response.data.department : '');
                        $('#status').val(response.data.status || 'aktif');
                        $('#hire-date').val(response.data.hire_date_raw || '');
                        $('#phone').val(response.data.phone !== '-' ? response.data.phone : '');

                        self.components.modal.fadeIn();
                    } else {
                        self.showToast('error', response.data?.message || wpAppCoreStaffData.i18n.loadError);
                    }
                },
                error: function() {
                    self.showToast('error', wpAppCoreStaffData.i18n.loadError);
                }
            });
        },

        closeModal() {
            this.components.modal.fadeOut();
            this.components.form[0].reset();
            this.isEditMode = false;
            this.currentId = null;
        },

        handleFormSubmit() {
            const self = this;
            const formData = this.components.form.serialize();
            const action = this.isEditMode ? 'update_platform_staff' : 'create_platform_staff';

            $.ajax({
                url: wpAppCoreStaffData.ajaxUrl,
                type: 'POST',
                data: formData + '&action=' + action + '&nonce=' + wpAppCoreStaffData.nonce,
                beforeSend: function() {
                    self.components.form.find('button[type="submit"]').prop('disabled', true).text('Menyimpan...');
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast('success', wpAppCoreStaffData.i18n.saveSuccess);
                        self.closeModal();
                        self.refreshTable();
                        self.loadStats();

                        if (self.isEditMode && self.currentId) {
                            self.loadStaffDetails(self.currentId);
                        }
                    } else {
                        self.showToast('error', response.data?.message || wpAppCoreStaffData.i18n.saveError);
                    }
                },
                error: function() {
                    self.showToast('error', wpAppCoreStaffData.i18n.saveError);
                },
                complete: function() {
                    self.components.form.find('button[type="submit"]').prop('disabled', false).text('Simpan');
                }
            });
        },

        confirmDelete(staffId) {
            if (!confirm(wpAppCoreStaffData.i18n.confirmDelete)) {
                return;
            }

            const self = this;

            $.ajax({
                url: wpAppCoreStaffData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'delete_platform_staff',
                    nonce: wpAppCoreStaffData.nonce,
                    staff_id: staffId
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast('success', wpAppCoreStaffData.i18n.deleteSuccess);
                        self.closeRightPanel();
                        self.refreshTable();
                        self.loadStats();
                    } else {
                        self.showToast('error', response.data?.message || wpAppCoreStaffData.i18n.deleteError);
                    }
                },
                error: function() {
                    self.showToast('error', wpAppCoreStaffData.i18n.deleteError);
                }
            });
        },

        loadStaffDetails(staffId) {
            const self = this;
            self.currentId = staffId;

            $.ajax({
                url: wpAppCoreStaffData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_platform_staff_details',
                    nonce: wpAppCoreStaffData.nonce,
                    staff_id: staffId
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.populateRightPanel(response.data);
                        self.openRightPanel();
                    } else {
                        self.showToast('error', response.data?.message || wpAppCoreStaffData.i18n.loadError);
                    }
                },
                error: function() {
                    self.showToast('error', wpAppCoreStaffData.i18n.loadError);
                }
            });
        },

        populateRightPanel(data) {
            $('#detail-employee-id').text(data.employee_id || '-');
            $('#detail-full-name').text(data.full_name || '-');
            $('#detail-department').text(data.department || '-');

            // Populate status with badge
            const statusClass = data.status === 'aktif' ? 'status-aktif' : 'status-tidak-aktif';
            const statusHtml = '<span class="staff-status-badge ' + statusClass + '">' +
                              (data.status_label || data.status || '-') + '</span>';
            $('#detail-status').html(statusHtml);

            $('#detail-hire-date').text(data.hire_date || '-');
            $('#detail-phone').text(data.phone || '-');
            $('#detail-user-login').text(data.user_login || '-');
            $('#detail-user-email').text(data.user_email || '-');
            $('#detail-created-at').text(data.created_at || '-');
            $('#detail-updated-at').text(data.updated_at || '-');

            // Show/hide action buttons based on permissions
            $('#edit-staff-btn').toggle(data.can_edit || false);
            $('#delete-staff-btn').toggle(data.can_delete || false);
        },

        openRightPanel() {
            const self = this;
            console.log('[Platform Staff] Opening right panel - Left panel will shrink from 100% to 45%');
            this.components.rightPanel.removeClass('hidden').addClass('visible');
            this.components.container.addClass('with-right-panel');

            // Hide columns and adjust DataTable after transition (300ms)
            setTimeout(function() {
                if (self.components.dataTable) {
                    console.log('[Platform Staff] Hiding columns: Status, Tanggal Bergabung, Telepon, Email');

                    // Hide columns: 3 (Status), 4 (Tanggal Bergabung), 5 (Telepon), 6 (Email)
                    // Showing only: Employee ID, Nama, Department, Aksi
                    self.components.dataTable.column(3).visible(false, false);
                    self.components.dataTable.column(4).visible(false, false);
                    self.components.dataTable.column(5).visible(false, false);
                    self.components.dataTable.column(6).visible(false, false);

                    // Force recalculation
                    self.components.dataTable.columns.adjust();

                    // Small delay then redraw
                    setTimeout(function() {
                        self.components.dataTable.draw(false);
                        console.log('[Platform Staff] DataTable adjusted - showing only: Employee ID, Nama, Department, Aksi');
                    }, 50);
                }
                console.log('[Platform Staff] Right panel opened - Left panel width:', self.components.container.find('.wp-platform-staff-left-panel').width());
            }, 350);
        },

        closeRightPanel() {
            const self = this;
            console.log('[Platform Staff] Closing right panel - Left panel will expand to 100%');
            this.components.rightPanel.removeClass('visible').addClass('hidden');
            this.components.container.removeClass('with-right-panel');
            this.currentId = null;

            // Show all columns and adjust DataTable after transition (300ms)
            setTimeout(function() {
                if (self.components.dataTable) {
                    console.log('[Platform Staff] Showing all columns back');

                    // Show back columns: 3 (Status), 4 (Tanggal Bergabung), 5 (Telepon), 6 (Email)
                    self.components.dataTable.column(3).visible(true, false);
                    self.components.dataTable.column(4).visible(true, false);
                    self.components.dataTable.column(5).visible(true, false);
                    self.components.dataTable.column(6).visible(true, false);

                    // Force recalculation
                    self.components.dataTable.columns.adjust();

                    // Small delay then redraw
                    setTimeout(function() {
                        self.components.dataTable.draw(false);
                        console.log('[Platform Staff] DataTable adjusted to full width - all columns visible');
                    }, 50);
                }
                console.log('[Platform Staff] Right panel closed - Left panel width:', self.components.container.find('.wp-platform-staff-left-panel').width());
            }, 350);
        },

        refreshTable() {
            if (this.components.dataTable) {
                this.components.dataTable.ajax.reload();
            }
        },

        loadStats() {
            const self = this;

            $.ajax({
                url: wpAppCoreStaffData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_platform_staff_stats',
                    nonce: wpAppCoreStaffData.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.updateStats(response.data);
                    }
                },
                error: function() {
                    console.error('Failed to load statistics');
                }
            });
        },

        updateStats(stats) {
            this.components.stats.totalStaff.text(stats.total_staff || 0);
            this.components.stats.recentHires.text(stats.recent_hires || 0);
            this.components.stats.totalDepartments.text(stats.departments?.length || 0);

            // Update department filter dropdown
            if (stats.departments && stats.departments.length > 0) {
                this.populateDepartmentFilter(stats.departments);
            }
        },

        populateDepartmentFilter(departments) {
            const $filter = $('#filter-department');
            const currentValue = $filter.val();

            // Keep "Semua Department" option
            $filter.find('option:not(:first)').remove();

            // Add department options
            departments.forEach(function(dept) {
                $filter.append($('<option>', {
                    value: dept,
                    text: dept
                }));
            });

            // Restore previous selection if exists
            if (currentValue) {
                $filter.val(currentValue);
            }

            console.log('[Platform Staff] Department filter populated with', departments.length, 'departments:', departments);
        },

        loadDepartments() {
            // Departments are loaded via loadStats() which calls updateStats()
            // No separate AJAX needed
        },

        showToast(type, message) {
            const toast = $('#staff-toast');
            toast.removeClass('success error info warning')
                .addClass(type)
                .text(message)
                .fadeIn();

            setTimeout(function() {
                toast.fadeOut();
            }, 3000);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        window.PlatformStaff = PlatformStaff;
        PlatformStaff.init();
    });

})(jQuery);
