<?php
/**
 * Edit Platform Staff Form
 *
 * @package     WP_App_Core
 * @subpackage  Views/Platform/Forms
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/platform/forms/edit-staff-form.php
 *
 * Description: Form untuk edit platform staff.
 *              Loaded via AJAX in WPModal.
 *
 * Variables:
 * @var object $staff Staff data object
 *
 * Changelog:
 * 1.0.0 - 2025-12-25
 * - Initial implementation
 */

defined('ABSPATH') || exit;

// Ensure $staff is set
if (!isset($staff) || !is_object($staff)) {
    echo '<p class="error">' . esc_html__('Staff data not available', 'wp-app-core') . '</p>';
    return;
}
?>

<form id="edit-staff-form" class="wpapp-form" method="post">
    <input type="hidden" name="action" value="save_platform_staff">
    <input type="hidden" name="mode" value="edit">
    <input type="hidden" name="staff_id" value="<?php echo esc_attr($staff->id); ?>">
    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wpdt_nonce'); ?>">

    <!-- Two Column Layout -->
    <div class="wpapp-form-grid">
        <!-- Left Column -->
        <div class="wpapp-form-column">
            <div class="wpapp-form-field">
                <label for="staff-employee-id">
                    <?php esc_html_e('Employee ID', 'wp-app-core'); ?>
                </label>
                <input
                    type="text"
                    id="staff-employee-id"
                    value="<?php echo esc_attr($staff->employee_id ?? ''); ?>"
                    class="wpapp-input"
                    disabled
                    readonly
                >
                <small class="wpapp-field-help">
                    <?php esc_html_e('Cannot be changed', 'wp-app-core'); ?>
                </small>
            </div>

            <div class="wpapp-form-field">
                <label for="staff-full-name">
                    <?php esc_html_e('Full Name', 'wp-app-core'); ?>
                    <span class="required">*</span>
                </label>
                <input
                    type="text"
                    id="staff-full-name"
                    name="full_name"
                    value="<?php echo esc_attr($staff->name ?? $staff->full_name ?? ''); ?>"
                    class="wpapp-input"
                    required
                >
            </div>

            <div class="wpapp-form-field">
                <label for="staff-email">
                    <?php esc_html_e('Email', 'wp-app-core'); ?>
                </label>
                <input
                    type="email"
                    id="staff-email"
                    value="<?php echo esc_attr($staff->email ?? ''); ?>"
                    class="wpapp-input"
                    disabled
                    readonly
                >
                <small class="wpapp-field-help">
                    <?php esc_html_e('Linked to WP user', 'wp-app-core'); ?>
                </small>
            </div>

            <div class="wpapp-form-field">
                <label for="staff-phone">
                    <?php esc_html_e('Phone', 'wp-app-core'); ?>
                </label>
                <input
                    type="text"
                    id="staff-phone"
                    name="phone"
                    value="<?php echo esc_attr($staff->phone ?? ''); ?>"
                    class="wpapp-input"
                    placeholder="08xxxxxxxxxx"
                >
            </div>
        </div>

        <!-- Right Column -->
        <div class="wpapp-form-column">
            <div class="wpapp-form-field">
                <label for="staff-department">
                    <?php esc_html_e('Department', 'wp-app-core'); ?>
                    <span class="required">*</span>
                </label>
                <input
                    type="text"
                    id="staff-department"
                    name="department"
                    value="<?php echo esc_attr($staff->department ?? ''); ?>"
                    class="wpapp-input"
                    required
                >
            </div>

            <div class="wpapp-form-field">
                <label for="staff-hire-date">
                    <?php esc_html_e('Hire Date', 'wp-app-core'); ?>
                </label>
                <input
                    type="date"
                    id="staff-hire-date"
                    name="hire_date"
                    value="<?php echo esc_attr($staff->hire_date ?? ''); ?>"
                    class="wpapp-input"
                >
            </div>

            <div class="wpapp-form-field">
                <label for="staff-status">
                    <?php esc_html_e('Status', 'wp-app-core'); ?>
                </label>
                <select
                    id="staff-status"
                    name="status"
                    class="wpapp-input"
                >
                    <option value="aktif" <?php selected($staff->status ?? 'aktif', 'aktif'); ?>>
                        <?php esc_html_e('Active', 'wp-app-core'); ?>
                    </option>
                    <option value="tidak_aktif" <?php selected($staff->status ?? '', 'tidak_aktif'); ?>>
                        <?php esc_html_e('Inactive', 'wp-app-core'); ?>
                    </option>
                </select>
            </div>
        </div>
    </div>
</form>

<style>
.wpapp-form {
    padding: 0;
    max-width: 100%;
}

/* Two Column Grid Layout */
.wpapp-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 0;
}

.wpapp-form-column {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.wpapp-form-field {
    display: flex;
    flex-direction: column;
}

.wpapp-form-field label {
    font-weight: 600;
    margin-bottom: 6px;
    color: #1d2327;
    font-size: 13px;
}

.wpapp-form-field label .required {
    color: #dc3232;
    margin-left: 3px;
}

.wpapp-input {
    width: 100%;
    padding: 6px 10px;
    border: 1px solid #dcdcdc;
    border-radius: 3px;
    font-size: 13px;
    line-height: 1.5;
}

.wpapp-input:focus {
    border-color: #2271b1;
    outline: none;
    box-shadow: 0 0 0 1px #2271b1;
}

.wpapp-input:disabled,
.wpapp-input:read-only {
    background-color: #f6f7f7;
    color: #646970;
    cursor: not-allowed;
}

.wpapp-field-help {
    margin-top: 4px;
    color: #646970;
    font-size: 11px;
    font-style: italic;
    line-height: 1.3;
}

.wpapp-form-field.has-error .wpapp-input {
    border-color: #dc3232;
}

/* Responsive: Stack on small screens */
@media (max-width: 768px) {
    .wpapp-form-grid {
        grid-template-columns: 1fr;
    }
}
</style>
