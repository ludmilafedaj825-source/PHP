```php  
/*TaskStorageInterface.php*/
<?php
declare(strict_types=1);

require_once __DIR__ . '/Task.php';

interface TaskStorageInterface
{
    /**
     * Повертає список усіх наявних завдань.
     *
     * @return Task[]
     */
    public function findAll(): array;

    /**
     * Знаходить завдання за його ідентифікатором.
     */
    public function findById(int $id): ?Task;

    /**
     * Додає нове завдання до сховища.
     *
     * @throws ValidationException якщо назва або дедлайн не проходять валідацію
     */
    public function add(string $title, ?string $deadline = null): Task;

    /**
     * Позначає завдання як виконане.
     *
     * @throws TaskNotFoundException якщо завдання з таким id не існує
     */
    public function complete(int $id): bool;

    /**
     * Видаляє завдання зі сховища.
     *
     * @throws TaskNotFoundException якщо завдання з таким id не існує
     */
    public function delete(int $id): bool;

    /*Повертає кількість виконаних завдань.*/
    public function countCompleted(): int;

    /*Повертає кількість прострочених завдань.*/
    public function countOverdue(): int;
}

```

```php 
/*TimestampableTrait.php*/
<?php
declare(strict_types=1);

trait TimestampableTrait
{
    private string $createdAt;
    private ?string $updatedAt = null;

    /*Ініціалізує початкові значення дат створення та оновлення.*/
    public function initTimestamps(?string $createdAt = null, ?string $updatedAt = null): void
    {
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt;
    }

    /*Оновлює мітку часу останньої модифікації на поточний момент.*/
    public function touch(): void
    {
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }
}
```

``` php  Exceptions.php
<?php
declare(strict_types=1);

/*Базовий клас для всіх доменних помилок у системі управління завданнями.*/
class TaskManagerException extends RuntimeException {}

/*Виняток, що виникає при зверненні до завдання за неіснуючим ID.*/
class TaskNotFoundException extends TaskManagerException
{
    public function __construct(int $taskId)
    {
        parent::__construct("Завдання з ідентифікатором #{$taskId} не знайдено.", 404);
    }
}

/* Виняток валідації вхідних даних (може містити перелік із кількох помилок).*/
class ValidationException extends TaskManagerException
{
    /** @var string[] */
    private array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct('Помилка валідації даних форми: ' . implode(', ', $errors), 422);
    }

    /**
     * Повертає масив текстових повідомлень про помилки.
     *
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
```

``` php 
/*Task.php*/
use TimestampableTrait;

    public function __construct(
        public readonly int $id,
        private string $title,
        private bool $isCompleted = false,
        private ?string $deadline = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
    ){
        $this->initTimestamps($createdAt, $updatedAt);
    }

    public function toArray(): array {
        return [
            'id' => $this -> id,
            'title' => $this -> title,
            'isCompleted' => $this -> isCompleted,
            'deadline' => $this -> deadline,
            'createdAt' => $this -> getCreatedAt(),
            'updatedAt' => $this -> getUpdatedAt(),
        ];
    }

    // Статичний метод: з масиву (прочитаного з JSON) створює об'єкт Task
    public static function fromArray(array $data): self{
        return new self(
            id: (int) $data['id'],
            title: (string) $data['title'],
            isCompleted: (bool) ($data['isCompleted'] ?? false),
            deadline: $data['deadline'] ?? null,
            createdAt: $data['createdAt'] ?? null,
            updatedAt: $data['updatedAt'] ?? null,
        );
    }   
```

```php 
/*TaskRepository.php*/
require_once __DIR__ . '/TaskStorageInterface.php';
require_once __DIR__ . '/Exceptions.php';
class TaskRepository implements TaskStorageInterface{
    ...
    public function add(string $title, ?string $deadline = null): Task
    {
        $errors=[];
        $cleanTitle = trim($title);

        if($cleanTitle===''){
            $errors[] = "Заповнення назви завдання є обов'язковим.";
        }
        elseif(mb_strlen($cleanTitle)<3){
            $errors[] = "Назва має містити не менше трьох символів.";
        }
        
        if($deadline !== null && $deadline !== ''){
            $timestamp = strtotime($deadline);
            if($timestamp === false){
                $errors[] = "Вказано некоректну дату дедлайну";
            }
        }
        else{
            $deadline = null;
        }

        if(!empty($errors)){
            throw new ValidationException($errors);
        }

        ...
    }
    ...
    private function load(): void
    {
       ...

        $json = file_get_contents($this->storageFile);
        $data = json_decode($json, true) ?? [];
        $this->tasks = array_map(fn(array $item) => Task::fromArray($item), $data);
    }
}
```

