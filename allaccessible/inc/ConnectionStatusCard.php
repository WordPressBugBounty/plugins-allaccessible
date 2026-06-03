<?php
/**
 * Connection Status card — shows the user EXACTLY which AllAccessible site
 * record this WordPress install is talking to, and surfaces any mismatch
 * between WP's configured URL and the server's canonical host.
 *
 * @package AllAccessible
 * @since   2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AllAccessible_ConnectionStatusCard {

    const NONCE = 'aacb_verify_connection';

    public static function render() {
        if (!class_exists('AllAccessible_ApiClient')) return;

        $wp_url     = (string) get_bloginfo('url');
        $account_id = (string) get_option('aacb_accountID', '');
        $subdomain_id = (int) get_option('aacb_siteID', 0); // wp_options name is legacy; value is the per-site id

        if ($account_id === '') {
            return; // Pre-onboarding — nothing useful to show.
        }

        // Pull current site-validation data. Cached for 30 min by ApiClient.
        $client = AllAccessible_ApiClient::get_instance();
        $opts   = $client->get_site_options();
        $err    = is_wp_error($opts) ? $opts->get_error_message() : null;

        $server_host = self::extract_server_host($opts);
        $tier        = $err ? null : (string) ($opts->_meta->pricingTier ?? $opts->tier ?? 'free');
        $match       = self::compare_hosts($wp_url, $server_host);

        AllAccessible_Debug::console('ConnectionStatusCard', array(
            'wp_url'         => $wp_url,
            'account_id'     => $account_id,
            'subdomain_id'   => $subdomain_id,
            'opts_is_error'  => is_wp_error($opts),
            'opts_error'     => $err,
            'server_host'    => $server_host,
            'tier'           => $tier,
            'host_match'     => $match,
            'opts_type'      => is_object($opts) ? get_class($opts) : gettype($opts),
        ));

        ?>
        <div class="allaccessible-admin aacb-connection-card aacx-v2" style="margin-bottom: var(--aacx-space-6);">
          <div class="aacx-v2__card aacx-v2__card--elevated">
            <div class="aacx-v2__card-header">
              <div>
                <h3 style="margin: 0 0 var(--aacx-space-1);"><?php esc_html_e('Connection status', 'allaccessible'); ?></h3>
                <p style="font-size: var(--aacx-text-sm); color: var(--aacx-text-muted); margin: 0;">
                  <?php esc_html_e('Confirm this WordPress install is linked to the correct AllAccessible site.', 'allaccessible'); ?>
                </p>
              </div>
              <button type="button"
                      class="aacb-verify-connection aacx-v2__btn aacx-v2__btn--secondary aacx-v2__btn--sm"
                      data-nonce="<?php echo esc_attr(wp_create_nonce(self::NONCE)); ?>">
                <?php esc_html_e('Re-verify', 'allaccessible'); ?>
              </button>
            </div>
            <div class="aacx-v2__card-body">
              <?php if ($err) : ?>
                <div class="aacx-v2__banner aacx-v2__banner--danger" style="margin-bottom: var(--aacx-space-4);">
                  <div>
                    <?php esc_html_e('Could not reach AllAccessible right now.', 'allaccessible'); ?>
                    <span style="color: var(--aacx-text-muted);"><?php echo esc_html($err); ?></span>
                  </div>
                </div>
              <?php endif; ?>

              <dl class="aacx-v2__grid aacx-v2__grid--2">
                <?php
                self::row(__('WordPress URL',  'allaccessible'), $wp_url);
                self::row(__('Account ID',     'allaccessible'), $account_id);
                self::row(__('Connected to',   'allaccessible'), $server_host ?: '—');
                self::row(__('Subdomain ID',   'allaccessible'), $subdomain_id > 0 ? '#' . $subdomain_id : '—');
                if ($tier !== null) {
                    self::row(__('Plan tier', 'allaccessible'), self::tier_label($tier));
                }
                ?>
              </dl>

              <?php if (!$err) : ?>
                <div style="margin-top: var(--aacx-space-4);">
                  <?php if ($match === 'ok') : ?>
                    <p style="font-size: var(--aacx-text-sm); color: var(--aacx-ok-700); font-weight: var(--aacx-weight-semibold);">
                      <span aria-hidden="true">✓</span> <?php esc_html_e('WordPress URL matches the connected AllAccessible site.', 'allaccessible'); ?>
                    </p>
                  <?php elseif ($match === 'mismatch') : ?>
                    <div class="aacx-v2__banner aacx-v2__banner--warn">
                      <div>
                        <strong><span aria-hidden="true">⚠</span> <?php esc_html_e('Mismatch detected', 'allaccessible'); ?></strong>
                        <p style="margin-top: var(--aacx-space-1); font-size: var(--aacx-text-sm);">
                          <?php
                          printf(
                              /* translators: 1: WP URL host, 2: server host */
                              esc_html__('WordPress is configured for %1$s but this plugin is linked to %2$s on your AllAccessible account. The Agentic Fixes page will show fixes for the connected site, not this WordPress install.', 'allaccessible'),
                              '<code>' . esc_html(self::host_of($wp_url)) . '</code>',
                              '<code>' . esc_html($server_host ?: '?') . '</code>'
                          );
                          ?>
                        </p>
                        <p style="margin-top: var(--aacx-space-2); font-size: var(--aacx-text-sm);">
                          <?php esc_html_e('Click "Re-verify" to refresh, or contact support if this site needs to be added to your account.', 'allaccessible'); ?>
                        </p>
                      </div>
                    </div>
                  <?php else : /* unknown — server returned nothing */ ?>
                    <p style="font-size: var(--aacx-text-sm); color: var(--aacx-text-muted);">
                      <?php esc_html_e('This site has not been registered with AllAccessible yet. Visit your WordPress homepage to trigger registration, then click "Re-verify".', 'allaccessible'); ?>
                    </p>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <?php self::render_inline_js(); ?>
        <?php
    }

    /* ─── helpers ──────────────────────────────────────────────────── */

    private static function row($label, $value) {
        ?>
        <div>
            <dt class="aacx-v2__page-eyebrow" style="margin-bottom: var(--aacx-space-1);">
                <?php echo esc_html($label); ?>
            </dt>
            <dd style="font-size: var(--aacx-text-sm); color: var(--aacx-text-strong); font-family: var(--aacx-font-mono); word-break: break-all; margin: 0;">
                <?php echo wp_kses_post($value); ?>
            </dd>
        </div>
        <?php
    }

    /**
     * Compare the WP siteurl host to whatever host the server has on record
     * for this account's connected site. Returns 'ok', 'mismatch', or 'unknown'.
     */
    private static function compare_hosts($wp_url, $server_host) {
        $wp_host = self::host_of($wp_url);
        if ($wp_host === null || $server_host === null || $server_host === '') {
            return 'unknown';
        }
        return ($wp_host === strtolower($server_host)) ? 'ok' : 'mismatch';
    }

    private static function host_of($url) {
        if (!is_string($url) || $url === '') return null;
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) return null;
        return strtolower($parts['host']);
    }

    /**
     * Best-effort extraction of the canonical host this account's connected
     * site is bound to on the server side. The validation response returns
     * various fields across legacy and v2 shapes; check each.
     */
    private static function extract_server_host($opts) {
        if (!is_object($opts) && !is_array($opts)) return null;
        $o = is_array($opts) ? (object) $opts : $opts;

        // v2 shape
        if (!empty($o->subdomainHost))   return self::host_of((string) $o->subdomainHost);
        if (!empty($o->siteUrl))         return self::host_of((string) $o->siteUrl);
        if (!empty($o->canonicalSiteUrl))return self::host_of((string) $o->canonicalSiteUrl);
        if (!empty($o->host))            return strtolower((string) $o->host);

        // Legacy
        if (!empty($o->subdomain) && !empty($o->domain)) {
            return strtolower($o->subdomain . '.' . $o->domain);
        }
        if (!empty($o->domain)) return strtolower((string) $o->domain);

        return null;
    }

    private static function tier_label($tier) {
        $map = array(
            'free'        => __('Free',        'allaccessible'),
            'starter'     => __('Starter',     'allaccessible'),
            'enterprise'  => __('Enterprise',  'allaccessible'),
            'legacy'      => __('Legacy',      'allaccessible'),
            'trial'       => __('Trial',       'allaccessible'),
        );
        return isset($map[$tier]) ? $map[$tier] : esc_html($tier);
    }

    private static function render_inline_js() {
        static $printed = false;
        if ($printed) return;
        $printed = true;
        ?>
        <script>
        (function($) {
            $(document).on('click', '.aacb-verify-connection', function(e) {
                e.preventDefault();
                const $btn = $(this);
                $btn.prop('disabled', true).text('<?php echo esc_js(__('Verifying…', 'allaccessible')); ?>');
                $.post(ajaxurl, {
                    action: 'aacb_verify_connection',
                    _wpnonce: $btn.data('nonce'),
                }).always(function() {
                    window.location.reload();
                });
            });
        })(jQuery);
        </script>
        <?php
    }
}
