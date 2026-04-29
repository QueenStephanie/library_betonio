<?php
require_once __DIR__ . '/backend/config/AppBootstrap.php';

$dbConfig = AppBootstrap::getDatabaseConfig();
$pdo = new PDO(
    'mysql:host=' . $dbConfig['host'] . ';port=' . $dbConfig['port'] . ';dbname=' . $dbConfig['name'] . ';charset=' . $dbConfig['charset'],
    $dbConfig['user'],
    $dbConfig['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "<h1>Book Copies Fixer</h1>";

// Check if book_copies exists
try {
    $pdo->query('SELECT 1 FROM book_copies LIMIT 1');
} catch (Exception $e) {
    die("book_copies table does not exist.");
}

$stmt = $pdo->query('SELECT id, title, total_copies FROM books WHERE is_active = 1');
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalInserted = 0;

foreach ($books as $book) {
    $bookId = (int)$book['id'];
    $expectedCopies = (int)$book['total_copies'];
    
    $copyStmt = $pdo->prepare('SELECT COUNT(*) FROM book_copies WHERE book_id = :book_id');
    $copyStmt->execute([':book_id' => $bookId]);
    $existingCopies = (int)$copyStmt->fetchColumn();
    
    if ($existingCopies < $expectedCopies) {
        $toInsert = $expectedCopies - $existingCopies;
        echo "Book ID {$bookId} ({$book['title']}) needs {$toInsert} copies.<br>";
        
        $insertStmt = $pdo->prepare('INSERT INTO book_copies (book_id, barcode, status, created_at, updated_at) VALUES (:book_id, :barcode, :status, NOW(), NOW())');
        
        for ($i = 0; $i < $toInsert; $i++) {
            $token = substr(sha1($bookId . '|' . microtime(true) . '|' . $i), 0, 8);
            $barcode = 'BK' . $bookId . '-' . strtoupper($token);
            
            $insertStmt->execute([
                ':book_id' => $bookId,
                ':barcode' => $barcode,
                ':status'  => 'available'
            ]);
            $totalInserted++;
        }
    }
}

echo "<br><strong>Done! Inserted {$totalInserted} missing copies.</strong>";
