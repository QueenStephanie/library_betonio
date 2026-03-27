<?php

/**
 * Authentication Functions
 * Handles user registration, login, logout, and session management
 */

class AuthManager
{
  private $db;

  public function __construct($database)
  {
    $this->db = $database;
  }

  /**
   * Register a new user
   */
  public function register($first_name, $last_name, $email, $password, $password_confirm)
  {
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
      return ['success' => false, 'error' => 'All fields are required'];
    }

    if ($password !== $password_confirm) {
      return ['success' => false, 'error' => 'Passwords do not match'];
    }

    if (strlen($password) < 8) {
      return ['success' => false, 'error' => 'Password must be at least 8 characters'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return ['success' => false, 'error' => 'Invalid email address'];
    }

    try {
      // Check if user exists
      $query = "SELECT id FROM users WHERE email = :email";
      $stmt = $this->db->prepare($query);
      $stmt->execute([':email' => $email]);

      if ($stmt->rowCount() > 0) {
        return ['success' => false, 'error' => 'Email already registered'];
      }

      // Hash password
      $password_hash = password_hash($password, PASSWORD_HASH_ALGO, PASSWORD_HASH_OPTIONS);

      // Generate verification token
      $verification_token = bin2hex(random_bytes(32));
      $token_expires = date('Y-m-d H:i:s', time() + 86400); // 24 hours

      // Insert user
      $query = "INSERT INTO users (first_name, last_name, email, password_hash, verification_token, verification_token_expires) 
                      VALUES (:first_name, :last_name, :email, :password_hash, :verification_token, :token_expires)";
      $stmt = $this->db->prepare($query);
      $stmt->execute([
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':email' => $email,
        ':password_hash' => $password_hash,
        ':verification_token' => $verification_token,
        ':token_expires' => $token_expires
      ]);

      $user_id = $this->db->lastInsertId();

      // Generate OTP
      $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

      // Store OTP - use MySQL DATE_ADD to avoid timezone issues
      $query = "INSERT INTO otp_codes (user_id, otp_code, purpose, expires_at) 
                      VALUES (:user_id, :otp, 'email_verification', DATE_ADD(NOW(), INTERVAL 10 MINUTE))";
      $stmt = $this->db->prepare($query);
      $stmt->execute([
        ':user_id' => $user_id,
        ':otp' => $otp
      ]);

      return [
        'success' => true,
        'message' => 'Registration successful. Check your email for verification code.',
        'user_id' => $user_id,
        'email' => $email,
        'verification_token' => $verification_token,
        'otp_sent' => true
      ];
    } catch (Exception $e) {
      return ['success' => false, 'error' => 'Registration failed: ' . $e->getMessage()];
    }
  }

