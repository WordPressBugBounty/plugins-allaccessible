<?php
/**
 * Onboarding Wizard for AllAccessible Plugin
 *
 * Modern setup flow with Tailwind CSS:
 * - Welcome screen
 * - Plan selection (Free Forever vs Paid Trial)
 * - Email-only signup
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

        // Enqueue our Tailwind CSS
        wp_enqueue_style(
            'allaccessible-admin',
            AACB_CSS . 'admin.css',
            array(),
            AACB_VERSION
        );

        // Add inline CSS to hide WordPress admin notices on wizard page
        $custom_css = "
            .notice, .updated, .error, .update-nag,
            div.notice, div.updated, div.error, div.update-nag {
                display: none !important;
            }
        ";
        wp_add_inline_style('allaccessible-admin', $custom_css);
    }

    /**
     * Render the wizard UI
     */
    public function render_wizard() {
        // Remove all admin notices on wizard page for clean experience
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');

        $site_url = get_bloginfo('wpurl');
        $admin_email = get_bloginfo('admin_email');
        ?>
        <div class="wrap allaccessible-admin">
            <div class="aacx-min-h-screen aacx-bg-gradient-to-br aacx-from-aacx-slate-900 aacx-to-aacx-primary-900 aacx-flex aacx-items-center aacx-justify-center aacx-py-12 aacx-px-4">
                <div class="aacx-max-w-4xl aacx-w-full">

                    <!-- Step 1: Welcome -->
                    <div id="wizard-step-1" class="wizard-step aacx-bg-white aacx-rounded-2xl aacx-shadow-2xl aacx-p-12 aacx-text-center">
                        <div class="aacx-mb-6">
                            <img src="<?php echo esc_url(AACB_IMG . 'bug.svg'); ?>" alt="AllAccessible" style="width: 80px; height: 80px;" class="aacx-mx-auto">
                        </div>

                        <h1 class="aacx-text-4xl aacx-font-black aacx-text-aacx-slate-900 aacx-mb-3">
                            <?php _e('Welcome to AllAccessible!', 'allaccessible'); ?>
                        </h1>

                        <p class="aacx-text-lg aacx-text-aacx-slate-600 aacx-mb-10 aacx-max-w-xl aacx-mx-auto">
                            <?php _e('Make your WordPress site accessible to everyone. Choose how you\'d like to get started:', 'allaccessible'); ?>
                        </p>

                        <!-- Three Option Cards -->
                        <div class="aacx-grid aacx-grid-cols-1 md:aacx-grid-cols-3 aacx-gap-4 aacx-max-w-4xl aacx-mx-auto">

                            <!-- Option 1: Create Account (Recommended) -->
                            <div
                                onclick="aacbWizardNext(2)"
                                class="aacx-relative aacx-border-2 aacx-border-aacx-primary-500 aacx-rounded-xl aacx-p-6 aacx-cursor-pointer hover:aacx-shadow-lg hover:-aacx-translate-y-1 aacx-transition-all aacx-duration-200 aacx-bg-aacx-primary-50">
                                <div class="aacx-absolute aacx-top-0 aacx-right-0 aacx-bg-aacx-secondary-600 aacx-text-white aacx-px-3 aacx-py-1 aacx-rounded-bl-lg aacx-rounded-tr-xl aacx-text-xs aacx-font-bold">
                                    <?php _e('Recommended', 'allaccessible'); ?>
                                </div>
                                <div class="aacx-mb-4 aacx-mt-2">
                                    <div class="aacx-w-12 aacx-h-12 aacx-bg-aacx-primary-600 aacx-rounded-full aacx-flex aacx-items-center aacx-justify-center aacx-mx-auto">
                                        <svg class="aacx-w-6 aacx-h-6 aacx-text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="aacx-text-lg aacx-font-bold aacx-text-aacx-slate-900 aacx-mb-2">
                                    <?php _e('Create Account', 'allaccessible'); ?>
                                </h3>
                                <p class="aacx-text-sm aacx-text-aacx-slate-600 aacx-mb-4">
                                    <?php _e('Set up a new AllAccessible account with access to all features, dashboard, and AI tools.', 'allaccessible'); ?>
                                </p>
                                <span class="aacx-inline-flex aacx-items-center aacx-text-aacx-primary-600 aacx-font-semibold aacx-text-sm">
                                    <?php _e('Get Started', 'allaccessible'); ?> →
                                </span>
                            </div>

                            <!-- Option 2: Connect Existing Account -->
                            <div
                                onclick="aacbWizardNext(4)"
                                class="aacx-border-2 aacx-border-aacx-slate-200 aacx-rounded-xl aacx-p-6 aacx-cursor-pointer hover:aacx-border-aacx-primary-300 hover:aacx-shadow-lg hover:-aacx-translate-y-1 aacx-transition-all aacx-duration-200">
                                <div class="aacx-mb-4">
                                    <div class="aacx-w-12 aacx-h-12 aacx-bg-aacx-slate-100 aacx-rounded-full aacx-flex aacx-items-center aacx-justify-center aacx-mx-auto">
                                        <svg class="aacx-w-6 aacx-h-6 aacx-text-aacx-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="aacx-text-lg aacx-font-bold aacx-text-aacx-slate-900 aacx-mb-2">
                                    <?php _e('Connect Account', 'allaccessible'); ?>
                                </h3>
                                <p class="aacx-text-sm aacx-text-aacx-slate-600 aacx-mb-4">
                                    <?php _e('Already have an AllAccessible account? Link this site to your existing account.', 'allaccessible'); ?>
                                </p>
                                <span class="aacx-inline-flex aacx-items-center aacx-text-aacx-primary-600 aacx-font-semibold aacx-text-sm">
                                    <?php _e('Link Account', 'allaccessible'); ?> →
                                </span>
                            </div>

                            <!-- Option 3: Skip (Limited Free Widget) -->
                            <div
                                onclick="aacbWizardSkip()"
                                class="aacx-border-2 aacx-border-aacx-slate-200 aacx-rounded-xl aacx-p-6 aacx-cursor-pointer hover:aacx-border-aacx-slate-300 hover:aacx-shadow-md aacx-transition-all aacx-duration-200 aacx-bg-aacx-slate-50">
                                <div class="aacx-mb-4">
                                    <div class="aacx-w-12 aacx-h-12 aacx-bg-aacx-slate-200 aacx-rounded-full aacx-flex aacx-items-center aacx-justify-center aacx-mx-auto">
                                        <svg class="aacx-w-6 aacx-h-6 aacx-text-aacx-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                </div>
                                <h3 class="aacx-text-lg aacx-font-bold aacx-text-aacx-slate-700 aacx-mb-2">
                                    <?php _e('Skip for Now', 'allaccessible'); ?>
                                </h3>
                                <p class="aacx-text-sm aacx-text-aacx-slate-500 aacx-mb-4">
                                    <?php _e('Use a basic widget without an account. Very limited features, no dashboard access.', 'allaccessible'); ?>
                                </p>
                                <span class="aacx-inline-flex aacx-items-center aacx-text-aacx-slate-500 aacx-font-semibold aacx-text-sm">
                                    <?php _e('Skip Setup', 'allaccessible'); ?> →
                                </span>
                            </div>

                        </div>
                    </div>

                    <!-- Step 2: Plan Selection -->
                    <div id="wizard-step-2" class="wizard-step aacx-hidden aacx-bg-white aacx-rounded-2xl aacx-shadow-2xl aacx-p-12">
                        <h2 class="aacx-text-4xl aacx-font-bold aacx-text-aacx-slate-900 aacx-mb-2 aacx-text-center">
                            <?php _e('Choose Your Plan', 'allaccessible'); ?>
                        </h2>
                        <p class="aacx-text-aacx-slate-600 aacx-text-center aacx-mb-10">
                            <?php _e('Enter your email to create a new account or link to an existing one', 'allaccessible'); ?>
                        </p>

                        <div class="aacx-text-center aacx-mb-6">
                            <button
                                onclick="aacbWizardNext(4)"
                                class="aacx-inline-flex aacx-items-center aacx-px-6 aacx-py-3 aacx-bg-aacx-slate-100 aacx-text-aacx-primary-600 aacx-rounded-lg aacx-font-semibold aacx-text-base hover:aacx-bg-aacx-primary-50 hover:aacx-text-aacx-primary-700 aacx-transition-all aacx-duration-200 aacx-shadow-sm hover:aacx-shadow-md">
                                <svg class="aacx-w-5 aacx-h-5 aacx-mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                <?php _e('Already have an account? Link it here', 'allaccessible'); ?>
                            </button>
                        </div>

                        <div class="aacx-grid aacx-grid-cols-1 md:aacx-grid-cols-2 aacx-gap-8 aacx-mb-8">

                            <!-- Free Forever Plan -->
                            <div class="aacx-border-2 aacx-border-aacx-slate-200 aacx-rounded-2xl aacx-p-8 hover:aacx-border-aacx-primary-300 aacx-transition-all aacx-cursor-pointer" onclick="aacbWizardSelectPlan('free')">
                                <div class="aacx-mb-6">
                                    <h3 class="aacx-text-2xl aacx-font-bold aacx-text-aacx-slate-900 aacx-mb-2">
                                        <?php _e('Free Forever', 'allaccessible'); ?>
                                    </h3>
                                    <p class="aacx-text-4xl aacx-font-black aacx-text-aacx-slate-900">
                                        $0<span class="aacx-text-lg aacx-font-normal aacx-text-aacx-slate-600">/month</span>
                                    </p>
                                </div>

                                <ul class="aacx-space-y-4 aacx-mb-8">
                                    <li class="aacx-flex aacx-items-start">
                                        <svg class="aacx-h-6 aacx-w-6 aacx-text-aacx-secondary-600 aacx-mr-3 aacx-flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                                        </svg>
                                        <span class="aacx-text-aacx-slate-700"><?php _e('Basic accessibility widget', 'allaccessible'); ?></span>
                                    </li>
                                    <li class="aacx-flex aacx-items-start">
                                        <svg class="aacx-h-6 aacx-w-6 aacx-text-aacx-secondary-600 aacx-mr-3 aacx-flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                                        </svg>
                                        <span class="aacx-text-aacx-slate-700"><?php _e('Color & contrast controls', 'allaccessible'); ?></span>
                                    </li>
                                    <li class="aacx-flex aacx-items-start">
                                        <svg class="aacx-h-6 aacx-w-6 aacx-text-aacx-secondary-600 aacx-mr-3 aacx-flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                                        </svg>
                                        <span class="aacx-text-aacx-slate-700"><?php _e('Text sizing & spacing', 'allaccessible'); ?></span>
                                    </li>
                                </ul>

                                <button
                                    class="aacx-w-full aacx-px-6 aacx-py-3 aacx-border-2 aacx-border-aacx-primary-600 aacx-text-aacx-primary-600 aacx-rounded-xl aacx-font-bold hover:aacx-bg-aacx-primary-50 aacx-transition-colors">
                                    <?php _e('Choose Free', 'allaccessible'); ?>
                                </button>
                            </div>

                            <!-- Paid Trial Plan -->
                            <div class="aacx-border-2 aacx-border-aacx-primary-600 aacx-rounded-2xl aacx-p-8 aacx-shadow-xl aacx-relative aacx-cursor-pointer" onclick="aacbWizardSelectPlan('trial')">
                                <div class="aacx-absolute aacx-top-0 aacx-right-0 aacx-bg-aacx-secondary-600 aacx-text-white aacx-px-4 aacx-py-1 aacx-rounded-bl-xl aacx-rounded-tr-2xl aacx-text-sm aacx-font-bold">
                                    <?php _e('Recommended', 'allaccessible'); ?>
                                </div>

                                <div class="aacx-mb-6 aacx-mt-4">
                                    <h3 class="aacx-text-2xl aacx-font-bold aacx-text-aacx-slate-900 aacx-mb-2">
                                        <?php _e('Premium Trial', 'allaccessible'); ?>
                                    </h3>
                                    <p class="aacx-text-4xl aacx-font-black aacx-text-aacx-primary-600">
                                        <?php _e('7 Days Free', 'allaccessible'); ?>
                                    </p>
                                    <p class="aacx-text-sm aacx-text-aacx-slate-600">
                                        <?php _e('Then $10/month', 'allaccessible'); ?>
                                    </p>
                                </div>

                                <ul class="aacx-space-y-4 aacx-mb-8">
                                    <li class="aacx-flex aacx-items-start">
                                        <svg class="aacx-h-6 aacx-w-6 aacx-text-aacx-secondary-600 aacx-mr-3 aacx-flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                                        </svg>
                                        <span class="aacx-text-aacx-slate-700 aacx-font-semibold"><?php _e('Everything in Free, plus:', 'allaccessible'); ?></span>
                                    </li>
                                    <li class="aacx-flex aacx-items-start">
                                        <svg class="aacx-h-6 aacx-w-6 aacx-text-aacx-secondary-600 aacx-mr-3 aacx-flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                                        </svg>
                                        <span class="aacx-text-aacx-slate-700"><?php _e('AI accessibility fixes', 'allaccessible'); ?></span>
                                    </li>
                                    <li class="aacx-flex aacx-items-start">
                                        <svg class="aacx-h-6 aacx-w-6 aacx-text-aacx-secondary-600 aacx-mr-3 aacx-flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                                        </svg>
                                        <span class="aacx-text-aacx-slate-700"><?php _e('WCAG/ADA compliance audits', 'allaccessible'); ?></span>
                                    </li>
                                    <li class="aacx-flex aacx-items-start">
                                        <svg class="aacx-h-6 aacx-w-6 aacx-text-aacx-secondary-600 aacx-mr-3 aacx-flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                                        </svg>
                                        <span class="aacx-text-aacx-slate-700"><?php _e('Widget customization', 'allaccessible'); ?></span>
                                    </li>
                                    <li class="aacx-flex aacx-items-start">
                                        <svg class="aacx-h-6 aacx-w-6 aacx-text-aacx-secondary-600 aacx-mr-3 aacx-flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                                        </svg>
                                        <span class="aacx-text-aacx-slate-700"><?php _e('Alt text generation', 'allaccessible'); ?></span>
                                    </li>
                                </ul>

                                <button
                                    class="aacx-w-full aacx-px-6 aacx-py-3 aacx-bg-aacx-primary-600 aacx-text-white aacx-rounded-xl aacx-font-bold aacx-shadow-xl hover:aacx-bg-aacx-primary-700 hover:aacx-shadow-2xl hover:-aacx-translate-y-1 aacx-transition-all aacx-duration-200">
                                    <?php _e('Start Free Trial', 'allaccessible'); ?> →
                                </button>
                            </div>
                        </div>

                        <div class="aacx-text-center aacx-mt-6">
                            <button
                                onclick="aacbWizardBack(1)"
                                class="aacx-inline-flex aacx-items-center aacx-px-4 aacx-py-2 aacx-text-aacx-slate-600 hover:aacx-text-aacx-slate-900 aacx-transition-colors aacx-font-medium aacx-rounded-lg hover:aacx-bg-aacx-slate-100">
                                <svg class="aacx-w-4 aacx-h-4 aacx-mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                <?php _e('Back', 'allaccessible'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Email Capture (New Account) -->
                    <div id="wizard-step-3" class="wizard-step aacx-hidden aacx-bg-white aacx-rounded-2xl aacx-shadow-2xl aacx-p-12">
                        <h2 class="aacx-text-4xl aacx-font-bold aacx-text-aacx-slate-900 aacx-mb-2 aacx-text-center">
                            <span id="wizard-plan-title"><?php _e('Create Your Account', 'allaccessible'); ?></span>
                        </h2>
                        <p class="aacx-text-aacx-slate-600 aacx-text-center aacx-mb-10">
                            <?php _e('We\'ll create a new account or link to your existing one', 'allaccessible'); ?>
                        </p>

                        <form id="aacb-wizard-form" class="aacx-max-w-md aacx-mx-auto">
                            <input type="hidden" id="wizard-selected-tier" name="tier" value="">
                            <input type="hidden" name="site_url" value="<?php echo esc_url($site_url); ?>">

                            <div class="aacx-mb-6">
                                <label class="aacx-block aacx-text-sm aacx-font-bold aacx-text-aacx-slate-700 aacx-mb-2">
                                    <?php _e('Your Email', 'allaccessible'); ?>
                                </label>
                                <input
                                    type="email"
                                    name="email"
                                    id="wizard-email"
                                    class="aacx-w-full aacx-px-4 aacx-py-3 aacx-border-2 aacx-border-aacx-slate-300 aacx-rounded-lg focus:aacx-border-aacx-primary-600 focus:aacx-outline-none aacx-transition-colors aacx-text-base"
                                    value="<?php echo esc_attr($admin_email); ?>"
                                    required
                                    placeholder="you@example.com"
                                >
                            </div>

                            <div class="aacx-bg-aacx-slate-50 aacx-border aacx-border-aacx-slate-200 aacx-rounded-lg aacx-p-4 aacx-mb-6">
                                <div class="aacx-flex aacx-items-start">
                                    <svg class="aacx-h-5 aacx-w-5 aacx-text-aacx-secondary-600 aacx-mr-2 aacx-mt-0.5 aacx-flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                                    </svg>
                                    <div class="aacx-text-sm aacx-text-aacx-slate-700">
                                        <p id="wizard-benefits-free" class="aacx-hidden">
                                            ✓ <?php _e('Free forever, no credit card', 'allaccessible'); ?><br>
                                            ✓ <?php _e('Upgrade anytime', 'allaccessible'); ?><br>
                                            ✓ <?php _e('Basic accessibility features', 'allaccessible'); ?>
                                        </p>
                                        <p id="wizard-benefits-trial" class="aacx-hidden">
                                            ✓ <?php _e('7-day trial, no credit card', 'allaccessible'); ?><br>
                                            ✓ <?php _e('Cancel anytime', 'allaccessible'); ?><br>
                                            ✓ <?php _e('Full premium features', 'allaccessible'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <button
                                type="submit"
                                id="wizard-submit-btn"
                                class="aacx-w-full aacx-px-8 aacx-py-4 aacx-bg-aacx-primary-600 aacx-text-white aacx-rounded-xl aacx-font-bold aacx-text-lg aacx-shadow-xl hover:aacx-bg-aacx-primary-700 hover:aacx-shadow-2xl hover:-aacx-translate-y-1 aacx-transition-all aacx-duration-200">
                                <span id="wizard-submit-text"><?php _e('Create Account', 'allaccessible'); ?></span>
                            </button>

                            <div id="wizard-loading" class="aacx-hidden aacx-text-center aacx-mt-4">
                                <svg class="aacx-animate-spin aacx-h-8 aacx-w-8 aacx-text-aacx-primary-600 aacx-mx-auto" fill="none" viewBox="0 0 24 24">
                                    <circle class="aacx-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="aacx-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="aacx-text-aacx-slate-600 aacx-mt-2"><?php _e('Creating your account...', 'allaccessible'); ?></p>
                            </div>

                            <div id="wizard-error" class="aacx-hidden aacx-mt-4 aacx-p-4 aacx-bg-red-50 aacx-border aacx-border-red-200 aacx-rounded-lg aacx-text-red-700"></div>
                        </form>

                        <div class="aacx-text-center aacx-mt-6">
                            <button
                                onclick="aacbWizardBack(2)"
                                class="aacx-inline-flex aacx-items-center aacx-px-4 aacx-py-2 aacx-text-aacx-slate-600 hover:aacx-text-aacx-slate-900 aacx-transition-colors aacx-font-medium aacx-rounded-lg hover:aacx-bg-aacx-slate-100">
                                <svg class="aacx-w-4 aacx-h-4 aacx-mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                <?php _e('Back', 'allaccessible'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Step 4: Link Existing Account -->
                    <div id="wizard-step-4" class="wizard-step aacx-hidden aacx-bg-white aacx-rounded-2xl aacx-shadow-2xl aacx-p-12">
                        <h2 class="aacx-text-4xl aacx-font-bold aacx-text-aacx-slate-900 aacx-mb-2 aacx-text-center">
                            <?php _e('Link Your Account', 'allaccessible'); ?>
                        </h2>
                        <p class="aacx-text-aacx-slate-600 aacx-text-center aacx-mb-10">
                            <?php _e('Enter the email address associated with your AllAccessible account', 'allaccessible'); ?>
                        </p>

                        <form id="aacb-existing-account-form" class="aacx-max-w-md aacx-mx-auto">
                            <input type="hidden" name="site_url" value="<?php echo esc_url($site_url); ?>">

                            <div class="aacx-mb-6">
                                <label class="aacx-block aacx-text-sm aacx-font-bold aacx-text-aacx-slate-700 aacx-mb-2">
                                    <?php _e('Your Email', 'allaccessible'); ?>
                                </label>
                                <input
                                    type="email"
                                    name="email"
                                    id="existing-account-email"
                                    class="aacx-w-full aacx-px-4 aacx-py-3 aacx-border-2 aacx-border-aacx-slate-300 aacx-rounded-lg focus:aacx-border-aacx-primary-600 focus:aacx-outline-none aacx-transition-colors aacx-text-base"
                                    value="<?php echo esc_attr($admin_email); ?>"
                                    required
                                    placeholder="you@example.com"
                                >
                            </div>

                            <div class="aacx-bg-aacx-blue-50 aacx-border aacx-border-aacx-blue-200 aacx-rounded-lg aacx-p-4 aacx-mb-6">
                                <div class="aacx-flex aacx-items-start">
                                    <svg class="aacx-h-5 aacx-w-5 aacx-text-aacx-blue-600 aacx-mr-2 aacx-mt-0.5 aacx-flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"/>
                                    </svg>
                                    <div class="aacx-text-sm aacx-text-aacx-slate-700">
                                        <p>
                                            ✓ <?php _e('We\'ll link this WordPress site to your existing account', 'allaccessible'); ?><br>
                                            ✓ <?php _e('Your current tier and settings will be preserved', 'allaccessible'); ?><br>
                                            ✓ <?php _e('The widget will activate with your existing configuration', 'allaccessible'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <button
                                type="submit"
                                id="existing-account-submit-btn"
                                class="aacx-w-full aacx-px-8 aacx-py-4 aacx-bg-aacx-primary-600 aacx-text-white aacx-rounded-xl aacx-font-bold aacx-text-lg aacx-shadow-xl hover:aacx-bg-aacx-primary-700 hover:aacx-shadow-2xl hover:-aacx-translate-y-1 aacx-transition-all aacx-duration-200">
                                <?php _e('Link Account', 'allaccessible'); ?> →
                            </button>

                            <div id="existing-account-loading" class="aacx-hidden aacx-text-center aacx-mt-4">
                                <svg class="aacx-animate-spin aacx-h-8 aacx-w-8 aacx-text-aacx-primary-600 aacx-mx-auto" fill="none" viewBox="0 0 24 24">
                                    <circle class="aacx-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="aacx-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="aacx-text-aacx-slate-600 aacx-mt-2"><?php _e('Linking your account...', 'allaccessible'); ?></p>
                            </div>

                            <div id="existing-account-error" class="aacx-hidden aacx-mt-4 aacx-p-4 aacx-bg-red-50 aacx-border aacx-border-red-200 aacx-rounded-lg aacx-text-red-700"></div>
                        </form>

                        <div class="aacx-text-center aacx-mt-6">
                            <button
                                onclick="aacbWizardBack(2)"
                                class="aacx-inline-flex aacx-items-center aacx-px-4 aacx-py-2 aacx-text-aacx-slate-600 hover:aacx-text-aacx-slate-900 aacx-transition-colors aacx-font-medium aacx-rounded-lg hover:aacx-bg-aacx-slate-100">
                                <svg class="aacx-w-4 aacx-h-4 aacx-mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                <?php _e('Back to Plan Selection', 'allaccessible'); ?>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            let selectedTier = '';

            // Navigation functions
            window.aacbWizardNext = function(step) {
                $('.wizard-step').addClass('aacx-hidden');
                $('#wizard-step-' + step).removeClass('aacx-hidden');
            };

            window.aacbWizardBack = function(step) {
                $('.wizard-step').addClass('aacx-hidden');
                $('#wizard-step-' + step).removeClass('aacx-hidden');
            };

            window.aacbWizardSelectPlan = function(tier) {
                selectedTier = tier;
                $('#wizard-selected-tier').val(tier);

                // Update step 3 UI (email capture)
                if (tier === 'free') {
                    $('#wizard-plan-title').text('<?php esc_js(_e('Start Free Forever', 'allaccessible')); ?>');
                    $('#wizard-submit-text').text('<?php esc_js(_e('Activate Free Account', 'allaccessible')); ?>');
                    $('#wizard-benefits-free').removeClass('aacx-hidden');
                    $('#wizard-benefits-trial').addClass('aacx-hidden');
                } else {
                    $('#wizard-plan-title').text('<?php esc_js(_e('Start Your 7-Day Trial', 'allaccessible')); ?>');
                    $('#wizard-submit-text').text('<?php esc_js(_e('Activate Free Trial', 'allaccessible')); ?>');
                    $('#wizard-benefits-trial').removeClass('aacx-hidden');
                    $('#wizard-benefits-free').addClass('aacx-hidden');
                }

                aacbWizardNext(3);
            };

            window.aacbWizardSkip = function() {
                if (confirm('<?php esc_js(_e('Are you sure? You can always set up your account later from the settings page.', 'allaccessible')); ?>')) {
                    $.post(ajaxurl, {
                        action: 'aacb_skip_wizard',
                        nonce: '<?php echo wp_create_nonce('aacb_wizard_nonce'); ?>'
                    }, function() {
                        window.location.href = '<?php echo admin_url('admin.php?page=allaccessible'); ?>';
                    });
                }
            };

            // Form submission
            $('#aacb-wizard-form').on('submit', function(e) {
                e.preventDefault();

                const email = $('#wizard-email').val().trim();
                const siteUrl = $('input[name="site_url"]').val();
                const tier = $('#wizard-selected-tier').val();

                if (!email || !tier) {
                    $('#wizard-error').text('<?php esc_js(_e('Please fill in all required fields', 'allaccessible')); ?>').removeClass('aacx-hidden');
                    return;
                }

                console.log('🚀 AllAccessible Wizard: Submitting...', {email, siteUrl, tier});

                // Show loading
                $('#wizard-submit-btn').prop('disabled', true);
                $('#wizard-loading').removeClass('aacx-hidden');
                $('#wizard-error').addClass('aacx-hidden');

                // Call add-site API directly
                $.ajax({
                    url: 'https://app.allaccessible.org/api/add-site',
                    method: 'POST',
                    contentType: 'application/json',
                    dataType: 'text', // Get as text first to handle CORS issues
                    data: JSON.stringify({
                        email: email,
                        url: siteUrl,
                        tier: tier,
                        source: 'wordpress-v2'
                    }),
                    success: function(responseText) {
                        console.log('✅ API Response (raw):', responseText);

                        // Try to parse JSON
                        let response;
                        try {
                            response = JSON.parse(responseText);
                        } catch(e) {
                            // Response might already be parsed by jQuery
                            response = responseText;
                        }

                        console.log('✅ API Response (parsed):', response);

                        if (response.error) {
                            console.error('❌ API returned error:', response);
                            $('#wizard-error').text(response.errors || '<?php esc_js(_e('An error occurred. Please try again.', 'allaccessible')); ?>').removeClass('aacx-hidden');
                            $('#wizard-loading').addClass('aacx-hidden');
                            $('#wizard-submit-btn').prop('disabled', false);
                            return;
                        }

                        // Extract accountID (try multiple possible field names)
                        const accountID = response.account || response.accountID || response.id || responseText;
                        console.log('💾 Account ID to save:', accountID);

                        if (!accountID) {
                            console.error('❌ No account ID in response');
                            $('#wizard-error').text('<?php esc_js(_e('Invalid response from server', 'allaccessible')); ?>').removeClass('aacx-hidden');
                            $('#wizard-loading').addClass('aacx-hidden');
                            $('#wizard-submit-btn').prop('disabled', false);
                            return;
                        }

                        // Save accountID via WordPress AJAX (tier will be fetched from API)
                        console.log('💾 Saving to WordPress...', {accountID});
                        $.post(ajaxurl, {
                            action: 'AllAccessible_save_settings',
                            aacb_accountID: accountID,
                            _wpnonce: '<?php echo wp_create_nonce('allaccessible_save_settings'); ?>'
                        }, function(saveResponse) {
                            console.log('✅ WordPress Save Response:', saveResponse);

                            // Mark wizard as complete
                            $.post(ajaxurl, {
                                action: 'aacb_complete_wizard',
                                nonce: '<?php echo wp_create_nonce('aacb_wizard_nonce'); ?>'
                            }, function(wizardResponse) {
                                console.log('✅ Wizard Complete Response:', wizardResponse);
                                console.log('🎉 Success! Redirecting to dashboard...');

                                // Redirect to AllAccessible dashboard
                                window.location.href = '<?php echo admin_url('admin.php?page=allaccessible&wizard=complete'); ?>';
                            });
                        }).fail(function(xhr, status, error) {
                            console.error('❌ WordPress save failed:', {xhr, status, error});
                            $('#wizard-error').text('<?php esc_js(_e('Failed to save settings. Please try again.', 'allaccessible')); ?>').removeClass('aacx-hidden');
                            $('#wizard-loading').addClass('aacx-hidden');
                            $('#wizard-submit-btn').prop('disabled', false);
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ API Error:', {xhr, status, error, responseText: xhr.responseText});

                        // Even if CORS blocks the response, the API might still work
                        // Try to extract accountID from response text
                        if (xhr.responseText) {
                            console.log('⚠️  CORS issue detected, but response exists. Trying to parse...');
                            try {
                                const response = JSON.parse(xhr.responseText);
                                const accountID = response.account || response.accountID || response.id || xhr.responseText;
                                if (accountID && accountID.length > 5) {
                                    console.log('✅ Found accountID despite CORS:', accountID);
                                    // Continue with saving (tier will be fetched from API)
                                    $.post(ajaxurl, {
                                        action: 'AllAccessible_save_settings',
                                        aacb_accountID: accountID,
                                        _wpnonce: '<?php echo wp_create_nonce('allaccessible_save_settings'); ?>'
                                    }, function() {
                                        $.post(ajaxurl, {
                                            action: 'aacb_complete_wizard',
                                            nonce: '<?php echo wp_create_nonce('aacb_wizard_nonce'); ?>'
                                        }, function() {
                                            window.location.href = '<?php echo admin_url('admin.php?page=allaccessible&wizard=complete'); ?>';
                                        });
                                    });
                                    return;
                                }
                            } catch(e) {
                                console.error('Failed to parse error response:', e);
                            }
                        }

                        let errorMsg = '<?php esc_js(_e('Could not connect to AllAccessible. Please try again.', 'allaccessible')); ?>';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMsg = xhr.responseJSON.errors;
                        }
                        $('#wizard-error').text(errorMsg).removeClass('aacx-hidden');
                        $('#wizard-loading').addClass('aacx-hidden');
                        $('#wizard-submit-btn').prop('disabled', false);
                    }
                });
            });

            // Existing account form submission (Step 4) - uses same add-site API
            $('#aacb-existing-account-form').on('submit', function(e) {
                e.preventDefault();

                const email = $('#existing-account-email').val().trim();
                const siteUrl = $('input[name="site_url"]', this).val();

                if (!email) {
                    $('#existing-account-error').text('<?php esc_js(_e('Please enter your email address', 'allaccessible')); ?>').removeClass('aacx-hidden');
                    return;
                }

                console.log('🔗 AllAccessible: Linking existing account via add-site API:', {email, siteUrl});

                // Show loading
                $('#existing-account-submit-btn').prop('disabled', true);
                $('#existing-account-loading').removeClass('aacx-hidden');
                $('#existing-account-error').addClass('aacx-hidden');

                // Call add-site API - backend will link to existing account if email exists
                $.ajax({
                    url: 'https://app.allaccessible.org/api/add-site',
                    method: 'POST',
                    contentType: 'application/json',
                    dataType: 'text',
                    data: JSON.stringify({
                        email: email,
                        url: siteUrl,
                        tier: 'existing', // Signal this is linking existing account
                        source: 'wordpress-v2-existing'
                    }),
                    success: function(responseText) {
                        console.log('✅ API Response (raw):', responseText);

                        let response;
                        try {
                            response = JSON.parse(responseText);
                        } catch(e) {
                            response = responseText;
                        }

                        console.log('✅ API Response (parsed):', response);

                        if (response.error) {
                            console.error('❌ API returned error:', response);
                            $('#existing-account-error').text(response.errors || '<?php esc_js(_e('Could not find an account with this email. Please check and try again.', 'allaccessible')); ?>').removeClass('aacx-hidden');
                            $('#existing-account-loading').addClass('aacx-hidden');
                            $('#existing-account-submit-btn').prop('disabled', false);
                            return;
                        }

                        // Extract accountID
                        const accountID = response.account || response.accountID || response.id || responseText;
                        console.log('💾 Account ID to save:', accountID);

                        if (!accountID) {
                            console.error('❌ No account ID in response');
                            $('#existing-account-error').text('<?php esc_js(_e('Invalid response from server', 'allaccessible')); ?>').removeClass('aacx-hidden');
                            $('#existing-account-loading').addClass('aacx-hidden');
                            $('#existing-account-submit-btn').prop('disabled', false);
                            return;
                        }

                        // Save accountID to WordPress (tier will be fetched from API)
                        $.post(ajaxurl, {
                            action: 'AllAccessible_save_settings',
                            aacb_accountID: accountID,
                            _wpnonce: '<?php echo wp_create_nonce('allaccessible_save_settings'); ?>'
                        }, function(saveResponse) {
                            console.log('✅ WordPress Save Response:', saveResponse);

                            // Mark wizard as complete
                            $.post(ajaxurl, {
                                action: 'aacb_complete_wizard',
                                nonce: '<?php echo wp_create_nonce('aacb_wizard_nonce'); ?>'
                            }, function() {
                                console.log('🎉 Existing account linked! Redirecting...');
                                window.location.href = '<?php echo admin_url('admin.php?page=allaccessible&wizard=complete'); ?>';
                            });
                        }).fail(function(xhr, status, error) {
                            console.error('❌ WordPress save failed:', {xhr, status, error});
                            $('#existing-account-error').text('<?php esc_js(_e('Failed to save settings. Please try again.', 'allaccessible')); ?>').removeClass('aacx-hidden');
                            $('#existing-account-loading').addClass('aacx-hidden');
                            $('#existing-account-submit-btn').prop('disabled', false);
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ API Error:', {xhr, status, error});

                        // Try to handle CORS issues
                        if (xhr.responseText) {
                            console.log('⚠️  CORS issue detected, trying to parse response...');
                            try {
                                const response = JSON.parse(xhr.responseText);
                                const accountID = response.account || response.accountID || response.id || xhr.responseText;
                                if (accountID && accountID.length > 5) {
                                    console.log('✅ Found accountID despite CORS:', accountID);
                                    $.post(ajaxurl, {
                                        action: 'AllAccessible_save_settings',
                                        aacb_accountID: accountID,
                                        _wpnonce: '<?php echo wp_create_nonce('allaccessible_save_settings'); ?>'
                                    }, function() {
                                        $.post(ajaxurl, {
                                            action: 'aacb_complete_wizard',
                                            nonce: '<?php echo wp_create_nonce('aacb_wizard_nonce'); ?>'
                                        }, function() {
                                            window.location.href = '<?php echo admin_url('admin.php?page=allaccessible&wizard=complete'); ?>';
                                        });
                                    });
                                    return;
                                }
                            } catch(e) {
                                console.error('Failed to parse error response:', e);
                            }
                        }

                        let errorMsg = '<?php esc_js(_e('Could not connect to AllAccessible. Please try again.', 'allaccessible')); ?>';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMsg = xhr.responseJSON.errors;
                        }
                        $('#existing-account-error').text(errorMsg).removeClass('aacx-hidden');
                        $('#existing-account-loading').addClass('aacx-hidden');
                        $('#existing-account-submit-btn').prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
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
        wp_send_json_success();
    }
}

// Initialize onboarding wizard after WordPress is fully loaded
add_action('plugins_loaded', function() {
    AllAccessible_OnboardingWizard::get_instance();
});
