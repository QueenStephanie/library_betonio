<?php

return static function (): array {
  $runnerPath = realpath(__DIR__ . '/../migrations/migrate.php');
  if (!is_string($runnerPath) || $runnerPath === '') {
    return [
      'name' => 'migration_runner_smoke',
      'pass' => false,
      'details' => 'Migration runner not found.',
    ];
  }

  $phpBinary = defined('PHP_BINARY') ? PHP_BINARY : 'php';

  $dryRunOutput = [];
  $dryRunExitCode = 1;
  exec(escapeshellarg($phpBinary) . ' ' . escapeshellarg($runnerPath) . ' --dry-run 2>&1', $dryRunOutput, $dryRunExitCode);
  $dryRunText = implode("\n", $dryRunOutput);

  $statusOutput = [];
  $statusExitCode = 1;
  exec(escapeshellarg($phpBinary) . ' ' . escapeshellarg($runnerPath) . ' --status 2>&1', $statusOutput, $statusExitCode);
  $statusText = implode("\n", $statusOutput);

  $pass = $dryRunExitCode === 0
    && $statusExitCode === 0
    && strpos($dryRunText, 'Pending migrations:') !== false
    && strpos($statusText, 'Applied migrations:') !== false
    && strpos($statusText, 'Pending migrations:') !== false;

  $details = 'dry-run exit=' . $dryRunExitCode . ', status exit=' . $statusExitCode;

  if (!$pass) {
    $details .= '; dry-run output=' . $dryRunText . '; status output=' . $statusText;
  }

  return [
    'name' => 'migration_runner_smoke',
    'pass' => $pass,
    'details' => $details,
  ];
};
