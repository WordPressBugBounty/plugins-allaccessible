<?php
/**
 * Tier feature gate
 *
 * @package AllAccessible
 * @since   2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AllAccessible_TierGate {

    /**
     * Known feature keys the plugin gates on. Mirrors the server's
     * resolved paid-tier feature list.
     *
     * Map value is the customer-facing label used in upgrade CTAs.
     */
    private static $known_features = null;

    private static function known_features() {
        if (self::$known_features === null) {
            self::$known_features = array(
                'agentic_fixes_approve'   => __('Approve, edit, and revert agentic fixes',  'allaccessible'),
                'image_alt_text_manager'  => __('Image Descriptions',                       'allaccessible'),
                'reports_export'          => __('Reports & Statement deep-links',           'allaccessible'),
                'per_page_editor_metabox' => __('Per-page accessibility breakdown in editor', 'allaccessible'),
                'notification_center'     => __('Notification center',                      'allaccessible'),
                'csv_export'              => __('CSV export of fixes',                      'allaccessible'),
                'pdf_export'              => __('PDF report export',                        'allaccessible'),
                'white_label'             => __('White-label branding',                     'allaccessible'),
                'weekly_scanning'         => __('Weekly automatic scans',                   'allaccessible'),
                'change_detection'        => __('Page change detection',                    'allaccessible'),
                'accommodation_widget'    => __('Accommodation request widget',             'allaccessible'),
            );
        }
        return self::$known_features;
    }

    /**
     * Does the current account have this feature enabled?
     *
     * Returns false on any error (missing account, API unreachable, etc.) so
     * callers can render an upgrade CTA without special-casing failure modes.
     *
     * @param string $feature One of the known feature keys.
     * @return bool
     */
    public static function can($feature) {
        if (!is_string($feature) || $feature === '') return false;
        if (!class_exists('AllAccessible_ApiClient')) return false;

        $opts = AllAccessible_ApiClient::get_instance()->get_site_options();
        if (is_wp_error($opts)) return false;

        // /validate returns the projected features array under `features`.
        $features = array();
        if (is_object($opts) && isset($opts->features)) {
            $features = (array) $opts->features;
        } elseif (is_array($opts) && isset($opts['features'])) {
            $features = (array) $opts['features'];
        }
        return in_array($feature, $features, true);
    }

    /**
     * Render a compact inline upgrade prompt. Use as the else-branch of can().
     *
     * @param string $feature      Feature key (label looked up from known list).
     * @param array  $opts {
     *   @type string $heading       Override headline. Default uses feature label.
     *   @type string $description   Override copy.
     *   @type string $url           Upgrade URL. Defaults to app billing.
     *   @type bool   $compact       Tight padding when true (sidebar / wizard contexts).
     * }
     */
    public static function render_upgrade_cta($feature, $opts = array()) {
        $known        = self::known_features();
        $label        = isset($known[$feature]) ? $known[$feature] : (string) $feature;
        $heading      = isset($opts['heading'])     ? (string) $opts['heading']     : sprintf(
            /* translators: %s: feature label */
            __('%s is available on paid plans', 'allaccessible'),
            $label
        );
        $description  = isset($opts['description']) ? (string) $opts['description'] : __('Upgrade to unlock this feature plus everything else included in the AllAccessible AI suite.', 'allaccessible');
        $url          = isset($opts['url'])         ? esc_url((string) $opts['url']) : 'https://app.allaccessible.org/billing';
        $compact      = !empty($opts['compact']);
        $body_padding = $compact ? 'var(--aacx-space-4)' : 'var(--aacx-space-6)';
        ?>
        <div class="aacb-tier-gate allaccessible-admin aacx-v2">
          <div class="aacx-v2__card aacx-v2__card--elevated" style="overflow: hidden; border-color: var(--aacx-warn-200, #fef3c7);">
            <div style="display: flex;">
              <div style="width: 4px; flex-shrink: 0; background: var(--aacx-warn-500);"></div>
              <div style="flex: 1; padding: <?php echo esc_attr($body_padding); ?>;">
                <p class="aacx-v2__page-eyebrow" style="color: var(--aacx-warn-700); margin-bottom: var(--aacx-space-1);">
                  <?php esc_html_e('Upgrade required', 'allaccessible'); ?>
                </p>
                <h3 style="margin-bottom: var(--aacx-space-2);"><?php echo esc_html($heading); ?></h3>
                <p style="font-size: var(--aacx-text-sm); color: var(--aacx-text); margin-bottom: var(--aacx-space-4); max-width: 60ch;">
                  <?php echo esc_html($description); ?>
                </p>
                <a href="<?php echo $url; ?>" target="_blank" rel="noopener" class="aacx-v2__btn aacx-v2__btn--primary">
                  <?php esc_html_e('Upgrade plan', 'allaccessible'); ?>
                  <span aria-hidden="true">→</span>
                </a>
              </div>
            </div>
          </div>
        </div>
        <?php
    }

    /**
     * Convenience wrapper for the common pattern: render upgrade prompt
     * inline and return false so the caller's gated code skips.
     *
     * @return bool false (so callers can `if (!TierGate::gate_or_cta('x')) return;`)
     */
    public static function gate_or_cta($feature, $opts = array()) {
        if (self::can($feature)) return true;
        self::render_upgrade_cta($feature, $opts);
        return false;
    }

    /**
     * Return the full features array surfaced by /validate. Useful for
     * debug panels and the Connection Status card.
     *
     * @return array
     */
    public static function all_active_features() {
        if (!class_exists('AllAccessible_ApiClient')) return array();
        $opts = AllAccessible_ApiClient::get_instance()->get_site_options();
        if (is_wp_error($opts)) return array();
        if (is_object($opts) && isset($opts->features)) return (array) $opts->features;
        if (is_array($opts) && isset($opts['features'])) return (array) $opts['features'];
        return array();
    }
}
