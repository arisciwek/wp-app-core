<?php
/**
 * Platform Permission Model
 *
 * @package     WP_App_Core
 * @subpackage  Models/Settings
 * @version     4.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Models/Settings/PlatformPermissionModel.php
 *
 * Description: Model untuk mengelola permission platform staff.
 *              REFACTORED: Now extends AbstractPermissionsModel.
 *              Only defines data - all logic in abstract.
 *
 * Changelog:
 * 4.0.0 - 2025-01-12 (TODO-1206)
 * - BREAKING: Now extends AbstractPermissionsModel
 * - Reduced from 910 lines to ~400 lines (56% reduction)
 * - Removed all methods - now in abstract
 * - Only capabilities, groups, descriptions, defaults remain
 * - All logic (resetToDefault, updateRoleCapabilities, etc) in abstract
 * 1.0.4 - 2025-10-19
 * - Added WP Customer capabilities for platform roles
 * 1.0.0 - 2025-10-19
 * - Initial release
 */

namespace WPAppCore\Models\Settings;

use WPAppCore\Models\Abstract\AbstractPermissionsModel;

class PlatformPermissionModel extends AbstractPermissionsModel {

    /**
     * Get role manager class
     */
    protected function getRoleManagerClass(): string {
        return 'WP_App_Core_Role_Manager';
    }

    /**
     * Get all available capabilities
     */
    public function getAllCapabilities(): array {
        return [
            // Platform Management
            'view_platform_dashboard' => 'Lihat Platform Dashboard',
            'manage_platform_settings' => 'Kelola Platform Settings',
            'manage_system_configuration' => 'Kelola Konfigurasi Sistem',
            'view_system_health' => 'Lihat System Health/Status',
            'manage_maintenance_mode' => 'Kelola Maintenance Mode',
            'manage_api_integrations' => 'Kelola API Keys/Integrations',

            // User & Role Management (Platform Staff)
            'view_platform_users' => 'Lihat Platform Users',
            'create_platform_users' => 'Buat Platform Users',
            'edit_platform_users' => 'Edit Platform Users',
            'delete_platform_users' => 'Hapus Platform Users',
            'manage_roles_permissions' => 'Kelola Roles & Permissions',
            'view_user_activity_logs' => 'Lihat User Activity Logs',

            // Tenant Management
            'view_all_tenants' => 'Lihat Semua Tenants',
            'approve_tenant_registration' => 'Approve/Reject Tenant Registration',
            'suspend_tenants' => 'Suspend/Activate Tenants',
            'view_tenant_activities' => 'Lihat Tenant Activities',
            'manage_tenant_subscriptions' => 'Kelola Tenant Subscriptions',
            'access_tenant_data' => 'Akses Tenant Data (Super Admin)',

            // Financial & Billing
            'view_financial_reports' => 'Lihat Financial Reports',
            'manage_pricing_plans' => 'Kelola Pricing/Subscription Plans',
            'process_payments_refunds' => 'Proses Payments/Refunds',
            'generate_invoices' => 'Generate Invoices',
            'view_transaction_history' => 'Lihat Transaction History',
            'manage_payment_gateways' => 'Kelola Payment Gateways',
            'export_financial_data' => 'Export Financial Data',

            // Support & Helpdesk
            'view_support_tickets' => 'Lihat Support Tickets',
            'respond_support_tickets' => 'Respond to Tickets',
            'escalate_tickets' => 'Escalate Tickets',
            'view_ticket_history' => 'Lihat Ticket History',
            'manage_faq_knowledge_base' => 'Kelola FAQ/Knowledge Base',
            'send_announcements' => 'Send Announcements',

            // Reports & Analytics
            'view_platform_analytics' => 'Lihat Platform Analytics',
            'view_usage_statistics' => 'Lihat Usage Statistics',
            'generate_custom_reports' => 'Generate Custom Reports',
            'export_reports' => 'Export Reports',
            'view_audit_logs' => 'Lihat Audit Logs',
            'view_security_reports' => 'Lihat Security Reports',

            // Content & Resources
            'manage_announcements' => 'Kelola Announcements',
            'manage_documentation' => 'Kelola Documentation',
            'manage_email_templates' => 'Kelola Email Templates',
            'manage_static_pages' => 'Kelola Static Pages',
            'manage_help_center_content' => 'Kelola Help Center Content',

            // WP Customer Plugin - Customer Management
            'view_customer_list' => 'Lihat Daftar Customer',
            'view_customer_detail' => 'Lihat Detail Customer',
            'view_own_customer' => 'Lihat Customer Sendiri',
            'add_customer' => 'Tambah Customer',
            'edit_all_customers' => 'Edit Semua Customer',
            'edit_own_customer' => 'Edit Customer Sendiri',
            'delete_customer' => 'Hapus Customer',

            // WP Customer Plugin - Branch Management
            'view_customer_branch_list' => 'Lihat Daftar Cabang',
            'view_customer_branch_detail' => 'Lihat Detail Cabang',
            'view_own_customer_branch' => 'Lihat Cabang Sendiri',
            'add_customer_branch' => 'Tambah Cabang',
            'edit_all_customer_branches' => 'Edit Semua Cabang',
            'edit_own_customer_branch' => 'Edit Cabang Sendiri',
            'delete_customer_branch' => 'Hapus Cabang',

            // WP Customer Plugin - Employee Management
            'view_customer_employee_list' => 'Lihat Daftar Karyawan',
            'view_customer_employee_detail' => 'Lihat Detail Karyawan',
            'view_own_customer_employee' => 'Lihat Karyawan Sendiri',
            'add_customer_employee' => 'Tambah Karyawan',
            'edit_all_customer_employees' => 'Edit Karyawan',
            'edit_own_customer_employee' => 'Edit Karyawan Sendiri',
            'delete_customer_employee' => 'Hapus Karyawan',

            // WP Customer Plugin - Membership Invoice Management
            'view_customer_membership_invoice_list' => 'Lihat Daftar Invoice Membership',
            'view_customer_membership_invoice_detail' => 'Lihat Detail Invoice Membership',
            'view_own_customer_membership_invoice' => 'Lihat Invoice Membership Sendiri',
            'create_customer_membership_invoice' => 'Buat Invoice Membership',
            'edit_all_customer_membership_invoices' => 'Edit Semua Invoice Membership',
            'edit_own_customer_membership_invoice' => 'Edit Invoice Membership Sendiri',
            'delete_customer_membership_invoice' => 'Hapus Invoice Membership',
            'approve_customer_membership_invoice' => 'Approve Invoice Membership',

            // WP Customer Plugin - Membership Invoice Payment
            'pay_all_customer_membership_invoices' => 'Bayar Semua Invoice Membership Customer',
            'pay_own_customer_membership_invoices' => 'Bayar Invoice Membership Customer Sendiri',
            'pay_own_branch_membership_invoices' => 'Bayar Invoice Membership Cabang Sendiri',
        ];
    }

