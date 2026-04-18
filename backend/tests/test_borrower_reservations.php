<?php

return static function (): array {
  require_once __DIR__ . '/../../backend/classes/CirculationRepository.php';

  $max = CirculationRepository::getBorrowerMaxActiveReservations();

  $expectsRejectUnauthenticated = CirculationRepository::evaluateBorrowerReservationCreationRules([
    'is_authenticated' => false,
    'book_id' => 3,
    'book_exists' => true,
    'book_is_active' => true,
    'has_duplicate_active_reservation' => false,
    'active_reservation_count' => 0,
    'max_active_reservations' => $max,
  ])['ok'] === false;

  $expectsRejectInvalidBook = CirculationRepository::evaluateBorrowerReservationCreationRules([
    'is_authenticated' => true,
    'book_id' => 0,
    'book_exists' => true,
    'book_is_active' => true,
    'has_duplicate_active_reservation' => false,
    'active_reservation_count' => 0,
    'max_active_reservations' => $max,
  ])['ok'] === false;

  $expectsRejectMissingBook = CirculationRepository::evaluateBorrowerReservationCreationRules([
    'is_authenticated' => true,
    'book_id' => 10,
    'book_exists' => false,
    'book_is_active' => false,
    'has_duplicate_active_reservation' => false,
    'active_reservation_count' => 0,
    'max_active_reservations' => $max,
  ])['ok'] === false;

  $expectsRejectInactiveBook = CirculationRepository::evaluateBorrowerReservationCreationRules([
    'is_authenticated' => true,
    'book_id' => 10,
    'book_exists' => true,
    'book_is_active' => false,
    'has_duplicate_active_reservation' => false,
    'active_reservation_count' => 0,
    'max_active_reservations' => $max,
  ])['ok'] === false;

  $expectsRejectDuplicate = CirculationRepository::evaluateBorrowerReservationCreationRules([
    'is_authenticated' => true,
    'book_id' => 9,
    'book_exists' => true,
    'book_is_active' => true,
    'has_duplicate_active_reservation' => true,
    'active_reservation_count' => 1,
    'max_active_reservations' => $max,
  ])['ok'] === false;

  $expectsRejectMax = CirculationRepository::evaluateBorrowerReservationCreationRules([
    'is_authenticated' => true,
    'book_id' => 9,
    'book_exists' => true,
    'book_is_active' => true,
    'has_duplicate_active_reservation' => false,
    'active_reservation_count' => $max,
    'max_active_reservations' => $max,
  ])['ok'] === false;

  $expectsAllowValid = CirculationRepository::evaluateBorrowerReservationCreationRules([
    'is_authenticated' => true,
    'book_id' => 9,
    'book_exists' => true,
    'book_is_active' => true,
    'has_duplicate_active_reservation' => false,
    'active_reservation_count' => max(0, $max - 1),
    'max_active_reservations' => $max,
  ])['ok'] === true;

  $cancelPendingAllowed = CirculationRepository::canBorrowerCancelReservationStatus('pending') === true;
  $cancelReadyAllowed = CirculationRepository::canBorrowerCancelReservationStatus('ready_for_pickup') === true
    && CirculationRepository::canBorrowerCancelReservationStatus('ready') === true;
  $cancelFulfilledBlocked = CirculationRepository::canBorrowerCancelReservationStatus('fulfilled') === false;
  $cancelCancelledBlocked = CirculationRepository::canBorrowerCancelReservationStatus('cancelled') === false;

  $pass = $expectsRejectUnauthenticated
    && $expectsRejectInvalidBook
    && $expectsRejectMissingBook
    && $expectsRejectInactiveBook
    && $expectsRejectDuplicate
    && $expectsRejectMax
    && $expectsAllowValid
    && $cancelPendingAllowed
    && $cancelReadyAllowed
    && $cancelFulfilledBlocked
    && $cancelCancelledBlocked;

  return [
    'name' => 'borrower_reservation_business_rules',
    'pass' => $pass,
    'details' => $pass
      ? 'Borrower reservation create/cancel business rules validated.'
      : 'Borrower reservation business rule assertions failed.',
  ];
};
