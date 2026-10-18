<?php
session_start();
// Видаляємо всі дані сесії
session_unset();
// Знищуємо сесію
session_destroy();
// Перенаправляємо користувача на сторінку входу
header('Location: login.php');
exit;
?>