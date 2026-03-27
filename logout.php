<?php

/**
 * Logout Handler
 * Clears session and redirects to login
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new AuthManager($db);
$auth->logout();

setFlash('success', 'You have been logged out successfully.');
redirect('/library_betonio/login.php');
