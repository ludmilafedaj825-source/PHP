<?php
$appName = "Task Manager";
$taskTitle = "Вивчити PHP та зробити лабораторні роботи";
$taskTimeEstimate = 10;
$isCompleted = false;

function formatTitle($text, $maxLength = 20)
{
    return strlen($text) > $maxLength ? substr($text, 0, $maxLength)."..." : $text;
}

function getCurrentGreeting(){
    $hour = date('H');
    if($hour>=6 && $hour<12){
        return "Доброго ранку";
    }
    elseif($hour>=12 && $hour<18){
        return "Добрий день";
    }
    elseif($hour>=18 && $hour<24){
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
    <title><?=  $appName ?></title>
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
            <li> <?= formatTitle($taskTitle) ?></li>
            <li> Нинішній час: <?= getCurrentGreeting() ?> </li>
            <li class="<?= $isCompleted ? 'task-done' : 'task-pending' ?>">
                <?php if ($isCompleted): ?>
                    <p> ✔️ Виконано </p>
                <?php else: ?>
                    <p> 🕒 У процесі </p>
                <?php endif; ?>
            </li>
            <li> <?= $taskTimeEstimate ;?> </li>
        </ul>
    </header>
</main>
</body>
</html>