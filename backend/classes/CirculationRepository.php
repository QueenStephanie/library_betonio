<?php

/**
 * CirculationRepository
 * Read-only summary queries for borrower and admin dashboards.
 */
class CirculationRepository
{
  public static function getBorrowerOverview(PDO $db, int $userId): array
  {
    $overview = [
      'current_loans' => 0,
      'due_soon' => 0,
      'active_reservations' => 0,
      'outstanding_fines' => 0.0,
      'loan_history_count' => 0,
    ];

    if ($userId <= 0) {
      return $overview;
    }

    $loansSql = "SELECT
      COUNT(CASE WHEN loan_status IN ('active', 'overdue') THEN 1 END) AS current_loans,
      COUNT(CASE WHEN loan_status IN ('active', 'overdue') AND due_at >= CURDATE() AND due_at <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 1 END) AS due_soon,
      COUNT(CASE WHEN loan_status = 'returned' THEN 1 END) AS loan_history_count,
      COALESCE(SUM(CASE WHEN loan_status IN ('active', 'overdue') THEN fine_amount ELSE 0 END), 0) AS outstanding_fines
      FROM loans
      WHERE user_id = :user_id";

    $reservationsSql = "SELECT COUNT(*) FROM reservations WHERE user_id = :user_id AND status IN ('pending', 'ready_for_pickup')";

    $loanStmt = $db->prepare($loansSql);
    $loanStmt->execute([':user_id' => $userId]);
    $loanRow = $loanStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $reservationStmt = $db->prepare($reservationsSql);
    $reservationStmt->execute([':user_id' => $userId]);

    $overview['current_loans'] = (int)($loanRow['current_loans'] ?? 0);
    $overview['due_soon'] = (int)($loanRow['due_soon'] ?? 0);
    $overview['loan_history_count'] = (int)($loanRow['loan_history_count'] ?? 0);
    $overview['outstanding_fines'] = (float)($loanRow['outstanding_fines'] ?? 0);
    $overview['active_reservations'] = (int)$reservationStmt->fetchColumn();

    return $overview;
  }

  public static function getAdminOverview(PDO $db): array
  {
    $overview = [
      'catalog_titles' => 0,
      'available_copies' => 0,
      'active_loans' => 0,
      'active_reservations' => 0,
    ];

    $queryMap = [
      'catalog_titles' => "SELECT COUNT(*) FROM books WHERE is_active = 1",
      'available_copies' => "SELECT COUNT(*) FROM book_copies WHERE status = 'available'",
      'active_loans' => "SELECT COUNT(*) FROM loans WHERE loan_status IN ('active', 'overdue')",
      'active_reservations' => "SELECT COUNT(*) FROM reservations WHERE status IN ('pending', 'ready_for_pickup')",
    ];

    foreach ($queryMap as $key => $sql) {
      $stmt = $db->query($sql);
      $overview[$key] = (int)$stmt->fetchColumn();
    }

    return $overview;
  }
}
