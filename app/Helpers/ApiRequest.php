<?php

declare(strict_types=1);

namespace App\Helpers;

final class ApiRequest
{
    private static ?string $rawBody = null;

    /** @var array<string, mixed>|null */
    private static ?array $parsedBody = null;

    private static bool $bodyParsed = false;

    public static function isApiPath(string $path): bool
    {
        return strpos($path, '/api/') === 0;
    }

    public static function clientIp(): string
    {
        $candidates = [];

        if (isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] !== '')
        {
            $candidates[] = trim($_SERVER['REMOTE_ADDR']);
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && is_string($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] !== '')
        {
            $candidates[] = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }

        foreach ($candidates as $ip)
        {
            if (filter_var($ip, FILTER_VALIDATE_IP) !== false)
            {
                return strlen($ip) <= 45 ? $ip : substr($ip, 0, 45);
            }
        }

        return '0.0.0.0';
    }

    public static function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
        if (!is_string($header) || $header === '')
        {
            return null;
        }

        if (preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches) !== 1)
        {
            return null;
        }

        $token = trim($matches[1]);

        return $token !== '' ? $token : null;
    }

    public static function rawBody(): string
    {
        if (self::$rawBody === null)
        {
            $raw = file_get_contents('php://input');
            self::$rawBody = is_string($raw) ? $raw : '';
        }

        return self::$rawBody;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function jsonBody(): ?array
    {
        if (self::$bodyParsed)
        {
            return self::$parsedBody;
        }

        self::$bodyParsed = true;
        $raw = self::rawBody();

        if ($raw === '')
        {
            self::$parsedBody = null;
            return null;
        }

        try
        {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        }
        catch (\JsonException $e)
        {
            self::$parsedBody = null;
            return null;
        }

        self::$parsedBody = is_array($decoded) ? $decoded : null;

        return self::$parsedBody;
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array<string, mixed>|null
     */
    public static function sanitizePayloadForLog(?array $payload): ?array
    {
        if ($payload === null || $payload === [])
        {
            return $payload;
        }

        $sanitized = [];
        foreach ($payload as $key => $value)
        {
            if (self::isSensitiveField((string) $key))
            {
                $sanitized[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value))
            {
                $sanitized[$key] = self::sanitizePayloadForLog($value);
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    private static function isSensitiveField(string $field): bool
    {
        $normalized = strtolower($field);

        return in_array($normalized, [
            'password',
            'password_confirm',
            'token',
            'access_token',
            'refresh_token',
            'authorization',
        ], true);
    }
}
