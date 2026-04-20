<?php
require_once 'backend/config/AppBootstrap.php';
$dbConfig = AppBootstrap::getDatabaseConfig();
$pdo = new PDO(
    'mysql:host=' . $dbConfig['host'] . ';port=' . $dbConfig['port'] . ';dbname=' . $dbConfig['name'] . ';charset=' . $dbConfig['charset'],
    $dbConfig['user'],
    $dbConfig['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Check loans status values
$stmt = $pdo->query("SELECT DISTINCT status FROM loans LIMIT 20");
$statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo 'loan statuses: ' . implode(', ', $statuses) . PHP_EOL;

// Check book_copies structure
try {
    $stmt = $pdo->query('DESCRIBE book_copies');
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo 'book_copies columns: ' . implode(', ', $cols) . PHP_EOL;
} catch (Exception $e) {
    echo 'book_copies: ' . $e->getMessage() . PHP_EOL;
}

// Check reservations columns  
try {
    $stmt = $pdo->query('DESCRIBE reservations');
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo 'reservations columns: ' . implode(', ', $cols) . PHP_EOL;
} catch (Exception $e) {
    echo 'reservations: ' . $e->getMessage() . PHP_EOL;
}

// Count active loans
$stmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM loans GROUP BY status");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "  loans.status={$r['status']}: {$r['cnt']}" . PHP_EOL;
}
