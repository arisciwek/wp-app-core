<?php
/**
 * Platform Settings Page Template
 *
 * @package     WP_App_Core
 * @subpackage  Views/Templates/Settings
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/templates/settings/settings-page.php
 *
 * Description: Main settings page template with tab navigation
 *              for platform settings management.
 *              GLOBAL SCOPE: Save & Reset buttons at page level,
 *              reusable across all wp-app-* plugins.
 *
 * Changelog:
 * 2.0.0 - 2025-11-12
 * - BREAKING: Moved Save & Reset buttons to page level
 * - Added data-current-tab for global button handling
 * - Pattern reusable for wp-customer, wp-agency, etc
 * 1.0.0 - 2025-10-19
 * - Initial creation
 * - Tab navigation structure
 */

if (!defined('ABSPATH')) {
    die;
}

$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

$tabs = [
    'general' => __('General', 'wp-app-core'),
    'email' => __('Email & Notifications', 'wp-app-core'),
    'permissions' => __('Platform Permissions', 'wp-app-core'),
    'demo-data' => __('Demo Data & Development', 'wp-app-core'),
    'security-authentication' => __('Security: Authentication', 'wp-app-core'),
    'security-session' => __('Security: Session', 'wp-app-core'),
    'security-policy' => __('Security: Policy & Audit', 'wp-app-core'),
];

// Tab configuration for buttons
$tab_config = [
    'general' => [
        'save_label' => __('Save General Settings', 'wp-app-core'),
        'reset_action' => 'reset_general_settings',
        'reset_title' => __('Reset General Settings?', 'wp-app-core'),
        'reset_message' => __('Are you sure you want to reset all general settings to their default values?\n\nThis action cannot be undone.', 'wp-app-core'),
        'form_id' => 'platform-general-settings-form'
    ],
    'email' => [
        'save_label' => __('Save Email Settings', 'wp-app-core'),
        'reset_action' => 'reset_email_settings',
        'reset_title' => __('Reset Email Settings?', 'wp-app-core'),
        'reset_message' => __('Are you sure you want to reset all email settings to their default values?\n\nThis action cannot be undone.', 'wp-app-core'),
        'form_id' => 'platform-email-settings-form'
    ],
    'security-authentication' => [
        'save_label' => __('Save Security Settings', 'wp-app-core'),
        'reset_action' => 'reset_security_authentication',
        'reset_title' => __('Reset Security Authentication Settings?', 'wp-app-core'),
        'reset_message' => __('Are you sure you want to reset all security authentication settings to their default values?\n\nThis action cannot be undone.', 'wp-app-core'),
        'form_id' => 'wp-app-core-security-authentication-form'
    ],
    'security-session' => [
        'save_label' => __('Save Session Settings', 'wp-app-core'),
        'reset_action' => 'reset_security_session',
        'reset_title' => __('Reset Security Session Settings?', 'wp-app-core'),
        'reset_message' => __('Are you sure you want to reset all security session settings to their default values?\n\nThis action cannot be undone.', 'wp-app-core'),
        'form_id' => 'wp-app-core-security-session-form'
    ],
    'security-policy' => [
        'save_label' => __('Save Policy Settings', 'wp-app-core'),
        'reset_action' => 'reset_security_policy',
        'reset_title' => __('Reset Security Policy Settings?', 'wp-app-core'),
        'reset_message' => __('Are you sure you want to reset all security policy settings to their default values?\n\nThis action cannot be undone.', 'wp-app-core'),
        'form_id' => 'wp-app-core-security-policy-form'
    ],
    'permissions' => [
        'save_label' => __('Save Permissions', 'wp-app-core'),
        'reset_action' => 'reset_platform_permissions',
        'reset_title' => __('Reset Permissions?', 'wp-app-core'),
        'reset_message' => __('Are you sure you want to reset all permissions to their default values?\n\nThis action cannot be undone.', 'wp-app-core'),
        'form_id' => 'platform-permissions-form'
    ],
    'demo-data' => [
        'save_label' => __('Save Development Settings', 'wp-app-core'),
        'reset_action' => '', // No reset for demo-data tab
        'reset_title' => '',
        'reset_message' => '',
        'form_id' => 'platform-demo-data-form'
    ],
];

$current_config = $tab_config[$current_tab] ?? $tab_config['general'];

?>

