<?php
/**
 * Reusable "Run your first scan" panel.
 *
 * @package AllAccessible
 * @since   2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AllAccessible_ScanTriggerPanel {

    /**
     * Print the panel markup. Caller decides the context (wizard / settings /
     * fixes-page) which influences copy + redirect-after-success behavior.
     *
     * @param array $opts {
     *   @type string $context           'wizard' | 'settings' | 'fixes' — for analytics + copy
     *   @type string $heading           Override panel heading
     *   @type string $description       Override description line
     *   @type string $success_redirect  URL to send the user after scan finishes (empty = stay)
     *   @type bool   $compact           Tight layout for sidebar / wizard contexts
     * }
     */
    public static function render(array $opts = array()) {
        $context           = isset($opts['context'])          ? sanitize_key($opts['context'])             : 'settings';
        $heading           = isset($opts['heading'])          ? (string) $opts['heading']                  : __('Run your first scan', 'allaccessible');
        $description       = isset($opts['description'])      ? (string) $opts['description']              : __('AllAccessible automatically finds every published page on your site — no sitemap needed. Start your first scan below; future scans then run automatically based on your plan.', 'allaccessible');
        $success_redirect  = isset($opts['success_redirect']) ? esc_url((string) $opts['success_redirect']) : '';
        $compact           = !empty($opts['compact']);
        $nonce      = wp_create_nonce(AACB_SCAN_NONCE);

        $client     = class_exists('AllAccessible_ApiClient') ? AllAccessible_ApiClient::get_instance() : null;
        $last_meta  = $client ? $client->get_last_scan_meta() : array('triggeredAt' => null, 'sitemapUrl' => '');
        $schedule   = $client ? $client->get_scan_schedule() : null;
        $sched_ok   = is_array($schedule);
        $already_scanned = ($sched_ok && !empty($schedule['lastScanAt'])) || !empty($last_meta['triggeredAt']);
        if ($context === 'wizard') {
            $already_scanned = false;
        }
        $btn_label_first   = __('Scan now', 'allaccessible');
        $btn_label_again   = __('Run scan again', 'allaccessible');
        $primary_button    = $already_scanned ? $btn_label_again : $btn_label_first;
        $page_count = 0;
        if (function_exists('wp_count_posts')) {
            $page_counts = wp_count_posts('page');
            $post_counts = wp_count_posts('post');
            $page_count  = (int) ($page_counts->publish ?? 0) + (int) ($post_counts->publish ?? 0);
        }

        $body_padding = $compact ? 'var(--aacx-space-4)' : 'var(--aacx-space-6)';
        ?>
        <div class="aacb-scan-trigger aacx-v2 allaccessible-admin"
             data-context="<?php echo esc_attr($context); ?>"
             data-nonce="<?php echo esc_attr($nonce); ?>"
             data-success-redirect="<?php echo esc_attr($success_redirect); ?>">

          <div class="aacx-v2__card aacx-v2__card--elevated">
            <div class="aacx-v2__card-body" style="padding: <?php echo esc_attr($body_padding); ?>;">

              <?php if ($heading !== '') : ?>
                  <h3 style="margin: 0 0 var(--aacx-space-2);"><?php echo esc_html($heading); ?></h3>
              <?php endif; ?>
              <p style="font-size: var(--aacx-text-sm); color: var(--aacx-text-muted); margin-bottom: var(--aacx-space-4);">
                <?php echo esc_html($description); ?>
              </p>

              <?php if ($sched_ok) :
                  $freq       = (string) ($schedule['scanFrequency']      ?? '');
                  $freq_label = (string) ($schedule['scanFrequencyLabel'] ?? '');
                  $last_at    = $schedule['lastScanAt'] ?? null;
                  $next_at    = $schedule['nextScanAt'] ?? null;
                  $is_free    = ($freq === 'never');
                  $banner_variant = $is_free ? 'warn' : 'ok';
              ?>
                  <div class="aacx-v2__banner aacx-v2__banner--<?php echo esc_attr($banner_variant); ?>" style="margin-bottom: var(--aacx-space-4);">
                      <div>
                          <strong style="font-size: var(--aacx-text-sm);"><?php echo esc_html($freq_label); ?></strong>
                          <p style="font-size: var(--aacx-text-xs); margin-top: var(--aacx-space-1);">
                              <?php if ($last_at) : ?>
                                  <?php
                                  printf(
                                      /* translators: %s: human-readable time diff */
                                      esc_html__('Last scan %s ago', 'allaccessible'),
                                      esc_html(human_time_diff(strtotime($last_at), current_time('timestamp')))
                                  );
                                  ?>
                              <?php else : ?>
                                  <?php esc_html_e('No scans yet', 'allaccessible'); ?>
                              <?php endif; ?>
                              <?php if ($next_at && !$is_free) : ?>
                                  <span style="margin: 0 var(--aacx-space-2);">·</span>
                                  <?php
                                  $next_ts = strtotime($next_at);
                                  $now_ts  = current_time('timestamp');
                                  if ($next_ts > $now_ts) {
                                      printf(
                                          /* translators: %s: human-readable time until */
                                          esc_html__('Next scan in %s', 'allaccessible'),
                                          esc_html(human_time_diff($now_ts, $next_ts))
                                      );
                                  } else {
                                      esc_html_e('Next scan due now', 'allaccessible');
                                  }
                                  ?>
                              <?php endif; ?>
                          </p>
                      </div>
                  </div>
              <?php elseif (!empty($last_meta['triggeredAt'])) : ?>
                  <p class="aacx-v2__help aacb-scan-last-triggered" style="margin-bottom: var(--aacx-space-4);">
                      <?php
                      printf(
                          /* translators: %s: human-readable time difference */
                          esc_html__('Last scan triggered %s ago', 'allaccessible'),
                          esc_html(human_time_diff((int) $last_meta['triggeredAt'], current_time('timestamp')))
                      );
                      ?>
                  </p>
              <?php endif; ?>

              <?php if ($page_count > 0) : ?>
                  <p style="font-size: var(--aacx-text-sm); color: var(--aacx-text-muted); margin: 0 0 var(--aacx-space-3); display: flex; align-items: center; gap: var(--aacx-space-2);">
                      <span class="dashicons dashicons-admin-page" aria-hidden="true" style="color: var(--aacx-primary-600);"></span>
                      <?php
                      printf(
                          /* translators: %d: number of published posts + pages */
                          esc_html(_n(
                              '%d published page will be scanned. This can take a minute or two.',
                              '%d published pages and posts will be scanned. This can take a minute or two.',
                              $page_count,
                              'allaccessible'
                          )),
                          (int) $page_count
                      );
                      ?>
                  </p>
              <?php endif; ?>
              <div style="display: flex; align-items: center; gap: var(--aacx-space-3);">
                  <button type="button" class="aacb-scan-start aacx-v2__btn aacx-v2__btn--primary aacx-v2__btn--lg"
                          data-first-label="<?php echo esc_attr($btn_label_first); ?>"
                          data-again-label="<?php echo esc_attr($btn_label_again); ?>">
                      <?php echo esc_html($primary_button); ?> →
                  </button>
                  <span class="aacb-scan-status aacx-hidden" style="font-size: var(--aacx-text-sm); color: var(--aacx-text-muted);"></span>
              </div>

              <div class="aacb-scan-progress aacx-hidden" style="margin-top: var(--aacx-space-4);">
                  <div role="progressbar" aria-valuemin="0" aria-valuemax="100" style="height: 8px; background: var(--aacx-slate-200); border-radius: var(--aacx-radius-pill); overflow: hidden;">
                      <div class="aacb-scan-bar" style="height: 100%; width: 0%; background: var(--aacx-primary-600); transition: width 200ms ease;"></div>
                  </div>
                  <p style="margin-top: var(--aacx-space-2); font-size: var(--aacx-text-sm); color: var(--aacx-text-muted);">
                      <span class="aacb-scan-done">0</span> /
                      <span class="aacb-scan-total">—</span>
                      <?php esc_html_e('pages scanned', 'allaccessible'); ?>
                      <span class="aacb-scan-eta" style="color: var(--aacx-text-muted);"></span>
                  </p>
              </div>

              <div class="aacb-scan-done-state aacb-scan-trigger-banner-ok aacx-v2__banner aacx-v2__banner--ok aacx-hidden" style="margin-top: var(--aacx-space-4);">
                  <div>
                      <span aria-hidden="true">✓</span>
                      <strong class="aacb-scan-pages-final">0</strong>
                      <?php esc_html_e('pages scanned. Accessibility scores appear in your Pages list within a minute or two as the AI finishes reviewing.', 'allaccessible'); ?>
                      <a href="<?php echo esc_url(admin_url('edit.php?post_type=page')); ?>" style="margin-left: var(--aacx-space-2);">
                          <?php esc_html_e('Open Pages list', 'allaccessible'); ?> →
                      </a>
                  </div>
              </div>

              <div class="aacb-scan-error-state aacx-v2__banner aacx-v2__banner--danger aacx-hidden" style="margin-top: var(--aacx-space-4);">
                  <div class="aacb-scan-error-msg"></div>
              </div>

              <?php
              $rg = 'aacb-sitemap-choice-' . esc_attr($context);
              ?>
              <details class="aacb-advanced-sitemap" style="margin-top: var(--aacx-space-5);">
                  <summary style="cursor:pointer;font-size:var(--aacx-text-sm);color:var(--aacx-text-muted);">
                      <?php esc_html_e('Scan a specific sitemap instead (advanced)', 'allaccessible'); ?>
                  </summary>
                  <div class="aacb-advanced-sitemap-body" data-loaded="0" data-radio-group="<?php echo esc_attr($rg); ?>" style="margin-top: var(--aacx-space-3);">
                      <p class="aacx-v2__help" style="margin-bottom: var(--aacx-space-3);">
                          <?php esc_html_e('Leave this closed to let AllAccessible auto-discover your pages (recommended). Open only to point the scan at a specific sitemap.', 'allaccessible'); ?>
                      </p>
                      <div class="aacb-sitemap-candidates">
                          <span class="aacx-v2__help aacb-sitemap-loading aacx-hidden"><?php esc_html_e('Looking for sitemaps…', 'allaccessible'); ?></span>
                      </div>
                  </div>
              </details>

            </div>
          </div>
        </div>
        <?php
    }

    /**
     * Enqueue the JS that drives every instance of the panel on the page.
     */
    public static function enqueue_inline_script() {
        static $printed = false;
        if ($printed) return;
        $printed = true;
        ?>
        <script>
        (function($) {
            const POLL_MS = 5000;

            function start(panel) {
                const $p       = $(panel);
                const nonce    = $p.data('nonce');
                const redirect = $p.data('success-redirect') || '';
                const $input   = $p.find('.aacb-scan-sitemap-input');
                const choice = $p.find('.aacb-sitemap-radio:checked').val();
                let sitemap = '';
                if (choice === '__custom__') {
                    sitemap = ($input.val() || '').trim();
                    if (!sitemap) { showError($p, '<?php echo esc_js(__('Enter your sitemap URL, or close Advanced to auto-discover.', 'allaccessible')); ?>'); return; }
                } else if (choice && choice !== '__skip__') {
                    sitemap = choice; // a verified candidate URL
                }
                // choice empty OR '__skip__' → sitemap stays '' (auto-discover)

                const $btn      = $p.find('.aacb-scan-start');
                const $progress = $p.find('.aacb-scan-progress');
                const $done     = $p.find('.aacb-scan-done-state');
                const $err      = $p.find('.aacb-scan-error-state');

                $btn.prop('disabled', true).text('<?php echo esc_js(__('Starting…', 'allaccessible')); ?>');
                $err.addClass('aacx-hidden');
                $done.addClass('aacx-hidden');
                $progress.removeClass('aacx-hidden');

                $.post(ajaxurl, {
                    action: 'aacb_start_scan',
                    _wpnonce: nonce,
                    sitemap_url: sitemap,
                    viewport: 'both',
                }).done(function(resp) {
                    if (!resp || !resp.success) {
                        showError($p, (resp && resp.data) || '<?php echo esc_js(__('Scan failed to start.', 'allaccessible')); ?>');
                        $btn.prop('disabled', false).text(($btn.data('first-label') || '<?php echo esc_js(__('Start first scan', 'allaccessible')); ?>') + ' →');
                        return;
                    }
                    // Dispatch the scan; results appear on the Agentic Fixes
                    // page shortly.
                    if (resp.data && resp.data.queued) {
                        $p.removeData('scan-retried');
                        $p.find('.aacb-scan-progress').addClass('aacx-hidden');
                        $p.find('.aacb-scan-pages-final').text('—');
                        $p.find('.aacb-scan-done-state').removeClass('aacx-hidden')
                            .find('p').text('<?php echo esc_js(__('Scan started. Results appear on the Agentic Fixes page in 2-5 minutes.', 'allaccessible')); ?>');
                        $p.find('.aacb-scan-last-triggered').text('<?php echo esc_js(__('Scan triggered just now', 'allaccessible')); ?>');
                        $btn.prop('disabled', false).text(($btn.data('again-label') || '<?php echo esc_js(__('Run scan again', 'allaccessible')); ?>'));
                        if (redirect) setTimeout(function() { window.location.href = redirect; }, 2500);
                        return;
                    }
                    // Fallback path for older responses.
                    const jobId = resp.data && (resp.data.jobId || resp.data.id);
                    if (!jobId) {
                        showError($p, '<?php echo esc_js(__('Server did not return a job ID.', 'allaccessible')); ?>');
                        $btn.prop('disabled', false).text(($btn.data('first-label') || '<?php echo esc_js(__('Start first scan', 'allaccessible')); ?>') + ' →');
                        return;
                    }
                    poll($p, nonce, jobId, redirect);
                }).fail(function(xhr) {
                    // Transient unavailability while the server finishes
                    // setup: retry once after a short delay.
                    if (xhr.status === 503 && !$p.data('scan-retried')) {
                        $p.data('scan-retried', 1);
                        $btn.text('<?php echo esc_js(__('Finishing setup…', 'allaccessible')); ?>');
                        setTimeout(function() { start($p); }, 4000);
                        return;
                    }
                    $p.removeData('scan-retried');
                    let msg = '<?php echo esc_js(__('Network error', 'allaccessible')); ?>';
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        msg = (typeof xhr.responseJSON.data === 'string')
                            ? xhr.responseJSON.data
                            : (xhr.responseJSON.data.reason || JSON.stringify(xhr.responseJSON.data));
                    } else if (xhr.status === 502 || xhr.status === 504) {
                        msg = '<?php echo esc_js(__('Server timeout while queuing scan. Try again.', 'allaccessible')); ?>';
                    } else if (xhr.status) {
                        msg = '<?php echo esc_js(__('Request failed (HTTP %s)', 'allaccessible')); ?>'.replace('%s', xhr.status);
                    }
                    showError($p, msg);
                    $btn.prop('disabled', false).text(($btn.data('first-label') || '<?php echo esc_js(__('Start first scan', 'allaccessible')); ?>') + ' →');
                });
            }

            function poll($p, nonce, jobId, redirect) {
                $.post(ajaxurl, { action: 'aacb_scan_status', _wpnonce: nonce, job_id: jobId })
                    .done(function(resp) {
                        if (!resp || !resp.success) { setTimeout(function() { poll($p, nonce, jobId, redirect); }, POLL_MS); return; }
                        const d = resp.data || {};
                        const done = Number(d.pages_done || d.pagesDone || 0);
                        const total = Number(d.total_pages || d.totalPages || 0);
                        const pct = total > 0 ? Math.min(100, Math.round((done / total) * 100)) : 5;
                        $p.find('.aacb-scan-done').text(done);
                        $p.find('.aacb-scan-total').text(total || '—');
                        $p.find('.aacb-scan-bar').css('width', pct + '%');
                        const status = (d.status || '').toLowerCase();
                        if (status === 'done' || status === 'complete' || status === 'partial') {
                            $p.find('.aacb-scan-pages-final').text(done);
                            $p.find('.aacb-scan-done-state').removeClass('aacx-hidden');
                            $p.find('.aacb-scan-progress').addClass('aacx-hidden');
                            const $b = $p.find('.aacb-scan-start');
                            $b.prop('disabled', false).text(($b.data('again-label') || '<?php echo esc_js(__('Run scan again', 'allaccessible')); ?>'));
                            if (redirect) setTimeout(function() { window.location.href = redirect; }, 1500);
                            return;
                        }
                        if (status === 'failed') {
                            showError($p, '<?php echo esc_js(__('Scan failed. Try again.', 'allaccessible')); ?>');
                            $p.find('.aacb-scan-progress').addClass('aacx-hidden');
                            $p.find('.aacb-scan-start').prop('disabled', false).text('<?php echo esc_js(__('Retry scan', 'allaccessible')); ?>');
                            return;
                        }
                        setTimeout(function() { poll($p, nonce, jobId, redirect); }, POLL_MS);
                    })
                    .fail(function() { setTimeout(function() { poll($p, nonce, jobId, redirect); }, POLL_MS); });
            }

            function showError($p, msg) {
                $p.find('.aacb-scan-error-msg').text(typeof msg === 'string' ? msg : JSON.stringify(msg));
                $p.find('.aacb-scan-error-state').removeClass('aacx-hidden');
                $p.find('.aacb-scan-progress').addClass('aacx-hidden');
            }

            const SRC_LABELS = {
                yoast: 'Yoast SEO', rankmath: 'Rank Math', aioseo: 'All in One SEO',
                seopress: 'SEOPress', 'wp-core': 'WordPress', 'http-probe': '<?php echo esc_js(__('Detected', 'allaccessible')); ?>'
            };
            function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

            // Load detected sitemap candidates when Advanced opens.
            function loadCandidates($p) {
                const $body = $p.find('.aacb-advanced-sitemap-body');
                if ($body.attr('data-loaded') === '1') return;
                $body.attr('data-loaded', '1');
                const rg    = $body.attr('data-radio-group') || 'aacb-sitemap-choice';
                const $box  = $p.find('.aacb-sitemap-candidates');
                $box.html('<span class="aacx-v2__help"><?php echo esc_js(__('Looking for sitemaps…', 'allaccessible')); ?></span>');

                $.post(ajaxurl, { action: 'aacb_detect_sitemap', _wpnonce: $p.data('nonce') })
                    .done(function(resp) {
                        const list = (resp && resp.success && resp.data && resp.data.candidates) || [];
                        let html = '';
                        list.forEach(function(c) {
                            const url = c && c.url ? String(c.url) : '';
                            if (!url) return;
                            const label = SRC_LABELS[c.source] || '<?php echo esc_js(__('Detected', 'allaccessible')); ?>';
                            html += '<label class="aacb-sitemap-option" style="display:flex;align-items:center;gap:8px;padding:8px 10px;border:1px solid var(--aacx-border);border-radius:6px;margin-bottom:8px;cursor:pointer;">'
                                +   '<input type="radio" name="' + esc(rg) + '" class="aacb-sitemap-radio" value="' + esc(url) + '">'
                                +   '<span style="display:flex;flex-direction:column;gap:2px;flex:1;min-width:0;">'
                                +     '<span style="font-size:var(--aacx-text-sm);color:var(--aacx-text-strong);">' + esc(label) + '</span>'
                                +     '<span style="font-family:var(--aacx-font-mono);font-size:var(--aacx-text-xs);color:var(--aacx-text-muted);word-break:break-all;">' + esc(url) + '</span>'
                                +   '</span>'
                                +   '<a href="' + esc(url) + '" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--ghost aacx-v2__btn--sm" style="flex-shrink:0;" onclick="event.stopPropagation();"><?php echo esc_js(__('Open & verify', 'allaccessible')); ?> ↗</a>'
                                + '</label>';
                        });
                        if (!list.length) {
                            html += '<p class="aacx-v2__help" style="margin-bottom:8px;"><?php echo esc_js(__('No reachable sitemap found. Enter one below, or close Advanced to auto-discover.', 'allaccessible')); ?></p>';
                        }
                        // Always offer a manual URL entry.
                        html += '<label class="aacb-sitemap-option" style="display:flex;align-items:center;gap:8px;padding:8px 10px;border:1px solid var(--aacx-border);border-radius:6px;margin-bottom:8px;cursor:pointer;">'
                            +     '<input type="radio" name="' + esc(rg) + '" class="aacb-sitemap-radio" value="__custom__">'
                            +     '<span style="font-size:var(--aacx-text-sm);"><?php echo esc_js(__('Enter my own sitemap URL', 'allaccessible')); ?></span>'
                            +   '</label>'
                            +   '<input type="url" class="aacb-scan-sitemap-input aacx-v2__input" placeholder="https://your-site.com/sitemap.xml" disabled>';
                        $box.html(html);
                    })
                    .fail(function() {
                        $box.html('<p class="aacx-v2__help"><?php echo esc_js(__('Could not check for sitemaps. Close Advanced to auto-discover instead.', 'allaccessible')); ?></p>');
                        $body.attr('data-loaded', '0'); // allow retry on next open
                    });
            }

            // `toggle` does not bubble, so bind directly (panel markup is
            // already in the DOM by the time this footer script runs).
            $(function() {
                $('.aacb-advanced-sitemap').on('toggle', function() {
                    if (this.open) loadCandidates($(this).closest('.aacb-scan-trigger'));
                });
            });

            $(document).on('click', '.aacb-scan-start', function(e) {
                e.preventDefault();
                start($(this).closest('.aacb-scan-trigger'));
            });
            $(document).on('change', '.aacb-sitemap-radio', function() {
                const $p = $(this).closest('.aacb-scan-trigger');
                const isCustom = $(this).val() === '__custom__';
                const $input = $p.find('.aacb-scan-sitemap-input');
                $input.prop('disabled', !isCustom);
                if (isCustom) { $input.focus(); } else { $input.val(''); }
            });
        })(jQuery);
        </script>
        <?php
    }
}
