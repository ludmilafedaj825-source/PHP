<?php
session_start();

// Перевіряємо, чи існує прапорець 'is_logged' і чи він не порожній
if (empty($_SESSION['is_logged_in'])) { 
    header('Location: index.php');
    exit;
}

// Читання кукі для отримання часу останнього входу
$lastLoginDate = "Невідомо";
if (isset($_COOKIE['user_time'])) {
    $lastLoginDate = date('d.m.Y H:i:s', (int) $_COOKIE['user_time']);
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Особистий кабінет</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>   
    <?php 
    include 'navigation.php'; 
    ?>
    <h1>Особистий кабінет</h1>
    
    <p>Вітаємо в секретній зоні! Ви успішно пройшли перевірку даних.</p>
    
    <p>Ваш ID користувача: <strong><?= htmlspecialchars((string) ($_SESSION['uid'] ?? 'Невідомо')) ?></strong></p>
    
    <p>Дата останнього входу: <strong><?= $lastLoginDate ?></strong></p>
    
    <hr>  

    <h3>Інформація про сесію</h3>
    <p>
        Ця сторінка захищена від несанкціонованого доступу. Тільки авторизовані користувачі можуть її бачити. 
        Щоб завершити сеанс та вийти, скористайтеся кнопкою "Вихід" у навігаційному меню.
    </p>

</body>
</html>