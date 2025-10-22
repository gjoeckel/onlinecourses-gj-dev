<?php
/**
 * Settings Toggle Test
 * Tests the collapsible "Change Passwords" section functionality
 */

require_once __DIR__ . '/../test_base.php';

// Test that the settings page structure is correct
TestBase::runTest('Settings Page Structure', function() {
    // This test would normally make HTTP requests, but for now we'll test the file structure
    $settings_file = __DIR__ . '/../../settings/index.php';
    
    TestBase::assertTrue(file_exists($settings_file), "Settings page file should exist");
    
    $content = file_get_contents($settings_file);
    
    // Check for required HTML elements
    TestBase::assertTrue(strpos($content, 'change-passwords-section') !== false, "Should contain section container");
    TestBase::assertTrue(strpos($content, 'toggle-passwords-button') !== false, "Should contain toggle button");
    TestBase::assertTrue(strpos($content, 'passwords-content') !== false, "Should contain content container");
    TestBase::assertTrue(strpos($content, 'section-header') !== false, "Should contain section header");
    TestBase::assertTrue(strpos($content, 'section-content') !== false, "Should contain section content");
});

// Test that the CSS file contains required styles
TestBase::runTest('Settings CSS Structure', function() {
    $css_file = __DIR__ . '/../../css/settings.css';
    
    TestBase::assertTrue(file_exists($css_file), "Settings CSS file should exist");
    
    $content = file_get_contents($css_file);
    
    // Check for required CSS classes
    TestBase::assertTrue(strpos($content, '.change-passwords-section') !== false, "Should contain section container styles");
    TestBase::assertTrue(strpos($content, '#toggle-passwords-button') !== false, "Should contain toggle button styles");
    TestBase::assertTrue(strpos($content, '.section-content') !== false, "Should contain content styles");
    TestBase::assertTrue(strpos($content, 'aria-expanded') !== false, "Should contain aria-expanded styles");
});

// Test that JavaScript functionality is included
TestBase::runTest('Settings JavaScript Structure', function() {
    $settings_file = __DIR__ . '/../../settings/index.php';
    
    $content = file_get_contents($settings_file);
    
    // Check for JavaScript functionality
    TestBase::assertTrue(strpos($content, 'toggle-passwords-button') !== false, "JavaScript should reference toggle button");
    TestBase::assertTrue(strpos($content, 'passwords-content') !== false, "JavaScript should reference content container");
    TestBase::assertTrue(strpos($content, 'aria-expanded') !== false, "JavaScript should handle aria-expanded attribute");
    TestBase::assertTrue(strpos($content, 'addEventListener') !== false, "JavaScript should include event listeners");
});

// Test accessibility features
TestBase::runTest('Settings Accessibility Features', function() {
    $settings_file = __DIR__ . '/../../settings/index.php';
    
    $content = file_get_contents($settings_file);
    
    // Check for accessibility attributes
    TestBase::assertTrue(strpos($content, 'aria-expanded') !== false, "Should have aria-expanded attribute");
    TestBase::assertTrue(strpos($content, 'aria-label') !== false, "Should have aria-label attribute");
    TestBase::assertTrue(strpos($content, 'type="button"') !== false, "Should have proper button type");
});

echo "\nSettings Toggle Tests Complete!\n"; 