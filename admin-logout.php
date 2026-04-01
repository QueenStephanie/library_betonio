<?php

require_once 'includes/config.php';
require_once 'includes/functions.php';

unset($_SESSION['admin_authenticated']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_last_login']);

redirect('admin-login.php');
