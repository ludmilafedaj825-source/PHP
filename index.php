<?php
$appName = "Task Manager";
$taskTitle = "Вивчити PHP";
$taskTimeEstimate = 10;
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<main>
    <header>
        <h1><?= $appName; ?></h1>
        <ul>
            <li> <?= $taskTitle; ?></li>
            <li> <?= $taskTimeEstimate ;?> </li>
        </ul>
    </header>
</main>
</body>
</html>