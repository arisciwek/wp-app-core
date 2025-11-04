<?php
/**
 * Abstract DataTable Model
 *
 * Base class for entity DataTable models providing shared implementation
 * for common patterns: status badges, action buttons, counting, etc.
 * Eliminates code duplication across DataTable models.
 *
 * @package     WPAppCore
 * @subpackage  Models/DataTable
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Models/DataTable/AbstractDataTableModel.php
 *
 * Description: Abstract base class for all entity DataTable models.
 *              Extends existing DataTableModel and provides concrete
 *              implementations of commonly duplicated methods like
 *              format_status_badge(), generate_action_buttons(), and
 *              get_total_count(). Child classes only need to implement
 *              entity-specific configuration and business logic.
 *
 * Dependencies:
 * - WPAppCore\Models\DataTable\DataTableModel (parent class)
 * - WordPress functions (__,current_user_can, esc_attr, esc_html)
 *
 * Usage:
 * ```php
 * class CustomerDataTableModel extends AbstractDataTableModel {
 *     protected function getEntityName(): string {
 *         return 'customer';
 *     }
 *
 *     // Implement other abstract methods...
 *
 *     // ✅ format_status_badge() - inherited FREE!
 *     // ✅ generate_action_buttons() - inherited FREE!
 *     // ✅ get_total_count() - inherited FREE!
 * }
 * ```
 *
 * Benefits:
 * - 60-70% code reduction in child classes
 * - Consistent UI across all DataTables
 * - Standardized patterns (DT_RowId, action buttons, status badges)
 * - Single source of truth for common operations
 * - Type-safe method signatures
 * - Easier testing and maintenance
 *
 * Changelog:
 * 1.0.0 - 2025-01-02
 * - Initial implementation
 * - Status badge: format_status_badge()
 * - Action buttons: generate_action_buttons()
 * - Counting: get_total_count(), getBaseStatusWhere()
 * - Panel integration: formatPanelRowData()
 * - 8 abstract methods for entity configuration
 * - Comprehensive PHPDoc documentation
 */

namespace WPAppCore\Models\DataTable;

defined('ABSPATH') || exit;

abstract class AbstractDataTableModel extends DataTableModel {

    // ========================================
    // ABSTRACT METHODS (Must be implemented by child classes)
    // ========================================

    /**
     * Get entity name (singular, lowercase)
     *
     * Used for panel integration, action buttons, and hook names.
     * Must use underscores for multi-word entities (e.g., 'platform_staff').
     *
     * @return string Entity name, e.g., 'customer', 'agency', 'platform_staff'
     *
     * @example
     * ```php
     * protected function getEntityName(): string {
     *     return 'customer';
     * }
     * ```
     */
    abstract protected function getEntityName(): string;

    /**
     * Get entity display name (singular)
     *
     * Used for UI messages and button titles.
     * Can include spaces and proper capitalization.
     *
     * @return string Display name, e.g., 'Customer', 'Agency', 'Platform Staff'
     *
     * @example
     * ```php
     * protected function getEntityDisplayName(): string {
     *     return 'Customer';
     * }
     * ```
     */
    abstract protected function getEntityDisplayName(): string;

    /**
     * Get text domain for translations
     *
     * Used for __() translation functions in status badges and buttons.
     *
     * @return string Text domain, e.g., 'wp-customer', 'wp-agency', 'wp-app-core'
     *
     * @example
     * ```php
     * protected function getTextDomain(): string {
     *     return 'wp-customer';
     * }
     * ```
     */
    abstract protected function getTextDomain(): string;

    /**
     * Get database table alias
     *
     * Single letter alias used in SQL queries.
     * Must match the alias used in constructor's $this->table.
     *
     * @return string Table alias, e.g., 'c', 'a', 's'
     *
     * @example
     * ```php
     * protected function getTableAlias(): string {
     *     return 'c';  // for 'wp_app_customers c'
     * }
     * ```
     */
    abstract protected function getTableAlias(): string;

    /**
     * Get active status value for this entity
     *
     * Different entities may use different status values.
     * Common values: 'aktif', 'active'
     *
     * @return string Active status value
     *
     * @example
     * ```php
     * protected function getStatusActiveValue(): string {
     *     return 'aktif';  // or 'active'
     * }
     * ```
     */
    abstract protected function getStatusActiveValue(): string;

    /**
     * Get edit permission capability
     *
     * WordPress capability required to show edit button.
     *
     * @return string Capability name
     *
     * @example
     * ```php
     * protected function getEditCapability(): string {
     *     return 'edit_all_customers';
     * }
     * ```
     */
    abstract protected function getEditCapability(): string;

    /**
     * Get delete permission capability
     *
     * WordPress capability required to show delete button.
     *
     * @return string Capability name
     *
     * @example
     * ```php
     * protected function getDeleteCapability(): string {
     *     return 'delete_customers';
     * }
     * ```
     */
    abstract protected function getDeleteCapability(): string;

