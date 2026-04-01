<?php

/**
 * User Authentication Class
 * Handles login, registration, OTP generation, password reset
 */

require_once __DIR__ . '/AuthSupport.php';
require_once __DIR__ . '/UserRepository.php';

class Auth
{
  private $db;
  private $table = 'users';
  private $otp_table = 'otp_codes';
  private $attempts_table = 'verification_attempts';
  private $login_history_table = 'login_history';

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
      return ['success' => false, 'error' => 'Password must be at least 8 characters long'];
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return ['success' => false, 'error' => 'Invalid email address'];
    }

    try {
      // Check if user already exists
      $existing_user = UserRepository::findByEmail($this->db, $this->table, $email, ['id']);
      if ($existing_user) {
        return ['success' => false, 'error' => 'Email already registered'];
      }

      // Hash password with bcrypt
      $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

      // Generate verification token
      $verification_token = bin2hex(random_bytes(32));

      // Insert user
      $query = "INSERT INTO " . $this->table . " 
                      (first_name, last_name, email, password_hash, verification_token, verification_token_expires)
                      VALUES (:first_name, :last_name, :email, :password_hash, :verification_token, DATE_ADD(NOW(), INTERVAL 24 HOUR))";

      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':first_name', $first_name);
      $stmt->bindParam(':last_name', $last_name);
      $stmt->bindParam(':email', $email);
      $stmt->bindParam(':password_hash', $password_hash);
      $stmt->bindParam(':verification_token', $verification_token);

      if ($stmt->execute()) {
        $user_id = $this->db->lastInsertId();

        // Log registration attempt
        $this->logAttempt($email, 'registration', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', true);

        return [
          'success' => true,
          'message' => 'Registration successful. Please verify your email.',
          'user_id' => $user_id,
          'email' => $email
        ];
      }
    } catch (PDOException $e) {
      error_log("Registration error: " . $e->getMessage());
      return ['success' => false, 'error' => 'Registration failed'];
    }

    return ['success' => false, 'error' => 'Registration failed'];
  }

  /**
   * User login with credentials
   */
  public function login($email, $password)
  {
    if (empty($email) || empty($password)) {
      return ['success' => false, 'error' => 'Email and password are required'];
    }

    try {
      $user = UserRepository::findByEmail(
        $this->db,
        $this->table,
        $email,
        ['id', 'first_name', 'last_name', 'email', 'password_hash', 'is_verified', 'is_active'],
        true
      );

      if ($user) {

        // Verify password
        if (password_verify($password, $user['password_hash'])) {

          // Check if email is verified
          if (!$user['is_verified']) {
            $this->logAttempt($email, 'login_attempt', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', false);
            return ['success' => false, 'error' => 'Please verify your email first', 'requires_verification' => true];
          }

          // Update last login time
          $update_query = "UPDATE " . $this->table . " SET last_login = NOW() WHERE id = :id";
          $update_stmt = $this->db->prepare($update_query);
          $update_stmt->bindParam(':id', $user['id']);
          $update_stmt->execute();

          // Log successful login
          $this->logLoginHistory($user['id'], true);
          $this->logAttempt($email, 'login_attempt', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', true);

          // Create session
          AuthSupport::setBackendSession($user);

          // Generate auth token (JWT alternative)
          $auth_token = $this->generateAuthToken($user['id'], $user['email']);

          return [
            'success' => true,
            'message' => 'Login successful',
            'user' => [
              'id' => $user['id'],
              'first_name' => $user['first_name'],
              'last_name' => $user['last_name'],
              'email' => $user['email']
            ],
            'auth_token' => $auth_token
          ];
        } else {
          $this->logAttempt($email, 'login_attempt', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', false);
          return ['success' => false, 'error' => 'Invalid credentials'];
        }
      } else {
        $this->logAttempt($email, 'login_attempt', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', false);
        return ['success' => false, 'error' => 'User not found'];
      }
    } catch (PDOException $e) {
      error_log("Login error: " . $e->getMessage());
      return ['success' => false, 'error' => 'Login failed'];
    }
  }

  /**
   * User logout
   */
  public function logout()
  {
    try {
      AuthSupport::ensureSessionStarted();

      if (isset($_SESSION['user_id'])) {
        // Log logout time
        $query = "UPDATE " . $this->login_history_table . " 
                          SET logout_time = NOW() 
                          WHERE user_id = :user_id AND logout_time IS NULL 
                          ORDER BY login_time DESC LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
      }

      // Destroy session
      AuthSupport::clearSession();

      return ['success' => true, 'message' => 'Logout successful'];
    } catch (Exception $e) {
      error_log("Logout error: " . $e->getMessage());
      return ['success' => false, 'error' => 'Logout failed'];
    }
  }

  /**
   * Check if user is authenticated
   */
  public function isAuthenticated()
  {
    AuthSupport::ensureSessionStarted();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
  }

  /**
   * Get current user
   */
  public function getCurrentUser()
  {
    if (!$this->isAuthenticated()) {
      return null;
    }

    AuthSupport::ensureSessionStarted();
    try {
      return UserRepository::findById(
        $this->db,
        $this->table,
        $_SESSION['user_id'],
        ['id', 'first_name', 'last_name', 'email', 'is_verified', 'created_at']
      );
    } catch (PDOException $e) {
      error_log("Get user error: " . $e->getMessage());
    }

    return null;
  }

  /**
   * Generate secure authentication token
   */
  private function generateAuthToken($user_id, $email)
  {
    $token_data = [
      'user_id' => $user_id,
      'email' => $email,
      'issued_at' => time(),
      'expires_at' => time() + (7 * 24 * 60 * 60) // 7 days
    ];

    return bin2hex(random_bytes(32)); // Simple token for now
  }

  /**
   * Log authentication attempt
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

  /**
   * Log login history
   */
  private function logLoginHistory($user_id, $is_successful)
  {
    try {
      $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
      $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

      $query = "INSERT INTO " . $this->login_history_table . " 
                      (user_id, ip_address, user_agent, is_successful)
                      VALUES (:user_id, :ip_address, :user_agent, :is_successful)";

      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':user_id', $user_id);
      $stmt->bindParam(':ip_address', $ip_address);
      $stmt->bindParam(':user_agent', $user_agent);
      $stmt->bindParam(':is_successful', $is_successful, PDO::PARAM_BOOL);

      $stmt->execute();
    } catch (PDOException $e) {
      error_log("Log history error: " . $e->getMessage());
    }
  }

}
