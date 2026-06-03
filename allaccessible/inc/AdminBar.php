<?php
/**
 * AllAccessible — WP Admin Bar entry.
 */

if (!defined('ABSPATH')) { exit; }

final class AllAccessible_AdminBar {

    const PAGE_SCORE_TRANSIENT_PREFIX = 'aacb_page_score_';
    const PAGE_AUDIT_META_TRANSIENT_PREFIX = 'aacb_page_audit_meta_';
    const ICON_DATA_URI = 'data:image/svg+xml;base64,PHN2ZyBpZD0iTGF5ZXJfMSIgZGF0YS1uYW1lPSJMYXllciAxIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB2aWV3Qm94PSIwIDAgMzE3LjkgMzE3Ljg5Ij48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImxpbmVhci1ncmFkaWVudCIgeDE9IjE4My4yOSIgeTE9IjIxOC4yMiIgeDI9IjE4My4yOSIgeTI9IjQxLjI0IiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHN0b3Agb2Zmc2V0PSIwIiBzdG9wLWNvbG9yPSIjMGY4Y2ZhIi8+PHN0b3Agb2Zmc2V0PSIxIiBzdG9wLWNvbG9yPSIjNTRiOGZmIi8+PC9saW5lYXJHcmFkaWVudD48bGluZWFyR3JhZGllbnQgaWQ9ImxpbmVhci1ncmFkaWVudC0yIiB4MT0iMTU4Ljk0IiB5MT0iMzA5LjQ3IiB4Mj0iMTU4Ljk2IiB5Mj0iMjMuMDgiIGdyYWRpZW50VW5pdHM9InVzZXJTcGFjZU9uVXNlIj48c3RvcCBvZmZzZXQ9IjAiIHN0b3AtY29sb3I9IiMwOTU3YjAiLz48c3RvcCBvZmZzZXQ9IjEiIHN0b3AtY29sb3I9IiMyMTc5ZWIiLz48L2xpbmVhckdyYWRpZW50PjwvZGVmcz48cGF0aCBkPSJNMjU2LjYyLDU3Ljg4bC05Ny45LDk3LjlMMTEwLDEwN2MtMTcuOTMtMTcuOTMtNDUsOS4xOC0yNy4xMSwyNy4xMWw2Mi4yLDYyLjJhMTkuMDgsMTkuMDgsMCwwLDAsMTMuMzUsNS41OWguNjdhMTkuMTQsMTkuMTQsMCwwLDAsMTMuMzUtNS41OUwyODMuNzMsODVDMzAxLjY3LDY3LjA2LDI3NC41NSw0MCwyNTYuNjIsNTcuODhaIiBzdHlsZT0iZmlsbC1ydWxlOmV2ZW5vZGQ7ZmlsbDp1cmwoI2xpbmVhci1ncmFkaWVudCkiLz48cGF0aCBkPSJNMTU5LDYwLjMxYTI3Ljg2LDI3Ljg2LDAsMSwxLTI3Ljg2LDI3Ljg1QTI3Ljg1LDI3Ljg1LDAsMCwxLDE1OSw2MC4zMVpNMTU5LDBhMTU4Ljg4LDE1OC44OCwwLDEsMCwxMzguOSw4MS42MiwzMC40OCwzMC40OCwwLDAsMS03LDEwLjU0TDI3OC4yNiwxMDQuOGExMzEuMTEsMTMxLjExLDAsMCwxLTI1Ljc1LDE0NS44MmwtNDcuMzUtNDcuMzVjLTE3LjkzLTE3LjkzLTQ1LDkuMTgtMjcuMTEsMjcuMTFMMjIxLjY2LDI3NEExMzEuMjEsMTMxLjIxLDAsMCwxLDk2LDI3My44M2w0My42LTQzLjZjMTcuOTQtMTcuOTQtOS4xOS00NS0yNy4xMS0yNy4xMUw2NS4xNiwyNTAuNEExMzEsMTMxLDAsMCwxLDI0Mi4yOSw1Ny44OGw3LjE3LTcuMTZhMjkuMzMsMjkuMzMsMCwwLDEsMTcuMTktOC42N0ExNTguNDEsMTU4LjQxLDAsMCwwLDE1OSwwWiIgc3R5bGU9ImZpbGwtcnVsZTpldmVub2RkO2ZpbGw6dXJsKCNsaW5lYXItZ3JhZGllbnQtMikiLz48L3N2Zz4=';

