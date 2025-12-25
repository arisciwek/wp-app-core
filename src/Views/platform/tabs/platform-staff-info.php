<?php
/**
 * Platform Staff Info Tab
 *
 * @package     WP_App_Core
 * @subpackage  Views/Platform/Tabs
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/platform/tabs/info.php
 *
 * Description: Tab untuk menampilkan informasi platform staff.
 *              Shows staff details and contact info.
 *
 * Changelog:
 * 1.0.0 - 2025-12-25
 * - Initial implementation for wp-datatable integration
 */

defined('ABSPATH') || exit;

// $data is passed from controller (staff object)
if (!isset($data) || !is_object($data)) {
    echo '<p>' . esc_html__('Staff data not available', 'wp-app-core') . '</p>';
    return;
}

$staff = $data;
?>

<div class="staff-info-tab">
    <div class="staff-info-section">
        <h3><?php echo esc_html__('Staff Details', 'wp-app-core'); ?></h3>

        <div class="staff-info-row">
            <label><?php echo esc_html__('Employee ID:', 'wp-app-core'); ?></label>
            <span><?php echo esc_html($staff->employee_id ?? '-'); ?></span>
        </div>

        <div class="staff-info-row">
            <label><?php echo esc_html__('Name:', 'wp-app-core'); ?></label>
            <span><?php echo esc_html($staff->name ?? $staff->full_name ?? '-'); ?></span>
        </div>

        <div class="staff-info-row">
            <label><?php echo esc_html__('Email:', 'wp-app-core'); ?></label>
            <span><?php echo esc_html($staff->email ?? '-'); ?></span>
        </div>

        <div class="staff-info-row">
            <label><?php echo esc_html__('Phone:', 'wp-app-core'); ?></label>
            <span><?php echo esc_html($staff->phone ?? '-'); ?></span>
        </div>

        <div class="staff-info-row">
            <label><?php echo esc_html__('Department:', 'wp-app-core'); ?></label>
            <span><?php echo esc_html($staff->department ?? '-'); ?></span>
        </div>

        <div class="staff-info-row">
            <label><?php echo esc_html__('Hire Date:', 'wp-app-core'); ?></label>
            <span>
                <?php
                if (!empty($staff->hire_date)) {
                    echo esc_html(date_i18n('d F Y', strtotime($staff->hire_date)));
                } else {
                    echo '-';
                }
                ?>
            </span>
        </div>

        <div class="staff-info-row">
            <label><?php echo esc_html__('Status:', 'wp-app-core'); ?></label>
            <span class="staff-status-badge staff-status-<?php echo esc_attr($staff->status ?? 'aktif'); ?>">
                <?php
                $status = $staff->status ?? 'aktif';
                echo esc_html($status === 'aktif' ? __('Active', 'wp-app-core') : __('Inactive', 'wp-app-core'));
                ?>
            </span>
        </div>
    </div>
</div>

<style>
.staff-info-tab {
    padding: 20px;
}

.staff-info-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.staff-info-section h3 {
    margin: 0 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    font-size: 16px;
    font-weight: 600;
}

.staff-info-row {
    display: flex;
    padding: 10px 0;
    border-bottom: 1px solid #f5f5f5;
}

.staff-info-row:last-child {
    border-bottom: none;
}

.staff-info-row label {
    min-width: 150px;
    font-weight: 600;
    color: #666;
}

.staff-info-row span {
    color: #333;
}

.staff-status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.staff-status-aktif {
    background-color: #d4edda;
    color: #155724;
}

.staff-status-tidak_aktif,
.staff-status-inactive {
    background-color: #f8d7da;
    color: #721c24;
}
</style>
