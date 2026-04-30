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

$appUrl = AppBootstrap::env('APP_URL', 'http://localhost');
$basePath = AppBootstrap::env('APP_BASE_PATH');
$basePath = $basePath !== null ? trim((string) $basePath) : '';

$detectedBasePath = '';
if (isset($_SERVER['SCRIPT_NAME'])) {
    $detectedBasePath = str_replace('\\', '/', dirname((string)$_SERVER['SCRIPT_NAME']));
    if ($detectedBasePath === '/' || $detectedBasePath === '.') {
        $detectedBasePath = '';
    }
    $detectedBasePath = rtrim($detectedBasePath, '/');
}

if ($basePath === '') {
    $basePath = $detectedBasePath;
} elseif ($detectedBasePath !== '' && PHP_SAPI !== 'cli') {
    $requestHost = isset($_SERVER['HTTP_HOST']) ? strtolower((string)preg_replace('/:\\d+$/', '', $_SERVER['HTTP_HOST'])) : '';
    $isLocalRequest = in_array($requestHost, ['localhost', '127.0.0.1', '::1', '[::1]'], true);

    if ($isLocalRequest && $detectedBasePath !== $basePath) {
        // Prefer runtime path in local dev to avoid broken redirects when APP_BASE_PATH is stale.
        $basePath = $detectedBasePath;
    }
}
$appName = AppBootstrap::env('APP_NAME', 'QueenLib');
$appEnv = AppBootstrap::env('APP_ENV', 'development');
$appDebug = AppBootstrap::env('APP_DEBUG') === 'true' || AppBootstrap::env('APP_ENV', 'development') === 'development';
$timezone = AppBootstrap::env('APP_TIMEZONE', 'UTC');

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

$mailHost = AppBootstrap::env('MAIL_HOST', 'smtp.gmail.com');
$mailPort = (int)(AppBootstrap::env('MAIL_PORT', 587));
$mailUser = AppBootstrap::env('MAIL_USER', '');
$mailPass = AppBootstrap::env('MAIL_PASS', '');
$mailFrom = AppBootstrap::env('MAIL_FROM', '');
$mailFromName = AppBootstrap::env('MAIL_FROM_NAME', 'QueenLib');

// ============================================
// ADMIN CONFIGURATION
// ============================================

$adminUsername = AppBootstrap::env('ADMIN_USERNAME', 'admin');
$superadminUsername = AppBootstrap::env('SUPERADMIN_USERNAME');
$superadminUsername = $superadminUsername !== null ? trim((string)$superadminUsername) : '';
if ($superadminUsername === '') {
    $superadminUsername = trim((string)$adminUsername);
}
$adminPassword = AppBootstrap::env('ADMIN_PASSWORD');
if ($adminPassword === null || $adminPassword === '') {
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

$sessionTimeout = (int)(AppBootstrap::env('SESSION_TIMEOUT', 3600));
$sessionAbsoluteTimeout = (int)(AppBootstrap::env('SESSION_ABSOLUTE_TIMEOUT', 43200));
$bcryptCost = (int)(AppBootstrap::env('BCRYPT_COST', 12));

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

session_cache_limiter('nocache');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// BUILD APP URL
// ============================================

$protocol = $isHttps ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;

$normalizedAppUrl = rtrim((string)$appUrl, '/');

if ($basePath !== '') {
    $appUrlParts = parse_url($normalizedAppUrl);
    $appUrlPath = '';
    if (is_array($appUrlParts)) {
        $appUrlPath = trim((string)($appUrlParts['path'] ?? ''));
    }

    $normalizedBasePath = '/' . trim($basePath, '/');
    if ($normalizedBasePath === '/') {
        $normalizedBasePath = '';
    }

    // Avoid duplicating base path when APP_URL already contains it.
    if ($normalizedBasePath !== '' && ($appUrlPath === '' || $appUrlPath === '/')) {
        $fullAppUrl = $normalizedAppUrl . $normalizedBasePath;
    } else {
        $fullAppUrl = $normalizedAppUrl;
    }
} else {
    $fullAppUrl = $normalizedAppUrl;
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
define('SESSION_ABSOLUTE_TIMEOUT', $sessionAbsoluteTimeout);

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
        $dbPortFromEnv = AppBootstrap::env('DB_PORT');
        $hostIsLocal = in_array(strtolower(DB_HOST), ['localhost', '127.0.0.1', '::1'], true);

        if (($dbPortFromEnv === null || $dbPortFromEnv === '') && $hostIsLocal) {
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
                
                // SQL Injection Hardening
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                $this->conn->setAttribute(PDO::MYSQL_ATTR_MULTI_STATEMENTS, false);
                
                // Enable strict SQL mode
                $this->conn->exec("SET sql_mode = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION,ONLY_FULL_GROUP_BY'");
                
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