    public static function register() {
        add_action('admin_bar_menu',        array(__CLASS__, 'add_nodes'), 80);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_action('wp_enqueue_scripts',    array(__CLASS__, 'enqueue_assets'));

        add_action('wp_ajax_aacb_scan_this_page', array(__CLASS__, 'ajax_scan_this_page'));
        add_action('wp_ajax_aacb_scan_status',    array(__CLASS__, 'ajax_scan_status'));
        add_action('wp_ajax_aacb_page_score',     array(__CLASS__, 'ajax_page_score'));
    }

    /**
     * Build the toolbar tree.
     */
    public static function add_nodes(\WP_Admin_Bar $bar) {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (function_exists('is_customize_preview') && is_customize_preview()) {
            return;
        }

        $is_frontend = !is_admin();
        $current_url = $is_frontend ? self::current_frontend_url() : '';

        // Resolve a score for the badge — purely from cache. NO API call.
        $score_label = self::cached_score_label_for_context($is_frontend, $current_url);

        // Root node — brand + (optional) score badge.
        $title = '<span class="aacb-bar-icon" aria-hidden="true"></span>';
        $title .= '<span class="aacb-bar-label">AllAccessible</span>';
        if ($score_label !== null) {
            $title .= ' <span class="aacb-bar-score aacb-bar-score--' . esc_attr($score_label['tier']) . '">'
                   . esc_html($score_label['text'])
                   . '</span>';
        }

        $bar->add_node(array(
            'id'    => 'allaccessible',
            'title' => $title,
            'href'  => admin_url('admin.php?page=allaccessible'),
            'meta'  => array('class' => 'aacb-admin-bar-root'),
        ));

        // Frontend-only: per-page actions.
        if ($is_frontend && $current_url !== '') {
            $bar->add_node(array(
                'id'     => 'allaccessible-scan-this',
                'parent' => 'allaccessible',
                'title'  => '<span class="aacb-bar-scan-icon" aria-hidden="true"></span>' .
                            esc_html__('Scan this page now', 'allaccessible'),
                'href'   => '#',
                'meta'   => array(
                    'class'   => 'aacb-bar-scan-trigger',
                    'onclick' => 'return false;',
                ),
            ));

            $report_url = self::page_report_url($current_url);
            if ($report_url !== null) {
                $bar->add_node(array(
                    'id'     => 'allaccessible-page-report',
                    'parent' => 'allaccessible',
                    'title'  => esc_html__('View page report', 'allaccessible'),
                    'href'   => esc_url($report_url),
                    'meta'   => array('target' => '_blank', 'rel' => 'noopener'),
                ));
            }

            // Separator
            $bar->add_group(array(
                'id'     => 'allaccessible-frontend-divider',
                'parent' => 'allaccessible',
                'meta'   => array('class' => 'ab-sub-secondary'),
            ));
        }

        // Always-visible plugin shortcuts.
        $shortcuts_parent = $is_frontend ? 'allaccessible-frontend-divider' : 'allaccessible';

        $bar->add_node(array(
            'id'     => 'allaccessible-dashboard',
            'parent' => $shortcuts_parent,
            'title'  => esc_html__('Dashboard', 'allaccessible'),
            'href'   => admin_url('admin.php?page=allaccessible'),
        ));
        $bar->add_node(array(
            'id'     => 'allaccessible-fixes',
            'parent' => $shortcuts_parent,
            'title'  => esc_html__('Audits & Fixes', 'allaccessible'),
            'href'   => admin_url('admin.php?page=allaccessible-agentic-fixes'),
        ));
        $bar->add_node(array(
            'id'     => 'allaccessible-settings',
            'parent' => $shortcuts_parent,
            'title'  => esc_html__('Settings', 'allaccessible'),
            'href'   => admin_url('admin.php?page=allaccessible-settings'),
        ));

        $bar->add_node(array(
            'id'     => 'allaccessible-open-app',
            'parent' => $shortcuts_parent,
            'title'  => esc_html__('Open dashboard', 'allaccessible'),
            'href'   => function_exists('aacb_app_url') ? aacb_app_url() : 'https://app.allaccessible.org/',
            'meta'   => array('target' => '_blank', 'rel' => 'noopener'),
        ));
    }

