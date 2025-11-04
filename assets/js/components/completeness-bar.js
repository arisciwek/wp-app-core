/**
 * Completeness Progress Bar Component - JavaScript
 *
 * @package     WPAppCore
 * @subpackage  Assets/JS/Components
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-app-core/assets/js/components/completeness-bar.js
 *
 * Description: JavaScript functionality for completeness progress bar component.
 *              Handles AJAX updates, animations, and interactive features.
 *              Works with CompletenessManager and completeness-bar.php template.
 *
 * Features:
 * - Real-time AJAX refresh
 * - Smooth animations
 * - Event handling
 * - Auto-refresh on entity updates
 * - Tooltip display for missing fields
 *
 * Dependencies:
 * - jQuery
 * - wp-app-core/completeness-bar.css
 *
 * Events Fired:
 * - wpapp:completeness-updated: After AJAX refresh
 * - wpapp:completeness-animation-complete: After progress animation
 *
 * Events Listened:
 * - wpapp:entity-updated: Triggers refresh
 * - wpapp:completeness-refresh: Manual refresh trigger
 *
 * Usage:
 * Auto-initializes on DOM ready. No manual init needed.
 * To manually refresh:
 * ```js
 * wpAppCompleteness.refresh('customer', customerId);
 * ```
 *
 * Changelog:
 * 1.0.0 - 2025-11-01 (TODO-1195)
 * - Initial implementation
 * - AJAX refresh functionality
 * - Progress bar animations
 * - Event system
 * - Auto-refresh on updates
 */

