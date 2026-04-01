<?php

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$currentSessionId = session_id();
AuthSupport::invalidateCurrentAdminSession($db, $currentSessionId);
clearAdminCsrfToken();

unset($_SESSION['admin_authenticated']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_last_login']);
unset($_SESSION['admin_auth_mode']);
unset($_SESSION['admin_credential_id']);
unset($_SESSION['show_admin_welcome']);
unset($_SESSION['admin_profile']);
unset($_SESSION['admin_password_changed_at']);

session_regenerate_id(true);

redirect('admin-login.php');
