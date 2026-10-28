<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/Task.php';
require_once __DIR__ . '/TaskStorageInterface.php';
require_once __DIR__ . '/TaskRepository.php';
require_once __DIR__ . '/Exceptions.php';

/*$appName = "Task Manager";
$taskTimeEstimate = 10;
$file = 'tasks.json';
$tasks = []; // асоціативний масив*/

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$repo = new TaskRepository(__DIR__ . '/tasks.json');

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

$tasks = $repo->findAll();

/*if(file_exists($file)) {
    $content = file_get_contents($file);
    $decoded = json_decode($content, true);

    if(is_array($decoded)) {
        $tasks= $decoded;
    }
}*/

//сортування масиву з пріоритетом
/*usort($tasks, function($a, $b){
    $priorities = [
        'High' => 1,
        'Medium' => 2,
        'Low' => 3
    ];

    $weightA = $priorities[$a['priority'] ?? ''] ?? 99;
    $weightB = $priorities[$b['priority'] ?? ''] ?? 99;

    return $weightA <=> $weightB;
});*/

function formatTitle($text, $maxLength = 20)
{
    return mb_strlen($text) > $maxLength ? mb_substr($text, 0, $maxLength)."..." : $text;
}

function getCurrentGreeting() {
    $hour = date('H');
    if($hour >= 6 && $hour < 12){
        return "Доброго ранку";
    }
    elseif($hour >= 12 && $hour < 18){
        return "Добрий день";
    }
    elseif($hour >= 18 && $hour < 24){
        return "Добрий вечір";
    }
    else{
        return "Доброї ночі";
    }
}

?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Менеджер завдань (ООП: Інтерфейси та Винятки)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <?php include 'navigation.php'; ?>
        <h1>Головна сторінка</h1>
    </header> 
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
    <div class="stats">
        Всього завдань: <?= count($tasks) ?>
        Виконано: <?= $repo->countCompleted() ?>
        Прострочено: <?= $repo->countOverdue() ?>
    </div>
    <div class="tasks-list">
    <?php if (empty($tasks)): ?>
        <p>Список завдань порожній.</p>
    <?php else: ?>
        <?php foreach ($tasks as $task): ?>
            <div class="task">
                <div>
                    <div>
                        <strong><?= htmlspecialchars($task->getTitle()) ?></strong>
                        <?php if ($task->getDeadline()): ?>
                            <small>(Дедлайн: <?= htmlspecialchars($task->getDeadline()) ?>)</small>
                        <?php endif; ?>
                        </div>
                        <div class="task-meta">
                            Створено: <?= htmlspecialchars($task->getCreatedAt()) ?>
                            <?php if ($task->getUpdatedAt()): ?>
                                Оновлено: <?= htmlspecialchars($task->getUpdatedAt()) ?>
                            <?php endif; ?>
                    </div>
                </div>
                <div class="actions">
                    <?php
                        $badgeClass = match ($task->getStatusLabel()) {
                            'Виконано'   => 'badge-success',
                            'Прострочено' => 'badge-danger',
                            default       => 'badge-primary',
                        };
                    ?>
                        <span class="badge <?= $badgeClass ?>"><?= $task->getStatusLabel() ?></span>

                    <?php if (!$task->isCompleted()): ?>
                        <form method="POST" action="index.php" style="display:inline">
                            <input type="hidden" name="action" value="complete">
                            <input type="hidden" name="id" value="<?= $task->getId() ?>">
                            <button type="submit" title="Позначити виконаним">✓</button>
                        </form>
                    <?php endif; ?>

                        <form method="POST" action="index.php" style="display:inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $task->getId() ?>">
                            <button type="submit" title="Видалити">✕</button>
                        </form>
                    </div>
                </div>
            </li>
        <?php endforeach; ?>
        <?php endif; ?>
</div>
</body>
</html>