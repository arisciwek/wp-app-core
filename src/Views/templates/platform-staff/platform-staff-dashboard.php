<?php
/**
 * Platform Staff Dashboard Template
 *
 * @package     WP_App_Core
 * @subpackage  Views/Templates
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/templates/platform-staff/platform-staff-dashboard.php
 *
 * Description: Main dashboard template untuk manajemen platform staff.
 *              Includes statistics overview, DataTable listing,
 *              right panel details, dan action buttons.
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial implementation
 * - Statistics display
 * - Staff listing
 * - Panel navigation
 */

defined('ABSPATH') || exit;

?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Dashboard Section -->
    <div class="wp-platform-staff-dashboard">
        <div class="postbox">
            <div class="inside">
                <div class="main">
                    <h2>Statistik Platform Staff</h2>
                    <div class="staff-stats-container">
                        <div class="staff-stat-box">
                            <h3>Total Staff</h3>
                            <p class="staff-stat-number"><span id="total-staff">0</span></p>
                        </div>
                        <div class="staff-stat-box">
                            <h3>Bergabung 30 Hari Terakhir</h3>
                            <p class="staff-stat-number" id="recent-hires">0</p>
                        </div>
                        <div class="staff-stat-box">
                            <h3>Total Department</h3>
                            <p class="staff-stat-number" id="total-departments">0</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="wp-platform-staff-content-area">
        <div id="wp-platform-staff-main-container" class="wp-platform-staff-container">
            <!-- Left Panel -->
            <?php require_once WP_APP_CORE_PATH . 'src/Views/templates/platform-staff/platform-staff-left-panel.php'; ?>

            <!-- Right Panel -->
            <div id="wp-platform-staff-right-panel" class="wp-platform-staff-right-panel hidden">
                <?php require_once WP_APP_CORE_PATH . 'src/Views/templates/platform-staff/platform-staff-right-panel.php'; ?>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="staff-toast" class="staff-toast"></div>
</div>
