<?php
/**
 * Dashboard Layout — Site Overview hero.
 *
 * Public helpers:
 *   - aacb_render_overview_hero( $extras = [], $opts = [] )  The Site Overview hero.
 *   - aacb_info_tip( $text )                                 Accessible CSS tooltip.
 *   - aacb_ov_dial( $score, $band )                          Inline SVG score dial.
 *
 * @package AllAccessible
 * @since   2.0.0
 */

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * Render the four-up dashboard hero stat row.
 *
 * Tiles: Site Score · Pages Audited · Pending Fixes · Plan Usage.
 *
 * @param string $account_tier  free|trial|starter|legacy|enterprise|unknown
 * @param object $site_options  /validate response — must expose ->usageSummary if usage data exists
 * @param array  $extras        optional data injected from the page:
 *                                - 'site_score'          int|null  0-100
 *                                - 'pages_scanned'       int|null
 *                                - 'pending_fixes'       int|null
 *                                - 'active_fixes'        int|null
 *                                - 'fixes_url'           string
 *                                - 'images_total'        int|null  live alt_text_data count
 *                                - 'images_ai_generated' int|null  subset with AI alt text
 *                                - 'images_pending'      int|null  AI suggested, awaiting review
 */
if (!function_exists('aacb_info_tip')) {
    /**
     * Tiny accessible "ⓘ" affordance for a stat/card label. 
     * 
     * @param string $text Short plain-text explanation (one or two sentences).
     */
    function aacb_info_tip($text) {
        $text = (string) $text;
        if ($text === '') {
            return '';
        }
        return '<span class="aacb-info-tip" tabindex="0" role="note" aria-label="' . esc_attr($text) . '">'
            . 'i<span class="aacb-info-tip__bubble" aria-hidden="true">' . esc_html($text) . '</span>'
            . '</span>';
    }
}

