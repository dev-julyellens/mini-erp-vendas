<?php

declare(strict_types=1);

namespace App\Core;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Monolog\Processor\PsrLogMessageProcessor;

final class Logger
{
    private static ?MonologLogger $logger = null;

    public static function channel(): MonologLogger
    {
        if (self::$logger === null)
        {
            self::$logger = self::build();
        }

        return self::$logger;
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function debug(string $message, array $context = []): void
    {
        self::channel()->debug($message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function info(string $message, array $context = []): void
    {
        self::channel()->info($message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function warning(string $message, array $context = []): void
    {
        self::channel()->warning($message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function error(string $message, array $context = []): void
    {
        self::channel()->error($message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function exception(\Throwable $e, string $message = '', array $context = []): void
    {
        $context['exception'] = $e;
        if ($message === '')
        {
            $message = $e->getMessage();
        }

        self::channel()->error($message, $context);
    }

    public static function resetForTesting(): void
    {
        self::$logger = null;
    }

    private static function build(): MonologLogger
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $log = $config['log'] ?? [];

        $path = isset($log['path']) ? (string) $log['path'] : dirname(__DIR__, 2) . '/storage/logs/app.log';
        $levelName = isset($log['level']) ? (string) $log['level'] : 'warning';
        $level = self::resolveLevel($levelName);

        $dir = dirname($path);
        if (!is_dir($dir))
        {
            mkdir($dir, 0755, true);
        }

        $logger = new MonologLogger('mini-erp');
        $logger->pushHandler(new StreamHandler($path, $level));
        $logger->pushProcessor(new PsrLogMessageProcessor());

        return $logger;
    }

    private static function resolveLevel(string $levelName): Level
    {
        return match (strtoupper(trim($levelName)))
        {
            'DEBUG' => Level::Debug,
            'INFO' => Level::Info,
            'NOTICE' => Level::Notice,
            'ERROR' => Level::Error,
            'CRITICAL' => Level::Critical,
            'ALERT' => Level::Alert,
            'EMERGENCY' => Level::Emergency,
            default => Level::Warning,
        };
    }
}
