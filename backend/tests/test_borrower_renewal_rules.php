<?php

return static function (): array {
  require_once __DIR__ . '/../../backend/classes/CirculationRepository.php';

  $maxRenewals = CirculationRepository::getBorrowerMaxRenewals();
  $extensionDays = CirculationRepository::getBorrowerRenewalExtensionDays();

  $rejectsUnauthenticated = CirculationRepository::evaluateBorrowerRenewalRules([
    'is_authenticated' => false,
    'loan_id' => 3,
    'loan_exists' => true,
    'is_own_loan' => true,
    'is_active_loan' => true,
    'has_active_queue_for_title' => false,
    'renewal_count' => 0,
    'max_renewals' => $maxRenewals,
  ])['ok'] === false;

  $rejectsMissingLoan = CirculationRepository::evaluateBorrowerRenewalRules([
    'is_authenticated' => true,
    'loan_id' => 3,
    'loan_exists' => false,
    'is_own_loan' => false,
    'is_active_loan' => false,
    'has_active_queue_for_title' => false,
    'renewal_count' => 0,
    'max_renewals' => $maxRenewals,
  ])['ok'] === false;

  $rejectsNotOwnLoan = CirculationRepository::evaluateBorrowerRenewalRules([
    'is_authenticated' => true,
    'loan_id' => 5,
    'loan_exists' => true,
    'is_own_loan' => false,
    'is_active_loan' => true,
    'has_active_queue_for_title' => false,
    'renewal_count' => 0,
    'max_renewals' => $maxRenewals,
  ])['ok'] === false;

  $rejectsClosedLoan = CirculationRepository::evaluateBorrowerRenewalRules([
    'is_authenticated' => true,
    'loan_id' => 5,
    'loan_exists' => true,
    'is_own_loan' => true,
    'is_active_loan' => false,
    'has_active_queue_for_title' => false,
    'renewal_count' => 0,
    'max_renewals' => $maxRenewals,
  ])['ok'] === false;

  $rejectsQueueConflict = CirculationRepository::evaluateBorrowerRenewalRules([
    'is_authenticated' => true,
    'loan_id' => 5,
    'loan_exists' => true,
    'is_own_loan' => true,
    'is_active_loan' => true,
    'has_active_queue_for_title' => true,
    'renewal_count' => 0,
    'max_renewals' => $maxRenewals,
  ])['ok'] === false;

  $rejectsMaxRenewals = CirculationRepository::evaluateBorrowerRenewalRules([
    'is_authenticated' => true,
    'loan_id' => 5,
    'loan_exists' => true,
    'is_own_loan' => true,
    'is_active_loan' => true,
    'has_active_queue_for_title' => false,
    'renewal_count' => $maxRenewals,
    'max_renewals' => $maxRenewals,
  ])['ok'] === false;

  $allowsRenewal = CirculationRepository::evaluateBorrowerRenewalRules([
    'is_authenticated' => true,
    'loan_id' => 5,
    'loan_exists' => true,
    'is_own_loan' => true,
    'is_active_loan' => true,
    'has_active_queue_for_title' => false,
    'renewal_count' => max(0, $maxRenewals - 1),
    'max_renewals' => $maxRenewals,
  ])['ok'] === true;

  $activeStatusChecks = CirculationRepository::isActiveLoanStatus('active')
    && CirculationRepository::isActiveLoanStatus('borrowed')
    && !CirculationRepository::isActiveLoanStatus('returned');

  $closedStatusChecks = CirculationRepository::isClosedLoanStatus('returned')
    && CirculationRepository::isClosedLoanStatus('lost')
    && !CirculationRepository::isClosedLoanStatus('active');

  $constantsValid = $maxRenewals >= 1 && $extensionDays >= 1;

  $pass = $rejectsUnauthenticated
    && $rejectsMissingLoan
    && $rejectsNotOwnLoan
    && $rejectsClosedLoan
    && $rejectsQueueConflict
    && $rejectsMaxRenewals
    && $allowsRenewal
    && $activeStatusChecks
    && $closedStatusChecks
    && $constantsValid;

  return [
    'name' => 'borrower_renewal_history_rules',
    'pass' => $pass,
    'details' => $pass
      ? 'Borrower renewal and active/closed loan rules validated.'
      : 'Borrower renewal/history rule assertions failed.',
  ];
};
