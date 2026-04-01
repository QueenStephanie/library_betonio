<?php

/**
 * Month-to-date fine reporting helper.
 */
class FineReporting
{
  public static function getMonthToDateReport(PDO $db)
  {
    $now = new DateTimeImmutable('now');
    $periodStart = $now->modify('first day of this month')->setTime(0, 0, 0);
    $periodEnd = $now->setTime(23, 59, 59);

    $aggregateStmt = $db->prepare(
      'SELECT
        COUNT(*) AS total_collections,
        COALESCE(SUM(amount), 0) AS total_amount,
        COALESCE(AVG(amount), 0) AS average_amount
      FROM fine_collections
      WHERE status = :status
        AND collected_at >= :period_start
        AND collected_at <= :period_end'
    );
    $aggregateStmt->execute([
      ':status' => 'collected',
      ':period_start' => $periodStart->format('Y-m-d H:i:s'),
      ':period_end' => $periodEnd->format('Y-m-d H:i:s'),
    ]);
    $aggregates = $aggregateStmt->fetch(PDO::FETCH_ASSOC) ?: [
      'total_collections' => 0,
      'total_amount' => 0,
      'average_amount' => 0,
    ];

    $itemsSql = <<<'SQL'
SELECT
  fc.id,
  fc.receipt_code,
  fc.amount,
  fc.status,
  fc.notes,
  fc.collected_at,
  CONCAT(COALESCE(collector.first_name, ''), ' ', COALESCE(collector.last_name, '')) AS collector_name,
  CONCAT(COALESCE(borrower.first_name, ''), ' ', COALESCE(borrower.last_name, '')) AS borrower_name
FROM fine_collections fc
LEFT JOIN users collector ON collector.id = fc.collected_by_user_id
LEFT JOIN users borrower ON borrower.id = fc.borrower_user_id
WHERE fc.status = :status
  AND fc.collected_at >= :period_start
  AND fc.collected_at <= :period_end
ORDER BY fc.collected_at DESC, fc.id DESC
SQL;

    $itemsStmt = $db->prepare($itemsSql);
    $itemsStmt->execute([
      ':status' => 'collected',
      ':period_start' => $periodStart->format('Y-m-d H:i:s'),
      ':period_end' => $periodEnd->format('Y-m-d H:i:s'),
    ]);

    return [
      'period_start' => $periodStart->format('Y-m-d'),
      'period_end' => $periodEnd->format('Y-m-d'),
      'period_label' => $periodStart->format('M j, Y') . ' to ' . $periodEnd->format('M j, Y'),
      'total_collections' => (int)$aggregates['total_collections'],
      'total_amount' => (float)$aggregates['total_amount'],
      'average_amount' => (float)$aggregates['average_amount'],
      'items' => $itemsStmt->fetchAll(PDO::FETCH_ASSOC),
    ];
  }
}
