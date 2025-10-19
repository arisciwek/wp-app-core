<?php
/**
 * Platform Staff No Access Template
 *
 * @package     WP_App_Core
 * @subpackage  Views/Templates
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/templates/platform-staff/platform-staff-no-access.php
 *
 * Description: Template untuk menampilkan pesan akses ditolak.
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial implementation
 */

defined('ABSPATH') || exit;

?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="notice notice-error">
        <p>
            <strong><?php _e('Akses Ditolak', 'wp-app-core'); ?></strong>
        </p>
        <p>
            <?php _e('Anda tidak memiliki izin untuk mengakses halaman ini.', 'wp-app-core'); ?>
        </p>
        <p>
            <?php _e('Silakan hubungi administrator jika Anda memerlukan akses.', 'wp-app-core'); ?>
        </p>
    </div>
</div>
