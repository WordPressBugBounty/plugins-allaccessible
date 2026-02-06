<?php
/**
 * Widget Customizer Component
 *
 * Simple Tailwind form for basic widget customization
 * Directs to dashboard for advanced settings
 *
 * @package AllAccessible
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * Render widget customizer form for paid users
 *
 * @param object $site_options Site options from /validate API
 * @param string $widget_settings_url URL to advanced widget settings
 */
function aacb_render_widget_customizer($site_options, $widget_settings_url) {
    if (!$site_options || is_wp_error($site_options)) {
        return;
    }

    // Get correct subdomain ID from validate response
    $subdomain_id = $site_options->subID ?? get_option('aacb_siteID');

    // Extract current values
    $color = $site_options->triggerBtnBg ?? '#2b446d';

    // Convert RGB color to hex if needed (database might have rgb(r,g,b) format)
    $color = trim($color);
    if (strpos($color, 'rgb') !== false) {
        preg_match('/rgb\((\d+),\s*(\d+),\s*(\d+)\)/', $color, $matches);
        if (count($matches) === 4) {
            $color = sprintf("#%02x%02x%02x", $matches[1], $matches[2], $matches[3]);
        } else {
            $color = '#2b446d'; // Fallback
        }
    }

    $position = $site_options->buttonPosition ?? 'bottom-right';
    $size = $site_options->triggerBtnSize ?? '55';
    $icon = $site_options->triggerSVG ?? 'Default';
    $shape = $site_options->triggerBtnRadius ?? '50';
    $white_label = $site_options->isWhitelabel ?? false;
    ?>

    <div class="aacx-bg-white aacx-rounded-xl aacx-shadow-xl aacx-border-2 aacx-border-aacx-gray-200 aacx-overflow-hidden aacx-mb-8">

        <div class="aacx-px-8 aacx-py-6 aacx-border-b-2 aacx-border-aacx-gray-200 aacx-bg-aacx-gray-50">
            <div class="aacx-flex aacx-items-center aacx-justify-between">
                <div class="aacx-flex aacx-items-center aacx-gap-4">
                    <div class="aacx-w-12 aacx-h-12 aacx-bg-aacx-primary-100 aacx-rounded-xl aacx-flex aacx-items-center aacx-justify-center">
                        <svg class="aacx-w-7 aacx-h-7 aacx-text-aacx-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="aacx-text-2xl aacx-font-bold aacx-text-aacx-slate-900">
                            <?php _e('Quick Widget Settings', 'allaccessible'); ?>
                        </h2>
                        <p class="aacx-text-xs aacx-text-aacx-slate-600 aacx-mt-1">
                            <?php _e('Customize your widget appearance', 'allaccessible'); ?>
                        </p>
                    </div>
                </div>
                <a href="<?php echo esc_url($widget_settings_url); ?>"
                   target="_blank"
                   class="aacx-inline-flex aacx-items-center aacx-gap-2 aacx-px-5 aacx-py-2.5 aacx-bg-aacx-primary-600 aacx-text-white aacx-rounded-lg aacx-font-semibold aacx-text-sm hover:aacx-bg-aacx-primary-700 aacx-shadow-lg hover:aacx-shadow-xl aacx-transition-all">
                    <?php _e('Advanced Settings', 'allaccessible'); ?>
                    <svg class="aacx-w-4 aacx-h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Info Message -->
        <div class="aacx-px-8 aacx-pt-6">
            <div class="aacx-bg-aacx-primary-50 aacx-border aacx-border-aacx-primary-200 aacx-rounded-lg aacx-p-4 aacx-mb-6">
                <p class="aacx-text-sm aacx-text-aacx-primary-800">
                    <strong><?php _e('Pro Tip:', 'allaccessible'); ?></strong>
                    <?php _e('For more options including accessibility profiles, custom CSS, and advanced features, visit', 'allaccessible'); ?>
                    <a href="<?php echo esc_url($widget_settings_url); ?>" target="_blank" class="aacx-underline aacx-font-semibold">
                        <?php _e('Advanced Widget Settings', 'allaccessible'); ?>
                    </a>
                </p>
            </div>
        </div>

        <!-- 2-Column Layout: Settings (2/3) + Preview (1/3) -->
        <div class="aacx-px-8 aacx-pb-8">
        <div class="aacx-grid aacx-grid-cols-1 md:aacx-grid-cols-2 aacx-gap-8">

            <!-- Left: Settings Form (1 column) -->
            <div class="md:aacx-col-span-1">
                <form id="aacb-widget-settings-form">
                    <input type="hidden" name="accountID" value="<?php echo esc_attr(get_option('aacb_accountID')); ?>">
                    <input type="hidden" name="subdomainID" value="<?php echo esc_attr($subdomain_id); ?>">

                    <div class="aacx-space-y-8">

                <!-- Row 1: Color + Position -->
                <div class="aacx-grid aacx-grid-cols-1 md:aacx-grid-cols-2 aacx-gap-8">

                <!-- Widget Color -->
                <div>
                    <label class="aacx-block aacx-text-sm aacx-font-bold aacx-text-aacx-slate-700 aacx-mb-2">
                        <?php _e('Widget Color', 'allaccessible'); ?>
                    </label>
                    <div class="aacx-flex aacx-items-center aacx-gap-3">
                        <input
                            type="color"
                            name="triggerBtnBg"
                            id="widget-color"
                            value="<?php echo esc_attr(trim($color)); ?>"
                            class="aacx-h-12 aacx-w-20 aacx-rounded aacx-border-2 aacx-border-aacx-gray-300 aacx-cursor-pointer">
                        <input
                            type="hidden"
                            id="widget-color-text"
                            value="<?php echo esc_attr(trim($color)); ?>"
                            class="aacx-flex-1 aacx-px-4 aacx-py-2 aacx-border-2 aacx-border-aacx-gray-300 aacx-rounded-lg aacx-text-sm"
                            readonly>
                    </div>
                    <p class="aacx-text-xs aacx-text-aacx-slate-500 aacx-mt-1">
                        <?php _e('Choose the color for your accessibility widget button', 'allaccessible'); ?>
                    </p>
                </div>

                <!-- Widget Position -->
                <div>
                    <label class="aacx-block aacx-text-sm aacx-font-bold aacx-text-aacx-slate-700 aacx-mb-2">
                        <?php _e('Widget Position', 'allaccessible'); ?>
                    </label>
                    <select
                        name="buttonPosition"
                        class="aacx-w-full aacx-px-4 aacx-py-2 aacx-border-2 aacx-border-aacx-gray-300 aacx-rounded-lg focus:aacx-border-aacx-primary-600 focus:aacx-outline-none aacx-transition-colors">
                        <option value="bottom-right" <?php selected($position, 'bottom-right'); ?>><?php _e('Bottom Right', 'allaccessible'); ?></option>
                        <option value="bottom-left" <?php selected($position, 'bottom-left'); ?>><?php _e('Bottom Left', 'allaccessible'); ?></option>
                        <option value="bottom-center" <?php selected($position, 'bottom-center'); ?>><?php _e('Bottom Center', 'allaccessible'); ?></option>
                        <option value="right-center" <?php selected($position, 'right-center'); ?>><?php _e('Middle Right', 'allaccessible'); ?></option>
                        <option value="left-center" <?php selected($position, 'left-center'); ?>><?php _e('Middle Left', 'allaccessible'); ?></option>
                        <option value="top-right" <?php selected($position, 'top-right'); ?>><?php _e('Top Right', 'allaccessible'); ?></option>
                        <option value="top-left" <?php selected($position, 'top-left'); ?>><?php _e('Top Left', 'allaccessible'); ?></option>
                    </select>
                    <p class="aacx-text-xs aacx-text-aacx-slate-500 aacx-mt-1">
                        <?php _e('Where the widget button appears on your site', 'allaccessible'); ?>
                    </p>
                </div>

                </div>

                <!-- Row 2: Size + Icon -->
                <div class="aacx-grid aacx-p-2 aacx-grid-cols-1 md:aacx-grid-cols-2 aacx-gap-8">

                <!-- Widget Size -->
                <div>
                    <label class="aacx-block aacx-text-sm aacx-font-bold aacx-text-aacx-slate-700 aacx-mb-2">
                        <?php _e('Widget Size', 'allaccessible'); ?>
                    </label>
                    <select
                        name="triggerBtnSize"
                        class="aacx-w-full aacx-px-4 aacx-py-2 aacx-border-2 aacx-border-aacx-gray-300 aacx-rounded-lg focus:aacx-border-aacx-primary-600 focus:aacx-outline-none aacx-transition-colors">
                        <option value="40" <?php selected($size, '40'); ?>><?php _e('Small', 'allaccessible'); ?></option>
                        <option value="55" <?php selected($size, '55'); ?>><?php _e('Medium (Default)', 'allaccessible'); ?></option>
                        <option value="80" <?php selected($size, '80'); ?>><?php _e('Large', 'allaccessible'); ?></option>
                    </select>
                    <p class="aacx-text-xs aacx-text-aacx-slate-500 aacx-mt-1">
                        <?php _e('Size of the widget button in pixels', 'allaccessible'); ?>
                    </p>
                </div>

                <!-- Widget Icon -->
                <div>
                    <label class="aacx-block aacx-text-sm aacx-font-bold aacx-text-aacx-slate-700 aacx-mb-2">
                        <?php _e('Widget Icon', 'allaccessible'); ?>
                    </label>
                    <div class="aacx-flex aacx-items-center aacx-gap-3">
                        <select
                            name="triggerSVG"
                            id="icon-selector"
                            class="aacx-flex-1 aacx-px-4 aacx-py-2 aacx-border-2 aacx-border-aacx-gray-300 aacx-rounded-lg focus:aacx-border-aacx-primary-600 focus:aacx-outline-none aacx-transition-colors">
                            <option value="Default" <?php selected($icon, 'Default'); ?>><?php _e('Default', 'allaccessible'); ?></option>
                            <option value="Alt" <?php selected($icon, 'Alt'); ?>><?php _e('Accessibility Person', 'allaccessible'); ?></option>
                            <option value="Alt2" <?php selected($icon, 'Alt2'); ?>><?php _e('Accessibility Settings', 'allaccessible'); ?></option>
                            <option value="Adjust" <?php selected($icon, 'Adjust'); ?>><?php _e('Adjust', 'allaccessible'); ?></option>
                            <option value="Chair2" <?php selected($icon, 'Chair2'); ?>><?php _e('Wheelchair 1', 'allaccessible'); ?></option>
                            <option value="Heart" <?php selected($icon, 'Heart'); ?>><?php _e('Heart', 'allaccessible'); ?></option>
                            <option value="Braille" <?php selected($icon, 'Braille'); ?>><?php _e('Braille', 'allaccessible'); ?></option>
                            <option value="Blind" <?php selected($icon, 'Blind'); ?>><?php _e('Blind/Cane', 'allaccessible'); ?></option>
                            <option value="Eye" <?php selected($icon, 'Eye'); ?>><?php _e('Eye', 'allaccessible'); ?></option>
                            <option value="Globe" <?php selected($icon, 'Globe'); ?>><?php _e('Globe', 'allaccessible'); ?></option>
                            <option value="Access" <?php selected($icon, 'Access'); ?>><?php _e('Universal Access', 'allaccessible'); ?></option>
                            <option value="Cogs" <?php selected($icon, 'Cogs'); ?>><?php _e('Settings', 'allaccessible'); ?></option>
                            <option value="Cane" <?php selected($icon, 'Cane'); ?>><?php _e('Walking Cane', 'allaccessible'); ?></option>
                        </select>
                        <div id="icon-preview" class="aacx-w-12 aacx-p-2 aacx-h-12 aacx-bg-aacx-primary-600 aacx-rounded-lg aacx-flex aacx-items-center aacx-justify-center aacx-flex-shrink-0">
                            <!-- SVG will be injected here -->
                        </div>
                    </div>
                    <p class="aacx-text-xs aacx-text-aacx-slate-500 aacx-mt-1">
                        <?php _e('Icon displayed in the widget button', 'allaccessible'); ?>
                    </p>
                </div>

                </div>

                <!-- Row 3: Shape -->
                <div class="aacx-grid aacx-grid-cols-1 md:aacx-grid-cols-2 aacx-gap-8">

                <!-- Widget Shape -->
                <div>
                    <label class="aacx-block aacx-text-sm aacx-font-bold aacx-text-aacx-slate-700 aacx-mb-2">
                        <?php _e('Widget Shape', 'allaccessible'); ?>
                    </label>
                    <select
                        name="triggerBtnRadius"
                        class="aacx-w-full aacx-px-4 aacx-py-2 aacx-border-2 aacx-border-aacx-gray-300 aacx-rounded-lg focus:aacx-border-aacx-primary-600 focus:aacx-outline-none aacx-transition-colors">
                        <option value="50" <?php selected($shape, '50'); ?>><?php _e('Circle', 'allaccessible'); ?></option>
                        <option value="0" <?php selected($shape, '0'); ?>><?php _e('Square', 'allaccessible'); ?></option>
                    </select>
                    <p class="aacx-text-xs aacx-text-aacx-slate-500 aacx-mt-1">
                        <?php _e('Shape of the widget button', 'allaccessible'); ?>
                    </p>
                </div>

                </div>

                </div>

            <!-- Save Button -->
            <div class="aacx-flex aacx-items-center aacx-gap-4 aacx-pt-8 aacx-mt-6 aacx-border-t-2 aacx-border-aacx-gray-200">
                <button
                    type="submit"
                    id="save-widget-settings"
                    class="aacx-px-8 aacx-py-4 aacx-bg-aacx-primary-600 aacx-text-white aacx-rounded-lg aacx-font-bold aacx-text-base hover:aacx-bg-aacx-primary-700 aacx-shadow-lg hover:aacx-shadow-xl aacx-transition-all aacx-flex aacx-items-center">
                    <svg class="aacx-w-5 aacx-h-5 aacx-mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <?php _e('Save Settings', 'allaccessible'); ?>
                </button>

                <a
                    href="#"
                    id="show-troubleshooting-link"
                    class="aacx-text-sm aacx-text-aacx-primary-600 hover:aacx-text-aacx-primary-700 aacx-underline aacx-flex aacx-items-center">
                    <svg class="aacx-w-4 aacx-h-4 aacx-mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?php _e('Having trouble viewing updates?', 'allaccessible'); ?>
                </a>

                <div id="troubleshooting-buttons" class="aacx-hidden">
                    <button
                        type="button"
                        id="invalidate-all-cache-button"
                        class="aacx-px-6 aacx-py-4 aacx-bg-aacx-warning-50 aacx-text-aacx-warning-700 aacx-rounded-lg aacx-font-semibold aacx-text-sm hover:aacx-bg-aacx-warning-100 aacx-border-2 aacx-border-aacx-warning-300 aacx-transition-all aacx-flex aacx-items-center"
                        title="<?php esc_attr_e('Clear all caches: browser, cookies, and server (full reset)', 'allaccessible'); ?>">
                        <svg class="aacx-w-4 aacx-h-4 aacx-mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                        </svg>
                        <?php _e('Clear All Caches', 'allaccessible'); ?>
                    </button>
                </div>

                <div id="save-status" class="aacx-hidden aacx-flex aacx-items-center aacx-text-sm">
                    <svg class="aacx-animate-spin aacx-h-5 aacx-w-5 aacx-text-aacx-primary-600 aacx-mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="aacx-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="aacx-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="aacx-text-aacx-slate-600"><?php _e('Saving...', 'allaccessible'); ?></span>
                </div>

                <div id="save-success" class="aacx-hidden aacx-flex aacx-items-center aacx-text-sm aacx-text-aacx-secondary-600">
                    <svg class="aacx-w-5 aacx-h-5 aacx-mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                    </svg>
                    <?php _e('Settings saved!', 'allaccessible'); ?>
                </div>

                <div id="save-error" class="aacx-hidden aacx-text-sm aacx-text-red-600"></div>

                <div id="reset-cache-success" class="aacx-hidden aacx-flex aacx-items-center aacx-text-sm aacx-text-aacx-secondary-600">
                    <svg class="aacx-w-5 aacx-h-5 aacx-mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                    </svg>
                    <?php _e('Cache cleared! Refresh your site to see changes.', 'allaccessible'); ?>
                </div>

                <div id="invalidate-cache-status" class="aacx-hidden aacx-flex aacx-items-center aacx-text-sm">
                    <svg class="aacx-animate-spin aacx-h-5 aacx-w-5 aacx-text-aacx-warning-600 aacx-mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="aacx-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="aacx-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="aacx-text-aacx-slate-600"><?php _e('Clearing all caches...', 'allaccessible'); ?></span>
                </div>

                <div id="invalidate-cache-success" class="aacx-hidden aacx-flex aacx-items-center aacx-text-sm aacx-text-aacx-secondary-600">
                    <svg class="aacx-w-5 aacx-h-5 aacx-mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                    </svg>
                    <?php _e('All caches cleared! Refresh your site to see changes.', 'allaccessible'); ?>
                </div>

                <div id="invalidate-cache-error" class="aacx-hidden aacx-text-sm aacx-text-red-600"></div>
            </div>
        </form>
            </div>

            <!-- Right: Live Preview (1 column) -->
            <div class="md:aacx-col-span-1">
                <div class="aacx-sticky" style="top: 20px;">
                    <div class="aacx-bg-aacx-gray-50 aacx-border-2 aacx-border-aacx-primary-200 aacx-rounded-xl aacx-p-6">
                        <h3 class="aacx-font-bold aacx-text-aacx-slate-900 aacx-mb-4 aacx-text-center">
                            <?php _e('Live Preview', 'allaccessible'); ?>
                        </h3>
                        <div class="aacx-relative aacx-bg-white aacx-border-2 aacx-border-aacx-gray-300 aacx-rounded-lg aacx-shadow-inner" style="height: 380px;">
                            <!-- Position indicator -->
                            <div id="widget-preview-position" class="aacx-absolute aacx-transition-all aacx-duration-300" style="bottom: 20px; right: 20px;">
                                <div id="widget-preview-button"
                                     class="aacx-shadow-xl aacx-flex aacx-items-center aacx-justify-center aacx-text-white aacx-font-bold aacx-cursor-pointer aacx-transition-all hover:aacx-shadow-2xl"
                                     data-color="<?php echo esc_attr(trim($color)); ?>"
                                     data-size="<?php echo esc_attr($size); ?>"
                                     data-shape="<?php echo esc_attr($shape); ?>">
                                    <!-- Icon SVG will be inserted by JavaScript -->
                                </div>
                            </div>
                        </div>
                        <p class="aacx-text-xs aacx-text-center aacx-text-aacx-slate-500 aacx-mt-3">
                            <?php _e('See how your widget will look', 'allaccessible'); ?>
                        </p>
                    </div>
                </div>
            </div>

        </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Icon SVGs from v1.3.8 (exact same as old plugin)
        const svgIcons = {
            Default: '<svg version="1.1" x="0px" y="0px" viewBox="0 0 79.583 79.638" style="width:85%;height:85%;" xmlns="http://www.w3.org/2000/svg"><g transform="matrix(1, 0, 0, 1, -10.250157, -10.143444)"><path fill="white" d="M55.6,73.2c0.5,1.1,1.5,1.7,2.6,1.7c0.4,0,0.8-0.1,1.2-0.3c1.5-0.7,2.1-2.4,1.4-3.9c0,0-5.5-12.6-6.5-17.2c-0.4-1.6-0.6-5.9-0.7-7.9c0-0.7,0.4-1.3,1-1.5l12.3-3.7c1.5-0.4,2.4-2.1,2-3.6c-0.4-1.5-2.1-2.4-3.6-2c0,0-11.4,3.7-15.5,3.7c-4,0-15.3-3.6-15.3-3.6c-1.5-0.4-3.2,0.3-3.7,1.8c-0.5,1.6,0.4,3.3,2,3.7l12.3,3.7c0.6,0.2,1.1,0.8,1,1.5c-0.1,2-0.3,6.3-0.7,7.9c-1,4.6-6.5,17.2-6.5,17.2c-0.7,1.5,0,3.2,1.4,3.9c0.4,0.2,0.8,0.3,1.2,0.3c1.1,0,2.2-0.6,2.6-1.7L50,61.2L55.6,73.2z"/><circle fill="white" cx="50" cy="30" r="5.6"/><path fill="white" d="M89.5,50c0-21.8-17.7-39.5-39.5-39.5c-21.8,0-39.5,17.7-39.5,39.5S28.2,89.5,50,89.5C71.8,89.5,89.5,71.8,89.5,50zM17.1,50c0-18.2,14.8-32.9,32.9-32.9S82.9,31.8,82.9,50S68.2,82.9,50,82.9S17.1,68.2,17.1,50z"/></g></svg>',
            Alt: '<svg version="1.1" x="0px" y="0px" viewBox="0 0 182.608 214.637" style="width:70%;height:70%;" xmlns="http://www.w3.org/2000/svg"><g transform="matrix(1, 0, 0, 1, -15.892562, 0.061192)"><path fill="white" d="M127.75,183.153c-10.629,10.63-24.76,16.483-39.793,16.483c-31.024,0-56.265-25.241-56.265-56.268c0-15.034,5.852-29.168,16.479-39.796c2.929-2.929,2.928-7.678-0.001-10.607c-2.929-2.93-7.678-2.929-10.607,0.001c-13.459,13.461-20.871,31.36-20.871,50.401c0,39.297,31.969,71.268,71.265,71.268c19.039,0,36.938-7.414,50.4-20.878c2.929-2.929,2.928-7.678-0.001-10.606C135.427,180.225,130.678,180.224,127.75,183.153z"/><path fill="white" d="M190.444,166.706h-21.335L155.9,130.057c-1.072-2.975-3.894-4.957-7.056-4.957H93.232V91.424h51.648c4.142,0,7.5-3.357,7.5-7.5c0-4.143-3.358-7.5-7.5-7.5H93.232V53.279c0-4.143-3.358-7.5-7.5-7.5c-4.142,0-7.5,3.357-7.5,7.5v79.32c0,4.143,3.358,7.5,7.5,7.5h57.842l13.209,36.649c1.072,2.975,3.894,4.957,7.056,4.957h26.604c4.142,0,7.5-3.357,7.5-7.5S194.587,166.706,190.444,166.706z"/><path fill="white" d="M86.015,36.438c10.063,0,18.221-8.154,18.221-18.224C104.235,8.161,96.078,0,86.015,0C75.952,0,67.796,8.161,67.796,18.215C67.796,28.284,75.952,36.438,86.015,36.438z"/></g></svg>',
            Alt2: '<svg x="0px" y="0px" viewBox="0.634 0.344 56.406 60.613" style="width:70%;height:70%;" xmlns="http://www.w3.org/2000/svg"><g transform="matrix(1, 0, 0, 1, -21.093462, -19.272371)"><path fill="white" d="M50.1,41.3c-4.9,0-8.8,3.9-8.8,8.8s3.9,8.8,8.8,8.8s8.8-3.9,8.8-8.8S55,41.3,50.1,41.3zM76.4,58l-4.6-3.9c0.3-1.4,0.4-2.9,0.4-4.3c0-1.4-0.1-2.9-0.4-4.3l4.6-3.9c1.5-1.3,2-3.5,1-5.3l-2-3.5c-0.8-1.3-2.1-2-3.6-2c-0.5,0-1,0.1-1.4,0.3l-5.8,2.1c-2.3-2-4.8-3.4-7.4-4.3l-1-5.9c-0.4-2-2.1-3.1-4.1-3.1h-4c-2,0-3.8,1.1-4.1,3.1l-1,5.8c-2.8,0.9-5.3,2.4-7.5,4.3L29.6,31c-0.5-0.1-0.9-0.3-1.4-0.3c-1.5,0-2.9,0.8-3.6,2l-2,3.5c-1,1.8-0.6,4,1,5.3l4.6,3.9c-0.3,1.4-0.4,2.9-0.4,4.3c0,1.5,0.1,2.9,0.4,4.3l-4.6,3.9c-1.5,1.3-2,3.5-1,5.3l2,3.5c0.8,1.3,2.1,2,3.6,2c0.5,0,1-0.1,1.4-0.3l5.8-2.1c2.3,2,4.8,3.4,7.4,4.3l1,6c0.4,2,2,3.4,4.1,3.4h4c2,0,3.8-1.5,4.1-3.5l1-6c2.9-1,5.5-2.5,7.8-4.6l5.4,2.1c0.5,0.1,1,0.3,1.5,0.3c1.5,0,2.9-0.8,3.6-2l1.9-3.3C78.4,61.5,77.9,59.3,76.4,58zM50.1,63.9c-7.7,0-13.8-6.2-13.8-13.8s6.2-13.8,13.8-13.8s13.8,6.2,13.8,13.8S57.7,63.9,50.1,63.9z"/></g></svg>',
            Adjust: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="width:70%;height:70%;"><path fill="white" d="M8 256c0 136.966 111.033 248 248 248s248-111.034 248-248S392.966 8 256 8 8 119.033 8 256zm248 184V72c101.705 0 184 82.311 184 184 0 101.705-82.311 184-184 184z"></path></svg>',
            Braille: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" style="width:70%;height:70%;"><path fill="white" d="M128 256c0 35.346-28.654 64-64 64S0 291.346 0 256s28.654-64 64-64 64 28.654 64 64zM64 384c-17.673 0-32 14.327-32 32s14.327 32 32 32 32-14.327 32-32-14.327-32-32-32zm0-352C28.654 32 0 60.654 0 96s28.654 64 64 64 64-28.654 64-64-28.654-64-64-64zm160 192c-17.673 0-32 14.327-32 32s14.327 32 32 32 32-14.327 32-32-14.327-32-32-32zm0 160c-17.673 0-32 14.327-32 32s14.327 32 32 32 32-14.327 32-32-14.327-32-32-32zm0-352c-35.346 0-64 28.654-64 64s28.654 64 64 64 64-28.654 64-64-28.654-64-64-64zm224 192c-17.673 0-32 14.327-32 32s14.327 32 32 32 32-14.327 32-32-14.327-32-32-32zm0 160c-17.673 0-32 14.327-32 32s14.327 32 32 32 32-14.327 32-32-14.327-32-32-32zm0-352c-35.346 0-64 28.654-64 64s28.654 64 64 64 64-28.654 64-64-28.654-64-64-64zm160 192c-17.673 0-32 14.327-32 32s14.327 32 32 32 32-14.327 32-32-14.327-32-32-32zm0 160c-17.673 0-32 14.327-32 32s14.327 32 32 32 32-14.327 32-32-14.327-32-32-32zm0-320c-17.673 0-32 14.327-32 32s14.327 32 32 32 32-14.327 32-32-14.327-32-32-32z"></path></svg>',
            Blind: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" style="width:70%;height:70%;"><path fill="white" d="M380.15 510.837a8 8 0 0 1-10.989-2.687l-125.33-206.427a31.923 31.923 0 0 0 12.958-9.485l126.048 207.608a8 8 0 0 1-2.687 10.991zM142.803 314.338l-32.54 89.485 36.12 88.285c6.693 16.36 25.377 24.192 41.733 17.501 16.357-6.692 24.193-25.376 17.501-41.734l-62.814-153.537zM96 88c24.301 0 44-19.699 44-44S120.301 0 96 0 52 19.699 52 44s19.699 44 44 44zm154.837 169.128l-120-152c-4.733-5.995-11.75-9.108-18.837-9.112V96H80v.026c-7.146.003-14.217 3.161-18.944 9.24L0 183.766v95.694c0 13.455 11.011 24.791 24.464 24.536C37.505 303.748 48 293.1 48 280v-79.766l16-20.571v140.698L9.927 469.055c-6.04 16.609 2.528 34.969 19.138 41.009 16.602 6.039 34.968-2.524 41.009-19.138L136 309.638V202.441l-31.406-39.816a4 4 0 1 1 6.269-4.971l102.3 129.217c9.145 11.584 24.368 11.339 33.708 3.965 10.41-8.216 12.159-23.334 3.966-33.708z"/></svg>',
            Eye: '<svg style="width:70%;height:70%;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="white" d="M38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7L525.6 386.7c39.6-40.6 66.4-86.1 79.9-118.4c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C465.5 68.8 400.8 32 320 32c-68.2 0-125 26.3-169.3 60.8L38.8 5.1zM223.1 149.5C248.6 126.2 282.7 112 320 112c79.5 0 144 64.5 144 144c0 24.9-6.3 48.3-17.4 68.7L408 294.5c5.2-11.8 8-24.8 8-38.5c0-53-43-96-96-96c-2.8 0-5.6 .1-8.4 .4c5.3 9.3 8.4 20.1 8.4 31.6c0 10.2-2.4 19.8-6.6 28.3l-90.3-70.8zM160 448c53 0 96-43 96-96s-43-96-96-96s-96 43-96 96s43 96 96 96z"/></svg>',
            Cogs: '<svg style="width:70%;height:70%;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="white" d="M224 96c26.5 0 48-21.5 48-48s-21.5-48-48-48s-48 21.5-48 48s21.5 48 48 48zM152.5 247.2c12.4-4.7 18.7-18.5 14-30.9s-18.5-18.7-30.9-14C75.1 225.1 32 283.5 32 352c0 88.4 71.6 160 160 160c61.2 0 114.3-34.3 141.2-84.7c6.2-11.7 1.8-26.2-9.9-32.5s-26.2-1.8-32.5 9.9C272 440 234.8 464 192 464c-61.9 0-112-50.1-112-112c0-47.9 30.1-88.8 72.5-104.8zM291.8 176l-1.9-9.7c-4.5-22.3-24-38.3-46.8-38.3c-30.1 0-52.7 27.5-46.8 57l23.1 115.5c6 29.9 32.2 51.4 62.8 51.4h5.1c.4 0 .8 0 1.3 0h94.1c6.7 0 12.6 4.1 15 10.4L434 459.2c6 16.1 23.8 24.6 40.1 19.1l48-16c16.8-5.6 25.8-23.7 20.2-40.5s-23.7-25.8-40.5-20.2l-18.7 6.2-25.5-68c-11.7-31.2-41.6-51.9-74.9-51.9H314.2l-9.6-48H368c17.7 0 32-14.3 32-32s-14.3-32-32-32H291.8z"/></svg>',
            Globe: '<svg style="width:70%;height:70%;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="white" d="M352 256c0 22.2-1.2 43.6-3.3 64H163.3c-2.2-20.4-3.3-41.8-3.3-64s1.2-43.6 3.3-64H348.7c2.2 20.4 3.3 41.8 3.3 64zm28.8-64H503.9c5.3 20.5 8.1 41.9 8.1 64s-2.8 43.5-8.1 64H380.8c2.1-20.6 3.2-42 3.2-64s-1.1-43.4-3.2-64zm112.6-32H376.7c-10-63.9-29.8-117.4-55.3-151.6c78.3 20.7 142 77.5 171.9 151.6zm-149.1 0H167.7c6.1-36.4 15.5-68.6 27-94.7c10.5-23.6 22.2-40.7 33.5-51.5C239.4 3.2 248.7 0 256 0s16.6 3.2 27.8 13.8c11.3 10.8 23 27.9 33.5 51.5c11.6 26 21 58.2 27 94.7zm-209 0H18.6C48.6 85.9 112.2 29.1 190.6 8.4C165.1 42.6 145.3 96.1 135.3 160zM8.1 192H131.2c-2.1 20.6-3.2 42-3.2 64s1.1 43.4 3.2 64H8.1C2.8 299.5 0 278.1 0 256s2.8-43.5 8.1-64zM194.7 446.6c-11.6-26-20.9-58.2-27-94.6H344.3c-6.1 36.4-15.5 68.6-27 94.6c-10.5 23.6-22.2 40.7-33.5 51.5C272.6 508.8 263.3 512 256 512s-16.6-3.2-27.8-13.8c-11.3-10.8-23-27.9-33.5-51.5zM135.3 352c10 63.9 29.8 117.4 55.3 151.6C112.2 482.9 48.6 426.1 18.6 352H135.3zm358.1 0c-30 74.1-93.6 130.9-171.9 151.6c25.5-34.2 45.2-87.7 55.3-151.6H493.4z"/></svg>',
            Heart: '<svg x="0px" y="0px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="width:70%;height:70%;"><path fill="white" d="M244 84L255.1 96L267.1 84.02C300.6 51.37 347 36.51 392.6 44.1C461.5 55.58 512 115.2 512 185.1V190.9C512 232.4 494.8 272.1 464.4 300.4L283.7 469.1C276.2 476.1 266.3 480 256 480C245.7 480 235.8 476.1 228.3 469.1L47.59 300.4C17.23 272.1 0 232.4 0 190.9V185.1C0 115.2 50.52 55.58 119.4 44.1C164.1 36.51 211.4 51.37 244 84C243.1 84 244 84.01 244 84L244 84zM255.1 163.9L210.1 117.1C188.4 96.28 157.6 86.4 127.3 91.44C81.55 99.07 48 138.7 48 185.1V190.9C48 219.1 59.71 246.1 80.34 265.3L256 429.3L431.7 265.3C452.3 246.1 464 219.1 464 190.9V185.1C464 138.7 430.4 99.07 384.7 91.44C354.4 86.4 323.6 96.28 301.9 117.1L255.1 163.9z"/></svg>',
            Chair2: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" style="width:70%;height:70%;"><path fill="white" d="M416 48c0 26.5-21.5 48-48 48s-48-21.5-48-48s21.5-48 48-48s48 21.5 48 48zM204.5 121.3c-5.4-2.5-11.7-1.9-16.4 1.7l-40.9 30.7c-14.1 10.6-34.2 7.7-44.8-6.4s-7.7-34.2 6.4-44.8l40.9-30.7c23.7-17.8 55.3-21 82.1-8.4l90.4 42.5c29.1 13.7 36.8 51.6 15.2 75.5L299.1 224h97.4c30.3 0 53 27.7 47.1 57.4L415.4 422.3c-3.5 17.3-20.3 28.6-37.7 25.1s-28.6-20.3-25.1-37.7L377 288H306.7c8.6 19.6 13.3 41.2 13.3 64c0 88.4-71.6 160-160 160S0 440.4 0 352s71.6-160 160-160c11.1 0 22 1.1 32.4 3.3l54.2-54.2-42.1-19.8zM160 448c53 0 96-43 96-96s-43-96-96-96s-96 43-96 96s43 96 96 96z"/></svg>',
            Chair3: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" style="width:70%;height:70%;"><path fill="white" d="M224 96c26.5 0 48-21.5 48-48s-21.5-48-48-48s-48 21.5-48 48s21.5 48 48 48zm-45.7 48h91.4c11.8 0 23.4 1.2 34.5 3.3c-2.1 18.5 7.4 35.6 21.8 44.8c-16.6 10.6-26.7 31.6-20 53.3c4 12.9 9.4 25.5 16.4 37.6s15.2 23.1 24.4 33c15.7 16.9 39.6 18.4 57.2 8.7v.9c0 9.2 2.7 18.5 7.9 26.3H29.7C13.3 512 0 498.7 0 482.3C0 383.8 79.8 304 178.3 304z"/></svg>',
            Access: '<svg style="width:70%;height:70%;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="white" d="M512 256c0 141.4-114.6 256-256 256S0 397.4 0 256S114.6 0 256 0S512 114.6 512 256zM161.5 169.9c-12.2-5.2-26.3 .4-31.5 12.6s.4 26.3 12.6 31.5l11.9 5.1c17.3 7.4 35.2 12.9 53.6 16.3v50.1c0 4.3-.7 8.6-2.1 12.6l-28.7 86.1c-4.2 12.6 2.6 26.2 15.2 30.4s26.2-2.6 30.4-15.2l24.4-73.2c1.3-3.8 4.8-6.4 8.8-6.4s7.6 2.6 8.8 6.4l24.4 73.2c4.2 12.6 17.8 19.4 30.4 15.2s19.4-17.8 15.2-30.4l-28.7-86.1c-1.4-4.1-2.1-8.3-2.1-12.6V235.5c18.4-3.5 36.3-8.9 53.6-16.3l11.9-5.1c12.2-5.2 17.8-19.3 12.6-31.5s-19.3-17.8-31.5-12.6L338.7 175c-26.1 11.2-54.2 17-82.7 17s-56.5-5.8-82.7-17l-11.9-5.1zM256 160c22.1 0 40-17.9 40-40s-17.9-40-40-40s-40 17.9-40 40s17.9 40 40 40z"/></svg>',
            Cane: '<svg style="width:70%;height:70%;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="white" d="M176 96c26.5 0 48-21.5 48-48s-21.5-48-48-48s-48 21.5-48 48s21.5 48 48 48zm-8.4 32c-36.4 0-69.6 20.5-85.9 53.1L35.4 273.7c-7.9 15.8-1.5 35 14.3 42.9s35 1.5 42.9-14.3L128 231.6v43.2c0 17 6.7 33.3 18.7 45.3L224 397.3V480c0 17.7 14.3 32 32 32s32-14.3 32-32V390.6c0-12.7-5.1-24.9-14.1-33.9L224 306.7V213.3l70.4 93.9c10.6 14.1 30.7 17 44.8 6.4s17-30.7 6.4-44.8L268.8 166.4C250.7 142.2 222.2 128 192 128H167.6zM128.3 346.8L97 472.2c-4.3 17.1 6.1 34.5 23.3 38.8s34.5-6.1 38.8-23.3l22-88.2-52.8-52.8zM450.8 505.1c5 7.3 15 9.1 22.3 4s9.1-15 4-22.3L358.9 316.1c-2.8 3.8-6.1 7.3-10.1 10.3c-5 3.8-10.5 6.4-16.2 7.9L450.8 505.1z"/></svg>'
        };

        // Update icon preview
        function updateIconPreview() {
            const selectedIcon = $('#icon-selector').val();
            const iconSVG = svgIcons[selectedIcon] || svgIcons['Default'];
            $('#icon-preview').html(iconSVG);
        }

        // Position mappings (MUST be defined FIRST before any function calls)
        const positions = {
            'bottom-right': { bottom: '20px', right: '20px', top: 'auto', left: 'auto' },
            'bottom-left': { bottom: '20px', left: '20px', top: 'auto', right: 'auto' },
            'bottom-center': { bottom: '20px', left: '50%', transform: 'translateX(-50%)', top: 'auto', right: 'auto' },
            'top-right': { top: '20px', right: '20px', bottom: 'auto', left: 'auto' },
            'top-left': { top: '20px', left: '20px', bottom: 'auto', right: 'auto' },
            'right-center': { top: '50%', right: '20px', transform: 'translateY(-50%)', bottom: 'auto', left: 'auto' },
            'left-center': { top: '50%', left: '20px', transform: 'translateY(-50%)', bottom: 'auto', right: 'auto' },
        };

        // Helper: Convert RGB to Hex if needed
        function rgbToHex(color) {
            // If already hex, return it
            if (color.startsWith('#')) return color;

            // Convert rgb(r,g,b) to hex
            const match = color.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
            if (match) {
                const r = parseInt(match[1]).toString(16).padStart(2, '0');
                const g = parseInt(match[2]).toString(16).padStart(2, '0');
                const b = parseInt(match[3]).toString(16).padStart(2, '0');
                return '#' + r + g + b;
            }
            return color;
        }

        // Update preview when settings change
        function updatePreview() {
            let color = $('#widget-color').val() || '#2b446d';
            const position = $('select[name="buttonPosition"]').val();
            const size = $('select[name="triggerBtnSize"]').val();
            const shape = $('select[name="triggerBtnRadius"]').val();
            const selectedIcon = $('#icon-selector').val();

            // Convert RGB to hex if needed
            color = rgbToHex(color);

            console.log('🎨 Preview update - Color:', color, 'Size:', size, 'Shape:', shape, 'Icon:', selectedIcon, 'Position:', position);

            // Update icon SVG
            const iconSVG = svgIcons[selectedIcon] || svgIcons['Default'];
            const $button = $('#widget-preview-button');

            $button.html(iconSVG);

            // Set all styles as one attribute to override any inline styles
            const previewSize = Math.max(parseInt(size), 60);
            const borderRadius = shape === '50' ? '50%' : '12px';

            $button.attr('style',
                'background-color: ' + color + '; ' +
                'width: ' + previewSize + 'px; ' +
                'height: ' + previewSize + 'px; ' +
                'border-radius: ' + borderRadius + '; ' +
                'display: flex; ' +
                'align-items: center; ' +
                'justify-content: center; ' +
                'box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); ' +
                'cursor: pointer; ' +
                'transition: all 0.3s; ' +
                'color: white; ' +
                'font-weight: bold;'
            );

            // Update position
            const pos = positions[position] || positions['bottom-right'];
            $('#widget-preview-position').css({
                top: pos.top || 'auto',
                right: pos.right || 'auto',
                bottom: pos.bottom || 'auto',
                left: pos.left || 'auto',
                transform: pos.transform || 'none'
            });
        }

        // Update color text input when color picker changes
        $('#widget-color').on('input', function() {
            updatePreview();
        });

        // Update position when dropdown changes
        $('select[name="buttonPosition"]').on('change', function() {
            updatePreview();
        });

        // Update size when dropdown changes
        $('select[name="triggerBtnSize"]').on('change', function() {
            updatePreview();
        });

        // Update shape when dropdown changes
        $('select[name="triggerBtnRadius"]').on('change', function() {
            updatePreview();
        });

        // Icon selector change
        $('#icon-selector').on('change', function() {
            updateIconPreview(); // Update the small preview box
            updatePreview(); // Update the live preview widget
        });

        // Initialize both previews with current settings
        updateIconPreview();
        updatePreview();

        // Form submission
        $('#aacb-widget-settings-form').on('submit', function(e) {
            e.preventDefault();

            const subdomainID = $('input[name="subdomainID"]').val();

            if (!subdomainID) {
                console.error('❌ Cannot save - no subdomain ID found');
                $('#save-status').addClass('aacx-hidden');
                $('#save-error').removeClass('aacx-hidden').text('Error: Missing subdomain ID. Please refresh and try again.');
                $('#save-widget-settings').prop('disabled', false);
                return;
            }

            const formData = {
                accountID: $('input[name="accountID"]').val(),
                subdomainID: subdomainID,
                triggerBtnBg: $('input[name="triggerBtnBg"]').val(),
                buttonPosition: $('select[name="buttonPosition"]').val(),
                triggerBtnSize: $('select[name="triggerBtnSize"]').val(),
                triggerSVG: $('select[name="triggerSVG"]').val(),
                triggerBtnRadius: $('select[name="triggerBtnRadius"]').val()
            };

            console.log('💾 Saving widget settings:', formData);

            // Show loading
            $('#save-widget-settings').prop('disabled', true);
            $('#save-status').removeClass('aacx-hidden');
            $('#save-success').addClass('aacx-hidden');
            $('#save-error').addClass('aacx-hidden');

            // Call save-site-options API with subdomain ID
            $.ajax({
                url: 'https://api.allaccessible.org/save-site-options/' + subdomainID,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(response) {
                    console.log('✅ Settings saved:', response);

                    // Clear server-side cache so next page load gets fresh data
                    $.post(ajaxurl, {
                        action: 'aacb_clear_cache',
                        _wpnonce: '<?php echo wp_create_nonce('aacb_clear_cache'); ?>'
                    });

                    // Clear browser-side caches (localStorage and cookies)
                    try {
                        // Clear localStorage overrideOptions
                        if (typeof localStorage !== 'undefined') {
                            localStorage.removeItem('overrideOptions');
                            console.log('✅ Cleared localStorage: overrideOptions');
                        }

                        // Clear aacxValidated cookie
                        document.cookie = 'aacxValidated=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                        console.log('✅ Cleared cookie: aacxValidated');
                    } catch (e) {
                        console.warn('⚠️ Could not clear browser cache:', e);
                    }

                    // Show success
                    $('#save-status').addClass('aacx-hidden');
                    $('#save-success').removeClass('aacx-hidden');
                    $('#save-widget-settings').prop('disabled', false);

                    // Hide success message after 3 seconds
                    setTimeout(function() {
                        $('#save-success').addClass('aacx-hidden');
                    }, 3000);
                },
                error: function(xhr, status, error) {
                    console.error('❌ Save failed:', {xhr, status, error});

                    $('#save-status').addClass('aacx-hidden');
                    $('#save-error').text('<?php esc_js(_e('Failed to save settings. Please try again.', 'allaccessible')); ?>').removeClass('aacx-hidden');
                    $('#save-widget-settings').prop('disabled', false);

                    setTimeout(function() {
                        $('#save-error').addClass('aacx-hidden');
                    }, 5000);
                }
            });
        });

        // Show troubleshooting link handler
        $('#show-troubleshooting-link').on('click', function(e) {
            e.preventDefault();

            // Hide the link
            $(this).addClass('aacx-hidden');

            // Show the cache clearing button
            $('#troubleshooting-buttons').removeClass('aacx-hidden');
        });

        // Reset cache button handler
        $('#reset-cache-button').on('click', function(e) {
            e.preventDefault();

            console.log('🔄 Manually clearing browser cache and cookies...');

            // Clear browser-side caches (localStorage and cookies)
            try {
                // Clear localStorage overrideOptions
                if (typeof localStorage !== 'undefined') {
                    localStorage.removeItem('overrideOptions');
                    console.log('✅ Cleared localStorage: overrideOptions');
                }

                // Clear aacxValidated cookie
                document.cookie = 'aacxValidated=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                console.log('✅ Cleared cookie: aacxValidated');

                // Clear server-side cache too
                $.post(ajaxurl, {
                    action: 'aacb_clear_cache',
                    _wpnonce: '<?php echo wp_create_nonce('aacb_clear_cache'); ?>'
                }, function() {
                    console.log('✅ Server cache cleared');
                });

                // Show success message
                $('#reset-cache-success').removeClass('aacx-hidden');

                // Hide success message after 5 seconds
                setTimeout(function() {
                    $('#reset-cache-success').addClass('aacx-hidden');
                }, 5000);

            } catch (e) {
                console.error('❌ Could not clear cache:', e);
                alert('<?php esc_js(_e('Error clearing cache. Please check browser console.', 'allaccessible')); ?>');
            }
        });

        // Clear all caches button handler (browser + server)
        $('#invalidate-all-cache-button').on('click', function(e) {
            e.preventDefault();

            console.log('🔄 Clearing all caches (browser + server)...');

            // Show loading status
            $('#invalidate-cache-status').removeClass('aacx-hidden');
            $('#invalidate-cache-success').addClass('aacx-hidden');
            $('#invalidate-cache-error').addClass('aacx-hidden');

            try {
                // Step 1: Clear browser-side caches (localStorage and cookies)
                if (typeof localStorage !== 'undefined') {
                    localStorage.removeItem('overrideOptions');
                    console.log('✅ Cleared localStorage: overrideOptions');
                }

                document.cookie = 'aacxValidated=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                console.log('✅ Cleared cookie: aacxValidated');

                // Step 2: Clear WordPress transient cache
                $.post(ajaxurl, {
                    action: 'aacb_clear_cache',
                    _wpnonce: '<?php echo wp_create_nonce('aacb_clear_cache'); ?>'
                }, function() {
                    console.log('✅ WordPress cache cleared');
                });

                // Step 3: Call Lambda API to invalidate server cache
                const accountID = $('input[name="accountID"]').val();

                if (accountID) {
                    $.ajax({
                        url: 'https://api.allaccessible.org/cache/invalidate-license',
                        method: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({ accountId: accountID }),
                        success: function(response) {
                            console.log('✅ Lambda cache invalidated:', response);

                            // Hide loading, show success
                            $('#invalidate-cache-status').addClass('aacx-hidden');
                            $('#invalidate-cache-success').removeClass('aacx-hidden');

                            // Hide success message after 5 seconds
                            setTimeout(function() {
                                $('#invalidate-cache-success').addClass('aacx-hidden');
                            }, 5000);
                        },
                        error: function(xhr, status, error) {
                            console.error('❌ Lambda cache invalidation failed:', error);

                            // Hide loading, show error
                            $('#invalidate-cache-status').addClass('aacx-hidden');
                            $('#invalidate-cache-error').removeClass('aacx-hidden').text('<?php esc_js(_e('Server cache invalidation failed. Browser cache was cleared.', 'allaccessible')); ?>');

                            setTimeout(function() {
                                $('#invalidate-cache-error').addClass('aacx-hidden');
                            }, 5000);
                        }
                    });
                } else {
                    console.warn('⚠️ No account ID found, skipping server cache invalidation');

                    // Hide loading, show partial success
                    $('#invalidate-cache-status').addClass('aacx-hidden');
                    $('#invalidate-cache-success').removeClass('aacx-hidden');

                    setTimeout(function() {
                        $('#invalidate-cache-success').addClass('aacx-hidden');
                    }, 5000);
                }

            } catch (e) {
                console.error('❌ Could not clear caches:', e);
                $('#invalidate-cache-status').addClass('aacx-hidden');
                $('#invalidate-cache-error').removeClass('aacx-hidden').text('<?php esc_js(_e('Error clearing caches. Please check browser console.', 'allaccessible')); ?>');
            }
        });
    });
    </script>
    <?php
}
