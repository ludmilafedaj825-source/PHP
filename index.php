<?php
$appName = "Task Manager";
$taskTimeEstimate = 10;
$tasks = [
    [
    'id'=> 1,
    'title' => 'Виконати лабораторну роботу №5',
    'priority' => 'High',
    'is_completed'=> true
    ],
    [
    'id'=> 2,
    'title' => 'Прочитати лекцію №5',
    'priority' => 'Low',
    'is_completed'=> true
    ],
    [
    'id'=> 3,
    'title' => 'Відповісти на контрольні запитання',
    'priority' => 'Medium',
    'is_completed'=> false
    ],
    [
    'id'=> 4,
    'title' => 'Підготуватися до екзамену',
    'priority' => 'High',
    'is_completed'=> false
    ]
]; // асоціативний масив


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
        <p> Нинішній час: <?= getCurrentGreeting() ?></p>
        <ol>
            <?php foreach ($tasks as $task): ?>
                <li>
                    <?= formatTitle($task['title']) ?> <br>
                    Пріоритет: <?=  $task['priority'] ?> <br>
                    <span class="<?= $task['is_completed'] ? 'task-done' : 'task-pending' ?>">
                        Статус: <?= $task['is_completed'] ? '✔️ Виконано' : '🕒 У процесі' ?>  </span> <br>
                    Час: <?= $taskTimeEstimate ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </header>
</main>
</body>
</html>