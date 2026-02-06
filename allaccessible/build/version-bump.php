<?php
/**
 * AllAccessible Version Bump Script
 *
 * This script automates the process of updating version numbers across the plugin
 *
 * Usage: php version-bump.php 1.3.7 1.3.8
 *
 * @package     AllAccessible
 * @since       1.3.7



 */

// Check if arguments are provided
if ($argc < 3) {
    echo "Usage: php version-bump.php <current-version> <new-version>\n";
    exit(1);
}

$current_version = $argv[1];
$new_version = $argv[2];

// Validate version numbers
if (!preg_match('/^\d+\.\d+\.\d+$/', $current_version) || !preg_match('/^\d+\.\d+\.\d+$/', $new_version)) {
    echo "Error: Version numbers must be in format x.y.z\n";
    exit(1);
}

// Files to update
$files = [
    __DIR__ . '/../inc/constants.php',
    __DIR__ . '/../allaccessible.php',
    __DIR__ . '/../README.txt'
];

$changes_made = 0;

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "Warning: File not found: $file\n";
        continue;
    }

    $content = file_get_contents($file);
    $updated_content = str_replace($current_version, $new_version, $content, $count);

    if ($count > 0) {
        file_put_contents($file, $updated_content);
        echo "Updated $count occurrences in $file\n";
        $changes_made += $count;
    } else {
        echo "No changes needed in $file\n";
    }
}

// Update the tested up to version in readme.txt
$readme_file = __DIR__ . '/../README.txt';
$readme_content = file_get_contents($readme_file);

// Get WordPress version from wp.org
$wp_version_info = file_get_contents('https://api.wordpress.org/core/version-check/1.7/');
$wp_version_data = json_decode($wp_version_info, true);
$latest_wp_version = isset($wp_version_data['offers'][0]['current']) ? $wp_version_data['offers'][0]['current'] : null;

if ($latest_wp_version) {
    $updated_readme = preg_replace('/Tested up to: \d+\.\d+(\.\d+)?/', 'Tested up to: ' . $latest_wp_version, $readme_content, $count);

    if ($count > 0) {
        file_put_contents($readme_file, $updated_readme);
        echo "Updated WordPress tested version to $latest_wp_version in README.txt\n";
        $changes_made++;
    }
}

// Add new version entry to changelog
$changelog_entry = "\n= $new_version =\n* \n\n";
$updated_readme = preg_replace('/(== Changelog ==\s+)/', '$1' . $changelog_entry, $readme_content, $count);

if ($count > 0) {
    file_put_contents($readme_file, $updated_readme);
    echo "Added new version entry to changelog in README.txt\n";
    $changes_made++;
}

echo "\nTotal changes made: $changes_made\n";
echo "Don't forget to update the changelog with actual changes!\n";
