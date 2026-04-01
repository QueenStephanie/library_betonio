<?php

/**
 * QueenLib Configuration
 * ====================
 * 
 * This file loads configuration from environment variables.
 * For deployment, set environment variables on your hosting platform.
 * See .env.example and .env.production.example for all required variables.
 * 
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', realpath(__DIR__ . '/..'));
}

require_once APP_ROOT . '/backend/config/AppBootstrap.php';

// ============================================
// LOAD ENVIRONMENT VARIABLES
// ============================================

AppBootstrap::loadEnvironment();

// ============================================
// APPLICATION CONFIGURATION
// ============================================

$appUrl = getenv('APP_URL') ?: 'http://localhost';
$basePath = getenv('APP_BASE_PATH');
$basePath = $basePath !== false ? trim($basePath) : '';

if ($basePath === '' && isset($_SERVER['SCRIPT_NAME'])) {
    $detectedBasePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    if ($detectedBasePath === '/' || $detectedBasePath === '.') {
        $detectedBasePath = '';
    }
    $basePath = rtrim($detectedBasePath, '/');
}
$appName = getenv('APP_NAME') ?: 'QueenLib';
$appEnv = getenv('APP_ENV') ?: 'development';
$appDebug = getenv('APP_DEBUG') === 'true' || getenv('APP_ENV') === 'development';
$timezone = getenv('APP_TIMEZONE') ?: 'UTC';

// ============================================
// DATABASE CONFIGURATION
// ============================================

$dbConfig = AppBootstrap::getDatabaseConfig();
$dbHost = $dbConfig['host'];
$dbPort = $dbConfig['port'];
$dbName = $dbConfig['name'];
$dbUser = $dbConfig['user'];
$dbPass = $dbConfig['password'];
$dbCharset = $dbConfig['charset'];

// ============================================
// EMAIL / SMTP CONFIGURATION
// ============================================

$mailHost = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
$mailPort = (int)(getenv('MAIL_PORT') ?: 587);
$mailUser = getenv('MAIL_USER') ?: '';
$mailPass = getenv('MAIL_PASS') ?: '';
$mailFrom = getenv('MAIL_FROM') ?: '';
$mailFromName = getenv('MAIL_FROM_NAME') ?: 'QueenLib';

// ============================================
// ADMIN CONFIGURATION
// ============================================

$adminUsername = getenv('ADMIN_USERNAME') ?: 'admin';
$superadminUsername = getenv('SUPERADMIN_USERNAME');
$superadminUsername = $superadminUsername !== false ? trim((string)$superadminUsername) : '';
if ($superadminUsername === '') {
    $superadminUsername = trim((string)$adminUsername);
}
$adminPassword = getenv('ADMIN_PASSWORD');
if ($adminPassword === false || $adminPassword === '') {
    $adminPassword = $appEnv === 'development' ? 'admin123' : '';
}

$adminBootstrapUsernameConfigured = trim((string)$adminUsername) !== '';
$adminBootstrapPasswordConfigured = trim((string)$adminPassword) !== '';
$adminBootstrapConfigured = $adminBootstrapUsernameConfigured && $adminBootstrapPasswordConfigured;
$adminBootstrapLooksDefault = in_array((string)$adminPassword, ['admin123', 'password', 'changeme'], true);
$adminBootstrapUnsafe = $appEnv !== 'development' && (!$adminBootstrapConfigured || $adminBootstrapLooksDefault);
$adminBootstrapAllowed = $adminBootstrapConfigured && !$adminBootstrapUnsafe;

// ============================================
// SECURITY CONFIGURATION
// ============================================

$sessionTimeout = (int)(getenv('SESSION_TIMEOUT') ?: 3600);
$bcryptCost = (int)(getenv('BCRYPT_COST') ?: 12);
$otpExpiry = (int)(getenv('OTP_EXPIRY') ?: 600);

// ============================================
// SET TIMEZONE
// ============================================

date_default_timezone_set($timezone);

// ============================================
// ERROR REPORTING
// ============================================

ini_set('display_errors', $appDebug ? '1' : '0');
error_reporting(E_ALL);

// ============================================
// SESSION CONFIGURATION
// ============================================

$isHttps = false;
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    $isHttps = true;
}
if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
    $isHttps = true;
}
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $isHttps = true;
}

$httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$hostOnly = strtolower((string) preg_replace('/:\\d+$/', '', $httpHost));
$isLocalHost = in_array($hostOnly, ['localhost', '127.0.0.1', '::1', '[::1]'], true);

// In local development, normalize accidental HTTPS URLs back to HTTP.
if (PHP_SAPI !== 'cli' && $isLocalHost && $isHttps) {
    $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    header('Location: http://localhost' . $requestUri, true, 302);
    exit();
}

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', $isHttps ? '1' : '0');
ini_set('session.cookie_samesite', 'Strict');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// BUILD APP URL
// ============================================

$protocol = $isHttps ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;

if ($basePath) {
    $fullAppUrl = rtrim($appUrl, '/') . $basePath;
} else {
    $fullAppUrl = rtrim($appUrl, '/');
}

// ============================================
// DEFINE CONSTANTS
// ============================================

define('DB_HOST', $dbHost);
define('DB_PORT', $dbPort);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASSWORD', $dbPass);
define('DB_CHARSET', $dbCharset);

define('APP_NAME', $appName);
define('APP_URL', $fullAppUrl);
define('APP_ENV', $appEnv);
define('APP_BASE_PATH', $basePath);
define('APP_DEBUG', $appDebug);

define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_HASH_OPTIONS', ['cost' => $bcryptCost]);
define('SESSION_TIMEOUT', $sessionTimeout);
define('OTP_EXPIRY', $otpExpiry);
define('OTP_LENGTH', 6);

define('ADMIN_USERNAME', $adminUsername);
define('SUPERADMIN_USERNAME', $superadminUsername);
define('ADMIN_PASSWORD', $adminPassword);
define('ADMIN_BOOTSTRAP_USERNAME_CONFIGURED', $adminBootstrapUsernameConfigured);
define('ADMIN_BOOTSTRAP_PASSWORD_CONFIGURED', $adminBootstrapPasswordConfigured);
define('ADMIN_BOOTSTRAP_CONFIGURED', $adminBootstrapConfigured);
define('ADMIN_BOOTSTRAP_UNSAFE', $adminBootstrapUnsafe);
define('ADMIN_BOOTSTRAP_ALLOWED', $adminBootstrapAllowed);

define('MAIL_HOST', $mailHost);
define('MAIL_PORT', $mailPort);
define('MAIL_USERNAME', $mailUser);
define('MAIL_PASSWORD', $mailPass);
define('MAIL_FROM', $mailFrom);
define('MAIL_FROM_NAME', $mailFromName);

// ============================================
// DATABASE CONNECTION CLASS
// ============================================

class Database
{
    private $conn;

    public function connect()
    {
        $portsToTry = [DB_PORT];
        $dbPortFromEnv = getenv('DB_PORT');
        $hostIsLocal = in_array(strtolower(DB_HOST), ['localhost', '127.0.0.1', '::1'], true);

        if ($dbPortFromEnv === false && $hostIsLocal) {
            if (!in_array(3306, $portsToTry, true)) {
                $portsToTry[] = 3306;
            }
            if (!in_array(3307, $portsToTry, true)) {
                $portsToTry[] = 3307;
            }
        }

        $lastError = null;
        foreach ($portsToTry as $port) {
            try {
                $dsn = 'mysql:host=' . DB_HOST . ';port=' . (int)$port . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
                $this->conn = new PDO($dsn, DB_USER, DB_PASSWORD);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return $this->conn;
            } catch (PDOException $e) {
                $lastError = $e;
            }
        }

        error_log('Database connection error: ' . ($lastError ? $lastError->getMessage() : 'unknown'));
        if (APP_DEBUG && $lastError) {
            die('Database connection failed: ' . $lastError->getMessage());
        }

        die('Database connection failed');
    }

    public function getConnection()
    {
        return $this->connect();
    }
}

$database = new Database();
$db = $database->getConnection();
