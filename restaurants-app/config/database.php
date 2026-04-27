<?php

/**
 * Database Configuration
 * Loads .env and returns PDO connection
 */

class Database
{
    private static ?PDO $connection = null;
    private static array $config = [];

    public static function load(): void
    {
        $envFile = dirname(__DIR__) . '/.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (trim($line) === '' || $line[0] === '#') {
                    continue;
                }
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    self::$config[trim($key)] = trim($value);
                }
            }
        }
    }

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::load();
            
            $host = self::$config['DB_HOST'] ?? 'localhost';
            $port = self::$config['DB_PORT'] ?? '5432';
            $dbname = self::$config['DB_NAME'] ?? ' Mekla';
            $user = self::$config['DB_USER'] ?? 'postgres';
            $pass = self::$config['DB_PASS'] ?? '';

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
            
            self::$connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            self::$connection->exec("SET search_path TO public");
        }
        
        return self::$connection;
    }

    public static function getConfig(string $key, $default = null)
    {
        if (empty(self::$config)) {
            self::load();
        }
        return self::$config[$key] ?? $default;
    }

    public static function close(): void
    {
        self::$connection = null;
    }
}