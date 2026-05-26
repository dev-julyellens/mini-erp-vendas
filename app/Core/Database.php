<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null)
        {
            $config = require dirname(__DIR__, 2) . '/config/app.php';
            $db = $config['database'];
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $db['host'],
                $db['port'],
                $db['database']
            );

            try
            {
                self::$instance = new PDO($dsn, $db['username'], $db['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            }
            catch (PDOException $e)
            {
                throw new PDOException('Database connection failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
            }
        }

        return self::$instance;
    }

    public static function resetForTesting(): void
    {
        self::$instance = null;
    }
}
