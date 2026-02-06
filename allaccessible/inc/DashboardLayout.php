<?php
/**
 * Dashboard Layout - Hero Stats & Visual Improvements
 *
 * Modern card-based layout with visual hierarchy
 *
 * @package AllAccessible
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * Render dashboard hero stats section
 *
 * @param string $account_tier Current tier
 * @param object $site_options Site options from /validate
 */
function aacb_render_dashboard_hero($account_tier, $site_options) {
    $is_paid = in_array($account_tier, array('starter', 'legacy', 'enterprise'));

    // Get quick stats
    $widget_status = 'active';
    $pageviews_used = 0;
    $pageviews_limit = 0;
    $pageviews_percent = 0;

    if ($site_options && !is_wp_error($site_options)) {
        if (isset($site_options->usageSummary->pageviews_monthly)) {
            $pv = $site_options->usageSummary->pageviews_monthly;
            $pageviews_used = $pv->current ?? 0;
            $pageviews_limit = $pv->limit ?? 0;
            $pageviews_percent = $pv->percent ?? 0;
        }
    }

    // Determine status color
    if ($pageviews_percent >= 100) {
        $status_color = 'red';
        $status_bg = 'aacx-bg-red-50';
        $status_text = 'aacx-text-red-600';
        $status_border = 'aacx-border-red-200';
    } elseif ($pageviews_percent >= 80) {
        $status_color = 'orange';
        $status_bg = 'aacx-bg-orange-50';
        $status_text = 'aacx-text-orange-600';
        $status_border = 'aacx-border-orange-200';
    } else {
        $status_color = 'green';
        $status_bg = 'aacx-bg-aacx-secondary-50';
        $status_text = 'aacx-text-aacx-secondary-600';
        $status_border = 'aacx-border-aacx-secondary-200';
    }
    ?>

    <!-- Hero Stats Section -->
    <div class="aacx-grid aacx-grid-cols-1 md:aacx-grid-cols-3 aacx-gap-6 aacx-mb-8">

        <!-- Widget Status Card -->
        <div class="aacx-bg-white aacx-rounded-xl aacx-shadow-xl aacx-border-2 aacx-border-aacx-secondary-200 aacx-p-8 aacx-relative aacx-overflow-hidden">
            <div style="position: absolute; top: 0; right: 0; width: 100px; height: 100px; background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); opacity: 0.1; border-radius: 0 12px 0 100%;"></div>
            <div class="aacx-relative">
                <div class="aacx-flex aacx-items-center aacx-gap-3 aacx-mb-4">
                    <div class="aacx-w-12 aacx-h-12 aacx-bg-aacx-secondary-100 aacx-rounded-xl aacx-flex aacx-items-center aacx-justify-center">
                        <svg class="aacx-w-7 aacx-h-7 aacx-text-aacx-secondary-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                        </svg>
                    </div>
                    <span class="aacx-text-xs aacx-font-bold aacx-text-aacx-slate-500 aacx-uppercase aacx-tracking-wide"><?php _e('Widget Status', 'allaccessible'); ?></span>
                </div>
                <div class="aacx-text-4xl aacx-font-black aacx-text-aacx-secondary-600 aacx-mb-2">
                    <?php _e('Active', 'allaccessible'); ?>
                </div>
                <p class="aacx-text-sm aacx-text-aacx-slate-600 aacx-font-medium">
                    <?php _e('Live on all pages', 'allaccessible'); ?>
                </p>
            </div>
        </div>

        <!-- Current Plan Card -->
        <div class="aacx-bg-white aacx-rounded-xl aacx-shadow-xl aacx-border-2 aacx-border-aacx-primary-200 aacx-p-8 aacx-relative aacx-overflow-hidden">
            <div style="position: absolute; top: 0; right: 0; width: 100px; height: 100px; background: linear-gradient(135deg, #1d4ed8 0%, #1e3a8a 100%); opacity: 0.1; border-radius: 0 12px 0 100%;"></div>
            <div class="aacx-relative">
                <div class="aacx-flex aacx-items-center aacx-gap-3 aacx-mb-4">
                    <div class="aacx-w-12 aacx-h-12 aacx-bg-aacx-primary-100 aacx-rounded-xl aacx-flex aacx-items-center aacx-justify-center">
                        <svg class="aacx-w-7 aacx-h-7 aacx-text-aacx-primary-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM9 9a1 1 0 012 0v4a1 1 0 11-2 0V9zm1-4a1 1 0 100 2 1 1 0 000-2z"/>
                        </svg>
                    </div>
                    <span class="aacx-text-xs aacx-font-bold aacx-text-aacx-slate-500 aacx-uppercase aacx-tracking-wide"><?php _e('Current Plan', 'allaccessible'); ?></span>
                </div>
                <div class="aacx-text-4xl aacx-font-black aacx-text-aacx-primary-600 aacx-mb-2">
                    <?php
                    switch ($account_tier) {
                        case 'starter':
                            _e('Starter', 'allaccessible');
                            break;
                        case 'enterprise':
                            _e('Enterprise', 'allaccessible');
                            break;
                        case 'legacy':
                            _e('Legacy', 'allaccessible');
                            break;
                        case 'free':
                        default:
                            _e('Free', 'allaccessible');
                    }
                    ?>
                </div>
                <p class="aacx-text-sm aacx-text-aacx-slate-600 aacx-font-medium">
                    <?php echo $is_paid ? __('Premium features', 'allaccessible') : __('Basic features', 'allaccessible'); ?>
                </p>
            </div>
        </div>

        <!-- Usage Status Card -->
        <div class="aacx-bg-white aacx-rounded-xl aacx-shadow-xl aacx-border-2 <?php echo esc_attr($status_border); ?> aacx-p-8 aacx-relative aacx-overflow-hidden">
            <div style="position: absolute; top: 0; right: 0; width: 100px; height: 100px; background: <?php echo $pageviews_percent >= 100 ? 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)' : ($pageviews_percent >= 80 ? 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)' : 'linear-gradient(135deg, #16a34a 0%, #15803d 100%)'); ?>; opacity: 0.1; border-radius: 0 12px 0 100%;"></div>
            <div class="aacx-relative">
                <div class="aacx-flex aacx-items-center aacx-gap-3 aacx-mb-4">
                    <div class="aacx-w-12 aacx-h-12 <?php echo esc_attr($status_bg); ?> aacx-rounded-xl aacx-flex aacx-items-center aacx-justify-center">
                        <svg class="aacx-w-7 aacx-h-7 <?php echo esc_attr($status_text); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <span class="aacx-text-xs aacx-font-bold aacx-text-aacx-slate-500 aacx-uppercase aacx-tracking-wide"><?php _e('Usage Status', 'allaccessible'); ?></span>
                </div>
                <div class="aacx-text-4xl aacx-font-black <?php echo esc_attr($status_text); ?> aacx-mb-2">
                    <?php echo esc_html($pageviews_percent); ?>%
                </div>
                <p class="aacx-text-sm aacx-text-aacx-slate-600 aacx-font-medium">
                    <?php
                    if ($pageviews_percent >= 100) {
                        _e('Limit exceeded', 'allaccessible');
                    } elseif ($pageviews_percent >= 80) {
                        _e('Approaching limit', 'allaccessible');
                    } else {
                        printf(__('%s of %s pageviews', 'allaccessible'), number_format($pageviews_used), number_format($pageviews_limit));
                    }
                    ?>
                </p>
            </div>
        </div>

    </div>
    <?php
}

