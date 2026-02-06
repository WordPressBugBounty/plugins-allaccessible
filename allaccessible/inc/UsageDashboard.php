<?php
/**
 * Usage Dashboard Component
 *
 * Shows usage metrics, limits, and conversion-focused CTAs
 *
 * @package AllAccessible
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * Render usage dashboard with metrics and progress bars
 *
 * @param object $site_options Site options from /validate API
 * @param string $account_tier Current account tier
 * @param string $addon_url URL to addon/billing page
 * @param string $audits_url URL to accessibility audits page
 */
function aacb_render_usage_dashboard($site_options, $account_tier, $addon_url, $audits_url) {
    if (!$site_options || is_wp_error($site_options) || !isset($site_options->usageSummary)) {
        return;
    }

    $usage = $site_options->usageSummary;
    $is_free = ($account_tier === 'free');
    $is_paid = in_array($account_tier, array('starter', 'legacy', 'enterprise'));
    $site_id = $site_options->siteID ?? get_option('aacb_siteID');
    $sub_id = $site_options->subID ?? null;
    ?>

    <div class="aacx-bg-white aacx-rounded-xl aacx-shadow-xl aacx-border-2 aacx-border-aacx-gray-200 aacx-overflow-hidden aacx-mb-8">

        <div class="aacx-px-8 aacx-py-6 aacx-border-b-2 aacx-border-aacx-gray-200 aacx-bg-aacx-gray-50">
            <div class="aacx-flex aacx-items-center aacx-justify-between">
                <div class="aacx-flex aacx-items-center aacx-gap-4">
                    <div class="aacx-w-12 aacx-h-12 aacx-bg-aacx-primary-100 aacx-rounded-xl aacx-flex aacx-items-center aacx-justify-center">
                        <svg class="aacx-w-7 aacx-h-7 aacx-text-aacx-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="aacx-text-2xl aacx-font-bold aacx-text-aacx-slate-900">
                            <?php _e('Usage Overview', 'allaccessible'); ?>
                        </h2>
                        <p class="aacx-text-xs aacx-text-aacx-slate-600 aacx-mt-1">
                            <?php _e('Monitor your monthly usage and limits', 'allaccessible'); ?>
                        </p>
                    </div>
                </div>
                <?php if ($is_free): ?>
                <a href="<?php echo esc_url($addon_url); ?>"
                   target="_blank"
                   class="aacx-inline-flex aacx-items-center aacx-gap-2 aacx-px-5 aacx-py-2.5 aacx-bg-aacx-primary-600 aacx-text-white aacx-rounded-lg aacx-font-semibold aacx-text-sm hover:aacx-bg-aacx-primary-700 aacx-shadow-lg hover:aacx-shadow-xl aacx-transition-all">
                    <?php _e('Upgrade Plan', 'allaccessible'); ?>
                    <svg class="aacx-w-4 aacx-h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="aacx-p-8">
            <div class="aacx-grid aacx-grid-cols-1 md:aacx-grid-cols-2 aacx-gap-6">

            <?php
            // Define which metrics to show
            $metrics_to_show = array(
                'pageviews_monthly' => array(
                    'label' => __('Pageviews This Month', 'allaccessible'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>',
                ),
                'ai_images_monthly' => array(
                    'label' => __('AI Image Alt Text', 'allaccessible'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
                ),
                'audit_runs_monthly' => array(
                    'label' => __('Accessibility Audits', 'allaccessible'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                ),
                'reports_monthly' => array(
                    'label' => __('Compliance Reports', 'allaccessible'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
                ),
            );

            foreach ($metrics_to_show as $key => $config) {
                if (!isset($usage->$key)) continue;

                $metric = $usage->$key;
                $current = $metric->current;
                $limit = $metric->limit;
                $percent = $metric->percent;
                $exceeded = $metric->exceeded;

                // Determine color based on usage
                if ($exceeded) {
                    $bar_color = 'aacx-bg-red-500';
                    $text_color = 'aacx-text-red-600';
                    $bg_color = 'aacx-bg-red-50';
                } elseif ($percent >= 80) {
                    $bar_color = 'aacx-bg-orange-500';
                    $text_color = 'aacx-text-orange-600';
                    $bg_color = 'aacx-bg-orange-50';
                } elseif ($percent >= 50) {
                    $bar_color = 'aacx-bg-yellow-500';
                    $text_color = 'aacx-text-yellow-600';
                    $bg_color = 'aacx-bg-yellow-50';
                } else {
                    $bar_color = 'aacx-bg-aacx-secondary-500';
                    $text_color = 'aacx-text-aacx-secondary-600';
                    $bg_color = 'aacx-bg-aacx-secondary-50';
                }

                // Check if feature is locked (limit = 0)
                $is_locked = ($limit === 0);
                ?>

                <div class="aacx-border-2 aacx-border-aacx-gray-200 aacx-rounded-lg aacx-p-6 aacx-transition-all hover:aacx-border-aacx-primary-200 hover:aacx-shadow-md <?php echo $exceeded ? 'aacx-border-red-300 aacx-bg-red-50' : ''; ?>">
                    <div class="aacx-flex aacx-items-start aacx-justify-between aacx-mb-4">
                        <div class="aacx-flex aacx-items-center aacx-gap-3">
                            <div class="aacx-flex-shrink-0 aacx-w-10 aacx-h-10 aacx-rounded-lg aacx-flex aacx-items-center aacx-justify-center <?php echo $exceeded ? 'aacx-bg-red-100' : 'aacx-bg-aacx-gray-100'; ?>">
                                <svg class="aacx-w-6 aacx-h-6 <?php echo esc_attr($text_color); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <?php echo $config['icon']; ?>
                                </svg>
                            </div>
                            <h3 class="aacx-font-bold aacx-text-aacx-slate-900 aacx-text-base">
                                <?php echo esc_html($config['label']); ?>
                            </h3>
                        </div>
                        <?php if ($is_locked && $is_free): ?>
                        <span class="aacx-px-2 aacx-py-1 aacx-bg-orange-100 aacx-text-orange-700 aacx-rounded aacx-text-xs aacx-font-semibold">
                            🔒 <?php _e('Upgrade', 'allaccessible'); ?>
                        </span>
                        <?php elseif ($is_locked && $is_paid): ?>
                        <span class="aacx-px-2 aacx-py-1 aacx-bg-aacx-primary-100 aacx-text-aacx-primary-700 aacx-rounded aacx-text-xs aacx-font-semibold">
                            <?php _e('Manage', 'allaccessible'); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!$is_locked): ?>
                    <!-- Progress Bar -->
                    <div class="aacx-mb-3">
                        <div class="aacx-flex aacx-justify-between aacx-text-sm aacx-mb-1">
                            <span class="aacx-font-semibold aacx-text-aacx-slate-700">
                                <?php echo number_format($current); ?> / <?php echo number_format($limit); ?>
                            </span>
                            <span class="<?php echo esc_attr($text_color); ?> aacx-font-semibold">
                                <?php echo esc_html($percent); ?>%
                            </span>
                        </div>
                        <div class="aacx-w-full aacx-bg-aacx-gray-200 aacx-rounded-full aacx-h-2 aacx-overflow-hidden">
                            <div class="<?php echo esc_attr($bar_color); ?> aacx-h-2 aacx-rounded-full aacx-transition-all"
                                 style="width: <?php echo min($percent, 100); ?>%"></div>
                        </div>
                    </div>

                    <?php if ($exceeded): ?>
                    <p class="aacx-text-xs aacx-text-red-600 aacx-font-semibold">
                        ⚠️ <?php _e('Limit exceeded - Widget using free features', 'allaccessible'); ?>
                    </p>
                    <?php elseif ($percent >= 80): ?>
                    <p class="aacx-text-xs aacx-text-orange-600 aacx-font-semibold">
                        ⚠️ <?php printf(__('%s remaining', 'allaccessible'), number_format($metric->remaining)); ?>
                    </p>
                    <?php else: ?>
                    <p class="aacx-text-xs aacx-text-aacx-slate-600">
                        <?php printf(__('%s remaining', 'allaccessible'), number_format($metric->remaining)); ?>
                    </p>
                    <?php endif; ?>

                    <?php else: ?>
                    <!-- Locked Feature -->
                    <?php if ($is_free): ?>
                    <!-- Free users: Show upgrade -->
                    <p class="aacx-text-sm aacx-text-aacx-slate-600 aacx-mb-3">
                        <?php _e('Upgrade to unlock this feature', 'allaccessible'); ?>
                    </p>
                    <a href="<?php echo esc_url($addon_url); ?>"
                       target="_blank"
                       class="aacx-text-aacx-primary-600 hover:aacx-text-aacx-primary-700 aacx-font-semibold aacx-text-xs">
                        <?php _e('Upgrade Plan', 'allaccessible'); ?> →
                    </a>
                    <?php else: ?>
                    <!-- Paid users: Link to dashboard feature -->
                    <p class="aacx-text-sm aacx-text-aacx-slate-600 aacx-mb-3">
                        <?php _e('Manage in your dashboard', 'allaccessible'); ?>
                    </p>
                    <?php
                    // Build feature-specific URL
                    if ($key === 'audit_runs_monthly') {
                        $feature_url = $audits_url; // Use audits_url from API
                    } elseif ($site_id && $sub_id) {
                        // Other features use siteID/subID pattern
                        if ($key === 'reports_monthly') {
                            $feature_url = 'https://app.allaccessible.org/site/' . $site_id . '/' . $sub_id . '/reports';
                        } elseif ($key === 'api_calls_monthly') {
                            $feature_url = 'https://app.allaccessible.org/site/' . $site_id . '/' . $sub_id . '/api';
                        } else {
                            $feature_url = 'https://app.allaccessible.org/site/' . $site_id;
                        }
                    } else {
                        $feature_url = 'https://app.allaccessible.org';
                    }
                    ?>
                    <a href="<?php echo esc_url($feature_url); ?>"
                       target="_blank"
                       class="aacx-text-aacx-primary-600 hover:aacx-text-aacx-primary-700 aacx-font-semibold aacx-text-xs">
                        <?php _e('Open Dashboard', 'allaccessible'); ?> →
                    </a>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php
            }
            ?>

            </div>

            <?php if ($is_free): ?>
            <!-- Upgrade CTA for Free Users -->
            <div class="aacx-mt-6 aacx-pt-6 aacx-border-t aacx-border-aacx-gray-200 aacx-text-center">
                <p class="aacx-text-aacx-slate-600 aacx-mb-4">
                    <?php _e('Need more? Upgrade your plan or add custom limits.', 'allaccessible'); ?>
                </p>
                <a href="<?php echo esc_url($addon_url); ?>"
                   target="_blank"
                   class="aacx-inline-flex aacx-items-center aacx-px-6 aacx-py-3 aacx-bg-aacx-secondary-600 aacx-text-white aacx-rounded-lg aacx-font-semibold hover:aacx-bg-aacx-secondary-700 aacx-transition-colors">
                    <?php _e('Upgrade Plan', 'allaccessible'); ?>
                    <svg class="aacx-w-5 aacx-h-5 aacx-ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
