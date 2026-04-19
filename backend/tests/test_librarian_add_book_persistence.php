<?php

return static function (): array {
  require_once __DIR__ . '/../../includes/config.php';
  global $db;

  if (!($db instanceof PDO)) {
    return [
      'name' => 'librarian_add_book_persistence',
      'pass' => false,
      'details' => 'Database connection is unavailable.',
    ];
  }

  require_once __DIR__ . '/../../backend/classes/LibrarianPortalRepository.php';

  $seed = bin2hex(random_bytes(4));
  $buildIsbn13 = static function (string $firstTwelveDigits): string {
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
      $digit = (int)$firstTwelveDigits[$i];
      $sum += $digit * ($i % 2 === 0 ? 1 : 3);
    }

    $checkDigit = (10 - ($sum % 10)) % 10;
    return $firstTwelveDigits . (string)$checkDigit;
  };

  $isbnFirst = $buildIsbn13('9780306406' . str_pad((string)random_int(10, 99), 2, '0', STR_PAD_LEFT));
  $isbnSecond = $buildIsbn13('9780131103' . str_pad((string)random_int(10, 99), 2, '0', STR_PAD_LEFT));

  $db->beginTransaction();

  try {
    $firstInsert = LibrarianPortalRepository::addBook($db, [
      'title' => 'Refactoring ' . $seed,
      'author' => 'Martin Fowler',
      'isbn' => 'ISBN ' . $isbnFirst,
      'publication_date' => '1999-07-08',
      'genre' => 'Programming',
    ]);

    $firstId = (int)($firstInsert['book_id'] ?? 0);
    $storedIsbnStmt = $db->prepare('SELECT isbn FROM books WHERE id = :id');
    $storedIsbnStmt->execute([':id' => $firstId]);
    $storedIsbn = (string)$storedIsbnStmt->fetchColumn();

    $duplicateByIsbn = LibrarianPortalRepository::addBook($db, [
      'title' => 'Refactoring Second Entry ' . $seed,
      'author' => 'Martin Fowler',
      'isbn' => $isbnFirst,
      'publication_date' => '1999-07-08',
      'genre' => 'Programming',
    ]);

    $secondBaseInsert = LibrarianPortalRepository::addBook($db, [
      'title' => 'Domain-Driven Design ' . $seed,
      'author' => 'Eric Evans',
      'isbn' => $isbnSecond,
      'publication_date' => '2003-08-30',
      'genre' => 'Software',
    ]);

    $duplicateByIdentity = LibrarianPortalRepository::addBook($db, [
      'title' => 'domain-driven design ' . $seed,
      'author' => 'eric evans',
      'isbn' => '9780134434421',
      'publication_date' => '2003-11-15',
      'genre' => 'Software',
    ]);

    $countStmt = $db->prepare('SELECT COUNT(*) FROM books WHERE title IN (:title_one, :title_two)');
    $countStmt->execute([
      ':title_one' => 'Refactoring ' . $seed,
      ':title_two' => 'Domain-Driven Design ' . $seed,
    ]);
    $bookCount = (int)$countStmt->fetchColumn();

    $pass = !empty($firstInsert['ok'])
      && $storedIsbn === $isbnFirst
      && empty($duplicateByIsbn['ok'])
      && !empty($secondBaseInsert['ok'])
      && empty($duplicateByIdentity['ok'])
      && $bookCount === 2;

    $db->rollBack();

    return [
      'name' => 'librarian_add_book_persistence',
      'pass' => $pass,
      'details' => $pass
        ? 'Add-book persistence and duplicate rules validated.'
        : 'Add-book persistence or duplicate rule assertions failed.',
    ];
  } catch (Throwable $e) {
    if ($db->inTransaction()) {
      $db->rollBack();
    }

    return [
      'name' => 'librarian_add_book_persistence',
      'pass' => false,
      'details' => 'Unhandled exception: ' . $e->getMessage(),
    ];
  }
};
