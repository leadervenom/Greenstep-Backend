<?php

namespace App;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function get(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $_ENV['DB_HOST'] ?? '127.0.0.1',
            $_ENV['DB_PORT'] ?? '3306',
            $_ENV['DB_NAME'] ?? 'greenstep_api',
            $_ENV['DB_CHARSET'] ?? 'utf8mb4'
        );

        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,

                // SSL options for Aiven
                PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/../certs/ca.pem',
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ];

            self::$pdo = new PDO(
                $dsn,
                $_ENV['DB_USER'] ?? 'root',
                $_ENV['DB_PASS'] ?? '',
                $options
            );

            return self::$pdo;

        } catch (PDOException $e) {
            error_log('[DB] ' . $e->getMessage());
            throw new RuntimeException('Database connection failed', 500, $e);
        }
    }
}