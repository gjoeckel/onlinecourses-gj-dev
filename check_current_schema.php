<?php
require_once 'config.php';
require_once 'db.php';

$db = new Database();

echo "<h2>Current Database Schema Check</h2>";

// Check courses table columns
echo "<h3>Courses Table Columns:</h3>";
$columns = $db->select("SHOW COLUMNS FROM courses");
echo "<ul>";
foreach ($columns as $column) {
    echo "<li><strong>{$column['Field']}</strong> - {$column['Type']} {$column['Null']} {$column['Default']}</li>";
}
echo "</ul>";

// Check registrations table columns
echo "<h3>Registrations Table Columns:</h3>";
$columns = $db->select("SHOW COLUMNS FROM registrations");
echo "<ul>";
foreach ($columns as $column) {
    echo "<li><strong>{$column['Field']}</strong> - {$column['Type']} {$column['Null']} {$column['Default']}</li>";
}
echo "</ul>";

// Check if new tables exist
echo "<h3>New Tables Status:</h3>";
$tables = ['enterprises', 'organizations', 'cron_history', 'quiz_tracking'];
foreach ($tables as $table) {
    $exists = $db->select("SHOW TABLES LIKE '$table'");
    $status = !empty($exists) ? "✅ EXISTS" : "❌ MISSING";
    echo "<p><strong>$table:</strong> $status</p>";
}

// Check indexes
echo "<h3>Important Indexes:</h3>";
$indexes = $db->select("SHOW INDEX FROM registrations");
echo "<p><strong>Registrations indexes:</strong></p><ul>";
foreach ($indexes as $index) {
    echo "<li>{$index['Key_name']} on {$index['Column_name']}</li>";
}
echo "</ul>";

$indexes = $db->select("SHOW INDEX FROM courses");
echo "<p><strong>Courses indexes:</strong></p><ul>";
foreach ($indexes as $index) {
    echo "<li>{$index['Key_name']} on {$index['Column_name']}</li>";
}
echo "</ul>";

echo "<h3>Summary:</h3>";
echo "<p>This will help us determine what database updates are still needed.</p>";
?> 