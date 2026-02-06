<?php
/**
 * Script to add missing critical translations to Italian and German WordPress plugin files
 */

$translations = [
    'it_IT' => [
        "Activate AllAccessible Premium to Unlock Ai Accessibility Features." => 
            "Attiva AllAccessible Premium per sbloccare le funzionalità di accessibilità AI.",
        
        "You may need to reload this page if your widget options are not visible" => 
            "Potrebbe essere necessario ricaricare questa pagina se le opzioni del widget non sono visibili",
        
        "Please give us any feedback that could help us improve" => 
            "Per favore, forniscici qualsiasi feedback che potrebbe aiutarci a migliorare",
        
        "It's a temporary deactivation, I'm troubleshooting" => 
            "È una disattivazione temporanea, sto risolvendo problemi",
    ],
    
    'de_DE' => [
        "Activate AllAccessible Premium to Unlock Ai Accessibility Features." => 
            "Aktivieren Sie AllAccessible Premium, um KI-Barrierefreiheitsfunktionen freizuschalten.",
        
        "You may need to reload this page if your widget options are not visible" => 
            "Sie müssen diese Seite möglicherweise neu laden, wenn Ihre Widget-Optionen nicht sichtbar sind",
        
        "Please give us any feedback that could help us improve" => 
            "Bitte geben Sie uns Feedback, das uns helfen könnte, uns zu verbessern",
        
        "It's a temporary deactivation, I'm troubleshooting" => 
            "Es ist eine vorübergehende Deaktivierung, ich führe eine Fehlersuche durch",
        
        "AllAccessible's %s widget is visible on the front end of your site." => 
            "Das %s Widget von AllAccessible ist auf der Vorderseite Ihrer Website sichtbar.",
    ],
];

// Instructions for manual addition
echo "Add these missing translations to the respective .po files:\n\n";

foreach ($translations as $lang => $strings) {
    echo "=== For allaccessible-{$lang}.po ===\n\n";
    
    foreach ($strings as $msgid => $msgstr) {
        echo "#: [Add appropriate source file reference]\n";
        echo "msgid \"" . addslashes($msgid) . "\"\n";
        echo "msgstr \"" . addslashes($msgstr) . "\"\n\n";
    }
    
    echo "\n";
}

echo "Note: These translations should be reviewed by native speakers for accuracy.\n";
echo "Professional translation services recommended for production use.\n";