<?php
declare(strict_types=1);
session_start();

if(!empty($_SESSION['is_logged_in'])){
    header('Location: index.php');
    exit;
}

$error = '';

$userName  = 'Admin';
$userPassword = hash('sha256', '12345678');

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if (isset($_POST['username']) &&
        isset($_POST['password']) && 
        $_POST['username']===$userName && 
        hash_equals($userPassword, hash('sha256', $_POST['password']))) {

            $_SESSION['is_logged_in'] = true;
            $_SESSION['user_name'] = 'Адміністратор';
            $_SESSION['uid'] = 99; // Унікальний ідентифікатор користувача
    
            
            $expiredDate = time() + 100;
            setcookie('user_time',  (string) time(), $expiredDate, '/');
            header('Location: cabinet.php');
            exit;
        } else{
            $error = "Невірний логін або пароль!";
        }
}
?>
<!DOCTYPE html>
<html lang="uk">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <?php include 'navigation.php'; ?>
        <h1>Вхід до системи</h1>
        <?php if ($error): ?>
            <p class="alert-danger"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <label for="username">Логін: </label>
            <input type="text" name="username" id="username" required><br><br>

            <label for="password">Пароль: </label>
            <input type="password" name="password" id="password" required><br><br>

            <button type="submit" name="Login">Увійти</button>
        </form>
        <p>
            <a href="index.php">Повернутися на головну сторінку</a>
        </p>
    </body>
</html>          
