<?php

return static function (): array {
  require_once __DIR__ . '/../../includes/config.php';
  require_once __DIR__ . '/../../includes/functions.php';

  $serverBackup = $_SERVER;

  try {
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['SERVER_PORT'] = '80';
    unset($_SERVER['HTTPS']);
    unset($_SERVER['HTTP_X_FORWARDED_PROTO']);

    $normalizedOk = normalizeOriginUrl('http://localhost/library_betonio/login.php') === 'http://localhost:80';
    $normalizedReject = normalizeOriginUrl('javascript:alert(1)') === null;

    $_SERVER['HTTP_ORIGIN'] = 'http://localhost/library_betonio/login.php';
    unset($_SERVER['HTTP_REFERER']);
    $sameOrigin = validateStateChangingRequestOrigin('test_same_origin');
    $sameOriginPass = !empty($sameOrigin['valid']) && ($sameOrigin['reason'] ?? '') === '';

    $_SERVER['HTTP_ORIGIN'] = 'https://evil.example.com/login';
    $crossOrigin = validateStateChangingRequestOrigin('test_cross_origin');
    $crossOriginPass = empty($crossOrigin['valid']) && ($crossOrigin['reason'] ?? '') === 'origin_mismatch';

    unset($_SERVER['HTTP_ORIGIN'], $_SERVER['HTTP_REFERER']);
    $missingHeaders = validateStateChangingRequestOrigin('test_missing_headers');
    $missingHeadersPass = empty($missingHeaders['valid']) && ($missingHeaders['reason'] ?? '') === 'missing_origin_headers';

    $pass = $normalizedOk && $normalizedReject && $sameOriginPass && $crossOriginPass && $missingHeadersPass;

    return [
      'name' => 'csrf_origin_helpers',
      'pass' => $pass,
      'details' => $pass
        ? 'Origin normalization and same-origin checks passed (positive and negative).'
        : 'Origin normalization or validation checks failed.',
    ];
  } finally {
    $_SERVER = $serverBackup;
  }
};
