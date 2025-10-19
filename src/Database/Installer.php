<?php
/**
 * Database Installer
 *
 * @package     WP_App_Core
 * @subpackage  Database
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Database/Installer.php
 *
 * Description: Handles database table installation and migrations
 *              for wp-app-core plugin.
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial creation
 * - Platform staff table installation
 */

namespace WPAppCore\Database;

defined('ABSPATH') || exit;

class Installer {
    // Complete list of tables to install, in dependency order
    private static $tables = [
        'app_platform_staff',
    ];

    // Table class mappings for easier maintenance
    private static $table_classes = [
        'app_platform_staff' => Tables\PlatformStaffDB::class,
    ];

    private static function debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[WP App Core Installer] " . $message);
        }
    }

    private static function verify_tables() {
        global $wpdb;
        foreach (self::$tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table_name
            ));
            if (!$table_exists) {
                self::debug("Table not found: {$table_name}");
                throw new \Exception("Failed to create table: {$table_name}");
            }
            self::debug("Verified table exists: {$table_name}");
        }
    }

    /**
     * Installs or updates the database tables
     */
    public static function run() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        global $wpdb;

        try {
            $wpdb->query('START TRANSACTION');
            self::debug("Starting database installation...");

            // Create tables in proper order
            foreach (self::$tables as $table) {
                $class = self::$table_classes[$table];
                self::debug("Creating {$table} table using {$class}...");
                dbDelta($class::get_schema());
            }

            // Run migrations for existing installations
            self::runMigrations();

            // Verify all tables were created
            self::verify_tables();

            self::debug("Database installation completed successfully.");
            $wpdb->query('COMMIT');
            return true;

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            self::debug('Database installation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Run database migrations for existing installations
     */
    private static function runMigrations() {
        global $wpdb;

        self::debug("Running migrations...");

        // Migration: Add full_name column to platform_staff table
        $table = $wpdb->prefix . 'app_platform_staff';
        $columns = $wpdb->get_results("DESCRIBE {$table}");

        $has_full_name = false;
        foreach ($columns as $column) {
            if ($column->Field === 'full_name') {
                $has_full_name = true;
                break;
            }
        }

        if (!$has_full_name) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN full_name varchar(100) NOT NULL AFTER employee_id");
            $wpdb->query("ALTER TABLE {$table} ADD INDEX full_name_index (full_name)");
            self::debug("Added full_name column to platform_staff table");
        }

        self::debug("Migrations completed");
    }

    /**
     * Drops all plugin tables
     * Called during uninstall
     */
    public static function dropTables() {
        global $wpdb;

        self::debug("Dropping all tables...");

        foreach (self::$tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
            self::debug("Dropped table: {$table_name}");
        }

        self::debug("All tables dropped successfully.");
    }
}