    /**
     * Get capability groups for nested tabs
     */
    public function getCapabilityGroups(): array {
        return [
            'platform_management' => [
                'title' => 'Platform',
                'description' => 'Platform Management',
                'caps' => [
                    'view_platform_dashboard',
                    'manage_platform_settings',
                    'manage_system_configuration',
                    'view_system_health',
                    'manage_maintenance_mode',
                    'manage_api_integrations'
                ]
            ],
            'user_role_management' => [
                'title' => 'User & Role',
                'description' => 'User & Role Management',
                'caps' => [
                    'view_platform_users',
                    'create_platform_users',
                    'edit_platform_users',
                    'delete_platform_users',
                    'manage_roles_permissions',
                    'view_user_activity_logs'
                ]
            ],
            'tenant_management' => [
                'title' => 'Tenant',
                'description' => 'Tenant Management',
                'caps' => [
                    'view_all_tenants',
                    'approve_tenant_registration',
                    'suspend_tenants',
                    'view_tenant_activities',
                    'manage_tenant_subscriptions',
                    'access_tenant_data'
                ]
            ],
            'financial_billing' => [
                'title' => 'Financial',
                'description' => 'Financial & Billing',
                'caps' => [
                    'view_financial_reports',
                    'manage_pricing_plans',
                    'process_payments_refunds',
                    'generate_invoices',
                    'view_transaction_history',
                    'manage_payment_gateways',
                    'export_financial_data'
                ]
            ],
            'support_helpdesk' => [
                'title' => 'Support',
                'description' => 'Support & Helpdesk',
                'caps' => [
                    'view_support_tickets',
                    'respond_support_tickets',
                    'escalate_tickets',
                    'view_ticket_history',
                    'manage_faq_knowledge_base',
                    'send_announcements'
                ]
            ],
            'reports_analytics' => [
                'title' => 'Reports',
                'description' => 'Reports & Analytics',
                'caps' => [
                    'view_platform_analytics',
                    'view_usage_statistics',
                    'generate_custom_reports',
                    'export_reports',
                    'view_audit_logs',
                    'view_security_reports'
                ]
            ],
            'content_resources' => [
                'title' => 'Content',
                'description' => 'Content & Resources',
                'caps' => [
                    'manage_announcements',
                    'manage_documentation',
                    'manage_email_templates',
                    'manage_static_pages',
                    'manage_help_center_content'
                ]
            ],
            'wp_customer_management' => [
                'title' => 'Customer',
                'description' => 'WP Customer - Customer Management',
                'caps' => [
                    'view_customer_list',
                    'view_customer_detail',
                    'view_own_customer',
                    'add_customer',
                    'edit_all_customers',
                    'edit_own_customer',
                    'delete_customer'
                ]
            ],
            'wp_customer_branch' => [
                'title' => 'Branch',
                'description' => 'WP Customer - Branch Management',
                'caps' => [
                    'view_customer_branch_list',
                    'view_customer_branch_detail',
                    'view_own_customer_branch',
                    'add_customer_branch',
                    'edit_all_customer_branches',
                    'edit_own_customer_branch',
                    'delete_customer_branch'
                ]
            ],
            'wp_customer_employee' => [
                'title' => 'Employee',
                'description' => 'WP Customer - Employee Management',
                'caps' => [
                    'view_customer_employee_list',
                    'view_customer_employee_detail',
                    'view_own_customer_employee',
                    'add_customer_employee',
                    'edit_all_customer_employees',
                    'edit_own_customer_employee',
                    'delete_customer_employee'
                ]
            ],
            'wp_customer_invoice' => [
                'title' => 'Invoice',
                'description' => 'WP Customer - Membership Invoice',
                'caps' => [
                    'view_customer_membership_invoice_list',
                    'view_customer_membership_invoice_detail',
                    'view_own_customer_membership_invoice',
                    'create_customer_membership_invoice',
                    'edit_all_customer_membership_invoices',
                    'edit_own_customer_membership_invoice',
                    'delete_customer_membership_invoice',
                    'approve_customer_membership_invoice'
                ]
            ],
            'wp_customer_invoice_payment' => [
                'title' => 'Payment',
                'description' => 'WP Customer - Invoice Payment',
                'caps' => [
                    'pay_all_customer_membership_invoices',
                    'pay_own_customer_membership_invoices',
                    'pay_own_branch_membership_invoices'
                ]
            ]
        ];
    }

