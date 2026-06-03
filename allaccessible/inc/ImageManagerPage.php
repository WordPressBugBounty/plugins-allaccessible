<?php
/**
 * Image alt text manager — admin page (admin.php?page=aacb-image-manager).
 *
 * @package AllAccessible
 * @since   2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AllAccessible_ImageManager_Page {

    const PAGE_SLUG    = 'aacb-image-manager';
    const NONCE_NAME   = 'aacb_image_manager_action';
    const DEFAULT_PER  = 24;

    public static function register() {
        add_action('admin_menu',            array(__CLASS__, 'register_menu'), 18);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_action('wp_ajax_aacb_override_image_alt', array(__CLASS__, 'ajax_override_alt'));
    }

    public static function register_menu() {
        add_submenu_page(
            'allaccessible',
            __('Image AI', 'allaccessible'),
            __('Image AI', 'allaccessible'),
            'manage_options',
            self::PAGE_SLUG,
            array(__CLASS__, 'render')
        );
    }

    public static function enqueue_assets($hook) {
        if ($hook !== 'allaccessible_page_' . self::PAGE_SLUG) return;
        wp_enqueue_style('aacb-admin', AACB_CSS . 'admin.css', array(), aacb_asset_ver('admin.css'));
        wp_enqueue_style('aacx-v2-admin', AACB_CSS . 'admin-v2.css', array(), aacb_asset_ver('admin-v2.css'));
    }

    public static function render() {
        if (!current_user_can('manage_options')) return;
        if (!get_option('aacb_wizard_completed') && !get_option('aacb_accountID')) {
            wp_redirect(admin_url('admin.php?page=allaccessible-wizard'));
            exit;
        }

        $allowed_filters = array('all', 'missing', 'ai', 'manual');
        $filter = isset($_GET['filter']) ? sanitize_key($_GET['filter']) : 'all';
        if (!in_array($filter, $allowed_filters, true)) $filter = 'all';

        $search = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';

        $page    = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $perPage = self::DEFAULT_PER;
        $gated = AllAccessible_TierGate::can('image_alt_text_manager');

        $client = AllAccessible_ApiClient::get_instance();
        $site_options = $client->get_site_options();
        $ai_quota = (is_object($site_options) && isset($site_options->aiQuota)) ? $site_options->aiQuota : null;
        $is_preview = !$gated && $ai_quota !== null;

        $data   = null;
        $err    = null;
        if ($gated || $is_preview) {
            $resp = $client->get_plugin_images(array(
                'page'    => $page,
                'perPage' => $perPage,
                'filter'  => $filter,
            ));
            if (is_wp_error($resp)) {
                $err = $resp->get_error_message();
            } else {
                $data = $resp;
            }
        }

        AllAccessible_Debug::console('ImageManagerPage', array(
            'gated'         => $gated,
            'filter'        => $filter,
            'page'          => $page,
            'per_page'      => $perPage,
            'is_error'      => $err !== null,
            'error'         => $err,
            'data_keys'     => is_array($data) ? array_keys($data) : null,
            'image_count'   => is_array($data) && isset($data['images']) ? count($data['images']) : null,
            'total'         => is_array($data) && isset($data['total']) ? $data['total'] : null,
        ));
        ?>
        <div class="wrap aacb-image-manager"
             data-nonce="<?php echo esc_attr(wp_create_nonce(self::NONCE_NAME)); ?>"
             style="margin:-10px 0 0 -20px;">
          <div class="aacx-v2">
            <div class="aacx-v2__page">

                <?php self::render_page_header(); ?>

                <?php if (!$gated && !$is_preview) : ?>
                    <?php AllAccessible_TierGate::render_upgrade_cta('image_alt_text_manager', array(
                        'heading'     => __('Image alt text is available on paid plans', 'allaccessible'),
                        'description' => __('Upgrade to review, refine, and approve every alt text on your site. Includes inline editing, manual overrides, and full media library coverage.', 'allaccessible'),
                    )); ?>
                <?php elseif ($is_preview) : ?>
                    <?php self::render_preview_banner($ai_quota); ?>
                    <?php if ($err) : ?>
                        <div class="aacx-v2__banner aacx-v2__banner--danger" role="alert">
                            <div><?php echo esc_html($err); ?></div>
                        </div>
                    <?php else : ?>
                        <?php self::render_preview_grid(is_array($data['items'] ?? null) ? $data['items'] : array(), $ai_quota); ?>
                    <?php endif; ?>
                <?php elseif ($err) : ?>
                    <div class="aacx-v2__banner aacx-v2__banner--danger" role="alert">
                        <div>
                            <strong><?php esc_html_e('Could not load image data right now.', 'allaccessible'); ?></strong>
                            <p style="margin-top:var(--aacx-space-1);font-size:var(--aacx-text-sm);">
                                <?php echo esc_html($err); ?>
                            </p>
                        </div>
                    </div>
                <?php else : ?>
                    <?php self::render_stats($data['stats'] ?? array()); ?>
                    <?php self::render_filter_bar($filter, $search, $data['stats'] ?? array()); ?>

                    <div class="aacb-grid-region" aria-live="polite" aria-atomic="false">
                        <p class="aacx-v2__help aacb-grid-status" data-role="grid-status" style="margin-bottom:var(--aacx-space-3);">
                            <?php
                            $count = is_array($data['items'] ?? null) ? count($data['items']) : 0;
                            printf(
                                /* translators: %d = number of images on this page */
                                esc_html(_n('%d image on this page.', '%d images on this page.', $count, 'allaccessible')),
                                (int) $count
                            );
                            ?>
                        </p>
                        <?php self::render_grid(is_array($data['items'] ?? null) ? $data['items'] : array()); ?>
                    </div>

                    <?php self::render_pagination($data['pagination'] ?? array(), $filter, $search); ?>
                <?php endif; ?>

            </div>

            <?php self::render_edit_modal(); ?>
          </div>
        </div>
        <?php self::render_inline_js();
    }

    /* ─── Page header ─────────────────────────────────────────────────── */

    private static function render_page_header() {
        ?>
        <header class="aacx-v2__page-header">
            <div>
                <div class="aacx-v2__page-eyebrow"><?php esc_html_e('Accessibility', 'allaccessible'); ?></div>
                <div class="aacx-v2__page-title">
                    <h1><?php esc_html_e('Image alt text', 'allaccessible'); ?></h1>
                    <span class="aacx-v2__ai-badge"><?php esc_html_e('AllAccessible AI', 'allaccessible'); ?></span>
                </div>
                <p class="aacx-v2__page-desc">
                    <?php esc_html_e('AllAccessible AI suggests descriptive alt text for every image on your site. Review and approve, or edit before it goes live.', 'allaccessible'); ?>
                </p>
            </div>
            <a href="https://app.allaccessible.org" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--secondary">
                <?php esc_html_e('Open full dashboard', 'allaccessible'); ?>
                <span aria-hidden="true">→</span>
            </a>
        </header>
        <?php
    }

    /* ─── Hero stats ──────────────────────────────────────────────────── */

    private static function render_stats($stats) {
        $total   = (int) ($stats['total']          ?? 0);
        $missing = (int) ($stats['missing']        ?? 0);
        $ai      = (int) ($stats['aiGenerated']    ?? 0);
        $manual  = (int) ($stats['manualOverride'] ?? 0);
        $with_alt = max(0, $total - $missing);
        $pct      = $total > 0 ? (int) round(($with_alt / $total) * 100) : 0;
        $ai_authored = $ai;
        $num = 'font-family:var(--aacx-font-mono);font-weight:var(--aacx-weight-bold);color:var(--aacx-text-strong);';
        $dot = '<span aria-hidden="true" style="color:var(--aacx-border);">·</span>';
        ?>
        <div class="aacx-v2__card aacx-v2__card--elevated" style="margin-bottom:var(--aacx-space-6);">
            <div class="aacx-v2__row" style="flex-wrap:wrap;align-items:center;gap:var(--aacx-space-2) var(--aacx-space-4);padding:var(--aacx-space-4) var(--aacx-space-5);font-size:var(--aacx-text-sm);color:var(--aacx-text);">
                <span><span style="<?php echo esc_attr($num); ?>"><?php echo esc_html(number_format_i18n($total)); ?></span> <?php esc_html_e('images', 'allaccessible'); ?></span>
                <?php echo $dot; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — static markup ?>
                <span class="<?php echo $pct >= 80 ? 'aacx-v2__stat-trend--up' : ''; ?>">
                    <span style="<?php echo esc_attr($num); ?>"><?php echo esc_html(number_format_i18n($with_alt)); ?></span>
                    <?php esc_html_e('with alt text', 'allaccessible'); ?>
                    <span style="color:var(--aacx-text-muted);">(<?php /* translators: %d = percentage */ printf(esc_html__('%d%%', 'allaccessible'), (int) $pct); ?>)</span>
                </span>
                <?php echo $dot; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — static markup ?>
                <span style="display:inline-flex;align-items:center;gap:var(--aacx-space-2);">
                    <span style="<?php echo esc_attr($num); ?>"><?php echo esc_html(number_format_i18n($ai_authored)); ?></span>
                    <span class="aacx-v2__ai-badge" style="font-size:0.625rem;padding:0 var(--aacx-space-2);">
                        <?php esc_html_e('AI-authored', 'allaccessible'); ?>
                    </span>
                </span>
            </div>
        </div>
        <?php
    }

    /* ─── Filter bar (dropdown + search) ─────────────────────────────── */

    private static function render_filter_bar($current, $search, $stats) {
        $options = array(
            'all'     => __('All images', 'allaccessible'),
            'missing' => __('Missing alt', 'allaccessible'),
            'ai'      => __('AI-suggested', 'allaccessible'),
            'manual'  => __('Manual', 'allaccessible'),
        );
        $base_url = admin_url('admin.php?page=' . self::PAGE_SLUG);
        ?>
        <form method="get" action="<?php echo esc_url($base_url); ?>"
              class="aacb-filter-bar"
              style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:var(--aacx-space-3);margin-bottom:var(--aacx-space-6);">
            <input type="hidden" name="page" value="<?php echo esc_attr(self::PAGE_SLUG); ?>">
            <input type="hidden" name="paged" value="1">

            <div class="aacx-v2__field" style="min-width:200px;">
                <label class="aacx-v2__label" for="aacb-filter-select"><?php esc_html_e('Show', 'allaccessible'); ?></label>
                <select class="aacx-v2__select" id="aacb-filter-select" name="filter" onchange="this.form.submit()">
                    <?php foreach ($options as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($current, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="aacx-v2__field" style="flex:1;min-width:240px;">
                <label class="aacx-v2__label" for="aacb-search-input"><?php esc_html_e('Search filename or alt text', 'allaccessible'); ?></label>
                <input class="aacx-v2__input" type="search" id="aacb-search-input" name="q"
                       value="<?php echo esc_attr($search); ?>"
                       placeholder="<?php esc_attr_e('Type to filter visible images…', 'allaccessible'); ?>"
                       autocomplete="off">
            </div>

            <button type="submit" class="aacx-v2__btn aacx-v2__btn--secondary">
                <?php esc_html_e('Apply', 'allaccessible'); ?>
            </button>
        </form>
        <?php
    }

    /* ─── Grid ──────────────────────────────────────────────────────── */

    private static function render_grid($items) {
        if (empty($items)) {
            self::render_empty_state();
            return;
        }
        ?>
        <div class="aacx-v2__grid aacx-v2__grid--3 aacb-image-grid" data-role="image-grid">
            <?php foreach ($items as $item) : self::render_tile($item); endforeach; ?>
        </div>
        <?php
    }

    private static function render_tile($item) {
        $src          = isset($item['src']) ? esc_url($item['src']) : '';
        $text         = isset($item['text']) ? (string) $item['text'] : '';
        $ai_text      = isset($item['aiText']) ? (string) $item['aiText'] : '';
        $source       = isset($item['source']) ? (string) $item['source'] : 'unknown';
        $page_url     = isset($item['pageUrl']) ? (string) $item['pageUrl'] : '';
        $id           = (int) ($item['id'] ?? 0);
        $is_missing   = ($text === '' && $ai_text === '');
        $has_override = !empty($item['hasManualOverride']);
        $is_ai        = ($source === 'ai' && !$has_override && !$is_missing);
        $filename = self::filename_from_url($src, $id);
        $tile_classes = 'aacx-v2__card aacx-v2__card--hoverable aacb-image-tile';
        if ($is_ai)      $tile_classes .= ' aacx-v2__card--ai aacb-image-tile--ai';
        if ($is_missing) $tile_classes .= ' aacb-image-tile--missing';

        // Data attributes used by JS for search filtering + bulk selection.
        $search_haystack = strtolower($filename . ' ' . $text . ' ' . $ai_text);
        ?>
        <article class="<?php echo esc_attr($tile_classes); ?>"
                 data-image-id="<?php echo esc_attr($id); ?>"
                 data-source="<?php echo esc_attr($source); ?>"
                 data-has-override="<?php echo $has_override ? '1' : '0'; ?>"
                 data-is-missing="<?php echo $is_missing ? '1' : '0'; ?>"
                 data-search="<?php echo esc_attr($search_haystack); ?>">

            <div class="aacb-image-tile__media" style="position:relative;aspect-ratio:1/1;background:var(--aacx-slate-100);overflow:hidden;">
                <?php if ($is_ai) : ?>
                    <span class="aacx-v2__ai-badge"
                          style="position:absolute;top:var(--aacx-space-2);right:var(--aacx-space-2);z-index:2;box-shadow:var(--aacx-shadow-sm);">
                        <?php esc_html_e('AI suggestion', 'allaccessible'); ?>
                    </span>
                <?php endif; ?>

                <?php if ($src) : ?>
                    <img src="<?php echo $src; ?>"
                         alt=""
                         loading="lazy"
                         style="width:100%;height:100%;object-fit:cover;display:block;"
                         onerror="this.style.display='none'; this.parentNode.classList.add('aacb-img-error');">
                <?php endif; ?>
            </div>

            <div style="padding:var(--aacx-space-4);display:flex;flex-direction:column;gap:var(--aacx-space-2);">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:var(--aacx-space-2);">
                    <p style="font-size:var(--aacx-text-xs);color:var(--aacx-text-muted);font-family:var(--aacx-font-mono);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin:0;"
                       title="<?php echo esc_attr($filename); ?>">
                        <?php echo esc_html($filename); ?>
                    </p>
                    <?php echo self::status_badge($source, $is_missing, $has_override); // pre-escaped ?>
                </div>

                <p style="font-size:var(--aacx-text-sm);color:var(--aacx-text);min-height:2.5em;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;margin:0;"
                   data-field="current-alt-text">
                    <?php
                    if ($is_missing) {
                        echo '<em style="color:var(--aacx-text-muted);">' . esc_html__('No alt text yet.', 'allaccessible') . '</em>';
                    } elseif ($text !== '') {
                        echo esc_html($text);
                    } else {
                        echo esc_html($ai_text);
                    }
                    ?>
                </p>

                <?php if ($page_url) : ?>
                    <p style="font-size:var(--aacx-text-xs);color:var(--aacx-text-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin:0;"
                       title="<?php echo esc_attr($page_url); ?>">
                        <?php esc_html_e('Found on:', 'allaccessible'); ?>
                        <a href="<?php echo esc_url($page_url); ?>" target="_blank" rel="noopener">
                            <?php echo esc_html(self::shorten_url($page_url)); ?>
                        </a>
                    </p>
                <?php endif; ?>

                <div style="display:flex;align-items:center;gap:var(--aacx-space-2);margin-top:var(--aacx-space-1);">
                    <button type="button"
                            class="aacb-edit-alt aacx-v2__btn aacx-v2__btn--secondary aacx-v2__btn--sm"
                            data-image-id="<?php echo esc_attr($id); ?>"
                            data-current-text="<?php echo esc_attr($text); ?>"
                            data-ai-text="<?php echo esc_attr($ai_text); ?>"
                            data-image-src="<?php echo esc_attr($src); ?>"
                            data-source="<?php echo esc_attr($source); ?>">
                        <?php esc_html_e('Edit', 'allaccessible'); ?>
                    </button>
                </div>
            </div>
        </article>
        <?php
    }

    /**
     * Maps the API's `source` token + missing/override state to a v2 badge.
     */
    private static function status_badge($source, $is_missing, $has_override) {
        if ($is_missing) {
            return '<span class="aacx-v2__badge aacx-v2__badge--warn">' .
                esc_html__('Needs alt', 'allaccessible') . '</span>';
        }
        return '<span class="aacx-v2__badge aacx-v2__badge--ok">' .
            esc_html__('Set', 'allaccessible') . '</span>';
    }

    private static function filename_from_url($url, $id) {
        if (!$url) {
            return sprintf(
                /* translators: %d = image ID */
                __('Image #%d', 'allaccessible'),
                (int) $id
            );
        }
        $parts = wp_parse_url($url);
        $path  = isset($parts['path']) ? $parts['path'] : '';
        $name  = basename($path);
        if ($name === '' || $name === '/') {
            return sprintf(__('Image #%d', 'allaccessible'), (int) $id);
        }
        return $name;
    }

    private static function shorten_url($url) {
        $parts = wp_parse_url($url);
        $path  = isset($parts['path']) ? $parts['path'] : $url;
        return mb_strlen($path) > 40 ? mb_substr($path, 0, 37) . '…' : $path;
    }

    /* ─── Free-tier preview (Aikido-style blur teaser) ──────────────── */

    /**
     * Top banner for free-tier preview mode. 
     */
    private static function render_preview_banner($ai_quota) {
        $used   = (int) ($ai_quota->altTextUsed ?? 0);
        $max    = (int) ($ai_quota->altTextMax ?? 10);
        ?>
        <div class="aacb-ai-preview-banner aacx-v2__card" style="margin-bottom: var(--aacx-space-6); padding: var(--aacx-space-5); background: linear-gradient(135deg, rgba(139,92,246,0.04), rgba(99,102,241,0.04)); border: 1px solid var(--aacx-slate-200);">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: var(--aacx-space-4); flex-wrap: wrap;">
                <div>
                    <div class="aacx-v2__page-eyebrow" style="color: #6d28d9;">
                        <span aria-hidden="true">✦</span> <?php esc_html_e('AllAccessible AI · Free preview', 'allaccessible'); ?>
                    </div>
                    <h2 style="margin: 4px 0 6px; font-size: 1.1rem;">
                        <?php printf(
                            esc_html__('You\'ve used %1$d of %2$d preview alt-texts on this site', 'allaccessible'),
                            $used, $max
                        ); ?>
                    </h2>
                    <p style="margin: 0; font-size: var(--aacx-text-sm); color: var(--aacx-text-muted); max-width: 60ch;">
                        <?php esc_html_e('Real AI output, drafted for your real images. Upgrade to unlock unlimited alt-text generation for every image on your site.', 'allaccessible'); ?>
                    </p>
                </div>
                <a href="https://app.allaccessible.org/billing" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--primary">
                    <?php esc_html_e('Unlock unlimited', 'allaccessible'); ?>
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Free-tier preview grid: first N items render real (the ones the AI
     * already drafted), remainder render with a blur filter on the caption
     * and a single upgrade CTA below. Honest preview — don't lie about counts.
     */
    private static function render_preview_grid($items, $ai_quota) {
        if (empty($items)) {
            self::render_empty_state();
            return;
        }
        $max = (int) ($ai_quota->altTextMax ?? 10);
        $idx = 0;
        ?>
        <div class="aacb-image-grid">
            <?php foreach ($items as $item) :
                $is_locked = $idx >= $max;
                if ($is_locked) {
                    echo '<div class="aacb-locked-card" aria-hidden="true">';
                }
                self::render_tile($item);
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
                        esc_html__('Unlock AI alt text for all %d images on your site', 'allaccessible'),
                        (int) $idx
                    ); ?>
                </p>
                <a href="https://app.allaccessible.org/billing" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--primary">
                    <?php esc_html_e('Upgrade — starts at $10/month', 'allaccessible'); ?>
                </a>
            </div>
        <?php endif; ?>
        <?php
    }

    /* ─── Empty state ──────────────────────────────────────────────── */

    private static function render_empty_state() {
        ?>
        <div class="aacx-v2__card">
            <div class="aacx-v2__empty">
                <svg class="aacx-v2__empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <path d="M21 15l-5-5L5 21"/>
                </svg>
                <h2 class="aacx-v2__empty-title"><?php esc_html_e('No images discovered yet', 'allaccessible'); ?></h2>
                <p style="max-width:46ch;margin:0 auto var(--aacx-space-5);">
                    <?php esc_html_e('Scan your site to discover images. AllAccessible AI writes alt text for each one automatically. You can review or override any entry from this page.', 'allaccessible'); ?>
                </p>
                <a href="https://app.allaccessible.org" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--primary">
                    <?php esc_html_e('Scan your site to discover images', 'allaccessible'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /* ─── Pagination ──────────────────────────────────────────────── */

    private static function render_pagination($pagination, $filter, $search = '') {
        $page       = (int) ($pagination['page']       ?? 1);
        $total      = (int) ($pagination['total']      ?? 0);
        $totalPages = (int) ($pagination['totalPages'] ?? 1);
        if ($totalPages <= 1) return;

        $base = admin_url('admin.php?page=' . self::PAGE_SLUG);
        $prev = max(1, $page - 1);
        $next = min($totalPages, $page + 1);
        $q    = array('filter' => $filter);
        if ($search !== '') $q['q'] = $search;
        ?>
        <nav class="aacx-v2__row aacx-v2__row--between" style="margin-top:var(--aacx-space-8);" aria-label="<?php esc_attr_e('Image pagination', 'allaccessible'); ?>">
            <p class="aacx-v2__help">
                <?php printf(
                    /* translators: 1: current page, 2: total pages, 3: total items */
                    esc_html__('Page %1$d of %2$d (%3$s images)', 'allaccessible'),
                    $page,
                    $totalPages,
                    esc_html(number_format_i18n($total))
                ); ?>
            </p>
            <div class="aacx-v2__row">
                <a href="<?php echo esc_url(add_query_arg($q + array('paged' => $prev), $base)); ?>"
                   class="aacx-v2__btn aacx-v2__btn--secondary aacx-v2__btn--sm"
                   <?php echo $page <= 1 ? 'aria-disabled="true" tabindex="-1" style="pointer-events:none;opacity:0.5;"' : ''; ?>>
                    <?php esc_html_e('Previous', 'allaccessible'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg($q + array('paged' => $next), $base)); ?>"
                   class="aacx-v2__btn aacx-v2__btn--secondary aacx-v2__btn--sm"
                   <?php echo $page >= $totalPages ? 'aria-disabled="true" tabindex="-1" style="pointer-events:none;opacity:0.5;"' : ''; ?>>
                    <?php esc_html_e('Next', 'allaccessible'); ?>
                </a>
            </div>
        </nav>
        <?php
    }

    /* ─── Edit modal (native <dialog> for focus trap) ──────────────── */

    private static function render_edit_modal() {
        ?>
        <dialog id="aacb-edit-alt-modal"
                class="aacb-edit-alt-modal"
                aria-labelledby="aacb-edit-alt-title"
                style="padding:0;border:none;border-radius:var(--aacx-radius-lg);max-width:560px;width:92%;background:var(--aacx-surface);box-shadow:var(--aacx-shadow-xl);">
            <form method="dialog" class="aacx-v2" style="margin:0;">
                <header style="padding:var(--aacx-space-5) var(--aacx-space-6);border-bottom:1px solid var(--aacx-border);display:flex;align-items:center;justify-content:space-between;gap:var(--aacx-space-3);">
                    <h2 id="aacb-edit-alt-title" style="margin:0;font-size:var(--aacx-text-xl);"><?php esc_html_e('Edit alt text', 'allaccessible'); ?></h2>
                    <button type="button" class="aacx-v2__btn aacx-v2__btn--ghost aacx-v2__btn--sm" id="aacb-edit-alt-close" aria-label="<?php esc_attr_e('Close', 'allaccessible'); ?>">×</button>
                </header>

                <div style="padding:var(--aacx-space-6);display:flex;flex-direction:column;gap:var(--aacx-space-4);">
                    <div style="background:var(--aacx-slate-100);border-radius:var(--aacx-radius-md);overflow:hidden;aspect-ratio:16/9;display:flex;align-items:center;justify-content:center;">
                        <img id="aacb-edit-alt-preview" alt="" style="width:100%;height:100%;object-fit:cover;display:block;">
                    </div>

                    <div id="aacb-edit-alt-ai-suggestion"
                         class="aacx-v2__banner aacx-v2__banner--ai"
                         hidden
                         style="flex-direction:column;align-items:flex-start;gap:var(--aacx-space-2);">
                        <span class="aacx-v2__ai-badge"><?php esc_html_e('AllAccessible AI suggests', 'allaccessible'); ?></span>
                        <p data-field="ai-suggestion-text" style="margin:0;font-size:var(--aacx-text-sm);color:var(--aacx-ai-800);"></p>
                        <button type="button"
                                class="aacx-v2__btn aacx-v2__btn--ghost aacx-v2__btn--sm"
                                id="aacb-edit-alt-use-suggestion"
                                style="align-self:flex-start;color:var(--aacx-ai-700);">
                            <?php esc_html_e('Use this suggestion', 'allaccessible'); ?>
                        </button>
                    </div>

                    <div class="aacx-v2__field">
                        <label class="aacx-v2__label" for="aacb-edit-alt-textarea"><?php esc_html_e('Alt text', 'allaccessible'); ?></label>
                        <textarea id="aacb-edit-alt-textarea"
                                  class="aacx-v2__textarea"
                                  rows="3"
                                  maxlength="500"></textarea>
                        <p class="aacx-v2__help">
                            <?php esc_html_e('Aim for 1–2 sentences describing the image\'s purpose, not its appearance.', 'allaccessible'); ?>
                        </p>
                    </div>

                    <p id="aacb-edit-alt-error" hidden
                       class="aacx-v2__banner aacx-v2__banner--danger"
                       style="font-size:var(--aacx-text-sm);"></p>
                </div>

                <footer style="padding:var(--aacx-space-4) var(--aacx-space-6);border-top:1px solid var(--aacx-border);background:var(--aacx-surface-alt);display:flex;align-items:center;justify-content:flex-end;gap:var(--aacx-space-2);">
                    <button type="button" class="aacx-v2__btn aacx-v2__btn--secondary" id="aacb-edit-alt-cancel">
                        <?php esc_html_e('Cancel', 'allaccessible'); ?>
                    </button>
                    <button type="button" class="aacx-v2__btn aacx-v2__btn--primary" id="aacb-edit-alt-save">
                        <?php esc_html_e('Save', 'allaccessible'); ?>
                    </button>
                </footer>
            </form>
        </dialog>

        <style>
            #aacb-edit-alt-modal::backdrop {
                background: rgba(15, 23, 42, 0.6);
                backdrop-filter: blur(2px);
            }
        </style>
        <?php
    }

    /* ─── Sticky bulk-actions bar ─────────────────────────────────── */

    /* ─── Inline JS — modal, search, save via AJAX ──── */

    private static function render_inline_js() {
        ?>
        <script>
        (function() {
            const root  = document.querySelector('.aacb-image-manager');
            if (!root) return;
            const nonce = root.dataset.nonce;
            const modal = document.getElementById('aacb-edit-alt-modal');
            const ta    = document.getElementById('aacb-edit-alt-textarea');
            const prev  = document.getElementById('aacb-edit-alt-preview');
            const aiBox = document.getElementById('aacb-edit-alt-ai-suggestion');
            const errEl = document.getElementById('aacb-edit-alt-error');
            const saveBtn = document.getElementById('aacb-edit-alt-save');
            const useSugBtn = document.getElementById('aacb-edit-alt-use-suggestion');
            const closeBtn = document.getElementById('aacb-edit-alt-close');
            const cancelBtn = document.getElementById('aacb-edit-alt-cancel');
            const gridStatus = root.querySelector('[data-role="grid-status"]');
            const searchInput = document.getElementById('aacb-search-input');

            let currentId = 0;
            let currentCard = null;
            let lastFocused = null;

            /* ─── Modal open/close ─────────────────────────────────── */
            function openModal(btn) {
                currentId   = parseInt(btn.dataset.imageId, 10) || 0;
                currentCard = btn.closest('article');
                lastFocused = btn;
                ta.value      = btn.dataset.currentText || '';
                prev.src      = btn.dataset.imageSrc    || '';
                const aiText  = btn.dataset.aiText      || '';
                if (aiText && aiText !== ta.value) {
                    aiBox.hidden = false;
                    aiBox.querySelector('[data-field="ai-suggestion-text"]').textContent = aiText;
                } else {
                    aiBox.hidden = true;
                }
                hideError();
                if (typeof modal.showModal === 'function') {
                    modal.showModal();
                } else {
                    // Fallback for browsers without <dialog>
                    modal.setAttribute('open', '');
                }
                setTimeout(function() { ta.focus(); ta.select(); }, 20);
            }
            function closeModal() {
                if (typeof modal.close === 'function') {
                    try { modal.close(); } catch(e) { modal.removeAttribute('open'); }
                } else {
                    modal.removeAttribute('open');
                }
                currentId   = 0;
                currentCard = null;
                if (lastFocused) { lastFocused.focus(); }
            }
            function showError(msg) {
                errEl.textContent = msg;
                errEl.hidden = false;
            }
            function hideError() {
                errEl.textContent = '';
                errEl.hidden = true;
            }

            // Saved-toast — sticky 8s with explanation copy.
            function aacbSavedNotice() {
                const msg = <?php echo wp_json_encode(__('Saved. Updates may take up to a minute to appear after refresh as the change propagates through our system.', 'allaccessible')); ?>;
                const t = document.createElement('div');
                t.textContent = msg;
                t.setAttribute('role', 'status');
                t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:100000;padding:14px 22px;border-radius:8px;font:14px/1.4 system-ui,sans-serif;color:#fff;box-shadow:0 6px 24px rgba(0,0,0,.18);transition:opacity .4s;max-width:90vw;background:#0f9d58;';
                document.body.appendChild(t);
                setTimeout(function(){ t.style.opacity = '0'; }, 7600);
                setTimeout(function(){ if (t.parentNode) t.parentNode.removeChild(t); }, 8200);
            }

            /* ─── Open from any "Edit" button in the grid ───────────── */
            root.addEventListener('click', function(e) {
                const btn = e.target.closest('.aacb-edit-alt');
                if (btn) {
                    e.preventDefault();
                    openModal(btn);
                }
            });
            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);
            modal.addEventListener('cancel', function(e) { e.preventDefault(); closeModal(); });
            modal.addEventListener('click', function(e) {
                // Close when clicking the backdrop (the <dialog> itself, not its form)
                if (e.target === modal) closeModal();
            });

            useSugBtn.addEventListener('click', function() {
                const txt = aiBox.querySelector('[data-field="ai-suggestion-text"]').textContent || '';
                ta.value = txt;
                ta.focus();
            });

            /* ─── Save via AJAX ─────────────────────────────────────── */
            saveBtn.addEventListener('click', async function() {
                if (!currentId) return;
                const value = ta.value.trim();
                if (value.length === 0) {
                    showError(<?php echo wp_json_encode(__('Alt text is required.', 'allaccessible')); ?>);
                    return;
                }
                if (value.length > 500) {
                    showError(<?php echo wp_json_encode(__('Alt text must be 500 characters or fewer.', 'allaccessible')); ?>);
                    return;
                }
                saveBtn.disabled = true;
                const origLabel = saveBtn.textContent;
                saveBtn.textContent = <?php echo wp_json_encode(__('Saving…', 'allaccessible')); ?>;
                try {
                    const fd = new FormData();
                    fd.append('action',    'aacb_override_image_alt');
                    fd.append('_wpnonce',  nonce);
                    fd.append('image_id',  String(currentId));
                    fd.append('text',      value);
                    const r = await fetch(ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' });
                    const data = await r.json();
                    if (!r.ok || !data || data.success !== true) {
                        throw new Error((data && data.data && data.data.message) || <?php echo wp_json_encode(__('Save failed', 'allaccessible')); ?>);
                    }
                    // Update the card in place — no full page reload.
                    if (currentCard) {
                        const altEl = currentCard.querySelector('[data-field="current-alt-text"]');
                        if (altEl) altEl.textContent = value;
                        const btn = currentCard.querySelector('.aacb-edit-alt');
                        if (btn) {
                            btn.dataset.currentText = value;
                            btn.dataset.source = 'manual';
                        }
                        currentCard.dataset.source = 'manual';
                        currentCard.dataset.hasOverride = '1';
                        currentCard.dataset.isMissing = '0';
                        currentCard.classList.remove('aacx-v2__card--ai', 'aacb-image-tile--ai', 'aacb-image-tile--missing');
                    }
                    closeModal();
                    aacbSavedNotice();
                } catch (err) {
                    showError(err.message || <?php echo wp_json_encode(__('Save failed. Please try again.', 'allaccessible')); ?>);
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.textContent = origLabel;
                }
            });

            /* ─── Client-side search filter ─────────────────────────── */
            function applySearch() {
                if (!searchInput) return;
                const q = (searchInput.value || '').trim().toLowerCase();
                const tiles = root.querySelectorAll('.aacb-image-tile');
                let visible = 0;
                tiles.forEach(function(tile) {
                    const hay = tile.dataset.search || '';
                    const match = q === '' || hay.indexOf(q) !== -1;
                    tile.hidden = !match;
                    if (match) visible++;
                });
                if (gridStatus) {
                    if (q === '') {
                        gridStatus.textContent = <?php echo wp_json_encode(__('Showing all images on this page.', 'allaccessible')); ?>;
                    } else {
                        const tpl = <?php echo wp_json_encode(__('Showing %d images matching your search.', 'allaccessible')); ?>;
                        gridStatus.textContent = tpl.replace('%d', String(visible));
                    }
                }
            }
            if (searchInput) {
                searchInput.addEventListener('input', applySearch);
                // If a `q` arrived in the URL, apply it on load too.
                if (searchInput.value) applySearch();
            }
        })();
        </script>
        <?php
    }

    /* ─── AJAX bridge for manual override ──────────────────────── */

    public static function ajax_override_alt() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'allaccessible')), 403);
        }
        check_ajax_referer(self::NONCE_NAME);

        $image_id = isset($_POST['image_id']) ? (int) $_POST['image_id'] : 0;
        $text     = isset($_POST['text']) ? wp_unslash($_POST['text']) : '';
        $text     = is_string($text) ? sanitize_text_field($text) : '';

        if ($image_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid image ID', 'allaccessible')), 400);
        }
        if ($text === '' || mb_strlen($text) > 500) {
            wp_send_json_error(array('message' => __('Alt text must be 1–500 characters', 'allaccessible')), 400);
        }

        $client = AllAccessible_ApiClient::get_instance();
        $result = $client->override_image_alt_text($image_id, $text);
        if (is_wp_error($result)) {
            $err_data = $result->get_error_data();
            $upstream = is_array($err_data) && isset($err_data['status']) ? (int) $err_data['status'] : 0;
            $status   = ($upstream >= 400 && $upstream < 600) ? $upstream : 502;
            wp_send_json_error(
                array(
                    'message'  => $result->get_error_message(),
                    'code'     => $result->get_error_code(),
                    'upstream' => $upstream ?: null,
                ),
                $status
            );
        }
        wp_send_json_success($result);
    }
}

add_action('plugins_loaded', function() {
    AllAccessible_ImageManager_Page::register();
});
