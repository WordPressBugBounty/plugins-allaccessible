<?php
/**
 * Onboarding Wizard for AllAccessible Plugin
 *
 * @package AllAccessible
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

class AllAccessible_OnboardingWizard {

    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'register_wizard_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_aacb_complete_wizard', array($this, 'ajax_complete_wizard'));
        add_action('wp_ajax_aacb_skip_wizard', array($this, 'ajax_skip_wizard'));
    }

    /**
     * Register hidden wizard page (not in menu)
     */
    public function register_wizard_page() {
        add_submenu_page(
            null, // No parent = hidden from menu
            __('AllAccessible Setup', 'allaccessible'),
            __('Setup', 'allaccessible'),
            'manage_options',
            'allaccessible-wizard',
            array($this, 'render_wizard')
        );
    }

    /**
     * Enqueue CSS and JS for wizard page
     */
    public function enqueue_assets($hook) {
        // Check if we're on the wizard page
        if (!isset($_GET['page']) || $_GET['page'] !== 'allaccessible-wizard') {
            return;
        }

        // Legacy Tailwind utility stylesheet (still ships with the build)
        wp_enqueue_style(
            'allaccessible-admin',
            AACB_CSS . 'admin.css',
            array(),
            AACB_VERSION
        );

        // Admin v2 design system — single source of truth for the redesign
        wp_enqueue_style(
            'aacx-v2-admin',
            AACB_CSS . 'admin-v2.css',
            array(),
            aacb_asset_ver('admin-v2.css')
        );

        // Hide WordPress admin notices on the wizard page for a clean experience
        $custom_css = "
            .notice, .updated, .error, .update-nag,
            div.notice, div.updated, div.error, div.update-nag {
                display: none !important;
            }
            /* Soften the wp-admin chrome around the wizard */
            #wpcontent { padding-left: 0 !important; }
            .aacx-v2-wizard-shell {
                min-height: calc(100vh - 32px);
                background: var(--aacx-bg, #f8fafc);
            }
        ";
        wp_add_inline_style('aacx-v2-admin', $custom_css);
    }

    /**
     * Render the wizard UI
     */
    public function render_wizard() {
        // Remove all admin notices on wizard page for clean experience
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');

        $site_url    = get_bloginfo('wpurl');
        $admin_email = get_bloginfo('admin_email');

        // Step labels for the stepper: a single "Get started" step
        // (email + plan picker + Continue on one screen), then First scan.
        $steps = array(
            1 => __('Get started', 'allaccessible'),
            2 => __('First scan',  'allaccessible'),
        );
        ?>
        <div class="wrap allaccessible-admin">
            <div class="aacx-v2 aacx-v2-wizard-shell">
                <div class="aacx-v2__page" role="region" aria-label="<?php esc_attr_e('AllAccessible setup', 'allaccessible'); ?>">

                    <!-- Brand header removed — the "Welcome to AllAccessible"
                         h2 inside the active step already brands the page. Stepper stays as
                         the canonical wayfinding. -->

                    <!-- Step indicator (always visible) -->
                    <nav class="aacx-v2__stepper" aria-label="<?php esc_attr_e('Setup progress', 'allaccessible'); ?>" id="aacb-wizard-stepper">
                        <?php
                        $step_count = count($steps);
                        $i = 0;
                        foreach ($steps as $num => $label):
                            $i++;
                            $is_last = ($i === $step_count);
                            ?>
                            <div class="aacx-v2__step<?php echo $num === 1 ? ' aacx-v2__step--active' : ''; ?>"
                                 data-step="<?php echo (int) $num; ?>"
                                 <?php echo $num === 1 ? 'aria-current="step"' : ''; ?>>
                                <span class="aacx-v2__step-dot"><?php echo (int) $num; ?></span>
                                <span class="aacx-v2__step-label"><?php echo esc_html($label); ?></span>
                            </div>
                            <?php if (!$is_last): ?>
                                <span class="aacx-v2__step-line" aria-hidden="true"></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </nav>

                    <!-- =================================================
                         Step 1: Get started (email + plan picker)
                         ================================================= -->
                    <section id="wizard-step-1" class="wizard-step aacx-v2__card aacx-v2__card--elevated" data-step="1">
                        <div class="aacx-v2__card-body">
                            <div class="aacx-v2__page-eyebrow"><?php esc_html_e('Step 1 of 2', 'allaccessible'); ?></div>
                            <h2 tabindex="-1" class="aacx-v2__page-title" style="font-size: var(--aacx-text-3xl);" id="wizard-step-1-heading">
                                <?php esc_html_e('Welcome to AllAccessible', 'allaccessible'); ?>
                            </h2>
                            <p class="aacx-v2__page-desc aacb-create-only" style="margin-top: var(--aacx-space-3);">
                                <?php esc_html_e('Make this WordPress site usable for every visitor. Enter your email and pick a plan to get started.', 'allaccessible'); ?>
                            </p>
                            <!-- ─── 1. FEATURE MINI-DEMOS ──────────────────────────────── -->
                            <div class="aacb-create-only" style="display:flex; align-items:center; gap: var(--aacx-space-2); margin: var(--aacx-space-4) 0 var(--aacx-space-3);">
                                <span style="display:inline-block; width: 28px; height: 2px; background: linear-gradient(90deg, #4f46e5 0%, #7c3aed 100%);"></span>
                                <p class="aacx-v2__page-eyebrow" style="margin: 0; color: var(--aacx-text-strong); font-weight: 700;">
                                    <?php esc_html_e('See what an account does for you', 'allaccessible'); ?>
                                </p>
                            </div>

                            <div class="aacx-v2__grid aacx-v2__grid--3 aacb-create-only" style="gap: var(--aacx-space-4);">
                                <!-- Accessibility scans — blue hover ring -->
                                <div style="background: var(--aacx-surface, #fff); border: 1px solid var(--aacx-border); border-radius: 12px; padding: var(--aacx-space-4); transition: box-shadow 200ms ease, transform 200ms ease;"
                                     onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 0 0 2px rgba(59,130,246,0.4), 0 8px 20px rgba(37,99,235,0.10)';"
                                     onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='none';">
                                    <strong style="font-size: 15px; color: var(--aacx-text-strong); display:block; margin-bottom: 4px;">
                                        <?php esc_html_e('Accessibility scans', 'allaccessible'); ?>
                                    </strong>
                                    <p style="font-size: 13px; color: var(--aacx-text-muted); margin: 0 0 var(--aacx-space-3); line-height: 1.45;">
                                        <?php esc_html_e('Every published page checked against WCAG 2.2, ADA, and EAA. Severity-tagged.', 'allaccessible'); ?>
                                    </p>
                                    <div style="border-radius: 8px; border: 1px solid var(--aacx-border); background: var(--aacx-surface-alt, #fafafa); padding: 10px 12px; font-family: 'SFMono-Regular', Menlo, monospace; font-size: 12px;">
                                        <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom: 6px;">
                                            <span style="color:var(--aacx-text-muted); font-size: 11px;">about-us</span>
                                            <span style="font-size: 22px; font-weight: 800; color: var(--aacx-text-strong); line-height: 1;">87 <small style="font-size: 11px; font-weight:500; color:var(--aacx-text-muted);">/100</small></span>
                                        </div>
                                        <div style="height: 5px; background: var(--aacx-border); border-radius: 999px; overflow: hidden;">
                                            <div style="height: 100%; width: 87%; background: linear-gradient(90deg, #10b981 0%, #34d399 100%);"></div>
                                        </div>
                                        <div style="display:flex; gap: 8px; margin-top: 6px; font-family: inherit; font-size: 10.5px;">
                                            <span style="color: #10b981 !important;">✓ <?php esc_html_e('24 fixed', 'allaccessible'); ?></span>
                                            <span style="color: #f59e0b !important;">⚠ <?php esc_html_e('3 to review', 'allaccessible'); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Agentic AI fixes — purple hover ring -->
                                <div style="background: var(--aacx-surface, #fff); border: 1px solid var(--aacx-border); border-radius: 12px; padding: var(--aacx-space-4); transition: box-shadow 200ms ease, transform 200ms ease;"
                                     onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 0 0 2px rgba(168,85,247,0.4), 0 8px 20px rgba(124,58,237,0.12)';"
                                     onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='none';">
                                    <strong style="font-size: 15px; color: var(--aacx-text-strong); display:block; margin-bottom: 4px;">
                                        <?php esc_html_e('Agentic AI fixes', 'allaccessible'); ?>
                                    </strong>
                                    <p style="font-size: 13px; color: var(--aacx-text-muted); margin: 0 0 var(--aacx-space-3); line-height: 1.45;">
                                        <?php esc_html_e('AI drafts the fix; your team approves before anything ships.', 'allaccessible'); ?>
                                    </p>
                                    <div style="border-radius: 8px; border: 1px solid var(--aacx-border); background: var(--aacx-surface-alt, #fafafa); padding: 10px 12px; font-family: 'SFMono-Regular', Menlo, monospace; font-size: 12px;">
                                        <div style="color:var(--aacx-text-muted); font-size:10.5px; margin-bottom: 5px;"><?php esc_html_e('Issue: button missing accessible name', 'allaccessible'); ?></div>
                                        <div style="padding: 4px 8px; border-radius: 6px; margin-bottom: 4px; background: rgba(239,68,68,0.12); color:#ef4444 !important; text-decoration: line-through; text-decoration-color: rgba(239,68,68,0.55); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">&lt;button&gt;🛒&lt;/button&gt;</div>
                                        <div style="padding: 4px 8px; border-radius: 6px; background: rgba(16,185,129,0.12); color:#10b981 !important; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><strong style="color:#a855f7 !important; font-size:10px; font-weight:800; letter-spacing:0.04em; margin-right:4px;">✦ <?php esc_html_e('AI fix', 'allaccessible'); ?></strong>&lt;button aria-label="Add to cart"&gt;🛒&lt;/button&gt;</div>
                                    </div>
                                </div>

                                <!-- Smart alt text — teal hover ring -->
                                <div style="background: var(--aacx-surface, #fff); border: 1px solid var(--aacx-border); border-radius: 12px; padding: var(--aacx-space-4); transition: box-shadow 200ms ease, transform 200ms ease;"
                                     onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 0 0 2px rgba(20,184,166,0.4), 0 8px 20px rgba(13,148,136,0.12)';"
                                     onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='none';">
                                    <strong style="font-size: 15px; color: var(--aacx-text-strong); display:block; margin-bottom: 4px;">
                                        <?php esc_html_e('Smart alt text', 'allaccessible'); ?>
                                    </strong>
                                    <p style="font-size: 13px; color: var(--aacx-text-muted); margin: 0 0 var(--aacx-space-3); line-height: 1.45;">
                                        <?php esc_html_e('Missing alt generated for every image, in the language your visitors use.', 'allaccessible'); ?>
                                    </p>
                                    <div style="border-radius: 8px; border: 1px solid var(--aacx-border); background: var(--aacx-surface-alt, #fafafa); padding: 10px 12px;">
                                        <div style="display:flex; gap: 10px; align-items: stretch;">
                                            <img src="<?php echo esc_url(AACB_IMG . 'demo-alt-text-sample.jpg'); ?>"
                                                 alt=""
                                                 style="flex: 0 0 64px; width: 64px; height: 64px; border-radius: 6px; object-fit: cover; display:block; box-shadow: 0 2px 6px rgba(0,0,0,0.12);">
                                            <div style="flex:1; font-size: 11px; color: var(--aacx-text); line-height: 1.45; display:flex; flex-direction:column; gap: 4px;">
                                                <span style="display:inline-flex; align-self:flex-start; align-items:center; gap:4px; padding: 2px 6px; border-radius: 999px; background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%); color:#fff; font-weight:700; font-size:9px; text-transform:uppercase; letter-spacing:0.06em;">✦ <?php esc_html_e('AI generated', 'allaccessible'); ?></span>
                                                <span style="font-family: 'SFMono-Regular', Menlo, monospace; color: var(--aacx-text-strong);">
                                                    "<?php esc_html_e('A golden retriever sitting in a sunlit park, wearing a pink bandana.', 'allaccessible'); ?>"
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- ─── 2. GET STARTED FORM (email + plan picker + CTA) ───
                                 Single form: submit the email + plan and the
                                 server creates or links the account.
                            -->
                            <div style="display:flex; align-items:center; gap: var(--aacx-space-2); margin: var(--aacx-space-5) 0 var(--aacx-space-3);">
                                <span style="display:inline-block; width: 28px; height: 2px; background: linear-gradient(90deg, #4f46e5 0%, #7c3aed 100%);"></span>
                                <p class="aacx-v2__page-eyebrow" style="margin: 0; color: var(--aacx-text-strong); font-weight: 700;">
                                    <?php esc_html_e('Get started', 'allaccessible'); ?>
                                </p>
                            </div>

                            <form id="aacb-wizard-form" novalidate>
                                <input type="hidden" name="site_url" value="<?php echo esc_attr($site_url); ?>">
                                <input type="hidden" id="wizard-selected-tier" name="tier" value="trial">

                                <!-- Email field -->
                                <div class="aacx-v2__field" style="margin-bottom: var(--aacx-space-4);">
                                    <label class="aacx-v2__label" for="wizard-email"><?php esc_html_e('Email address', 'allaccessible'); ?></label>
                                    <input
                                        type="email"
                                        name="email"
                                        id="wizard-email"
                                        class="aacx-v2__input"
                                        value="<?php echo esc_attr($admin_email); ?>"
                                        required
                                        placeholder="you@example.com"
                                        autocomplete="email"
                                        style="font-size: 16px; padding: 10px 12px;">
                                    <span id="wizard-email-help" class="aacx-v2__help" style="font-size: 12.5px;">
                                        <?php esc_html_e("We'll create a new account or link your existing one — same email, either way.", 'allaccessible'); ?>
                                    </span>
                                </div>

                                <!-- Plan picker — compact radio cards (Free vs Trial).
                                     The wrapper id `wizard-plan-section` is hidden by JS
                                     for existing customers, who keep their current plan. -->
                                <div id="wizard-plan-section" role="radiogroup" aria-label="<?php esc_attr_e('Plan', 'allaccessible'); ?>" style="display:grid; grid-template-columns: 1fr 1fr; gap: var(--aacx-space-3); margin-bottom: var(--aacx-space-4);">

                                    <!-- Free plan card -->
                                    <label class="aacb-plan-option" style="cursor:pointer; display:block; padding: var(--aacx-space-4); border: 2px solid var(--aacx-border); border-radius: 12px; background: var(--aacx-surface, #fff); transition: border-color 150ms ease, box-shadow 150ms ease; position: relative;">
                                        <input type="radio" name="aacb_plan" value="free" style="position:absolute; opacity:0; pointer-events:none;">
                                        <span style="display:inline-block; font-size:10.5px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; padding: 3px 8px; border-radius: 999px; background:#f3f4f6; color:#374151 !important;">
                                            <?php esc_html_e('Free forever', 'allaccessible'); ?>
                                        </span>
                                        <div style="margin-top: var(--aacx-space-2); font-size: 22px; font-weight: 800; color: var(--aacx-text-strong); line-height: 1;">
                                            $0 <span style="font-size: 13px; font-weight: 500; color: var(--aacx-text-muted);">/ mo</span>
                                        </div>
                                        <p style="font-size: 12.5px; color: var(--aacx-text-muted); margin: var(--aacx-space-2) 0 0; line-height: 1.4;">
                                            <?php esc_html_e('Widget, contrast + sizing controls, essential accessibility tools.', 'allaccessible'); ?>
                                        </p>
                                    </label>

                                    <!-- Trial plan card — pre-selected (matches hidden input default 'trial') -->
                                    <label class="aacb-plan-option aacb-plan-option--checked" style="cursor:pointer; display:block; padding: var(--aacx-space-4); border: 2px solid #7c3aed; border-radius: 12px; background: linear-gradient(135deg, rgba(124,58,237,0.04) 0%, rgba(79,70,229,0.04) 100%); transition: border-color 150ms ease, box-shadow 150ms ease; position: relative; box-shadow: 0 0 0 3px rgba(124,58,237,0.10);">
                                        <input type="radio" name="aacb_plan" value="trial" checked style="position:absolute; opacity:0; pointer-events:none;">
                                        <div style="position:absolute; top:-9px; right: var(--aacx-space-3); background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%); color:#fff !important; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; padding: 3px 8px; border-radius: 999px; box-shadow: 0 4px 12px rgba(139,92,246,0.35);">
                                            ✦ <?php esc_html_e('Most popular', 'allaccessible'); ?>
                                        </div>
                                        <span style="display:inline-block; font-size:10.5px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; padding: 3px 8px; border-radius: 999px; background:#ede9fe; color:#5b21b6 !important;">
                                            <?php esc_html_e('AllAccessible AI', 'allaccessible'); ?>
                                        </span>
                                        <div style="margin-top: var(--aacx-space-2); font-size: 22px; font-weight: 800; color: var(--aacx-text-strong); line-height: 1;">
                                            <?php esc_html_e('7 days free', 'allaccessible'); ?> <span style="font-size: 12.5px; font-weight: 500; color: var(--aacx-text-muted);"><?php esc_html_e('then $10/mo', 'allaccessible'); ?></span>
                                        </div>
                                        <p style="font-size: 12.5px; color: var(--aacx-text-muted); margin: var(--aacx-space-2) 0 0; line-height: 1.4;">
                                            <?php esc_html_e('Agentic AI fixes, smart alt text, WCAG/ADA/EAA audits.', 'allaccessible'); ?>
                                        </p>
                                    </label>
                                </div>

                                <!-- Auto-state confirmation. Shown only when state === 'auto'.
                                     Displays the matched email + site URL so the user
                                     confirms what they're reconnecting before clicking. -->
                                <div id="wizard-auto-confirm" style="display:none; margin-bottom: var(--aacx-space-4); padding: 14px 16px; background: linear-gradient(135deg, rgba(124,58,237,0.06) 0%, rgba(79,70,229,0.06) 100%); border: 1px solid rgba(124,58,237,0.25); border-radius: 10px;">
                                    <div style="display:flex; align-items:center; gap: 10px;">
                                        <span aria-hidden="true" style="font-size: 24px;">✦</span>
                                        <div style="flex:1;">
                                            <strong style="display:block; font-size: 14px; color: var(--aacx-text-strong); margin-bottom: 2px;">
                                                <?php esc_html_e('Ready to reconnect', 'allaccessible'); ?>
                                            </strong>
                                            <span id="wizard-auto-confirm-detail" style="font-size: 12.5px; color: var(--aacx-text-muted);">
                                                <!-- populated by JS: "Linking {site} to {email}" -->
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Link-pending note. Shown when the site is on a
                                     different account. Hidden by default; JS toggles it.
                                     Explains that dashboard visibility waits for the
                                     existing owner to approve access. -->
                                <div id="wizard-pending-note" style="display:none; margin-bottom: var(--aacx-space-3); padding: 10px 12px; background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.35); border-radius: 8px; font-size: 12.5px; color: #92400e !important;">
                                    <strong style="color:#92400e !important;"><?php esc_html_e('Heads up:', 'allaccessible'); ?></strong>
                                    <?php esc_html_e("This site is on another AllAccessible account. The plugin will work right away, but the site only appears in your dashboard once the existing admin approves your access — they'll get an email automatically.", 'allaccessible'); ?>
                                </div>

                                <!-- Loading + error/welcome banner (toggled via JS) -->
                                <div id="wizard-status" style="display:none; margin-bottom: var(--aacx-space-3);"></div>

                                <!-- Submit -->
                                <button type="submit" id="wizard-submit-btn"
                                        class="aacx-v2__btn aacx-v2__btn--primary aacx-v2__btn--lg"
                                        style="width: 100%; justify-content: center; font-weight: 700;">
                                    <span id="wizard-submit-text"><?php esc_html_e('Continue', 'allaccessible'); ?> →</span>
                                </button>

                                <!-- Consent disclosure (browse-wrap pattern) -->
                                <p class="aacx-v2__help" style="text-align: center; margin: var(--aacx-space-2) 0 0; font-size: 12px;">
                                    <?php
                                    printf(
                                        /* translators: 1: Terms link, 2: Privacy Policy link */
                                        esc_html__('By continuing you agree to our %1$s and %2$s.', 'allaccessible'),
                                        '<a href="https://www.allaccessible.org/terms-conditions" target="_blank" rel="noopener">' . esc_html__('Terms & Conditions', 'allaccessible') . '</a>',
                                        '<a href="https://www.allaccessible.org/privacy-policy" target="_blank" rel="noopener">' . esc_html__('Privacy Policy', 'allaccessible') . '</a>'
                                    );
                                    ?>
                                </p>

                                <!-- Skip-for-now (tertiary text link) -->
                                <p style="text-align: center; margin: var(--aacx-space-3) 0 0;">
                                    <a href="#" onclick="event.preventDefault(); aacbWizardSkip();"
                                       style="color: var(--aacx-text-muted); font-size: 12.5px; text-decoration: underline; text-underline-offset: 3px;">
                                        <?php esc_html_e('Skip and use just the free widget', 'allaccessible'); ?>
                                    </a>
                                </p>
                            </form>

                            <!-- ─── 3. CREDIBILITY (proof band) ──────────────────────── -->
                            <div class="aacb-wizard-proof aacb-create-only" style="
                                margin-top: var(--aacx-space-5);
                                padding: var(--aacx-space-4) var(--aacx-space-5);
                                background: linear-gradient(135deg, #1e3a8a 0%, #4338ca 50%, #6d28d9 100%);
                                border-radius: 12px;
                                box-shadow: 0 8px 24px rgba(67, 56, 202, 0.22), 0 2px 8px rgba(0,0,0,0.06);
                                color: #fff;
                                position: relative;
                                overflow: hidden;
                            ">
                                <div aria-hidden="true" style="position:absolute; top:-60px; right:-60px; width:200px; height:200px; border-radius:50%; background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%); pointer-events:none;"></div>
                                <div aria-hidden="true" style="position:absolute; bottom:-80px; left:30%; width:240px; height:240px; border-radius:50%; background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%); pointer-events:none;"></div>

                                <div style="position:relative; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: var(--aacx-space-4);">
                                    <div style="display:flex; gap: var(--aacx-space-6); flex-wrap: wrap;">
                                        <div>
                                            <p style="font-size: 28px; font-weight: 800; margin: 0; line-height: 1; letter-spacing: -0.02em; color:#fff; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                                220M<span style="font-size:0.6em; opacity:0.85;">+</span>
                                            </p>
                                            <p style="font-size: 10.5px; color: rgba(255,255,255,0.85); margin: 4px 0 0; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600;">
                                                <?php esc_html_e('Interactions / month', 'allaccessible'); ?>
                                            </p>
                                        </div>
                                        <div>
                                            <p style="font-size: 28px; font-weight: 800; margin: 0; line-height: 1; letter-spacing: -0.02em; color:#fff; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                                8.2<span style="font-size:0.7em; opacity:0.85;">M</span>
                                            </p>
                                            <p style="font-size: 10.5px; color: rgba(255,255,255,0.85); margin: 4px 0 0; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600;">
                                                <?php esc_html_e('Visitors served / month', 'allaccessible'); ?>
                                            </p>
                                        </div>
                                        <div>
                                            <p style="font-size: 28px; font-weight: 800; margin: 0; line-height: 1; letter-spacing: -0.02em; color:#fff; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                                5<span style="font-size:0.55em; opacity:0.85; margin-left:4px;">yrs</span>
                                            </p>
                                            <p style="font-size: 10.5px; color: rgba(255,255,255,0.85); margin: 4px 0 0; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600;">
                                                <?php esc_html_e('Protecting WordPress', 'allaccessible'); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div style="display:flex; flex-wrap: wrap; gap: 6px;">
                                        <span style="font-size:11px; font-weight:700; padding: 5px 10px; border-radius: 6px; background: rgba(255,255,255,0.18); color:#fff; backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.2);"><?php esc_html_e('WCAG 2.2', 'allaccessible'); ?></span>
                                        <span style="font-size:11px; font-weight:700; padding: 5px 10px; border-radius: 6px; background: rgba(255,255,255,0.18); color:#fff; backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.2);"><?php esc_html_e('ADA', 'allaccessible'); ?></span>
                                        <span style="font-size:11px; font-weight:700; padding: 5px 10px; border-radius: 6px; background: rgba(255,255,255,0.18); color:#fff; backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.2);"><?php esc_html_e('Section 508', 'allaccessible'); ?></span>
                                        <span style="font-size:11px; font-weight:700; padding: 5px 10px; border-radius: 6px; background: rgba(255,255,255,0.18); color:#fff; backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.2);"><?php esc_html_e('EAA 2025', 'allaccessible'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- ─── 4. TRUST FOOTER ──────────────────────────────────── -->
                            <div class="aacb-create-only" style="
                                margin-top: var(--aacx-space-4);
                                padding-top: var(--aacx-space-3);
                                border-top: 1px solid var(--aacx-border);
                                display:flex;
                                flex-wrap: wrap;
                                justify-content: space-between;
                                align-items: center;
                                gap: var(--aacx-space-2);
                                color: var(--aacx-text-muted);
                                font-size: 12.5px;
                            ">
                                <div style="display:flex; flex-wrap: wrap; gap: var(--aacx-space-5);">
                                    <span style="display:inline-flex; align-items:center; gap: var(--aacx-space-1);">
                                        <span class="dashicons dashicons-yes-alt" aria-hidden="true" style="font-size:16px; color: var(--aacx-ok-600, #15803d);"></span>
                                        <?php esc_html_e('Free to start', 'allaccessible'); ?>
                                    </span>
                                    <span style="display:inline-flex; align-items:center; gap: var(--aacx-space-1);">
                                        <span class="dashicons dashicons-yes-alt" aria-hidden="true" style="font-size:16px; color: var(--aacx-ok-600, #15803d);"></span>
                                        <?php esc_html_e('No credit card required', 'allaccessible'); ?>
                                    </span>
                                    <span style="display:inline-flex; align-items:center; gap: var(--aacx-space-1);">
                                        <span class="dashicons dashicons-lock" aria-hidden="true" style="font-size:16px; color: var(--aacx-ok-600, #15803d);"></span>
                                        <?php esc_html_e('Privacy-first — GDPR / CCPA', 'allaccessible'); ?>
                                    </span>
                                    <span style="display:inline-flex; align-items:center; gap: var(--aacx-space-1);">
                                        <span class="dashicons dashicons-update" aria-hidden="true" style="font-size:16px; color: var(--aacx-ok-600, #15803d);"></span>
                                        <?php esc_html_e('Cancel any time on paid plans', 'allaccessible'); ?>
                                    </span>
                                </div>
                                <span style="font-size: var(--aacx-text-xs); color: var(--aacx-text-muted);">
                                    <?php esc_html_e('Trusted on thousands of WordPress sites worldwide.', 'allaccessible'); ?>
                                </span>
                            </div>
                        </div>
                    </section>

                    <!-- =================================================
                         Step 2: First scan (success state)
                         ================================================= -->
                    <section id="wizard-step-2" class="wizard-step aacx-v2__card aacx-v2__card--elevated" data-step="2" hidden>
                        <div class="aacx-v2__card-header">
                            <div>
                                <div class="aacx-v2__page-eyebrow"><?php esc_html_e('Step 2 of 2', 'allaccessible'); ?></div>
                                <h2 tabindex="-1" id="wizard-step-2-heading"><?php esc_html_e('Run your first scan', 'allaccessible'); ?></h2>
                            </div>
                            <span class="aacx-v2__badge aacx-v2__badge--ok"><?php esc_html_e('Account ready', 'allaccessible'); ?></span>
                        </div>
                        <div class="aacx-v2__card-body">
                            <div class="aacx-v2__banner aacx-v2__banner--ok" style="margin-bottom: var(--aacx-space-6);">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                                <div>
                                    <strong><?php esc_html_e('Your account is set up.', 'allaccessible'); ?></strong>
                                    <p style="margin-top: var(--aacx-space-1); font-size: var(--aacx-text-sm);">
                                        <?php esc_html_e('The AllAccessible agents are ready to scan this site for accessibility issues.', 'allaccessible'); ?>
                                    </p>
                                </div>
                            </div>

                            <?php
                            if (class_exists('AllAccessible_ScanTriggerPanel')) {
                                AllAccessible_ScanTriggerPanel::render(array(
                                    'context'          => 'wizard',
                                    'heading'          => '', // step section already renders the H2 — avoid a duplicate
                                    'description'      => __('AllAccessible automatically finds every published page on your site — no sitemap needed. Start your first scan below; you can re-run it any time from the Agentic Fixes page.', 'allaccessible'),
                                    'success_redirect' => admin_url('admin.php?page=allaccessible'),
                                    'compact'          => true,
                                ));
                            }
                            ?>
                        </div>
                        <div class="aacx-v2__card-footer">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=allaccessible')); ?>" class="aacx-v2__btn aacx-v2__btn--ghost">
                                <?php esc_html_e('Skip for now', 'allaccessible'); ?>
                            </a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=allaccessible')); ?>" class="aacx-v2__btn aacx-v2__btn--primary aacx-v2__btn--lg">
                                <?php esc_html_e('Go to dashboard', 'allaccessible'); ?>
                            </a>
                        </div>
                    </section>

                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Map of wizard-step DOM id → stepper position.
            var stepMap = {
                1: 1, // Get started (email + plan picker)
                2: 2  // First scan
            };

            function updateStepper(stepperPos) {
                $('#aacb-wizard-stepper .aacx-v2__step').each(function() {
                    var pos = parseInt($(this).data('step'), 10);
                    $(this).removeClass('aacx-v2__step--active aacx-v2__step--done');
                    $(this).removeAttr('aria-current');
                    if (pos < stepperPos) {
                        $(this).addClass('aacx-v2__step--done');
                    } else if (pos === stepperPos) {
                        $(this).addClass('aacx-v2__step--active');
                        $(this).attr('aria-current', 'step');
                    }
                });
                // Connecting lines reflect completion
                $('#aacb-wizard-stepper .aacx-v2__step-line').each(function(idx) {
                    if (idx + 1 < stepperPos) {
                        $(this).addClass('aacx-v2__step-line--done');
                    } else {
                        $(this).removeClass('aacx-v2__step-line--done');
                    }
                });
            }

            function showStep(step) {
                $('.wizard-step').attr('hidden', true);
                var $target = $('#wizard-step-' + step);
                $target.removeAttr('hidden');

                // Update stepper
                var stepperPos = stepMap[step] || 1;
                updateStepper(stepperPos);

                // Move focus to the new step's heading for screen-reader users
                var $heading = $('#wizard-step-' + step + '-heading');
                if ($heading.length) {
                    setTimeout(function() { $heading.focus(); }, 50);
                }

                // Scroll to top of wizard
                if (typeof window.scrollTo === 'function') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }

            // Navigation functions (kept on window for inline onclick handlers)
            window.aacbWizardNext = function(step) { showStep(step); };
            window.aacbWizardBack = function(step) { showStep(step); };

            // Resume: if an account is already linked for this site, skip the
            // Get-started form and land on the first-scan step.
            <?php if (get_option('aacb_accountID')) : ?>
            showStep(2);
            <?php endif; ?>
            $(document).on('change', 'input[name="aacb_plan"]', function() {
                var picked = $(this).val();
                $('#wizard-selected-tier').val(picked);
                $('.aacb-plan-option').each(function() {
                    var $card = $(this);
                    var isChecked = $card.find('input[name="aacb_plan"]').is(':checked');
                    if (isChecked) {
                        $card.addClass('aacb-plan-option--checked');
                        $card.css({
                            'border-color': '#7c3aed',
                            'box-shadow': '0 0 0 3px rgba(124,58,237,0.10)',
                            'background': 'linear-gradient(135deg, rgba(124,58,237,0.04) 0%, rgba(79,70,229,0.04) 100%)'
                        });
                    } else {
                        $card.removeClass('aacb-plan-option--checked');
                        $card.css({
                            'border-color': 'var(--aacx-border)',
                            'box-shadow': 'none',
                            'background': 'var(--aacx-surface, #fff)'
                        });
                    }
                });
            });
            // Make the whole card clickable (clicking the label already
            // toggles the inner radio; this just keeps focus behavior tidy).
            $(document).on('click', '.aacb-plan-option', function(e) {
                if (e.target.tagName !== 'INPUT') {
                    $(this).find('input[name="aacb_plan"]').prop('checked', true).trigger('change');
                }
            });

            window.aacbWizardSkip = function() {
                if (confirm('<?php echo esc_js(__('Skip setup for now? You can finish any time from the settings page.', 'allaccessible')); ?>')) {
                    $.post(ajaxurl, {
                        action: 'aacb_skip_wizard',
                        nonce: '<?php echo wp_create_nonce('aacb_wizard_nonce'); ?>'
                    }, function() {
                        window.location.href = '<?php echo admin_url('admin.php?page=allaccessible'); ?>';
                    });
                }
            };

            // ─── UNIFIED FORM SUBMIT ──────────────────────────────────
            // Submit the form; show a welcome-back vs account-created
            // message based on the response.
            function setStatus(html, variant) {
                var bg = '#e0e7ff', color = '#3730a3', border = '#a5b4fc';
                if (variant === 'error') { bg = '#fef2f2'; color = '#991b1b'; border = '#fca5a5'; }
                if (variant === 'ok')    { bg = '#ecfdf5'; color = '#065f46'; border = '#6ee7b7'; }
                if (variant === 'info')  { bg = '#eff6ff'; color = '#1e40af'; border = '#93c5fd'; }
                $('#wizard-status')
                    .css({
                        display: 'block',
                        background: bg,
                        color: color,
                        border: '1px solid ' + border,
                        'border-radius': '8px',
                        padding: '10px 12px',
                        'font-size': '13px'
                    })
                    .html(html);
            }
            function actionCopy(action) {
                if (action === 'created') {
                    return <?php echo wp_json_encode(__('Account created. Provisioning your site…', 'allaccessible')); ?>;
                }
                if (action === 'linked-existing-account') {
                    return <?php echo wp_json_encode(__('Welcome back. We linked this WordPress site to your account.', 'allaccessible')); ?>;
                }
                if (action === 'linked-existing-site') {
                    return <?php echo wp_json_encode(__('Welcome back. This site was already on your account.', 'allaccessible')); ?>;
                }
                return <?php echo wp_json_encode(__('Connected.', 'allaccessible')); ?>;
            }

            $('#aacb-wizard-form').on('submit', function(e) {
                e.preventDefault();

                var $form = $(this);
                var email = $form.find('#wizard-email').val().trim();
                var siteUrl = $form.find('input[name="site_url"]').val();
                var tier = $('#wizard-selected-tier').val() || 'trial';

                if (!email) {
                    setStatus('<?php echo esc_js(__('Please enter your email address.', 'allaccessible')); ?>', 'error');
                    return;
                }

                $('#wizard-submit-btn').prop('disabled', true);
                $('#wizard-submit-text').text('<?php echo esc_js(__('Setting things up…', 'allaccessible')); ?>');
                setStatus('<?php echo esc_js(__('Connecting to AllAccessible…', 'allaccessible')); ?>', 'info');

                $.ajax({
                    url: 'https://app.allaccessible.org/api/add-site',
                    method: 'POST',
                    contentType: 'application/json',
                    dataType: 'text',
                    headers: { 'Accept': 'application/json' },
                    data: JSON.stringify({
                        email:  email,
                        url:    siteUrl,
                        tier:   tier,
                        source: 'wordpress-v2-unified'
                    }),
                    success: function(responseText, _status, xhr) {
                        var response = null;
                        try { response = JSON.parse(responseText); } catch (err) { response = null; }

                        // Normalize older responses that return a bare string.
                        var accountID = null;
                        var action = 'created';
                        if (response && typeof response === 'object') {
                            if (response.error) {
                                setStatus(response.error || '<?php echo esc_js(__('Something went wrong. Please try again.', 'allaccessible')); ?>', 'error');
                                $('#wizard-submit-btn').prop('disabled', false);
                                $('#wizard-submit-text').text('<?php echo esc_js(__('Continue', 'allaccessible')); ?> →');
                                return;
                            }
                            accountID = response.accountID || response.account || response.id;
                            action = response.action || (response.created ? 'created' : 'linked-existing-account');
                        } else if (typeof responseText === 'string' && responseText.length > 4) {
                            accountID = responseText.replace(/^"+|"+$/g, '').trim();
                        }

                        if (!accountID) {
                            setStatus('<?php echo esc_js(__('We received an unexpected response. Please try again.', 'allaccessible')); ?>', 'error');
                            $('#wizard-submit-btn').prop('disabled', false);
                            $('#wizard-submit-text').text('<?php echo esc_js(__('Continue', 'allaccessible')); ?> →');
                            return;
                        }

                        setStatus(actionCopy(action), 'ok');

                        // Save accountID + mark wizard complete + advance.
                        $.post(ajaxurl, {
                            action: 'AllAccessible_save_settings',
                            aacb_accountID: accountID,
                            _wpnonce: '<?php echo wp_create_nonce('allaccessible_save_settings'); ?>'
                        }).done(function() {
                            $.post(ajaxurl, {
                                action: 'aacb_complete_wizard',
                                nonce: '<?php echo wp_create_nonce('aacb_wizard_nonce'); ?>'
                            }).done(function() {
                                setTimeout(function() { showStep(2); }, 600);
                            });
                        }).fail(function() {
                            setStatus('<?php echo esc_js(__('We could not save your settings. Please try again.', 'allaccessible')); ?>', 'error');
                            $('#wizard-submit-btn').prop('disabled', false);
                            $('#wizard-submit-text').text('<?php echo esc_js(__('Continue', 'allaccessible')); ?> →');
                        });
                    },
                    error: function(xhr) {
                        // Some browser/server combinations land here even with
                        // a 200 body. Replay the success-path parse.
                        if (xhr.responseText) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                var accountID = response.accountID || response.account || response.id;
                                if (accountID && (accountID + '').length > 4) {
                                    $.post(ajaxurl, {
                                        action: 'AllAccessible_save_settings',
                                        aacb_accountID: accountID,
                                        _wpnonce: '<?php echo wp_create_nonce('allaccessible_save_settings'); ?>'
                                    }).done(function() {
                                        $.post(ajaxurl, {
                                            action: 'aacb_complete_wizard',
                                            nonce: '<?php echo wp_create_nonce('aacb_wizard_nonce'); ?>'
                                        }).done(function() { setTimeout(function() { showStep(2); }, 600); });
                                    });
                                    return;
                                }
                            } catch (err) { /* fall through */ }
                        }
                        var msg = '<?php echo esc_js(__('We could not reach AllAccessible. Please try again.', 'allaccessible')); ?>';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            msg = xhr.responseJSON.error;
                        }
                        setStatus(msg, 'error');
                        $('#wizard-submit-btn').prop('disabled', false);
                        $('#wizard-submit-text').text('<?php echo esc_js(__('Continue', 'allaccessible')); ?> →');
                    }
                });
            });

            // ─── SITE + EMAIL PRECHECK ────────────────────────────────
            var _siteUrl              = <?php echo wp_json_encode($site_url); ?>;
            var _precheckTimer        = null;
            var _precheckLastKey      = '';
            var _precheckCurrentState = 'create';
            var _autoAttachAccountID  = '';   // populated when state === 'auto'

            function setState(state, ctx) {
                ctx = ctx || {};
                if (state === _precheckCurrentState && state !== 'auto') return;
                _precheckCurrentState = state;

                if (state === 'auto') {
                    _autoAttachAccountID = ctx.accountID || '';
                    $('#wizard-step-1-heading').text('<?php echo esc_js(__('Welcome back', 'allaccessible')); ?>');
                    $('.aacb-create-only').hide();
                    $('#wizard-email').closest('.aacx-v2__field').stop(true, true).slideUp(150);
                    $('#wizard-plan-section').stop(true, true).slideUp(150);
                    $('#wizard-pending-note').stop(true, true).slideUp(100);
                    // Populate + show the confirmation card.
                    var siteHost = '';
                    try { siteHost = (new URL(_siteUrl)).hostname; } catch (e) { siteHost = _siteUrl; }
                    var matchedEmail = ($('#wizard-email').val() || '').trim();
                    $('#wizard-auto-confirm-detail').text(
                        '<?php echo esc_js(__('Linking', 'allaccessible')); ?> ' + siteHost +
                        ' <?php echo esc_js(__('to', 'allaccessible')); ?> ' + matchedEmail
                    );
                    $('#wizard-auto-confirm').stop(true, true).slideDown(150);
                    $('#wizard-submit-text').text(
                        '<?php echo esc_js(__('Reconnect this site', 'allaccessible')); ?> →'
                    );
                    return;
                }

                // Non-auto states: restore the create-only marketing content
                // + show the email field.
                $('.aacb-create-only').show();
                $('#wizard-auto-confirm').stop(true, true).slideUp(100);
                $('#wizard-email').closest('.aacx-v2__field').stop(true, true).slideDown(150);

                if (state === 'link') {
                    _autoAttachAccountID = '';
                    $('#wizard-step-1-heading').text('<?php echo esc_js(__('Welcome back', 'allaccessible')); ?>');
                    $('#wizard-email-help').text(
                        '<?php echo esc_js(__('We will link this WordPress site to your existing AllAccessible account.', 'allaccessible')); ?>'
                    );
                    $('#wizard-plan-section').stop(true, true).slideUp(150);
                    $('#wizard-pending-note').stop(true, true).slideUp(100);
                    $('#wizard-submit-text').text(
                        '<?php echo esc_js(__('Connect this site', 'allaccessible')); ?> →'
                    );
                } else if (state === 'link-pending') {
                    // This site is on another account. The plugin works
                    // locally right away; dashboard visibility waits for the
                    // existing owner to approve access.
                    _autoAttachAccountID = '';
                    $('#wizard-step-1-heading').text('<?php echo esc_js(__('Request access to this site', 'allaccessible')); ?>');
                    $('#wizard-email-help').text(
                        '<?php echo esc_js(__('This site is on another AllAccessible account. Enter your email to request access.', 'allaccessible')); ?>'
                    );
                    $('#wizard-plan-section').stop(true, true).slideUp(150);
                    $('#wizard-pending-note').stop(true, true).slideDown(150);
                    $('#wizard-submit-text').text(
                        '<?php echo esc_js(__('Request access', 'allaccessible')); ?> →'
                    );
                } else {
                    // 'create' (default)
                    _autoAttachAccountID = '';
                    $('#wizard-step-1-heading').text(
                        '<?php echo esc_js(__('Welcome to AllAccessible', 'allaccessible')); ?>'
                    );
                    $('#wizard-email-help').text(
                        '<?php echo esc_js(__("We'll create a new account or link your existing one — same email, either way.", 'allaccessible')); ?>'
                    );
                    $('#wizard-plan-section').stop(true, true).slideDown(150);
                    $('#wizard-pending-note').stop(true, true).slideUp(100);
                    $('#wizard-submit-text').text(
                        '<?php echo esc_js(__('Continue', 'allaccessible')); ?> →'
                    );
                }
            }

            function runPrecheck(email) {
                email = (email || '').trim().toLowerCase();
                if (!email || email.indexOf('@') === -1 || email.indexOf('.') === -1) return;
                var cacheKey = _siteUrl + '|' + email;
                if (cacheKey === _precheckLastKey) return;
                _precheckLastKey = cacheKey;

                $.ajax({
                    url: 'https://app.allaccessible.org/api/check-site-account',
                    method: 'POST',
                    contentType: 'application/json',
                    dataType: 'json',
                    data: JSON.stringify({ url: _siteUrl, email: email }),
                    timeout: 3000
                }).done(function(resp) {
                    if (!resp || resp.error) { setState('create'); return; }
                    if (resp.site_exists && resp.user_matches && resp.account_id) {
                        setState('auto', { accountID: resp.account_id });
                    } else if (resp.site_exists && !resp.user_matches) {
                        setState('link-pending');
                    } else if (resp.user_exists) {
                        setState('link');
                    } else {
                        setState('create');
                    }
                }).fail(function() { setState('create'); });
            }

            $('#wizard-email').on('blur change', function() {
                var email = $(this).val().trim().toLowerCase();
                clearTimeout(_precheckTimer);
                _precheckTimer = setTimeout(function() { runPrecheck(email); }, 350);
            });
            // Fire on load with the pre-filled admin email — typical fresh
            // install case, lets us land directly in 'auto' state for
            // returning customers reinstalling on the same WP.
            setTimeout(function() {
                var prefilled = ($('#wizard-email').val() || '').trim().toLowerCase();
                if (prefilled) runPrecheck(prefilled);
            }, 500);

            // Reconnect short-circuit: when already matched, attach the
            // existing account directly without re-submitting the form.
            $('#aacb-wizard-form').on('submit', function(e) {
                if (_precheckCurrentState !== 'auto' || !_autoAttachAccountID) return;
                e.preventDefault();
                e.stopImmediatePropagation();
                setStatus('<?php echo esc_js(__('Reconnecting…', 'allaccessible')); ?>', 'info');
                $('#wizard-submit-btn').prop('disabled', true);
                $.post(ajaxurl, {
                    action: 'AllAccessible_save_settings',
                    aacb_accountID: _autoAttachAccountID,
                    _wpnonce: '<?php echo wp_create_nonce('allaccessible_save_settings'); ?>'
                }).done(function() {
                    $.post(ajaxurl, {
                        action: 'aacb_complete_wizard',
                        nonce: '<?php echo wp_create_nonce('aacb_wizard_nonce'); ?>'
                    }).done(function() {
                        setStatus('<?php echo esc_js(__('Reconnected. Provisioning…', 'allaccessible')); ?>', 'ok');
                        setTimeout(function() { showStep(2); }, 500);
                    });
                }).fail(function() {
                    setStatus('<?php echo esc_js(__('We could not save your settings. Please try again.', 'allaccessible')); ?>', 'error');
                    $('#wizard-submit-btn').prop('disabled', false);
                });
            });

            // Initialize stepper on load
            updateStepper(1);
        });
        </script>
        <?php
        // Print the scan-trigger JS for step 5 (idempotent — printed once per page).
        if (class_exists('AllAccessible_ScanTriggerPanel')) {
            AllAccessible_ScanTriggerPanel::enqueue_inline_script();
        }
    }

    /**
     * AJAX handler for completing wizard (just marks it done)
     */
    public function ajax_complete_wizard() {
        check_ajax_referer('aacb_wizard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'allaccessible')));
        }

        update_option('aacb_wizard_completed', true);

        // Wizard finish
        if (class_exists('AllAccessible_PostLinkBackfill')) {
            AllAccessible_PostLinkBackfill::on_activate();
        }

        wp_send_json_success();
    }

    /**
     * AJAX handler for skipping wizard
     */
    public function ajax_skip_wizard() {
        check_ajax_referer('aacb_wizard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error();
        }

        update_option('aacb_wizard_completed', true);

        // Wizard finish
        if (class_exists('AllAccessible_PostLinkBackfill')) {
            AllAccessible_PostLinkBackfill::on_activate();
        }

        wp_send_json_success();
    }
}

// Initialize onboarding wizard after WordPress is fully loaded
add_action('plugins_loaded', function() {
    AllAccessible_OnboardingWizard::get_instance();
});
