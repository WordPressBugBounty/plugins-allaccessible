/**
 * AllAccessible Editor Meta Box JavaScript
 *
 * Handles loading and displaying accessibility scores in the editor meta box.
 *
 * @package AllAccessible
 * @version 2.0.0
 */

(function($) {
    'use strict';

    const AACBEditorMeta = {
        /**
         * Initialize
         */
        init: function() {
            this.loadScore();
            this.bindEvents();
        },

        /**
         * Bind UI events
         */
        bindEvents: function() {
            $('#aacb-rescan-btn').on('click', this.rescanPage.bind(this));
            $('#aacb-retry-btn').on('click', this.loadScore.bind(this));
        },

        /**
         * Load accessibility score for current page
         */
        loadScore: function() {
            const postId = aacbEditorMeta.post_id;

            if (!postId) {
                this.showError();
                return;
            }

            // Show loading state
            $('#aacb-metabox-loading').show();
            $('#aacb-metabox-content').hide();
            $('#aacb-metabox-error').hide();

            // Fetch score from REST API
            $.ajax({
                url: aacbEditorMeta.rest_url + 'page-score/' + postId,
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', aacbEditorMeta.nonce);
                },
                success: this.displayScore.bind(this),
                error: this.showError.bind(this)
            });
        },

        /**
         * Display score in meta box
         */
        displayScore: function(data) {
            // Hide loading, show content
            $('#aacb-metabox-loading').hide();
            $('#aacb-metabox-content').show();
            $('#aacb-metabox-error').hide();

            // Update score
            $('#aacb-score-value').text(data.overall_score);
            $('#aacb-score-grade').text('Grade: ' + data.grade);
            $('#aacb-wcag-value').text(data.wcag_level);

            // Update score circle color
            const scoreCircle = $('#aacb-score-circle');
            scoreCircle.removeClass('score-excellent score-good score-fair score-poor');

            if (data.overall_score >= 90) {
                scoreCircle.addClass('score-excellent');
            } else if (data.overall_score >= 75) {
                scoreCircle.addClass('score-good');
            } else if (data.overall_score >= 60) {
                scoreCircle.addClass('score-fair');
            } else {
                scoreCircle.addClass('score-poor');
            }

            // Update issues counts. Guard: the API can return a partial payload
            // (e.g. a page that was never scanned, or an error-shaped response on
            // an HTTP 200) where `issues` is absent — default to zeros instead of
            // throwing "Cannot read properties of undefined (reading 'critical')".
            var issues = data.issues || {};
            $('#aacb-issues-critical').text(issues.critical || 0);
            $('#aacb-issues-serious').text(issues.serious || 0);
            $('#aacb-issues-moderate').text(issues.moderate || 0);
            $('#aacb-issues-minor').text(issues.minor || 0);

            // Update last scan time
            if (data.last_scan) {
                const lastScan = new Date(data.last_scan);
                const timeAgo = this.getTimeAgo(lastScan);
                $('#aacb-last-scan-time').text(timeAgo);
            } else {
                $('#aacb-last-scan-time').text(aacbEditorMeta.labels.loading);
            }
        },

        /**
         * Show error state
         */
        showError: function() {
            $('#aacb-metabox-loading').hide();
            $('#aacb-metabox-content').hide();
            $('#aacb-metabox-error').show();
        },

        /**
         * Trigger rescan for current page
         */
        rescanPage: function() {
            const postId = aacbEditorMeta.post_id;
            const button = $('#aacb-rescan-btn');

            // Disable button and show loading
            button.prop('disabled', true);
            button.html('<span class="spinner is-active" style="float:none;margin:0 5px 0 0;"></span>' +
                        aacbEditorMeta.labels.loading);

            // Trigger rescan via REST API
            $.ajax({
                url: aacbEditorMeta.rest_url + 'page-score/' + postId + '/rescan',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', aacbEditorMeta.nonce);
                },
                success: function(data) {
                    // Show success message
                    button.html('<span class="dashicons dashicons-yes"></span> ' +
                               aacbEditorMeta.labels.rescan_success);

                    // Reload score after 3 seconds
                    setTimeout(function() {
                        button.prop('disabled', false);
                        button.html('<span class="dashicons dashicons-update"></span> Rescan Page');
                        this.loadScore();
                    }.bind(this), 3000);
                }.bind(this),
                error: function() {
                    // Show error message
                    button.html('<span class="dashicons dashicons-no"></span> ' +
                               aacbEditorMeta.labels.rescan_error);

                    // Re-enable button after 2 seconds
                    setTimeout(function() {
                        button.prop('disabled', false);
                        button.html('<span class="dashicons dashicons-update"></span> Rescan Page');
                    }, 2000);
                }
            });
        },

        /**
         * Get human-readable time ago
         */
        getTimeAgo: function(date) {
            const seconds = Math.floor((new Date() - date) / 1000);
            let interval = seconds / 31536000;

            if (interval > 1) {
                return Math.floor(interval) + ' years ago';
            }
            interval = seconds / 2592000;
            if (interval > 1) {
                return Math.floor(interval) + ' months ago';
            }
            interval = seconds / 86400;
            if (interval > 1) {
                return Math.floor(interval) + ' days ago';
            }
            interval = seconds / 3600;
            if (interval > 1) {
                return Math.floor(interval) + ' hours ago';
            }
            interval = seconds / 60;
            if (interval > 1) {
                return Math.floor(interval) + ' minutes ago';
            }
            return Math.floor(seconds) + ' seconds ago';
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('#aacb_accessibility_score').length > 0 || $('#aacb_accessibility_score_upgrade').length > 0) {
            AACBEditorMeta.init();
        }
    });

})(jQuery);
