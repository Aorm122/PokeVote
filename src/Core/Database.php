<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $driver = Env::get('DB_DRIVER', 'mysql');
        $dsn = Env::get('DB_DSN');

        if ($dsn === null) {
            if ($driver === 'sqlite') {
                $dbPath = Env::get('DB_PATH', dirname(__DIR__, 2) . '/storage/pokevote.sqlite');
                $dsn = 'sqlite:' . $dbPath;
            } else {
                $host = Env::get('DB_HOST', '127.0.0.1');
                $port = Env::get('DB_PORT', '3306');
                $name = Env::get('DB_NAME', 'pokevote');
                $charset = Env::get('DB_CHARSET', 'utf8mb4');
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $name, $charset);
            }
        }

        $username = Env::get('DB_USER', 'root');
        $password = Env::get('DB_PASSWORD', '');

        try {
            self::$connection = new PDO(
                $dsn,
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $exception) {
            throw new PDOException('Database connection failed: ' . $exception->getMessage(), (int) $exception->getCode(), $exception);
        }

        return self::$connection;
    }
}
