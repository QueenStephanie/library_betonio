<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once APP_ROOT . '/backend/classes/PermissionGate.php';
require_once APP_ROOT . '/backend/classes/LibrarianPortalRepository.php';
require_once APP_ROOT . '/backend/classes/FineReporting.php';

PermissionGate::requirePageAccess('librarian', 'print_records');

$reportType = strtolower(trim((string)($_GET['type'] ?? '')));
$autoPrint = isset($_GET['auto_print']) && (string)($_GET['auto_print']) === '1';

$statusFilterRaw = trim((string)($_GET['status'] ?? ''));
$statusFilter = preg_replace('/[^a-z0-9_\-\s]/i', '', $statusFilterRaw);
if (!is_string($statusFilter)) {
  $statusFilter = '';
}
$statusFilter = trim($statusFilter);

$fromRaw = trim((string)($_GET['from'] ?? ''));
$toRaw = trim((string)($_GET['to'] ?? ''));
$fromDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromRaw) === 1 ? $fromRaw : '';
$toDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $toRaw) === 1 ? $toRaw : '';

$allowedTypes = ['circulation', 'reservations', 'fines', 'admin-fines'];
$isValidType = in_array($reportType, $allowedTypes, true);

$reportTitle = 'Printable Form';
$reportSubtitle = 'Generated library records';
$datasetAvailable = true;
$datasetMessage = '';
$rows = [];
$summaryCards = [];
$periodLabel = '';

$generatedAt = new DateTimeImmutable('now');
$generatedAtDisplay = $generatedAt->format('M j, Y g:i A');

$formatDateTime = static function ($value): string {
  $text = trim((string)$value);
  if ($text === '') {
    return '-';
  }

  $timestamp = strtotime($text);
  if ($timestamp === false) {
    return $text;
  }

  return date('M j, Y g:i A', $timestamp);
};

$formatText = static function ($value): string {
  if ($value === null) {
    return '-';
  }

  $text = trim((string)$value);
  return $text !== '' ? $text : '-';
};

if ($isValidType) {
  $pageAccessMap = [
    'circulation' => 'librarian-circulation',
    'reservations' => 'librarian-reservations',
    'fines' => 'librarian-fines',
    'admin-fines' => 'admin-fines',
  ];

  $requiredPage = $pageAccessMap[$reportType] ?? '';
  if ($requiredPage !== '') {
    PermissionGate::requirePageAccess($requiredPage);
  }

  if ($reportType === 'circulation') {
    $reportTitle = 'Circulation Printable Form';
    $reportSubtitle = 'Active circulation records';

    try {
      $result = LibrarianPortalRepository::getCirculationRows($db, 500);
      $rows = is_array($result['rows'] ?? null) ? $result['rows'] : [];

      if (empty($result['available'])) {
        $datasetAvailable = false;
        $datasetMessage = trim((string)($result['message'] ?? 'Circulation records are unavailable.'));
      }

      $overdueCount = 0;
      foreach ($rows as $row) {
        if (!empty($row['is_overdue'])) {
          $overdueCount++;
        }
      }

      $summaryCards = [
        ['label' => 'Active Loans', 'value' => (string)count($rows)],
        ['label' => 'Overdue Loans', 'value' => (string)$overdueCount],
      ];
    } catch (Exception $e) {
      error_log('print-records circulation error: ' . $e->getMessage());
      $datasetAvailable = false;
      $datasetMessage = 'Unable to load circulation records right now.';
    }
  } elseif ($reportType === 'reservations') {
    $reportTitle = 'Reservation Queue Printable Form';
    $reportSubtitle = 'Pending and ready reservation queue';

    try {
      $result = LibrarianPortalRepository::getReservationQueue($db, 500);
      $rows = is_array($result['rows'] ?? null) ? $result['rows'] : [];

      if (empty($result['available'])) {
        $datasetAvailable = false;
        $datasetMessage = trim((string)($result['message'] ?? 'Reservation queue is unavailable.'));
      }

      $readyCount = 0;
      foreach ($rows as $row) {
        $status = strtolower(trim((string)($row['status'] ?? '')));
        if (in_array($status, ['ready'], true)) {
          $readyCount++;
        }
      }

      $summaryCards = [
        ['label' => 'Queue Items', 'value' => (string)count($rows)],
        ['label' => 'Ready Items', 'value' => (string)$readyCount],
      ];
    } catch (Exception $e) {
      error_log('print-records reservations error: ' . $e->getMessage());
      $datasetAvailable = false;
      $datasetMessage = 'Unable to load reservation queue right now.';
    }
  } elseif ($reportType === 'fines' || $reportType === 'admin-fines') {
    $reportTitle = $reportType === 'admin-fines'
      ? 'Admin Fine Report Printable Form'
      : 'Fine Report Printable Form';
    $reportSubtitle = 'Month-to-date fine collection report';

    try {
      $report = FineReporting::getMonthToDateReport($db);
      $rows = is_array($report['items'] ?? null) ? $report['items'] : [];
      $periodLabel = trim((string)($report['period_label'] ?? ''));

      $summaryCards = [
        ['label' => 'MTD Amount', 'value' => number_format((float)($report['total_amount'] ?? 0), 2)],
        ['label' => 'MTD Collections', 'value' => (string)((int)($report['total_collections'] ?? 0))],
        ['label' => 'MTD Average', 'value' => number_format((float)($report['average_amount'] ?? 0), 2)],
      ];

      $allTime = LibrarianPortalRepository::getFineCollectionTotals($db);
      $summaryCards[] = [
        'label' => 'All-Time Amount',
        'value' => number_format((float)($allTime['all_time_amount'] ?? 0), 2),
      ];
      $summaryCards[] = [
        'label' => 'All-Time Collections',
        'value' => (string)((int)($allTime['all_time_collections'] ?? 0)),
      ];
    } catch (Exception $e) {
      error_log('print-records fines error: ' . $e->getMessage());
      $datasetAvailable = false;
      $datasetMessage = 'Unable to load fine report records right now.';
    }
  }
} else {
  http_response_code(400);
  $datasetAvailable = false;
  $datasetMessage = 'Unknown printable form type.';
  $reportTitle = 'Invalid Printable Form Request';
  $reportSubtitle = 'Requested report type is not supported.';
}

