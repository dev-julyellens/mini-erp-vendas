<?php

declare(strict_types=1);

namespace App\Helpers;

final class ApiResponse
{
    /**
     * @param array<string, mixed> $data
     */
    public static function send(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        try
        {
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }
        catch (\JsonException $e)
        {
            http_response_code(500);
            echo '{"success":false,"message":"JSON encoding failed"}';
        }

        exit;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed>|null $meta
     */
    public static function success(array $data = [], int $status = 200, ?array $meta = null): void
    {
        $payload = ['success' => true];

        if ($data !== [])
        {
            $payload['data'] = $data;
        }

        if ($meta !== null && $meta !== [])
        {
            $payload['meta'] = $meta;
        }

        self::send($payload, $status);
    }

    /**
     * @param array<string, string>|null $errors
     */
    public static function error(string $message, int $status = 400, ?array $errors = null): void
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null && $errors !== [])
        {
            $payload['errors'] = $errors;
        }

        self::send($payload, $status);
    }
}