if (!function_exists('aacb_ov_dial')) {
    /**
     * Score dial for the Site Overview hero.
     */
    function aacb_ov_dial($score, $band) {
        $has = is_int($score);
        $val = $has ? max(0, min(100, $score)) : 0;
        $r = 40; $cx = 52; $cy = 52;
        $circ = 2 * M_PI * $r;
        $off  = $circ * (1 - $val / 100);
        $colors = array(
            'ok'      => '#16a34a',
            'primary' => '#2563eb',
            'warn'    => '#d97706',
            'danger'  => '#dc2626',
            'none'    => '#94a3b8',
        );
        $stroke = isset($colors[$band]) ? $colors[$band] : $colors['primary'];
        ob_start();
        ?>
        <svg width="104" height="104" viewBox="0 0 104 104" role="img"
             aria-label="<?php echo esc_attr(sprintf(
                /* translators: %s: accessibility score 0-100 or em-dash */
                __('Accessibility score %s out of 100', 'allaccessible'),
                $has ? (string) $val : '—'
             )); ?>">
            <circle cx="<?php echo $cx; ?>" cy="<?php echo $cy; ?>" r="<?php echo $r; ?>" fill="none" stroke="#e2e8f0" stroke-width="8"/>
            <?php if ($has) : ?>
                <circle cx="<?php echo $cx; ?>" cy="<?php echo $cy; ?>" r="<?php echo $r; ?>" fill="none"
                        stroke="<?php echo esc_attr($stroke); ?>" stroke-width="8" stroke-linecap="round"
                        stroke-dasharray="<?php echo esc_attr($circ); ?>"
                        stroke-dashoffset="<?php echo esc_attr($off); ?>"
                        transform="rotate(-90 <?php echo $cx; ?> <?php echo $cy; ?>)"/>
            <?php endif; ?>
            <text x="<?php echo $cx; ?>" y="<?php echo $cy - 2; ?>" text-anchor="middle" dominant-baseline="central"
                  font-family="ui-monospace, SFMono-Regular, Menlo, monospace" font-size="30" font-weight="700"
                  fill="<?php echo esc_attr($stroke); ?>"><?php echo esc_html($has ? (string) $val : '—'); ?></text>
            <?php if ($has) : ?>
                <text x="<?php echo $cx; ?>" y="<?php echo $cy + 22; ?>" text-anchor="middle" dominant-baseline="central"
                      font-family="var(--aacx-font-sans, sans-serif)" font-size="11" font-weight="600"
                      letter-spacing="1" fill="#94a3b8">/ 100</text>
            <?php endif; ?>
        </svg>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('aacb_render_overview_hero')) {
    /**
     * Site Overview hero.
     *
     * @param array $extras Same hero context SettingsPage builds (site_score,
     *                      remediation_score, pages_scanned, pending_fixes,
     *                      manual_required_count, ai_resolved_count,
     *                      images_ai_generated, images_total, usage_bars, ...).
     * @param array $opts   has_agentic(bool), preview_url, dashboard_url,
     *                      fixes_url, issues_anchor.
     */
    function aacb_render_overview_hero($extras, $opts = array()) {
        $extras = is_array($extras) ? $extras : array();
        $opts   = is_array($opts)   ? $opts   : array();

        $score = (isset($extras['site_score']) && $extras['site_score'] !== null)
            ? (int) round((float) $extras['site_score']) : null;
        $remediation = (isset($extras['remediation_score']) && $extras['remediation_score'] !== null)
            ? (int) round((float) $extras['remediation_score']) : null;
        $is_live   = !empty($extras['is_live_estimate']);
        $pages     = isset($extras['pages_scanned']) ? (int) $extras['pages_scanned'] : null;
        $pending   = (int) ($extras['pending_fixes'] ?? 0);
        $manual    = (int) ($extras['manual_required_count'] ?? 0);
        $ai_res    = (int) ($extras['ai_resolved_count'] ?? 0);
        $alt_ai    = (int) ($extras['images_ai_generated'] ?? 0);
        $img_total = (int) ($extras['images_total'] ?? 0);

        $pv_cur = null; $pv_lim = null;
        if (!empty($extras['usage_bars']) && is_array($extras['usage_bars'])) {
            foreach ($extras['usage_bars'] as $bar) {
                if (is_array($bar) && isset($bar['limit'])) { // first bar = pageviews
                    $pv_cur = (int) ($bar['current'] ?? 0);
                    $pv_lim = (int) $bar['limit'];
                    break;
                }
            }
        }

        $has_agentic   = !empty($opts['has_agentic']);
        $preview_url   = isset($opts['preview_url'])   ? (string) $opts['preview_url']   : '';
        $dashboard_url = isset($opts['dashboard_url']) ? (string) $opts['dashboard_url'] : '';
        $fixes_url     = isset($opts['fixes_url'])     ? (string) $opts['fixes_url']     : admin_url('admin.php?page=aacb-agentic-fixes');
        $issues_anchor = isset($opts['issues_anchor']) ? (string) $opts['issues_anchor'] : '#aacb-overview-issues';
        $pages_url     = isset($opts['pages_url'])     ? (string) $opts['pages_url']     : '';

        $sched = isset($opts['schedule']) && is_array($opts['schedule']) ? $opts['schedule'] : null;
        $cad_header = ''; $cad_freq = ''; $cad_last = ''; $cad_next = '';
        if ($sched) {
            $freq      = (string) ($sched['scanFrequency'] ?? '');
            $last_at   = $sched['lastScanAt'] ?? null;
            $next_at   = $sched['nextScanAt'] ?? null;
            $freq_word = array(
                'daily'   => __('Daily', 'allaccessible'),
                'weekly'  => __('Weekly', 'allaccessible'),
                'monthly' => __('Monthly', 'allaccessible'),
                'never'   => __('On-demand', 'allaccessible'),
            );
            if (isset($freq_word[$freq])) {
                $cad_freq = $freq_word[$freq];
            } elseif (!empty($sched['scanFrequencyDays'])) {
                $cad_freq = sprintf(
                    /* translators: %s: number of days between scans */
                    __('Every %s days', 'allaccessible'),
                    number_format_i18n((int) $sched['scanFrequencyDays'])
                );
            }
            if ($cad_freq !== '' || $last_at) {
                // 'never' = no recurring schedule (e.g. free tier) — scans are
                // on-demand, so don't label them "Scheduled".
                $cad_header = ($freq === 'never')
                    ? __('Scans', 'allaccessible')
                    : __('Scheduled scans', 'allaccessible');
                $now = current_time('timestamp');
                if ($last_at && ($lt = strtotime($last_at))) {
                    $cad_last = sprintf(
                        /* translators: %s: human time diff, e.g. "2 hours" */
                        __('%s ago', 'allaccessible'),
                        human_time_diff($lt, $now)
                    );
                } else {
                    $cad_last = __('No scans yet', 'allaccessible');
                }
                if ($freq !== 'never' && $next_at && ($nt = strtotime($next_at))) {
                    $cad_next = $nt > $now
                        ? sprintf(/* translators: %s: human time diff */ __('in %s', 'allaccessible'), human_time_diff($now, $nt))
                        : __('due now', 'allaccessible');
                }
            }
        }
        $has_cadence = ($cad_header !== '');

        // Verdict + score band.
        if ($score === null) {
            $verdict = __('Run your first scan', 'allaccessible'); $band = 'none'; $accent = 'var(--aacx-slate-400)';
        } elseif ($score >= 90) {
            // NOTE: never claim "compliant" / "compliance" — legal-exposure
            // phrasing (see legal language audit). Describe the score/coverage.
            $verdict = __('Excellent — strong accessibility coverage.', 'allaccessible'); $band = 'ok'; $accent = 'var(--aacx-ok-600)';
        } elseif ($score >= 75) {
            $verdict = __('Good — a few fixes to go.', 'allaccessible'); $band = 'primary'; $accent = 'var(--aacx-primary-600)';
        } elseif ($score >= 50) {
            $verdict = __('Needs attention.', 'allaccessible'); $band = 'warn'; $accent = 'var(--aacx-warn-600)';
        } else {
            $verdict = __('Needs work — start with the top issues.', 'allaccessible'); $band = 'danger'; $accent = 'var(--aacx-danger-600)';
        }

        // Sub-line (pre-escaped segments joined by a muted dot).
        $dot = ' <span style="color:var(--aacx-border);">·</span> ';
        if ($score === null) {
            $sub_html = esc_html__('Run a scan and AllAccessible agents will check every page for accessibility issues.', 'allaccessible');
        } else {
            $bits = array();
            if ($remediation !== null) {
                $bits[] = sprintf(
                    /* translators: %s: percentage with <b> wrapper */
                    esc_html__('%s%% of issues remediated', 'allaccessible'),
                    '<b>' . esc_html(number_format_i18n($remediation)) . '</b>'
                );
            }
            if ($pages !== null && $pages > 0) {
                $pg = sprintf(
                    esc_html(_n('%s page audited', '%s pages audited', $pages, 'allaccessible')),
                    '<b>' . esc_html(number_format_i18n($pages)) . '</b>'
                );
                if ($pages_url) {
                    $pg .= ' <a href="' . esc_url($pages_url) . '"'
                        . ' style="color:var(--aacx-primary-600);font-weight:var(--aacx-weight-semibold);white-space:nowrap;text-decoration:none;">'
                        . esc_html__('View pages', 'allaccessible') . ' →</a>';
                }
                $bits[] = $pg;
            }
            $sub_html = implode($dot, $bits);
        }

        // Smart action bar — single most important next step.
        $act_class = 'aacb-ov-action--clear'; $act_icon = '✓'; $act_text = ''; $act_btn = '';
        if ($has_agentic && $pending > 0) {
            $act_class = 'aacb-ov-action--attention'; $act_icon = '⚡';
            $act_text = sprintf(
                esc_html(_n('%s fix is ready for your review', '%s fixes are ready for your review', $pending, 'allaccessible')),
                '<b>' . esc_html(number_format_i18n($pending)) . '</b>'
            );
            $act_btn = '<a href="' . esc_url($fixes_url) . '" class="aacx-v2__btn aacx-v2__btn--primary aacx-v2__btn--sm">'
                . esc_html__('Review fixes', 'allaccessible') . ' →</a>';
        } elseif ($manual > 0) {
            $act_class = 'aacb-ov-action--attention'; $act_icon = '⚠';
            $act_text = sprintf(
                esc_html(_n('%s issue needs a manual fix', '%s issues need a manual fix', $manual, 'allaccessible')),
                '<b>' . esc_html(number_format_i18n($manual)) . '</b>'
            );
            $act_btn = '<a href="' . esc_url($issues_anchor) . '" class="aacx-v2__btn aacx-v2__btn--secondary aacx-v2__btn--sm">'
                . esc_html__('View issues', 'allaccessible') . ' ↓</a>';
        } elseif ($score !== null) {
            $act_class = 'aacb-ov-action--clear'; $act_icon = '✓';
            $act_text = esc_html__('You\'re all caught up — AllAccessible agents are monitoring your site.', 'allaccessible');
        } else {
            $act_class = 'aacb-ov-action--attention'; $act_icon = '✦';
            $act_text = esc_html__('No scan yet — run one to find and fix accessibility issues.', 'allaccessible');
            $act_btn = '<a href="' . esc_url($fixes_url) . '" class="aacx-v2__btn aacx-v2__btn--primary aacx-v2__btn--sm">'
                . esc_html__('Get started', 'allaccessible') . ' →</a>';
        }

        // Metric strip (proof) — only items we have data for.
        $metrics = array();
        $metrics[] = '<span class="aacb-ov-metrics__item aacb-ov-metrics__ai">✦ <b>' . esc_html(number_format_i18n($ai_res)) . '</b> ' . esc_html__('AI-resolved', 'allaccessible') . '</span>';
        $metrics[] = '<span class="aacb-ov-metrics__item"><b>' . esc_html(number_format_i18n($manual)) . '</b> ' . esc_html__('manual', 'allaccessible') . '</span>';
        if ($img_total > 0) {
            $metrics[] = '<span class="aacb-ov-metrics__item"><b>' . esc_html(number_format_i18n($alt_ai)) . '</b> ' . esc_html__('images with alt text', 'allaccessible') . '</span>';
        }
        if ($pv_lim) {
            $metrics[] = '<span class="aacb-ov-metrics__item"><b>' . esc_html(number_format_i18n((int) $pv_cur)) . '</b> / ' . esc_html(number_format_i18n($pv_lim)) . ' ' . esc_html__('pageviews', 'allaccessible') . '</span>';
        }
        $metric_sep = '<span class="aacb-ov-metrics__sep" aria-hidden="true">·</span>';
        ?>
        <div class="aacb-ov" style="--aacb-ov-accent: <?php echo esc_attr($accent); ?>;">

          <section class="aacb-ov-hero" aria-label="<?php esc_attr_e('Accessibility status', 'allaccessible'); ?>">
            <div class="aacb-ov-hero__dial"><?php echo aacb_ov_dial($score, $band); // SVG, internally escaped // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
            <div class="aacb-ov-hero__body">
              <p class="aacb-ov-hero__verdict"><?php echo esc_html($verdict); ?></p>
              <p class="aacb-ov-hero__sub">
                <?php echo $sub_html; // pre-escaped segments // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php if ($is_live) : ?><span class="aacb-ov-hero__live"><i></i><?php esc_html_e('Live · updates nightly', 'allaccessible'); ?></span><?php endif; ?>
              </p>
              <?php if ($preview_url || $dashboard_url) : ?>
                <div class="aacb-ov-hero__actions" style="display:flex;gap:var(--aacx-space-2);margin-top:var(--aacx-space-4);flex-wrap:wrap;">
                  <?php if ($preview_url) : ?>
                    <a href="<?php echo esc_url($preview_url); ?>" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--secondary aacx-v2__btn--sm"><?php esc_html_e('Preview widget', 'allaccessible'); ?></a>
                  <?php endif; ?>
                  <?php if ($dashboard_url) : ?>
                    <a href="<?php echo esc_url($dashboard_url); ?>" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--ghost aacx-v2__btn--sm"><?php esc_html_e('Open dashboard', 'allaccessible'); ?> →</a>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>

            <?php if ($has_cadence) : ?>
              <div class="aacb-ov-hero__cadence" aria-label="<?php esc_attr_e('Scan schedule', 'allaccessible'); ?>">
                <div class="aacb-ov-hero__cadence-label">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
                  </svg>
                  <?php echo esc_html($cad_header); ?>
                  <?php echo aacb_info_tip(__('How often AllAccessible automatically scans your whole site behind the scenes for a deep accessibility analysis. You can also run on-demand scans of individual pages anytime — from the WordPress page editor, or on the front end while logged in.', 'allaccessible')); ?>
                </div>
                <?php if ($cad_freq !== '') : ?>
                  <div class="aacb-ov-hero__cadence-row"><span><?php esc_html_e('Frequency', 'allaccessible'); ?></span><b><?php echo esc_html($cad_freq); ?></b></div>
                <?php endif; ?>
                <?php if ($cad_last !== '') : ?>
                  <div class="aacb-ov-hero__cadence-row"><span><?php esc_html_e('Last', 'allaccessible'); ?></span><b><?php echo esc_html($cad_last); ?></b></div>
                <?php endif; ?>
                <?php if ($cad_next !== '') : ?>
                  <div class="aacb-ov-hero__cadence-row"><span><?php esc_html_e('Next', 'allaccessible'); ?></span><b><?php echo esc_html($cad_next); ?></b></div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </section>

          <div class="aacb-ov-action <?php echo esc_attr($act_class); ?>" role="status">
            <span class="aacb-ov-action__text">
              <span class="aacb-ov-action__icon" aria-hidden="true"><?php echo esc_html($act_icon); ?></span>
              <span><?php echo $act_text; // pre-escaped // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
            </span>
            <?php if ($act_btn) : ?><span><?php echo $act_btn; // pre-built, escaped // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><?php endif; ?>
          </div>

          <div class="aacb-ov-metrics" aria-label="<?php esc_attr_e('Remediation activity', 'allaccessible'); ?>">
            <?php echo implode(' ' . $metric_sep . ' ', $metrics); // pre-escaped // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          </div>

        </div>
        <?php
    }
}
