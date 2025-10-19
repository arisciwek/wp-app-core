<?php
/**
 * Platform Permission Model
 *
 * @package     WP_App_Core
 * @subpackage  Models/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Models/Settings/PlatformPermissionModel.php
 *
 * Description: Model untuk mengelola permission platform staff
 *              Mengatur capabilities untuk role platform management
 *
 * Changelog:
 * 1.0.1 - 2025-10-19
 * - Added explicit 'read' capability to all platform roles for wp-admin access
 * - Updated addCapabilities() method to explicitly add 'read' capability
 * - Updated resetToDefault() method to explicitly add 'read' capability
 *
 * 1.0.0 - 2025-10-19
 * - Initial release
 * - Platform permission groups (Platform, User, Tenant, Financial, Support, Reports, Content)
 * - Role-based permission management
 */

namespace WPAppCore\Models\Settings;

class PlatformPermissionModel {

    /**
     * Available capabilities untuk platform
     */
    private $available_capabilities = [
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

        // Tenant Management (Customer/Branch Management)
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
    ];

    /**
     * Capability groups untuk permission matrix
     */
    private $capability_groups = [
        'platform_management' => [
            'title' => 'Platform Management',
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
            'title' => 'User & Role Management',
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
            'title' => 'Tenant Management',
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
            'title' => 'Financial & Billing',
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
            'title' => 'Support & Helpdesk',
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
            'title' => 'Reports & Analytics',
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
            'title' => 'Content & Resources',
            'caps' => [
                'manage_announcements',
                'manage_documentation',
                'manage_email_templates',
                'manage_static_pages',
                'manage_help_center_content'
            ]
        ]
    ];

    /**
     * Get all available capabilities
     *
     * @return array
     */
    public function getAllCapabilities(): array {
        return $this->available_capabilities;
    }

    /**
     * Get capability groups
     *
     * @return array
     */
    public function getCapabilityGroups(): array {
        return $this->capability_groups;
    }

    /**
     * Check if role has capability
     *
     * @param string $role_name
     * @param string $capability
     * @return bool
     */
    public function roleHasCapability(string $role_name, string $capability): bool {
        $role = get_role($role_name);
        if (!$role) {
            return false;
        }
        return $role->has_cap($capability);
    }

    /**
     * Add default capabilities to roles
     * Called during plugin activation or when resetting permissions
     */
    public function addCapabilities(): void {
        // Require Role Manager
        require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-role-manager.php';

        // Add all capabilities to administrator
        $admin = get_role('administrator');
        if ($admin) {
            foreach (array_keys($this->available_capabilities) as $cap) {
                $admin->add_cap($cap);
            }
        }

        // Add default capabilities to platform roles
        $platform_roles = \WP_App_Core_Role_Manager::getRoleSlugs();
        foreach ($platform_roles as $role_slug) {
            $role = get_role($role_slug);
            if ($role) {
                // Add 'read' capability explicitly - required for wp-admin access
                $role->add_cap('read');

                $default_caps = $this->getDefaultCapabilitiesForRole($role_slug);
                foreach ($default_caps as $cap => $enabled) {
                    // Skip 'read' as it's already added above
                    if ($cap === 'read') {
                        continue;
                    }

                    if ($enabled && isset($this->available_capabilities[$cap])) {
                        $role->add_cap($cap);
                    } else if (!$enabled) {
                        $role->remove_cap($cap);
                    }
                }
            }
        }
    }

    /**
     * Add default capabilities to administrator only
     * Legacy method for backward compatibility
     */
    public function addDefaultCapabilities(): void {
        $admin = get_role('administrator');
        if ($admin) {
            foreach (array_keys($this->available_capabilities) as $cap) {
                $admin->add_cap($cap);
            }
        }
    }

    /**
     * Update role capabilities
     *
     * @param string $role_name
     * @param array $capabilities
     * @return bool
     */
    public function updateRoleCapabilities(string $role_name, array $capabilities): bool {
        // Don't allow modifying administrator role
        if ($role_name === 'administrator') {
            return false;
        }

        $role = get_role($role_name);
        if (!$role) {
            return false;
        }

        // Remove all platform capabilities first
        foreach (array_keys($this->available_capabilities) as $cap) {
            $role->remove_cap($cap);
        }

        // Add new capabilities
        foreach ($capabilities as $cap => $enabled) {
            if ($enabled && isset($this->available_capabilities[$cap])) {
                $role->add_cap($cap);
            }
        }

        return true;
    }

