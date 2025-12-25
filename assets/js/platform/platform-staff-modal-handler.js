/**
 * Platform Staff Modal Handler
 *
 * @package     WP_App_Core
 * @subpackage  Assets/JS/Platform
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/platform/platform-staff-modal-handler.js
 *
 * Description: Handles modal CRUD operations for Platform Staff.
 *              Uses centralized modal system from wp-modal plugin.
 *              Pattern inspired by wp-customer CustomerModalHandler.
 *
 * Dependencies:
 * - jQuery
 * - WPModal (from wp-modal plugin)
 * - wpPlatformStaffConfig localized object
 *
 * Changelog:
 * 1.0.0 - 2025-12-25
 * - Initial implementation
 * - Edit staff modal
 * - Delete staff confirmation
 */

(function($) {
    'use strict';

    /**
     * Platform Staff Modal Handler
     */
    const PlatformStaffModalHandler = {

        /**
         * Initialize modal handlers
         */
        init() {
            console.log('[StaffModal] Initializing...');
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents() {
            // Edit Staff button
            $(document).on('click', '.staff-edit-btn', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Prevent row click
                const staffId = $(e.currentTarget).data('id');
                console.log('[StaffModal] Edit button clicked for staff:', staffId);
                this.showEditModal(staffId);
            });

            // Delete Staff button
            $(document).on('click', '.staff-delete-btn', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Prevent row click
                const staffId = $(e.currentTarget).data('id');
                console.log('[StaffModal] Delete button clicked for staff:', staffId);
                this.showDeleteConfirm(staffId);
            });

            console.log('[StaffModal] Events bound');
        },

        /**
         * Show Edit Staff Modal
         *
         * @param {number} staffId Staff ID to edit
         */
        showEditModal(staffId) {
            console.log('[StaffModal] Opening edit staff modal for ID:', staffId);

            // Check if WPModal is available
            if (typeof WPModal === 'undefined') {
                console.error('[StaffModal] WPModal not found!');
                alert('Modal system not available. Please refresh the page.');
                return;
            }

            // Show modal with form (mode=edit)
            WPModal.show({
                type: 'form',
                title: 'Edit Platform Staff',
                size: 'large',
                bodyUrl: wpPlatformStaffConfig.ajaxUrl +
                         '?action=get_platform_staff_form' +
                         '&mode=edit' +
                         '&staff_id=' + staffId +
                         '&nonce=' + wpPlatformStaffConfig.nonce,
                buttons: {
                    cancel: {
                        label: 'Cancel',
                        class: 'button'
                    },
                    submit: {
                        label: 'Update Staff',
                        class: 'button button-primary',
                        type: 'submit'
                    }
                },
                onSubmit: (formData, $form) => {
                    return this.handleSave(formData, $form);
                }
            });
        },

        /**
         * Show Delete Confirmation
         *
         * @param {number} staffId Staff ID to delete
         */
        showDeleteConfirm(staffId) {
            console.log('[StaffModal] Showing delete confirm for staff ID:', staffId);

            // Check if WPModal is available
            if (typeof WPModal === 'undefined') {
                console.error('[StaffModal] WPModal not found!');
                alert('Modal system not available. Please refresh the page.');
                return;
            }

            WPModal.confirm({
                title: 'Delete Platform Staff',
                message: 'Are you sure you want to delete this staff member? This action cannot be undone.',
                confirmText: 'Delete',
                confirmClass: 'button-danger',
                onConfirm: () => {
                    this.handleDelete(staffId);
                }
            });
        },

        /**
         * Handle form save (update)
         *
         * @param {Object} formData Form data
         * @param {jQuery} $form Form element
         * @return {boolean} false to prevent default
         */
        handleSave(formData, $form) {
            console.log('[StaffModal] Saving staff...');

            // Remove any existing error messages
            $('.wpapp-modal-error').remove();
            $('.wpapp-field-error').remove();
            $('.wpapp-form-field').removeClass('has-error');

            // Validate form first
            if (!this.validateForm($form)) {
                console.log('[StaffModal] Form validation failed');
                return false;
            }

            // Create FormData from form element if not already FormData
            if (!(formData instanceof FormData)) {
                console.log('[StaffModal] Creating new FormData from form');
                formData = new FormData($form[0]);
            }

            // Show loading
            WPModal.loading(true);

            // Submit via AJAX
            $.ajax({
                url: wpPlatformStaffConfig.ajaxUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        console.log('[StaffModal] Save successful:', response);

                        // Get staff ID from response
                        const staffId = response.data.staff ? response.data.staff.id : null;
                        console.log('[StaffModal] Staff ID from response:', staffId);

                        // Stop loading
                        WPModal.loading(false);

                        // Hide modal
                        WPModal.hide();

                        // Refresh DataTable, then open panel
                        if (window.platformStaffDataTableInstance) {
                            console.log('[StaffModal] Refreshing DataTable...');

                            window.platformStaffDataTableInstance.ajax.reload(function() {
                                console.log('[StaffModal] DataTable reload completed');

                                // Open panel after reload
                                if (staffId) {
                                    console.log('[StaffModal] Triggering panel open request for staff:', staffId);
                                    $(document).trigger('wpdt:panel-open-request', {
                                        entity: 'platform_staff',
                                        id: staffId
                                    });
                                }
                            }, false);
                        }

                        // Reload statistics
                        setTimeout(() => {
                            $(document).trigger('wpdt:refresh-stats');
                        }, 500);

                    } else {
                        console.error('[StaffModal] Save failed:', response);

                        // Stop loading
                        WPModal.loading(false);

                        // Show error message inside modal
                        this.showErrorInModal(response.data.message || 'Failed to save staff');
                    }
                },
                error: (xhr, status, error) => {
                    WPModal.loading(false);
                    console.error('[StaffModal] AJAX error:', error);

                    let errorMessage = 'Network error. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMessage = xhr.responseJSON.data.message;
                    }

                    this.showErrorInModal(errorMessage);
                }
            });

            return false; // Prevent default form submission
        },

        /**
         * Handle staff deletion
         *
         * @param {number} staffId Staff ID to delete
         */
        handleDelete(staffId) {
            console.log('[StaffModal] Deleting staff ID:', staffId);

            // Show loading
            WPModal.loading(true, 'Deleting staff...');

            const deleteData = {
                action: 'delete_platform_staff',
                staff_id: staffId,
                nonce: wpPlatformStaffConfig.nonce
            };

            $.ajax({
                url: wpPlatformStaffConfig.ajaxUrl,
                method: 'POST',
                data: deleteData,
                success: (response) => {
                    WPModal.loading(false);

                    if (response.success) {
                        console.log('[StaffModal] Delete successful:', response);

                        // Refresh DataTable
                        if (window.platformStaffDataTableInstance) {
                            console.log('[StaffModal] Refreshing DataTable...');
                            window.platformStaffDataTableInstance.ajax.reload(null, false);
                        }

                        // Reload statistics
                        setTimeout(() => {
                            $(document).trigger('wpdt:refresh-stats');
                        }, 500);
                    } else {
                        console.error('[StaffModal] Delete failed:', response);
                        WPModal.info({
                            infoType: 'error',
                            title: 'Error',
                            message: response.data.message || 'Failed to delete staff',
                            autoClose: 5000
                        });
                    }
                },
                error: (xhr, status, error) => {
                    WPModal.loading(false);
                    console.error('[StaffModal] Delete AJAX error:', error);

                    WPModal.info({
                        infoType: 'error',
                        title: 'Error',
                        message: 'Network error. Please try again.',
                        autoClose: 5000
                    });
                }
            });
        },

        /**
         * Show error message inside modal
         *
         * @param {string} message Error message to display
         */
        showErrorInModal(message) {
            // Remove any existing error messages
            $('.wpapp-modal-error').remove();

            // Create error message element
            const $errorDiv = $('<div class="wpapp-modal-error" style="' +
                'background: #dc3232; ' +
                'color: white; ' +
                'padding: 12px 15px; ' +
                'margin: 0 0 15px 0; ' +
                'border-radius: 4px; ' +
                'font-size: 14px; ' +
                'line-height: 1.5;">' +
                '<strong>Error:</strong> ' + message +
                '</div>');

            // Insert error message at top of modal body
            $('.wpapp-modal-body').prepend($errorDiv);

            // Scroll to top of modal to show error
            $('.wpapp-modal-body').scrollTop(0);

            // Auto-remove after 10 seconds
            setTimeout(function() {
                $errorDiv.fadeOut(400, function() {
                    $(this).remove();
                });
            }, 10000);
        },

        /**
         * Validate form before submit
         *
         * @param {jQuery} $form Form element
         * @return {boolean} True if valid, false if invalid
         */
        validateForm($form) {
            let isValid = true;
            const errors = [];

            // Full Name (required)
            const fullName = $form.find('#staff-full-name').val();
            if (!fullName || fullName.trim() === '') {
                this.showFieldError('#staff-full-name', 'Full name is required');
                errors.push('Full name is required');
                isValid = false;
            }

            // Department (required)
            const department = $form.find('#staff-department').val();
            if (!department || department.trim() === '') {
                this.showFieldError('#staff-department', 'Department is required');
                errors.push('Department is required');
                isValid = false;
            }

            // Show summary error if there are errors
            if (!isValid) {
                const errorMessage = 'Please fix the following errors:<br>• ' + errors.join('<br>• ');
                this.showErrorInModal(errorMessage);
            }

            return isValid;
        },

        /**
         * Show error message for specific field
         *
         * @param {string} fieldSelector Field selector
         * @param {string} message Error message
         */
        showFieldError(fieldSelector, message) {
            const $field = $(fieldSelector);
            const $wrapper = $field.closest('.wpapp-form-field');

            // Add error class to wrapper
            $wrapper.addClass('has-error');

            // Remove existing error message
            $wrapper.find('.wpapp-field-error').remove();

            // Add error message below field
            const $errorMsg = $('<span class="wpapp-field-error" style="' +
                'color: #dc3232; ' +
                'font-size: 12px; ' +
                'display: block; ' +
                'margin-top: 4px;">' +
                message +
                '</span>');

            $field.after($errorMsg);

            // Add red border to field
            $field.css('border-color', '#dc3232');

            // Remove error on input
            $field.one('input change', function() {
                $wrapper.removeClass('has-error');
                $wrapper.find('.wpapp-field-error').remove();
                $(this).css('border-color', '');
            });
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        console.log('[StaffModal] Document ready');
        PlatformStaffModalHandler.init();
    });

    // Export to global scope
    window.PlatformStaffModalHandler = PlatformStaffModalHandler;

})(jQuery);
