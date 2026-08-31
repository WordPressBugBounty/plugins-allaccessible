<?php
/**
 * Agentic Fixes — wp-admin dashboard widget.
 *
 * @package AllAccessible
 * @since   2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AllAccessible_AgenticFixes_DashboardWidget {

    const WIDGET_ID = 'aacb_agentic_fixes_dashboard';

    public static function register() {
        add_action('wp_dashboard_setup', array(__CLASS__, 'register_widget'));
    }

    public static function register_widget() {
        if (!current_user_can('manage_options')) return;

        // Unlinked installs get a local-data variant: the missing-alt-text
        // counter needs no account and no API, and hands the wizard a
        // concrete reason to exist.
        if (!get_option('aacb_accountID')) {
            wp_add_dashboard_widget(
                self::WIDGET_ID,
                __('AllAccessible — Accessibility', 'allaccessible'),
                array(__CLASS__, 'render_unlinked')
            );
            return;
        }

        wp_add_dashboard_widget(
            self::WIDGET_ID,
            __('AllAccessible — Agentic Fixes', 'allaccessible'),
            array(__CLASS__, 'render')
        );
    }

    /**
     * Unlinked variant — counts media-library images with no alt text
     * straight from the local database. Cached for a day; the count only
     * moves when media is uploaded or alt text is edited.
     */
    public static function render_unlinked() {
        $missing = get_transient('aacb_missing_alt_count');
        if (false === $missing) {
            global $wpdb;
            $missing = (int) $wpdb->get_var(
                "SELECT COUNT(p.ID)
                 FROM {$wpdb->posts} p
                 LEFT JOIN {$wpdb->postmeta} m
                   ON m.post_id = p.ID AND m.meta_key = '_wp_attachment_image_alt'
                 WHERE p.post_type = 'attachment'
                   AND p.post_mime_type LIKE 'image/%'
                   AND (m.meta_value IS NULL OR m.meta_value = '')"
            );
            set_transient('aacb_missing_alt_count', $missing, DAY_IN_SECONDS);
        }

        $wizard_url = admin_url('admin.php?page=allaccessible-wizard');
        ?>
        <div class="aacb-dashboard-tile" style="font-size:13px; line-height:1.5;">
            <?php if ($missing > 0) : ?>
                <div style="display:flex; align-items:baseline; gap:10px; margin:0 0 4px 0;">
                    <span style="font:700 28px/1 ui-monospace, SFMono-Regular, Menlo, monospace; color:#0f172a;">
                        <?php echo esc_html(number_format_i18n($missing)); ?>
                    </span>
                    <span style="font-size:12px; color:#64748b;">
                        <?php echo esc_html(_n('image is missing alt text', 'images are missing alt text', $missing, 'allaccessible')); ?>
                    </span>
                </div>
                <p style="margin:0 0 12px 0; color:#64748b; font-size:12px;">
                    <?php esc_html_e('Missing alt text is the most common accessibility issue (WCAG 1.1.1) — screen readers skip these images entirely.', 'allaccessible'); ?>
                </p>
                <p style="margin:0 0 12px 0; padding:8px 10px; background:#fef3e7; border-left:3px solid #f59e0b; border-radius:2px; color:#854603; font-size:12px;">
                    <?php esc_html_e('AllAccessible AI can draft descriptive alt text for these images — free preview, no credit card.', 'allaccessible'); ?>
                </p>
                <p style="margin:0;">
                    <a href="<?php echo esc_url($wizard_url); ?>" style="font-weight:600; color:#1d4ed8;">
                        <?php esc_html_e('Fix alt text with AI', 'allaccessible'); ?> →
                    </a>
                </p>
            <?php else : ?>
                <p style="margin:0 0 8px 0; color:#475569;">
                    <?php esc_html_e('All your images have alt text — nice work. Alt text is only one of the accessibility checks your pages need.', 'allaccessible'); ?>
                </p>
                <p style="margin:0;">
                    <a href="<?php echo esc_url($wizard_url); ?>" style="font-weight:600; color:#1d4ed8;">
                        <?php esc_html_e('Scan your site free', 'allaccessible'); ?> →
                    </a>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function render() {
        if (!class_exists('AllAccessible_ApiClient')) {
            self::render_error(__('Plugin not fully initialized. Please reload.', 'allaccessible'));
            return;
        }

        $client  = AllAccessible_ApiClient::get_instance();
        $summary = $client->get_manifest_summary();

        AllAccessible_Debug::api('AgenticFixesDashboardWidget', null, $summary);

        if (is_wp_error($summary)) {
            self::render_error($summary->get_error_message());
            return;
        }

        $tier         = isset($summary['tier']) ? (string) $summary['tier'] : 'free';
        $isFree       = ($tier === 'free');
        $totalIssues  = $isFree
            ? (int) ($summary['summary']['totalIssuesDetected'] ?? 0)
            : (int) ($summary['summary']['totalFixes']          ?? 0);
        $pending      = (int) ($summary['summary']['pendingApproval'] ?? 0);
        $pagesScanned = (int) ($summary['summary']['pagesScanned']    ?? 0);
        $lastScanAt   = $summary['lastScanAt'] ?? null;

        $cta_url = $isFree
            ? (is_array($summary['upgradeCta'] ?? null) && !empty($summary['upgradeCta']['url'])
                ? (string) $summary['upgradeCta']['url']
                : 'https://app.allaccessible.org/billing')
            : admin_url('admin.php?page=aacb-agentic-fixes');
        $cta_label = $isFree
            ? __('Upgrade to apply fixes', 'allaccessible')
            : __('Review fixes', 'allaccessible');

        ?>
        <div class="aacb-dashboard-tile" style="font-size:13px; line-height:1.5;">
            <?php if ($totalIssues === 0 && !$lastScanAt) : ?>
                <p style="margin:0 0 8px 0; color:#475569;">
                    <?php esc_html_e('No scans yet. Submit your sitemap from the AllAccessible page to start.', 'allaccessible'); ?>
                </p>
                <p style="margin:0;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=allaccessible-account')); ?>" style="font-weight:600;">
                        <?php esc_html_e('Go to AllAccessible', 'allaccessible'); ?> →
                    </a>
                </p>
                <?php return; ?>
            <?php endif; ?>

            <!-- Anchor number + label -->
            <div style="display:flex; align-items:baseline; gap:10px; margin:0 0 4px 0;">
                <span style="font:700 28px/1 ui-monospace, SFMono-Regular, Menlo, monospace; color:#0f172a;">
                    <?php echo esc_html(number_format_i18n($totalIssues)); ?>
                </span>
                <span style="font-size:12px; color:#64748b;">
                    <?php
                    echo esc_html(_n('accessibility issue detected', 'accessibility issues detected', $totalIssues, 'allaccessible'));
                    ?>
                </span>
            </div>

            <!-- Secondary line: pages scanned + last scan -->
            <p style="margin:0 0 12px 0; color:#64748b; font-size:12px;">
                <?php if ($pagesScanned > 0) :
                    printf(
                        /* translators: %s: count of pages scanned */
                        esc_html(_n('%s page scanned', '%s pages scanned', $pagesScanned, 'allaccessible')),
                        esc_html(number_format_i18n($pagesScanned))
                    );
                endif; ?>
                <?php if ($lastScanAt) :
                    if ($pagesScanned > 0) echo ' · ';
                    printf(
                        /* translators: %s: human-readable time diff (e.g., "2 hours") */
                        esc_html__('last scan %s ago', 'allaccessible'),
                        esc_html(human_time_diff(strtotime($lastScanAt), current_time('timestamp')))
                    );
                endif; ?>
            </p>

            <?php if (!$isFree && $pending > 0) : ?>
                <!-- Paid tier nudge: pending count is the one number that matters -->
                <p style="margin:0 0 12px 0; padding:8px 10px; background:#f0fdf4; border-left:3px solid #16a34a; border-radius:2px; color:#15803d; font-size:12px;">
                    <strong>
                        <?php
                        printf(
                            /* translators: %s: count of manifests waiting on approval */
                            esc_html(_n('%s manifest pending your approval', '%s manifests pending your approval', $pending, 'allaccessible')),
                            esc_html(number_format_i18n($pending))
                        );
                        ?>
                    </strong>
                </p>
            <?php elseif ($isFree && $totalIssues > 0) : ?>
                <!-- Free tier nudge: hand-off note that paid does the work for them -->
                <p style="margin:0 0 12px 0; padding:8px 10px; background:#fef3e7; border-left:3px solid #f59e0b; border-radius:2px; color:#854603; font-size:12px;">
                    <?php esc_html_e('AllAccessible agents can auto-fix these. Upgrade to enable agentic AI remediation.', 'allaccessible'); ?>
                </p>
            <?php endif; ?>

            <p style="margin:0;">
                <a href="<?php echo esc_url($cta_url); ?>"
                   <?php echo $isFree ? 'target="_blank" rel="noopener"' : ''; ?>
                   style="font-weight:600; color:#1d4ed8;">
                    <?php echo esc_html($cta_label); ?> →
                </a>
            </p>
        </div>
        <?php
    }

    private static function render_error($message) {
        ?>
        <p style="color:#64748b; font-size:13px; margin:0 0 4px 0;">
            <?php esc_html_e('Could not load fixes summary right now.', 'allaccessible'); ?>
        </p>
        <p style="color:#94a3b8; font-size:11px; margin:0;">
            <?php echo esc_html($message); ?>
        </p>
        <?php
    }
}

AllAccessible_AgenticFixes_DashboardWidget::register();