``` php 

/*index.php*/
require_once __DIR__ . '/TaskStorageInterface.php';
require_once __DIR__ . '/Exceptions.php';

...
$formErrors = [];
$feedbackMessage = $_SESSION['feedback'] ?? null;
unset($_SESSION['feedback']);

// Перевіряємо GET-параметр у посиланні:
// Якщо користувача перенаправило з create.php?created=1 — покажемо повідомлення про успіх!
$is_created = isset($_GET['created']) && $_GET['created'] === '1';
if ($is_created && $feedbackMessage === null) {
    $feedbackMessage = 'Завдання успішно додано!';
}

// Обробка дій
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try{

    if ($action === 'add') {
        $title    = $_POST['title'] ?? '';
        $deadline = trim($_POST['deadline'] ?? '') ?: null;

        $repo->add($title, $deadline);
        $_SESSION['feedback'] = 'Завдання успішно додано!';
        header('Location: index.php');
        exit;
    }

    if ($action === 'complete') {
        $id = (int) ($_POST['id'] ?? 0);
        $repo->complete($id);
        $_SESSION['feedback'] = 'Статус завдання оновлено!';
        header('Location: index.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $repo->delete($id);
        $_SESSION['feedback'] = 'Завдання видалено!';
        header('Location: index.php');
        exit;
    }

    header('Location: index.php');
    exit;
    }
    catch(ValidationException $e){
        // Перехоплюємо помилки валідації форми
        $formErrors = $e->getErrors();
    }
    catch(TaskNotFoundException $e){
        // Перехоплюємо спробу маніпуляції неіснуючим завданням
        $formErrors = [$e->getMessage()];
    }
    catch(TaskManagerException $e){
        $formErrors = ['Помилка програми: ' . $e->getMessage()];
    }
    catch(Throwable $e){
        $formErrors = ['Критичний збій обробки запиту. Зверніться до техпідтримки.'];
    }
    finally{}
}
...
    <p>Користувач: <?= getCurrentGreeting() ?> <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Адміністратор') ?></strong> | <a href="logout.php">Вийти</a></p>
        <?php if ($feedbackMessage): ?>
            <div class="alert-success"><?= htmlspecialchars($feedbackMessage) ?></div>
        <?php endif; ?>

        <?php if (!empty($formErrors)): ?>
            <div class="alert-error">
                <strong>Увага! Виявлено помилки під час виконання операції:</strong>
                <ul>
                    <?php foreach ($formErrors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    <h2> Нове завдання</h2>
        <form method="POST" action="index.php" class = "form-add">
            <input type="hidden" name="action" value="add">
            <input type="text" name="title" placeholder="Що потрібно зробити (мінімум 3 символи)?" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
            <input type="date" name="deadline" value="<?= htmlspecialchars($_POST['deadline'] ?? '') ?>">
            <button type="submit">Додати</button>
        </form> 
```

``` json
    /*JSON*/
    {
        "id": 8,
        "title": "Виконати лабораторну роботу 10 PHP",
        "isCompleted": true,
        "deadline": "2026-10-19",
        "createdAt": "2026-10-28 13:05:44",
        "updatedAt": "2026-10-28 13:05:56"
    },
    {
        "id": 9,
        "title": "Лабораторна робота 2",
        "isCompleted": true,
        "deadline": "2026-10-21",
        "createdAt": "2026-10-28 13:05:44",
        "updatedAt": "2026-10-28 13:06:58"
    },
    {
        "id": 10,
        "title": "PHP Lab11",
        "isCompleted": false,
        "deadline": "2026-10-29",
        "createdAt": "2026-10-28 13:05:44",
        "updatedAt": null
    }
```
# Контрольні запитання

1) Різниця між інтерфейсом та абстрактним класом - у чому полягає відмінність між interface та abstract class, і чому для сховища TaskStorageInterface обрано саме інтерфейс;

Інтерфейс - це набір сигнатур методів без реалізації. Вони описують контракт - набір методів, що клас зобов'язується реалізувати. 
Абстрактні класи дозволяють визначити загальну поведінку для групи класів, залишаючи метод для обов'язкової реалізації нащадками.
Для сховища TaskStorageInterface обрано інтерфейс, бо JSON-файл і база даних не мають спільного коду, а мають лише спільний набір операцій.

2) Переваги слабого зв’язку (Loose Coupling) - як саме підключення репозиторію через контракт TaskStorageInterface полегшить майбутній перехід від JSON-файлів до бази даних MySQL за допомогою PDO;

Перевага слабкого зв'язку полягає в тому, що класи та модулі мінімально залежні одне від одного.
Для того, щоб перейти до бази даних MySQL за допомогою PDO, достатньо написати новий клас implements TaskStorageInterface, який виконує ті самі методи через PDO, і підставитини його в місці, де створюється репозиторій, увесь інший код не потрібно змінювати.

3) Горизонтальне перевикористання через трейти - яку проблему розв’язує TimestampableTrait в умовах відсутності множинного наслідування класів у PHP;

Трейти вирішують задачу повторного використання коду між непов’язаними класами без наслідування.
У PHP клас може мати лише одного батька. Трейт дозволяє використати готовий код у будь-якому класі, не використовуючи успадкування. TimestampableTrait додає будь-якому класу дату створення та оновлення без копіювання коду.

4) Черговість перехоплення у блоках catch - чому блок catch (ValidationException $e) обов’язково повинен розміщуватися перед catch (TaskManagerException $e) та catch (Throwable $e);

catch  перевіряється зверху вниз і виконує перший, що підійшов. ValidationException є дочірнім класом TaskManagerException, тобто наслідує, а TaskManagerException наслідує RuntimeException. Якщо загальний catch(TaskManagerException або Throwable) поставити першим, він перехопить усе, і конкретний ніколи не спрацює. Тому порядок перевірки завжди: від конкретного до загального.

5) Призначення секції finally - за яких обставин виконується код всередині блоку finally, і які завдання з вивільнення системних ресурсів доцільно в ньому розташовувати.

Код finally виконується завжди, незалежно від того, чи виникла помилка чи ні. Там зазвичай закривають файли, з'єднання з базою, знімають блокування.