    /**
     * Inline CSS + JS for the toolbar
     */
    public static function enqueue_assets() {
        if (!is_admin_bar_showing() || !current_user_can('manage_options')) {
            return;
        }

        $is_frontend = !is_admin();
        $page_url    = $is_frontend ? self::current_frontend_url() : '';
        $can_scan    = ($is_frontend && $page_url !== '');

        $icon_uri = self::ICON_DATA_URI;
        $scan_icon_uri = "data:image/svg+xml;utf8," . rawurlencode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">' .
            '<path fill="black" d="M17.65 6.35A7.96 7.96 0 0 0 12 4a8 8 0 1 0 7.74 10h-2.08A6 6 0 1 1 12 6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>' .
            '</svg>'
        );
        $css = <<<CSS
            #wpadminbar .aacb-bar-icon{display:inline-block;width:18px;height:18px;margin:5px 6px 0 0;vertical-align:top;background-color:currentColor;-webkit-mask:url('{$icon_uri}') center / contain no-repeat;mask:url('{$icon_uri}') center / contain no-repeat}
            #wpadminbar .aacb-bar-scan-icon{display:inline-block;width:14px;height:14px;margin-right:6px;vertical-align:-2px;background-color:currentColor;-webkit-mask:url("{$scan_icon_uri}") center / contain no-repeat;mask:url("{$scan_icon_uri}") center / contain no-repeat}
            #wpadminbar .aacb-bar-score{display:inline-block;margin-left:6px;padding:0 6px;border-radius:8px;font-size:11px;font-weight:600;line-height:18px;vertical-align:middle}
            #wpadminbar .aacb-bar-score--good{background:#0f9d58;color:#fff}
            #wpadminbar .aacb-bar-score--warn{background:#f4b400;color:#1f1f1f}
            #wpadminbar .aacb-bar-score--bad{background:#d93025;color:#fff}
            #wpadminbar .aacb-bar-scan-trigger.is-busy>.ab-item{opacity:.6;pointer-events:none}
            #wpadminbar .aacb-bar-scan-trigger.is-busy .aacb-bar-scan-icon{animation:aacb-spin 1s linear infinite}
            @keyframes aacb-spin{from{transform:rotate(0)}to{transform:rotate(360deg)}}
            CSS;
        wp_register_style('aacb-admin-bar', false, array(), AACB_VERSION);
        wp_enqueue_style('aacb-admin-bar');
        wp_add_inline_style('aacb-admin-bar', $css);

        if (!$can_scan) {
            return;
        }

