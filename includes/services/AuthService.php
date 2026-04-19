<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../../backend/classes/PasswordRecovery.php';

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

    // Start transaction to ensure atomicity
    $this->db->beginTransaction();

    try {
      $result = $auth->register($firstName, $lastName, $email, $password, $passwordConfirm);

      if (empty($result['success'])) {
        $this->db->rollBack();
        return $result;
      }

      $verificationToken = (string)($result['verification_token'] ?? '');
      if ($verificationToken === '') {
        $this->db->rollBack();
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
        $this->db->commit();
        return $result;
      }

      // Email failed: rollback user creation
      $this->db->rollBack();
      return [
        'success' => false,
        'error' => 'Registration failed because verification email could not be sent. Please check SMTP settings.',
      ];
    } catch (Throwable $e) {
      if ($this->db->inTransaction()) {
        $this->db->rollBack();
      }
      error_log('AuthService::registerBorrower error: ' . $e->getMessage());
      return [
        'success' => false,
        'error' => 'Registration failed: ' . $e->getMessage(),
      ];
    }
  }

  public function verifyEmailByToken(string $email, string $token): array
  {
    $auth = new AuthManager($this->db);
    return $auth->verifyEmailByToken($email, $token);
  }

  public function requestPasswordReset(string $email): array
  {
    try {
      $mailHandler = $this->createMailHandler();
      $passwordRecovery = new PasswordRecovery($this->db, $mailHandler);
      return $passwordRecovery->requestPasswordReset($email);
    } catch (Throwable $e) {
      error_log('AuthService::requestPasswordReset error: ' . $e->getMessage());
      return ['success' => false, 'error' => 'Password reset request failed'];
    }
  }

  private function createMailHandler()
  {
    $composerAutoloadPath = __DIR__ . '/../../backend/vendor/autoload.php';
    if (!file_exists($composerAutoloadPath)) {
      throw new RuntimeException('Composer autoload not found at backend/vendor/autoload.php. Run composer install in backend/.');
    }

    $mailHandlerPath = __DIR__ . '/../../backend/mail/MailHandler.php';
    if (!file_exists($mailHandlerPath)) {
      throw new RuntimeException('Mail handler not found at backend/mail/MailHandler.php.');
    }

    require_once $composerAutoloadPath;
    require_once $mailHandlerPath;

    return new MailHandler($this->db);
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
