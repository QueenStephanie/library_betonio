<?php

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

clearAdminCsrfToken();
AuthSupport::clearSession();
redirect(appPath('login.php', ['logout' => 1]));
