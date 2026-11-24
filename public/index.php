<?php
declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\TaskController;
use App\Models\Database;
use App\Repositories\TaskRepository;
use App\Router;
use App\Services\TaskService;
use Dotenv\Dotenv;

// 1. Стандартний автозавантажувач класів Composer PSR-4
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Безпечне завантаження конфігурації з файлу .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// 3. Старт користувацької сесії
session_start();

// 4. Побудова дерева залежностей (Dependency Injection)
$pdo            = Database::getConnection();
$taskRepository = new TaskRepository($pdo);
$taskService    = new TaskService($taskRepository);

// Інстанціюємо контролери, передаючи необхідні сервіси через конструктор
$taskController = new TaskController($taskService);
$authController = new AuthController();

// 5. Ініціалізація та налаштування маршрутизатора
$router = new Router();

// Маршрути задач
$router->get('/',                [$taskController, 'index']);
$router->get('/tasks/create',    [$taskController, 'create']);
$router->post('/tasks/create',   [$taskController, 'store']);
$router->post('/tasks/complete', [$taskController, 'complete']);
$router->post('/tasks/delete',   [$taskController, 'delete']);

// Маршрути авторизації
$router->get('/login',           [$authController, 'showLogin']);
$router->post('/login',          [$authController, 'login']);
$router->get('/logout',          [$authController, 'logout']);

// 6. Обробка вхідного HTTP-запиту
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);