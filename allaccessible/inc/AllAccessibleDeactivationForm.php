<?php

namespace allaccessible;

if(!is_admin())
	return;

global $pagenow;

if($pagenow != "plugins.php")
	return;

if(defined('ALLACCESSIBLE_DEACTIVATE_FEEDBACK_FORM_INCLUDED'))
	return;
define('ALLACCESSIBLE_DEACTIVATE_FEEDBACK_FORM_INCLUDED', true);

add_action('admin_enqueue_scripts', function() {
	
	// Enqueue scripts
	wp_enqueue_script('remodal', AACB_JS . 'remodal.min.js',array(),AACB_VERSION);
	wp_enqueue_style('remodal', AACB_CSS . 'remodal.css',array(),AACB_VERSION);
	wp_enqueue_style('remodal-default-theme', AACB_CSS . 'remodal-default-theme.css',array(),AACB_VERSION);
	
	wp_enqueue_script('allaccessible-deactivate-feedback-form', AACB_JS . 'deactivate-feedback-form.js',array(),AACB_VERSION);
	wp_enqueue_style('allaccessible-deactivate-feedback-form', AACB_CSS . 'deactivate-feedback-form.css',array(),AACB_VERSION);
	
	// Localized strings
	wp_localize_script('allaccessible-deactivate-feedback-form', 'allaccessible_deactivate_feedback_form_strings', get_allaccessible_localized_strings());
	
	// Plugins
	$plugins = apply_filters('allaccessible_deactivate_feedback_form_plugins', array());
	
	// Reasons
    $defaultReasons = get_default_reasons();

    foreach($plugins as $plugin)
	{
		$plugin->reasons = apply_filters('allaccessible_deactivate_feedback_form_reasons', $defaultReasons, $plugin);
	}
	
	// Send plugin data
	wp_localize_script('allaccessible-deactivate-feedback-form', 'allaccessible_deactivate_feedback_form_plugins', $plugins);
});

function get_allaccessible_localized_strings() {
    return array(
        'quick_feedback'			=> __('AllAccessible Deactivation', 'allaccessible'),
        'foreword'					=> __('If you have a moment, please let us know why you are deactivating AllAccessible. All submissions are anonymous and we only use this feedback to improve this plugin.', 'allaccessible'),
        'do_not_attach_email'		=> __('Do not send my e-mail address with this feedback', 'allaccessible'),

        'brief_description'			=> __('Please give us any feedback that could help us improve', 'allaccessible'),

        'cancel'					=> __('Cancel', 'allaccessible'),
        'skip_and_deactivate'		=> __('Skip &amp; Deactivate', 'allaccessible'),
        'submit_and_deactivate'		=> __('Submit &amp; Deactivate', 'allaccessible'),
        'please_wait'				=> __('Please wait', 'allaccessible'),
        'thank_you'					=> __('Thank you!', 'allaccessible')
    );
}

function get_default_reasons() {
    return array(
        'suddenly-stopped-working' => array(
            'option'=> __('The plugin suddenly stopped working', 'allaccessible'),
            'comment' => array(
                'type' => 'textarea',
                'text' => __('We\'re sorry to hear that, check <a href="https://support.allaccessible.org" target="_blank">AllAccessible Support</a>. Can you describe the issue?', 'allaccessible')
            )
        ),
        'plugin-broke-site'			=> array(
            'option'=> __('The plugin broke my site', 'allaccessible'),
            'comment' => array(
                'type' => 'textarea',
                'text' => __('We\'re sorry to hear that, check <a href="https://support.allaccessible.org" target="_blank">AllAccessible Support</a>. Can you describe the issue?', 'allaccessible')
            )
        ),
        'no-longer-needed'			=> array(
            'option'=> __('I don\'t need this plugin any more', 'allaccessible'),
            'comment' => null
        ),
        'found-better-plugin'		=> array(
            'option' => __('I found a better plugin', 'allaccessible'),
            'comment' => array(
                'type' => 'textarea',
                'text' => __('Can you please share the name of the other plugin?', 'allaccessible')
            )
        ),
        'temporary-deactivation'	=> array(
            'option' => __('It\'s a temporary deactivation, I\'m troubleshooting', 'allaccessible'),
            'comment' => null
        ),
        'other'						=> array(
            'option' => __('Other', 'allaccessible'),
            'comment' => array(
                'type' => 'textarea',
                'text' => __('Can you describe the issue?', 'allaccessible')
            )
        )
    );
}

/**
 * Hook for adding plugins, pass an array of objects in the following format:
 *  'slug'		=> 'plugin-slug'
 *  'version'	=> 'plugin-version'
 * @return array The plugins in the format described above
 */
add_filter('allaccessible_deactivate_feedback_form_plugins', function($plugins) {
	return $plugins;
});