    /**
     * Get filter hook name for WHERE conditions
     *
     * Filter hook applied in get_total_count() for cross-plugin integration.
     * Return empty string if no filter hook is needed.
     *
     * @return string Filter hook name or empty string
     *
     * @example
     * ```php
     * protected function getFilterHookName(): string {
     *     return 'wpapp_datatable_customers_where';
     * }
     * ```
     *
     * @example Without filter hook
     * ```php
     * protected function getFilterHookName(): string {
     *     return '';  // No filter hook
     * }
     * ```
     */
    abstract protected function getFilterHookName(): string;

    // ========================================
    // CONCRETE METHODS (Shared implementation)
    // ========================================

    /**
     * Format status badge with color coding
     *
     * Generates HTML badge for status column.
     * Uses entity's active status value for color determination.
     *
     * Colors:
     * - Active: Green badge ('success')
     * - Inactive: Red badge ('error')
     *
     * @param string $status Status value
     * @return string HTML badge
     *
     * @example
     * ```php
     * // In format_row()
     * 'status' => $this->format_status_badge($row->status)
     * ```
     */
    protected function format_status_badge(string $status): string {
        $active_value = $this->getStatusActiveValue();
        $badge_class = $status === $active_value ? 'success' : 'error';
        $status_text = $status === $active_value
            ? __('Active', $this->getTextDomain())
            : __('Inactive', $this->getTextDomain());

        return sprintf(
            '<span class="wpapp-badge wpapp-badge-%s">%s</span>',
            esc_attr($badge_class),
            esc_html($status_text)
        );
    }

