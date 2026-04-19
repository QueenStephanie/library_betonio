<?php

/**
 * ReceiptRepository
 *
 * Handles durable transaction receipt persistence and lookups.
 */
class ReceiptRepository
{
  /** @var array<string, bool> */
  private static $tableCache = [];

  /** @var array<string, array<string, bool>> */
  private static $columnCache = [];

  private static function tableExists(PDO $db, string $table): bool
  {
    if (isset(self::$tableCache[$table])) {
      return self::$tableCache[$table];
    }

    $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name');
    $stmt->execute([':table_name' => $table]);
    $exists = (int)$stmt->fetchColumn() > 0;
    self::$tableCache[$table] = $exists;

    return $exists;
  }

  /** @return array<string, bool> */
  private static function getColumnMap(PDO $db, string $table): array
  {
    if (isset(self::$columnCache[$table])) {
      return self::$columnCache[$table];
    }

    if (!self::tableExists($db, $table)) {
      self::$columnCache[$table] = [];
      return self::$columnCache[$table];
    }

    $stmt = $db->prepare('SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table_name');
    $stmt->execute([':table_name' => $table]);

    $columns = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $column) {
      $columns[strtolower((string)$column)] = true;
    }

    self::$columnCache[$table] = $columns;
    return $columns;
  }

  private static function hasColumn(PDO $db, string $table, string $column): bool
  {
    $map = self::getColumnMap($db, $table);
    return isset($map[strtolower($column)]);
  }

  private static function requireReceiptsTable(PDO $db): void
  {
    if (!self::tableExists($db, 'transaction_receipts')) {
      throw new RuntimeException('Receipt storage is unavailable (missing transaction_receipts table).');
    }

    $required = ['id', 'receipt_code', 'transaction_type', 'transaction_ref_id'];
    foreach ($required as $column) {
      if (!self::hasColumn($db, 'transaction_receipts', $column)) {
        throw new RuntimeException('Receipt storage is unavailable due to incompatible transaction_receipts schema.');
      }
    }
  }

  private static function normalizeTypeToken(string $type): string
  {
    $normalized = strtolower(trim($type));
    if ($normalized === '') {
      return 'GEN';
    }

    $parts = preg_split('/[^a-z0-9]+/', $normalized);
    if ($parts === false) {
      $parts = [$normalized];
    }

    $token = '';
    foreach ($parts as $part) {
      if ($part === '') {
        continue;
      }
      $token .= strtoupper(substr($part, 0, 1));
      if (strlen($token) >= 3) {
        break;
      }
    }

    if ($token === '') {
      $token = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $normalized) ?: 'GEN', 0, 3));
    }

    return str_pad(substr($token, 0, 3), 3, 'X');
  }

  public static function generateReceiptCode(string $transactionType): string
  {
    $token = self::normalizeTypeToken($transactionType);
    $datePart = date('Ymd');
    $randomPart = strtoupper(str_pad(dechex(random_int(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT));
    return 'RCPT-' . $token . '-' . $datePart . '-' . $randomPart;
  }

  /**
   * @param array<string,mixed> $row
   * @return array<string,mixed>
   */
  private static function hydrateRow(array $row): array
  {
    $payload = [];
    $payloadRaw = (string)($row['payload_json'] ?? '');
    if ($payloadRaw !== '') {
      $decoded = json_decode($payloadRaw, true);
      if (is_array($decoded)) {
        $payload = $decoded;
      }
    }

    return [
      'id' => (int)($row['id'] ?? 0),
      'receipt_code' => (string)($row['receipt_code'] ?? ''),
      'transaction_type' => (string)($row['transaction_type'] ?? ''),
      'transaction_ref_id' => (int)($row['transaction_ref_id'] ?? 0),
      'borrower_user_id' => isset($row['borrower_user_id']) ? (int)$row['borrower_user_id'] : null,
      'actor_user_id' => isset($row['actor_user_id']) ? (int)$row['actor_user_id'] : null,
      'amount' => isset($row['amount']) ? (float)$row['amount'] : null,
      'payload_json' => $payloadRaw,
      'payload' => $payload,
      'created_at' => (string)($row['created_at'] ?? ''),
    ];
  }

  /**
   * @param array<string,mixed> $data
   * @return array<string,mixed>
   */
  public static function createForTransaction(PDO $db, array $data): array
  {
    self::requireReceiptsTable($db);

    $transactionType = strtolower(trim((string)($data['transaction_type'] ?? '')));
    $transactionRefId = (int)($data['transaction_ref_id'] ?? 0);
    $borrowerUserId = isset($data['borrower_user_id']) ? (int)$data['borrower_user_id'] : null;
    $actorUserId = isset($data['actor_user_id']) ? (int)$data['actor_user_id'] : null;
    $amount = array_key_exists('amount', $data) && $data['amount'] !== null ? (float)$data['amount'] : null;
    $payload = isset($data['payload']) && is_array($data['payload']) ? $data['payload'] : [];

    if ($transactionType === '' || $transactionRefId <= 0) {
      throw new InvalidArgumentException('Receipt creation requires transaction_type and transaction_ref_id.');
    }

    $existing = $db->prepare(
      'SELECT * FROM transaction_receipts
       WHERE transaction_type = :transaction_type AND transaction_ref_id = :transaction_ref_id
       LIMIT 1'
    );
    $existing->execute([
      ':transaction_type' => $transactionType,
      ':transaction_ref_id' => $transactionRefId,
    ]);
    $existingRow = $existing->fetch(PDO::FETCH_ASSOC);
    if (is_array($existingRow)) {
      $result = self::hydrateRow($existingRow);
      $result['created'] = false;
      return $result;
    }

    $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if (!is_string($payloadJson)) {
      $payloadJson = '{}';
    }

    $columns = ['receipt_code', 'transaction_type', 'transaction_ref_id'];
    $values = [':receipt_code', ':transaction_type', ':transaction_ref_id'];
    $params = [
      ':transaction_type' => $transactionType,
      ':transaction_ref_id' => $transactionRefId,
    ];

    if (self::hasColumn($db, 'transaction_receipts', 'borrower_user_id')) {
      $columns[] = 'borrower_user_id';
      $values[] = ':borrower_user_id';
      $params[':borrower_user_id'] = $borrowerUserId;
    }

    if (self::hasColumn($db, 'transaction_receipts', 'actor_user_id')) {
      $columns[] = 'actor_user_id';
      $values[] = ':actor_user_id';
      $params[':actor_user_id'] = $actorUserId;
    }

    if (self::hasColumn($db, 'transaction_receipts', 'amount')) {
      $columns[] = 'amount';
      $values[] = ':amount';
      $params[':amount'] = $amount;
    }

    if (self::hasColumn($db, 'transaction_receipts', 'payload_json')) {
      $columns[] = 'payload_json';
      $values[] = ':payload_json';
      $params[':payload_json'] = $payloadJson;
    }

    $insertSql = 'INSERT INTO transaction_receipts (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')';
    $insertStmt = $db->prepare($insertSql);

    $maxAttempts = 5;
    for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
      $params[':receipt_code'] = self::generateReceiptCode($transactionType);

      try {
        $insertStmt->execute($params);
        $receiptId = (int)$db->lastInsertId();
        $created = self::findById($db, $receiptId);
        if ($created === null) {
          throw new RuntimeException('Receipt was inserted but could not be reloaded.');
        }

        $created['created'] = true;
        return $created;
      } catch (PDOException $e) {
        $sqlState = (string)($e->errorInfo[0] ?? '');
        $driverCode = (int)($e->errorInfo[1] ?? 0);
        $isDuplicate = $sqlState === '23000' || in_array($driverCode, [1062, 1169], true);

        if (!$isDuplicate) {
          throw $e;
        }

        $existing->execute([
          ':transaction_type' => $transactionType,
          ':transaction_ref_id' => $transactionRefId,
        ]);
        $existingRow = $existing->fetch(PDO::FETCH_ASSOC);
        if (is_array($existingRow)) {
          $result = self::hydrateRow($existingRow);
          $result['created'] = false;
          return $result;
        }

        if ($attempt >= $maxAttempts - 1) {
          throw new RuntimeException('Unable to generate a unique receipt code after multiple attempts.');
        }
      }
    }

    throw new RuntimeException('Receipt creation failed unexpectedly.');
  }

  /**
   * @return array<string,mixed>|null
   */
  public static function findById(PDO $db, int $id): ?array
  {
    if ($id <= 0 || !self::tableExists($db, 'transaction_receipts')) {
      return null;
    }

    $stmt = $db->prepare('SELECT * FROM transaction_receipts WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($row)) {
      return null;
    }

    return self::hydrateRow($row);
  }

  /**
   * @return array<string,mixed>|null
   */
  public static function findByCode(PDO $db, string $code): ?array
  {
    $code = trim($code);
    if ($code === '' || !self::tableExists($db, 'transaction_receipts')) {
      return null;
    }

    $stmt = $db->prepare('SELECT * FROM transaction_receipts WHERE receipt_code = :receipt_code LIMIT 1');
    $stmt->execute([':receipt_code' => $code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($row)) {
      return null;
    }

    return self::hydrateRow($row);
  }
}
