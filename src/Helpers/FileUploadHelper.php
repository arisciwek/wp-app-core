<?php
/**
 * File Upload Helper
 *
 * @package     WP_App_Core
 * @subpackage  Helpers
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Helpers/FileUploadHelper.php
 *
 * Description: Global helper class untuk handle file upload operations.
 *              Digunakan oleh semua plugin dalam ekosistem wp-app.
 *              Menyediakan utilities untuk directory creation, filename generation,
 *              file validation, dan file management.
 *
 * Methods:
 * - createMembershipInvoiceDirectory()  : Create directory structure by year/month
 * - generateProofFileName()             : Generate safe filename with invoice number
 * - validateProofFile()                 : Validate uploaded file (type, size, security)
 * - getFileInfo()                       : Get file information
 * - deleteProofFile()                   : Delete proof file
 * - getMimeType()                       : Get actual MIME type from file
 * - isAllowedMimeType()                 : Check if MIME type is allowed
 *
 * Constants:
 * - WP_CUSTOMER_MAX_PROOF_FILE_SIZE    : Max file size (default 5MB, overrideable)
 *
 * Changelog:
 * 1.0.0 - 2025-10-18 (Task-2162 Review-01)
 * - Moved from wp-customer to wp-app-core for global use
 * - Support for JPG, PNG, PDF uploads
 * - Directory structure: {year}/{month}/inv-{number}-{timestamp}.{ext}
 * - Max file size: 5MB (configurable)
 * - MIME type validation with actual file check
 */

namespace WPAppCore\Helpers;

defined('ABSPATH') || exit;

class FileUploadHelper {