    /**
     * Generate standard action buttons
     *
     * Creates action buttons with permission checks:
     * 1. View button (always shown) - opens panel
     * 2. Edit button (if user has edit capability)
     * 3. Delete button (if user has delete capability)
     * 4. Custom buttons (optional, via parameter)
     *
     * @param object $row Database row object
     * @param array $custom_buttons Optional array of custom button HTML
     * @return string HTML action buttons
     *
     * @example Basic usage
     * ```php
     * // In format_row()
     * 'actions' => $this->generate_action_buttons($row)
     * ```
     *
     * @example With custom button
     * ```php
     * $custom_buttons = [
     *     sprintf(
     *         '<button class="button button-small custom-btn" data-id="%d">
     *             <span class="dashicons dashicons-admin-generic"></span>
     *         </button>',
     *         $row->id
     *     )
     * ];
     * 'actions' => $this->generate_action_buttons($row, $custom_buttons)
     * ```
     */
    protected function generate_action_buttons($row, array $custom_buttons = []): string {
        $buttons = [];
        $entity = $this->getEntityName();
        $text_domain = $this->getTextDomain();

        // 1. View button (always shown, opens panel)
        $buttons[] = sprintf(
            '<button type="button" class="button button-small wpapp-panel-trigger"
                    data-id="%d" data-entity="%s" title="%s">
                <span class="dashicons dashicons-visibility"></span>
            </button>',
            esc_attr($row->id),
            esc_attr($entity),
            esc_attr__('View Details', $text_domain)
        );

        // 2. Edit button (if user has permission)
        if (current_user_can($this->getEditCapability())) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small %s-edit-btn"
                        data-id="%d" title="%s">
                    <span class="dashicons dashicons-edit"></span>
                </button>',
                esc_attr($entity),
                esc_attr($row->id),
                esc_attr__('Edit', $text_domain)
            );
        }

        // 3. Delete button (if user has permission)
        if (current_user_can($this->getDeleteCapability())) {
            $buttons[] = sprintf(
                '<button type="button" class="button button-small %s-delete-btn"
                        data-id="%d" title="%s">
                    <span class="dashicons dashicons-trash"></span>
                </button>',
                esc_attr($entity),
                esc_attr($row->id),
                esc_attr__('Delete', $text_domain)
            );
        }

        // 4. Add custom buttons (if provided)
        if (!empty($custom_buttons)) {
            $buttons = array_merge($buttons, $custom_buttons);
        }

        return implode(' ', $buttons);
    }

    /**
     * Get total count with filtering
     *
     * Helper method for dashboard statistics.
     * Applies status filtering and cross-plugin filter hooks.
     *
     * Flow:
     * 1. Use entity's default status if not provided
     * 2. Temporarily set $_POST for get_where()
     * 3. Get WHERE conditions from child class
     * 4. Apply filter hook (if defined)
     * 5. Build and execute COUNT query
     *
     * @param string|null $status_filter Status to filter ('aktif', 'active', 'all', etc.)
     *                                   If null, uses getStatusActiveValue()
     * @return int Total count
     *
     * @example
     * ```php
     * // Get total active customers
     * $total = $this->get_total_count();
     *
     * // Get total inactive customers
     * $inactive = $this->get_total_count('tidak aktif');
     *
     * // Get all customers
     * $all = $this->get_total_count('all');
     * ```
     */
    public function get_total_count(string $status_filter = null): int {
        global $wpdb;

        // Use entity's default status if not provided
        if ($status_filter === null) {
            $status_filter = $this->getStatusActiveValue();
        }

        // Prepare minimal request data for filtering
        $request_data = [
            'start' => 0,
            'length' => 1,
            'search' => ['value' => ''],
            'order' => [['column' => 0, 'dir' => 'asc']],
            'status_filter' => $status_filter
        ];

        // Temporarily set POST for get_where() method
        $original_post = $_POST;
        $_POST['status_filter'] = $status_filter;

        // Get WHERE conditions from child class
        $where_conditions = $this->get_where();

        // Apply filter hook (if defined)
        // Allows cross-plugin integration (e.g., wp-customer filtering agencies)
        $filter_hook = $this->getFilterHookName();
        if (!empty($filter_hook)) {
            /**
             * Filter WHERE conditions for counting
             *
             * Allows external plugins to add additional WHERE conditions.
             * Critical for cross-plugin access control.
             *
             * @param array $where_conditions Current WHERE conditions
             * @param array $request_data Request parameters
             * @param AbstractDataTableModel $this Model instance
             */
            $where_conditions = apply_filters(
                $filter_hook,
                $where_conditions,
                $request_data,
                $this
            );
        }

        // Restore original POST
        $_POST = $original_post;

        // Build WHERE clause
        $where_sql = '';
        if (!empty($where_conditions)) {
            $where_sql = ' WHERE ' . implode(' AND ', $where_conditions);
        }

        // Build and execute count query
        $alias = $this->getTableAlias();
        $count_sql = "SELECT COUNT(DISTINCT {$alias}.id) as total
                      FROM {$this->table}
                      " . implode(' ', $this->base_joins) . "
                      {$where_sql}";

        return (int) $wpdb->get_var($count_sql);
    }

    /**
     * Get base WHERE conditions for status filtering
     *
     * Provides standard status filtering logic that can be used
     * in child class get_where() implementations.
     *
     * Handles:
     * - Status filter from $_POST
     * - Default to entity's active status
     * - 'all' value = no status filter
     *
     * @return array WHERE conditions
     *
     * @example
     * ```php
     * public function get_where(): array {
     *     // Start with base status filtering
     *     $where = $this->getBaseStatusWhere();
     *
     *     // Add entity-specific filtering
     *     if (!current_user_can('view_all')) {
     *         $where[] = "c.user_id = " . get_current_user_id();
     *     }
     *
     *     return $where;
     * }
     * ```
     */
    protected function getBaseStatusWhere(): array {
        global $wpdb;
        $where = [];

        // Get status filter from request
        $status_filter = isset($_POST['status_filter'])
            ? sanitize_text_field($_POST['status_filter'])
            : $this->getStatusActiveValue();

        // Apply status filter (skip if 'all')
        if ($status_filter !== 'all') {
            $alias = $this->getTableAlias();
            $where[] = $wpdb->prepare("{$alias}.status = %s", $status_filter);
        }

        return $where;
    }

    /**
     * Format panel integration data
     *
     * Generates DT_RowId and DT_RowData for panel system integration.
     * Required for wpapp-panel-manager.js to open panels correctly.
     *
     * DT_RowId format: `{entity}-{id}`
     * - Used as HTML id attribute
     * - Used for panel URL hash
     *
     * DT_RowData format:
     * - id: Entity ID (used in AJAX requests)
     * - entity: Entity name (used for routing)
     *
     * @param object $row Database row object
     * @return array Panel integration data
     *
     * @example
     * ```php
     * protected function format_row($row): array {
     *     return array_merge(
     *         // Panel integration
     *         $this->formatPanelRowData($row),
     *
     *         // Entity-specific columns
     *         [
     *             'code' => esc_html($row->code),
     *             'name' => esc_html($row->name),
     *             // ...
     *         ]
     *     );
     * }
     * ```
     */
    protected function formatPanelRowData($row): array {
        $entity = $this->getEntityName();

        return [
            'DT_RowId' => $entity . '-' . $row->id,
            'DT_RowData' => [
                'id' => $row->id,
                'entity' => $entity
            ]
        ];
    }

    /**
     * Get WHERE conditions for filtering
     *
     * Child classes SHOULD override this method to add entity-specific filtering.
     * Can use getBaseStatusWhere() as starting point.
     *
     * @return array WHERE conditions
     *
     * @example
     * ```php
     * public function get_where(): array {
     *     // Use base status filtering
     *     $where = $this->getBaseStatusWhere();
     *
     *     // Add permission-based filtering
     *     if (!current_user_can('view_all_customers')) {
     *         $where[] = $wpdb->prepare(
     *             'c.user_id = %d',
     *             get_current_user_id()
     *         );
     *     }
     *
     *     return $where;
     * }
     * ```
     */
    public function get_where(): array {
        // Default implementation: base status filtering only
        return $this->getBaseStatusWhere();
    }
}
