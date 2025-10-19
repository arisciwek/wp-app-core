/**
 * Edit Employee Form Handler
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Employee
 * @version     1.0.1
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/employee/edit-employee-form.js
 *
 * Description: Handler untuk form edit karyawan.
 *              Includes form validation, AJAX submission,
 *              error handling, dan modal management.
 *              Terintegrasi dengan toast notifications.
 *
 * Dependencies:
 * - jQuery
 * - jQuery Validation
 * - AgencyToast for notifications
 * - WIModal for confirmations
 *
 * Last modified: 2024-07-27
 * - Updated to handle multiple select roles instead of department checkboxes
 */
(function($) {
    'use strict';

    const EditEmployeeForm = {
        modal: null,
        form: null,
        agencyId: null,

        init() {
            this.modal = $('#edit-employee-modal');
            this.form = $('#edit-employee-form');

            this.bindEvents();
            this.initializeValidation();
        },

        bindEvents() {
            // Form events
            this.form.on('submit', (e) => this.handleUpdate(e));

            // Input validation events
            this.form.on('input', 'input[name="name"], input[name="email"]', (e) => {
                this.validateField(e.target);
            });

            // Edit button handler for DataTable rows
            $(document).on('click', '.edit-employee', (e) => {
                const id = $(e.currentTarget).data('id');
                if (id) {
                    this.loadEmployeeData(id);
                }
            });

            // Modal events
            $('.modal-close', this.modal).on('click', () => this.hideModal());
            $('.cancel-edit', this.modal).on('click', () => this.hideModal());

            // Close modal when clicking outside
            this.modal.on('click', (e) => {
                if ($(e.target).is('.modal-overlay')) {
                    this.hideModal();
                }
            });
        },

	async loadEmployeeData(id) {
	    try {
		const response = await $.ajax({
		    url: wpAgencyData.ajaxUrl,
		    type: 'POST',
		    data: {
		        action: 'get_employee',
		        id: id,
		        nonce: wpAgencyData.nonce
		    }
		});

		if (response.success && response.data) {
		    console.log('Employee data received:', response.data);
		    
		    // Store agency ID for division loading
		    this.agencyId = response.data.agency_id;

		    // Load divisions then show form
		    await this.loadDivisions(response.data.agency_id, response.data.division_id);
		    
		    // Show form with data (user_roles will be included)
		    this.showEditForm(response.data);
		} else {
		    AgencyToast.error(response.data?.message || 'Gagal memuat data karyawan');
		}
	    } catch (error) {
		console.error('Load employee error:', error);
		AgencyToast.error('Gagal menghubungi server');
	    }
	},

        async loadDivisions(agencyId, selectedDivisionId = null) {
            try {
                const response = await $.ajax({
                    url: wpAgencyData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_agency_divisions',
                        agency_id: agencyId,
                        nonce: wpAgencyData.nonce
                    }
                });

                if (response.success && response.data) {
                    const $select = this.form.find('#edit-employee-division');
                    $select.find('option:not(:first)').remove();

                    response.data.forEach(division => {
                        const option = new Option(division.name, division.id);
                        if (division.id === selectedDivisionId) {
                            option.selected = true;
                        }
                        $select.append(option);
                    });
                }
            } catch (error) {
                console.error('Load divisions error:', error);
                AgencyToast.error('Gagal memuat daftar cabang');
            }
        },

	async showEditForm(data) {
	    if (!data) {
		AgencyToast.error('Data karyawan tidak valid');
		return;
	    }

	    // Reset form first
	    this.resetForm();

	    // Load divisions first
	    await this.loadDivisions(data.agency_id, data.division_id);

	    // Populate form data
	    this.form.find('#edit-employee-id').val(data.id);
	    this.form.find('[name="name"]').val(data.name);
	    this.form.find('[name="position"]').val(data.position);
	    this.form.find('[name="email"]').val(data.email);
	    this.form.find('[name="phone"]').val(data.phone);
	    console.log('Setting status to:', data.status);
	    this.form.find('[name="status"]').val(data.status);
	    this.form.find('[name="keterangan"]').val(data.keterangan || '');

	    // Set selected roles - data.user_roles should be available from the controller
	    if (data.user_roles && data.user_roles.length > 0) {
		console.log('Setting roles:', data.user_roles);
		this.form.find('[name="roles[]"]').val(data.user_roles);
		
		// Trigger change event to update any UI elements that depend on selection
		this.form.find('[name="roles[]"]').trigger('change');
	    } else {
		console.log('No roles found for user');
		// Clear selection if no roles
		this.form.find('[name="roles[]"]').val([]);
	    }

	    // Update modal title
	    this.modal.find('.modal-header h3').text(`Edit Karyawan: ${data.name}`);

	    // Show modal with animation
	    this.modal.fadeIn(300, () => {
		this.form.find('[name="name"]').focus();
	    });
	},

	// Remove or simplify the loadCurrentUserRoles method as it's no longer needed:
	async loadCurrentUserRoles(userId) {
	    // This method is no longer needed as roles come from the server
	    // Keep it empty for backward compatibility or remove it entirely
	},

        async getUserData(userId) {
            // This is a simplified approach - in a real implementation,
            // you might need to add an AJAX endpoint to get user roles
            // For now, we'll assume the roles are available or use a placeholder
            // You may need to modify the backend to include user roles in the employee data
            return new Promise((resolve) => {
                // Placeholder - replace with actual AJAX call if needed
                // For now, we'll assume roles are passed in employee data or handle differently
                resolve({ roles: [] }); // Default empty
            });
        },

        hideModal() {
            this.modal.fadeOut(300, () => {
                this.resetForm();
                this.agencyId = null;
            });
        },

        // Updated validation for roles
        initializeValidation() {
            // Tambahkan custom method untuk validasi nomor telepon Indonesia
            $.validator.addMethod("phoneID", function(value, element) {
                if (this.optional(element)) {
                    return true;
                }
                return /^(\+62|62|0)[\s-]?8[1-9]{1}[\s-]?\d{1,4}[\s-]?\d{1,4}[\s-]?\d{1,4}$/.test(value);
            }, "Format nomor telepon tidak valid");

            this.form.validate({
                rules: {
                    name: {
                        required: true,
                        minlength: 3,
                        maxlength: 100
                    },
                    division_id: {
                        required: true
                    },
                    position: {
                        required: true,
                        minlength: 2,
                        maxlength: 100
                    },
                    'roles[]': {
                        required: true,
                        minlength: 1
                    },
                    email: {
                        required: true,
                        email: true,
                        maxlength: 100
                    },
                    phone: {
                        maxlength: 20,
                        phoneID: true
                    },
                    keterangan: {
                        maxlength: 200
                    },
                    status: {
                        required: true
                    }
                },
                messages: {
                    name: {
                        required: 'Nama karyawan wajib diisi',
                        minlength: 'Nama karyawan minimal 3 karakter',
                        maxlength: 'Nama karyawan maksimal 100 karakter'
                    },
                    division_id: {
                        required: 'Cabang wajib dipilih'
                    },
                    position: {
                        required: 'Jabatan wajib diisi',
                        minlength: 'Jabatan minimal 2 karakter',
                        maxlength: 'Jabatan maksimal 100 karakter'
                    },
                    'roles[]': {
                        required: 'Minimal satu role harus dipilih',
                        minlength: 'Minimal satu role harus dipilih'
                    },
                    email: {
                        required: 'Email wajib diisi',
                        email: 'Format email tidak valid',
                        maxlength: 'Email maksimal 100 karakter'
                    },
                    phone: {
                        maxlength: 'Nomor telepon maksimal 20 karakter',
                        phoneID: 'Format nomor telepon tidak valid'
                    },
                    keterangan: {
                        maxlength: 'Keterangan maksimal 200 karakter'
                    },
                    status: {
                        required: 'Status wajib dipilih'
                    }
                },
                errorElement: 'span',
                errorClass: 'form-error',
                errorPlacement: (error, element) => {
                    error.insertAfter(element);
                },
                highlight: (element) => {
                    $(element).addClass('error');
                },
                unhighlight: (element) => {
                    $(element).removeClass('error');
                }
            });
        },

        validateField(field) {
            const $field = $(field);
            const fieldName = $field.attr('name');
            const value = $field.val().trim();
            const errors = [];

            switch (fieldName) {
                case 'name':
                    if (!value) {
                        errors.push('Nama karyawan wajib diisi');
                    } else {
                        if (value.length < 3) {
                            errors.push('Nama karyawan minimal 3 karakter');
                        }
                        if (value.length > 100) {
                            errors.push('Nama karyawan maksimal 100 karakter');
                        }
                    }
                    break;

                case 'email':
                    if (!value) {
                        errors.push('Email wajib diisi');
                    } else {
                        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                            errors.push('Format email tidak valid');
                        }
                        if (value.length > 100) {
                            errors.push('Email maksimal 100 karakter');
                        }
                    }
                    break;
            }

            const $error = $field.next('.form-error');
            if (errors.length > 0) {
                $field.addClass('error');
                if ($error.length) {
                    $error.text(errors[0]);
                } else {
                    $('<span class="form-error"></span>')
                        .text(errors[0])
                        .insertAfter($field);
                }
                return false;
            } else {
                $field.removeClass('error');
                $error.remove();
                return true;
            }
        },

        async handleUpdate(e) {
            e.preventDefault();

            if (!this.form.valid()) {
                return;
            }

            const id = this.form.find('#edit-employee-id').val();

            const formData = {
                action: 'update_employee',
                nonce: wpAgencyData.nonce,
                id: id,
                agency_id: this.agencyId,
                division_id: this.form.find('[name="division_id"]').val(),
                name: this.form.find('[name="name"]').val().trim(),
                position: this.form.find('[name="position"]').val().trim(),
                // Roles array instead of department checkboxes
                roles: this.form.find('[name="roles[]"]').val(),
                keterangan: this.form.find('[name="keterangan"]').val().trim(),
                email: this.form.find('[name="email"]').val().trim(),
                phone: this.form.find('[name="phone"]').val().trim(),
                status: this.form.find('[name="status"]').val()
            };

            this.setLoadingState(true);

            try {
                const response = await $.ajax({
                    url: wpAgencyData.ajaxUrl,
                    type: 'POST',
                    data: formData
                });

                if (response.success) {
                    AgencyToast.success('Data karyawan berhasil diperbarui');
                    this.hideModal();
                    $(document).trigger('employee:updated', [response.data]);

                    if (window.EmployeeDataTable) {
                        window.EmployeeDataTable.refresh();
                    }
                } else {
                    AgencyToast.error(response.data?.message || 'Gagal memperbarui karyawan');
                }
            } catch (error) {
                console.error('Update employee error:', error);
                AgencyToast.error('Gagal menghubungi server');
            } finally {
                this.setLoadingState(false);
            }
        },

        setLoadingState(loading) {
            const $submitBtn = this.form.find('[type="submit"]');
            const $spinner = this.form.find('.spinner');

            if (loading) {
                $submitBtn.prop('disabled', true);
                $spinner.addClass('is-active');
                this.form.addClass('loading');
            } else {
                $submitBtn.prop('disabled', false);
                $spinner.removeClass('is-active');
                this.form.removeClass('loading');
            }
        },

        resetForm() {
            this.form[0].reset();
            this.form.find('.form-error').remove();
            this.form.find('.error').removeClass('error');
            this.form.validate().resetForm();
            this.modal.find('.modal-header h3').text('Edit Karyawan');
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        window.EditEmployeeForm = EditEmployeeForm;
        EditEmployeeForm.init();
    });

})(jQuery);
