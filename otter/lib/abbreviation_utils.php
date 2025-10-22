<?php
/**
 * abbreviation_utils.php
 *
 * Shared utility for abbreviating organization names consistently across the application.
 * Used in settings, reports, and other pages that display organization names.
 */

/**
 * Abbreviate organization names using prioritized, single-abbreviation logic
 * @param string $name The organization name to abbreviate
 * @return string The abbreviated organization name
 */
function abbreviateOrganizationName($name) {
    $rules = [
        'Community College District' => 'CCD',
        'Junior College District' => 'JCD',
        'Community College' => 'CC',
        'Continuing Education' => 'Cont Ed',
    ];

    foreach ($rules as $pattern => $abbr) {
        if (strpos($name, $pattern) !== false) {
            return str_replace($pattern, $abbr, $name);
        }
    }

    return $name;
}

/**
 * JavaScript version of the abbreviation function for client-side use
 * @return string JavaScript code for the abbreviation function
 */
function getAbbreviationJavaScript() {
    return "
function abbreviateOrganizationNameJS(name) {
    const rules = [
        ['Community College District', 'CCD'],
        ['Junior College District', 'JCD'],
        ['Community College', 'CC'],
        ['Continuing Education', 'Cont Ed'],
    ];

    for (const [pattern, abbr] of rules) {
        if (name.includes(pattern)) {
            return name.replace(pattern, abbr);
        }
    }

    return name;
}";
}