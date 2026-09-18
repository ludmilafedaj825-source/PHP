<?php
$appName = "Task Manager";
$taskTitle = "Вивчити PHP";
$taskTimeEstimate = 10;
$isCompleted = false;
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .task-done{
            color: green;
            text-decoration:underline;
        }
        .task-pending{
            color: gray;
            text-decoration: underline;
        }
    </style>
</head>
<body>
<main>
    <header>
        <h1><?= $appName; ?></h1>
        <ul>
            <li> <?= $taskTitle; ?></li>
            <li class="<?= $isCompleted ? 'task-done' : 'task-pending' ?>">
                <?php if ($isCompleted): ?>
                    <p> ✔️ Виконано </p>
                <?php else: ?>
                    <p> 🕒 В процесі </p>
                <?php endif; ?>
            </li>
            <li> <?= $taskTimeEstimate ;?> </li>
        </ul>
    </header>
</main>
</body>
</html>