<?php
declare(strict_types=1);

http_response_code(410);
header('Content-Type: application/json; charset=UTF-8');

echo json_encode([
  'success' => false,
  'error' => 'Receipt reprint endpoint is retired. Migrate to the canonical circulation/fines flow for receipt output.',
], JSON_UNESCAPED_SLASHES);
