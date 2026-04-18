<?php

declare(strict_types=1);

$testFiles = [
  __DIR__ . '/test_migration_runner.php',
  __DIR__ . '/test_auth_session_helpers.php',
  __DIR__ . '/test_csrf_origin_helpers.php',
  __DIR__ . '/test_permission_gate.php',
  __DIR__ . '/test_borrower_reservations.php',
<<<<<<< ours
<<<<<<< ours
=======
  __DIR__ . '/test_librarian_checkout_rules.php',
  __DIR__ . '/test_borrower_renewal_rules.php',
>>>>>>> theirs
=======
  __DIR__ . '/test_librarian_checkout_rules.php',
  __DIR__ . '/test_borrower_renewal_rules.php',
>>>>>>> theirs
];

$results = [];
$passed = 0;
$failed = 0;

foreach ($testFiles as $testFile) {
  if (!file_exists($testFile)) {
    $results[] = [
      'name' => basename($testFile),
      'pass' => false,
      'details' => 'Test file not found.',
    ];
    $failed++;
    continue;
  }

  $factory = require $testFile;

  if (!is_callable($factory)) {
    $results[] = [
      'name' => basename($testFile),
      'pass' => false,
      'details' => 'Test file did not return a callable.',
    ];
    $failed++;
    continue;
  }

  try {
    $result = $factory();
    $name = (string)($result['name'] ?? basename($testFile));
    $pass = !empty($result['pass']);
    $details = (string)($result['details'] ?? '');

    $results[] = [
      'name' => $name,
      'pass' => $pass,
      'details' => $details,
    ];

    if ($pass) {
      $passed++;
    } else {
      $failed++;
    }
  } catch (Throwable $e) {
    $results[] = [
      'name' => basename($testFile),
      'pass' => false,
      'details' => 'Unhandled exception: ' . $e->getMessage(),
    ];
    $failed++;
  }
}

fwrite(STDOUT, 'Backend regression harness' . PHP_EOL);
fwrite(STDOUT, str_repeat('-', 28) . PHP_EOL);

foreach ($results as $result) {
  $marker = $result['pass'] ? 'PASS' : 'FAIL';
  fwrite(STDOUT, '[' . $marker . '] ' . $result['name'] . PHP_EOL);
  if ($result['details'] !== '') {
    fwrite(STDOUT, '       ' . $result['details'] . PHP_EOL);
  }
}

fwrite(STDOUT, str_repeat('-', 28) . PHP_EOL);
fwrite(STDOUT, 'Passed: ' . $passed . PHP_EOL);
fwrite(STDOUT, 'Failed: ' . $failed . PHP_EOL);

exit($failed > 0 ? 1 : 0);