    /**
     * Get capability descriptions for tooltips
     */
    public function getCapabilityDescriptions(): array {
        return [
            // Platform Management
            'view_platform_dashboard' => __('Memungkinkan melihat dashboard platform', 'wp-app-core'),
            'manage_platform_settings' => __('Memungkinkan mengelola pengaturan platform', 'wp-app-core'),
            'manage_system_configuration' => __('Memungkinkan konfigurasi sistem tingkat lanjut', 'wp-app-core'),
            'view_system_health' => __('Memungkinkan melihat status kesehatan sistem', 'wp-app-core'),
            'manage_maintenance_mode' => __('Memungkinkan mengaktifkan/menonaktifkan maintenance mode', 'wp-app-core'),
            'manage_api_integrations' => __('Memungkinkan mengelola API keys dan integrasi', 'wp-app-core'),

            // User & Role Management
            'view_platform_users' => __('Memungkinkan melihat daftar platform users', 'wp-app-core'),
            'create_platform_users' => __('Memungkinkan membuat platform users baru', 'wp-app-core'),
            'edit_platform_users' => __('Memungkinkan mengedit platform users', 'wp-app-core'),
            'delete_platform_users' => __('Memungkinkan menghapus platform users', 'wp-app-core'),
            'manage_roles_permissions' => __('Memungkinkan mengelola roles dan permissions', 'wp-app-core'),
            'view_user_activity_logs' => __('Memungkinkan melihat user activity logs', 'wp-app-core'),

            // Tenant Management
            'view_all_tenants' => __('Memungkinkan melihat semua tenants', 'wp-app-core'),
            'approve_tenant_registration' => __('Memungkinkan approve/reject tenant registration', 'wp-app-core'),
            'suspend_tenants' => __('Memungkinkan suspend/activate tenants', 'wp-app-core'),
            'view_tenant_activities' => __('Memungkinkan melihat aktivitas tenants', 'wp-app-core'),
            'manage_tenant_subscriptions' => __('Memungkinkan mengelola tenant subscriptions', 'wp-app-core'),
            'access_tenant_data' => __('Memungkinkan akses data tenant (super admin only)', 'wp-app-core'),

            // Financial & Billing
            'view_financial_reports' => __('Memungkinkan melihat laporan keuangan', 'wp-app-core'),
            'manage_pricing_plans' => __('Memungkinkan mengelola pricing plans', 'wp-app-core'),
            'process_payments_refunds' => __('Memungkinkan memproses payments dan refunds', 'wp-app-core'),
            'generate_invoices' => __('Memungkinkan generate invoices', 'wp-app-core'),
            'view_transaction_history' => __('Memungkinkan melihat transaction history', 'wp-app-core'),
            'manage_payment_gateways' => __('Memungkinkan mengelola payment gateways', 'wp-app-core'),
            'export_financial_data' => __('Memungkinkan export data keuangan', 'wp-app-core'),

            // Support & Helpdesk
            'view_support_tickets' => __('Memungkinkan melihat support tickets', 'wp-app-core'),
            'respond_support_tickets' => __('Memungkinkan respond to tickets', 'wp-app-core'),
            'escalate_tickets' => __('Memungkinkan escalate tickets', 'wp-app-core'),
            'view_ticket_history' => __('Memungkinkan melihat ticket history', 'wp-app-core'),
            'manage_faq_knowledge_base' => __('Memungkinkan mengelola FAQ/knowledge base', 'wp-app-core'),
            'send_announcements' => __('Memungkinkan send announcements', 'wp-app-core'),

            // Reports & Analytics
            'view_platform_analytics' => __('Memungkinkan melihat platform analytics', 'wp-app-core'),
            'view_usage_statistics' => __('Memungkinkan melihat usage statistics', 'wp-app-core'),
            'generate_custom_reports' => __('Memungkinkan generate custom reports', 'wp-app-core'),
            'export_reports' => __('Memungkinkan export reports', 'wp-app-core'),
            'view_audit_logs' => __('Memungkinkan melihat audit logs', 'wp-app-core'),
            'view_security_reports' => __('Memungkinkan melihat security reports', 'wp-app-core'),

            // Content & Resources
            'manage_announcements' => __('Memungkinkan mengelola announcements', 'wp-app-core'),
            'manage_documentation' => __('Memungkinkan mengelola documentation', 'wp-app-core'),
            'manage_email_templates' => __('Memungkinkan mengelola email templates', 'wp-app-core'),
            'manage_static_pages' => __('Memungkinkan mengelola static pages', 'wp-app-core'),
            'manage_help_center_content' => __('Memungkinkan mengelola help center content', 'wp-app-core'),

            // WP Customer capabilities (shortened for brevity)
            'view_customer_list' => __('Memungkinkan melihat daftar customer', 'wp-app-core'),
            'view_customer_detail' => __('Memungkinkan melihat detail customer', 'wp-app-core'),
            'add_customer' => __('Memungkinkan menambah customer', 'wp-app-core'),
            'edit_all_customers' => __('Memungkinkan mengedit semua customer', 'wp-app-core'),
            'delete_customer' => __('Memungkinkan menghapus customer', 'wp-app-core'),
        ];
    }

