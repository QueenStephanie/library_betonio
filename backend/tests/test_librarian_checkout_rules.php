<?php

return static function (): array {
  require_once __DIR__ . '/../../backend/classes/LibrarianPortalRepository.php';

  $rejectsInvalidBorrower = LibrarianPortalRepository::evaluateCheckoutRules([
    'borrower_user_id' => 0,
    'book_id' => 10,
    'has_available_copy' => true,
  ])['ok'] === false;

  $rejectsInvalidBook = LibrarianPortalRepository::evaluateCheckoutRules([
    'borrower_user_id' => 15,
    'book_id' => 0,
    'has_available_copy' => true,
  ])['ok'] === false;

  $rejectsUnavailableCopy = LibrarianPortalRepository::evaluateCheckoutRules([
    'borrower_user_id' => 15,
    'book_id' => 25,
    'has_available_copy' => false,
  ])['ok'] === false;

  $allowsValidCheckout = LibrarianPortalRepository::evaluateCheckoutRules([
    'borrower_user_id' => 15,
    'book_id' => 25,
    'has_available_copy' => true,
  ])['ok'] === true;

  $rejectsMissingReservation = LibrarianPortalRepository::evaluateReadyReservationCheckoutRules([
    'reservation_id' => 999,
    'reservation_exists' => false,
    'reservation_status' => 'ready_for_pickup',
    'borrower_user_id' => 5,
    'book_id' => 20,
    'has_available_copy' => true,
  ])['ok'] === false;

  $rejectsWrongStatus = LibrarianPortalRepository::evaluateReadyReservationCheckoutRules([
    'reservation_id' => 21,
    'reservation_exists' => true,
    'reservation_status' => 'pending',
    'borrower_user_id' => 5,
    'book_id' => 20,
    'has_available_copy' => true,
  ])['ok'] === false;

  $rejectsNoCopy = LibrarianPortalRepository::evaluateReadyReservationCheckoutRules([
    'reservation_id' => 21,
    'reservation_exists' => true,
    'reservation_status' => 'ready_for_pickup',
    'borrower_user_id' => 5,
    'book_id' => 20,
    'has_available_copy' => false,
  ])['ok'] === false;

  $allowsReadyBridge = LibrarianPortalRepository::evaluateReadyReservationCheckoutRules([
    'reservation_id' => 21,
    'reservation_exists' => true,
    'reservation_status' => 'ready',
    'borrower_user_id' => 5,
    'book_id' => 20,
    'has_available_copy' => true,
  ])['ok'] === true;

  $pass = $rejectsInvalidBorrower
    && $rejectsInvalidBook
    && $rejectsUnavailableCopy
    && $allowsValidCheckout
    && $rejectsMissingReservation
    && $rejectsWrongStatus
    && $rejectsNoCopy
    && $allowsReadyBridge;

  return [
    'name' => 'librarian_checkout_bridge_rules',
    'pass' => $pass,
    'details' => $pass
      ? 'Checkout and ready-reservation bridge rules validated.'
      : 'Checkout or ready-reservation bridge rule assertions failed.',
  ];
};
