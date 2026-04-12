<?php

require_once 'includes/config.php';
require_once 'includes/functions.php';

setFlash('info', 'Admin login now uses the single sign-in page.');
redirect(appPath('login.php', ['force' => 1]));
