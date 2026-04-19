<?php

return static function (): array {
  require_once __DIR__ . '/../../backend/classes/LibrarianPortalRepository.php';

  $rejectsMissingTitle = LibrarianPortalRepository::evaluateBookCreationRules([
    'title' => '',
    'author' => 'Author Name',
    'isbn' => '9780306406157',
    'publication_date' => '2020-01-01',
    'genre' => 'Science',
  ])['ok'] === false;

  $rejectsMissingAuthor = LibrarianPortalRepository::evaluateBookCreationRules([
    'title' => 'Valid Title',
    'author' => '',
    'isbn' => '9780306406157',
    'publication_date' => '2020-01-01',
    'genre' => 'Science',
  ])['ok'] === false;

  $rejectsInvalidIsbn = LibrarianPortalRepository::evaluateBookCreationRules([
    'title' => 'Valid Title',
    'author' => 'Author Name',
    'isbn' => '1234567890',
    'publication_date' => '2020-01-01',
    'genre' => 'Science',
  ])['ok'] === false;

  $rejectsInvalidDate = LibrarianPortalRepository::evaluateBookCreationRules([
    'title' => 'Valid Title',
    'author' => 'Author Name',
    'isbn' => '9780306406157',
    'publication_date' => '2020-13-40',
    'genre' => 'Science',
  ])['ok'] === false;

  $rejectsFutureDate = LibrarianPortalRepository::evaluateBookCreationRules([
    'title' => 'Valid Title',
    'author' => 'Author Name',
    'isbn' => '9780306406157',
    'publication_date' => '2999-01-01',
    'genre' => 'Science',
  ])['ok'] === false;

  $rejectsMissingGenre = LibrarianPortalRepository::evaluateBookCreationRules([
    'title' => 'Valid Title',
    'author' => 'Author Name',
    'isbn' => '9780306406157',
    'publication_date' => '2020-01-01',
    'genre' => '',
  ])['ok'] === false;

  $allowsValidPayload = LibrarianPortalRepository::evaluateBookCreationRules([
    'title' => 'The Pragmatic Programmer',
    'author' => 'Andrew Hunt',
    'isbn' => '978-0-201-61622-4',
    'publication_date' => '1999-10-30',
    'genre' => 'Programming',
  ]);

  $normalizesYear = ($allowsValidPayload['normalized']['published_year'] ?? 0) === 1999;
  $normalizesIsbn = ($allowsValidPayload['normalized']['isbn_normalized'] ?? '') === '9780201616224';

  $pass = $rejectsMissingTitle
    && $rejectsMissingAuthor
    && $rejectsInvalidIsbn
    && $rejectsInvalidDate
    && $rejectsFutureDate
    && $rejectsMissingGenre
    && $allowsValidPayload['ok'] === true
    && $normalizesYear
    && $normalizesIsbn;

  return [
    'name' => 'librarian_add_book_rules',
    'pass' => $pass,
    'details' => $pass
      ? 'Add-book validation rules validated.'
      : 'Add-book validation rule assertions failed.',
  ];
};
