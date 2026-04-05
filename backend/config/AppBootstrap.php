<?php

/**
 * Shared bootstrap helpers for environment/config loading.
 */
class AppBootstrap
{
  private static $envLoaded = false;
  private static $envValues = [];

  /**
   * Load environment variables from project root .env files.
   */
  public static function loadEnvironment()
  {
    if (self::$envLoaded) {
      return;
    }

    $projectRoot = self::projectRoot();
    $preferLocalEnv = self::isLocalRuntime();
    $envFiles = $preferLocalEnv
      ? [$projectRoot . '/.env', $projectRoot . '/.env.production']
      : [$projectRoot . '/.env.production', $projectRoot . '/.env'];

    foreach ($envFiles as $envFile) {
      if (self::loadEnvFile($envFile)) {
        break;
      }
    }

    self::$envLoaded = true;
  }

  /**
   * Shared database config getter.
   */
  public static function getDatabaseConfig()
  {
    self::loadEnvironment();

    $password = self::env('DB_PASS');
    if ($password === null) {
      $password = self::env('DB_PASSWORD', '');
    }

    return [
      'host' => self::env('DB_HOST', 'localhost'),
      'port' => (int) self::env('DB_PORT', 3307),
      'name' => self::env('DB_NAME', 'library_betonio'),
      'user' => self::env('DB_USER', 'root'),
      'password' => $password,
      'charset' => self::env('DB_CHARSET', 'utf8mb4')
    ];
  }

  /**
   * Read an environment value loaded from file or server env.
   */
  public static function env($key, $default = null)
  {
    self::loadEnvironment();

    if (array_key_exists($key, self::$envValues)) {
      return self::$envValues[$key];
    }

    $value = self::readNativeEnv($key);
    if ($value !== null) {
      return $value;
    }

    return $default;
  }

  private static function projectRoot()
  {
    $root = realpath(__DIR__ . '/../../');
    if ($root !== false) {
      return $root;
    }

    return dirname(__DIR__, 2);
  }

  /**
   * Detect local/dev runtime so localhost uses local .env settings first.
   */
  private static function isLocalRuntime()
  {
    if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
      return true;
    }

    $hostCandidates = [];
    if (isset($_SERVER['HTTP_HOST'])) {
      $hostCandidates[] = (string) $_SERVER['HTTP_HOST'];
    }
    if (isset($_SERVER['SERVER_NAME'])) {
      $hostCandidates[] = (string) $_SERVER['SERVER_NAME'];
    }

    foreach ($hostCandidates as $hostCandidate) {
      $host = strtolower(trim($hostCandidate));
      $host = preg_replace('/:\d+$/', '', $host);
      if ($host === '[::1]') {
        $host = '::1';
      }

      if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
        return true;
      }
    }

    $appEnv = self::readNativeEnv('APP_ENV');
    if (is_string($appEnv) && in_array(strtolower(trim($appEnv)), ['development', 'dev', 'local'], true)) {
      return true;
    }

    return false;
  }

  private static function loadEnvFile($filePath)
  {
    if (!is_file($filePath) || !is_readable($filePath)) {
      return false;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
      return false;
    }

    foreach ($lines as $line) {
      $line = trim($line);
      $line = ltrim($line, "\xEF\xBB\xBF");
      if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
        continue;
      }

      list($key, $value) = explode('=', $line, 2);
      $key = trim($key);
      $value = trim($value);

      if (
        (substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
        (substr($value, 0, 1) === "'" && substr($value, -1) === "'")
      ) {
        $value = substr($value, 1, -1);
      }

      $nativeValue = self::readNativeEnv($key);
      $hasNativeValue = $nativeValue !== null;
      if ($hasNativeValue) {
        self::$envValues[$key] = (string) $nativeValue;
        continue;
      }

      self::$envValues[$key] = $value;
      $_ENV[$key] = $value;
      $_SERVER[$key] = $value;

      if (function_exists('putenv')) {
        @putenv($key . '=' . $value);
      }
    }

    return true;
  }

  private static function readNativeEnv($key)
  {
    $value = getenv($key);
    if ($value !== false) {
      return $value;
    }

    if (isset($_ENV[$key])) {
      return $_ENV[$key];
    }

    if (isset($_SERVER[$key])) {
      return $_SERVER[$key];
    }

    return null;
  }
}