(function($) {
    'use strict';

    /**
     * WPApp Completeness Component
     */
    window.wpAppCompleteness = {

        /**
         * Configuration
         */
        config: {
            ajaxUrl: ajaxurl,
            animationDuration: 600,
            refreshDelay: 300,
            debug: false
        },

        /**
         * Active refresh timers
         * Format: {container_id: timer_id}
         */
        refreshTimers: {},

        /**
         * Initialize component
         */
        init: function() {
            this.bindEvents();
            this.log('Completeness component initialized');
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            var self = this;

            // Listen for entity updates (from other components)
            $(document).on('wpapp:entity-updated', function(e, data) {
                if (data.entity_type && data.entity_id) {
                    self.log('Entity updated event received:', data);
                    self.refresh(data.entity_type, data.entity_id);
                }
            });

            // Listen for manual refresh requests
            $(document).on('wpapp:completeness-refresh', function(e, entityType, entityId) {
                self.log('Manual refresh requested');
                self.refresh(entityType, entityId);
            });

            // Animate progress bars when they come into view
            this.setupIntersectionObserver();
        },

        /**
         * Refresh completeness display via AJAX
         *
         * @param {string} entityType Entity type (customer, surveyor, etc.)
         * @param {int} entityId Entity ID
         * @param {jQuery} $container Optional specific container to update
         */
        refresh: function(entityType, entityId, $container) {
            var self = this;

            // Find container if not provided
            if (!$container) {
                $container = $('.wpapp-completeness-container[data-entity-type="' + entityType + '"][data-entity-id="' + entityId + '"]');
            }

            if (!$container.length) {
                self.log('Container not found for refresh');
                return;
            }

            // Show loading state
            $container.addClass('wpapp-loading');

            // Clear any existing timer for this container
            var containerId = $container.attr('id') || 'container-' + Date.now();
            if (self.refreshTimers[containerId]) {
                clearTimeout(self.refreshTimers[containerId]);
            }

            // Debounce refresh
            self.refreshTimers[containerId] = setTimeout(function() {
                self.log('Refreshing completeness:', entityType, entityId);

                $.ajax({
                    url: self.config.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'wpapp_get_completeness',
                        entity_type: entityType,
                        entity_id: entityId,
                        nonce: wpAppConfig ? wpAppConfig.nonce : ''
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            self.updateDisplay($container, response.data);
                            $(document).trigger('wpapp:completeness-updated', [response.data]);
                        } else {
                            self.log('Refresh failed:', response);
                        }
                    },
                    error: function(xhr, status, error) {
                        self.log('AJAX error:', error);
                    },
                    complete: function() {
                        $container.removeClass('wpapp-loading');
                        delete self.refreshTimers[containerId];
                    }
                });
            }, self.config.refreshDelay);
        },

        /**
         * Update completeness display with new data
         *
         * @param {jQuery} $container Container element
         * @param {object} data Completeness data
         */
        updateDisplay: function($container, data) {
            var self = this;

            // Update percentage badge
            var $badge = $container.find('.wpapp-completeness-percentage-badge');
            var $percentageNum = $badge.find('.percentage-number');
            var oldPercentage = parseInt($percentageNum.text()) || 0;
            var newPercentage = data.percentage || 0;

            // Animate percentage change
            if (oldPercentage !== newPercentage) {
                this.animateNumber($percentageNum, oldPercentage, newPercentage);
            }

            // Update color classes
            var colorClass = this.getColorClass(newPercentage);
            $badge.removeClass('progress-success progress-warning progress-danger').addClass(colorClass);

            // Update status text
            var $statusText = $container.find('.wpapp-completeness-status-text');
            $statusText.text(this.getStatusText(newPercentage));
            $statusText.removeClass('progress-success progress-warning progress-danger').addClass(colorClass);

            // Update progress bar
            var $progressBar = $container.find('.wpapp-progress-bar');
            $progressBar.css('width', newPercentage + '%');
            $progressBar.removeClass('progress-success progress-warning progress-danger').addClass(colorClass);

            // Update progress label
            $progressBar.find('.wpapp-progress-label').text(
                data.earned_points + '/' + data.total_points + ' points'
            );

            // Update can_transact attribute
            $container.attr('data-can-transact', data.can_transact ? 'true' : 'false');

            // Show/hide warning
            var $warning = $container.find('.wpapp-completeness-warning');
            if (!data.can_transact) {
                if (!$warning.length) {
                    // Create warning if it doesn't exist
                    var threshold = data.minimum_threshold || 80;
                    var warningHtml = '<div class="wpapp-completeness-warning">' +
                        '<span class="dashicons dashicons-warning"></span>' +
                        '<div class="warning-text">' +
                        'Profile is ' + newPercentage + '% complete. Minimum ' + threshold + '% required to start transactions.' +
                        '</div></div>';
                    $container.find('.wpapp-progress-bar-container').after(warningHtml);
                }
            } else {
                $warning.fadeOut(300, function() { $(this).remove(); });
            }

            // Trigger animation complete event
            setTimeout(function() {
                $(document).trigger('wpapp:completeness-animation-complete', [data]);
            }, self.config.animationDuration);
        },

        /**
         * Animate number change
         *
         * @param {jQuery} $element Element containing number
         * @param {int} from Start value
         * @param {int} to End value
         */
        animateNumber: function($element, from, to) {
            var self = this;
            var duration = this.config.animationDuration;
            var startTime = Date.now();

            function update() {
                var elapsed = Date.now() - startTime;
                var progress = Math.min(elapsed / duration, 1);

                // Easing function (easeOutCubic)
                var eased = 1 - Math.pow(1 - progress, 3);

                var current = Math.round(from + (to - from) * eased);
                $element.text(current);

                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            }

            requestAnimationFrame(update);
        },

        /**
         * Get color class based on percentage
         *
         * @param {int} percentage Completion percentage
         * @return {string} Color class
         */
        getColorClass: function(percentage) {
            if (percentage >= 80) return 'progress-success';
            if (percentage >= 50) return 'progress-warning';
            return 'progress-danger';
        },

        /**
         * Get status text based on percentage
         *
         * @param {int} percentage Completion percentage
         * @return {string} Status text
         */
        getStatusText: function(percentage) {
            if (percentage >= 90) return 'Excellent';
            if (percentage >= 70) return 'Good';
            if (percentage >= 50) return 'Fair';
            return 'Incomplete';
        },

        /**
         * Setup Intersection Observer for lazy animation
         *
         * Animates progress bars when they scroll into view
         */
        setupIntersectionObserver: function() {
            if (!('IntersectionObserver' in window)) {
                // Fallback: animate immediately
                this.animateVisibleProgressBars();
                return;
            }

            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var $container = $(entry.target);
                        if (!$container.hasClass('wpapp-animated')) {
                            wpAppCompleteness.animateProgressBar($container);
                            $container.addClass('wpapp-animated');
                        }
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '50px'
            });

            $('.wpapp-completeness-container').each(function() {
                observer.observe(this);
            });
        },

        /**
         * Animate progress bar on initial load
         *
         * @param {jQuery} $container Container element
         */
        animateProgressBar: function($container) {
            var $progressBar = $container.find('.wpapp-progress-bar');
            var targetWidth = $progressBar.css('width');

            // Start from 0 and animate to target
            $progressBar.css('width', '0%');
            setTimeout(function() {
                $progressBar.css('width', targetWidth);
            }, 100);
        },

        /**
         * Animate all visible progress bars (fallback)
         */
        animateVisibleProgressBars: function() {
            var self = this;
            $('.wpapp-completeness-container').each(function() {
                if (self.isElementInViewport(this)) {
                    self.animateProgressBar($(this));
                }
            });
        },

        /**
         * Check if element is in viewport
         *
         * @param {Element} el DOM element
         * @return {boolean} True if in viewport
         */
        isElementInViewport: function(el) {
            var rect = el.getBoundingClientRect();
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        },

        /**
         * Debug logging
         *
         * @param {*} ...args Arguments to log
         */
        log: function() {
            if (this.config.debug || (window.wpAppConfig && wpAppConfig.debug)) {
                console.log('[WPApp Completeness]', ...arguments);
            }
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        wpAppCompleteness.init();
    });

})(jQuery);