  /**
   * Login user
   */
  public function login($email, $password)
  {
    if (empty($email) || empty($password)) {
      return ['success' => false, 'error' => 'Email and password required'];
    }

    try {
      // Find user
      $query = "SELECT id, first_name, last_name, email, password_hash, is_verified FROM users WHERE email = :email";
      $stmt = $this->db->prepare($query);
      $stmt->execute([':email' => $email]);

      if ($stmt->rowCount() === 0) {
        return ['success' => false, 'error' => 'Invalid email or password'];
      }

      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      // Verify password
      if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Invalid email or password'];
      }

      // Check if email is verified
      if (!$user['is_verified']) {
        return ['success' => false, 'error' => 'Please verify your email first', 'unverified' => true, 'email' => $email];
      }

      // Set session
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
      $_SESSION['user_email'] = $user['email'];
      $_SESSION['login_time'] = time();

      return [
        'success' => true,
        'message' => 'Login successful',
        'user' => $user
      ];
    } catch (Exception $e) {
      return ['success' => false, 'error' => 'Login failed: ' . $e->getMessage()];
    }
  }

  /**
   * Logout user
   */
  public function logout()
  {
    session_destroy();
    return ['success' => true];
  }

  /**
   * Check if user is logged in
   */
  public function isLoggedIn()
  {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
  }

  /**
   * Get current user information
   */
  public function getCurrentUser()
  {
    if (!$this->isLoggedIn()) {
      return null;
    }

    try {
      $query = "SELECT id, first_name, last_name, email, is_verified FROM users WHERE id = :id";
      $stmt = $this->db->prepare($query);
      $stmt->execute([':id' => $_SESSION['user_id']]);
      return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      return null;
    }
  }

  /**
   * Verify OTP
   */
  public function verifyOTP($email, $otp)
  {
    if (empty($email) || empty($otp)) {
      return ['success' => false, 'error' => 'OTP and email required'];
    }

    try {
      // Find user by email
      $query = "SELECT id FROM users WHERE email = :email";
      $stmt = $this->db->prepare($query);
      $stmt->execute([':email' => $email]);

      if ($stmt->rowCount() === 0) {
        return ['success' => false, 'error' => 'User not found'];
      }

      $user = $stmt->fetch(PDO::FETCH_ASSOC);
      $user_id = $user['id'];

      // Find valid OTP for this user - check for ANY issues
      $query = "SELECT id, otp_code, expires_at, is_used FROM otp_codes WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1";
      $stmt = $this->db->prepare($query);
      $stmt->execute([':user_id' => $user_id]);

      if ($stmt->rowCount() === 0) {
        return ['success' => false, 'error' => 'No verification code found. Please request a new one.'];
      }

      $otp_record = $stmt->fetch(PDO::FETCH_ASSOC);

      // Check if OTP is expired
      if (strtotime($otp_record['expires_at']) < time()) {
        return ['success' => false, 'error' => 'Verification code has expired. Please request a new one.'];
      }

      // Check if OTP is already used
      if ($otp_record['is_used']) {
        return ['success' => false, 'error' => 'Verification code has already been used. Please request a new one.'];
      }

      // Check if OTP matches
      if ($otp_record['otp_code'] !== $otp) {
        return ['success' => false, 'error' => 'Incorrect verification code. Please try again.'];
      }

      // Mark OTP as used
      $query = "UPDATE otp_codes SET is_used = 1, used_at = NOW() WHERE id = :id";
      $stmt = $this->db->prepare($query);
      $stmt->execute([':id' => $otp_record['id']]);

      // Update user as verified
      $query = "UPDATE users
                SET is_verified = 1,
                    verification_token = NULL,
                    verification_token_expires = NULL
                WHERE id = :id";
      $stmt = $this->db->prepare($query);
      $stmt->execute([':id' => $user_id]);

      // Verify the update was successful
      $verify_query = "SELECT is_verified FROM users WHERE id = :id";
      $verify_stmt = $this->db->prepare($verify_query);
      $verify_stmt->execute([':id' => $user_id]);
      $verify_check = $verify_stmt->fetch(PDO::FETCH_ASSOC);

      if (!$verify_check['is_verified']) {
        return ['success' => false, 'error' => 'Verification update failed. Please try again.'];
      }

      return ['success' => true, 'message' => 'Email verified successfully! You can now log in.'];
    } catch (Exception $e) {
      return ['success' => false, 'error' => 'Verification failed: ' . $e->getMessage()];
    }
  }

  /**
   * Verify email directly from the emailed verification link
   */
  public function verifyEmailByToken($email, $token)
  {
    if (empty($email) || empty($token)) {
      return ['success' => false, 'error' => 'Verification link is incomplete'];
    }

    try {
      $query = "SELECT id, is_verified, verification_token_expires
                FROM users
                WHERE email = :email AND verification_token = :token
                LIMIT 1";
      $stmt = $this->db->prepare($query);
      $stmt->execute([
        ':email' => $email,
        ':token' => $token
      ]);

      if ($stmt->rowCount() === 0) {
        return ['success' => false, 'error' => 'Invalid or expired verification link'];
      }

      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($user['is_verified']) {
        return ['success' => true, 'message' => 'Email already verified'];
      }

      if (!empty($user['verification_token_expires']) && strtotime($user['verification_token_expires']) < time()) {
        return ['success' => false, 'error' => 'Verification link has expired'];
      }

      $update_query = "UPDATE users
                       SET is_verified = 1,
                           verification_token = NULL,
                           verification_token_expires = NULL
                       WHERE id = :id";
      $update_stmt = $this->db->prepare($update_query);
      $update_stmt->execute([':id' => $user['id']]);

      $otp_query = "UPDATE otp_codes
                    SET is_used = 1, used_at = NOW()
                    WHERE user_id = :user_id
                      AND purpose = 'email_verification'
                      AND is_used = 0";
      $otp_stmt = $this->db->prepare($otp_query);
      $otp_stmt->execute([':user_id' => $user['id']]);

      return ['success' => true, 'message' => 'Email verified successfully! You can now log in.'];
    } catch (Exception $e) {
      return ['success' => false, 'error' => 'Verification failed: ' . $e->getMessage()];
    }
  }

  /**
   * Request password reset
   */
  public function requestPasswordReset($email)
  {
    if (empty($email)) {
      return ['success' => false, 'error' => 'Email required'];
    }

    try {
      // Find user
      $query = "SELECT id FROM users WHERE email = :email";
      $stmt = $this->db->prepare($query);
      $stmt->execute([':email' => $email]);

      if ($stmt->rowCount() === 0) {
        return ['success' => false, 'error' => 'Email not found'];
      }

      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      // Generate reset token
      $reset_token = bin2hex(random_bytes(32));
      $token_expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

      // Store token
      $query = "UPDATE users SET reset_token = :token, reset_token_expires = :expires WHERE id = :id";
      $stmt = $this->db->prepare($query);
      $stmt->execute([
        ':token' => $reset_token,
        ':expires' => $token_expires,
        ':id' => $user['id']
      ]);

      // Store in temporary storage for email sending
      $_SESSION['password_reset_email'] = $email;
      $_SESSION['password_reset_token'] = $reset_token;

      return [
        'success' => true,
        'message' => 'Password reset instructions sent to your email',
        'reset_token' => $reset_token,
        'email' => $email
      ];
    } catch (Exception $e) {
      return ['success' => false, 'error' => 'Reset request failed: ' . $e->getMessage()];
    }
  }

  /**
   * Reset password with token
   */
  public function resetPassword($email, $reset_token, $password, $password_confirm)
  {
    if (empty($email) || empty($reset_token) || empty($password)) {
      return ['success' => false, 'error' => 'All fields required'];
    }

    if ($password !== $password_confirm) {
      return ['success' => false, 'error' => 'Passwords do not match'];
    }

    if (strlen($password) < 8) {
      return ['success' => false, 'error' => 'Password must be at least 8 characters'];
    }

    try {
      // Verify token
      $query = "SELECT id FROM users WHERE email = :email AND reset_token = :token AND reset_token_expires > NOW()";
      $stmt = $this->db->prepare($query);
      $stmt->execute([':email' => $email, ':token' => $reset_token]);

      if ($stmt->rowCount() === 0) {
        return ['success' => false, 'error' => 'Invalid or expired reset token'];
      }

      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      // Update password
      $password_hash = password_hash($password, PASSWORD_HASH_ALGO, PASSWORD_HASH_OPTIONS);

      $query = "UPDATE users SET password_hash = :password, reset_token = NULL, reset_token_expires = NULL WHERE id = :id";
      $stmt = $this->db->prepare($query);
      $stmt->execute([
        ':password' => $password_hash,
        ':id' => $user['id']
      ]);

      return ['success' => true, 'message' => 'Password reset successfully'];
    } catch (Exception $e) {
      return ['success' => false, 'error' => 'Password reset failed: ' . $e->getMessage()];
    }
  }
}
