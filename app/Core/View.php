<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = [], string $layout = 'layouts/main'): void
    {
        $viewsPath = dirname(__DIR__) . '/Views/';
        $file = $viewsPath . $template . '.php';
        $layoutFile = $viewsPath . $layout . '.php';

        if (!is_file($file))
        {
            http_response_code(500);
            echo 'View não encontrada: ' . htmlspecialchars($template, ENT_QUOTES, 'UTF-8');
            exit;
        }

        if (!is_file($layoutFile))
        {
            http_response_code(500);
            echo 'Layout não encontrado: ' . htmlspecialchars($layout, ENT_QUOTES, 'UTF-8');
            exit;
        }

        if (!headers_sent())
        {
            header('Content-Type: text/html; charset=UTF-8');
        }

        extract($data, EXTR_SKIP);
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $appName = (string) ($config['app_name'] ?? 'App');
        $baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
        $__viewFile = $file;
        $needsDataTables = str_contains((string) file_get_contents($file), 'js-datatable');

        if ($layout === 'layouts/main')
        {
            $notificationLayout = [
                'notificationUnreadCount' => 0,
                'notificationRecent' => [],
                'notificationToasts' => [],
            ];

            if (\App\Helpers\Auth::check() && \App\Helpers\CompanyContext::hasSelected())
            {
                $notificationLayout = (new \App\Services\NotificationService())->layoutPayload();
            }

            extract($notificationLayout, EXTR_SKIP);
        }

        require $layoutFile;
    }
}
