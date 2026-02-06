<?php
/**
 * Feature Comparison Table
 *
 * Shows free vs paid features to drive conversions
 *
 * @package AllAccessible
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * Render feature comparison table
 *
 * @param string $addon_url URL to addon/billing page
 */
function aacb_render_feature_comparison($addon_url) {
    ?>
    <div class="aacx-bg-white aacx-rounded-lg aacx-shadow-sm aacx-border aacx-border-aacx-gray-200 aacx-overflow-hidden aacx-mb-6">
        <div class="aacx-p-8">
            <h2 class="aacx-text-2xl aacx-font-bold aacx-text-aacx-slate-900 aacx-mb-6 aacx-text-center">
                <?php _e('Compare Plans', 'allaccessible'); ?>
            </h2>

            <div class="aacx-overflow-x-auto">
                <table class="aacx-w-full">
                    <thead>
                        <tr class="aacx-border-b aacx-border-aacx-gray-200">
                            <th class="aacx-text-left aacx-py-3 aacx-px-4 aacx-font-bold aacx-text-aacx-slate-900">
                                <?php _e('Feature', 'allaccessible'); ?>
                            </th>
                            <th class="aacx-text-center aacx-py-3 aacx-px-4 aacx-font-bold aacx-text-aacx-slate-700">
                                <?php _e('Free', 'allaccessible'); ?>
                            </th>
                            <th class="aacx-text-center aacx-py-3 aacx-px-4 aacx-font-bold aacx-text-aacx-primary-900 aacx-bg-aacx-primary-50">
                                <?php _e('Starter', 'allaccessible'); ?>
                            </th>
                            <th class="aacx-text-center aacx-py-3 aacx-px-4 aacx-font-bold aacx-text-aacx-secondary-900 aacx-bg-aacx-secondary-50">
                                <?php _e('Enterprise', 'allaccessible'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="aacx-border-b aacx-border-aacx-gray-100">
                            <td class="aacx-py-3 aacx-px-4 aacx-text-aacx-slate-700"><?php _e('Accessibility Widget', 'allaccessible'); ?></td>
                            <td class="aacx-text-center aacx-py-3 aacx-px-4"><span class="aacx-text-aacx-secondary-600 aacx-text-xl">✓</span></td>
                            <td class="aacx-text-center aacx-py-3 aacx-px-4 aacx-bg-aacx-primary-50"><span class="aacx-text-aacx-secondary-600 aacx-text-xl">✓</span></td>
                            <td class="aacx-text-center aacx-py-3 aacx-px-4 aacx-bg-aacx-secondary-50"><span class="aacx-text-aacx-secondary-600 aacx-text-xl">✓</span></td>
                        </tr>
                        <tr class="aacx-border-b aacx-border-aacx-gray-100">
                            <td class="aacx-py-3 aacx-px-4 aacx-text-aacx-slate-700"><?php _e('Monthly Pageviews', 'allaccessible'); ?></td>
                            <td class="aacx-text-center aacx-py-3 aacx-px-4 aacx-text-aacx-slate-600 aacx-font-semibold">1,000</td>
                            <td class="aacx-text-center aacx-py-3 aacx-px-4 aacx-bg-aacx-primary-50 aacx-text-aacx-primary-900 aacx-font-bold">10,000</td>
                            <td class="aacx-text-center aacx-py-3 aacx-px-4 aacx-bg-aacx-secondary-50 aacx-text-aacx-secondary-900 aacx-font-bold">50,000</td>
                        </tr>
                        <tr class="aacx-border-b aacx-border-aacx-gray-100">
                            <td class="aacx-py-3 aacx-px-4 aacx-text-aacx-slate-700"><?php _e('AI Image Alt Text', 'allaccessible'); ?></td>
                            <td class="aacx-text-center aacx-py-3 aacx-px-4"><span class="aacx-text-aacx-slate-400">✗</span></td>
                            <td class="aacx-text-center aacx-py-3 aacx-px-4 aacx-bg-aacx-primary-50 aacx-text-aacx-primary-900 aacx-font-bold">250/mo</td>
                            <td class="aacx-text-center aacx-py-3 aacx-px-4 aacx-bg-aacx-secondary-50 aacx-text-aacx-secondary-900 aacx-font-bold">1,000/mo</td>
                        </tr>
                        <tr class="aacx-border-b aacx-border-aacx-gray-100">
                            <td class="aacx-py-3 aacx-px-4 aacx-text-aacx-slate-700"><?php _e('Compliance Audits', 'allaccessible'); ?></td>
                            <td class="aacx-text-center aacx-py-3 aacx-px-4"><span class="aacx-text-aacx-slate-400">✗</span></td>
                            <td class="aacx-text-center aacx-py-3 aacx-px-4 aacx-bg-aacx-primary-50 aacx-text-aacx-primary-900"><?php _e('Monthly', 'allaccessible'); ?></td>
                            <td class="aacx-text-center aacx-py-3 aacx-px-4 aacx-bg-aacx-secondary-50 aacx-text-aacx-secondary-900 aacx-font-bold"><?php _e('Weekly', 'allaccessible'); ?></td>
                        </tr>
                        <tr class="aacx-border-b aacx-border-aacx-gray-100">
                            <td class="aacx-py-3 aacx-px-4 aacx-text-aacx-slate-700"><?php _e('Widget Customization', 'allaccessible'); ?></td>
                            <td class="aacx-text-center aacx-py-3 aacx-px-4"><span class="aacx-text-aacx-slate-400">✗</span></td>
                            <td class="aacx-text-center aacx-py-3 aacx-px-4 aacx-bg-aacx-primary-50"><span class="aacx-text-aacx-secondary-600 aacx-text-xl">✓</span></td>
                            <td class="aacx-text-center aacx-py-3 aacx-px-4 aacx-bg-aacx-secondary-50"><span class="aacx-text-aacx-secondary-600 aacx-text-xl">✓</span></td>
                        </tr>
                        <tr class="aacx-border-b aacx-border-aacx-gray-100">
                            <td class="aacx-py-3 aacx-px-4 aacx-text-aacx-slate-700"><?php _e('VPAT Reports', 'allaccessible'); ?></td>
                            <td class="aacx-text-center aacx-py-3 aacx-px-4"><span class="aacx-text-aacx-slate-400">✗</span></td>
                            <td class="aacx-text-center aacx-py-3 aacx-px-4 aacx-bg-aacx-primary-50"><span class="aacx-text-aacx-slate-400">✗</span></td>
                            <td class="aacx-text-center aacx-py-3 aacx-px-4 aacx-bg-aacx-secondary-50"><span class="aacx-text-aacx-secondary-600 aacx-text-xl">✓</span></td>
                        </tr>
                        <tr>
                            <td class="aacx-py-3 aacx-px-4 aacx-text-aacx-slate-700"><?php _e('Priority Support', 'allaccessible'); ?></td>
                            <td class="aacx-text-center aacx-py-3 aacx-px-4"><span class="aacx-text-aacx-slate-400">✗</span></td>
                            <td class="aacx-text-center aacx-py-3 aacx-px-4 aacx-bg-aacx-primary-50"><span class="aacx-text-aacx-secondary-600 aacx-text-xl">✓</span></td>
                            <td class="aacx-text-center aacx-py-3 aacx-px-4 aacx-bg-aacx-secondary-50 aacx-text-aacx-secondary-900 aacx-font-bold"><?php _e('Dedicated', 'allaccessible'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="aacx-mt-6 aacx-text-center">
                <a href="<?php echo esc_url($addon_url); ?>"
                   target="_blank"
                   class="aacx-inline-flex aacx-items-center aacx-px-8 aacx-py-4 aacx-bg-aacx-primary-600 aacx-text-white aacx-rounded-xl aacx-font-bold aacx-text-lg aacx-shadow-xl hover:aacx-bg-aacx-primary-700 hover:aacx-shadow-2xl hover:-aacx-translate-y-1 aacx-transition-all aacx-duration-200">
                    <?php _e('Upgrade Plan', 'allaccessible'); ?>
                    <svg class="aacx-w-5 aacx-h-5 aacx-ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
    <?php
}
