<?php
/**
 * Widget Language card
 *
 * Owner default UI language for the widget: 'auto' (follow each visitor's
 * browser, then the page language) or a specific language every visitor sees
 * first. Visitors can still switch inside the widget.
 *
 * Available on every tier (matches the dashboard's Language Settings card),
 * so it is its own card above the paid-only Quick Widget Settings. Saves
 * through the same endpoint the customizer uses; the server keeps only
 * 'auto' or a code the widget ships.
 *
 * @package AllAccessible
 * @since 2.1.7
 */

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * @param object|null $site_options Site options from the API (may be null before setup)
 * @param string      $widget_settings_url Dashboard Widget Settings URL
 */
function aacb_render_widget_language_card($site_options, $widget_settings_url) {
    if (!$site_options || is_wp_error($site_options)) {
        return;
    }

    $subdomain_id = $site_options->subID ?? get_option('aacb_siteID');
    $current      = aacb_normalize_widget_language($site_options->language ?? 'auto');
    $languages    = aacb_widget_languages();

    $site_locale  = function_exists('get_locale') ? get_locale() : '';
    $suggested    = aacb_widget_language_from_wp_locale($site_locale);
    $suggested_name = ($suggested && isset($languages[$suggested])) ? $languages[$suggested] : null;
    ?>
    <div class="aacx-v2__card aacx-v2__card--elevated" id="aacb-widget-language-card" style="margin-bottom: var(--aacx-space-6);">
        <div class="aacx-v2__card-header">
            <div>
                <h3 style="margin: 0;"><?php _e('Widget Language', 'allaccessible'); ?></h3>
                <p style="font-size: var(--aacx-text-xs); color: var(--aacx-text-muted); margin-top: 2px;">
                    <?php _e('The language the widget opens in for your visitors. Each visitor can still switch languages inside the widget.', 'allaccessible'); ?>
                </p>
            </div>
        </div>
        <div class="aacx-v2__card-body">
            <form id="aacb-widget-language-form">
                <input type="hidden" name="accountID" value="<?php echo esc_attr(get_option('aacb_accountID')); ?>">
                <input type="hidden" name="subdomainID" value="<?php echo esc_attr($subdomain_id); ?>">
                <div class="aacx-v2__grid aacx-v2__grid--2">
                    <div class="aacx-v2__field">
                        <label class="aacx-v2__label" for="aacb-widget-language"><?php _e('Default Language', 'allaccessible'); ?></label>
                        <select name="language" id="aacb-widget-language" class="aacx-v2__select">
                            <option value="auto" <?php selected($current, 'auto'); ?>><?php _e("Auto (match each visitor's browser)", 'allaccessible'); ?></option>
                            <?php foreach ($languages as $code => $name) : ?>
                                <option value="<?php echo esc_attr($code); ?>" <?php selected($current, $code); ?>><?php echo esc_html($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="aacx-v2__help">
                            <?php _e("Auto follows each visitor's browser language, then your page language. Pick a language to show the widget in that language to every visitor unless they choose another.", 'allaccessible'); ?>
                            <?php if ($suggested_name && $current === 'auto') : ?>
                                <br><?php printf(
                                    /* translators: 1: language name, 2: WordPress locale code */
                                    esc_html__('Your WordPress site language is %1$s (%2$s).', 'allaccessible'),
                                    '<strong>' . esc_html($suggested_name) . '</strong>',
                                    esc_html($site_locale)
                                ); ?>
                                <button type="button" class="aacx-v2__btn aacx-v2__btn--ghost aacx-v2__btn--sm" id="aacb-widget-language-use-site" data-code="<?php echo esc_attr($suggested); ?>" style="margin-left: 6px;">
                                    <?php printf(esc_html__('Use %s', 'allaccessible'), esc_html($suggested_name)); ?>
                                </button>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="aacx-v2__field" style="display: flex; align-items: flex-end; gap: var(--aacx-space-3);">
                        <button type="submit" id="aacb-widget-language-save" class="aacx-v2__btn aacx-v2__btn--primary"><?php _e('Save Language', 'allaccessible'); ?></button>
                        <span id="aacb-widget-language-status" class="aacx-hidden" style="font-size: var(--aacx-text-sm); color: var(--aacx-text-muted);"><?php _e('Saving…', 'allaccessible'); ?></span>
                        <span id="aacb-widget-language-success" class="aacx-hidden" style="font-size: var(--aacx-text-sm); color: var(--aacx-ok-700);"><?php _e('Saved. Changes typically apply immediately; allow up to 30 minutes for cached widgets.', 'allaccessible'); ?></span>
                        <span id="aacb-widget-language-error" class="aacx-hidden" style="font-size: var(--aacx-text-sm); color: var(--aacx-danger-700);"></span>
                    </div>
                </div>
            </form>
            <p class="aacx-v2__help" style="margin-top: var(--aacx-space-4);">
                <?php _e('Text-to-Speech voices and other advanced options live in', 'allaccessible'); ?>
                <a href="<?php echo esc_url($widget_settings_url); ?>" target="_blank"><?php _e('Advanced Widget Settings', 'allaccessible'); ?></a>.
            </p>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        var $form = $('#aacb-widget-language-form');
        var $select = $('#aacb-widget-language');

        $('#aacb-widget-language-use-site').on('click', function() {
            $select.val($(this).data('code')).trigger('change');
        });

        $form.on('submit', function(e) {
            e.preventDefault();
            var subdomainID = $form.find('input[name="subdomainID"]').val();
            var $btn = $('#aacb-widget-language-save');
            var $status = $('#aacb-widget-language-status');
            var $ok = $('#aacb-widget-language-success');
            var $err = $('#aacb-widget-language-error');

            if (!subdomainID) {
                $err.removeClass('aacx-hidden').text(<?php echo wp_json_encode(__('Missing site ID. Please refresh and try again.', 'allaccessible')); ?>);
                return;
            }

            $btn.prop('disabled', true);
            $status.removeClass('aacx-hidden');
            $ok.addClass('aacx-hidden');
            $err.addClass('aacx-hidden');

            // Same app-service endpoint the Quick Widget Settings form uses:
            // it busts its own cache and triggers the edge cache invalidation.
            $.ajax({
                url: 'https://app.allaccessible.org/api/save-site-options/' + subdomainID,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    accountID: $form.find('input[name="accountID"]').val(),
                    subdomainID: subdomainID,
                    language: $select.val()
                }),
                success: function() {
                    $.post(ajaxurl, {
                        action: 'aacb_clear_cache',
                        _wpnonce: <?php echo wp_json_encode(wp_create_nonce('aacb_clear_cache')); ?>
                    });
                    if (typeof aacxClearWidgetCaches === 'function') {
                        aacxClearWidgetCaches();
                    }
                    $status.addClass('aacx-hidden');
                    $ok.removeClass('aacx-hidden');
                    $btn.prop('disabled', false);
                },
                error: function(xhr) {
                    $status.addClass('aacx-hidden');
                    $err.removeClass('aacx-hidden').text(<?php echo wp_json_encode(__('Could not save. Please try again.', 'allaccessible')); ?> + (xhr && xhr.status ? ' (' + xhr.status + ')' : ''));
                    $btn.prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php
}
