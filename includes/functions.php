<?php

/**
 * Helper Functions
 * Utility functions for the application
 */

/**
 * Redirect to a page
 */
function redirect($path, $status = 302)
{
  header("Location: $path", true, $status);
  exit();
}

/**
 * Check if user is logged in, redirect if not
 */
function requireLogin()
{
  if (!isset($_SESSION['user_id'])) {
    redirect('/library_betonio/login.php');
  }
}

/**
 * Sanitize user input
 */
function sanitize($data)
{
  if (is_array($data)) {
    return array_map('sanitize', $data);
  }
  return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 */
function isEmailValid($email)
{
  return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate a random string
 */
function generateRandomString($length = 32)
{
  return bin2hex(random_bytes($length / 2));
}

/**
 * Set flash message
 */
function setFlash($type, $message)
{
  $_SESSION['flash'] = [
    'type' => $type, // 'success', 'error', 'warning', 'info'
    'message' => $message
  ];
}

/**
 * Get and clear flash message
 */
function getFlash()
{
  if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
  }
  return null;
}

/**
 * Display flash message as HTML
 */
function displayFlash()
{
  $flash = getFlash();
  if (!$flash) return '';

  $cssClass = 'alert-' . $flash['type'];
  return "<div class='alert {$cssClass}' role='alert'>{$flash['message']}</div>";
}

/**
 * Get form post value safely
 */
function getPost($key, $default = '')
{
  return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

/**
 * Send OTP email using PHPMailer
 */
function sendOTPEmail($email, $otp, $name = '', $verification_token = '')
{
  try {
    // Load PHPMailer
    require_once __DIR__ . '/../backend/vendor/autoload.php';

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    // Get email config
    $config = require __DIR__ . '/../backend/config/email.config.php';

    // Server settings
    $mail->isSMTP();
    $mail->Host = $config['smtp']['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp']['username'];
    $mail->Password = $config['smtp']['password'];
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $config['smtp']['port'];

    // SSL verification
    $mail->SMTPOptions = [
      'ssl' => [
        'verify_peer' => $config['enable_ssl_verification'],
        'verify_peer_name' => $config['enable_ssl_verification'],
        'allow_self_signed' => false
      ]
    ];

    // Recipients
    $mail->setFrom($config['smtp']['from_email'], $config['smtp']['from_name']);
    $mail->addAddress($email);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'QueenLib - Email Verification Code';

    // Create verification page link
    $verification_link = 'http://localhost/library_betonio/verify-otp.php?email=' . urlencode($email);
    if (!empty($verification_token)) {
      $verification_link .= '&token=' . urlencode($verification_token);
    }

    $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; background-color: #f9f9f9; padding: 20px; border-radius: 8px; }
                .header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background-color: white; padding: 30px; }
                .otp-box { background-color: #e8f4f8; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0; }
                .otp-code { font-size: 36px; font-weight: bold; color: #2c3e50; letter-spacing: 8px; }
                .button { display: inline-block; padding: 14px 32px; background-color: #3498db; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 20px 0; }
                .button:hover { background-color: #2980b9; }
                .alt-text { color: #7f8c8d; font-size: 13px; word-break: break-all; }
                .footer { text-align: center; color: #777; font-size: 12px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>QueenLib</h1>
                    <p>Email Verification</p>
                </div>
                <div class='content'>
                    <h2>Hello $name,</h2>
                    <p>Thank you for registering! Open the verification page below, then enter the 6-digit code to complete your email verification:</p>
                    
                    <center>
                        <a href='$verification_link' class='button'>Open Verification Page</a>
                    </center>
                    
                    <div class='otp-box'>
                        <p>Or enter this verification code:</p>
                        <div class='otp-code'>$otp</div>
                        <p style='color: #e74c3c; font-weight: bold;'>This code expires in 10 minutes</p>
                    </div>
                    
                    <p style='color: #7f8c8d; font-size: 13px;'>If the button above doesn't work, copy and paste this verification page link in your browser:</p>
                    <p class='alt-text'><strong>$verification_link</strong></p>
                    
                    <p>If you didn't create this account, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2026 QueenLib. All rights reserved.</p>
                    <p>This is an automated email. Please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
    ";

    $mail->Body = $body;
    $mail->AltBody = "Your OTP code is: $otp. This code expires in 10 minutes.\n\nVerify here: $verification_link";

    if ($mail->send()) {
      error_log("OTP email sent successfully to: $email");
      return true;
    }
  } catch (Exception $e) {
    error_log("Email sending error: " . $e->getMessage());
    return false;
  }
}

/**
 * Send password reset email using PHPMailer
 */
function sendPasswordResetEmail($email, $reset_link, $name = '')
{
  try {
    // Load PHPMailer
    require_once __DIR__ . '/../backend/vendor/autoload.php';

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    // Get email config
    $config = require __DIR__ . '/../backend/config/email.config.php';

    // Server settings
    $mail->isSMTP();
    $mail->Host = $config['smtp']['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp']['username'];
    $mail->Password = $config['smtp']['password'];
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $config['smtp']['port'];

    // SSL verification
    $mail->SMTPOptions = [
      'ssl' => [
        'verify_peer' => $config['enable_ssl_verification'],
        'verify_peer_name' => $config['enable_ssl_verification'],
        'allow_self_signed' => false
      ]
    ];

    // Recipients
    $mail->setFrom($config['smtp']['from_email'], $config['smtp']['from_name']);
    $mail->addAddress($email);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'QueenLib - Password Reset Request';

    $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; background-color: #f9f9f9; padding: 20px; border-radius: 8px; }
                .header { background-color: #e74c3c; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background-color: white; padding: 30px; }
                .button { display: inline-block; padding: 12px 30px; background-color: #e74c3c; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .warning { background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; }
                .footer { text-align: center; color: #777; font-size: 12px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>QueenLib</h1>
                    <p>Password Reset Request</p>
                </div>
                <div class='content'>
                    <h2>Hello $name,</h2>
                    <p>We received a request to reset your password. Click the button below to proceed:</p>
                    
                    <center>
                        <a href='$reset_link' class='button'>Reset Your Password</a>
                    </center>
                    
                    <div class='warning'>
                        <strong>⚠️ Security Notice:</strong> This link will expire in 1 hour. If you didn't request a password reset, please ignore this email and your account will remain secure.
                    </div>
                    
                    <p>If the button doesn't work, copy and paste this link in your browser:</p>
                    <p style='word-break: break-all; color: #3498db; font-family: monospace; font-size: 12px;'>
                        $reset_link
                    </p>
                </div>
                <div class='footer'>
                    <p>&copy; 2026 QueenLib. All rights reserved.</p>
                    <p>This is an automated email. Please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
    ";

    $mail->Body = $body;
    $mail->AltBody = "Click the link to reset your password: $reset_link";

    if ($mail->send()) {
      error_log("Password reset email sent successfully to: $email");
      return true;
    }
  } catch (Exception $e) {
    error_log("Email sending error: " . $e->getMessage());
    return false;
  }
}

/**
 * Log activity for auditing
 */
function logActivity($user_id, $action, $details = '')
{
  global $db;
  try {
    $query = "INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (:user_id, :action, :details, :ip)";
    $stmt = $db->prepare($query);
    $stmt->execute([
      ':user_id' => $user_id,
      ':action' => $action,
      ':details' => $details,
      ':ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
    ]);
  } catch (Exception $e) {
    error_log("Activity log error: " . $e->getMessage());
  }
}

/**
 * Check session timeout
 */
function checkSessionTimeout()
{
  if (isset($_SESSION['login_time'])) {
    $elapsed = time() - $_SESSION['login_time'];
    if ($elapsed > SESSION_TIMEOUT) {
      session_destroy();
      redirect('/library_betonio/login.php?timeout=1');
    }
  }
}
