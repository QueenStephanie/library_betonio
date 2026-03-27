<?php

/**
 * Password Recovery Class
 * Handles password reset and recovery functionality
 */

class PasswordRecovery
{
  private $db;
  private $table = 'users';
  private $otp_table = 'otp_codes';
  private $attempts_table = 'verification_attempts';
  private $mail_handler;
  private $config;

  public function __construct($database, $mail_handler = null)
  {
    $this->db = $database;
    $this->mail_handler = $mail_handler;
    $this->config = require __DIR__ . '/../config/email.config.php';
  }

  /**
   * Request password reset
   */
  public function requestPasswordReset($email)
  {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return ['success' => false, 'error' => 'Invalid email address'];
    }

    try {
      // Check if user exists
      $query = "SELECT id, first_name, email FROM " . $this->table . " WHERE email = :email AND is_active = 1";
      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':email', $email);
      $stmt->execute();

      if ($stmt->rowCount() === 0) {
        // Don't reveal if email exists (security best practice)
        return [
          'success' => true,
          'message' => 'If email exists, password reset link will be sent'
        ];
      }

      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      // Check rate limiting (prevent abuse)
      $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
      $rate_limit_check = $this->checkRateLimit($email, 'password_reset', $ip_address, 3);

      if (!$rate_limit_check) {
        return ['success' => false, 'error' => 'Too many password reset requests. Try again later.'];
      }

      // Generate reset token (valid for 1 hour)
      $reset_token = bin2hex(random_bytes(32));
      $reset_token_hash = password_hash($reset_token, PASSWORD_BCRYPT);
      $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

      // Update user with reset token
      $update_query = "UPDATE " . $this->table . " 
                            SET reset_token = :reset_token, reset_token_expires = :expires_at 
                            WHERE id = :user_id";

      $update_stmt = $this->db->prepare($update_query);
      $update_stmt->bindParam(':reset_token', $reset_token_hash);
      $update_stmt->bindParam(':expires_at', $expires_at);
      $update_stmt->bindParam(':user_id', $user['id']);
      $update_stmt->execute();

      // Send reset email if mail handler is available
      if ($this->mail_handler) {
        $mail_result = $this->mail_handler->sendPasswordResetEmail($email, $reset_token, $user['first_name']);

        if (!$mail_result['success']) {
          error_log("Failed to send password reset email to $email");
        }
      }

      // Log attempt
      $this->logAttempt($email, 'password_reset', $ip_address, true);

