<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../../backend/classes/PasswordRecovery.php';
require_once __DIR__ . '/../../backend/mail/MailHandler.php';

/**
 * Canonical authentication service for public auth flows.
 */
class AuthService
{
  /** @var PDO */
  private $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  public function login(string $email, string $password): array
  {
    $auth = new AuthManager($this->db);
    return $auth->login($email, $password);
  }

  public function registerBorrower(string $firstName, string $lastName, string $email, string $password, string $passwordConfirm): array
  {
    $auth = new AuthManager($this->db);
    $result = $auth->register($firstName, $lastName, $email, $password, $passwordConfirm);

    if (empty($result['success'])) {
      return $result;
    }

    $verificationToken = (string)($result['verification_token'] ?? '');
    if ($verificationToken === '') {
      try {
        $rollback = $this->db->prepare('DELETE FROM users WHERE id = :id AND is_verified = 0');
        $rollback->execute([':id' => (int)($result['user_id'] ?? 0)]);
      } catch (Exception $rollbackError) {
        error_log('Registration rollback failed: ' . $rollbackError->getMessage());
      }

      return [
        'success' => false,
        'error' => 'Registration failed because verification token was not generated. Please try again.',
      ];
    }

    $mailResult = sendVerificationEmail(
      $email,
      $firstName,
      $verificationToken
    );

    if (!empty($mailResult['success'])) {
      return $result;
    }

    try {
      $rollback = $this->db->prepare('DELETE FROM users WHERE id = :id AND is_verified = 0');
      $rollback->execute([':id' => (int)($result['user_id'] ?? 0)]);
    } catch (Exception $rollbackError) {
      error_log('Registration rollback failed: ' . $rollbackError->getMessage());
    }

    return [
      'success' => false,
      'error' => 'Registration failed because verification email could not be sent. Please check SMTP settings.',
    ];
  }

  public function verifyEmailByToken(string $email, string $token): array
  {
    $auth = new AuthManager($this->db);
    return $auth->verifyEmailByToken($email, $token);
  }

  public function requestPasswordReset(string $email): array
  {
    $mailHandler = new MailHandler($this->db);
    $passwordRecovery = new PasswordRecovery($this->db, $mailHandler);
    return $passwordRecovery->requestPasswordReset($email);
  }

  public function verifyResetToken(string $email, string $token): array
  {
    $passwordRecovery = new PasswordRecovery($this->db);
    return $passwordRecovery->verifyResetToken($email, $token);
  }

  public function resetPassword(string $email, string $token, string $password, string $passwordConfirm): array
  {
    $passwordRecovery = new PasswordRecovery($this->db);
    return $passwordRecovery->resetPassword($email, $token, $password, $passwordConfirm);
  }
}
