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
 * Parent screen (SettingsPage.php) already opens an `.aacx-v2` namespace
 * wrapper, so this partial renders inside that context.
 *
 * @param string $addon_url URL to addon/billing page
 */
function aacb_render_feature_comparison($addon_url) {
    ?>
    <div class="aacx-v2__card aacx-v2__card--elevated aacx-v2__card--featured" style="margin-bottom: var(--aacx-space-6);">
        <div class="aacx-v2__card-header" style="justify-content: center;">
            <h2 style="text-align: center;">
                <?php _e('Compare Plans', 'allaccessible'); ?>
            </h2>
        </div>
        <div class="aacx-v2__card-body">
            <div style="overflow-x: auto;">
                <table class="aacx-v2__table">
                    <thead>
                        <tr>
                            <th>
                                <?php _e('Feature', 'allaccessible'); ?>
                            </th>
                            <th style="text-align: center;">
                                <?php _e('Free', 'allaccessible'); ?>
                            </th>
                            <th style="text-align: center; background: var(--aacx-primary-50); color: var(--aacx-primary-700);">
                                <?php _e('Starter', 'allaccessible'); ?>
                            </th>
                            <th style="text-align: center; background: var(--aacx-ai-50); color: var(--aacx-ai-700);">
                                <?php _e('Enterprise', 'allaccessible'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php _e('Accessibility Widget', 'allaccessible'); ?></td>
                            <td style="text-align: center;"><span style="color: var(--aacx-ok-600); font-size: var(--aacx-text-xl);" aria-label="<?php esc_attr_e('Included', 'allaccessible'); ?>">✓</span></td>
                            <td style="text-align: center; background: var(--aacx-primary-50);"><span style="color: var(--aacx-ok-600); font-size: var(--aacx-text-xl);" aria-label="<?php esc_attr_e('Included', 'allaccessible'); ?>">✓</span></td>
                            <td style="text-align: center; background: var(--aacx-ai-50);"><span style="color: var(--aacx-ok-600); font-size: var(--aacx-text-xl);" aria-label="<?php esc_attr_e('Included', 'allaccessible'); ?>">✓</span></td>
                        </tr>
                        <tr>
                            <td><?php _e('Monthly Pageviews', 'allaccessible'); ?></td>
                            <td style="text-align: center; color: var(--aacx-text-muted); font-weight: var(--aacx-weight-semibold);">1,000</td>
                            <td style="text-align: center; background: var(--aacx-primary-50); color: var(--aacx-primary-700); font-weight: var(--aacx-weight-bold);">10,000</td>
                            <td style="text-align: center; background: var(--aacx-ai-50); color: var(--aacx-ai-700); font-weight: var(--aacx-weight-bold);"><?php esc_html_e('Unlimited', 'allaccessible'); ?></td>
                        </tr>
                        <tr>
                            <td>
                                <span class="aacx-v2__ai-badge"><?php _e('AI Image Alt Text', 'allaccessible'); ?></span>
                            </td>
                            <td style="text-align: center;"><?php esc_html_e('10 preview', 'allaccessible'); ?></td>
                            <td style="text-align: center; background: var(--aacx-primary-50); color: var(--aacx-primary-700); font-weight: var(--aacx-weight-bold);"><?php esc_html_e('Unlimited', 'allaccessible'); ?></td>
                            <td style="text-align: center; background: var(--aacx-ai-50); color: var(--aacx-ai-700); font-weight: var(--aacx-weight-bold);"><?php esc_html_e('Unlimited', 'allaccessible'); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e('Compliance Audits', 'allaccessible'); ?></td>
                            <td style="text-align: center;"><span style="color: var(--aacx-slate-400);" aria-label="<?php esc_attr_e('Not included', 'allaccessible'); ?>">✗</span></td>
                            <td style="text-align: center; background: var(--aacx-primary-50); color: var(--aacx-primary-700);"><?php _e('Monthly', 'allaccessible'); ?></td>
                            <td style="text-align: center; background: var(--aacx-ai-50); color: var(--aacx-ai-700); font-weight: var(--aacx-weight-bold);"><?php _e('Weekly', 'allaccessible'); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e('Widget Customization', 'allaccessible'); ?></td>
                            <td style="text-align: center;"><span style="color: var(--aacx-slate-400);" aria-label="<?php esc_attr_e('Not included', 'allaccessible'); ?>">✗</span></td>
                            <td style="text-align: center; background: var(--aacx-primary-50);"><span style="color: var(--aacx-ok-600); font-size: var(--aacx-text-xl);" aria-label="<?php esc_attr_e('Included', 'allaccessible'); ?>">✓</span></td>
                            <td style="text-align: center; background: var(--aacx-ai-50);"><span style="color: var(--aacx-ok-600); font-size: var(--aacx-text-xl);" aria-label="<?php esc_attr_e('Included', 'allaccessible'); ?>">✓</span></td>
                        </tr>
                        <tr>
                            <td><?php _e('VPAT Reports', 'allaccessible'); ?></td>
                            <td style="text-align: center;"><span style="color: var(--aacx-slate-400);" aria-label="<?php esc_attr_e('Not included', 'allaccessible'); ?>">✗</span></td>
                            <td style="text-align: center; background: var(--aacx-primary-50);"><span style="color: var(--aacx-slate-400);" aria-label="<?php esc_attr_e('Not included', 'allaccessible'); ?>">✗</span></td>
                            <td style="text-align: center; background: var(--aacx-ai-50);"><span style="color: var(--aacx-ok-600); font-size: var(--aacx-text-xl);" aria-label="<?php esc_attr_e('Included', 'allaccessible'); ?>">✓</span></td>
                        </tr>
                        <tr>
                            <td><?php _e('Priority Support', 'allaccessible'); ?></td>
                            <td style="text-align: center;"><span style="color: var(--aacx-slate-400);" aria-label="<?php esc_attr_e('Not included', 'allaccessible'); ?>">✗</span></td>
                            <td style="text-align: center; background: var(--aacx-primary-50);"><span style="color: var(--aacx-ok-600); font-size: var(--aacx-text-xl);" aria-label="<?php esc_attr_e('Included', 'allaccessible'); ?>">✓</span></td>
                            <td style="text-align: center; background: var(--aacx-ai-50); color: var(--aacx-ai-700); font-weight: var(--aacx-weight-bold);"><?php _e('Dedicated', 'allaccessible'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: var(--aacx-space-6); text-align: center;">
                <a href="<?php echo esc_url($addon_url); ?>"
                   target="_blank"
                   class="aacx-v2__btn aacx-v2__btn--primary aacx-v2__btn--lg">
                    <?php _e('Upgrade Plan', 'allaccessible'); ?>
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
    <?php
}
