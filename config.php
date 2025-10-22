<?php
// Database credentials
$dbhost = "webaim.cksrc9aw1l51.us-east-2.rds.amazonaws.com";
$dbuser = "onlinecourses";
$dbpass = "1ggA@SB4";
$dbname = "onlinecourses";

// Include local configuration if it exists
if (file_exists('config.local.php')) {
    include 'config.local.php';
}

// Include Canvas configuration
require_once('includes/canvas.php');

// Email configuration
$config = [
    'email_from' => 'accessibledocs@webaim.org',
    'email_from_name' => 'Course Registration System',
    'email_reply_to' => 'accessibledocs@webaim.org',
    'email_disabled' => !empty(getenv('DISABLE_EMAILS')),
    'months' => [
        1 => 'January', 2 => 'February', 3 => 'March',
        4 => 'April', 5 => 'May', 6 => 'June',
        7 => 'July', 8 => 'August', 9 => 'September',
        10 => 'October', 11 => 'November', 12 => 'December'
    ],
    'roles' => ['faculty', 'staff'],
    'course_id' => '2681',
    're_enrollment_fee' => 25,
    'statuses' => [
        'submitter' => 'Submitter',
        'active' => 'Active',
        'enrollee' => 'Enrolled',
        'completer' => 'Completed',
        'earner' => 'Certificate Earner',
        'expired' => 'Expired',
        'reenrolled' => 'Re-enrolled',
        'review' => 'In Review'
    ],
    'mailgun' => [
        'api_key' => 'YOUR_MAILGUN_API_KEY',
        'domain' => 'YOUR_MAILGUN_DOMAIN',
        'region' => 'us'
    ],
    'canvas' => $canvas_config
];

// Common validation functions
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/\.edu$/', $email);
}

function validateCohortMonth($month) {
    return $month >= 1 && $month <= 12;
}

function validateCohortYear($year) {
    return $year >= 24 && $year <= 99;
}

function validateRole($role) {
    global $config;
    return in_array($role, $config['roles']);
}

// Common HTML head content
function getHtmlHead($title) {
    return <<<HTML
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
HTML;
}

// Common JavaScript includes
function getJsIncludes() {
    return <<<HTML
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
HTML;
}
?> 