<?php
/**
 * AllAccessible Editor Meta Box
 *
 * Adds accessibility score meta box to post/page editor.
 * Shows page-specific audit results and quick actions.
 *
 * @package AllAccessible
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AllAccessible_EditorMetaBox {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Add meta box to post/page editor
     */
    public function add_meta_box() {
        $premium = (bool) get_option('aacb_accountID');

        // Only show for premium users
        if (!$premium) {
            // Show upgrade prompt instead
            add_meta_box(
                'aacb_accessibility_score_upgrade',
                __('Accessibility Score', 'allaccessible'),
                array($this, 'render_upgrade_meta_box'),
                array('post', 'page'),
                'side',
                'high'
            );
            return;
        }

        add_meta_box(
            'aacb_accessibility_score',
            __('Accessibility Score', 'allaccessible'),
            array($this, 'render_meta_box'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    /**
     * Enqueue scripts for meta box
     */
    public function enqueue_scripts($hook) {
        // Only load on post/page editor
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        wp_enqueue_style(
            'aacx-v2-admin',
            AACB_CSS . 'admin-v2.css',
            array(),
            aacb_asset_ver('admin-v2.css')
        );

        wp_enqueue_script(
            'allaccessible-editor-metabox',
            AACB_JS . 'js/editor-metabox.js',
            array('jquery'),
            aacb_asset_ver('js/editor-metabox.js'),
            true
        );

        wp_localize_script('allaccessible-editor-metabox', 'aacbEditorMeta', array(
            'nonce' => wp_create_nonce('wp_rest'),
            'post_id' => get_the_ID(),
            'labels' => array(
                'loading' => __('Loading score...', 'allaccessible'),
                'error' => __('Error loading score', 'allaccessible'),
                'rescan_success' => __('Rescan initiated successfully', 'allaccessible'),
                'rescan_error' => __('Error initiating rescan', 'allaccessible'),
            ),
        ));
    }

    /**
     * Render accessibility score meta box.
     */
    public function render_meta_box($post) {
        // Canonicalize the permalink before any API call so the canonical
        // URL lookup hits on the first try.
        $post_url = AllAccessible_UrlCanonicalizer::for_post((int) $post->ID);
        if ($post_url === '') $post_url = (string) get_permalink($post->ID);

        if ($post->post_status !== 'publish') {
            ?>
            <div class="aacb-metabox-wrapper allaccessible-admin aacx-v2">
                <p class="aacb-notice"><?php _e('Publish this page to see its accessibility score.', 'allaccessible'); ?></p>
            </div>
            <?php
            return;
        }

        if (!class_exists('AllAccessible_ApiClient')) {
            return;
        }

        $client = AllAccessible_ApiClient::get_instance();
        $audit  = $client->get_page_audit((int) $post->ID, $post_url);

        if (!is_wp_error($audit) && isset($audit['data_source']) && $audit['data_source'] === 'page') {
            // Only attempt linkage when we actually got a page row.
            $client->link_post_to_page((int) $post->ID, $post_url);
        }

        AllAccessible_Debug::console('EditorMetaBox — post ' . (int) $post->ID, array(
            'post_id'        => (int) $post->ID,
            'raw_permalink'  => (string) get_permalink($post->ID),
            'canonical_url'  => $post_url,
            'api_is_error'   => is_wp_error($audit),
            'api_error'      => is_wp_error($audit) ? $audit->get_error_message() : null,
            'api_response'   => is_wp_error($audit) ? null : $audit,
            'render_branch'  => (is_wp_error($audit) || !is_array($audit)) ? 'score_unavailable' : 'full_panel',
        ));

        if (is_wp_error($audit) || !is_array($audit)) {
            ?>
            <div class="aacb-metabox-wrapper allaccessible-admin aacx-v2">
                <div class="aacx-v2__banner aacx-v2__banner--warn">
                    <div>
                        <strong><?php _e('Score unavailable', 'allaccessible'); ?></strong>
                        <p style="font-size: var(--aacx-text-xs); margin-top: var(--aacx-space-1);">
                            <?php _e('Could not load this page\'s accessibility score. Try refreshing the editor.', 'allaccessible'); ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php
            return;
        }

        $score        = isset($audit['overall_score']) ? (int) $audit['overall_score'] : null;
        $issues       = is_array($audit['issues'] ?? null) ? $audit['issues'] : array();
        $total_issues = (int) ($audit['total_issues'] ?? 0);
        $last_scan    = isset($audit['last_scan']) ? (string) $audit['last_scan'] : '';
        $data_source  = (string) ($audit['data_source'] ?? 'none');
        $audit_status = (string) ($audit['audit_status'] ?? 'never');
        $breakdown        = is_array($audit['score_breakdown'] ?? null) ? $audit['score_breakdown'] : array();
        $score_raw        = isset($breakdown['raw'])               ? (int) $breakdown['raw']               : null;
        $score_potential  = isset($breakdown['potential'])         ? (int) $breakdown['potential']         : null;
        $widget_fixed     = isset($breakdown['widget_fixed'])      ? (int) $breakdown['widget_fixed']      : 0;
        $manifest_active  = isset($breakdown['manifest_approved']) ? (int) $breakdown['manifest_approved'] : 0;
        $manifest_pending = isset($breakdown['manifest_pending'])  ? (int) $breakdown['manifest_pending']  : 0;

        // Score → color band + grade label.
        $score_class = 'score-poor';
        $grade_label = __('Needs attention', 'allaccessible');
        if ($score === null) {
            $score_class = '';
            $grade_label = __('Awaiting scan', 'allaccessible');
        } elseif ($score >= 90) {
            $score_class = 'score-excellent';
            $grade_label = __('Excellent', 'allaccessible');
        } elseif ($score >= 75) {
            $score_class = 'score-good';
            $grade_label = __('Good', 'allaccessible');
        } elseif ($score >= 50) {
            $score_class = 'score-fair';
            $grade_label = __('Needs review', 'allaccessible');
        }

        // Last-scan human-readable.
        $last_scan_human = '';
        if ($last_scan !== '') {
            $ts = strtotime($last_scan);
            if ($ts) {
                $last_scan_human = sprintf(
                    /* translators: %s: human-readable time diff (e.g., "2 hours") */
                    __('%s ago', 'allaccessible'),
                    human_time_diff($ts, current_time('timestamp'))
                );
            }
        }

        $deeplink       = 'https://app.allaccessible.org';
        $score_deeplink = '';
        $opts           = $client->get_site_options();
        $audit_id       = isset($audit['audit_id']) ? (int) $audit['audit_id'] : 0;
        $sub_id_api     = isset($audit['subdomain_id']) ? (int) $audit['subdomain_id'] : 0;
        if (!is_wp_error($opts) && is_object($opts) && !empty($opts->siteID)) {
            $sub  = $sub_id_api > 0 ? $sub_id_api : (int) get_option('aacb_siteID');
            $tok  = (string) $opts->siteID;
            if ($sub > 0 && $tok !== '') {
                $deeplink = sprintf(
                    'https://app.allaccessible.org/site/%s/%d/accessibility-audits',
                    rawurlencode($tok),
                    $sub
                );
                if ($audit_id > 0) {
                    $score_deeplink = sprintf(
                        'https://app.allaccessible.org/site/%s/%d/audit/%d',
                        rawurlencode($tok),
                        $sub,
                        $audit_id
                    );
                }
            }
        }
        ?>
        <?php
        $has_lift    = ($score_raw !== null && $score !== null && $score_raw < $score);
        $has_pending = ($score_potential !== null && $score !== null && $score_potential > $score);
        $score_str   = $score !== null ? (string) $score : '—';
        $circle_aria = $score !== null
            ? sprintf(__('Accessibility score %1$d out of 100, %2$s. Open full audit.', 'allaccessible'), (int) $score, $grade_label)
            : __('Accessibility score not available yet.', 'allaccessible');
        ?>
        <div class="aacb-metabox-wrapper allaccessible-admin aacx-v2">

            <!-- 1. Score circle — the visual anchor; metabox title supplies "Accessibility Score" -->
            <div class="aacb-score-display">
                <?php if ($score_deeplink !== '') : ?>
                    <a href="<?php echo esc_url($score_deeplink); ?>"
                       target="_blank"
                       rel="noopener"
                       class="aacb-score-circle <?php echo esc_attr($score_class); ?>"
                       aria-label="<?php echo esc_attr($circle_aria); ?>">
                        <div class="aacb-score-number"><?php echo esc_html($score_str); ?></div>
                        <div class="aacb-score-grade"><?php echo esc_html($grade_label); ?></div>
                    </a>
                <?php else : ?>
                    <div class="aacb-score-circle <?php echo esc_attr($score_class); ?>"
                         aria-label="<?php echo esc_attr($circle_aria); ?>">
                        <div class="aacb-score-number"><?php echo esc_html($score_str); ?></div>
                        <div class="aacb-score-grade"><?php echo esc_html($grade_label); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 2. Lift strip — raw → current delta with green +N gain pill -->
            <?php if ($has_lift) :
                $gain = (int) $score - (int) $score_raw;
                $lift_aria = sprintf(
                    /* translators: 1: raw, 2: current, 3: lift delta */
                    __('Score lifted from %1$d to %2$d by AllAccessible AI, a gain of %3$d points.', 'allaccessible'),
                    (int) $score_raw, (int) $score, $gain
                );
            ?>
                <div class="aacx-lift-strip" role="group" aria-label="<?php echo esc_attr($lift_aria); ?>">
                    <span class="aacx-v2__ai-badge">
                        <?php esc_html_e('AllAccessible AI', 'allaccessible'); ?>
                    </span>
                    <span class="aacx-lift-delta" aria-hidden="true">
                        <span class="aacx-lift-from"><?php echo esc_html((int) $score_raw); ?></span>
                        <span class="aacx-lift-arrow">→</span>
                        <span class="aacx-lift-to"><?php echo esc_html((int) $score); ?></span>
                        <span class="aacx-lift-gain">+<?php echo esc_html($gain); ?></span>
                    </span>
                </div>
                <?php

                $detail_parts = array();
                if ($widget_fixed > 0) {
                    $detail_parts[] = sprintf(
                        /* translators: %s: count of issues AllAccessible auto-resolves at runtime */
                        _n('%s issue resolved by AllAccessible', '%s issues resolved by AllAccessible', $widget_fixed, 'allaccessible'),
                        number_format_i18n($widget_fixed)
                    );
                }
                if ($manifest_active > 0) {
                    $detail_parts[] = sprintf(
                        /* translators: %s: count of live agentic AI fixes */
                        _n('%s agentic AI fix live', '%s agentic AI fixes live', $manifest_active, 'allaccessible'),
                        number_format_i18n($manifest_active)
                    );
                }
                if (!empty($detail_parts)) : ?>
                    <p class="aacx-lift-detail"><?php echo esc_html(implode(' · ', $detail_parts)); ?></p>
                <?php endif; ?>
            <?php endif; ?>

            <!-- 3. Pending fixes CTA — full-card click target, only when pending lift available -->
            <?php if ($has_pending) :
                $pending_aria = sprintf(
                    /* translators: 1: pending count, 2: potential score */
                    _n(
                        'Review %1$d pending agentic AI fix; approve to reach %2$d%% accessibility score',
                        'Review %1$d pending agentic AI fixes; approve to reach %2$d%% accessibility score',
                        $manifest_pending,
                        'allaccessible'
                    ),
                    $manifest_pending, (int) $score_potential
                );
            ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=aacb-agentic-fixes')); ?>"
                   class="aacx-pending-cta aacx-v2__card aacx-v2__card--ai"
                   aria-label="<?php echo esc_attr($pending_aria); ?>">
                    <div class="aacx-pending-cta__icon" aria-hidden="true">✦</div>
                    <div class="aacx-pending-cta__body">
                        <div class="aacx-pending-cta__title">
                            <?php printf(
                                /* translators: %s: count of pending fixes */
                                esc_html(_n('%s fix ready to review', '%s fixes ready to review', $manifest_pending, 'allaccessible')),
                                esc_html(number_format_i18n($manifest_pending))
                            ); ?>
                        </div>
                        <div class="aacx-pending-cta__sub">
                            <?php printf(
                                /* translators: %s: potential score */
                                esc_html__('Approve to reach %s%%', 'allaccessible'),
                                '<strong>' . esc_html((int) $score_potential) . '</strong>'
                            ); ?>
                        </div>
                    </div>
                    <div class="aacx-pending-cta__chev" aria-hidden="true">›</div>
                </a>
            <?php endif; ?>

            <!-- 4. Issues summary — compact inline chips -->
            <div class="aacx-issues-summary">
                <div class="aacx-issues-summary__title">
                    <?php printf(
                        /* translators: %s: total issues */
                        esc_html__('Issues (%s)', 'allaccessible'),
                        esc_html(number_format_i18n($total_issues))
                    ); ?>
                </div>
                <div class="aacx-issues-chips" role="list">
                    <?php
                    $chip_specs = array(
                        'crit' => array('count' => (int) ($issues['critical'] ?? 0), 'label' => __('Critical', 'allaccessible')),
                        'ser'  => array('count' => (int) ($issues['serious']  ?? 0), 'label' => __('Serious',  'allaccessible')),
                        'mod'  => array('count' => (int) ($issues['moderate'] ?? 0), 'label' => __('Moderate', 'allaccessible')),
                        'min'  => array('count' => (int) ($issues['minor']    ?? 0), 'label' => __('Minor',    'allaccessible')),
                    );
                    foreach ($chip_specs as $key => $spec) :
                        $chip_class = 'aacx-issue-chip aacx-issue-chip--' . $key . ($spec['count'] === 0 ? ' aacx-issue-chip--zero' : '');
                        $chip_aria  = sprintf('%s: %d', $spec['label'], $spec['count']);
                    ?>
                        <span class="<?php echo esc_attr($chip_class); ?>"
                              role="listitem"
                              title="<?php echo esc_attr($spec['label']); ?>"
                              aria-label="<?php echo esc_attr($chip_aria); ?>">
                            <span class="aacx-issue-chip__dot" aria-hidden="true"></span>
                            <?php echo esc_html(number_format_i18n($spec['count'])); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 5. Last scanned -->
            <?php if ($last_scan_human !== '') : ?>
                <div class="aacb-last-scan">
                    <span class="dashicons dashicons-clock" aria-hidden="true"></span>
                    <span><?php
                        printf(
                            /* translators: %s: human-readable time ago */
                            esc_html__('Last scanned %s', 'allaccessible'),
                            esc_html($last_scan_human)
                        );
                    ?></span>
                </div>
            <?php endif; ?>

            <!-- 6. Primary CTA — full audit -->
            <div class="aacb-actions" style="display:flex;flex-direction:column;gap:var(--aacx-space-2);">
                <a href="<?php echo esc_url($score_deeplink !== '' ? $score_deeplink : $deeplink); ?>"
                   target="_blank"
                   rel="noopener"
                   class="aacx-v2__btn aacx-v2__btn--primary"
                   style="width: 100%;">
                    <?php _e('View full report', 'allaccessible'); ?>
                    <span aria-hidden="true">↗</span>
                </a>
                <button type="button"
                        class="aacx-v2__btn aacx-v2__btn--secondary aacb-metabox-rescan"
                        data-post-url="<?php echo esc_attr((string) get_permalink($post->ID)); ?>"
                        data-post-id="<?php echo esc_attr((int) $post->ID); ?>"
                        style="width: 100%;">
                    <?php _e('Rescan this page', 'allaccessible'); ?>
                </button>
            </div>

            <style>
                /* Minimal local spinner + status. Scoped to .aacb-metabox-rescan
                   so it doesn't fight wp-admin styles elsewhere. */
                @keyframes aacb-rescan-spin { to { transform: rotate(360deg); } }
                .aacb-metabox-rescan .dashicons-update.is-spinning {
                    animation: aacb-rescan-spin 1s linear infinite;
                    vertical-align: -3px;
                    margin-right: 4px;
                }
                .aacb-rescan-status {
                    display: block;
                    margin-top: 8px;
                    font-size: 12px;
                    line-height: 1.4;
                }
                .aacb-rescan-status.is-error   { color: #b91c1c; }
                .aacb-rescan-status.is-warning { color: #92400e; }
                .aacb-rescan-status.is-ok      { color: #15803d; }
                .aacb-rescan-status .aacb-rescan-action {
                    margin-left: 6px;
                    text-decoration: underline;
                    cursor: pointer;
                }
            </style>
            <script>
            (function(){
                if (typeof document === 'undefined') return;
                document.addEventListener('DOMContentLoaded', function(){
                    var btn = document.querySelector('.aacb-metabox-rescan');
                    if (!btn) return;
                    var ajaxUrl     = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
                    var scanNonce   = <?php echo wp_json_encode(wp_create_nonce('aacb_scan_this_page')); ?>;
                    var statusNonce = <?php echo wp_json_encode(wp_create_nonce('aacb_scan_status')); ?>;
                    // ONE label from click through done. Was two-stage
                    // ("queued" then "running") which read as redundant.
                    var i18n = {
                        scanning: <?php echo wp_json_encode(__('Scanning page…', 'allaccessible')); ?>,
                        doneClean: <?php echo wp_json_encode(__('Scan complete. Refreshing…', 'allaccessible')); ?>,
                        doneDirty: <?php echo wp_json_encode(__('Scan complete. Save or reload to see the updated score.', 'allaccessible')); ?>,
                        viewScore: <?php echo wp_json_encode(__('Reload now', 'allaccessible')); ?>,
                        failed:   <?php echo wp_json_encode(__('Scan failed. Try again or check the dashboard.', 'allaccessible')); ?>,
                        timeout:  <?php echo wp_json_encode(__('Still running — refresh the page in a minute.', 'allaccessible')); ?>,
                        rate:     <?php echo wp_json_encode(__('Scan rate limit reached. Try again later.', 'allaccessible')); ?>,
                        error:    <?php echo wp_json_encode(__('Could not start scan.', 'allaccessible')); ?>,
                        network:  <?php echo wp_json_encode(__('Network error. Check your connection and try again.', 'allaccessible')); ?>,
                    };
                    var origLabel = btn.textContent;

                    // Inline status node beneath the button. Replaces the
                    // old alert() popups, which were jarring inside Gutenberg.
                    var statusEl = document.createElement('span');
                    statusEl.className = 'aacb-rescan-status';
                    statusEl.setAttribute('aria-live', 'polite');
                    statusEl.hidden = true;
                    btn.insertAdjacentElement('afterend', statusEl);

                    function setStatus(text, variant) {
                        statusEl.textContent = text || '';
                        statusEl.className = 'aacb-rescan-status' + (variant ? ' is-' + variant : '');
                        statusEl.hidden = !text;
                    }
                    function setButtonScanning() {
                        btn.disabled = true;
                        btn.innerHTML = '<span class="dashicons dashicons-update is-spinning" aria-hidden="true"></span>' + escapeHtml(i18n.scanning);
                    }
                    function resetButton() {
                        btn.disabled = false;
                        btn.textContent = origLabel;
                    }
                    function escapeHtml(s) {
                        var d = document.createElement('div');
                        d.textContent = s;
                        return d.innerHTML;
                    }
                    function isPostDirty() {
                        try {
                            // Gutenberg: ask the editor whether the post has
                            // unsaved edits. Auto-reload would lose them.
                            if (window.wp && wp.data && typeof wp.data.select === 'function') {
                                var ed = wp.data.select('core/editor');
                                if (ed && typeof ed.isEditedPostDirty === 'function') {
                                    return !!ed.isEditedPostDirty();
                                }
                            }
                        } catch (e) {}
                        return false; // Classic editor: assume safe to reload.
                    }
                    function finishDone() {
                        if (isPostDirty()) {
                            resetButton();
                            statusEl.textContent = i18n.doneDirty + ' ';
                            statusEl.className = 'aacb-rescan-status is-ok';
                            statusEl.hidden = false;
                            var link = document.createElement('a');
                            link.href = '#';
                            link.className = 'aacb-rescan-action';
                            link.textContent = i18n.viewScore;
                            link.addEventListener('click', function(ev){
                                ev.preventDefault();
                                window.location.reload();
                            });
                            statusEl.appendChild(link);
                            return;
                        }
                        setStatus(i18n.doneClean, 'ok');
                        setTimeout(function(){ window.location.reload(); }, 2500);
                    }

                    btn.addEventListener('click', function(e){
                        e.preventDefault();
                        if (btn.disabled) return;
                        var pageUrl = btn.dataset.postUrl || '';
                        if (!pageUrl) return;

                        setStatus('', null);
                        setButtonScanning();

                        var fd = new FormData();
                        fd.append('action', 'aacb_scan_this_page');
                        fd.append('_ajax_nonce', scanNonce);
                        fd.append('page_url', pageUrl);

                        fetch(ajaxUrl, {method:'POST', credentials:'same-origin', body:fd})
                          .then(function(r){ return r.json().catch(function(){ return null; }); })
                          .then(function(j){
                              if (j && j.success && j.data && j.data.jobId) {
                                  pollStatus(j.data.jobId, pageUrl);
                              } else {
                                  resetButton();
                                  var msg = (j && j.data && j.data.message) ? j.data.message : i18n.error;
                                  if (j && j.data && j.data.code === 429) msg = i18n.rate;
                                  setStatus(msg, 'error');
                              }
                          })
                          .catch(function(){
                              resetButton();
                              setStatus(i18n.network, 'error');
                          });
                    });

                    function pollStatus(jobId, pageUrl) {
                        var started = Date.now();
                        var intervalMs = 4000;
                        var maxMs = 180000;
                        var timer = setInterval(function(){
                            if (Date.now() - started > maxMs) {
                                clearInterval(timer);
                                resetButton();
                                setStatus(i18n.timeout, 'warning');
                                return;
                            }
                            var fd = new FormData();
                            fd.append('action', 'aacb_scan_status');
                            fd.append('_ajax_nonce', statusNonce);
                            fd.append('job_id', jobId);
                            fd.append('page_url', pageUrl);
                            fetch(ajaxUrl, {method:'POST', credentials:'same-origin', body:fd})
                              .then(function(r){ return r.json().catch(function(){ return null; }); })
                              .then(function(j){
                                  if (!j || !j.success || !j.data) return; // transient — keep polling
                                  var status = j.data.status;
                                  if (status === 'done') {
                                      clearInterval(timer);
                                      finishDone();
                                  } else if (status === 'failed') {
                                      clearInterval(timer);
                                      resetButton();
                                      setStatus(i18n.failed, 'error');
                                  }
                                  // queued / running: spinner already conveys
                                  // progress — no need to flip labels.
                              })
                              .catch(function(){ /* transient — keep polling */ });
                        }, intervalMs);
                    }
                });
            })();
            </script>

            <!-- 8. Footer status -->
            <div class="aacb-update-notice">
                <?php if ($data_source === 'page') : ?>
                    <span class="dashicons dashicons-yes-alt" aria-hidden="true" style="color: var(--aacx-ok-600);"></span>
                    <?php _e('Page-level score', 'allaccessible'); ?>
                <?php elseif ($data_source === 'site') : ?>
                    <span class="dashicons dashicons-info" aria-hidden="true"></span>
                    <?php _e('Site-level score (page not yet crawled)', 'allaccessible'); ?>
                <?php else : ?>
                    <span class="dashicons dashicons-info" aria-hidden="true"></span>
                    <?php _e('Awaiting first scan', 'allaccessible'); ?>
                <?php endif; ?>
            </div>
        </div>

        <style>
            .aacb-metabox-wrapper { padding: 10px; }

            /* Score circle — anchor */
            .aacb-score-display { text-align: center; margin-bottom: var(--aacx-space-3); }
            .aacb-score-circle {
                width: 100px;
                height: 100px;
                border-radius: 50%;
                border: 7px solid #e5e7eb;
                display: inline-flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                margin: 0 auto;
                text-decoration: none;
                cursor: pointer;
                transition: transform var(--aacx-transition);
            }
            a.aacb-score-circle:hover { transform: scale(1.02); }
            .aacb-score-circle.score-excellent { border-color: #00aa62; }
            .aacb-score-circle.score-good      { border-color: #54b8ff; }
            .aacb-score-circle.score-fair      { border-color: #f59e0b; }
            .aacb-score-circle.score-poor      { border-color: #ef4444; }
            .aacb-score-number {
                font-size: 28px;
                font-weight: var(--aacx-weight-bold);
                color: var(--aacx-text-strong);
                line-height: 1;
            }
            .aacb-score-grade {
                font-size: var(--aacx-text-xs);
                color: var(--aacx-text-muted);
                margin-top: 2px;
            }

            /* Lift strip */
            .aacx-lift-strip {
                display: flex;
                align-items: center;
                gap: var(--aacx-space-2);
                flex-wrap: wrap;
                padding: var(--aacx-space-2) var(--aacx-space-3);
                background: linear-gradient(135deg, var(--aacx-ai-50) 0%, var(--aacx-primary-50) 100%);
                border: 1px solid var(--aacx-ai-200);
                border-radius: var(--aacx-radius-md);
                margin-bottom: var(--aacx-space-1);
            }
            .aacx-lift-delta {
                display: inline-flex;
                align-items: center;
                gap: var(--aacx-space-2);
                font-variant-numeric: tabular-nums;
            }
            .aacx-lift-from { color: var(--aacx-text-muted); font-size: var(--aacx-text-sm); }
            .aacx-lift-arrow { color: var(--aacx-text-muted); font-size: var(--aacx-text-base); }
            .aacx-lift-to {
                color: var(--aacx-text-strong);
                font-size: var(--aacx-text-sm);
                font-weight: var(--aacx-weight-semibold);
            }
            .aacx-lift-gain {
                display: inline-flex;
                align-items: center;
                padding: 0 var(--aacx-space-2);
                height: 18px;
                background: var(--aacx-ok-100);
                color: var(--aacx-ok-700);
                font-size: var(--aacx-text-xs);
                font-weight: var(--aacx-weight-semibold);
                border-radius: var(--aacx-radius-pill);
                line-height: 1;
            }
            .aacx-lift-detail {
                margin: var(--aacx-space-1) 0 var(--aacx-space-3);
                font-size: var(--aacx-text-xs);
                color: var(--aacx-text-muted);
                line-height: var(--aacx-leading-normal);
            }

            /* Pending fixes CTA card */
            .aacx-pending-cta {
                display: flex;
                align-items: center;
                gap: var(--aacx-space-3);
                padding: var(--aacx-space-3);
                margin-bottom: var(--aacx-space-3);
                text-decoration: none !important;
                transition: border-color var(--aacx-transition), box-shadow var(--aacx-transition);
            }
            .aacx-pending-cta:hover {
                border-color: var(--aacx-ai-300);
                box-shadow: var(--aacx-shadow-sm);
            }
            .aacx-pending-cta__icon {
                flex-shrink: 0;
                width: 28px; height: 28px;
                display: flex; align-items: center; justify-content: center;
                background: var(--aacx-ai-100);
                color: var(--aacx-ai-700);
                border-radius: 50%;
                font-size: var(--aacx-text-base);
                font-weight: var(--aacx-weight-bold);
            }
            .aacx-pending-cta__body { flex: 1; min-width: 0; }
            .aacx-pending-cta__title {
                font-size: var(--aacx-text-sm);
                font-weight: var(--aacx-weight-semibold);
                color: var(--aacx-ai-800);
                line-height: 1.3;
            }
            .aacx-pending-cta__sub {
                font-size: var(--aacx-text-xs);
                color: var(--aacx-text-muted);
                margin-top: 2px;
                line-height: 1.3;
            }
            .aacx-pending-cta__sub strong {
                color: var(--aacx-text-strong);
                font-weight: var(--aacx-weight-semibold);
            }
            .aacx-pending-cta__chev {
                color: var(--aacx-ai-700);
                font-size: var(--aacx-text-lg);
                font-weight: var(--aacx-weight-bold);
                line-height: 1;
            }

            /* Issue chips */
            .aacx-issues-summary { margin-bottom: var(--aacx-space-3); }
            .aacx-issues-summary__title {
                font-size: var(--aacx-text-sm);
                font-weight: var(--aacx-weight-semibold);
                color: var(--aacx-text-strong);
                margin-bottom: var(--aacx-space-2);
            }
            .aacx-issues-chips {
                display: flex;
                gap: var(--aacx-space-2);
                flex-wrap: wrap;
            }
            .aacx-issue-chip {
                display: inline-flex;
                align-items: center;
                gap: var(--aacx-space-1);
                padding: 0 var(--aacx-space-2);
                height: 22px;
                background: var(--aacx-slate-100);
                color: var(--aacx-text-strong);
                font-size: var(--aacx-text-xs);
                font-weight: var(--aacx-weight-semibold);
                border-radius: var(--aacx-radius-pill);
                font-variant-numeric: tabular-nums;
                line-height: 1;
                transition: background var(--aacx-transition);
            }
            .aacx-issue-chip:hover { background: var(--aacx-slate-200); }
            .aacx-issue-chip--zero { opacity: 0.5; }
            .aacx-issue-chip__dot {
                width: 6px; height: 6px;
                border-radius: 50%;
                display: inline-block;
                flex-shrink: 0;
            }
            .aacx-issue-chip--crit .aacx-issue-chip__dot { background: var(--aacx-danger-500); }
            .aacx-issue-chip--ser  .aacx-issue-chip__dot { background: var(--aacx-urgent-500); }
            .aacx-issue-chip--mod  .aacx-issue-chip__dot { background: var(--aacx-warn-500); }
            .aacx-issue-chip--min  .aacx-issue-chip__dot { background: var(--aacx-primary-500); }
            .aacx-issue-chip--zero .aacx-issue-chip__dot { background: var(--aacx-slate-300); }

            /* Last scanned line */
            .aacb-last-scan {
                display: flex;
                align-items: center;
                gap: var(--aacx-space-1);
                margin: var(--aacx-space-3) 0;
                font-size: var(--aacx-text-xs);
                color: var(--aacx-text-muted);
            }
            .aacb-last-scan .dashicons {
                font-size: 12px; width: 12px; height: 12px;
            }

            /* Primary CTA spacing */
            .aacb-actions { margin-bottom: var(--aacx-space-3); }

            /* Footer status */
            .aacb-update-notice {
                margin-top: var(--aacx-space-2);
                padding-top: var(--aacx-space-2);
                border-top: 1px solid var(--aacx-border);
                text-align: center;
                font-size: var(--aacx-text-xs);
                color: var(--aacx-text-muted);
            }
            .aacb-update-notice .dashicons {
                font-size: 14px; width: 14px; height: 14px;
                vertical-align: middle;
            }

            /* Empty / loading states (unchanged from prior layout) */
            .aacb-loading { text-align: center; padding: 20px; }
            .aacb-notice {
                padding: 12px;
                background: #f0f6fc;
                border-left: 4px solid #54b8ff;
                margin: 0;
            }
        </style>
        <?php
    }

    /**
     * Render upgrade prompt for free users
     */
    public function render_upgrade_meta_box($post) {
        ?>
        <div class="aacb-metabox-wrapper allaccessible-admin aacx-v2">
            <div class="aacb-upgrade-prompt" style="text-align: center; padding: var(--aacx-space-4) 0;">
                <svg style="width: 48px; height: 48px; margin: 0 auto var(--aacx-space-3); color: var(--aacx-slate-400); display: block;" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                </svg>
                <h4 style="font-size: var(--aacx-text-lg); font-weight: var(--aacx-weight-semibold); margin-bottom: var(--aacx-space-2);">
                    <?php _e('Premium Feature', 'allaccessible'); ?>
                </h4>
                <p style="font-size: var(--aacx-text-sm); color: var(--aacx-text-muted); margin-bottom: var(--aacx-space-4);">
                    <?php _e('See accessibility scores for each page/post with a premium account.', 'allaccessible'); ?>
                </p>
                <a href="<?php echo admin_url('admin.php?page=allaccessible#pluginSettings'); ?>" class="aacx-v2__btn aacx-v2__btn--primary">
                    <?php _e('Upgrade to Premium', 'allaccessible'); ?>
                </a>
                <p style="font-size: var(--aacx-text-xs); color: var(--aacx-text-muted); margin-top: var(--aacx-space-3);">
                    <?php _e('7-day free trial available', 'allaccessible'); ?>
                </p>
            </div>
        </div>
        <?php
    }
}

// Initialize editor meta box after WordPress is fully loaded
add_action('plugins_loaded', function() {
    AllAccessible_EditorMetaBox::get_instance();
});
