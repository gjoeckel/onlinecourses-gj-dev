<?php
// Centralized output buffering for JSON responses
function startJsonResponse() {
    ob_start();
    header('Content-Type: application/json');
}

function sendJsonError($message = null) {
    require_once __DIR__ . '/error_messages.php';
    $defaultMessage = ErrorMessages::getTechnicalDifficulties();
    ob_clean();
    echo json_encode(['error' => $message ?? $defaultMessage]);
    exit;
}

function sendJsonResponse($data, $prettyPrint = false) {
    ob_clean();
    if ($prettyPrint) {
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        echo json_encode($data);
    }
    exit;
}

function sendJsonErrorWithStatus($message, $statusCode) {
    ob_clean();
    http_response_code($statusCode);
    echo json_encode(['error' => $message]);
    exit;
} 