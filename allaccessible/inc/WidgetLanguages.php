<?php


if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * Widget language code => native display name, sorted by code.
 *
 * @return array<string, string>
 */
function aacb_widget_languages() {
    return array(
        'ar' => 'العربية',
        'da' => 'Dansk',
        'de' => 'Deutsch',
        'en' => 'English',
        'es' => 'Español',
        'fi' => 'Suomi',
        'fr' => 'Français',
        'he' => 'עברית',
        'hi' => 'हिन्दी',
        'hu' => 'Magyar',
        'it' => 'Italiano',
        'ja' => '日本語',
        'ko' => '한국어',
        'ms' => 'Bahasa Melayu',
        'nl' => 'Nederlands',
        'no' => 'Norsk',
        'pl' => 'Polski',
        'pt' => 'Português',
        'sr' => 'Српски (Latin)',
        'sr2' => 'Српски (Ћирилица)',
        'sv' => 'Svenska',
        'ta' => 'தமிழ்',
        'zh' => '中文',
    );
}

/**
 * Only 'auto' or a code the widget ships may be saved as the owner default.
 *
 * @param mixed $value
 * @return string 'auto' or a supported code
 */
function aacb_normalize_widget_language($value) {
    if (!is_string($value) || $value === '' || $value === 'auto') {
        return 'auto';
    }
    $langs = aacb_widget_languages();
    return isset($langs[$value]) ? $value : 'auto';
}

/**
 * Map a WordPress site locale (e.g. pl_PL, de_DE_formal, sr_RS) to the widget
 * code the card can suggest, or null when the widget does not ship that
 * language.
 *
 * @param string $wp_locale
 * @return string|null
 */
function aacb_widget_language_from_wp_locale($wp_locale) {
    if (!is_string($wp_locale) || $wp_locale === '') {
        return null;
    }
    $primary = strtolower(strtok($wp_locale, '_-'));
    // WordPress-specific spellings the widget files under another code.
    $aliases = array(
        'nb' => 'no',  // Norwegian Bokmål
        'nn' => 'no',  // Norwegian Nynorsk
        'sr' => 'sr2', // WordPress sr_RS is Cyrillic; widget 'sr' is Latin, 'sr2' Cyrillic
    );
    $code = isset($aliases[$primary]) ? $aliases[$primary] : $primary;
    $langs = aacb_widget_languages();
    return isset($langs[$code]) ? $code : null;
}
