<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string, string> */
    private array $routes = [];

    public function get(string $path, string $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, string $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, string $handler): void
    {
        $normalized = $this->normalizePath($path);
        $this->routes[$method . ' ' . $normalized] = $handler;
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path, '/');
        return $path === '' ? '/' : '/' . $path;
    }

    public function dispatch(string $method, string $path): void
    {
        $path = $this->normalizePath($path);
        $key = $method . ' ' . $path;

        if (!isset($this->routes[$key]))
        {
            http_response_code(404);
            echo '404 - Not Found';
            return;
        }

        $handler = $this->routes[$key];
        [$class, $action] = explode('@', $handler);
        if (!class_exists($class) || !method_exists($class, $action))
        {
            http_response_code(500);
            echo 'Invalid handler';
            return;
        }

        $controller = new $class();
        $controller->{$action}();
    }
}
