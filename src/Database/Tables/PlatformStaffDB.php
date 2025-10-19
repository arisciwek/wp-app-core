<?php
/**
 * Platform Staff Table Schema
 *
 * @package     WP_App_Core
 * @subpackage  Database/Tables
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Database/Tables/PlatformStaffDB.php
 *
 * Description: Mendefinisikan struktur tabel platform staff.
 *              Table prefix yang digunakan adalah 'app_'.
 *              Stores platform staff profile data.
 *
 * Fields:
 * - id             : Primary key
 * - user_id        : ID User WP (foreign key to wp_users)
 * - employee_id    : Unique employee identifier (STAFF-001, etc.)
 * - department     : Platform department
 * - hire_date      : Date joined platform team
 * - phone          : Contact phone number
 * - created_at     : Timestamp pembuatan
 * - updated_at     : Timestamp update terakhir
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial version
 * - Platform staff profile data storage
 */

namespace WPAppCore\Database\Tables;

defined('ABSPATH') || exit;

class PlatformStaffDB {
    public static function get_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_platform_staff';
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL auto_increment,
            user_id bigint(20) UNSIGNED NOT NULL,
            employee_id varchar(20) NOT NULL,
            full_name varchar(100) NOT NULL,
            department varchar(50) NULL,
            hire_date date NULL,
            phone varchar(20) NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id),
            UNIQUE KEY employee_id (employee_id),
            KEY department_index (department),
            KEY full_name_index (full_name)
        ) $charset_collate ENGINE=InnoDB;";
    }
}
