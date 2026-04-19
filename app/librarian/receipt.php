<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once APP_ROOT . '/backend/classes/PermissionGate.php';
require_once APP_ROOT . '/backend/classes/ReceiptRepository.php';

PermissionGate::requireAdminRole('librarian', 'admin-dashboard.php');

$receiptId = isset($_GET['receipt_id']) ? (int)$_GET['receipt_id'] : 0;
$receiptCode = trim((string)($_GET['receipt_code'] ?? ''));
$autoPrint = isset($_GET['auto_print']) && (string)$_GET['auto_print'] === '1';
$download = isset($_GET['download']) && (string)$_GET['download'] === '1';
$receiptRepositoryClass = 'ReceiptRepository';

$receipt = null;
if ($receiptId > 0) {
  $receipt = $receiptRepositoryClass::findById($db, $receiptId);
} elseif ($receiptCode !== '') {
  $receipt = $receiptRepositoryClass::findByCode($db, $receiptCode);
}

if (!is_array($receipt)) {
  http_response_code(404);
}

if ($download && is_array($receipt)) {
  $downloadCode = trim((string)($receipt['receipt_code'] ?? ''));
  $downloadBaseName = $downloadCode !== '' ? strtolower($downloadCode) : ('receipt-' . (int)($receipt['id'] ?? 0));
  $downloadBaseName = preg_replace('/[^a-z0-9._-]/i', '_', $downloadBaseName);
  if (!is_string($downloadBaseName) || trim($downloadBaseName) === '') {
    $downloadBaseName = 'receipt';
  }

  header('Content-Type: text/html; charset=UTF-8');
  header('Content-Disposition: attachment; filename="' . $downloadBaseName . '.html"');
}

$payload = is_array($receipt['payload'] ?? null) ? $receipt['payload'] : [];
$title = 'Transaction Receipt';
if (is_array($receipt)) {
  $title = strtoupper(str_replace('_', ' ', (string)($receipt['transaction_type'] ?? 'transaction')));
  $title = trim($title) !== '' ? $title . ' RECEIPT' : 'TRANSACTION RECEIPT';
}

function receiptValue($value): string
{
  if ($value === null) {
    return '-';
  }

  if (is_bool($value)) {
    return $value ? 'true' : 'false';
  }

  if (is_scalar($value)) {
    $text = trim((string)$value);
    return $text !== '' ? $text : '-';
  }

  return '-';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
  <style>
    body {
      font-family: "Outfit", Arial, sans-serif;
      background: #f7f5f1;
      color: #1f2937;
      margin: 0;
      padding: 24px;
    }

    .receipt-wrap {
      max-width: 760px;
      margin: 0 auto;
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 24px;
    }

    h1 {
      margin: 0 0 12px;
      font-size: 1.5rem;
      letter-spacing: 0.03em;
    }

    .meta {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px 16px;
      margin-bottom: 20px;
      font-size: 0.95rem;
    }

    .meta strong {
      display: inline-block;
      min-width: 150px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 8px;
    }

    .table-wrap {
      overflow-x: auto;
    }

    th,
    td {
      text-align: left;
      padding: 8px;
      border-bottom: 1px solid #eceff3;
      vertical-align: top;
      font-size: 0.9rem;
      overflow-wrap: anywhere;
      word-break: break-word;
    }

    th {
      width: 32%;
      color: #374151;
    }

    .missing {
      color: #b91c1c;
      font-weight: 600;
    }

    @media (max-width: 640px) {
      .meta {
        grid-template-columns: 1fr;
      }

      .meta strong {
        min-width: 0;
      }
    }

    @media print {
      body {
        background: #fff;
        padding: 0;
      }

      .receipt-wrap {
        border: 0;
        border-radius: 0;
        max-width: none;
        padding: 0;
      }
    }
  </style>
</head>

<body>
  <main class="receipt-wrap">
    <?php if (!is_array($receipt)): ?>
      <h1>Receipt Not Found</h1>
      <p class="missing">No receipt was found for the provided identifier.</p>
    <?php else: ?>
      <h1><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>

      <section class="meta">
        <div><strong>Receipt Code:</strong> <?php echo htmlspecialchars((string)($receipt['receipt_code'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Created At:</strong> <?php echo htmlspecialchars((string)($receipt['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Receipt ID:</strong> <?php echo (int)($receipt['id'] ?? 0); ?></div>
        <div><strong>Type:</strong> <?php echo htmlspecialchars((string)($receipt['transaction_type'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Reference ID:</strong> <?php echo (int)($receipt['transaction_ref_id'] ?? 0); ?></div>
        <div><strong>Borrower User ID:</strong> <?php echo htmlspecialchars(receiptValue($receipt['borrower_user_id'] ?? null), ENT_QUOTES, 'UTF-8'); ?></div>
      </section>

      <section>
        <h2 style="margin:0 0 8px;font-size:1.05rem;">Receipt Snapshot</h2>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Field</th>
                <th>Value</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($payload)): ?>
                <tr>
                  <td colspan="2">No snapshot data stored.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($payload as $key => $value): ?>
                  <tr>
                    <th><?php echo htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8'); ?></th>
                    <td><?php echo htmlspecialchars(receiptValue($value), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    <?php endif; ?>
  </main>

  <?php if ($autoPrint && is_array($receipt)): ?>
    <script>
      window.addEventListener('load', function () {
        window.print();
      });
    </script>
  <?php endif; ?>
</body>

</html>
