<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - QueenLib</title>
    <!-- SweetAlert2 CSS -->
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
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <!-- SweetAlert Configuration -->
    <script src="/library_betonio/public/js/sweetalert-config.js"></script>

    <script>
        // Show logout success alert
        SweetAlerts.success(
            'Logged Out Successfully',
            'You have been logged out. Redirecting to login page...',
            function() {
                window.location.href = '/library_betonio/login.php';
            }
        );
    </script>

    <?php
    /**
     * Logout Handler (Backend)
     * Clears session and prepares for redirect
     */

    require_once 'includes/config.php';
    require_once 'includes/auth.php';
    require_once 'includes/functions.php';

    $auth = new AuthManager($db);
    $auth->logout();
    ?>
</body>
</html>
