<?php
/**
 * Agentic Fixes — main admin page (admin.php?page=aacb-agentic-fixes).
 *
 * @package AllAccessible
 * @since   2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AllAccessible_AgenticFixes_Page {

    const PAGE_SLUG  = 'aacb-agentic-fixes';
    const NONCE_NAME = 'aacb_manifest_action';
    const BETA_TIERS = array('starter', 'pro', 'business', 'enterprise', 'agency');

    public static function register() {
        add_action('admin_menu',            array(__CLASS__, 'register_menu'), 15);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    public static function register_menu() {
        add_submenu_page(
            'allaccessible',
            __('Agentic Fixes', 'allaccessible'),
            __('Agentic Fixes', 'allaccessible'),
            'manage_options',
            self::PAGE_SLUG,
            array(__CLASS__, 'render')
        );
    }

    public static function enqueue_assets($hook) {
        if ($hook !== 'allaccessible_page_' . self::PAGE_SLUG) return;

        $legacy = AACB_PATH . 'assets/admin.css';
        $legacy_ver = file_exists($legacy) ? (string) filemtime($legacy) : AACB_VERSION;
        wp_enqueue_style('aacb-admin', AACB_URL . 'assets/admin.css', array(), $legacy_ver);
        wp_enqueue_style('aacx-v2-admin', AACB_CSS . 'admin-v2.css', array(), aacb_asset_ver('admin-v2.css'));
    }

    public static function render() {
        if (!current_user_can('manage_options')) return;
        if (!get_option('aacb_wizard_completed') && !get_option('aacb_accountID')) {
            wp_redirect(admin_url('admin.php?page=allaccessible-wizard'));
            exit;
        }
        $status = isset($_GET['status']) ? sanitize_key($_GET['status']) : 'draft';
        if (!in_array($status, array('draft', 'approved', 'reverted'), true)) {
            $status = 'draft';
        }

        $client  = AllAccessible_ApiClient::get_instance();
        $summary = $client->get_manifest_summary(false, $status);
        $err     = is_wp_error($summary) ? $summary->get_error_message() : null;
        // Normalize to an array immediately: on API failure get_manifest_summary
        // returns a WP_Error, and the array subscripts below (e.g. line ~118
        // $summary['countsByStatus']) FATAL on "object of type WP_Error as array"
        // before any is_array() check on the subscript result can run. Mirror the
        // $agg guard. Empty array → page renders the empty/first-run state.
        if (!is_array($summary)) {
            $summary = array();
        }

        $tier    = (string) $client->get_subscription_tier();
        if ($tier === '') $tier = 'free';
        $isFree  = ($tier === 'free');
        $isBeta  = in_array($tier, self::BETA_TIERS, true);

        $site_options_obj = $client->get_site_options();
        $features_list    = array();
        if (is_object($site_options_obj) && isset($site_options_obj->features) && is_array($site_options_obj->features)) {
            $features_list = $site_options_obj->features;
        }
        $has_agentic   = in_array('agentic_fixes_approve', $features_list, true);
        $is_legacy_v1  = ($tier === 'legacy') || (!$has_agentic && !$isFree);

        AllAccessible_Debug::console('AgenticFixesPage', array(
            'status_tab'         => $status,
            'tier'               => $tier,
            'is_free'            => $isFree,
            'is_beta'            => $isBeta,
            'is_legacy_v1'       => $is_legacy_v1,
            'has_agentic_feature'=> $has_agentic,
            'features_list'      => $features_list,
            'summary_is_error'   => is_wp_error($summary),
            'summary_error'      => $err,
            'summary_keys'       => is_array($summary) ? array_keys($summary) : null,
            'summary'            => is_wp_error($summary) ? null : $summary,
        ));

        if ($is_legacy_v1) {
            self::render_legacy_upgrade_page();
            return;
        }

        $agg = $client->get_audit_aggregation();
        if (is_wp_error($agg) || !is_array($agg)) {
            $agg = null;
        }

        $hero_site_score        = null;
        $hero_remediation_score = null;
        $hero_pages_scanned     = null;
        $hero_is_live_estimate  = false;
        if ($agg && !empty($agg['hasData'])) {
            $hero_site_score        = isset($agg['overallScore'])     ? (float) $agg['overallScore']     : null;
            $hero_remediation_score = isset($agg['remediationScore']) ? (float) $agg['remediationScore'] : null;
            $hero_pages_scanned     = isset($agg['totalPagesScanned'])
                ? (int) $agg['totalPagesScanned']
                : (isset($agg['coverage']['totalPagesScanned']) ? (int) $agg['coverage']['totalPagesScanned'] : null);
            $hero_is_live_estimate  = !empty($agg['isLiveEstimate']);
        }
        $deeplink_token = $client->get_subscription_id() ?: '';
        $extras_hero = array(
            'site_score'        => $hero_site_score,
            'remediation_score' => $hero_remediation_score,
            'pages_scanned'     => $hero_pages_scanned,
            'is_live_estimate'  => $hero_is_live_estimate,
            'fixes_url'         => admin_url('admin.php?page=aacb-agentic-fixes'),
            'site_id_external'  => $deeplink_token,
        );

        $counts   = is_array($summary['countsByStatus'] ?? null) ? $summary['countsByStatus'] : array();
        $groups   = is_array($summary['groups']         ?? null) ? $summary['groups']         : array();
        $pending  = (int) ($counts['draft']    ?? 0);
        $approved = (int) ($counts['approved'] ?? 0);
        $reverted = (int) ($counts['reverted'] ?? 0);

        $show_first_run_hint = ($status === 'draft' && $approved === 0 && $reverted === 0);

        ?>
        <div class="wrap aacb-agentic-fixes"
             data-nonce="<?php echo esc_attr(wp_create_nonce(self::NONCE_NAME)); ?>"
             style="margin:-10px 0 0 -20px;">
          <div class="aacx-v2">
            <div class="aacx-v2__page">

              <!-- ── Page header ──────────────────────────────────────── -->
              <header class="aacx-v2__page-header">
                  <div>
                      <p class="aacx-v2__page-eyebrow">
                          <?php esc_html_e('AllAccessible AI', 'allaccessible'); ?>
                      </p>
                      <div class="aacx-v2__page-title">
                          <h1><?php esc_html_e('Agentic Fixes', 'allaccessible'); ?></h1>
                          <span class="aacx-v2__ai-badge">
                              <?php esc_html_e('AllAccessible AI', 'allaccessible'); ?>
                          </span>
                          <?php if ($isBeta) : ?>
                              <span class="aacx-v2__badge aacx-v2__badge--beta"
                                    aria-label="<?php esc_attr_e('Beta feature', 'allaccessible'); ?>">
                                  <?php esc_html_e('Beta', 'allaccessible'); ?>
                              </span>
                          <?php endif; ?>
                      </div>
                      <p class="aacx-v2__page-desc">
                          <?php esc_html_e('AllAccessible agents propose accessibility fixes. You review and approve before anything goes live on your site.', 'allaccessible'); ?>
                      </p>
                  </div>
                  <?php
                  $dashboard_url = $deeplink_token
                      ? 'https://app.allaccessible.org/site/' . rawurlencode($deeplink_token)
                      : 'https://app.allaccessible.org/';
                  ?>
                  <a href="<?php echo esc_url($dashboard_url); ?>" target="_blank" rel="noopener"
                     class="aacx-v2__btn aacx-v2__btn--secondary">
                      <?php esc_html_e('Open dashboard', 'allaccessible'); ?> →
                  </a>
              </header>

              <?php if ($err) : ?>
                  <div class="aacx-v2__banner aacx-v2__banner--danger" role="alert" style="margin-bottom:var(--aacx-space-6);">
                      <div>
                          <strong><?php esc_html_e('Could not reach AllAccessible right now.', 'allaccessible'); ?></strong>
                          <p style="margin-top:var(--aacx-space-1);font-size:var(--aacx-text-sm);">
                              <?php echo esc_html($err); ?>
                          </p>
                      </div>
                  </div>
              <?php endif; ?>

              <?php
              if (!$isFree) :
                  $ctx = array();
                  if ($hero_site_score !== null) {
                      $ctx[] = '<span class="aacb-ov-metrics__item"><b>'
                          . esc_html(number_format_i18n((float) $hero_site_score, 0)) . '</b> ' . esc_html__('score', 'allaccessible') . '</span>';
                  }
                  if ($hero_pages_scanned !== null && $hero_pages_scanned > 0) {
                      $ctx[] = '<span class="aacb-ov-metrics__item"><b>'
                          . esc_html(number_format_i18n($hero_pages_scanned)) . '</b> ' . esc_html__('pages audited', 'allaccessible') . '</span>';
                  }
                  $last_scan_at = is_array($summary) ? ($summary['lastScanAt'] ?? null) : null;
                  if ($last_scan_at) {
                      $ls_ts = strtotime($last_scan_at);
                      if ($ls_ts) {
                          $ctx[] = '<span class="aacb-ov-metrics__item">' . sprintf(
                              /* translators: %s: human-readable time difference (e.g. "2 hours") */
                              esc_html__('Last scan %s ago', 'allaccessible'),
                              esc_html(human_time_diff($ls_ts, current_time('timestamp')))
                          ) . '</span>';
                      }
                  }
                  if (!empty($ctx)) :
              ?>
                  <div class="aacb-ov-metrics" style="margin-bottom:var(--aacx-space-6);">
                      <?php echo implode(' <span class="aacb-ov-metrics__sep" aria-hidden="true">·</span> ', $ctx); // pre-escaped // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                  </div>
              <?php
                  endif;
              endif;
              ?>

              <?php
              $ai_quota = (is_object($site_options_obj) && isset($site_options_obj->aiQuota)) ? $site_options_obj->aiQuota : null;
              $is_preview = $isFree && $ai_quota !== null;
              ?>
              <?php if ($is_preview) : ?>
                  <?php self::render_preview_panel($groups, $ai_quota); ?>
              <?php elseif ($isFree) : ?>
                  <?php self::render_upgrade_panel(is_array($summary) ? $summary : array()); ?>
              <?php else : ?>
                  <?php self::render_tabs($status, $pending, $approved, $reverted); ?>

                  <div role="tabpanel"
                       id="aacb-panel-<?php echo esc_attr($status); ?>"
                       aria-labelledby="aacb-tab-<?php echo esc_attr($status); ?>"
                       tabindex="0">
                      <?php self::render_tab_panel($status, $groups, $show_first_run_hint); ?>
                  </div>
              <?php endif; ?>

            </div>
          </div>
        </div>

        <?php self::render_modals(); ?>
        <?php self::render_inline_js();
    }

    /* ─── Status tabs (ARIA tablist) ──────────────────────────────── */

    private static function render_tabs($active, $pending, $approved, $reverted) {
        $tabs = array(
            'draft' => array(
                'label' => __('Pending review',      'allaccessible'),
                'count' => $pending,
            ),
            'approved' => array(
                'label' => __('Approved & applied',  'allaccessible'),
                'count' => $approved,
            ),
            'reverted' => array(
                'label' => __('Reverted',            'allaccessible'),
                'count' => $reverted,
            ),
        );
        ?>
        <div class="aacx-v2__tabs" role="tablist" aria-label="<?php esc_attr_e('Filter Agentic Fixes by status', 'allaccessible'); ?>">
            <?php foreach ($tabs as $key => $tab) :
                $is_active = ($active === $key);
                $url = esc_url(add_query_arg(array('page' => self::PAGE_SLUG, 'status' => $key), admin_url('admin.php')));
            ?>
                <a href="<?php echo $url; ?>"
                   id="aacb-tab-<?php echo esc_attr($key); ?>"
                   role="tab"
                   aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                   aria-controls="aacb-panel-<?php echo esc_attr($key); ?>"
                   tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
                   class="aacx-v2__tab<?php echo $is_active ? ' aacx-v2__tab--active' : ''; ?>">
                    <?php echo esc_html($tab['label']); ?>
                    <span class="aacx-v2__badge aacx-v2__badge--primary">
                        <?php echo esc_html(number_format_i18n($tab['count'])); ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /* ─── Tab panel router ───────────────────────────────────────── */

    private static function render_tab_panel($status, $groups, $show_first_run_hint) {
        if (isset($_GET['aacb_preview_empty']) && $_GET['aacb_preview_empty'] === '1') {
            self::render_empty_state($status);
            return;
        }

        usort($groups, function($a, $b) {
            $pa = (int) ($a['pageCount'] ?? 0);
            $pb = (int) ($b['pageCount'] ?? 0);
            if ($pa === $pb) {
                return ((float) ($b['avgConfidence'] ?? 0)) <=> ((float) ($a['avgConfidence'] ?? 0));
            }
            return $pb <=> $pa;
        });

        if (empty($groups)) {
            self::render_empty_state($status);
            return;
        }

        if ($status === 'draft' && $show_first_run_hint) {
            self::render_first_run_hint();
        }

        if ($status === 'draft') {
            $by_type = array();
            foreach ($groups as $g) {
                $t = (string) ($g['type'] ?? 'other');
                if (!isset($by_type[$t])) $by_type[$t] = 0;
                $by_type[$t]++;
            }
            arsort($by_type);
            $top_type  = key($by_type);
            $top_count = (int) reset($by_type);
            if ($top_count >= 5) {
                self::render_bulk_approve_banner($top_type, $top_count);
            }
        }

        ?>
        <div class="aacx-v2__stack">
            <?php foreach ($groups as $g) self::render_fix_card($g, $status); ?>
        </div>
        <?php
    }

    /* ─── First-run hint banner ──────────────────────────────────── */

    private static function render_first_run_hint() {
        ?>
        <div class="aacx-v2__banner aacx-v2__banner--info" style="margin-bottom:var(--aacx-space-6);">
            <div>
                <strong><?php esc_html_e('How this works', 'allaccessible'); ?></strong>
                <p style="margin-top:var(--aacx-space-1);font-size:var(--aacx-text-sm);">
                    <?php esc_html_e('AllAccessible agents review your site and propose fixes for accessibility issues. Nothing changes on your site until you approve. You can edit any suggestion before approving, and you can revert any approved fix at any time.', 'allaccessible'); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /* ─── Bulk approve banner ────────────────────────────────────── */

    private static function render_bulk_approve_banner($type, $count) {
        $type_label = AllAccessible_AgenticFixes_Labels::fix_type_label($type);
        ?>
        <div class="aacx-v2__banner aacx-v2__banner--ai" style="margin-bottom:var(--aacx-space-6);justify-content:space-between;align-items:center;">
            <div>
                <span class="aacx-v2__ai-badge" style="margin-bottom:var(--aacx-space-2);">
                    <?php esc_html_e('AllAccessible agents', 'allaccessible'); ?>
                </span>
                <p style="font-size:var(--aacx-text-sm);font-weight:var(--aacx-weight-semibold);">
                    <?php
                    printf(
                        /* translators: 1: count of fixes; 2: rule type label (e.g., "Missing image description") */
                        esc_html__('%1$s pending fixes for "%2$s". Approve them all in one click.', 'allaccessible'),
                        esc_html(number_format_i18n($count)),
                        esc_html($type_label)
                    );
                    ?>
                </p>
            </div>
            <button type="button"
                    class="aacx-v2__btn aacx-v2__btn--ai aacb-approve-all-by-type"
                    data-type="<?php echo esc_attr($type); ?>"
                    data-site-id="<?php echo (int) get_option('aacb_siteID'); ?>"
                    aria-label="<?php echo esc_attr(sprintf(
                        /* translators: 1: count; 2: rule label */
                        __('Bulk approve %1$s %2$s fixes', 'allaccessible'),
                        number_format_i18n($count),
                        $type_label
                    )); ?>">
                <?php
                printf(
                    /* translators: %s: count of fixes */
                    esc_html__('Bulk approve all %s', 'allaccessible'),
                    esc_html(number_format_i18n($count))
                );
                ?>
            </button>
        </div>
        <?php
    }

    /* ─── Fix card (one per group) ───────────────────────────────── */
    private static function render_fix_card($g, $status) {
        $signature   = (string) ($g['signature']    ?? '');
        $type        = (string) ($g['type']         ?? 'other');
        $selector    = (string) ($g['selector']     ?? '');
        $rationale   = (string) ($g['rationaleKey'] ?? 'generic_fix');
        $value       = $g['payloadValue']           ?? null;
        $wcag        = (array)  ($g['wcag']         ?? array());
        $confidence  = (float)  ($g['avgConfidence'] ?? 0);
        $page_count  = (int)    ($g['pageCount']    ?? 0);
        $manifest_ids= (array)  ($g['manifestIds']  ?? array());
        $sample_urls = (array)  ($g['sampleUrls']   ?? array());
        $applied_at  = $g['appliedAt']              ?? null;

        $type_label = AllAccessible_AgenticFixes_Labels::fix_type_label($type);
        $rat_label  = AllAccessible_AgenticFixes_Labels::rationale_label($rationale);

        $confidence_pct = (int) round($confidence * 100);
        if ($confidence_pct >= 85) {
            $conf_class = 'aacx-v2__badge--ok';
        } elseif ($confidence_pct >= 70) {
            $conf_class = 'aacx-v2__badge--warn';
        } else {
            $conf_class = '';
        }
        ?>
        <article class="aacx-v2__card aacx-v2__card--hoverable aacb-queue-row"
                 data-signature="<?php echo esc_attr($signature); ?>"
                 data-rule-type="<?php echo esc_attr($type); ?>"
                 data-manifest-ids="<?php echo esc_attr(implode(',', array_map('intval', $manifest_ids))); ?>">

            <header class="aacx-v2__card-header" style="align-items:flex-start;gap:var(--aacx-space-3);flex-wrap:wrap;">
                <div style="display:flex;flex-direction:column;gap:var(--aacx-space-2);min-width:0;flex:1;">
                    <h3 style="margin:0;line-height:1.25;"><?php echo esc_html($type_label); ?></h3>
                    <div class="aacx-v2__row" style="flex-wrap:wrap;gap:var(--aacx-space-2);">
                        <?php foreach ($wcag as $c) : ?>
                            <a href="https://www.w3.org/WAI/WCAG21/quickref/#qr-<?php echo esc_attr(str_replace('.', '-', (string) $c)); ?>"
                               target="_blank" rel="noopener"
                               class="aacx-v2__badge"
                               style="font-size:var(--aacx-text-xs);color:var(--aacx-text-muted);text-decoration:none;white-space:nowrap;"
                               title="<?php echo esc_attr(AllAccessible_AgenticFixes_Labels::wcag_label((string) $c)); ?>">
                                <?php
                                printf(
                                    /* translators: %s: WCAG success criterion number (e.g., "1.1.1") */
                                    esc_html__('WCAG %s', 'allaccessible'),
                                    esc_html((string) $c)
                                );
                                ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="aacx-v2__row" style="flex-shrink:0;gap:var(--aacx-space-2);align-items:center;flex-wrap:wrap;justify-content:flex-end;">
                    <?php if ($confidence_pct > 0) : ?>
                        <span class="aacx-v2__badge <?php echo esc_attr($conf_class); ?>"
                              aria-label="<?php echo esc_attr(sprintf(
                                  /* translators: %s: confidence percentage 0-100 */
                                  __('confidence: %s percent', 'allaccessible'),
                                  number_format_i18n($confidence_pct)
                              )); ?>">
                            <?php
                            printf(
                                /* translators: %s: confidence percentage 0-100 */
                                esc_html__('%s%% confidence', 'allaccessible'),
                                esc_html(number_format_i18n($confidence_pct))
                            );
                            ?>
                        </span>
                    <?php endif; ?>
                    <span class="aacx-v2__ai-badge">
                        <?php esc_html_e('AllAccessible agents', 'allaccessible'); ?>
                    </span>
                </div>
            </header>

            <div class="aacx-v2__card-body">
                <div class="aacx-v2__stack">

                    <!-- What's wrong -->
                    <div>
                        <p class="aacx-v2__label" style="margin-bottom:var(--aacx-space-1);">
                            <?php esc_html_e("What's wrong", 'allaccessible'); ?>
                        </p>
                        <p style="font-size:var(--aacx-text-sm);color:var(--aacx-text);">
                            <?php
                            printf(
                                /* translators: 1: rationale phrase (e.g., "Suggested alt text for image"); 2: selector */
                                esc_html__('%1$s on element %2$s', 'allaccessible'),
                                esc_html($rat_label),
                                '<code style="font-family:var(--aacx-font-mono);background:var(--aacx-slate-100);padding:2px 6px;border-radius:var(--aacx-radius-sm);font-size:var(--aacx-text-xs);">' . esc_html($selector) . '</code>'
                            );
                            ?>
                        </p>
                    </div>

                    <!-- Proposed fix -->
                    <?php if ($value !== null) : ?>
                        <div>
                            <p class="aacx-v2__label" style="margin-bottom:var(--aacx-space-1);">
                                <?php esc_html_e('Proposed fix', 'allaccessible'); ?>
                            </p>
                            <?php if ($status === 'draft') : ?>
                                <textarea class="aacx-v2__textarea aacb-group-value"
                                          rows="2" maxlength="500"
                                          style="font-family:var(--aacx-font-mono);font-size:var(--aacx-text-sm);"
                                          aria-label="<?php esc_attr_e('Edit proposed fix value', 'allaccessible'); ?>"><?php echo esc_textarea($value); ?></textarea>
                            <?php else : ?>
                                <code style="display:block;font-family:var(--aacx-font-mono);background:var(--aacx-slate-100);padding:var(--aacx-space-3) var(--aacx-space-4);border-radius:var(--aacx-radius);font-size:var(--aacx-text-sm);color:var(--aacx-text-strong);word-break:break-word;">
                                    <?php echo esc_html($value); ?>
                                </code>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Page count + applied-on date for approved tab -->
                    <div class="aacx-v2__row" style="gap:var(--aacx-space-4);flex-wrap:wrap;">
                        <span style="font-size:var(--aacx-text-sm);color:var(--aacx-text-muted);">
                            <strong style="color:var(--aacx-text-strong);"><?php echo esc_html(number_format_i18n($page_count)); ?></strong>
                            <?php echo esc_html(_n('page affected', 'pages affected', $page_count, 'allaccessible')); ?>
                        </span>
                        <?php if ($status === 'approved' && $applied_at) :
                            $applied_ts = strtotime($applied_at);
                        ?>
                            <span style="font-size:var(--aacx-text-sm);color:var(--aacx-text-muted);">
                                <?php
                                printf(
                                    /* translators: %s: formatted date (e.g., "2026-05-23") */
                                    esc_html__('Applied on %s', 'allaccessible'),
                                    esc_html(date_i18n('Y-m-d', $applied_ts))
                                );
                                ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Where it appears (collapsible) -->
                    <?php if (!empty($sample_urls)) : ?>
                        <details class="aacb-where-appears">
                            <summary style="cursor:pointer;font-size:var(--aacx-text-sm);font-weight:var(--aacx-weight-semibold);color:var(--aacx-text-muted);display:inline-flex;align-items:center;gap:var(--aacx-space-2);user-select:none;list-style:none;">
                                <span aria-hidden="true" class="aacb-where-chev" style="display:inline-block;transition:transform var(--aacx-transition);">▸</span>
                                <?php
                                printf(
                                    /* translators: %s: count of pages where this fix appears */
                                    esc_html__('Where it appears (%s)', 'allaccessible'),
                                    esc_html(number_format_i18n($page_count))
                                );
                                ?>
                            </summary>
                            <ul style="margin-top:var(--aacx-space-3);display:flex;flex-wrap:wrap;gap:var(--aacx-space-2);list-style:none;padding:0;">
                                <?php foreach ($sample_urls as $u) :
                                    $short = parse_url($u, PHP_URL_PATH) ?: $u;
                                    $post_id = function_exists('url_to_postid') ? (int) url_to_postid($u) : 0;
                                ?>
                                    <li style="display:inline-flex;align-items:center;gap:var(--aacx-space-1);font-size:var(--aacx-text-xs);background:var(--aacx-slate-50);border:1px solid var(--aacx-border);border-radius:var(--aacx-radius-sm);padding:var(--aacx-space-1) var(--aacx-space-2);">
                                        <a href="<?php echo esc_url($u); ?>" target="_blank" rel="noopener">
                                            <?php echo esc_html($short); ?>
                                        </a>
                                        <?php if ($selector !== '') : ?>
                                            <span aria-hidden="true" style="color:var(--aacx-slate-300);">·</span>
                                            <a href="<?php echo esc_url(self::highlight_url($u, $selector)); ?>"
                                               target="_blank" rel="noopener"
                                               style="display:inline-flex;align-items:center;gap:2px;"
                                               title="<?php esc_attr_e('Open this page with the affected element scrolled into view and highlighted', 'allaccessible'); ?>">
                                                <?php esc_html_e('View on site', 'allaccessible'); ?>
                                                <span aria-hidden="true">↗</span>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($post_id > 0) : ?>
                                            <span aria-hidden="true" style="color:var(--aacx-slate-300);">·</span>
                                            <a href="<?php echo esc_url(get_edit_post_link($post_id)); ?>">
                                                <?php esc_html_e('Edit', 'allaccessible'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                                <?php if ($page_count > count($sample_urls)) : ?>
                                    <li style="font-size:var(--aacx-text-xs);color:var(--aacx-text-muted);padding:var(--aacx-space-1) var(--aacx-space-2);">
                                        <?php
                                        printf(
                                            /* translators: %s: count of additional pages not listed */
                                            esc_html__('+%s more', 'allaccessible'),
                                            esc_html(number_format_i18n($page_count - count($sample_urls)))
                                        );
                                        ?>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </details>
                    <?php endif; ?>

                </div>
            </div>

            <footer class="aacx-v2__card-footer">
                <?php if ($status === 'draft') : ?>
                    <div class="aacx-v2__row">
                        <button type="button" class="aacx-v2__btn aacx-v2__btn--ghost aacx-v2__btn--sm aacb-group-skip">
                            <?php esc_html_e('Skip', 'allaccessible'); ?>
                        </button>
                    </div>
                    <div class="aacx-v2__row">
                        <?php if ($value !== null) : ?>
                            <button type="button" class="aacx-v2__btn aacx-v2__btn--secondary aacx-v2__btn--sm aacb-group-save-edit"
                                    aria-label="<?php echo esc_attr(sprintf(
                                        /* translators: 1: rule label; 2: page count */
                                        __('Save edit and apply to %2$s pages for %1$s', 'allaccessible'),
                                        $type_label,
                                        number_format_i18n($page_count)
                                    )); ?>">
                                <?php esc_html_e('Save edit', 'allaccessible'); ?>
                            </button>
                        <?php endif; ?>
                        <button type="button"
                                class="aacx-v2__btn aacx-v2__btn--primary aacb-group-approve-all"
                                aria-label="<?php echo esc_attr(sprintf(
                                    /* translators: 1: rule label; 2: page count */
                                    __('Approve %1$s on %2$s pages', 'allaccessible'),
                                    $type_label,
                                    number_format_i18n($page_count)
                                )); ?>">
                            <?php
                            printf(
                                /* translators: %s: count of pages */
                                esc_html(_n('Approve on %s page', 'Approve on %s pages', $page_count, 'allaccessible')),
                                esc_html(number_format_i18n($page_count))
                            );
                            ?>
                        </button>
                    </div>
                <?php elseif ($status === 'approved') : ?>
                    <span style="font-size:var(--aacx-text-xs);color:var(--aacx-text-muted);">
                        <?php esc_html_e('Live on your site', 'allaccessible'); ?>
                    </span>
                    <button type="button" class="aacx-v2__btn aacx-v2__btn--ghost aacx-v2__btn--sm aacb-group-revert-all"
                            aria-label="<?php echo esc_attr(sprintf(
                                /* translators: 1: rule label; 2: page count */
                                __('Revert %1$s on %2$s pages', 'allaccessible'),
                                $type_label,
                                number_format_i18n($page_count)
                            )); ?>">
                        <?php esc_html_e('Revert', 'allaccessible'); ?>
                    </button>
                <?php else : /* reverted */ ?>
                    <span style="font-size:var(--aacx-text-xs);color:var(--aacx-text-muted);">
                        <?php esc_html_e('Not applied', 'allaccessible'); ?>
                    </span>
                <?php endif; ?>
            </footer>
        </article>
        <?php
    }

    /**
     * Append `?aa-highlight=<base64url(selector)>` to a page URL
     */
    private static function highlight_url($url, $selector) {
        $url      = (string) $url;
        $selector = (string) $selector;
        if ($selector === '') {
            return $url;
        }
        $encoded = rtrim(strtr(base64_encode($selector), '+/', '-_'), '=');
        $glue    = (strpos($url, '?') !== false) ? '&' : '?';
        return $url . $glue . 'aa-highlight=' . $encoded;
    }

    /* ─── Legacy V1 upgrade page ──────────────────────────────────── */

    /**
     * Full-page upgrade prompt shown to legacy V1 widget customers in place
     * of the Agentic Fixes UI.
     */
    private static function render_legacy_upgrade_page() {
        $upgrade_url = class_exists('AllAccessible_ApiClient')
            ? AllAccessible_ApiClient::get_instance()->get_migration_url()
            : 'https://app.allaccessible.org/billing';
        ?>
        <div class="wrap aacb-agentic-fixes" style="margin:-10px 0 0 -20px;">
          <div class="aacx-v2">
            <div class="aacx-v2__page">

              <header class="aacx-v2__page-header">
                <div>
                  <p class="aacx-v2__page-eyebrow"><?php esc_html_e('AllAccessible AI', 'allaccessible'); ?></p>
                  <div class="aacx-v2__page-title">
                    <h1><?php esc_html_e('Agentic Fixes', 'allaccessible'); ?></h1>
                    <span class="aacx-v2__badge aacx-v2__badge--primary"><?php esc_html_e('Legacy widget', 'allaccessible'); ?></span>
                  </div>
                  <p class="aacx-v2__page-desc">
                    <?php esc_html_e('Agentic AI remediation is not available on your current plan.', 'allaccessible'); ?>
                  </p>
                </div>
              </header>

              <div class="aacx-v2__card aacx-v2__card--ai aacx-v2__card--elevated">
                <div class="aacx-v2__card-header">
                  <div>
                    <span class="aacx-v2__ai-badge" style="margin-bottom: var(--aacx-space-2); display: inline-flex;">
                      <?php esc_html_e('AllAccessible AI', 'allaccessible'); ?>
                    </span>
                    <h3 style="margin-top: var(--aacx-space-1);"><?php esc_html_e('Upgrade to unlock Agentic Fixes', 'allaccessible'); ?></h3>
                  </div>
                </div>
                <div class="aacx-v2__card-body">
                  <p style="color: var(--aacx-text); margin-bottom: var(--aacx-space-4);">
                    <?php esc_html_e('Your site runs the legacy AllAccessible widget, which predates agentic AI remediation. The widget keeps working as-is, but upgrading to the current plan tier unlocks:', 'allaccessible'); ?>
                  </p>
                  <ul style="margin: 0 0 var(--aacx-space-5); padding-left: var(--aacx-space-5); display: grid; gap: var(--aacx-space-2); font-size: var(--aacx-text-sm); color: var(--aacx-text);">
                    <li><?php esc_html_e('Human-in-the-loop agentic AI remediation — agents propose fixes, your team approves.', 'allaccessible'); ?></li>
                    <li><?php esc_html_e('Continuous accessibility scoring with site-level coverage tracking.', 'allaccessible'); ?></li>
                    <li><?php esc_html_e('Higher monthly pageview caps and AllAccessible AI alt text generation.', 'allaccessible'); ?></li>
                    <li><?php esc_html_e('Image AI manager, per-page audit scores in the WordPress editor, and downloadable WCAG / VPAT reports.', 'allaccessible'); ?></li>
                  </ul>
                  <a href="<?php echo esc_url($upgrade_url); ?>" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--primary aacx-v2__btn--lg">
                    <?php esc_html_e('See upgrade options', 'allaccessible'); ?>
                    <span aria-hidden="true">→</span>
                  </a>
                </div>
              </div>

            </div>
          </div>
        </div>
        <?php
    }

    /* ─── Empty state ─────────────────────────────────────────────── */

    private static function render_empty_state($status) {
        if ($status === 'draft') {
            // First-impression empty state.
            if (class_exists('AllAccessible_ScanTriggerPanel')) {
                AllAccessible_ScanTriggerPanel::render(array(
                    'context'          => 'fixes',
                    'heading'          => __("You're all caught up", 'allaccessible'),
                    'description'      => __('No fixes are waiting for review. Run a scan and AllAccessible agents will propose any new fixes they find.', 'allaccessible'),
                    'success_redirect' => admin_url('admin.php?page=aacb-agentic-fixes&status=draft'),
                ));

                AllAccessible_ScanTriggerPanel::enqueue_inline_script();
            } else {
                // Defensive fallback (panel class should always be loaded).
                ?>
                <div class="aacx-v2__card">
                    <div class="aacx-v2__empty">
                        <p class="aacx-v2__empty-title"><?php esc_html_e("You're all caught up", 'allaccessible'); ?></p>
                        <p><?php esc_html_e('No fixes are waiting for review right now.', 'allaccessible'); ?></p>
                    </div>
                </div>
                <?php
            }
        } elseif ($status === 'approved') {
            ?>
            <div class="aacx-v2__card">
                <div class="aacx-v2__empty">
                    <p class="aacx-v2__empty-title">
                        <?php esc_html_e('No fixes approved yet', 'allaccessible'); ?>
                    </p>
                    <p>
                        <?php esc_html_e('Approved fixes will appear here once you start reviewing the pending queue.', 'allaccessible'); ?>
                    </p>
                </div>
            </div>
            <?php
        } else { /* reverted */
            ?>
            <div class="aacx-v2__card">
                <div class="aacx-v2__empty">
                    <p class="aacx-v2__empty-title">
                        <?php esc_html_e('Nothing has been reverted', 'allaccessible'); ?>
                    </p>
                    <p>
                        <?php esc_html_e('Any approved fix you revert will be tracked here for reference.', 'allaccessible'); ?>
                    </p>
                </div>
            </div>
            <?php
        }
    }

    /* ─── Preview panel (free tier — 5 real + blurred remainder) ───── */

    private static function render_preview_panel($groups, $ai_quota) {
        $max = (int) ($ai_quota->manifestMax ?? 5);
        $used = (int) ($ai_quota->manifestUsed ?? 0);
        ?>
        <div class="aacb-ai-preview-banner aacx-v2__card" style="margin-bottom: var(--aacx-space-6); padding: var(--aacx-space-5); background: linear-gradient(135deg, rgba(139,92,246,0.04), rgba(99,102,241,0.04)); border: 1px solid var(--aacx-slate-200);">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: var(--aacx-space-4); flex-wrap: wrap;">
                <div>
                    <div class="aacx-v2__page-eyebrow" style="color: #6d28d9;">
                        <span aria-hidden="true">✦</span> <?php esc_html_e('AllAccessible AI · Free preview', 'allaccessible'); ?>
                    </div>
                    <h2 style="margin: 4px 0 6px; font-size: 1.1rem;">
                        <?php printf(
                            esc_html__('You\'ve previewed %1$d of %2$d agentic fix proposals', 'allaccessible'),
                            $used, $max
                        ); ?>
                    </h2>
                    <p style="margin: 0; font-size: var(--aacx-text-sm); color: var(--aacx-text-muted); max-width: 60ch;">
                        <?php esc_html_e('Real proposals drafted for the real issues found on your site. Upgrade to review, approve, and apply the rest — human-in-the-loop, every fix.', 'allaccessible'); ?>
                    </p>
                </div>
                <a href="https://app.allaccessible.org/billing" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--primary">
                    <?php esc_html_e('Unlock unlimited', 'allaccessible'); ?>
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>

        <?php
        if (empty($groups)) {
            self::render_upgrade_panel(array());
            return;
        }
        $idx = 0;
        ?>
        <div class="aacb-agentic-groups" role="list" aria-label="<?php esc_attr_e('Fix proposal preview', 'allaccessible'); ?>">
            <?php foreach ($groups as $group) :
                $is_locked = $idx >= $max;
                if ($is_locked) {
                    echo '<div class="aacb-locked-card" aria-hidden="true">';
                }
                if (method_exists(__CLASS__, 'render_group_card')) {
                    self::render_group_card($group);
                } else {
                    $rule  = is_array($group) ? ($group['ruleKey'] ?? ($group['rule'] ?? '')) : '';
                    $sev   = is_array($group) ? ($group['severity'] ?? '') : '';
                    $pages = is_array($group) ? (int) ($group['affectedPagesCount'] ?? ($group['pageCount'] ?? 0)) : 0;
                    ?>
                    <div class="aacx-v2__card" style="margin-bottom: var(--aacx-space-3); padding: var(--aacx-space-4);">
                        <div style="display: flex; gap: var(--aacx-space-3); align-items: center;">
                            <span class="aacx-v2__badge aacx-v2__badge--<?php echo esc_attr($sev); ?>"><?php echo esc_html(ucfirst($sev)); ?></span>
                            <strong style="font-size: var(--aacx-text-sm);"><?php echo esc_html($rule); ?></strong>
                            <span style="font-size: var(--aacx-text-xs); color: var(--aacx-text-muted); margin-left: auto;">
                                <?php printf(esc_html(_n('%d page', '%d pages', $pages, 'allaccessible')), $pages); ?>
                            </span>
                        </div>
                    </div>
                    <?php
                }
                if ($is_locked) {
                    echo '</div>';
                }
                $idx++;
            endforeach; ?>
        </div>
        <?php if ($idx > $max) : ?>
            <div class="aacb-locked-cta" style="margin-top: var(--aacx-space-5); text-align: center; padding: var(--aacx-space-6); background: var(--aacx-bg-secondary, #f8fafc); border-radius: var(--aacx-radius); border: 1px dashed var(--aacx-slate-300);">
                <p style="margin: 0 0 var(--aacx-space-3); font-weight: var(--aacx-weight-semibold);">
                    <span aria-hidden="true">✦</span>
                    <?php printf(
                        esc_html__('Unlock %d more agentic fix proposals for your site', 'allaccessible'),
                        (int) ($idx - $max)
                    ); ?>
                </p>
                <a href="https://app.allaccessible.org/billing" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--primary">
                    <?php esc_html_e('Upgrade — starts at $10/month', 'allaccessible'); ?>
                </a>
            </div>
        <?php endif; ?>
        <?php
    }

    /* ─── Upgrade panel (free tier — no preview state) ─────────────── */

    private static function render_upgrade_panel($summary) {
        $cta      = is_array($summary['upgradeCta'] ?? null) ? $summary['upgradeCta'] : array();
        $headline = AllAccessible_AgenticFixes_Labels::cta_label($cta['headlineKey'] ?? 'upgrade_headline_agent_fixes');
        $subhead  = AllAccessible_AgenticFixes_Labels::cta_label($cta['subheadKey']  ?? 'upgrade_subhead_human_in_loop');
        $url      = isset($cta['url']) ? (string) $cta['url'] : 'https://app.allaccessible.org/billing';
        ?>
        <div class="aacx-v2__card aacx-v2__card--ai" style="margin-top:var(--aacx-space-6);">
            <div class="aacx-v2__card-body">
                <div class="aacx-v2__stack">
                    <span class="aacx-v2__ai-badge">
                        <?php esc_html_e('AllAccessible AI', 'allaccessible'); ?>
                    </span>
                    <h2><?php echo esc_html($headline); ?></h2>
                    <p style="color:var(--aacx-text);max-width:60ch;">
                        <?php echo esc_html($subhead); ?>
                    </p>
                    <div>
                        <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener"
                           class="aacx-v2__btn aacx-v2__btn--ai">
                            <?php esc_html_e('Upgrade plan', 'allaccessible'); ?>
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /* ─── Modals (kept for legacy AJAX wiring) ─────────────────────── */

    private static function render_modals() {
        ?>
        <div id="aacb-edit-modal" class="aacb-modal" style="display:none;">
            <div class="aacb-modal-content">
                <h2><?php esc_html_e('Edit fix', 'allaccessible'); ?></h2>
                <p style="color:var(--aacx-slate-600);font-size:14px;">
                    <?php esc_html_e('Update the suggested value before approving. Max 500 characters.', 'allaccessible'); ?>
                </p>
                <textarea id="aacb-edit-value" rows="4" maxlength="500" style="width:100%;margin-top:8px;"></textarea>
                <p class="aacb-modal-actions">
                    <button class="button button-primary" id="aacb-edit-save"><?php esc_html_e('Save', 'allaccessible'); ?></button>
                    <button class="button aacb-modal-cancel"><?php esc_html_e('Cancel', 'allaccessible'); ?></button>
                </p>
            </div>
        </div>

        <div id="aacb-revert-modal" class="aacb-modal" style="display:none;">
            <div class="aacb-modal-content">
                <h2><?php esc_html_e('Revert manifest', 'allaccessible'); ?></h2>
                <p style="color:var(--aacx-slate-600);font-size:14px;">
                    <?php esc_html_e('Why are you reverting? This helps us improve fix quality. Max 500 characters.', 'allaccessible'); ?>
                </p>
                <textarea id="aacb-revert-reason" rows="3" maxlength="500" style="width:100%;margin-top:8px;"></textarea>
                <p class="aacb-modal-actions">
                    <button class="button button-primary" id="aacb-revert-confirm"><?php esc_html_e('Revert', 'allaccessible'); ?></button>
                    <button class="button aacb-modal-cancel"><?php esc_html_e('Cancel', 'allaccessible'); ?></button>
                </p>
            </div>
        </div>

        <style>
        .aacb-modal { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); z-index: 100000; display: flex; align-items: center; justify-content: center; }
        .aacb-modal-content { background: white; padding: 24px; border-radius: 10px; max-width: 500px; width: 90%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .aacb-modal-actions { margin-top: 16px; display: flex; gap: 8px; }
        /* "Where it appears" chevron — rotates 90deg when open. Matches
           the dev-tools collapsible pattern used on Account → Advanced. */
        .aacb-where-appears summary::-webkit-details-marker { display: none; }
        .aacb-where-appears summary:hover { color: var(--aacx-text-strong); }
        .aacb-where-appears summary:focus-visible {
            outline: 2px solid var(--aacx-primary-500);
            outline-offset: 2px;
            border-radius: var(--aacx-radius-sm);
        }
        .aacb-where-appears[open] .aacb-where-chev { transform: rotate(90deg); }
        @media (prefers-reduced-motion: reduce) {
            .aacb-where-appears .aacb-where-chev { transition: none; }
        }
        </style>
        <?php
    }

    /* ─── Inline JS — AJAX wiring (endpoints + nonce unchanged) ────── */

    private static function render_inline_js() {
        // Token used to deep-link to the manifest review page for this site.
        $deeplink_site_token = '';
        if (class_exists('AllAccessible_ApiClient')) {
            $opts = AllAccessible_ApiClient::get_instance()->get_site_options();
            if (!is_wp_error($opts) && is_object($opts) && !empty($opts->siteID)) {
                $deeplink_site_token = (string) $opts->siteID;
            }
        }
        ?>
        <script>
        (function($) {
            const ajaxUrl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
            const nonce   = $('.aacb-agentic-fixes').data('nonce');
            const siteId            = <?php echo (int) get_option('aacb_siteID'); ?>;
            const accountId         = '<?php echo esc_js((string) get_option('aacb_accountID')); ?>';
            const deeplinkSiteToken = '<?php echo esc_js($deeplink_site_token); ?>';
            let activeManifestId = null;
            let activeFixIndex   = null;

            function reload()        { window.location.reload(); }
            function showErr(msg)    { aacbToast(msg || '<?php echo esc_js(__('Action failed', 'allaccessible')); ?>', 'error', 5000); }
            function groupIdsOf($row){
                const raw = ($row.data('manifest-ids') || '').toString();
                return raw.split(',').map(function(s) { return parseInt(s, 10); }).filter(function(n) { return n > 0; });
            }

            function aacbToast(message, kind, holdMs) {
                kind   = kind   || 'info';
                holdMs = holdMs || 3500;
                const bg = kind === 'success' ? '#0f9d58'
                         : kind === 'error'   ? '#d93025'
                         : kind === 'warn'    ? '#f4b400'
                         : '#7c3aed';
                const t = document.createElement('div');
                t.textContent = message;
                t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:100000;padding:12px 20px;border-radius:6px;font:14px/1.4 system-ui,sans-serif;color:#fff;box-shadow:0 6px 24px rgba(0,0,0,.18);transition:opacity .4s;max-width:80vw;background:'+bg;
                document.body.appendChild(t);
                setTimeout(function(){ t.style.opacity = '0'; }, holdMs);
                setTimeout(function(){ if (t.parentNode) t.parentNode.removeChild(t); }, holdMs + 600);
            }

            // Summarize the bulk-approve server response into a toast + reload decision.
            function aacbHandleBulkResponse(resp) {
                const data = (resp && resp.data) ? resp.data : null;
                if (!data) {
                    showErr('<?php echo esc_js(__('Server returned no data', 'allaccessible')); ?>');
                    return;
                }
                const approved  = parseInt(data.approvedCount || 0, 10);
                const skipped   = Array.isArray(data.skipped) ? data.skipped : [];
                const truncated = !!data.truncated;

                // Aggregate skip reasons for the toast.
                const reasonCounts = {};
                skipped.forEach(function(s){
                    const r = (s && s.reason) ? s.reason : 'unknown';
                    reasonCounts[r] = (reasonCounts[r] || 0) + 1;
                });
                const reasonSummary = Object.keys(reasonCounts).map(function(r){
                    return reasonCounts[r] + ' ' + r.replace(/_/g, ' ');
                }).join(', ');

                let kind = 'success';
                let msg;
                if (approved > 0 && skipped.length === 0) {
                    msg = approved + ' approved.';
                } else if (approved > 0 && skipped.length > 0) {
                    kind = 'warn';
                    msg  = approved + ' approved · ' + skipped.length + ' skipped (' + reasonSummary + ').';
                } else if (approved === 0 && skipped.length > 0) {
                    kind = 'error';
                    msg  = 'Nothing approved. ' + skipped.length + ' skipped (' + reasonSummary + ').';
                } else {
                    msg = 'No changes.';
                }
                if (truncated) {
                    msg += ' Note: ' + (data.originalCount || '?') + '→' + (data.processedCount || '?') + ' (server cap).';
                    if (kind === 'success') kind = 'warn';
                }

                aacbToast(msg, kind, kind === 'success' ? 2500 : 6000);
                if (approved > 0) {
                    setTimeout(reload, kind === 'success' ? 800 : 1600);
                }
            }

            /* ─── Per-card approve / revert / save-edit / skip ─── */
            $(document).on('click', '.aacb-group-approve-all', function(e) {
                e.preventDefault();
                const $row = $(this).closest('.aacb-queue-row');
                const ids  = groupIdsOf($row);
                if (ids.length === 0) return;
                if (!confirm('<?php echo esc_js(__('Approve this fix on every page where it appears?', 'allaccessible')); ?>')) return;
                $(this).prop('disabled', true).text('…');
                $.post(ajaxUrl, { action: 'aacb_bulk_approve_manifests', _wpnonce: nonce, site_id: siteId, manifest_ids: ids })
                  .done(reload).fail(showErr);
            });

            $(document).on('click', '.aacb-group-revert-all', function(e) {
                e.preventDefault();
                const $row = $(this).closest('.aacb-queue-row');
                const ids  = groupIdsOf($row);
                const reason = prompt('<?php echo esc_js(__('Revert reason (max 500 chars):', 'allaccessible')); ?>', '');
                if (reason === null) return;
                $(this).prop('disabled', true).text('…');
                Promise.all(ids.map(function(id) {
                    return $.post(ajaxUrl, { action: 'aacb_revert_manifest', _wpnonce: nonce, manifest_id: id, reason: reason });
                })).then(reload, showErr);
            });

            $(document).on('click', '.aacb-group-save-edit', function() {
                const $row  = $(this).closest('.aacb-queue-row');
                const ids   = groupIdsOf($row);
                const value = $row.find('.aacb-group-value').val().trim();
                if (!value) { showErr('<?php echo esc_js(__('Value cannot be empty', 'allaccessible')); ?>'); return; }
                if (!confirm('<?php echo esc_js(__('Apply this value to every page in the group?', 'allaccessible')); ?>')) return;
                $(this).prop('disabled', true).text('…');
                // fix_index=0 because grouped fixes share one canonical entry per manifest.
                Promise.all(ids.map(function(id) {
                    return $.post(ajaxUrl, { action: 'aacb_edit_fix', _wpnonce: nonce, manifest_id: id, fix_index: 0, value: value });
                })).then(reload, showErr);
            });

            $(document).on('click', '.aacb-group-skip', function() {
                $(this).closest('.aacb-queue-row').fadeOut(150);
            });

            /* ─── Bulk approve by rule type ─── */
            $(document).on('click', '.aacb-approve-all-by-type', function() {
                const $btn = $(this);
                if ($btn.hasClass('is-busy')) return;

                const type = String($btn.data('type'));
                const ids = [];
                $('.aacb-queue-row[data-rule-type="' + type + '"]').each(function() {
                    groupIdsOf($(this)).forEach(function(id) { ids.push(id); });
                });
                if (ids.length === 0) { showErr('<?php echo esc_js(__('Nothing to approve.', 'allaccessible')); ?>'); return; }
                if (!confirm('<?php echo esc_js(__('Approve every pending fix of this type?', 'allaccessible')); ?>')) return;

                const originalText = $btn.text();
                $btn.addClass('is-busy').prop('disabled', true).text('…');

                $.post(ajaxUrl, {
                        action: 'aacb_bulk_approve_manifests',
                        _wpnonce: nonce,
                        site_id: siteId,
                        manifest_ids: ids
                    })
                    .done(function(resp){
                        $btn.removeClass('is-busy').prop('disabled', false).text(originalText);
                        aacbHandleBulkResponse(resp);
                    })
                    .fail(function(xhr){
                        $btn.removeClass('is-busy').prop('disabled', false).text(originalText);
                        let msg = '<?php echo esc_js(__('Bulk approve failed', 'allaccessible')); ?>';
                        try {
                            const j = xhr.responseJSON;
                            if (j && j.data && j.data.message) msg = j.data.message;
                            else if (j && j.data) msg = (typeof j.data === 'string') ? j.data : JSON.stringify(j.data);
                        } catch (e) { /* keep default msg */ }
                        showErr(msg);
                    });
            });

            /* ─── Status-tab keyboard navigation (ARIA tablist) ─── */
            $(document).on('keydown', '.aacx-v2__tabs [role="tab"]', function(e) {
                if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
                e.preventDefault();
                const $tabs = $('.aacx-v2__tabs [role="tab"]');
                const i = $tabs.index(this);
                const next = e.key === 'ArrowRight'
                    ? (i + 1) % $tabs.length
                    : (i - 1 + $tabs.length) % $tabs.length;
                $tabs.eq(next).focus()[0].click();
            });

            /* ─── Legacy modal wiring — kept for other surfaces ─── */
            $(document).on('click', '.aacb-approve', function() {
                const id = $(this).data('id');
                if (!confirm('<?php echo esc_js(__('Approve this manifest? Fixes will go live immediately.', 'allaccessible')); ?>')) return;
                $(this).prop('disabled', true).text('…');
                $.post(ajaxUrl, { action: 'aacb_approve_manifest', _wpnonce: nonce, manifest_id: id })
                  .done(reload).fail(showErr);
            });
            $(document).on('click', '.aacb-revert', function() {
                activeManifestId = $(this).data('id');
                $('#aacb-revert-reason').val('');
                $('#aacb-revert-modal').show();
            });
            $('#aacb-revert-confirm').on('click', function() {
                const reason = $('#aacb-revert-reason').val().trim();
                $(this).prop('disabled', true).text('…');
                $.post(ajaxUrl, { action: 'aacb_revert_manifest', _wpnonce: nonce, manifest_id: activeManifestId, reason: reason })
                  .done(reload).fail(showErr);
            });
            $(document).on('click', '.aacb-edit', function() {
                activeManifestId = $(this).data('id');
                activeFixIndex   = parseInt($(this).data('fix-index'), 10) || 0;
                $('#aacb-edit-value').val('');
                $('#aacb-edit-modal').show();
            });
            $('#aacb-edit-save').on('click', function() {
                const value = $('#aacb-edit-value').val().trim();
                if (!value) { showErr('<?php echo esc_js(__('Value cannot be empty', 'allaccessible')); ?>'); return; }
                $(this).prop('disabled', true).text('…');
                $.post(ajaxUrl, { action: 'aacb_edit_fix', _wpnonce: nonce, manifest_id: activeManifestId, fix_index: activeFixIndex, value: value })
                  .done(reload).fail(showErr);
            });
            $(document).on('click', '.aacb-modal-cancel', function() {
                $(this).closest('.aacb-modal').hide();
            });
        })(jQuery);
        </script>
        <?php
    }
}

AllAccessible_AgenticFixes_Page::register();
