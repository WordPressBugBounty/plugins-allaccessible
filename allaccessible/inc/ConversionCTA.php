<?php
/**
 * Smart Conversion CTAs
 *
 * Shows contextual upgrade messages based on usage patterns
 *
 * @package AllAccessible
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * Render smart CTA based on user behavior
 *
 * @param object $site_options Site options from /validate
 * @param string $account_tier Current tier
 * @param string $addon_url URL to addon/billing page
 */
function aacb_render_smart_cta($site_options, $account_tier, $addon_url) {
    if (!$site_options || is_wp_error($site_options)) {
        return;
    }

    // Don't show to enterprise users (highest tier)
    if ($account_tier === 'enterprise') {
        return;
    }

    $exceeded = $site_options->exceeded ?? false;
    $exceeded_limits = $site_options->exceededLimits ?? array();
    $usage = $site_options->usageSummary ?? null;

    // Determine CTA strategy
    $cta_type = 'default';
    $cta_title = '';
    $cta_message = '';
    $cta_button = __('Upgrade Now', 'allaccessible');
    $cta_url = $addon_url; // Use app addon URL, not marketing site

    if ($exceeded && !empty($exceeded_limits)) {
        // URGENT: They've exceeded limits
        $cta_type = 'urgent';
        $cta_title = __('Usage Limit Exceeded!', 'allaccessible');
        $cta_message = sprintf(
            __('Your widget is currently using free features only. Upgrade to %s to restore premium functionality and increase your limits.', 'allaccessible'),
            '<strong>' . ($account_tier === 'free' ? 'Starter' : 'Enterprise') . '</strong>'
        );
        $cta_button = $account_tier === 'free' ? __('Upgrade to Starter', 'allaccessible') : __('Upgrade to Enterprise', 'allaccessible');

    } elseif ($usage) {
        // Check if approaching limits (>80%)
        $approaching_limit = false;
        $approaching_metric = '';

        foreach ($usage as $key => $metric) {
            if (isset($metric->percent) && $metric->percent >= 80 && $metric->percent < 100) {
                $approaching_limit = true;
                $approaching_metric = str_replace('_', ' ', ucwords($key, '_'));
                break;
            }
        }

        if ($approaching_limit) {
            $cta_type = 'warning';
            $cta_title = __('Approaching Your Limit', 'allaccessible');
            $cta_message = sprintf(
                __('You\'re using over 80%% of your %s. Upgrade before you hit the limit to avoid service interruption.', 'allaccessible'),
                '<strong>' . $approaching_metric . '</strong>'
            );
            $cta_button = __('Upgrade Before Limit', 'allaccessible');

        } elseif ($account_tier === 'free') {
            // Free users with no issues - feature upsell
            $cta_type = 'feature';
            $cta_title = __('Unlock Premium Features', 'allaccessible');
            $cta_message = __('Get AI-powered accessibility fixes, monthly compliance audits, custom widget styling, and priority support.', 'allaccessible');
            $cta_button = __('Start 7-Day Trial', 'allaccessible');

        } elseif ($account_tier === 'starter' || $account_tier === 'legacy') {
            // Starter users - upsell add-ons
            $cta_type = 'feature';
            $cta_title = __('Scale with Add-Ons', 'allaccessible');
            $cta_message = __('Need more pageviews, AI fixes, or audits? Add custom limits to your plan or upgrade to Enterprise for unlimited access.', 'allaccessible');
            $cta_button = __('Explore Add-Ons', 'allaccessible');
        }
    }

    if (!$cta_title) {
        return; // No CTA to show
    }

    // Determine styling
    $bg_colors = array(
        'urgent' => 'aacx-bg-gradient-to-br aacx-from-red-500 aacx-to-red-600',
        'warning' => 'aacx-bg-gradient-to-br aacx-from-orange-500 aacx-to-orange-600',
        'feature' => 'aacx-bg-gradient-to-br aacx-from-aacx-primary-500 aacx-to-aacx-primary-700',
        'default' => 'aacx-bg-gradient-to-br aacx-from-aacx-secondary-500 aacx-to-aacx-secondary-600',
    );

    $bg_color = $bg_colors[$cta_type] ?? $bg_colors['default'];
    ?>

    <div class="<?php echo esc_attr($bg_color); ?> aacx-rounded-lg aacx-shadow-xl aacx-p-8 aacx-mb-6 aacx-text-white">
        <div class="aacx-max-w-4xl aacx-mx-auto aacx-text-center">
            <h2 class="aacx-text-3xl aacx-font-bold aacx-mb-3">
                <?php echo esc_html($cta_title); ?>
            </h2>
            <p class="aacx-text-lg aacx-mb-6 aacx-opacity-95">
                <?php echo $cta_message; ?>
            </p>
            <a href="<?php echo esc_url($cta_url); ?>"
               target="_blank"
               class="aacx-inline-flex aacx-items-center aacx-px-8 aacx-py-4 aacx-bg-white aacx-text-aacx-slate-900 aacx-rounded-xl aacx-font-bold aacx-text-lg aacx-shadow-2xl hover:aacx-shadow-xl hover:-aacx-translate-y-1 aacx-transition-all aacx-duration-200">
                <?php echo esc_html($cta_button); ?>
                <svg class="aacx-w-5 aacx-h-5 aacx-ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </a>
        </div>
    </div>
    <?php
}
