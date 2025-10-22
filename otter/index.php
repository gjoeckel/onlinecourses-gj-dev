<?php
// Simple redirect to login.php
// This handles both https://webaim.org/training/online/otter and https://webaim.org/training/online/otter/
// without any authentication checks - login.php remains the sole entry point

header('Location: login.php');
exit; 