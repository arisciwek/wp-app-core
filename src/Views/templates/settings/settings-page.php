<?php
/**
 * Platform Settings Page Template
 *
 * @package     WP_App_Core
 * @subpackage  Views/Templates/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Views/templates/settings/settings-page.php
 *
 * Description: Main settings page template with tab navigation
 *              for platform settings management
 *
 * Changelog:
 * 1.0.0 - 2025-10-19
 * - Initial creation
 * - Tab navigation structure
 * - Settings error handling
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

?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors(); ?>

    <nav class="nav-tab-wrapper wp-clearfix" style="margin-bottom: 20px;">
        <?php foreach ($tabs as $tab_key => $tab_caption): ?>
            <?php $active = $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>
            <a href="<?php echo add_query_arg('tab', $tab_key); ?>"
               class="nav-tab <?php echo $active; ?>">
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
</div>
