<?php
/**
 * Completeness Progress Bar Component
 *
 * @package     WPAppCore
 * @subpackage  Components/Completeness
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Components/Completeness/completeness-bar.php
 *
 * Description: Reusable progress bar template for displaying entity completeness.
 *              Shows percentage, visual progress bar, missing fields, and detailed breakdown.
 *              Used by all plugins (customer, surveyor, association).
 *
 * Variables Available (set by CompletenessManager::renderProgressBar):
 * @var CompletenessResult $completeness Calculation result
 * @var array $args Display options
 *   - show_details (bool): Show detailed breakdown
 *   - show_missing (bool): Show missing fields list
 *   - class (string): Additional CSS classes
 *   - entity_label (string): Entity type label for display
 * @var array $config Entity configuration (label, icon, description)
 *
 * CSS Classes Used:
 * - wpapp-completeness-container: Main wrapper
 * - wpapp-completeness-header: Top section (title + percentage)
 * - wpapp-progress-bar-container: Progress bar wrapper
 * - wpapp-progress-bar: Actual progress bar
 * - progress-success/warning/danger: Color classes
 * - wpapp-completeness-warning: Warning message for low completion
 * - wpapp-completeness-missing: Missing fields section
 * - wpapp-completeness-details: Expandable breakdown
 *
 * JavaScript Events:
 * - wpapp:completeness-rendered: Fired after render
 * - wpapp:completeness-details-toggled: When details expanded/collapsed
 *
 * Usage:
 * Don't include this directly. Use CompletenessManager::renderProgressBar()
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-1195)
 * - Initial implementation
 * - Responsive design
 * - Color-coded progress bar
 * - Missing fields display
 * - Expandable detailed breakdown
 */

use WPAppCore\Models\Completeness\CompletenessResult;

defined('ABSPATH') || exit;

// Ensure we have required variables
if (!isset($completeness) || !($completeness instanceof CompletenessResult)) {
    echo '<p class="wpapp-error">' . __('Invalid completeness data.', 'wp-app-core') . '</p>';
    return;
}

// Determine color class based on percentage
$color_class = $completeness->getColorClass();
$status_text = $completeness->getStatusText();
$entity_label = $args['entity_label'] ?? __('Profile', 'wp-app-core');
$calculator = isset($calculator) ? $calculator : null;
$threshold = $completeness->minimum_threshold ?? 80;
?>

