<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
	http_response_code(404);
	exit('Not Found');
}

require_once 'includes/config.php';
require_once 'includes/functions.php';

setFlash('info', 'Admin login now uses the single sign-in page.');
redirect(appPath('login.php', ['force' => 1]));
