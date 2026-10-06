<?php
$appName = "Task Manager";
$taskTimeEstimate = 10;
$file = 'data.json';
$tasks = []; // асоціативний масив

if(file_exists($file)) {
    $content = file_get_contents($file);
    $decoded = json_decode($content, true);

    if(is_array($decoded)) {
        $tasks= $decoded;
    }
}
// Перевіряємо GET-параметр у посиланні:
// Якщо користувача перенаправило з create.php?created=1 — покажемо повідомлення про успіх!
$is_created = isset($_GET['created']) && $_GET['created'] === '1';
//сортування масиву з пріоритетом
usort($tasks, function($a, $b){
    $priorities = [
        'High' => 1,
        'Medium' => 2,
        'Low' => 3
    ];

    $weightA = $priorities[$a['priority']] ?? 99;
    $weightB = $priorities[$b['priority']] ?? 99;

    return $weightA <=> $weightB;
});

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
    <style>
        .task-done{
            color: green;
            text-decoration:underline;
        }
        .task-pending{
            color: gray;
        }
    </style>
</head>
<body>
<main>
    <header>
        <h1><?= $appName; ?></h1>
        <a href="create.php">Додати нове завдання</a>
        <p> Нинішній час: <?= getCurrentGreeting() ?></p>
    </header> 
    <?php if ($is_created): ?> 
        <div class="alert alert-success" >
            <p>Завдання успішно збережено!</p>
        </div>
    <?php endif; ?>
        <ol>
            <?php foreach ($tasks as $task): ?>
                <?php $is_completed = $task['is_completed'] ?? false; ?> 
                <li>
                    <?= formatTitle($task['title']) ?> <br>
                    Пріоритет: <?=  $task['priority'] ?> <br>
                    <span class="<?= $is_completed ? 'task-done' : 'task-pending' ?>">
                        Статус: <?= $is_completed ? '✔️ Виконано' : '🕒 У процесі' ?>  </span> <br>
                    Час: <?= $taskTimeEstimate ?>
                </li>
            <?php endforeach; ?>
        </ol>
</main>
</body>
</html>