<div class="wrap wp-app-settings-page">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php
    // GLOBAL SCOPE: Page-level notification handling
    // Suppress WordPress default notices when we have custom tab-specific notices
    $show_custom_notice = false;

    // Check if we have custom save notice
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true' && isset($_GET['saved_tab'])) {
        $saved_tab = sanitize_key($_GET['saved_tab']);
        if ($saved_tab === $current_tab) {
            $show_custom_notice = true;
        }
    }

    // Check if we have custom reset notice
    if (isset($_GET['reset']) && isset($_GET['reset_tab'])) {
        $reset_tab = sanitize_key($_GET['reset_tab']);
        if ($reset_tab === $current_tab) {
            $show_custom_notice = true;
        }
    }

    // Only show WordPress default notices if we don't have custom notices
    if (!$show_custom_notice) {
        settings_errors();
    }
    ?>

    <?php
    // ABSTRACT PATTERN: Get notification messages from controller via hook
    // Each tab controller registers their messages via wpapp_settings_notification_messages hook
    $notification_messages = $controller->getNotificationMessages();
    $save_messages = $notification_messages['save_messages'];
    $reset_messages = $notification_messages['reset_messages'];

    // Show save success notices
    // Only show if saved_tab matches current_tab (prevent showing on tab switch)
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true' && isset($_GET['saved_tab'])) {
        $saved_tab = sanitize_key($_GET['saved_tab']);

        // Only show notice if we're on the same tab that was saved
        if ($saved_tab === $current_tab) {
            // Get message from controller-registered messages
            $success_message = $save_messages[$current_tab] ?? __('Settings have been saved successfully.', 'wp-app-core');
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong><?php echo esc_html($success_message); ?></strong></p>
            </div>
            <?php
        }
    }

    // Show reset success/error notices
    // Only show if reset_tab matches current_tab (prevent showing on tab switch)
    if (isset($_GET['reset']) && isset($_GET['reset_tab'])) {
        $reset_status = sanitize_key($_GET['reset']);
        $reset_tab = sanitize_key($_GET['reset_tab']);

        // Only show notice if we're on the same tab that was reset
        if ($reset_tab === $current_tab) {
            if ($reset_status === 'success') {
                // Get message from controller-registered messages
                $success_message = $reset_messages[$current_tab] ?? __('Settings have been reset to default values successfully.', 'wp-app-core');
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php echo esc_html($success_message); ?></strong></p>
                </div>
                <?php
            } elseif ($reset_status === 'error') {
                $error_message = isset($_GET['message'])
                    ? sanitize_text_field($_GET['message'])
                    : __('Failed to reset settings.', 'wp-app-core');
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><strong><?php _e('Error:', 'wp-app-core'); ?></strong> <?php echo esc_html($error_message); ?></p>
                </div>
                <?php
            }
        }
    }
    ?>

    <nav class="nav-tab-wrapper wp-clearfix" style="margin-bottom: 20px;">
        <?php foreach ($tabs as $tab_key => $tab_caption): ?>
            <?php $active = $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>
            <a href="<?php echo add_query_arg('tab', $tab_key); ?>"
               class="nav-tab <?php echo $active; ?>"
               data-tab="<?php echo esc_attr($tab_key); ?>">
                <?php echo esc_html($tab_caption); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="tab-content">
        <?php
        // Load the tab view through controller
        if (isset($controller)) {
            $controller->loadTabView($current_tab);
        }
        ?>
    </div>

    <!-- GLOBAL SCOPE: Page-level buttons for ALL tabs -->
    <div class="settings-page-footer" style="position: sticky; bottom: 0; background: #f0f0f1; padding: 15px 20px; border-top: 1px solid #c3c4c7; margin: 20px -20px -10px -20px; z-index: 100;">
        <p class="submit" style="margin: 0;">
            <button type="submit"
                    id="wpapp-settings-save"
                    class="button button-primary"
                    data-current-tab="<?php echo esc_attr($current_tab); ?>"
                    data-form-id="<?php echo esc_attr($current_config['form_id']); ?>">
                <?php echo esc_html($current_config['save_label']); ?>
            </button>

            <?php if (!empty($current_config['reset_action'])): ?>
            <button type="button"
                    id="wpapp-settings-reset"
                    class="button button-secondary"
                    data-current-tab="<?php echo esc_attr($current_tab); ?>"
                    data-reset-action="<?php echo esc_attr($current_config['reset_action']); ?>"
                    data-reset-title="<?php echo esc_attr($current_config['reset_title']); ?>"
                    data-reset-message="<?php echo esc_attr($current_config['reset_message']); ?>">
                <?php _e('Reset to Default', 'wp-app-core'); ?>
            </button>
            <?php endif; ?>
        </p>
    </div>
</div>
