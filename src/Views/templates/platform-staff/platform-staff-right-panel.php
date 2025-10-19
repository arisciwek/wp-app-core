<?php
/**
 * Platform Staff Right Panel Template
 *
 * @package     WP_App_Core
 * @subpackage  Views/Templates
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/templates/platform-staff/platform-staff-right-panel.php
 *
 * Description: Right panel untuk menampilkan detail staff.
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial implementation
 */

defined('ABSPATH') || exit;

?>
<div class="staff-detail-panel">
    <div class="panel-header">
        <h2>Detail Staff</h2>
        <button type="button" id="close-panel-btn" class="button">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
    </div>

    <div class="panel-content">
        <div class="staff-info-section">
            <h3>Informasi Dasar</h3>
            <table class="staff-info-table">
                <tr>
                    <td class="label">Employee ID:</td>
                    <td class="value" id="detail-employee-id">-</td>
                </tr>
                <tr>
                    <td class="label">Nama Lengkap:</td>
                    <td class="value" id="detail-full-name">-</td>
                </tr>
                <tr>
                    <td class="label">Department:</td>
                    <td class="value" id="detail-department">-</td>
                </tr>
                <tr>
                    <td class="label">Status:</td>
                    <td class="value" id="detail-status">-</td>
                </tr>
                <tr>
                    <td class="label">Tanggal Bergabung:</td>
                    <td class="value" id="detail-hire-date">-</td>
                </tr>
                <tr>
                    <td class="label">Telepon:</td>
                    <td class="value" id="detail-phone">-</td>
                </tr>
            </table>
        </div>

        <div class="staff-info-section">
            <h3>Informasi User</h3>
            <table class="staff-info-table">
                <tr>
                    <td class="label">Username:</td>
                    <td class="value" id="detail-user-login">-</td>
                </tr>
                <tr>
                    <td class="label">Email:</td>
                    <td class="value" id="detail-user-email">-</td>
                </tr>
            </table>
        </div>

        <div class="staff-info-section">
            <h3>Informasi Sistem</h3>
            <table class="staff-info-table">
                <tr>
                    <td class="label">Dibuat:</td>
                    <td class="value" id="detail-created-at">-</td>
                </tr>
                <tr>
                    <td class="label">Diubah:</td>
                    <td class="value" id="detail-updated-at">-</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="panel-actions">
        <button type="button" id="edit-staff-btn" class="button button-primary">
            <span class="dashicons dashicons-edit"></span>
            Edit
        </button>
        <button type="button" id="delete-staff-btn" class="button button-link-delete">
            <span class="dashicons dashicons-trash"></span>
            Hapus
        </button>
    </div>
</div>
