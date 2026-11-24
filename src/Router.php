<?php
declare(strict_types=1);

namespace App;

use RuntimeException;

class Router
{
    private array $routes = [];

    public function get(string $uri, array $action): void
    {
        $this->routes['GET'][$uri] = $action;
    }

    public function post(string $uri, array $action): void
    {
        $this->routes['POST'][$uri] = $action;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        $method = strtoupper($method);

        if (!isset($this->routes[$method][$path])) {
            $this->handleNotFound($method, $path);
            return;
        }

        $action = $this->routes[$method][$path];

        // Якщо передано вже готовий об'єкт або функцію
        if (is_callable($action)) {
            $action();
            return;
        }

        // Якщо передано масив вигляду [Об'єктАбоКлас, 'метод']
        [$target, $methodName] = $action;

        if (is_object($target)) {
            $target->$methodName();
            return;
        }

        if (is_string($target)) {
            if (!class_exists($target)) {
                throw new RuntimeException("Клас '{$target}' не знайдено.");
            }
            $controller = new $target();
            $controller->$methodName();
        }
    }

    private function handleNotFound(string $method, string $path): void
    {
        http_response_code(404);
        echo "<h1 style='font-family: sans-serif; text-align: center; margin-top: 50px;'>404 — Сторінку не знайдено</h1>";
        echo "<p style='font-family: sans-serif; text-align: center; color: #666;'>Маршрут {$method} {$path} не зареєстровано.</p>";
    }
}