/**
 * Render section header with icon
 *
 * @param string $title Section title
 * @param string $icon_path SVG path data
 * @param string $action_url Optional action link URL
 * @param string $action_text Optional action link text
 */
function aacb_render_section_header($title, $icon_path, $action_url = '', $action_text = '') {
    ?>
    <div class="aacx-flex aacx-items-center aacx-justify-between aacx-mb-6">
        <div class="aacx-flex aacx-items-center aacx-gap-3">
            <div class="aacx-w-10 aacx-h-10 aacx-bg-aacx-primary-100 aacx-rounded-lg aacx-flex aacx-items-center aacx-justify-center">
                <svg class="aacx-w-6 aacx-h-6 aacx-text-aacx-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <?php echo $icon_path; ?>
                </svg>
            </div>
            <h2 class="aacx-text-2xl aacx-font-bold aacx-text-aacx-slate-900">
                <?php echo esc_html($title); ?>
            </h2>
        </div>
        <?php if ($action_url): ?>
        <a href="<?php echo esc_url($action_url); ?>"
           target="_blank"
           class="aacx-text-aacx-primary-600 hover:aacx-text-aacx-primary-700 aacx-font-semibold aacx-text-sm aacx-flex aacx-items-center aacx-gap-1">
            <?php echo esc_html($action_text); ?>
            <svg class="aacx-w-4 aacx-h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <?php endif; ?>
    </div>
    <?php
}