        // JS — bind click on the scan trigger, POST to admin-ajax, toast.
        $handle = 'aacb-admin-bar-js';
        wp_register_script($handle, false, array(), AACB_VERSION, true);
        wp_enqueue_script($handle);
        wp_localize_script($handle, 'AACB_AdminBar', array(
            'ajaxUrl'      => admin_url('admin-ajax.php'),
            'scanNonce'    => wp_create_nonce('aacb_scan_this_page'),
            'statusNonce'  => wp_create_nonce('aacb_scan_status'),
            'scoreNonce'   => wp_create_nonce('aacb_page_score'),
            'pageUrl'      => $page_url,
            'pollIntervalMs' => 5000,
            'pollMaxMs'      => 180000,
            'i18n'    => array(
                'running'  => __('Scanning page…', 'allaccessible'),
                'done'     => __('Scan complete. Score: %d%%', 'allaccessible'),
                'doneNoScore' => __('Scan complete. Refresh to see updated score.', 'allaccessible'),
                'failed'   => __('Scan failed. Try again or check the dashboard.', 'allaccessible'),
                'timeout'  => __('Scan is still running — check back in a minute.', 'allaccessible'),
                'error'    => __('Could not start scan: ', 'allaccessible'),
                'rate'     => __('Scan rate limit reached. Try again later.', 'allaccessible'),
            ),
        ));
        $js = <<<JS
(function(){
  if (typeof document === 'undefined') return;
  document.addEventListener('DOMContentLoaded', function(){
    lazyFetchScore();

    var trigger = document.getElementById('wp-admin-bar-allaccessible-scan-this');
    if (!trigger) return;
    var anchor = trigger.querySelector('a.ab-item') || trigger;

    anchor.addEventListener('click', function(e){
      e.preventDefault();
      if (trigger.classList.contains('is-busy')) return;
      trigger.classList.add('is-busy');

      var fd = new FormData();
      fd.append('action', 'aacb_scan_this_page');
      fd.append('_ajax_nonce', AACB_AdminBar.scanNonce);
      fd.append('page_url', AACB_AdminBar.pageUrl);

      fetch(AACB_AdminBar.ajaxUrl, {method:'POST', credentials:'same-origin', body:fd})
        .then(function(r){ return r.json().catch(function(){ return null; }); })
        .then(function(j){
          if (j && j.success && j.data && j.data.jobId) {
            var toast = aacbToast(AACB_AdminBar.i18n.running, 'progress', true);
            startPolling(j.data.jobId, toast);
          } else {
            trigger.classList.remove('is-busy');
            var msg = (j && j.data && j.data.message) ? j.data.message : (AACB_AdminBar.i18n.error + 'unknown');
            if (j && j.data && j.data.code === 429) msg = AACB_AdminBar.i18n.rate;
            aacbToast(msg, 'error', false);
          }
        })
        .catch(function(){
          trigger.classList.remove('is-busy');
          aacbToast(AACB_AdminBar.i18n.error + 'network', 'error', false);
        });
    });
  });

  function startPolling(jobId, toast){
    var started = Date.now();
    var trigger = document.getElementById('wp-admin-bar-allaccessible-scan-this');
    var timer = setInterval(function(){
      if (Date.now() - started > AACB_AdminBar.pollMaxMs) {
        clearInterval(timer);
        if (trigger) trigger.classList.remove('is-busy');
        aacbToastUpdate(toast, AACB_AdminBar.i18n.timeout, 'error', false);
        return;
      }
      var fd = new FormData();
      fd.append('action', 'aacb_scan_status');
      fd.append('_ajax_nonce', AACB_AdminBar.statusNonce);
      fd.append('job_id', jobId);
      fd.append('page_url', AACB_AdminBar.pageUrl);
      fetch(AACB_AdminBar.ajaxUrl, {method:'POST', credentials:'same-origin', body:fd})
        .then(function(r){ return r.json().catch(function(){ return null; }); })
        .then(function(j){
          if (!j || !j.success || !j.data) return; // transient error — keep polling
          var status = j.data.status;
          var score = j.data.score;
          if (status === 'done') {
            clearInterval(timer);
            if (trigger) trigger.classList.remove('is-busy');
            updateBarBadge(score);
            document.dispatchEvent(new CustomEvent('aacb:page-score-refresh', {
              detail: { pageUrl: AACB_AdminBar.pageUrl, score: score }
            }));
            var msg = (typeof score === 'number')
              ? AACB_AdminBar.i18n.done.replace('%d', score)
              : AACB_AdminBar.i18n.doneNoScore;
            aacbToastUpdate(toast, msg, 'success', false);
          } else if (status === 'failed') {
            clearInterval(timer);
            if (trigger) trigger.classList.remove('is-busy');
            aacbToastUpdate(toast, AACB_AdminBar.i18n.failed, 'error', false);
          } else {
            // running / queued — keep toast in progress state
            aacbToastUpdate(toast, AACB_AdminBar.i18n.running, 'progress', true);
          }
        })
        .catch(function(){ /* transient — keep polling */ });
    }, AACB_AdminBar.pollIntervalMs);
  }

  function updateBarBadge(score){
    if (typeof score !== 'number') return;
    var root = document.getElementById('wp-admin-bar-allaccessible');
    if (!root) return;
    var chip = root.querySelector('.aacb-bar-score');
    var tier = score >= 90 ? 'good' : (score >= 70 ? 'warn' : 'bad');
    if (chip) {
      chip.textContent = score + '%';
      chip.className = 'aacb-bar-score aacb-bar-score--' + tier;
    } else {
      var label = root.querySelector('.ab-item');
      if (label) {
        var span = document.createElement('span');
        span.className = 'aacb-bar-score aacb-bar-score--' + tier;
        span.textContent = score + '%';
        label.appendChild(document.createTextNode(' '));
        label.appendChild(span);
      }
    }
  }

  function lazyFetchScore(){
    var root = document.getElementById('wp-admin-bar-allaccessible');
    if (!root) return;
    // If chip is already painted (admin context with cached aggregation),
    // no fetch needed.
    if (root.querySelector('.aacb-bar-score')) return;
    if (!AACB_AdminBar.pageUrl || !AACB_AdminBar.scoreNonce) return;
    var fd = new FormData();
    fd.append('action', 'aacb_page_score');
    fd.append('_ajax_nonce', AACB_AdminBar.scoreNonce);
    fd.append('page_url', AACB_AdminBar.pageUrl);
    fetch(AACB_AdminBar.ajaxUrl, {method:'POST', credentials:'same-origin', body:fd})
      .then(function(r){ return r.json().catch(function(){ return null; }); })
      .then(function(j){
        if (j && j.success && j.data && typeof j.data.score === 'number') {
          updateBarBadge(j.data.score);
        }
        // Silent on no-score — admin bar just stays brand-only.
      })
      .catch(function(){ /* silent */ });
  }

  function aacbToast(message, kind, sticky){
    var t = document.createElement('div');
    t.className = 'aacb-toast aacb-toast--' + kind;
    t.textContent = message;
    var bg = (kind === 'success') ? '#0f9d58' : (kind === 'error' ? '#d93025' : '#7c3aed');
    t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:100000;padding:12px 20px;border-radius:6px;font:14px/1.4 system-ui,sans-serif;color:#fff;box-shadow:0 6px 24px rgba(0,0,0,.18);transition:opacity .4s;background:'+bg;
    document.body.appendChild(t);
    if (!sticky) scheduleDismiss(t);
    return t;
  }
  function aacbToastUpdate(t, message, kind, sticky){
    if (!t) return aacbToast(message, kind, sticky);
    t.textContent = message;
    t.className = 'aacb-toast aacb-toast--' + kind;
    var bg = (kind === 'success') ? '#0f9d58' : (kind === 'error' ? '#d93025' : '#7c3aed');
    t.style.background = bg;
    if (!sticky) scheduleDismiss(t);
  }
  function scheduleDismiss(t){
    setTimeout(function(){ t.style.opacity = '0'; }, 4000);
    setTimeout(function(){ if (t.parentNode) t.parentNode.removeChild(t); }, 4600);
  }
})();
JS;
        wp_add_inline_script($handle, $js);
    }

    /**
     * AJAX entry — kicks off a single-page scan via ApiClient.
     */
    public static function ajax_scan_this_page() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'allaccessible'), 'code' => 403), 403);
        }
        check_ajax_referer('aacb_scan_this_page');

        $page_url = isset($_POST['page_url']) ? esc_url_raw(wp_unslash($_POST['page_url'])) : '';
        if (empty($page_url)) {
            wp_send_json_error(array('message' => __('Missing page URL.', 'allaccessible'), 'code' => 400), 400);
        }

        $client = AllAccessible_ApiClient::get_instance();
        $result = $client->trigger_page_scan($page_url, 'wp_admin_bar');

        AllAccessible_Debug::api('AdminBar::ajax_scan_this_page', array('page_url' => $page_url), $result);

        if (is_wp_error($result)) {
            $data    = $result->get_error_data();
            $status  = is_array($data) && isset($data['status']) ? (int) $data['status'] : 500;
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
                'code'    => $status,
            ), $status);
        }

        wp_send_json_success(array(
            'jobId'   => isset($result['jobId']) ? (int) $result['jobId'] : null,
            'pageUrl' => $page_url,
        ));
    }

    /**
     * AJAX poll handler
     */
    public static function ajax_scan_status() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'allaccessible'), 'code' => 403), 403);
        }
        check_ajax_referer('aacb_scan_status');

        $job_id   = isset($_POST['job_id']) ? (int) $_POST['job_id'] : 0;
        $page_url = isset($_POST['page_url']) ? esc_url_raw(wp_unslash($_POST['page_url'])) : '';
        if ($job_id <= 0) {
            wp_send_json_error(array('message' => __('Missing job ID.', 'allaccessible'), 'code' => 400), 400);
        }

        $client = AllAccessible_ApiClient::get_instance();
        $result = $client->get_scan_status($job_id);
        if (is_wp_error($result)) {
            $data    = $result->get_error_data();
            $status  = is_array($data) && isset($data['status']) ? (int) $data['status'] : 500;
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
                'code'    => $status,
            ), $status);
        }

        $job_status   = isset($result['job']['status']) ? (string) $result['job']['status'] : 'unknown';
        $pages_done   = isset($result['job']['pagesDone']) ? (int) $result['job']['pagesDone'] : 0;
        $pages_failed = isset($result['job']['pagesFailed']) ? (int) $result['job']['pagesFailed'] : 0;
        $total_pages  = isset($result['job']['totalPages']) ? (int) $result['job']['totalPages'] : 0;

        $score = null;
        if ($job_status === 'done' && $page_url !== '') {
            $client->bust_page_caches($page_url);
            $fresh = $client->get_page_audit(null, $page_url);
            if (!is_wp_error($fresh) && is_array($fresh)) {
                if (isset($fresh['overall_score']) && is_numeric($fresh['overall_score'])) {
                    $score = (int) $fresh['overall_score'];
                } elseif (isset($fresh['score_breakdown']['current']) && is_numeric($fresh['score_breakdown']['current'])) {
                    $score = (int) $fresh['score_breakdown']['current'];
                }
            }
        }

        wp_send_json_success(array(
            'status'      => $job_status,
            'pagesDone'   => $pages_done,
            'pagesFailed' => $pages_failed,
            'totalPages'  => $total_pages,
            'score'       => $score,
        ));
    }

    /**
     * AJAX entry — return the current per-page score for the admin bar.
     */
    public static function ajax_page_score() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied.', 'code' => 403), 403);
        }
        check_ajax_referer('aacb_page_score');

        $page_url = isset($_POST['page_url']) ? esc_url_raw(wp_unslash($_POST['page_url'])) : '';
        if (empty($page_url)) {
            wp_send_json_error(array('message' => 'Missing page URL.', 'code' => 400), 400);
        }

        $client = AllAccessible_ApiClient::get_instance();
        $audit  = $client->get_page_audit(null, $page_url);

        AllAccessible_Debug::api('AdminBar::ajax_page_score', array('page_url' => $page_url), $audit);

        if (is_wp_error($audit) || !is_array($audit)) {
            wp_send_json_success(array('score' => null));
        }

        $score = null;
        if (isset($audit['overall_score']) && is_numeric($audit['overall_score'])) {
            $score = (int) $audit['overall_score'];
        } elseif (isset($audit['score_breakdown']['current']) && is_numeric($audit['score_breakdown']['current'])) {
            $score = (int) $audit['score_breakdown']['current'];
        }
        wp_send_json_success(array('score' => $score));
    }

    /* ─── helpers ───────────────────────────────────────────────────── */

    /**
     * Resolve the URL the user is currently viewing on the frontend.
     */
    private static function current_frontend_url() {
        if (is_singular()) {
            $id = get_queried_object_id();
            if ($id) {
                $permalink = get_permalink($id);
                if ($permalink) return $permalink;
            }
        }
        if (is_front_page() || is_home()) {
            return home_url('/');
        }
        return '';
    }

    /**
     * Pull a score from a transient if one was warmed by another UI
     * surface.
     */
    private static function cached_score_label_for_context($is_frontend, $current_url) {
        $score = null;
        if ($is_frontend && $current_url !== '') {
            $key = self::PAGE_SCORE_TRANSIENT_PREFIX . md5($current_url);
            $cached = get_transient($key);
            if (is_numeric($cached)) {
                $score = (int) $cached;
            }
        } else {
            $agg = get_transient('aacb_cache_audit_aggregation_v1');
            if (is_array($agg) && isset($agg['overall_score']) && is_numeric($agg['overall_score'])) {
                $score = (int) $agg['overall_score'];
            }
        }

        if ($score === null) {
            return null;
        }
        $tier = $score >= 90 ? 'good' : ($score >= 70 ? 'warn' : 'bad');
        return array('text' => $score . '%', 'tier' => $tier);
    }

    /**
     * Build a "View page report" deep-link for the current frontend URL.
     */
    private static function page_report_url($page_url) {
        $key = self::PAGE_AUDIT_META_TRANSIENT_PREFIX . md5($page_url);
        $meta = get_transient($key);
        if (is_array($meta) && !empty($meta['audit_id']) && !empty($meta['subdomain_id'])) {
            return sprintf(
                'https://app.allaccessible.org/dashboard/audits/%d/audit/%d',
                (int) $meta['subdomain_id'],
                (int) $meta['audit_id']
            );
        }
        return null;
    }
}
