<?php

/**
 * Email Integration Test Suite
 * Tests OTP and Password Reset flows with PHPMailer
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit();
}

$test_results = [];

try {
  // Load dependencies
  require_once __DIR__ . '/config/Database.php';
  require_once __DIR__ . '/classes/EmailVerification.php';
  require_once __DIR__ . '/classes/PasswordRecovery.php';
  require_once __DIR__ . '/mail/MailHandler.php';
  require_once __DIR__ . '/vendor/autoload.php';

  // Test 1: Database Connection
  $test_results['database'] = [
    'name' => 'Database Connection',
    'status' => 'pending'
  ];

  try {
    $database = new Database();
    $db = $database->connect();
    $test_results['database']['status'] = 'passed';
    $test_results['database']['details'] = 'Connected successfully';
  } catch (Exception $e) {
    $test_results['database']['status'] = 'failed';
    $test_results['database']['details'] = $e->getMessage();
  }

  // Test 2: PHPMailer Initialization
  $test_results['phpmailer'] = [
    'name' => 'PHPMailer Initialization',
    'status' => 'pending'
  ];

  try {
    $mail_handler = new MailHandler($db);
    $test_results['phpmailer']['status'] = 'passed';
    $test_results['phpmailer']['details'] = 'PHPMailer initialized with Gmail SMTP';
  } catch (Exception $e) {
    $test_results['phpmailer']['status'] = 'failed';
    $test_results['phpmailer']['details'] = $e->getMessage();
  }

  // Test 3: EmailVerification Class
  $test_results['email_verification'] = [
    'name' => 'EmailVerification Class',
    'status' => 'pending'
  ];

  try {
    $email_verification = new EmailVerification($db, $mail_handler);
    $otp = $email_verification->generateOTP();
    if (strlen($otp) === 6 && is_numeric($otp)) {
      $test_results['email_verification']['status'] = 'passed';
      $test_results['email_verification']['details'] = "OTP Generation working (Example: $otp)";
    } else {
      throw new Exception('OTP format invalid');
    }
  } catch (Exception $e) {
    $test_results['email_verification']['status'] = 'failed';
    $test_results['email_verification']['details'] = $e->getMessage();
  }

  // Test 4: PasswordRecovery Class
  $test_results['password_recovery'] = [
    'name' => 'PasswordRecovery Class',
    'status' => 'pending'
  ];

  try {
    $password_recovery = new PasswordRecovery($db, $mail_handler);
    $test_results['password_recovery']['status'] = 'passed';
    $test_results['password_recovery']['details'] = 'PasswordRecovery class initialized';
  } catch (Exception $e) {
    $test_results['password_recovery']['status'] = 'failed';
    $test_results['password_recovery']['details'] = $e->getMessage();
  }

  // Test 5: Email Configuration
  $test_results['config'] = [
    'name' => 'Email Configuration',
    'status' => 'pending'
  ];

  try {
    $config = require __DIR__ . '/config/email.config.php';
    if (!empty($config['smtp']['host']) && !empty($config['smtp']['username'])) {
      $test_results['config']['status'] = 'passed';
      $test_results['config']['details'] = "SMTP: {$config['smtp']['host']}:{$config['smtp']['port']} | From: {$config['smtp']['from_email']}";
    } else {
      throw new Exception('Configuration incomplete');
    }
  } catch (Exception $e) {
    $test_results['config']['status'] = 'failed';
    $test_results['config']['details'] = $e->getMessage();
  }

  // Test 6: Database Tables Existence
  $test_results['tables'] = [
    'name' => 'Database Tables',
    'status' => 'pending',
    'tables' => []
  ];

  try {
    $required_tables = ['users', 'otp_codes', 'verification_attempts'];
    $all_exist = true;

    foreach ($required_tables as $table) {
      $stmt = $db->query("SHOW TABLES LIKE '$table'");
      $exists = $stmt->rowCount() > 0;
      $test_results['tables']['tables'][$table] = $exists ? '✓' : '✗';
      if (!$exists) $all_exist = false;
    }

    $test_results['tables']['status'] = $all_exist ? 'passed' : 'failed';
    $test_results['tables']['details'] = $all_exist ? 'All required tables found' : 'Some tables missing';
  } catch (Exception $e) {
    $test_results['tables']['status'] = 'failed';
    $test_results['tables']['details'] = $e->getMessage();
  }

  // Summary
  $passed = 0;
  $failed = 0;
  foreach ($test_results as $test) {
    if ($test['status'] === 'passed') $passed++;
    else if ($test['status'] === 'failed') $failed++;
  }

  $summary = [
    'total_tests' => count($test_results),
    'passed' => $passed,
    'failed' => $failed,
    'success_rate' => $passed . '/' . count($test_results),
    'overall_status' => $failed === 0 ? 'All systems operational ✓' : 'Some issues detected ⚠'
  ];

  http_response_code(200);
  echo json_encode([
    'summary' => $summary,
    'tests' => $test_results
  ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'error' => 'Integration test failed',
    'message' => $e->getMessage()
  ], JSON_PRETTY_PRINT);
}