      return [
        'success' => true,
        'message' => 'If email exists, password reset link will be sent',
        'user_id' => $user['id']
      ];
    } catch (PDOException $e) {
      error_log("Password reset request error: " . $e->getMessage());
      return ['success' => false, 'error' => 'Password reset request failed'];
    }
  }

  /**
   * Verify reset token and reset password
   */
  public function resetPassword($email, $reset_token, $new_password, $confirm_password)
  {
    // Validation
    if (empty($email) || empty($reset_token) || empty($new_password)) {
      return ['success' => false, 'error' => 'All fields are required'];
    }

    if ($new_password !== $confirm_password) {
      return ['success' => false, 'error' => 'Passwords do not match'];
    }

    if (strlen($new_password) < 8) {
      return ['success' => false, 'error' => 'Password must be at least 8 characters long'];
    }

    // Check password strength
    if (!$this->isPasswordStrong($new_password)) {
      return ['success' => false, 'error' => 'Password must contain uppercase, lowercase, number, and special character'];
    }

    try {
      // Get user and verify reset token
      $query = "SELECT id, email, reset_token, reset_token_expires, password_hash 
                      FROM " . $this->table . " 
                      WHERE email = :email AND reset_token IS NOT NULL";

      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':email', $email);
      $stmt->execute();

      if ($stmt->rowCount() === 0) {
        return ['success' => false, 'error' => 'Invalid or expired reset token'];
      }

      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      // Check if token is expired
      if (strtotime($user['reset_token_expires']) < time()) {
        return ['success' => false, 'error' => 'Reset token has expired'];
      }

      // Verify token (using password_verify for security)
      if (!password_verify($reset_token, $user['reset_token'])) {
        $this->logAttempt($email, 'password_reset_verify', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', false);
        return ['success' => false, 'error' => 'Invalid reset token'];
      }

      // Check that new password is different from old password
      if (password_verify($new_password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'New password must be different from current password'];
      }

      // Hash new password
      $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);

      // Update password and clear reset token
      $update_query = "UPDATE " . $this->table . " 
                            SET password_hash = :password_hash, 
                                reset_token = NULL, 
                                reset_token_expires = NULL,
                                updated_at = NOW()
                            WHERE id = :user_id";

      $update_stmt = $this->db->prepare($update_query);
      $update_stmt->bindParam(':password_hash', $new_password_hash);
      $update_stmt->bindParam(':user_id', $user['id']);
      $update_stmt->execute();

      // Log successful reset
      $this->logAttempt($email, 'password_reset_verify', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', true);

      return [
        'success' => true,
        'message' => 'Password reset successfully. You can now login with your new password.',
        'user_id' => $user['id'],
        'redirect' => '/library_betonio/login.html'
      ];
    } catch (PDOException $e) {
      error_log("Password reset error: " . $e->getMessage());
      return ['success' => false, 'error' => 'Password reset failed'];
    }
  }

  /**
   * Check password strength
   * Requires: uppercase, lowercase, number, special character
   */
  private function isPasswordStrong($password)
  {
    $has_uppercase = preg_match('/[A-Z]/', $password);
    $has_lowercase = preg_match('/[a-z]/', $password);
    $has_number = preg_match('/[0-9]/', $password);
    $has_special = preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password);

    return $has_uppercase && $has_lowercase && $has_number && $has_special;
  }

  /**
   * Verify reset token is valid
   */
  public function verifyResetToken($email, $reset_token)
  {
    try {
      $query = "SELECT id, reset_token, reset_token_expires 
                      FROM " . $this->table . " 
                      WHERE email = :email AND reset_token IS NOT NULL";

      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':email', $email);
      $stmt->execute();

      if ($stmt->rowCount() === 0) {
        return ['success' => false, 'error' => 'Invalid reset token'];
      }

      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      // Check if expired
      if (strtotime($user['reset_token_expires']) < time()) {
        return ['success' => false, 'error' => 'Reset token has expired'];
      }

      // Verify token
      if (!password_verify($reset_token, $user['reset_token'])) {
        return ['success' => false, 'error' => 'Invalid reset token'];
      }

      return [
        'success' => true,
        'message' => 'Reset token is valid',
        'user_id' => $user['id'],
        'email' => $email
      ];
    } catch (PDOException $e) {
      error_log("Token verification error: " . $e->getMessage());
      return ['success' => false, 'error' => 'Token verification failed'];
    }
  }

  /**
   * Check rate limiting
   */
  private function checkRateLimit($email, $attempt_type, $ip_address, $limit)
  {
    try {
      $query = "SELECT COUNT(*) as count FROM " . $this->attempts_table . " 
                      WHERE (email = :email OR ip_address = :ip_address)
                      AND attempt_type = :attempt_type
                      AND attempted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";

      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':email', $email);
      $stmt->bindParam(':ip_address', $ip_address);
      $stmt->bindParam(':attempt_type', $attempt_type);
      $stmt->execute();

      $result = $stmt->fetch(PDO::FETCH_ASSOC);

      return $result['count'] < $limit;
    } catch (PDOException $e) {
      error_log("Rate limit check error: " . $e->getMessage());
      return true;
    }
  }

  /**
   * Log attempt
   */
  private function logAttempt($email, $attempt_type, $ip_address, $is_successful)
  {
    try {
      $query = "INSERT INTO " . $this->attempts_table . " 
                      (email, attempt_type, ip_address, is_successful)
                      VALUES (:email, :attempt_type, :ip_address, :is_successful)";

      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':email', $email);
      $stmt->bindParam(':attempt_type', $attempt_type);
      $stmt->bindParam(':ip_address', $ip_address);
      $stmt->bindParam(':is_successful', $is_successful, PDO::PARAM_BOOL);

      $stmt->execute();
    } catch (PDOException $e) {
      error_log("Log attempt error: " . $e->getMessage());
    }
  }
}
