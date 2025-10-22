<?php
require_once 'config.php';
require_once 'db.php';

$db = new Database();

echo "<h2>Finding Terms of Use Quiz ID</h2>";

// Get course information
$courses = $db->select("SELECT id, course_id, course_title FROM courses LIMIT 5");

if (empty($courses)) {
    echo "<p>❌ No courses found in database.</p>";
    exit;
}

echo "<h3>Available Courses:</h3>";
echo "<ul>";
foreach ($courses as $course) {
    echo "<li><strong>Course ID:</strong> {$course['id']} | <strong>Canvas ID:</strong> {$course['course_id']} | <strong>Title:</strong> {$course['course_title']}</li>";
}
echo "</ul>";

echo "<h3>How to Find the ToU Quiz ID:</h3>";
echo "<ol>";
echo "<li><strong>Go to Canvas</strong> and navigate to your course</li>";
echo "<li><strong>Go to Quizzes</strong> in the course navigation</li>";
echo "<li><strong>Find the Terms of Use quiz</strong> (it might be named something like 'Terms of Use', 'Course Agreement', 'Acceptance Quiz', etc.)</li>";
echo "<li><strong>Click on the quiz</strong> to open it</li>";
echo "<li><strong>Look at the URL</strong> - it will look like: <code>https://yourcanvas.instructure.com/courses/123/quizzes/456</code></li>";
echo "<li><strong>The number after 'quizzes/' is the quiz ID</strong> (in this example, it would be 456)</li>";
echo "</ol>";

echo "<h3>Alternative Method (API):</h3>";
echo "<p>If you have API access, you can also find it by:</p>";
echo "<ol>";
echo "<li>Making a GET request to: <code>https://yourcanvas.instructure.com/api/v1/courses/{COURSE_ID}/quizzes</code></li>";
echo "<li>Look for the quiz with a name containing 'Terms', 'Use', 'Agreement', or similar</li>";
echo "<li>The 'id' field in the response is the quiz ID you need</li>";
echo "</ol>";

echo "<h3>Once You Have the Quiz ID:</h3>";
echo "<p>Run this SQL command (replace the values):</p>";
echo "<pre>";
foreach ($courses as $course) {
    echo "-- For course: {$course['course_title']} (ID: {$course['id']})\n";
    echo "UPDATE courses SET tou_quiz_id = 'YOUR_QUIZ_ID_HERE' WHERE id = {$course['id']};\n\n";
}
echo "</pre>";

echo "<h3>Test the Update:</h3>";
echo "<p>After updating, you can test by running:</p>";
echo "<pre>php cron_daily_optimized.php</pre>";
echo "<p>This should now check for ToU quiz completion instead of skipping it.</p>";

echo "<h3>Expected Behavior After Fix:</h3>";
echo "<ul>";
echo "<li>Users in 'active' status will be checked for ToU quiz completion</li>";
echo "<li>If they score 1 point on the ToU quiz, they'll transition to 'enrollee'</li>";
echo "<li>The cron job will no longer show 'ToU quiz not configured' messages</li>";
echo "</ul>";
?> 