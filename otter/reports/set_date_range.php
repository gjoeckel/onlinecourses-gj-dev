<?php
require_once __DIR__ . '/../lib/session.php';
require_once __DIR__ . '/../lib/output_buffer.php';
initializeSession();
startJsonResponse();
$input = json_decode(file_get_contents('php://input'), true);
$start = $input['start_date'] ?? '';
$end = $input['end_date'] ?? '';
if (preg_match('/^\d{2}-\d{2}-\d{2}$/', $start) && preg_match('/^\d{2}-\d{2}-\d{2}$/', $end)) {
    $_SESSION['reports_date_range'] = [
        'start' => $start,
        'end' => $end
    ];
    sendJsonResponse(['success' => true]);
} else {
    require_once __DIR__ . '/../lib/error_messages.php';
    sendJsonResponse(['success' => false, 'error' => ErrorMessages::getTechnicalDifficulties()]);
}