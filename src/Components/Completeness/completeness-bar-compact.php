<?php
/**
 * Completeness Progress Bar - Compact Variant
 *
 * @package     WPAppCore
 * @subpackage  Components/Completeness
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/src/Components/Completeness/completeness-bar-compact.php
 *
 * Description: Compact version of completeness bar for AdminBar dropdown.
 *              Minimal, space-efficient design optimized for dropdown display.
 *              Shows only essential info: percentage, progress bar, and status.
 *
 * Differences from full version (completeness-bar.php):
 * - No detailed breakdown (categories)
 * - No missing fields list
 * - Minimal padding and spacing
 * - Smaller font sizes
 * - Single-line status message
 *
 * Variables Available:
 * @var CompletenessResult $completeness Calculation result
 * @var array $args Display options
 * @var string $entity_label Entity type label
 *
 * Usage:
 * Use CompletenessManager::renderProgressBar() with compact variant:
 * ```php
 * $manager->renderProgressBar('customer', $customer_id, [
 *     'variant' => 'compact',
 *     'show_details' => false,
 *     'show_missing' => false
 * ]);
 * ```
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-1195)
 * - Initial creation
 * - Optimized for AdminBar dropdown
 * - Minimal, clean design
 * - Mobile-friendly
 */

use WPAppCore\Models\Completeness\CompletenessResult;

defined('ABSPATH') || exit;

// Validate input
if (!isset($completeness) || !($completeness instanceof CompletenessResult)) {
    return;
}

$color_class = $completeness->getColorClass();
$status_text = $completeness->getStatusText();
$entity_label = $args['entity_label'] ?? __('Profile', 'wp-app-core');
$threshold = $completeness->minimum_threshold ?? 80;
?>

<div class="wpapp-completeness-compact"
     data-percentage="<?php echo esc_attr($completeness->percentage); ?>"
     data-can-transact="<?php echo $completeness->can_transact ? 'true' : 'false'; ?>">

    <!-- Header: Label + Badge -->
    <div class="wpapp-completeness-compact-header">
        <span class="wpapp-completeness-compact-label">
            <?php echo esc_html($entity_label); ?> <?php _e('Status', 'wp-app-core'); ?>:
        </span>
        <span class="wpapp-completeness-compact-badge <?php echo esc_attr($color_class); ?>">
            <?php echo esc_html($completeness->percentage); ?>%
            <span class="wpapp-completeness-compact-status"><?php echo esc_html($status_text); ?></span>
        </span>
    </div>

    <!-- Progress Bar -->
    <div class="wpapp-progress-bar-compact-container">
        <div class="wpapp-progress-bar-compact <?php echo esc_attr($color_class); ?>"
             style="width: <?php echo esc_attr($completeness->percentage); ?>%"
             role="progressbar"
             aria-valuenow="<?php echo esc_attr($completeness->percentage); ?>"
             aria-valuemin="0"
             aria-valuemax="100"
             aria-label="<?php esc_attr_e('Profile completeness', 'wp-app-core'); ?>">
        </div>
    </div>

    <!-- Status Message (compact) -->
    <?php if (!$completeness->can_transact): ?>
    <div class="wpapp-completeness-compact-message">
        <span class="dashicons dashicons-warning"></span>
        <?php
        printf(
            /* translators: %d: minimum threshold percentage */
            __('%d%% required to transact', 'wp-app-core'),
            $threshold
        );
        ?>
    </div>
    <?php endif; ?>

    <!-- Missing Count (if any) -->
    <?php
    $missing_count = count($completeness->missing_required);
    if ($missing_count > 0):
    ?>
    <div class="wpapp-completeness-compact-missing-count">
        <span class="dashicons dashicons-info"></span>
        <?php
        printf(
            /* translators: %d: number of missing required fields */
            _n('%d field missing', '%d fields missing', $missing_count, 'wp-app-core'),
            $missing_count
        );
        ?>
    </div>
    <?php endif; ?>

</div>

<style>
/* Compact variant styles - inline to avoid dependency issues */
.wpapp-completeness-compact {
    margin: 10px 0;
    padding: 12px;
    background: #f8f8f8;
    border-radius: 4px;
    border-left: 3px solid #ddd;
}

.wpapp-completeness-compact-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    font-size: 12px;
}

.wpapp-completeness-compact-label {
    font-weight: 600;
    color: #23282d;
}

.wpapp-completeness-compact-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8px;
    border-radius: 3px;
    font-weight: 700;
    font-size: 11px;
    line-height: 1;
}

.wpapp-completeness-compact-badge.progress-success {
    background: #ecf7ed;
    color: #46b450;
}

.wpapp-completeness-compact-badge.progress-warning {
    background: #fff8e5;
    color: #d97706;
}

.wpapp-completeness-compact-badge.progress-danger {
    background: #fce8e8;
    color: #dc3232;
}

.wpapp-completeness-compact-status {
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Progress bar */
.wpapp-progress-bar-compact-container {
    height: 6px;
    background: #e0e0e0;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 8px;
}

.wpapp-progress-bar-compact {
    height: 100%;
    border-radius: 3px;
    transition: width 0.6s ease;
}

.wpapp-progress-bar-compact.progress-success {
    background: #46b450;
}

.wpapp-progress-bar-compact.progress-warning {
    background: #ffb900;
}

.wpapp-progress-bar-compact.progress-danger {
    background: #dc3232;
}

/* Messages */
.wpapp-completeness-compact-message,
.wpapp-completeness-compact-missing-count {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    color: #666;
    margin-top: 6px;
}

.wpapp-completeness-compact-message {
    color: #d97706;
}

.wpapp-completeness-compact-message .dashicons,
.wpapp-completeness-compact-missing-count .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Color variants */
.wpapp-completeness-compact[data-can-transact="true"] {
    border-left-color: #46b450;
}

.wpapp-completeness-compact[data-can-transact="false"] {
    border-left-color: #dc3232;
}
</style>
