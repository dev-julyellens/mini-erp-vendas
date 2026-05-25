<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function redirect(string $path): void
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $base = rtrim((string) $config['base_url'], '/');
        if ($path !== '' && $path[0] === '/')
        {
            $path = substr($path, 1);
        }
        header('Location: ' . $base . '/' . $path);
        exit;
    }

    protected function json(array $data, int $status = 200): void
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
     */
    protected function view(string $template, array $data = [], string $layout = 'layouts/main'): void
    {
        View::render($template, $data, $layout);
    }
}
