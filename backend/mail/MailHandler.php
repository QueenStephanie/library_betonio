<?php

/**
 * Mail Handler using PHPMailer
 * Safe error handling and template-based email sending
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailHandler
{
    private $mail;
    private $config;
    private $db;

    private function errorResponse($fallbackMessage, Exception $e)
    {
        $message = $fallbackMessage;
        if (defined('APP_DEBUG') && APP_DEBUG) {
            $message .= ': ' . $e->getMessage();
        }

        return ['success' => false, 'error' => $message];
    }

    public function __construct($database = null)
    {
        $this->config = require __DIR__ . '/../config/email.config.php';
        $this->db = $database;
        $this->initializePHPMailer();
    }

    private function buildAppUrl($path, array $query = [])
    {
        $appUrl = class_exists('AppBootstrap')
            ? AppBootstrap::env('APP_URL')
            : getenv('APP_URL');
        if (!$appUrl && defined('APP_URL')) {
            $appUrl = APP_URL;
        }

        $basePath = class_exists('AppBootstrap')
            ? AppBootstrap::env('APP_BASE_PATH')
            : getenv('APP_BASE_PATH');
        if (!$basePath && defined('APP_BASE_PATH')) {
            $basePath = APP_BASE_PATH;
        }

        $base = rtrim($appUrl ?: 'http://localhost', '/');

        if (!empty($basePath)) {
            $base = $base . $basePath;
        }

        $url = $base . '/' . ltrim($path, '/');

        if (!empty($query)) {
            $queryString = http_build_query($query);
            if ($queryString !== '') {
                $url .= '?' . $queryString;
            }
        }

        return $url;
    }

    /**
     * Initialize PHPMailer with configured settings
     */
    private function initializePHPMailer()
    {
        try {
            if (!empty($this->config['smtp']['auth']) && (empty($this->config['smtp']['username']) || empty($this->config['smtp']['password']))) {
                throw new Exception('SMTP credentials are missing. Set MAIL_USER and MAIL_PASS in .env.');
            }

            $this->mail = new PHPMailer(true);
            $this->mail->CharSet = 'UTF-8';

            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host = $this->config['smtp']['host'];
            $this->mail->SMTPAuth = !empty($this->config['smtp']['auth']);
            $this->mail->Username = $this->config['smtp']['username'];
            $this->mail->Password = $this->config['smtp']['password'];
            $encryption = strtolower((string)($this->config['smtp']['encryption'] ?? 'tls'));
            if ($encryption === 'ssl') {
                $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $this->mail->SMTPSecure = '';
                $this->mail->SMTPAutoTLS = false;
            }
            $this->mail->Port = $this->config['smtp']['port'];

            // SSL verification
            $this->mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => $this->config['enable_ssl_verification'],
                    'verify_peer_name' => $this->config['enable_ssl_verification'],
                    'allow_self_signed' => false
                ]
            ];

            // Default sender
            $this->mail->setFrom($this->config['smtp']['from_email'], $this->config['smtp']['from_name']);
        } catch (Exception $e) {
            error_log("PHPMailer initialization error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send email verification link (token-based)
     */
    public function sendVerificationEmail($email, $user_name, $verification_token = '')
    {
        try {
            $this->mail->addAddress($email);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'QueenLib - Verify Your Email';

            // Create verification link with token
            $query = ['email' => $email];
            if (!empty($verification_token)) {
                $query['token'] = $verification_token;
            }
            $verification_link = $this->buildAppUrl('verify-otp.php', $query);

            // HTML Email Body
            $body = $this->getVerificationEmailTemplate($user_name, $verification_link);

            $this->mail->Body = $body;
            $this->mail->AltBody = "Click here to verify your email: $verification_link";

            $this->mail->send();

            return ['success' => true, 'message' => 'Verification email sent successfully'];
        } catch (Exception $e) {
            error_log("Error sending verification email: " . $e->getMessage());
            return $this->errorResponse('Failed to send verification email', $e);
        } finally {
            $this->mail->clearAllRecipients();
            $this->mail->clearAttachments();
        }
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($email, $reset_token, $user_name)
    {
        try {
            $this->mail->addAddress($email);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Password Reset Request - Library Betonio';

            // Generate reset link WITH email parameter
            $reset_link = $this->buildAppUrl('reset-password.php', [
                'email' => $email,
                'token' => $reset_token
            ]);

            $body = $this->getPasswordResetEmailTemplate($user_name, $reset_link);

            $this->mail->Body = $body;
            $this->mail->AltBody = "Click the link to reset your password: $reset_link";

            $this->mail->send();

            return ['success' => true, 'message' => 'Password reset email sent successfully'];
        } catch (Exception $e) {
            error_log("Error sending password reset email: " . $e->getMessage());
            return $this->errorResponse('Failed to send password reset email', $e);
        } finally {
            $this->mail->clearAllRecipients();
            $this->mail->clearAttachments();
        }
    }

    /**
     * Send password reset email using a pre-built reset link.
     */
    public function sendPasswordResetEmailByLink($email, $reset_link, $user_name)
    {
        try {
            $this->mail->addAddress($email);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Password Reset Request - Library Betonio';

            $body = $this->getPasswordResetEmailTemplate($user_name, $reset_link);

            $this->mail->Body = $body;
            $this->mail->AltBody = "Click the link to reset your password: $reset_link";

            $this->mail->send();

            return ['success' => true, 'message' => 'Password reset email sent successfully'];
        } catch (Exception $e) {
            error_log("Error sending password reset email by link: " . $e->getMessage());
            return $this->errorResponse('Failed to send password reset email', $e);
        } finally {
            $this->mail->clearAllRecipients();
            $this->mail->clearAttachments();
        }
    }

    /**
     * Verification Email HTML Template (Link-based)
     */
    private function getVerificationEmailTemplate($name, $verification_link)
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; background-color: #f9f9f9; padding: 20px; border-radius: 8px; }
                .header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background-color: white; padding: 30px; }
                .button { display: inline-block; padding: 14px 32px; background-color: #3498db; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 20px 0; }
                .button:hover { background-color: #2980b9; }
                .footer { text-align: center; color: #777; font-size: 12px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Library Betonio</h1>
                    <p>Email Verification</p>
                </div>
                <div class="content">
                    <h2>Hello $name,</h2>
                    <p>Thank you for registering with QueenLib! Click the button below to verify your email address and activate your account.</p>
                    
                    <center>
                        <a href="$verification_link" class="button">Verify Email</a>
                    </center>
                    
                    <p style="color: #e74c3c; font-weight: bold;">This link expires in 24 hours.</p>
                    
                    <p style="color: #7f8c8d; font-size: 13px;">If you didn't create this account or didn't request to verify this email, please ignore this message.</p>
                </div>
                <div class="footer">
                    <p>&copy; 2026 Library Betonio. All rights reserved.</p>
                    <p>This is an automated email. Please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Password Reset Email HTML Template
     */
    private function getPasswordResetEmailTemplate($name, $reset_link)
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; background-color: #f9f9f9; padding: 20px; border-radius: 8px; }
                .header { background-color: #e74c3c; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background-color: white; padding: 30px; }
                .button { display: inline-block; padding: 12px 30px; background-color: #e74c3c; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; color: #777; font-size: 12px; margin-top: 20px; }
                .warning { background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Library Betonio</h1>
                    <p>Password Reset Request</p>
                </div>
                <div class="content">
                    <h2>Hello $name,</h2>
                    <p>We received a request to reset your password. Click the button below to proceed:</p>
                    
                    <center>
                        <a href="$reset_link" class="button">Reset Your Password</a>
                    </center>
                    
                    <div class="warning">
                        <strong>⚠️ Security Notice:</strong> This link will expire in 10 minutes. If you didn't request a password reset, please ignore this email and your account will remain secure.
                    </div>
                    
                    <p>If the button doesn't work, copy and paste this link in your browser:</p>
                    <p style="word-break: break-all; color: #3498db; font-family: monospace; font-size: 12px;">
                        $reset_link
                    </p>
                </div>
                <div class="footer">
                    <p>&copy; 2026 Library Betonio. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Send test email
     */
    public function sendTestEmail($email, $name)
    {
        try {
            $this->mail->addAddress($email);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Test Email - PHPMailer Configuration Verification';

            $body = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; background-color: #f9f9f9; padding: 20px; border-radius: 8px; }
                .header { background-color: #27ae60; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background-color: white; padding: 30px; }
                .footer { text-align: center; color: #777; font-size: 12px; margin-top: 20px; }
                .details { background-color: #ecf0f1; padding: 15px; border-radius: 5px; margin: 20px 0; font-family: monospace; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>✓ PHPMailer Test</h1>
                    <p>Configuration Verification</p>
                </div>
                <div class="content">
                    <h2>Hello $name,</h2>
                    <p>Great news! Your PHPMailer configuration is working correctly.</p>
                    
                    <p>This test email confirms that:</p>
                    <ul>
                        <li>SMTP connection is successful</li>
                        <li>Authentication is working</li>
                        <li>Email delivery is operational</li>
                    </ul>
                    
                    <div class="details">
                        <strong>System Information:</strong><br>
                        Server Time: {$this->getCurrentTimestamp()}<br>
                        Sent From: {$this->config['smtp']['from_email']}<br>
                        SMTP Host: {$this->config['smtp']['host']}:{$this->config['smtp']['port']}
                    </div>
                    
                    <p>Your Library Betonio system is ready to send registration confirmations, password resets, and other notifications.</p>
                </div>
                <div class="footer">
                    <p>&copy; 2026 Library Betonio. All rights reserved.</p>
                    <p>This is an automated test email. No action is required.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;

            $this->mail->Body = $body;
            $this->mail->AltBody = "PHPMailer test email sent successfully.";

            $result = $this->mail->send();

            // Clear recipients for next email
            $this->mail->clearAddresses();

            return ['success' => true, 'message' => 'Test email sent successfully'];
        } catch (Exception $e) {
            error_log("Error sending test email: " . $e->getMessage());
            return $this->errorResponse('Failed to send test email', $e);
        }
    }

    /**
     * Get current timestamp
     */
    private function getCurrentTimestamp()
    {
        return date('Y-m-d H:i:s') . ' (UTC+0)';
    }
}
