<?php
declare(strict_types=1);

http_response_code(410);
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

echo json_encode([
  'success' => false,
  'error' => 'Endpoint retired (410 Gone). Avatar upload is handled by account profile endpoints.',
  'code' => 'endpoint_retired',
  'migration' => [
    'message' => 'Use app/user/account.php profile update flow for avatar and profile changes.',
    'target' => 'app/user/account.php',
  ],
], JSON_UNESCAPED_SLASHES);
