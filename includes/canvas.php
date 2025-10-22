<?php
require_once('/var/websites/webaim/master_includes/onlinecourses_common.php');

global $CANVAS_API_TOKEN, $CANVAS_API_URL;

$canvas_config = [
    'access_token' => $CANVAS_API_TOKEN,
    'api_url' => $CANVAS_API_URL
];
?> 