    /**
     * Reset permissions to default
     *
     * @return bool
     */
    public function resetToDefault(): bool {
        try {
            // Require Role Manager
            require_once WP_APP_CORE_PLUGIN_DIR . 'includes/class-role-manager.php';

            // Get all roles
            foreach (get_editable_roles() as $role_name => $role_info) {
                $role = get_role($role_name);
                if (!$role) continue;

                // Remove all platform capabilities first
                foreach (array_keys($this->available_capabilities) as $cap) {
                    $role->remove_cap($cap);
                }

                // Administrator gets all capabilities
                if ($role_name === 'administrator') {
                    foreach (array_keys($this->available_capabilities) as $cap) {
                        $role->add_cap($cap);
                    }
                    continue;
                }

                // Platform roles get their default capabilities
                if (\WP_App_Core_Role_Manager::isPluginRole($role_name)) {
                    // Add 'read' capability explicitly - required for wp-admin access
                    $role->add_cap('read');

                    $default_caps = $this->getDefaultCapabilitiesForRole($role_name);
                    foreach ($default_caps as $cap => $enabled) {
                        // Skip 'read' as it's already added above
                        if ($cap === 'read') {
                            continue;
                        }

                        if ($enabled && isset($this->available_capabilities[$cap])) {
                            $role->add_cap($cap);
                        }
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            error_log('Error resetting platform permissions: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get role capabilities matrix
     *
     * @return array
     */
    public function getRoleCapabilitiesMatrix(): array {
        $matrix = [];
        $roles = get_editable_roles();

        foreach ($roles as $role_name => $role_info) {
            $role = get_role($role_name);
            if (!$role) continue;

            $matrix[$role_name] = [
                'name' => $role_info['name'],
                'capabilities' => []
            ];

            foreach (array_keys($this->available_capabilities) as $cap) {
                $matrix[$role_name]['capabilities'][$cap] = $role->has_cap($cap);
            }
        }

        return $matrix;
    }

    /**
     * Get default capabilities for a specific platform role
     *
     * @param string $role_slug Role slug
     * @return array Array of capability => bool pairs
     */
    private function getDefaultCapabilitiesForRole(string $role_slug): array {
        $defaults = [
            'platform_super_admin' => [
                'read' => true,
                // Platform Management - Full access
                'view_platform_dashboard' => true,
                'manage_platform_settings' => true,
                'manage_system_configuration' => true,
                'view_system_health' => true,
                'manage_maintenance_mode' => true,
                'manage_api_integrations' => true,
                // User & Role Management - Full access
                'view_platform_users' => true,
                'create_platform_users' => true,
                'edit_platform_users' => true,
                'delete_platform_users' => true,
                'manage_roles_permissions' => true,
                'view_user_activity_logs' => true,
                // Tenant Management - Full access
                'view_all_tenants' => true,
                'approve_tenant_registration' => true,
                'suspend_tenants' => true,
                'view_tenant_activities' => true,
                'manage_tenant_subscriptions' => true,
                'access_tenant_data' => true,
                // Financial & Billing - Full access
                'view_financial_reports' => true,
                'manage_pricing_plans' => true,
                'process_payments_refunds' => true,
                'generate_invoices' => true,
                'view_transaction_history' => true,
                'manage_payment_gateways' => true,
                'export_financial_data' => true,
                // Support & Helpdesk - Full access
                'view_support_tickets' => true,
                'respond_support_tickets' => true,
                'escalate_tickets' => true,
                'view_ticket_history' => true,
                'manage_faq_knowledge_base' => true,
                'send_announcements' => true,
                // Reports & Analytics - Full access
                'view_platform_analytics' => true,
                'view_usage_statistics' => true,
                'generate_custom_reports' => true,
                'export_reports' => true,
                'view_audit_logs' => true,
                'view_security_reports' => true,
                // Content & Resources - Full access
                'manage_announcements' => true,
                'manage_documentation' => true,
                'manage_email_templates' => true,
                'manage_static_pages' => true,
                'manage_help_center_content' => true,
            ],
            'platform_admin' => [
                'read' => true,
                // Platform Management - Limited
                'view_platform_dashboard' => true,
                'manage_platform_settings' => true,
                'view_system_health' => true,
                // User & Role Management - Limited
                'view_platform_users' => true,
                'create_platform_users' => true,
                'edit_platform_users' => true,
                // Tenant Management - Full
                'view_all_tenants' => true,
                'approve_tenant_registration' => true,
                'suspend_tenants' => true,
                'view_tenant_activities' => true,
                'manage_tenant_subscriptions' => true,
                // Financial & Billing - View only
                'view_financial_reports' => true,
                'view_transaction_history' => true,
                // Support & Helpdesk - Full
                'view_support_tickets' => true,
                'respond_support_tickets' => true,
                'escalate_tickets' => true,
                'manage_faq_knowledge_base' => true,
                // Reports & Analytics - Limited
                'view_platform_analytics' => true,
                'view_usage_statistics' => true,
                'view_audit_logs' => true,
            ],
            'platform_manager' => [
                'read' => true,
                // Platform Management
                'view_platform_dashboard' => true,
                'view_system_health' => true,
                // Tenant Management
                'view_all_tenants' => true,
                'view_tenant_activities' => true,
                // Support & Helpdesk
                'view_support_tickets' => true,
                'respond_support_tickets' => true,
                'view_ticket_history' => true,
                // Reports & Analytics
                'view_platform_analytics' => true,
                'view_usage_statistics' => true,
                'generate_custom_reports' => true,
            ],
            'platform_support' => [
                'read' => true,
                // Platform Management
                'view_platform_dashboard' => true,
                // Tenant Management - View only
                'view_all_tenants' => true,
                // Support & Helpdesk - Full
                'view_support_tickets' => true,
                'respond_support_tickets' => true,
                'escalate_tickets' => true,
                'view_ticket_history' => true,
                'manage_faq_knowledge_base' => true,
            ],
            'platform_finance' => [
                'read' => true,
                // Platform Management
                'view_platform_dashboard' => true,
                // Tenant Management - View only
                'view_all_tenants' => true,
                // Financial & Billing - Full
                'view_financial_reports' => true,
                'manage_pricing_plans' => true,
                'process_payments_refunds' => true,
                'generate_invoices' => true,
                'view_transaction_history' => true,
                'export_financial_data' => true,
                // Reports & Analytics - Financial only
                'view_platform_analytics' => true,
                'export_reports' => true,
            ],
            'platform_analyst' => [
                'read' => true,
                // Platform Management
                'view_platform_dashboard' => true,
                'view_system_health' => true,
                // Tenant Management - View only
                'view_all_tenants' => true,
                'view_tenant_activities' => true,
                // Reports & Analytics - Full
                'view_platform_analytics' => true,
                'view_usage_statistics' => true,
                'generate_custom_reports' => true,
                'export_reports' => true,
                'view_audit_logs' => true,
                'view_security_reports' => true,
            ],
            'platform_viewer' => [
                'read' => true,
                // Platform Management - View only
                'view_platform_dashboard' => true,
                // Tenant Management - View only
                'view_all_tenants' => true,
                // Reports & Analytics - View only
                'view_platform_analytics' => true,
                'view_usage_statistics' => true,
            ],
        ];

        return $defaults[$role_slug] ?? [];
    }

    /**
     * Get capability descriptions for tooltips
     *
     * @return array Array of capability => description pairs
     */
    public function getCapabilityDescriptions(): array {
        return [
            // Platform Management
            'view_platform_dashboard' => __('Memungkinkan melihat dashboard platform', 'wp-app-core'),
            'manage_platform_settings' => __('Memungkinkan mengelola pengaturan platform', 'wp-app-core'),
            'manage_system_configuration' => __('Memungkinkan mengelola konfigurasi sistem', 'wp-app-core'),
            'view_system_health' => __('Memungkinkan melihat status kesehatan sistem', 'wp-app-core'),
            'manage_maintenance_mode' => __('Memungkinkan mengaktifkan maintenance mode', 'wp-app-core'),
            'manage_api_integrations' => __('Memungkinkan mengelola API keys dan integrasi', 'wp-app-core'),

            // User & Role Management
            'view_platform_users' => __('Memungkinkan melihat daftar platform users', 'wp-app-core'),
            'create_platform_users' => __('Memungkinkan membuat platform users baru', 'wp-app-core'),
            'edit_platform_users' => __('Memungkinkan mengedit platform users', 'wp-app-core'),
            'delete_platform_users' => __('Memungkinkan menghapus platform users', 'wp-app-core'),
            'manage_roles_permissions' => __('Memungkinkan mengelola roles dan permissions', 'wp-app-core'),
            'view_user_activity_logs' => __('Memungkinkan melihat user activity logs', 'wp-app-core'),

            // Tenant Management
            'view_all_tenants' => __('Memungkinkan melihat semua tenants (customer/branch)', 'wp-app-core'),
            'approve_tenant_registration' => __('Memungkinkan approve/reject tenant registration', 'wp-app-core'),
            'suspend_tenants' => __('Memungkinkan suspend/activate tenants', 'wp-app-core'),
            'view_tenant_activities' => __('Memungkinkan melihat tenant activities', 'wp-app-core'),
            'manage_tenant_subscriptions' => __('Memungkinkan mengelola tenant subscriptions', 'wp-app-core'),
            'access_tenant_data' => __('Memungkinkan akses tenant data (super admin)', 'wp-app-core'),

            // Financial & Billing
            'view_financial_reports' => __('Memungkinkan melihat financial reports', 'wp-app-core'),
            'manage_pricing_plans' => __('Memungkinkan mengelola pricing/subscription plans', 'wp-app-core'),
            'process_payments_refunds' => __('Memungkinkan proses payments/refunds', 'wp-app-core'),
            'generate_invoices' => __('Memungkinkan generate invoices', 'wp-app-core'),
            'view_transaction_history' => __('Memungkinkan melihat transaction history', 'wp-app-core'),
            'manage_payment_gateways' => __('Memungkinkan mengelola payment gateways', 'wp-app-core'),
            'export_financial_data' => __('Memungkinkan export financial data', 'wp-app-core'),

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
        ];
    }

    /**
     * Get description for a specific capability
     *
     * @param string $capability Capability name
     * @return string Description or empty string if not found
     */
    public function getCapabilityDescription(string $capability): string {
        $descriptions = $this->getCapabilityDescriptions();
        return $descriptions[$capability] ?? '';
    }
}
