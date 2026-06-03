<?php
/**
 * Agentic Fixes — translation map.
 *
 * @package AllAccessible
 * @since   2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AllAccessible_AgenticFixes_Labels {

    /**
     * Translate a fix-type enum key into a customer-friendly noun phrase.
     *
     * @param string $type API value from byType[].type or topIssues[].type.
     * @return string Translated label, safe for esc_html() output.
     */
    public static function fix_type_label($type) {
        $map = array(
            'missing-alt'   => __('Missing image description',  'allaccessible'),
            'empty-button'  => __('Button without label',       'allaccessible'),
            'no-form-label' => __('Form field without label',   'allaccessible'),
            'missing-lang'  => __('Missing page language',      'allaccessible'),
            'empty-link'    => __('Link without text',          'allaccessible'),
            'aria-rule'     => __('ARIA labeling issue',        'allaccessible'),
            'aria-heading'  => __('Heading hierarchy issue',    'allaccessible'),
            'other'         => __('Other accessibility issue',  'allaccessible'),
        );
        $key = is_string($type) ? $type : 'other';
        return isset($map[$key]) ? $map[$key] : __('Other accessibility issue', 'allaccessible');
    }

    /**
     * Translate a manifest status enum.
     *
     * @param string $status One of draft, approved, active, reverted.
     * @return string
     */
    public static function status_label($status) {
        $map = array(
            'draft'    => __('Pending review', 'allaccessible'),
            'approved' => __('Approved',       'allaccessible'),
            'active'   => __('Active',         'allaccessible'),
            'reverted' => __('Reverted',       'allaccessible'),
        );
        $key = is_string($status) ? $status : '';
        return isset($map[$key]) ? $map[$key] : esc_html((string) $status);
    }

    /**
     * Translate a fix-source label into a brand-safe user-facing string.
     *
     * @param string $source One of agent, rule, scanner, manual.
     * @return string
     */
    public static function source_label($source) {
        $map = array(
            'agent'   => __('AllAccessible agents',  'allaccessible'),
            'rule'    => __('Automated rule check',  'allaccessible'),
            'scanner' => __('Compliance scanner',    'allaccessible'),
            'manual'  => __('Manual review',         'allaccessible'),
        );
        $key = is_string($source) ? $source : 'agent';
        return isset($map[$key]) ? $map[$key] : __('AllAccessible agents', 'allaccessible');
    }

    /**
     * Translate a rationale enum key into a one-line explanation.
     *
     * @param string $key One of gen_alt, gen_button_label, gen_form_label,
     *                    set_page_lang, gen_link_text, fix_aria_role,
     *                    fix_heading_hierarchy, generic_fix.
     * @return string
     */
    public static function rationale_label($key) {
        $map = array(
            'gen_alt'                => __('Suggested alt text for image',           'allaccessible'),
            'gen_button_label'       => __('Suggested label for button',             'allaccessible'),
            'gen_form_label'         => __('Suggested label for form field',         'allaccessible'),
            'set_page_lang'          => __('Set page language attribute',            'allaccessible'),
            'gen_link_text'          => __('Suggested text for link',                'allaccessible'),
            'fix_aria_role'          => __('Correct ARIA role assignment',           'allaccessible'),
            'fix_heading_hierarchy'  => __('Fix heading order',                      'allaccessible'),
            'generic_fix'            => __('Accessibility fix suggested',            'allaccessible'),
        );
        $k = is_string($key) ? $key : 'generic_fix';
        return isset($map[$k]) ? $map[$k] : __('Accessibility fix suggested', 'allaccessible');
    }

    /**
     * Translate a WCAG Success Criterion identifier into "ID — Name".
     *
     * @param string $criterion e.g., '1.1.1', '4.1.2'.
     * @return string e.g., "1.1.1 — Non-text Content".
     */
    public static function wcag_label($criterion) {
        $k = is_string($criterion) ? trim($criterion) : '';
        if ($k === '') {
            return '';
        }
        $name = self::wcag_name($k);
        return $name !== '' ? $k . ' — ' . $name : $k;
    }

    /**
     * Full WCAG 2.2 success-criterion name lookup (all 87 SC, A/AA/AAA).
     *
     * The SC id (e.g. "1.4.3") is a frozen, universal spec identifier and stays
     * in code — only the name is translatable. Names are the canonical W3C
     * titles; translators may localize where an official translation exists.
     * Returns '' for an unknown id so the caller can fall back to the bare id.
     *
     * @param string $id Normalized SC id, e.g. "1.1.1".
     * @return string Translated SC name, or '' if not a known criterion.
     */
    public static function wcag_name($id) {
        $map = array(
            // Perceivable
            '1.1.1' => __('Non-text Content',                                  'allaccessible'),
            '1.2.1' => __('Audio-only and Video-only (Prerecorded)',           'allaccessible'),
            '1.2.2' => __('Captions (Prerecorded)',                            'allaccessible'),
            '1.2.3' => __('Audio Description or Media Alternative (Prerecorded)', 'allaccessible'),
            '1.2.4' => __('Captions (Live)',                                   'allaccessible'),
            '1.2.5' => __('Audio Description (Prerecorded)',                   'allaccessible'),
            '1.2.6' => __('Sign Language (Prerecorded)',                       'allaccessible'),
            '1.2.7' => __('Extended Audio Description (Prerecorded)',          'allaccessible'),
            '1.2.8' => __('Media Alternative (Prerecorded)',                   'allaccessible'),
            '1.2.9' => __('Audio-only (Live)',                                 'allaccessible'),
            '1.3.1' => __('Info and Relationships',                            'allaccessible'),
            '1.3.2' => __('Meaningful Sequence',                               'allaccessible'),
            '1.3.3' => __('Sensory Characteristics',                           'allaccessible'),
            '1.3.4' => __('Orientation',                                       'allaccessible'),
            '1.3.5' => __('Identify Input Purpose',                            'allaccessible'),
            '1.3.6' => __('Identify Purpose',                                  'allaccessible'),
            '1.4.1' => __('Use of Color',                                      'allaccessible'),
            '1.4.2' => __('Audio Control',                                     'allaccessible'),
            '1.4.3' => __('Contrast (Minimum)',                                'allaccessible'),
            '1.4.4' => __('Resize Text',                                       'allaccessible'),
            '1.4.5' => __('Images of Text',                                    'allaccessible'),
            '1.4.6' => __('Contrast (Enhanced)',                               'allaccessible'),
            '1.4.7' => __('Low or No Background Audio',                        'allaccessible'),
            '1.4.8' => __('Visual Presentation',                               'allaccessible'),
            '1.4.9' => __('Images of Text (No Exception)',                     'allaccessible'),
            '1.4.10' => __('Reflow',                                           'allaccessible'),
            '1.4.11' => __('Non-text Contrast',                                'allaccessible'),
            '1.4.12' => __('Text Spacing',                                     'allaccessible'),
            '1.4.13' => __('Content on Hover or Focus',                        'allaccessible'),
            // Operable
            '2.1.1' => __('Keyboard',                                          'allaccessible'),
            '2.1.2' => __('No Keyboard Trap',                                  'allaccessible'),
            '2.1.3' => __('Keyboard (No Exception)',                           'allaccessible'),
            '2.1.4' => __('Character Key Shortcuts',                           'allaccessible'),
            '2.2.1' => __('Timing Adjustable',                                 'allaccessible'),
            '2.2.2' => __('Pause, Stop, Hide',                                 'allaccessible'),
            '2.2.3' => __('No Timing',                                         'allaccessible'),
            '2.2.4' => __('Interruptions',                                     'allaccessible'),
            '2.2.5' => __('Re-authenticating',                                 'allaccessible'),
            '2.2.6' => __('Timeouts',                                          'allaccessible'),
            '2.3.1' => __('Three Flashes or Below Threshold',                  'allaccessible'),
            '2.3.2' => __('Three Flashes',                                     'allaccessible'),
            '2.3.3' => __('Animation from Interactions',                       'allaccessible'),
            '2.4.1' => __('Bypass Blocks',                                     'allaccessible'),
            '2.4.2' => __('Page Titled',                                       'allaccessible'),
            '2.4.3' => __('Focus Order',                                       'allaccessible'),
            '2.4.4' => __('Link Purpose (In Context)',                         'allaccessible'),
            '2.4.5' => __('Multiple Ways',                                     'allaccessible'),
            '2.4.6' => __('Headings and Labels',                               'allaccessible'),
            '2.4.7' => __('Focus Visible',                                     'allaccessible'),
            '2.4.8' => __('Location',                                          'allaccessible'),
            '2.4.9' => __('Link Purpose (Link Only)',                          'allaccessible'),
            '2.4.10' => __('Section Headings',                                 'allaccessible'),
            '2.4.11' => __('Focus Not Obscured (Minimum)',                     'allaccessible'),
            '2.4.12' => __('Focus Not Obscured (Enhanced)',                    'allaccessible'),
            '2.4.13' => __('Focus Appearance',                                 'allaccessible'),
            '2.5.1' => __('Pointer Gestures',                                  'allaccessible'),
            '2.5.2' => __('Pointer Cancellation',                              'allaccessible'),
            '2.5.3' => __('Label in Name',                                     'allaccessible'),
            '2.5.4' => __('Motion Actuation',                                  'allaccessible'),
            '2.5.5' => __('Target Size (Enhanced)',                            'allaccessible'),
            '2.5.6' => __('Concurrent Input Mechanisms',                       'allaccessible'),
            '2.5.7' => __('Dragging Movements',                                'allaccessible'),
            '2.5.8' => __('Target Size (Minimum)',                             'allaccessible'),
            // Understandable
            '3.1.1' => __('Language of Page',                                  'allaccessible'),
            '3.1.2' => __('Language of Parts',                                 'allaccessible'),
            '3.1.3' => __('Unusual Words',                                     'allaccessible'),
            '3.1.4' => __('Abbreviations',                                     'allaccessible'),
            '3.1.5' => __('Reading Level',                                     'allaccessible'),
            '3.1.6' => __('Pronunciation',                                     'allaccessible'),
            '3.2.1' => __('On Focus',                                          'allaccessible'),
            '3.2.2' => __('On Input',                                          'allaccessible'),
            '3.2.3' => __('Consistent Navigation',                             'allaccessible'),
            '3.2.4' => __('Consistent Identification',                         'allaccessible'),
            '3.2.5' => __('Change on Request',                                 'allaccessible'),
            '3.2.6' => __('Consistent Help',                                   'allaccessible'),
            '3.3.1' => __('Error Identification',                              'allaccessible'),
            '3.3.2' => __('Labels or Instructions',                            'allaccessible'),
            '3.3.3' => __('Error Suggestion',                                  'allaccessible'),
            '3.3.4' => __('Error Prevention (Legal, Financial, Data)',         'allaccessible'),
            '3.3.5' => __('Help',                                              'allaccessible'),
            '3.3.6' => __('Error Prevention (All)',                            'allaccessible'),
            '3.3.7' => __('Redundant Entry',                                   'allaccessible'),
            '3.3.8' => __('Accessible Authentication (Minimum)',              'allaccessible'),
            '3.3.9' => __('Accessible Authentication (Enhanced)',             'allaccessible'),
            // Robust
            '4.1.1' => __('Parsing',                                           'allaccessible'),
            '4.1.2' => __('Name, Role, Value',                                 'allaccessible'),
            '4.1.3' => __('Status Messages',                                   'allaccessible'),
        );
        return isset($map[$id]) ? $map[$id] : '';
    }

    /**
     * Translate an upgrade-CTA
     *
     * @param string $key e.g., upgrade_headline_agent_fixes.
     * @return string
     */
    public static function cta_label($key) {
        $map = array(
            'upgrade_headline_agent_fixes' => __('Let AllAccessible agents fix these for you', 'allaccessible'),
            'upgrade_subhead_human_in_loop' => __('Premium plans include human-in-the-loop agentic AI remediation. Your team approves, agents do the work.', 'allaccessible'),
        );
        $k = is_string($key) ? $key : '';
        return isset($map[$k]) ? $map[$k] : '';
    }
}
