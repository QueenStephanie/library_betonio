<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

/**
 * Logout Handler
 * Clears session first, then renders success UI
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new AuthManager($db);
$auth->logout();

$page_alerts = [
  [
    'type' => 'success',
    'title' => 'Logged Out Successfully',
    'message' => 'You have been logged out. Redirecting to login page...',
    'redirect' => appPath('login.php')
  ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - QueenLib</title>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <?php renderSweetAlertScripts(); ?>
    <?php renderPageAlerts($page_alerts); ?>
</body>
</html>
