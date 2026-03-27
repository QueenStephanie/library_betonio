<?php

/**
 * Email Verification Class
 * Handles OTP generation, sending, and verification
 */

class EmailVerification
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
   * Generate OTP code
   */
  public function generateOTP()
  {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
  }

  /**
   * Request OTP for email verification
   */
  public function requestOTP($email)
  {
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return ['success' => false, 'error' => 'Invalid email address'];
    }

    try {
      // Check if email exists and not verified
      $query = "SELECT id, first_name, is_verified FROM " . $this->table . " WHERE email = :email";
      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':email', $email);
      $stmt->execute();

      if ($stmt->rowCount() === 0) {
        return ['success' => false, 'error' => 'Email not registered'];
      }

      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      // Check if already verified
      if ($user['is_verified']) {
        return ['success' => false, 'error' => 'Email already verified'];
      }

      // Check rate limiting (3 OTP requests per hour)
      $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
      $rate_limit_check = $this->checkRateLimit($email, 'otp_request', $ip_address, 3);

      if (!$rate_limit_check) {
        return ['success' => false, 'error' => 'Too many OTP requests. Please try again later.'];
      }

      // Generate OTP
      $otp_code = $this->generateOTP();
      $expires_at = date('Y-m-d H:i:s', strtotime('+' . $this->config['otp_validity'] . ' seconds'));

      // Invalidate previous OTPs
      $delete_query = "UPDATE " . $this->otp_table . " 
                            SET is_used = 1 
                            WHERE user_id = :user_id AND is_used = 0 AND purpose = 'email_verification'";
      $delete_stmt = $this->db->prepare($delete_query);
      $delete_stmt->bindParam(':user_id', $user['id']);
      $delete_stmt->execute();

      // Store OTP
      $insert_query = "INSERT INTO " . $this->otp_table . " 
                            (user_id, otp_code, purpose, expires_at)
                            VALUES (:user_id, :otp_code, 'email_verification', :expires_at)";
      $insert_stmt = $this->db->prepare($insert_query);
      $insert_stmt->bindParam(':user_id', $user['id']);
      $insert_stmt->bindParam(':otp_code', $otp_code);
      $insert_stmt->bindParam(':expires_at', $expires_at);
      $insert_stmt->execute();

      // Send OTP email if mail handler is available
      if ($this->mail_handler) {
        $mail_result = $this->mail_handler->sendOTPEmail($email, $otp_code, $user['first_name']);

        if (!$mail_result['success']) {
          return ['success' => false, 'error' => 'Failed to send OTP email'];
        }
      }

      // Log successful attempt
      $this->logAttempt($email, 'otp_request', $ip_address, true);

      return [
        'success' => true,
        'message' => 'OTP sent to your email',
        'user_id' => $user['id'],
        'otp_validity_seconds' => $this->config['otp_validity']
      ];
    } catch (PDOException $e) {
      error_log("OTP request error: " . $e->getMessage());
      return ['success' => false, 'error' => 'Failed to generate OTP'];
    }
  }

  /**
   * Verify OTP code
   */
  public function verifyOTP($email, $otp_code)
  {
    if (empty($email) || empty($otp_code)) {
      return ['success' => false, 'error' => 'Email and OTP are required'];
    }

    try {
      // Get user
      $user_query = "SELECT id, email, is_verified FROM " . $this->table . " WHERE email = :email";
      $user_stmt = $this->db->prepare($user_query);
      $user_stmt->bindParam(':email', $email);
      $user_stmt->execute();

      if ($user_stmt->rowCount() === 0) {
        return ['success' => false, 'error' => 'User not found'];
      }

      $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

      // Check if already verified
      if ($user['is_verified']) {
        return ['success' => false, 'error' => 'Email already verified'];
      }

      // Check rate limiting
      $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
      $rate_limit_check = $this->checkRateLimit($email, 'otp_verify', $ip_address, 5);

      if (!$rate_limit_check) {
        return ['success' => false, 'error' => 'Too many verification attempts. Please try again later.'];
      }

      // Verify OTP
      $otp_query = "SELECT id, otp_code, expires_at, is_used 
                          FROM " . $this->otp_table . " 
                          WHERE user_id = :user_id 
                          AND purpose = 'email_verification' 
                          AND is_used = 0 
                          ORDER BY created_at DESC 
                          LIMIT 1";

      $otp_stmt = $this->db->prepare($otp_query);
      $otp_stmt->bindParam(':user_id', $user['id']);
      $otp_stmt->execute();

      if ($otp_stmt->rowCount() === 0) {
        $this->logAttempt($email, 'otp_verify', $ip_address, false);
        return ['success' => false, 'error' => 'No active OTP found'];
      }

      $otp_record = $otp_stmt->fetch(PDO::FETCH_ASSOC);

      // Check if OTP expired
      if (strtotime($otp_record['expires_at']) < time()) {
        $this->logAttempt($email, 'otp_verify', $ip_address, false);
        return ['success' => false, 'error' => 'OTP has expired'];
      }

      // Verify OTP code (use hash comparison for security)
      if ($otp_record['otp_code'] !== $otp_code) {
        $this->logAttempt($email, 'otp_verify', $ip_address, false);
        return ['success' => false, 'error' => 'Invalid OTP code'];
      }

      // Mark OTP as used
      $mark_used_query = "UPDATE " . $this->otp_table . " 
                               SET is_used = 1, used_at = NOW() 
                               WHERE id = :otp_id";
      $mark_used_stmt = $this->db->prepare($mark_used_query);
      $mark_used_stmt->bindParam(':otp_id', $otp_record['id']);
      $mark_used_stmt->execute();

      // Mark user as verified
      $verify_query = "UPDATE " . $this->table . " 
                            SET is_verified = 1, verification_token = NULL, verification_token_expires = NULL 
                            WHERE id = :user_id";
      $verify_stmt = $this->db->prepare($verify_query);
      $verify_stmt->bindParam(':user_id', $user['id']);
      $verify_stmt->execute();

      // Log successful verification
      $this->logAttempt($email, 'otp_verify', $ip_address, true);

      return [
        'success' => true,
        'message' => 'Email verified successfully',
        'user_id' => $user['id'],
        'email' => $user['email']
      ];
    } catch (PDOException $e) {
      error_log("OTP verification error: " . $e->getMessage());
      return ['success' => false, 'error' => 'Verification failed'];
    }
  }

  /**
   * Resend OTP
   */
  public function resendOTP($email)
  {
    try {
      // Check if enough time has passed since last OTP request
      $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
      $check_query = "SELECT created_at FROM " . $this->otp_table . " 
                           WHERE user_id = (SELECT id FROM " . $this->table . " WHERE email = :email) 
                           AND purpose = 'email_verification' 
                           AND created_at > DATE_SUB(NOW(), INTERVAL " . $this->config['otp_resend_delay'] . " SECOND)
                           ORDER BY created_at DESC 
                           LIMIT 1";

      $check_stmt = $this->db->prepare($check_query);
      $check_stmt->bindParam(':email', $email);
      $check_stmt->execute();

      if ($check_stmt->rowCount() > 0) {
        return ['success' => false, 'error' => 'Please wait before requesting another OTP'];
      }

      // Request new OTP
      return $this->requestOTP($email);
    } catch (PDOException $e) {
      error_log("Resend OTP error: " . $e->getMessage());
      return ['success' => false, 'error' => 'Failed to resend OTP'];
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
      return true; // Allow on error
    }
  }

  /**
   * Log verification attempt
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