    /**
     * Get default capabilities for a specific role
     * Defines which capabilities each platform role should have by default
     */
    public function getDefaultCapabilitiesForRole(string $role_slug): array {
        $defaults = [
            'platform_staff' => [
                'read' => true
                // Base role - no additional capabilities
            ],
            'platform_super_admin' => [
                'read' => true,
                // Full access to everything
                'view_platform_dashboard' => true,
                'manage_platform_settings' => true,
                'manage_system_configuration' => true,
                'view_system_health' => true,
                'manage_maintenance_mode' => true,
                'manage_api_integrations' => true,
                'view_platform_users' => true,
                'create_platform_users' => true,
                'edit_platform_users' => true,
                'delete_platform_users' => true,
                'manage_roles_permissions' => true,
                'view_user_activity_logs' => true,
                'view_all_tenants' => true,
                'approve_tenant_registration' => true,
                'suspend_tenants' => true,
                'view_tenant_activities' => true,
                'manage_tenant_subscriptions' => true,
                'access_tenant_data' => true,
                'view_financial_reports' => true,
                'manage_pricing_plans' => true,
                'process_payments_refunds' => true,
                'generate_invoices' => true,
                'view_transaction_history' => true,
                'manage_payment_gateways' => true,
                'export_financial_data' => true,
                'view_support_tickets' => true,
                'respond_support_tickets' => true,
                'escalate_tickets' => true,
                'view_ticket_history' => true,
                'manage_faq_knowledge_base' => true,
                'send_announcements' => true,
                'view_platform_analytics' => true,
                'view_usage_statistics' => true,
                'generate_custom_reports' => true,
                'export_reports' => true,
                'view_audit_logs' => true,
                'view_security_reports' => true,
                'manage_announcements' => true,
                'manage_documentation' => true,
                'manage_email_templates' => true,
                'manage_static_pages' => true,
                'manage_help_center_content' => true,
                // WP Customer full access
                'view_customer_list' => true,
                'view_customer_detail' => true,
                'add_customer' => true,
                'edit_all_customers' => true,
                'delete_customer' => true,
                'view_customer_branch_list' => true,
                'view_customer_branch_detail' => true,
                'add_customer_branch' => true,
                'edit_all_customer_branches' => true,
                'delete_customer_branch' => true,
                'view_customer_employee_list' => true,
                'view_customer_employee_detail' => true,
                'add_customer_employee' => true,
                'edit_all_customer_employees' => true,
                'delete_customer_employee' => true,
                'view_customer_membership_invoice_list' => true,
                'view_customer_membership_invoice_detail' => true,
                'create_customer_membership_invoice' => true,
                'edit_all_customer_membership_invoices' => true,
                'delete_customer_membership_invoice' => true,
                'approve_customer_membership_invoice' => true,
                'pay_all_customer_membership_invoices' => true,
            ],
            'platform_admin' => [
                'read' => true,
                // Management access (no delete)
                'view_platform_dashboard' => true,
                'manage_platform_settings' => true,
                'view_system_health' => true,
                'view_platform_users' => true,
                'create_platform_users' => true,
                'edit_platform_users' => true,
                'manage_roles_permissions' => true,
                'view_all_tenants' => true,
                'approve_tenant_registration' => true,
                'view_tenant_activities' => true,
                'view_financial_reports' => true,
                'view_transaction_history' => true,
                'view_support_tickets' => true,
                'respond_support_tickets' => true,
                'view_platform_analytics' => true,
                'view_customer_list' => true,
                'view_customer_detail' => true,
                'view_customer_branch_list' => true,
            ],
            'platform_manager' => [
                'read' => true,
                // View and respond access
                'view_platform_dashboard' => true,
                'view_system_health' => true,
                'view_platform_users' => true,
                'view_all_tenants' => true,
                'view_financial_reports' => true,
                'view_support_tickets' => true,
                'respond_support_tickets' => true,
                'view_platform_analytics' => true,
            ],
            'platform_support' => [
                'read' => true,
                // Support-focused access
                'view_platform_dashboard' => true,
                'view_support_tickets' => true,
                'respond_support_tickets' => true,
                'view_ticket_history' => true,
                'manage_faq_knowledge_base' => true,
            ],
            'platform_finance' => [
                'read' => true,
                // Finance-focused access
                'view_platform_dashboard' => true,
                'view_financial_reports' => true,
                'view_transaction_history' => true,
                'export_financial_data' => true,
                'view_customer_list' => true,
                'view_customer_detail' => true,
                'view_customer_membership_invoice_list' => true,
                'view_customer_membership_invoice_detail' => true,
            ],
            'platform_analyst' => [
                'read' => true,
                // Analytics-focused access
                'view_platform_dashboard' => true,
                'view_platform_analytics' => true,
                'view_usage_statistics' => true,
                'generate_custom_reports' => true,
                'export_reports' => true,
                'view_customer_list' => true,
                'view_customer_detail' => true,
            ],
            'platform_viewer' => [
                'read' => true,
                // View-only access
                'view_platform_dashboard' => true,
                'view_system_health' => true,
                'view_customer_list' => true,
                'view_customer_detail' => true,
            ],
        ];

        return $defaults[$role_slug] ?? [];
    }
}
