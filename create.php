<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/Task.php';
require_once __DIR__ . '/TaskRepository.php';
require_once __DIR__ . '/Exceptions.php';
 
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$appName = "Task Manager";
$title = '';
$deadline = '';
$errors = [];

if($_SERVER['REQUEST_METHOD']==='POST') {
 // ?? '' — оператор об'єднання з null: якщо в $_POST чомусь немає такого ключа
    $title = trim($_POST['title'] ?? '');
    $deadline = trim($_POST['deadline'] ?? '');

   try {
        $repo = new TaskRepository(__DIR__ . '/tasks.json');
        $repo->add($title, $deadline ?: null);
        // Параметр ?created=1 повідомить головній сторінці, що все успішно збережено!
        header('Location: index.php?created=1');
        exit; // Зупиняємо виконання скрипта після перенаправлення
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
    } catch (TaskManagerException $e) {
        $errors = ['Помилка програми: ' . $e->getMessage()];
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
        <?php include 'navigation.php' ?>
        <a href="index.php"> Повернутися до списку </a>
        <h1>Нове завдання</h1>
    </header>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <p>Виникли помилки при збереженні завдання.</p>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
     <form action="create.php" method = "POST">
            <div class="field">
                <label for="title">Назва завдання:  <span class="required">*</span> </label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    value="<?= htmlspecialchars($title) ?>">
            </div>

            <div class="field"> 
                <label for="deadline">Дедлайн:</label>
                <input type="date"
                    id="deadline"
                    name="deadline"
                    value="<?= htmlspecialchars($deadline)?>">
            </div>
            <button type="submit"> Зберегти </button>
    </form>
</main>
</body>
</html>