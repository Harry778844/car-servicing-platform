<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Database Connection & Query Helper Class (PDO)
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    private static string $driver = 'mysql';

    /**
     * Get singleton PDO connection
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                // First attempt: MySQL connection
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_TIMEOUT            => 2, // Quick timeout if MySQL daemon is not running
                ];
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                self::$driver = 'mysql';
            } catch (PDOException $e) {
                // Secondary Fallback: SQLite database file for standalone local development/preview
                $sqlitePath = __DIR__ . '/../database/carservice.sqlite';
                try {
                    $dsn = "sqlite:" . $sqlitePath;
                    self::$instance = new PDO($dsn, null, null, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]);
                    self::$driver = 'sqlite';
                    self::$instance->exec('PRAGMA foreign_keys = ON;');
                } catch (Exception $sqle) {
                    die("Database Connection Error: Could not connect to MySQL (" . $e->getMessage() . ") or SQLite (" . $sqle->getMessage() . "). Please ensure MySQL service is running or check config/database.php");
                }
            }
        }
        return self::$instance;
    }

    public static function getDriver(): string {
        return self::$driver;
    }

    /**
     * Quick query helper for fetching all records
     */
    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Quick query helper for fetching single record
     */
    public static function fetchOne(string $sql, array $params = []): ?array {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Execute INSERT, UPDATE, DELETE query
     */
    public static function execute(string $sql, array $params = []): bool {
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Get last inserted ID
     */
    public static function lastInsertId(): string {
        return self::getConnection()->lastInsertId();
    }
}
