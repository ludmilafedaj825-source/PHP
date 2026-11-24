```json
{
    "name": "student/task-manager",
    "description": "Task Manager application with MVC, PSR-4 and Service Layer",
    "type": "project",
    "license": "MIT",
    "require": {
        "php": ">=8.1",
        "vlucas/phpdotenv": "^5.8"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

TaskService.php
```php
    public function getTasksPaginated(int $userId, int $page = 1, int $perPage = 5): array
    {
        $page = max(1, $page);
        $totalTasks = $this->repository->countByUser($userId);
        $totalPages = (int) ceil($totalTasks / $perPage);
        $tasks = $this->repository->findAllByUser($userId, $page, $perPage);

        return [
            'tasks'       => $tasks,
            'totalTasks'  => $totalTasks,
            'totalPages'  => $totalPages,
            'currentPage' => $page,
        ];
    }

    /**
     * Створення нового завдання з валідацією бізнес-правил
     *
     * @throws InvalidArgumentException
     */
    public function createTask(int $userId, string $title, ?string $deadline): int
    {
        $trimmedTitle = trim($title);

        if (mb_strlen($trimmedTitle) < 3) {
            throw new InvalidArgumentException('Назва завдання повинна містити щонайменше 3 символи.');
        }

        if (mb_strlen($trimmedTitle) > 255) {
            throw new InvalidArgumentException('Назва завдання не може перевищувати 255 символів.');
        }

        $formattedDeadline = null;
        if (!empty($deadline)) {
            $timestamp = strtotime($deadline);
            if ($timestamp === false) {
                throw new InvalidArgumentException('Вказано некоректний формат дати дедлайну.');
            }
            $formattedDeadline = date('Y-m-d H:i:s', $timestamp);
        }

        return $this->repository->add($userId, $trimmedTitle, $formattedDeadline);
    }

```
Точка входу public/index.php (побудова дерева DI)

``` php
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
```

# Контрольні запитання

1) Що таке Composer і яку роль він відіграє в екосистемі PHP? Чим файл composer.json відрізняється від composer.lock?

Composer — менеджер залежностей для PHP; завантажує та оновлює сторонні бібліотеки. Він вирішує проблеми важкості оновлення бібліотек, конфлікту версії та відсутності стандартного способу підключення (автозавантаження), він читає файл composer.json, завантажує потрібні пакети з репозиторію packagist.org, перевіряє сумісність версій і генерує єдиний файл автозавантаження. composer.json — маніфест проєкту; описує залежності та правила автозавантаження. composer.lock генерується автоматично і фіксує точні версії встановлених пакетів, щоб у всіх розробників було однакове середовище.

2) Поясніть принципи стандарту автозавантаження PSR-4. Що означає запис "App\\": "src/" у блоці autoload?

PSR-4 встановлює відповідність між простором імен класу та шляхом до файлу на диску: структура папок повторює простори імен. Запис "App\\": "src/" означає, що префікс простору імен "App\\" вказує на директорію "src/". Тому клас App\Models\Task інтерпретатор автоматично шукатиме у файлі src/Models/Task.php, а клас App\Controllers\TaskController — у файлі src/Controllers/TaskController.php.

3) Що таке простори імен (namespace) та інструкція use? Яку проблему вони вирішують при підключенні сторонніх бібліотек?

Простір імен (namespace) — це логічна «папка» для класів, яка дає кожному класу повну «адресу», наприклад App\Models\Task. Інструкція use імпортує клас з іншого простору імен, щоб у коді можна було писати коротке ім'я замість повного. Вони запобігають конфліктам імен: якщо у проєкті та у сторонній бібліотеці є класи з однаковою назвою (наприклад, User), PHP розрізняє їх за повною адресою.

4) Чому конфігураційні дані (паролі БД, API-ключі) неприпустимо зберігати у файлах вихідного коду, і яку роль відіграє бібліотека phpdotenv разом із файлами .env та .env.example?

Облікові дані у вихідному коді потрапляють у Git-репозиторій і можуть стати доступними стороннім, тому це грубе порушення безпеки; конфігурація має бути відокремлена від коду. Бібліотека phpdotenv зчитує файл .env і поміщає значення у $_ENV, звідки їх бере код (наприклад, клас Database). Файл .env з реальними секретами додається до .gitignore, а .env.example — це шаблон без секретів, який комітять, щоб інші розробники знали, які змінні потрібні.

5) У чому полягає суть архітектурного патерну Service Layer та принципу Dependency Injection? Чому створення залежностей через new всередині контролера вважається поганою практикою?

Service Layer концентрує бізнес-правила (валідація, перевірка дедлайну, пагінація) в окремому класі TaskService, а контролер стає «тонким» координатором HTTP-запитів. Dependency Injection — це передача залежностей у клас ззовні, через конструктор, а не створення їх усередині; у проєкті все збирається в public/index.php. Створення залежностей через new у контролері погане, бо жорстко прив'язує його до конкретних реалізацій, унеможливлює тестування без реальної БД і вимагає змінювати код контролера при заміні, наприклад, репозиторію.
