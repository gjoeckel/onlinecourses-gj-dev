<?php
require_once 'db.php';
require_once 'config.php';
include_once 'mailgun.php';

// Include the master Canvas configuration
require_once __DIR__ . '/../master_includes/onlinecourses_common.php';

$db = new Database();
$accessToken = $config['canvas']['access_token'];
$apiUrl = $config['canvas']['api_url'];

// 1. Check re-enrolled users for certificate eligibility
$reenrolledUsers = $db->select(
    "SELECT r.*, c.* FROM registrations r 
     JOIN courses c ON r.course_id = c.id 
     WHERE r.status = 'reenrolled' 
     AND r.last_activity_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
);

foreach ($reenrolledUsers as $user) {
    $totalScore = 0;
    $examScores = [];
    $allSubmitted = true;
    
    // Check all quizzes and exams
    $assignments = [
        'overview_of_document_accessibility_id',
        'images_id',
        'hyperlinks_id',
        'contrast_color_reliance_id',
        'optimizing_writing_id',
        'exam_1_id',
        'headings_in_word_id',
        'optimizing_powerpoint_presentations_id',
        'lists_columns_id',
        'tables_id',
        'exam_2_id',
        'evaluating_accessibility_id',
        'practicing_evaluation_repair_id',
        'creating_pdfs_id',
        'exam_3_id',
        'introduction_to_optimizing_pdfs_id',
        'checking_accessibility_id',
        'reading_order_tool_id',
        'content_order_and_tags_order_id',
        'exam_4_id'
    ];
    
    foreach ($assignments as $assignmentId) {
        if (empty($user[$assignmentId])) continue;
        
        $endpoint = "{$apiUrl}/courses/{$user['course_id']}/assignments/{$user[$assignmentId]}/submissions/{$user['canvas_user_id']}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $submission = json_decode($result, true);
            if (isset($submission['score'])) {
                $totalScore += $submission['score'];
                if (strpos($assignmentId, 'exam_') !== false) {
                    $examScores[] = $submission['score'];
                }
            } else {
                $allSubmitted = false;
                break;
            }
        } else {
            $allSubmitted = false;
            break;
        }
    }
    
    // Check if user qualifies for certificate
    if ($allSubmitted && $totalScore >= 93 && count($examScores) === 4 && min($examScores) >= 8) {
        $db->update('registrations', 
            [
                'status' => 'earner',
                'earnerdate' => date('Y-m-d H:i:s')
            ], 
            'id = ?', 
            [$user['id']]
        );
        
        // Enroll in review cohort
        $reviewEndpoint = "{$apiUrl}/courses/{$user['course_id']}/enrollments";
        $enrollmentData = [
            'enrollment' => [
                'user_id' => $user['canvas_user_id'],
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'invited',
                'notify' => true
            ]
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $reviewEndpoint);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($enrollmentData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        
        curl_exec($ch);
        curl_close($ch);
    }
}

// 2. Check for expired users in recently closed cohorts
$recentlyClosedCohorts = $db->select(
    "SELECT id FROM courses 
     WHERE close_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
     AND close_date <= NOW()"
);

if (!empty($recentlyClosedCohorts)) {
    $cohortIds = array_column($recentlyClosedCohorts, 'id');
    $cohortIdsStr = implode(',', $cohortIds);
    
    // Update status for users who started but didn't complete
    $db->update(
        'registrations',
        ['status' => 'expired'],
        "course_id IN ($cohortIdsStr) AND status IN ('enrolled', 'completer')"
    );
    
    // Get expired users to send re-enrollment email
    $expiredUsers = $db->select(
        "SELECT * FROM registrations 
         WHERE course_id IN ($cohortIdsStr) 
         AND status = 'expired'"
    );
    
    foreach ($expiredUsers as $user) {
        // Send re-enrollment email
        $to = $user['email'];
        $from = 'noreply@yourdomain.com';
        $replyto = 'support@yourdomain.com';
        $subject = "Re-enroll in Accessibility Course";
        $message = "Hello " . htmlspecialchars($user['name']) . ",\n\n" .
            "Our records show you started but did not complete your cohort: " . htmlspecialchars($user['cohort']) . ".\n" .
            "You are eligible to re-enroll for a $25 fee and finish the course.\n\n" .
            "To re-enroll, please visit: https://yourdomain.com/reenroll (or contact us for help).\n\n" .
            "If you have questions, reply to this email.\n\n" .
            "Thank you!";
        sendMailgun($to, $from, $subject, $message, $replyto);
    }
}

// 3. Update quiz completion status for all users
if (file_exists('quiz_score_tracker.php')) {
    require_once 'quiz_score_tracker.php';
    $tracker = new QuizScoreTracker();
    $quizResults = $tracker->processAllQuizUpdates();
    
    // Log quiz tracking results
    error_log("Weekly cron: Quiz tracking completed - {$quizResults['processed']} processed, {$quizResults['errors']} errors");
}

// 4. Import courses from Canvas and update total quiz points
if (file_exists('import_canvas_courses.php')) {
    require_once 'import_canvas_courses.php';
    $importer = new CourseImporter();
    $courseResults = $importer->importAndUpdatePoints();
    
    // Log course import results
    $importSummary = "Course import: {$courseResults['import']['total_imported']} new, {$courseResults['import']['total_updated']} updated, {$courseResults['import']['total_errors']} errors";
    $pointsSummary = "Points update: {$courseResults['points_update']['successful']} successful, {$courseResults['points_update']['failed']} failed";
    error_log("Weekly cron: {$importSummary}. {$pointsSummary}");
}
?> 