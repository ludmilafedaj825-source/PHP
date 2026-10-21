<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/Task.php';
require_once __DIR__ . '/TaskRepository.php';

$appName = "Task Manager";
/*$taskTimeEstimate = 10;
$file = 'tasks.json';
$tasks = []; // асоціативний масив*/

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$repo = new TaskRepository(__DIR__ . '/tasks.json');

// Обробка дій
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title    = trim($_POST['title'] ?? '');
        $deadline = trim($_POST['deadline'] ?? '') ?: null;
        if (!empty($title)) {
            $repo->add($title, $deadline);
        }
    }

    if ($action === 'complete') {
        $repo->complete((int) ($_POST['id'] ?? 0));
    }

    if ($action === 'delete') {
        $repo->delete((int) ($_POST['id'] ?? 0));
    }

    header('Location: index.php');
    exit;
}

$tasks = $repo->findAll();


/*if(file_exists($file)) {
    $content = file_get_contents($file);
    $decoded = json_decode($content, true);

    if(is_array($decoded)) {
        $tasks= $decoded;
    }
}*/

// Перевіряємо GET-параметр у посиланні:
// Якщо користувача перенаправило з create.php?created=1 — покажемо повідомлення про успіх!
$is_created = isset($_GET['created']) && $_GET['created'] === '1';
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
    return strlen($text) > $maxLength ? substr($text, 0, $maxLength)."..." : $text;
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
    <title><?= $appName ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main>
    <header>
        <?php include 'navigation.php'; ?>
        <h1>Головна сторінка</h1>
    </header> 
    <?php if(isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true):  ?>
            <p>Привіт, <?= htmlspecialchars($_SESSION['user_name']) ?>! <a href="logout.php">Вийти</a></p>
        <?php else: ?>
            <p>Ви увійшли як Гість. <a href="login.php">Вийти</a></p>
        <?php endif; ?>
        <div class="stats">
            Всього: <?= count($tasks) ?> |
            Виконано: <?= $repo->countCompleted() ?> |
            Прострочено: <?= $repo->countOverdue() ?>
        </div>
    <h2>Додати завдання</h2>
        <form method="POST" action="create.php">
            <input type="hidden" name="action" value="add">
            <input type="text" name="title" placeholder="Назва завдання" required>
            <input type="date" name="deadline">
            <button type="submit">Додати</button>
        </form>
    <?php if ($is_created): ?> 
        <div class="alert alert-success" >
            <p>Завдання успішно збережено!</p>
        </div>
    <?php endif; ?>

    <h2>Список завдань</h2>  
    <ol>
        <?php foreach ($tasks as $task): ?>
        <?php $statusClass = match ($task->getStatusLabel()) {
                'Виконано'    => 'task-done',
                'Прострочено' => 'task-overdue',
                default       => 'task-pending',
        }; ?> 
            <li>
                <div class="task">
                    <div>
                        <strong><?= htmlspecialchars($task->getTitle()) ?></strong>
                            <?php if ($task->getDeadline()): ?>
                                <small>(до <?= htmlspecialchars($task->getDeadline()) ?>)</small>
                            <?php endif; ?>
                            <span class="badge <?= $statusClass ?>"><?= $task->getStatusLabel() ?></span>
                    </div>
                <div class="actions">
                        <?php if (!$task->isCompleted()): ?>
                            <form method="POST" action="index.php" style="display:inline">
                                <input type="hidden" name="action" value="complete">
                                <input type="hidden" name="id" value="<?= $task->getId() ?>">
                                <button type="submit">✓</button>
                            </form>
                        <?php endif; ?>
                            <form method="POST" action="index.php" style="display:inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $task->getId() ?>">
                                <button type="submit">✕</button>
                            </form>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
</main>
</body>
</html>