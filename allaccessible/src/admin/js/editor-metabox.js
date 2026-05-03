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

            // Update issues counts
            $('#aacb-issues-critical').text(data.issues.critical);
            $('#aacb-issues-serious').text(data.issues.serious);
            $('#aacb-issues-moderate').text(data.issues.moderate);
            $('#aacb-issues-minor').text(data.issues.minor);

            // Update last scan time
            if (data.last_scan) {
                const lastScan = new Date(data.last_scan);
                const timeAgo = this.getTimeAgo(lastScan);
                $('#aacb-last-scan-time').text(timeAgo);
            } else {
                $('#aacb-last-scan-time').text('No scan yet');
            }

            // Update data source notice
            if (data.data_source === 'site') {
                $('#aacb-data-source-notice').text('Showing site-wide score (no page-specific scan yet)');
            } else if (data.data_source === 'page') {
                $('#aacb-data-source-notice').text('Page-specific score • Updates daily');
            } else {
                $('#aacb-data-source-notice').text('Scores update once daily');
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
