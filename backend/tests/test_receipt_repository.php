<?php

return static function (): array {
  require_once __DIR__ . '/../../backend/classes/ReceiptRepository.php';

  $codes = [];
  $passesPattern = true;
  $isUnique = true;

  for ($i = 0; $i < 120; $i++) {
    $code = ReceiptRepository::generateReceiptCode('reservation_checkout');
    $codes[] = $code;

    if (preg_match('/^RCPT-[A-Z0-9]{3}-[0-9]{8}-[A-F0-9]{6}$/', $code) !== 1) {
      $passesPattern = false;
      break;
    }
  }

  if (count(array_unique($codes)) !== count($codes)) {
    $isUnique = false;
  }

  $pass = $passesPattern && $isUnique;

  return [
    'name' => 'receipt_code_generation',
    'pass' => $pass,
    'details' => $pass
      ? 'Receipt code format and uniqueness checks passed.'
      : 'Receipt code generation failed format or uniqueness checks.',
  ];
};
