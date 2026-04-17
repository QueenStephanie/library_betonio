<?php

/**
 * Legacy wrapper kept for backward compatibility.
 *
 * The hardening enum SQL was consolidated into the canonical migration runner
 * flow and 2026_04_17_harden_verification_attempt_types.sql is now a documented
 * no-op placeholder to avoid duplicate-application confusion.
 *
 * Usage:
 *   php backend/migrations/run-harden-verification-attempt-types.php
 */

$runnerPath = __DIR__ . '/migrate.php';
if (!file_exists($runnerPath)) {
  fwrite(STDERR, 'Canonical migration runner not found: ' . $runnerPath . PHP_EOL);
  exit(1);
}

$phpBinary = defined('PHP_BINARY') ? PHP_BINARY : 'php';
$command = escapeshellarg($phpBinary) . ' ' . escapeshellarg($runnerPath) . ' --apply';

fwrite(STDOUT, 'Notice: harden wrapper is superseded; delegating to migrate.php --apply' . PHP_EOL);
passthru($command, $exitCode);
exit((int)$exitCode);
