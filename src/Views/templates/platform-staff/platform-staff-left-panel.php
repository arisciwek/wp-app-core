<?php
/**
 * Platform Staff Left Panel Template
 *
 * @package     WP_App_Core
 * @subpackage  Views/Templates
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/templates/platform-staff/platform-staff-left-panel.php
 *
 * Description: Left panel dengan DataTable listing dan filter options.
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial implementation
 */

defined('ABSPATH') || exit;

?>
<div class="wp-platform-staff-left-panel">
    <div class="postbox">
        <div class="inside">
            <!-- Action Buttons -->
            <div class="staff-actions">
                <button type="button" id="add-staff-btn" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    Tambah Staff
                </button>
                <button type="button" id="refresh-staff-btn" class="button">
                    <span class="dashicons dashicons-update"></span>
                    Refresh
                </button>
            </div>

            <!-- Filters -->
            <div class="staff-filters">
                <label for="filter-department">Department:</label>
                <select id="filter-department" name="filter_department">
                    <option value="">Semua</option>
                </select>

                <label for="filter-status">Status:</label>
                <select id="filter-status" name="filter_status">
                    <option value="">Semua</option>
                    <option value="aktif" selected>Aktif</option>
                    <option value="tidak_aktif">Tidak Aktif</option>
                </select>
            </div>

            <!-- DataTable -->
            <table id="platform-staff-table" class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Nama Lengkap</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Tanggal Bergabung</th>
                        <th>Telepon</th>
                        <th>Email</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="8" class="dataTables_empty">Memuat data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Staff Modal -->
<div id="staff-modal" class="staff-modal" style="display: none;">
    <div class="staff-modal-content">
        <span class="staff-modal-close">&times;</span>
        <h2 id="staff-modal-title">Tambah Staff</h2>
        <form id="staff-form">
            <input type="hidden" id="staff-id" name="staff_id">
            <input type="hidden" id="user-id" name="user_id">

            <div class="form-sections-container">
                <!-- Section 1: Informasi User Login -->
                <div class="form-section">
                    <h3 class="form-section-title">Informasi User Login</h3>

                    <div class="form-field">
                        <label for="user-email">Email <span class="required">*</span></label>
                        <input type="email" id="user-email" name="user_email" required maxlength="100">
                    </div>

                    <div class="form-field">
                        <label for="user-login">Username <span class="required">*</span></label>
                        <input type="text" id="user-login" name="user_login" required maxlength="60" pattern="[a-zA-Z0-9_-]+" title="Hanya huruf, angka, underscore, dan dash">
                    </div>
                </div>

                <!-- Section 2: Informasi Profile Staff -->
                <div class="form-section">
                    <h3 class="form-section-title">Informasi Profile Staff</h3>

                    <div class="form-field">
                        <label for="full-name">Nama Lengkap <span class="required">*</span></label>
                        <input type="text" id="full-name" name="full_name" required maxlength="100">
                    </div>

                    <div class="form-field">
                        <label for="department">Department</label>
                        <select id="department" name="department">
                            <option value="">- Pilih Department -</option>
                            <option value="IT">IT</option>
                            <option value="Finance">Finance</option>
                            <option value="HR">HR</option>
                            <option value="Marketing">Marketing</option>
                            <option value="Operations">Operations</option>
                            <option value="Sales">Sales</option>
                            <option value="Support">Support</option>
                            <option value="Management">Management</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="status">Status <span class="required">*</span></label>
                        <select id="status" name="status" required>
                            <option value="aktif">Aktif</option>
                            <option value="tidak_aktif">Tidak Aktif</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="hire-date">Tanggal Bergabung</label>
                        <input type="date" id="hire-date" name="hire_date">
                    </div>

                    <div class="form-field">
                        <label for="phone">Telepon</label>
                        <input type="tel" id="phone" name="phone" maxlength="20">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary">Simpan</button>
                <button type="button" class="button staff-modal-close">Batal</button>
            </div>
        </form>
    </div>
</div>
