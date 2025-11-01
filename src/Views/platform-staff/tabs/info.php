<?php
/**
 * Platform Staff Information Tab - Pure View Pattern
 *
 * @package     WP_App_Core
 * @subpackage  Views/Platform-Staff/Tabs
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/platform-staff/tabs/info.php
 *
 * Description: Pure HTML view for platform staff information tab.
 *              Direct template - no controller logic, no hooks, no partials.
 *              Follows true MVC View pattern like wp-agency.
 *
 * Pattern: Simple and Direct
 * - This file: Pure HTML template
 * - Variables: $staff passed directly from controller
 * - Scope: LOCAL (platform-staff-* classes)
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-1192)
 * - Initial creation
 * - Follows wp-agency pattern
 * - Pure presentation layer
 */

defined('ABSPATH') || exit;

// $staff variable is passed from controller
if (!isset($staff)) {
    echo '<p>' . __('Data not available', 'wp-app-core') . '</p>';
    return;
}
?>

<div class="platform-staff-details-grid">
    <!-- Staff Information -->
    <div class="platform-staff-detail-section">
        <h3><?php esc_html_e('Staff Information', 'wp-app-core'); ?></h3>

        <div class="platform-staff-detail-row">
            <label><?php esc_html_e('Employee ID', 'wp-app-core'); ?>:</label>
            <span><?php echo esc_html($staff->employee_id ?? '-'); ?></span>
        </div>

        <div class="platform-staff-detail-row">
            <label><?php esc_html_e('Full Name', 'wp-app-core'); ?>:</label>
            <span><?php echo esc_html($staff->full_name ?? '-'); ?></span>
        </div>

        <div class="platform-staff-detail-row">
            <label><?php esc_html_e('Department', 'wp-app-core'); ?>:</label>
            <span><?php echo esc_html($staff->department ?? '-'); ?></span>
        </div>

        <div class="platform-staff-detail-row">
            <label><?php esc_html_e('Status', 'wp-app-core'); ?>:</label>
            <span>
                <?php
                $status_class = ($staff->status ?? '') === 'aktif' ? 'success' : 'error';
                $status_text = ($staff->status ?? '') === 'aktif'
                    ? __('Active', 'wp-app-core')
                    : __('Inactive', 'wp-app-core');
                ?>
                <span class="wpapp-badge wpapp-badge-<?php echo esc_attr($status_class); ?>">
                    <?php echo esc_html($status_text); ?>
                </span>
            </span>
        </div>
    </div>

    <!-- Employment Details -->
    <div class="platform-staff-detail-section">
        <h3><?php esc_html_e('Employment Details', 'wp-app-core'); ?></h3>

        <div class="platform-staff-detail-row">
            <label><?php esc_html_e('Hire Date', 'wp-app-core'); ?>:</label>
            <span>
                <?php
                if (!empty($staff->hire_date)) {
                    echo esc_html(date('d/m/Y', strtotime($staff->hire_date)));
                } else {
                    echo '-';
                }
                ?>
            </span>
        </div>

        <div class="platform-staff-detail-row">
            <label><?php esc_html_e('Phone', 'wp-app-core'); ?>:</label>
            <span><?php echo esc_html($staff->phone ?? '-'); ?></span>
        </div>
    </div>

    <!-- WordPress User Information -->
    <div class="platform-staff-detail-section">
        <h3><?php esc_html_e('WordPress User', 'wp-app-core'); ?></h3>

        <?php
        $user = isset($staff->user_id) ? get_userdata($staff->user_id) : null;
        ?>

        <div class="platform-staff-detail-row">
            <label><?php esc_html_e('Username', 'wp-app-core'); ?>:</label>
            <span><?php echo $user ? esc_html($user->user_login) : '-'; ?></span>
        </div>

        <div class="platform-staff-detail-row">
            <label><?php esc_html_e('Email', 'wp-app-core'); ?>:</label>
            <span><?php echo $user ? esc_html($user->user_email) : '-'; ?></span>
        </div>

        <div class="platform-staff-detail-row">
            <label><?php esc_html_e('User ID', 'wp-app-core'); ?>:</label>
            <span><?php echo esc_html($staff->user_id ?? '-'); ?></span>
        </div>
    </div>

    <!-- Timestamps -->
    <div class="platform-staff-detail-section">
        <h3><?php esc_html_e('Timestamps', 'wp-app-core'); ?></h3>

        <div class="platform-staff-detail-row">
            <label><?php esc_html_e('Created At', 'wp-app-core'); ?>:</label>
            <span>
                <?php
                if (!empty($staff->created_at)) {
                    echo esc_html(date('d/m/Y H:i', strtotime($staff->created_at)));
                } else {
                    echo '-';
                }
                ?>
            </span>
        </div>

        <div class="platform-staff-detail-row">
            <label><?php esc_html_e('Updated At', 'wp-app-core'); ?>:</label>
            <span>
                <?php
                if (!empty($staff->updated_at)) {
                    echo esc_html(date('d/m/Y H:i', strtotime($staff->updated_at)));
                } else {
                    echo '-';
                }
                ?>
            </span>
        </div>
    </div>
</div>
