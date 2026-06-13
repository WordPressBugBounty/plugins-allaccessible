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
                '<strong>' . esc_html($approaching_metric) . '</strong>'
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

    // Map CTA type → admin-v2 component variants.
    // `feature` uses the AI-flavored card (gradient + sparkle button) since the
    // feature upsell highlights AllAccessible AI / agentic remediation.
    // `urgent` + `warning` use semantic banner variants for time-sensitive states.
    // `default` falls back to a featured (primary-tinted) card.
    $variants = array(
        'urgent'  => array('card' => 'aacx-v2__card aacx-v2__card--elevated', 'banner' => 'aacx-v2__banner aacx-v2__banner--danger', 'btn' => 'aacx-v2__btn aacx-v2__btn--primary aacx-v2__btn--lg'),
        'warning' => array('card' => 'aacx-v2__card aacx-v2__card--elevated', 'banner' => 'aacx-v2__banner aacx-v2__banner--warn',   'btn' => 'aacx-v2__btn aacx-v2__btn--primary aacx-v2__btn--lg'),
        'feature' => array('card' => 'aacx-v2__card aacx-v2__card--elevated aacx-v2__card--ai', 'banner' => '', 'btn' => 'aacx-v2__btn aacx-v2__btn--ai aacx-v2__btn--lg'),
        'default' => array('card' => 'aacx-v2__card aacx-v2__card--elevated aacx-v2__card--featured', 'banner' => '', 'btn' => 'aacx-v2__btn aacx-v2__btn--primary aacx-v2__btn--lg'),
    );

    $variant = $variants[$cta_type] ?? $variants['default'];
    ?>

    <div class="<?php echo esc_attr($variant['card']); ?>" style="margin-bottom: var(--aacx-space-6);">
        <div class="aacx-v2__card-body">
            <?php if ($variant['banner']): ?>
                <div class="<?php echo esc_attr($variant['banner']); ?>" style="margin-bottom: var(--aacx-space-5);" role="alert">
                    <strong><?php echo esc_html($cta_title); ?></strong>
                </div>
            <?php else: ?>
                <?php if ($cta_type === 'feature'): ?>
                    <div style="margin-bottom: var(--aacx-space-3);">
                        <span class="aacx-v2__ai-badge"><?php esc_html_e('AllAccessible AI', 'allaccessible'); ?></span>
                    </div>
                <?php endif; ?>
                <h2 style="margin-bottom: var(--aacx-space-3);">
                    <?php echo esc_html($cta_title); ?>
                </h2>
            <?php endif; ?>

            <p style="font-size: var(--aacx-text-lg); margin-bottom: var(--aacx-space-6); max-width: 60ch;">
                <?php echo wp_kses($cta_message, array('strong' => array())); ?>
            </p>

            <a href="<?php echo esc_url($cta_url); ?>"
               target="_blank"
               class="<?php echo esc_attr($variant['btn']); ?>">
                <?php echo esc_html($cta_button); ?>
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </a>
        </div>
    </div>
    <?php
}