<div class="wpapp-completeness-container <?php echo esc_attr($args['class'] ?? ''); ?>"
     data-percentage="<?php echo esc_attr($completeness->percentage); ?>"
     data-can-transact="<?php echo $completeness->can_transact ? 'true' : 'false'; ?>">

    <!-- Header: Title + Percentage -->
    <div class="wpapp-completeness-header">
        <div class="wpapp-completeness-title">
            <h4>
                <?php
                printf(
                    /* translators: %s: entity label (Customer, Surveyor, etc.) */
                    __('%s Completeness', 'wp-app-core'),
                    esc_html($entity_label)
                );
                ?>
            </h4>
            <span class="wpapp-completeness-status-text <?php echo esc_attr($color_class); ?>">
                <?php echo esc_html($status_text); ?>
            </span>
        </div>
        <div class="wpapp-completeness-percentage-badge <?php echo esc_attr($color_class); ?>">
            <span class="percentage-number"><?php echo esc_html($completeness->percentage); ?></span>
            <span class="percentage-symbol">%</span>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="wpapp-progress-bar-container">
        <div class="wpapp-progress-bar <?php echo esc_attr($color_class); ?>"
             style="width: <?php echo esc_attr($completeness->percentage); ?>%"
             role="progressbar"
             aria-valuenow="<?php echo esc_attr($completeness->percentage); ?>"
             aria-valuemin="0"
             aria-valuemax="100"
             aria-label="<?php esc_attr_e('Completeness progress', 'wp-app-core'); ?>">
            <span class="wpapp-progress-label">
                <?php echo esc_html($completeness->earned_points); ?>/<?php echo esc_html($completeness->total_points); ?>
                <?php _e('points', 'wp-app-core'); ?>
            </span>
        </div>
    </div>

    <!-- Warning Message (if cannot transact) -->
    <?php if (!$completeness->can_transact): ?>
    <div class="wpapp-completeness-warning">
        <span class="dashicons dashicons-warning"></span>
        <div class="warning-text">
            <?php
            printf(
                /* translators: 1: current percentage, 2: minimum threshold percentage */
                __('Profile is %1$d%% complete. Minimum %2$d%% required to start transactions.', 'wp-app-core'),
                $completeness->percentage,
                $threshold
            );
            ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Missing Required Fields -->
    <?php if ($args['show_missing'] && !empty($completeness->missing_required)): ?>
    <div class="wpapp-completeness-missing required">
        <h5>
            <span class="dashicons dashicons-info"></span>
            <?php _e('Required Information Missing:', 'wp-app-core'); ?>
        </h5>
        <ul class="wpapp-missing-fields-list">
            <?php foreach ($completeness->missing_required as $field): ?>
            <li>
                <span class="dashicons dashicons-minus"></span>
                <?php echo esc_html($field); ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Missing Optional Fields (if any) -->
    <?php if ($args['show_missing'] && !empty($completeness->missing_optional)): ?>
    <div class="wpapp-completeness-missing optional">
        <h5>
            <span class="dashicons dashicons-lightbulb"></span>
            <?php _e('Optional Information (Improves Score):', 'wp-app-core'); ?>
        </h5>
        <ul class="wpapp-missing-fields-list">
            <?php foreach ($completeness->missing_optional as $field): ?>
            <li>
                <span class="dashicons dashicons-minus"></span>
                <?php echo esc_html($field); ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Detailed Breakdown (expandable) -->
    <?php if ($args['show_details'] && !empty($completeness->categories)): ?>
    <details class="wpapp-completeness-details">
        <summary>
            <span class="dashicons dashicons-chart-bar"></span>
            <?php _e('View Detailed Breakdown', 'wp-app-core'); ?>
            <span class="dashicons dashicons-arrow-down-alt2 toggle-icon"></span>
        </summary>
        <div class="wpapp-completeness-breakdown">
            <?php foreach ($completeness->categories as $category => $data): ?>
            <?php
            $cat_percentage = $data['total'] > 0 ? round(($data['earned'] / $data['total']) * 100) : 0;
            $cat_color = '';
            if ($cat_percentage >= 80) $cat_color = 'progress-success';
            elseif ($cat_percentage >= 50) $cat_color = 'progress-warning';
            else $cat_color = 'progress-danger';
            ?>
            <div class="category-item">
                <div class="category-header">
                    <strong class="category-name"><?php echo esc_html($category); ?></strong>
                    <span class="category-score">
                        <?php echo esc_html($data['earned']); ?>/<?php echo esc_html($data['total']); ?>
                        <span class="category-percentage <?php echo esc_attr($cat_color); ?>">
                            (<?php echo esc_html($cat_percentage); ?>%)
                        </span>
                    </span>
                </div>
                <div class="category-progress-bar-container">
                    <div class="category-progress-bar <?php echo esc_attr($cat_color); ?>"
                         style="width: <?php echo esc_attr($cat_percentage); ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </details>
    <?php endif; ?>

</div>

<script>
// Fire event after render for JavaScript integrations
jQuery(document).ready(function($) {
    $(document).trigger('wpapp:completeness-rendered', [{
        percentage: <?php echo json_encode($completeness->percentage); ?>,
        can_transact: <?php echo json_encode($completeness->can_transact); ?>,
        missing_required: <?php echo json_encode($completeness->missing_required); ?>
    }]);

    // Toggle icon animation
    $('.wpapp-completeness-details').on('toggle', function() {
        var $icon = $(this).find('.toggle-icon');
        if ($(this).prop('open')) {
            $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
        } else {
            $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
        }
        $(document).trigger('wpapp:completeness-details-toggled', [$(this).prop('open')]);
    });
});
</script>
