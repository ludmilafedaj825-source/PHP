<?php
declare(strict_types=1);

class Router
{
    /**
     * Таблиця зареєстрованих маршрутів: [МЕТОД => [ШЛЯХ => [КласКонтролера, Метод]]]
     * @var array<string, array<string, array{0: string, 1: string}>>
     */
    private array $routes = [];

    /**
     * Реєстрація GET-маршруту
     *
     * @param string $uri
     * @param array{0: string, 1: string} $action
     */
    public function get(string $uri, array $action): void
    {
        $this->routes['GET'][$uri] = $action;
    }

    /**
     * Реєстрація POST-маршруту
     *
     * @param string $uri
     * @param array{0: string, 1: string} $action
     */
    public function post(string $uri, array $action): void
    {
        $this->routes['POST'][$uri] = $action;
    }

    /**
     * Диспетчеризація вхідного HTTP-запиту
     *
     * @param string $method HTTP-метод (GET, POST тощо)
     * @param string $uri Повний URI запиту
     */
    public function dispatch(string $method, string $uri): void
    {
        // Відокремлюємо шлях від GET-параметрів (/tasks?page=2 -> /tasks)
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        // Нормалізуємо шлях: видаляємо зайвий слеш у кінці, крім кореня
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        $method = strtoupper($method);

        // Перевіряємо наявність маршруту в таблиці
        if (!isset($this->routes[$method][$path])) {
            $this->handleNotFound($method, $path);
            return;
        }

        [$controllerClass, $actionMethod] = $this->routes[$method][$path];

        if (!class_exists($controllerClass)) {
            throw new RuntimeException("Клас контролера '{$controllerClass}' не знайдено.");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $actionMethod)) {
            throw new RuntimeException("Метод '{$actionMethod}' не знайдено в класі '{$controllerClass}'.");
        }

        // Викликаємо відповідну дію контролера
        $controller->$actionMethod();
    }

    /**
     * Відображення стандартної сторінки помилки 404
     */
    private function handleNotFound(string $method, string $path): void
    {
        http_response_code(404);
        ?>
        <!DOCTYPE html>
        <html lang="uk">
        <head>
            <meta charset="UTF-8">
            <title>404 — Сторінку не знайдено</title>
            <link rel="stylesheet" href="style.css">
            <style>
                body { font-family: system-ui, sans-serif; background: #f8fafc; color: #1e293b; display: grid; place-content: center; min-height: 100vh; margin: 0; text-align: center; }
                h1 { font-size: 3rem; margin: 0 0 10px; color: #ef4444; }
            </style>
        </head>
        <body>
            <h1>404</h1>
            <p>Маршрут <code><?= htmlspecialchars($method) ?> <?= htmlspecialchars($path) ?></code> не зареєстровано в системі.</p>
            <a href="/">Повернутися на головну</a>
        </body>
        </html>
        <?php
    }
}