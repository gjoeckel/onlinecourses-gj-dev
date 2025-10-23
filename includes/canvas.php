<?php
// Check local development first, then production fallback
$master_includes_path = '/Users/a00288946/cursor-global/projects/cursor-otter-dev/master_includes/onlinecourses_common.php';
if (!file_exists($master_includes_path)) {
    $master_includes_path = '/var/websites/webaim/master_includes/onlinecourses_common.php';
}
require_once($master_includes_path);

global $CANVAS_API_TOKEN, $CANVAS_API_URL;

$canvas_config = [
    'access_token' => $CANVAS_API_TOKEN,
    'api_url' => $CANVAS_API_URL
];
?>
