<?php
require_once 'config.php';
require_once 'db.php';

$db = new Database();

echo "<h2>Auto-Find Terms of Use Assignment IDs</h2>";

// Get credentials
$creds = get_credentials_from_config_file();
$accessToken = $creds['token'];
$apiUrl = $creds['url'];

if (empty($accessToken) || empty($apiUrl)) {
    die("❌ Missing Canvas API credentials. Check config file.\n");
}

// First, add the tou_quiz_id column if it doesn't exist
try {
    $columns = $db->select("SHOW COLUMNS FROM courses LIKE 'tou_quiz_id'");
    if (empty($columns)) {
        echo "<p>⚠️ tou_quiz_id column doesn't exist. You'll need to add it manually with:</p>";
        echo "<pre>ALTER TABLE courses ADD COLUMN tou_quiz_id VARCHAR(255) NULL AFTER exam_4_id;</pre>";
        echo "<p>Run this in phpMyAdmin first, then refresh this page.</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking column: " . $e->getMessage() . "</p>";
    exit;
}

// Get all courses from database
$courses = $db->select("SELECT id, course_id, course_title FROM courses WHERE course_id IS NOT NULL");

if (empty($courses)) {
    echo "<p>❌ No courses found in database.</p>";
    exit;
}

echo "<h3>Processing " . count($courses) . " courses...</h3>";

$foundCount = 0;
$updatedCount = 0;

foreach ($courses as $course) {
    echo "<h4>Course: {$course['course_title']} (Canvas ID: {$course['course_id']})</h4>";

    // Call Canvas API to get assignments
    $endpoint = "{$apiUrl}/courses/{$course['course_id']}/assignments";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $assignments = json_decode($result, true);

        // Look for Terms of Use assignment
        $touAssignment = null;
        foreach ($assignments as $assignment) {
            if (stripos($assignment['name'], 'terms of use') !== false ||
                stripos($assignment['name'], 'terms') !== false && stripos($assignment['name'], 'use') !== false) {
                $touAssignment = $assignment;
                break;
            }
        }

        if ($touAssignment) {
            $foundCount++;
            echo "<p>✅ Found Terms of Use assignment: <strong>{$touAssignment['name']}</strong> (ID: {$touAssignment['id']})</p>";

            // Update database
            try {
                $db->update('courses',
                    ['tou_quiz_id' => $touAssignment['id']],
                    'id = ?',
                    [$course['id']]
                );
                $updatedCount++;
                echo "<p>✅ Updated database with assignment ID {$touAssignment['id']}</p>";
            } catch (Exception $e) {
                echo "<p>❌ Failed to update database: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p>❌ No Terms of Use assignment found in this course</p>";
            echo "<p>Available assignments:</p><ul>";
            foreach ($assignments as $assignment) {
                echo "<li>{$assignment['name']} (ID: {$assignment['id']})</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p>❌ API call failed with HTTP code: $httpCode</p>";
        if ($result) {
            echo "<p>Error: " . htmlspecialchars($result) . "</p>";
        }
    }

    echo "<hr>";
}

echo "<h3>Summary:</h3>";
echo "<p>✅ Found Terms of Use assignments in $foundCount courses</p>";
echo "<p>✅ Updated database for $updatedCount courses</p>";

// Show final results
echo "<h3>Final Database State:</h3>";
$finalCourses = $db->select("SELECT id, course_id, course_title, tou_quiz_id FROM courses ORDER BY id");
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Canvas ID</th><th>Title</th><th>ToU Assignment ID</th></tr>";
foreach ($finalCourses as $course) {
    $status = empty($course['tou_quiz_id']) ? "❌ NOT SET" : "✅ {$course['tou_quiz_id']}";
    echo "<tr>";
    echo "<td>{$course['id']}</td>";
    echo "<td>{$course['course_id']}</td>";
    echo "<td>{$course['course_title']}</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Next Steps:</h3>";
echo "<p>Now that the assignment IDs are set, the cron job should be able to check for ToU completion.</p>";
echo "<p>Test by running: <code>php cron_daily_optimized.php</code></p>";

function get_credentials_from_config_file() {
    // Check local development first, then production fallback
    $config_path = '/Users/a00288946/cursor-global/projects/cursor-otter-dev/master_includes/onlinecourses_common.php';
    if (!file_exists($config_path)) {
        $config_path = '/var/websites/webaim/master_includes/onlinecourses_common.php';
    }
    $credentials = [
        'dbhost' => null, 'dbuser' => null, 'dbpass' => null, 'dbname' => null,
        'token' => null, 'url' => null
    ];

    if (!is_readable($config_path)) {
        return $credentials;
    }

    $config_content = file_get_contents($config_path);

    $patterns = [
        'dbhost' => '/\$dbhost\s*=\s*"([^"]+)"/',
        'dbuser' => '/\$dbuser\s*=\s*"([^"]+)"/',
        'dbpass' => '/\$dbpass\s*=\s*"([^"]+)"/',
        'dbname' => '/\$dbname\s*=\s*"([^"]+)"/',
        'token'  => '/\$CANVAS_API_TOKEN\s*=\s*"([^"]+)"/',
        'url'    => '/\$CANVAS_API_URL\s*=\s*"([^"]+)"/'
    ];

    foreach ($patterns as $key => $pattern) {
        if (preg_match($pattern, $config_content, $matches)) {
            $credentials[$key] = $matches[1];
        }
    }

    return $credentials;
}
?>