$filterSummary = [];
if ($statusFilter !== '') {
  $filterSummary[] = 'Status: ' . ucwords(str_replace('_', ' ', $statusFilter));
}
if ($fromDate !== '') {
  $filterSummary[] = 'From: ' . $fromDate;
}
if ($toDate !== '') {
  $filterSummary[] = 'To: ' . $toDate;
}

$printCssFile = APP_ROOT . '/public/css/print.css';
$printCssVersion = file_exists($printCssFile) ? (string)filemtime($printCssFile) : (string)time();
$printCssHref = htmlspecialchars(appPath('public/css/print.css', ['v' => $printCssVersion]), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($reportTitle, ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="stylesheet" href="<?php echo $printCssHref; ?>">
</head>

<body class="print-document-body">
  <main class="print-document">
    <header class="print-document-header">
      <h1><?php echo htmlspecialchars($reportTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
      <p><?php echo htmlspecialchars($reportSubtitle, ENT_QUOTES, 'UTF-8'); ?></p>
      <div class="print-meta-grid">
        <div><strong>Generated:</strong> <?php echo htmlspecialchars($generatedAtDisplay, ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Type:</strong> <?php echo htmlspecialchars($isValidType ? $reportType : 'invalid', ENT_QUOTES, 'UTF-8'); ?></div>
        <?php if ($periodLabel !== ''): ?>
          <div><strong>Period:</strong> <?php echo htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
      </div>
    </header>

    <?php if (!empty($filterSummary)): ?>
      <section class="print-section">
        <h2>Filters</h2>
        <p><?php echo htmlspecialchars(implode(' | ', $filterSummary), ENT_QUOTES, 'UTF-8'); ?></p>
      </section>
    <?php endif; ?>

    <?php if (!empty($summaryCards)): ?>
      <section class="print-section">
        <h2>Totals</h2>
        <div class="print-summary-grid">
          <?php foreach ($summaryCards as $card): ?>
            <article class="print-summary-card">
              <span><?php echo htmlspecialchars((string)($card['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
              <strong><?php echo htmlspecialchars((string)($card['value'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <section class="print-section">
      <h2>Records</h2>

      <?php if (!$datasetAvailable): ?>
        <p class="print-warning"><?php echo htmlspecialchars($datasetMessage !== '' ? $datasetMessage : 'No records available for this printable form.', ENT_QUOTES, 'UTF-8'); ?></p>
      <?php elseif (empty($rows)): ?>
        <p class="print-empty">No records available for the selected printable form.</p>
      <?php elseif ($reportType === 'circulation'): ?>
        <div class="print-table-wrap">
          <table class="print-table">
            <thead>
              <tr>
                <th>Loan ID</th>
                <th>Borrower</th>
                <th>Book</th>
                <th>Barcode</th>
                <th>Due</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
<?php foreach ($rows as $row): ?>
<?php
$borrowerName = formatBorrowerName(
    (string)($row['borrower_first_name'] ?? ''),
    (string)($row['borrower_last_name'] ?? ''),
    (string)($row['borrower_email'] ?? '')
);
if ($borrowerName === '') {
    $borrowerName = 'N/A';
}
$bookLabel = trim((string)($row['title'] ?? ''));
                if ($bookLabel === '') {
                  $bookLabel = 'Unknown title';
                }
                $bookAuthor = trim((string)($row['author'] ?? ''));
                if ($bookAuthor !== '') {
                  $bookLabel .= ' - ' . $bookAuthor;
                }

                $loanStatus = strtolower(trim((string)($row['loan_status'] ?? '')));
                $isOverdue = !empty($row['is_overdue']);
                $status = $isOverdue ? 'Overdue' : ucwords(str_replace('_', ' ', $loanStatus));
                if ($status === '' || $status === ' ') {
                  $status = '-';
                }
                ?>
                <tr>
                  <td>#<?php echo (int)($row['id'] ?? 0); ?></td>
                  <td><?php echo htmlspecialchars($borrowerName, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($bookLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($formatText($row['barcode'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($formatDateTime($row['due_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php elseif ($reportType === 'reservations'): ?>
        <div class="print-table-wrap">
          <table class="print-table">
            <thead>
              <tr>
                <th>Reservation ID</th>
                <th>Queue Position</th>
                <th>Borrower</th>
                <th>Book</th>
                <th>Status</th>
                <th>Queued At</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $row): ?>
                <?php
                $borrowerName = formatBorrowerName(
                    (string)($row['borrower_first_name'] ?? ''),
                    (string)($row['borrower_last_name'] ?? ''),
                    (string)($row['borrower_email'] ?? '')
                );
                if ($borrowerName === '') {
                    $borrowerName = 'N/A';
                }
                $bookLabel = trim((string)($row['book_title'] ?? ''));
                if ($bookLabel === '') {
                  $bookLabel = 'Unknown title';
                }
                $bookAuthor = trim((string)($row['book_author'] ?? ''));
                if ($bookAuthor !== '') {
                  $bookLabel .= ' - ' . $bookAuthor;
                }
                $status = ucwords(str_replace('_', ' ', strtolower(trim((string)($row['status'] ?? '')))));
                if ($status === '') {
                  $status = '-';
                }
                ?>
                <tr>
                  <td>#<?php echo (int)($row['id'] ?? 0); ?></td>
                  <td><?php echo (int)($row['queue_position'] ?? 0); ?></td>
                  <td><?php echo htmlspecialchars($borrowerName, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($bookLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($formatDateTime($row['queued_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="print-table-wrap">
          <table class="print-table">
            <thead>
              <tr>
                <th>Date & Time</th>
                <th>Receipt</th>
                <th>Borrower</th>
                <th>Collector</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $item): ?>
                <tr>
                  <td><?php echo htmlspecialchars($formatDateTime($item['collected_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($formatText($item['receipt_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($formatText($item['borrower_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($formatText($item['collector_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars(number_format((float)($item['amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars(ucfirst((string)($item['status'] ?? '-')), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($formatText($item['notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <?php if ($autoPrint && $datasetAvailable): ?>
    <script>
      window.addEventListener('load', function () {
        window.print();
      });
    </script>
  <?php endif; ?>
</body>

</html>