    /**
     * Allowed MIME types for payment proof
     */
    const ALLOWED_MIME_TYPES = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png'  => ['png'],
        'application/pdf' => ['pdf']
    ];

    /**
     * Base upload directory relative to wp-content/uploads
     */
    const BASE_UPLOAD_DIR = 'wp-customer/membership-invoices';

    /**
     * Create membership invoice directory structure
     *
     * Creates directory: /wp-content/uploads/wp-customer/membership-invoices/{year}/{month}/
     *
     * @param int $year  Year (e.g., 2025)
     * @param int $month Month (1-12)
     * @return array|WP_Error Array with 'path' and 'url' on success, WP_Error on failure
     */
    public static function createMembershipInvoiceDirectory($year, $month) {
        // Get WordPress upload directory
        $upload_dir = wp_upload_dir();

        if ($upload_dir['error']) {
            return new \WP_Error(
                'upload_dir_error',
                $upload_dir['error']
            );
        }

        // Build directory path: /uploads/wp-customer/membership-invoices/2025/01/
        $month_padded = str_pad($month, 2, '0', STR_PAD_LEFT);
        $relative_path = self::BASE_UPLOAD_DIR . '/' . $year . '/' . $month_padded;
        $directory_path = $upload_dir['basedir'] . '/' . $relative_path;
        $directory_url = $upload_dir['baseurl'] . '/' . $relative_path;

        // Create directory if not exists
        if (!file_exists($directory_path)) {
            if (!wp_mkdir_p($directory_path)) {
                return new \WP_Error(
                    'directory_creation_failed',
                    __('Gagal membuat direktori upload', 'wp-customer')
                );
            }

            // Create .htaccess for security (prevent directory listing)
            $htaccess_file = $directory_path . '/.htaccess';
            $htaccess_content = "Options -Indexes\n";
            @file_put_contents($htaccess_file, $htaccess_content);

            // Create index.php for extra security
            $index_file = $directory_path . '/index.php';
            @file_put_contents($index_file, '<?php // Silence is golden');
        }

        return [
            'path' => $directory_path,
            'url'  => $directory_url
        ];
    }

    /**
     * Generate safe filename for proof file
     *
     * Pattern: inv-{invoice_number}-{timestamp}.{ext}
     * Example: inv-20251018-90009-1737123456.jpg
     *
     * @param string $invoice_number Invoice number (e.g., INV-20251018-90009)
     * @param string $extension      File extension
     * @return string Safe filename (lowercase)
     */
    public static function generateProofFileName($invoice_number, $extension) {
        // Remove 'INV-' prefix if exists, convert to lowercase
        $invoice_number = str_replace('INV-', '', $invoice_number);
        $invoice_number = strtolower($invoice_number);

        // Generate timestamp for uniqueness
        $timestamp = time();

        // Convert extension to lowercase
        $extension = strtolower($extension);

        // Build filename: inv-{number}-{timestamp}.{ext}
        return sprintf('inv-%s-%d.%s', $invoice_number, $timestamp, $extension);
    }

    /**
     * Validate uploaded proof file
     *
     * Validates:
     * - File uploaded via HTTP POST
     * - File size within limit
     * - MIME type allowed
     * - Extension matches MIME type
     * - No upload errors
     *
     * @param array $file $_FILES array element
     * @return true|WP_Error True on success, WP_Error on failure
     */
    public static function validateProofFile($file) {
        // Check if file uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return new \WP_Error(
                'no_file_uploaded',
                __('Tidak ada file yang diupload', 'wp-customer')
            );
        }

        // Check upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new \WP_Error(
                'upload_error',
                self::getUploadErrorMessage($file['error'])
            );
        }

        // Get max file size (5MB default, configurable)
        $max_size = defined('WP_CUSTOMER_MAX_PROOF_FILE_SIZE')
            ? WP_CUSTOMER_MAX_PROOF_FILE_SIZE
            : 5 * 1024 * 1024; // 5MB

        // Check file size
        if ($file['size'] > $max_size) {
            $max_size_mb = $max_size / (1024 * 1024);
            return new \WP_Error(
                'file_too_large',
                sprintf(
                    __('Ukuran file maksimal %s MB', 'wp-customer'),
                    $max_size_mb
                )
            );
        }

        // Get actual MIME type from file
        $mime_type = self::getMimeType($file['tmp_name']);

        // Check if MIME type allowed
        if (!self::isAllowedMimeType($mime_type)) {
            return new \WP_Error(
                'invalid_file_type',
                __('Hanya file JPG, PNG, atau PDF yang diperbolehkan', 'wp-customer')
            );
        }

        // Get file extension from original filename
        $file_parts = pathinfo($file['name']);
        $extension = isset($file_parts['extension']) ? strtolower($file_parts['extension']) : '';

        // Verify extension matches MIME type
        if (!self::extensionMatchesMimeType($extension, $mime_type)) {
            return new \WP_Error(
                'extension_mismatch',
                __('Ekstensi file tidak sesuai dengan tipe file', 'wp-customer')
            );
        }

        return true;
    }

    /**
     * Get actual MIME type from file
     *
     * @param string $file_path Path to file
     * @return string MIME type
     */
    public static function getMimeType($file_path) {
        // Try to get MIME type using WordPress function
        $filetype = wp_check_filetype($file_path);

        if ($filetype['type']) {
            return $filetype['type'];
        }

        // Fallback to PHP's mime_content_type if available
        if (function_exists('mime_content_type')) {
            return mime_content_type($file_path);
        }

        // Last fallback to finfo
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file_path);
            finfo_close($finfo);
            return $mime;
        }

        return 'application/octet-stream';
    }

    /**
     * Check if MIME type is allowed
     *
     * @param string $mime_type MIME type to check
     * @return bool True if allowed
     */
    public static function isAllowedMimeType($mime_type) {
        return array_key_exists($mime_type, self::ALLOWED_MIME_TYPES);
    }

    /**
     * Check if extension matches MIME type
     *
     * @param string $extension File extension
     * @param string $mime_type MIME type
     * @return bool True if matches
     */
    public static function extensionMatchesMimeType($extension, $mime_type) {
        if (!isset(self::ALLOWED_MIME_TYPES[$mime_type])) {
            return false;
        }

        return in_array($extension, self::ALLOWED_MIME_TYPES[$mime_type]);
    }

    /**
     * Get file information
     *
     * @param string $file_path Full path to file
     * @return array|WP_Error File info or error
     */
    public static function getFileInfo($file_path) {
        if (!file_exists($file_path)) {
            return new \WP_Error(
                'file_not_found',
                __('File tidak ditemukan', 'wp-customer')
            );
        }

        $upload_dir = wp_upload_dir();
        $relative_path = str_replace($upload_dir['basedir'] . '/', '', $file_path);

        return [
            'path'      => $file_path,
            'url'       => $upload_dir['baseurl'] . '/' . $relative_path,
            'size'      => filesize($file_path),
            'mime_type' => self::getMimeType($file_path),
            'relative'  => $relative_path
        ];
    }

    /**
     * Delete proof file
     *
     * @param string $file_path Full or relative path to file
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function deleteProofFile($file_path) {
        // If relative path, convert to absolute
        if (strpos($file_path, ABSPATH) !== 0) {
            $upload_dir = wp_upload_dir();
            $file_path = $upload_dir['basedir'] . '/' . $file_path;
        }

        if (!file_exists($file_path)) {
            return new \WP_Error(
                'file_not_found',
                __('File tidak ditemukan', 'wp-customer')
            );
        }

        if (!is_writable($file_path)) {
            return new \WP_Error(
                'file_not_writable',
                __('File tidak bisa dihapus', 'wp-customer')
            );
        }

        if (!unlink($file_path)) {
            return new \WP_Error(
                'delete_failed',
                __('Gagal menghapus file', 'wp-customer')
            );
        }

        return true;
    }

    /**
     * Get upload error message
     *
     * @param int $error_code PHP upload error code
     * @return string Error message
     */
    private static function getUploadErrorMessage($error_code) {
        $messages = [
            UPLOAD_ERR_INI_SIZE   => __('File terlalu besar (server limit)', 'wp-customer'),
            UPLOAD_ERR_FORM_SIZE  => __('File terlalu besar (form limit)', 'wp-customer'),
            UPLOAD_ERR_PARTIAL    => __('File hanya terupload sebagian', 'wp-customer'),
            UPLOAD_ERR_NO_FILE    => __('Tidak ada file yang diupload', 'wp-customer'),
            UPLOAD_ERR_NO_TMP_DIR => __('Temporary folder tidak ada', 'wp-customer'),
            UPLOAD_ERR_CANT_WRITE => __('Gagal menulis file ke disk', 'wp-customer'),
            UPLOAD_ERR_EXTENSION  => __('Upload dihentikan oleh extension', 'wp-customer'),
        ];

        return isset($messages[$error_code])
            ? $messages[$error_code]
            : __('Upload error tidak diketahui', 'wp-customer');
    }
}
