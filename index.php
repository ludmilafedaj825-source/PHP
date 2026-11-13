<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Task.php';
require_once __DIR__ . '/TaskStorageInterface.php';
require_once __DIR__ . '/TaskRepository.php';
require_once __DIR__ . '/Exceptions.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Студент';
}

$currentUserId = (int) $_SESSION['user_id'];
$pdo = Database::getConnection();
$repo = new TaskRepository($pdo);

$formErrors = [];
$feedbackMessage = $_SESSION['feedback'] ?? null;
unset($_SESSION['feedback']);

// Обробка POST-запитів
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
            $title = $_POST['title'] ?? '';
            $deadline = trim($_POST['deadline'] ?? '') ?: null;

            $repo->add($currentUserId, $title, $deadline);
            $_SESSION['feedback'] = 'Завдання успішно збережено в базі даних!';
            header('Location: index.php');
            exit;
        }

        if ($action === 'complete') {
            $id = (int) ($_POST['id'] ?? 0);
            $repo->complete($id, $currentUserId);
            $_SESSION['feedback'] = 'Статус завдання змінено на "Виконано"!';
            header('Location: index.php');
            exit;
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $repo->delete($id, $currentUserId);
            $_SESSION['feedback'] = 'Завдання видалено з бази даних!';
            header('Location: index.php');
            exit;
        }
    } catch (ValidationException $e) {
        // Перехоплюємо помилки валідації форми
        $formErrors = $e->getErrors();
    } catch (TaskNotFoundException $e) {
        // Перехоплюємо спробу маніпуляції неіснуючим завданням
        $formErrors = [$e->getMessage()];
    } catch (TaskManagerException $e) {
        $formErrors = ['Помилка програми: ' . $e->getMessage()];
    } catch (Throwable $e) {
        $formErrors = ['Критична помилка: ' . $e->getMessage()];
    }
}

// Розрахунок параметрів пагінації
$perPage = 5;
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalTasks = $repo->countByUser($currentUserId);
$totalPages = max(1, (int) ceil($totalTasks / $perPage));

if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
}

$tasks = $repo->findAllByUser($currentUserId, $page, $perPage);
$completedCount = $repo->countCompletedByUser($currentUserId);
$overdueCount = $repo->countOverdueByUser($currentUserId);

/*
**function formatTitle($text, $maxLength = 20)
{
    return mb_strlen($text) > $maxLength ? mb_substr($text, 0, $maxLength)."..." : $text;
}**
*/

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
    <title>Менеджер завдань - Лабораторна №13</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <?php include 'navigation.php'; ?>
        <h1>Менеджер завдань (MySQL + PDO)</h1>
    </header> 
        <p> Авторизовани користувач: <strong><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></strong>
            <a href="test_db.php">🔍 Діагностика БД (MySQL)</a> |
            <a href="login.php">Вийти</a>
        </p>
        <div class="stats">
            <span>Всього завдань: <?= $totalTasks ?></span>
            <span>Виконано: <?= $completedCount ?></span>
            <span>Прострочено: <?= $overdueCount ?></span>
    </div>
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

        <form method="POST" action="index.php" class = "form-add">
            <input type="hidden" name="action" value="add">
            <input type="text" name="title" placeholder="Що потрібно зробити (мінімум 3 символи)?" required>
            <input type="date" name="deadline" placeholder="Дата дедлайну">
            <button type="submit">Додати</button>
        </form>
    <h2>Список завдань (Сторінка <?= $page ?> з <?= $totalPages ?>)</h2>

    <div class="tasks-list">
        <?php if (empty($tasks)): ?>
            <p>Список завдань порожній.</p>
        <?php else: ?>
            <?php foreach ($tasks as $task): ?>
                <div class="task <?= $task->isCompleted() ? 'task-completed' : '' ?>">
                    <div>
                        <strong><?= htmlspecialchars($task->getTitle()) ?></strong>
                        <br>
                        <small>(Дедлайн: <?= htmlspecialchars($task->getDeadline() ?? 'Не встановлено') ?>)</small>
                    </div>
                    <div class="task-meta">
                        <?php if(!$task->isCompleted()): ?>
                            <form method="POST" action="index.php" style="display:inline">
                                <input type="hidden" name="action" value="complete">
                                <input type="hidden" name="id" value="<?= $task->id ?>">
                                <button type="submit" title="Позначити виконаним">✓</button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" action="index.php" style="display:inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $task->id ?>">
                            <button type="submit" title="Видалити">✕</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>">Назад</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>">Вперед</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?> 
        <?php endif; ?>
</div>
</body>
</html>