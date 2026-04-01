<?php

/**
 * Shared bootstrap helpers for environment/config loading.
 */
class AppBootstrap
{
  private static $envLoaded = false;

  /**
   * Load environment variables from project root .env files.
   */
  public static function loadEnvironment()
  {
    if (self::$envLoaded) {
      return;
    }

    $projectRoot = self::projectRoot();
    $envFiles = [
      $projectRoot . '/.env.production',
      $projectRoot . '/.env'
    ];

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

    $password = getenv('DB_PASS');
    if ($password === false) {
      $password = getenv('DB_PASSWORD');
    }
    if ($password === false) {
      $password = '';
    }

    return [
      'host' => getenv('DB_HOST') !== false ? getenv('DB_HOST') : 'localhost',
      'port' => (int) (getenv('DB_PORT') !== false ? getenv('DB_PORT') : 3307),
      'name' => getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'library_betonio',
      'user' => getenv('DB_USER') !== false ? getenv('DB_USER') : 'root',
      'password' => $password,
      'charset' => getenv('DB_CHARSET') !== false ? getenv('DB_CHARSET') : 'utf8mb4'
    ];
  }

  private static function projectRoot()
  {
    $root = realpath(__DIR__ . '/../../');
    if ($root !== false) {
      return $root;
    }

    return dirname(__DIR__, 2);
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

      if (getenv($key) === false) {
        putenv($key . '=' . $value);
      }
    }

    return true;
  }
}
