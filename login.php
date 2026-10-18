<?php
session_start();
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
            setcookie('user_time', time(), $expiredDate, '/');
            header('Location: index.php');
            exit;
        } else{
            $error = "Невірний логін або пароль!";
        }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login</title>
    </head>
    <body>
        <?php 
        include 'navigation.php';
        if(isset($error)) {
            echo "<p style='color:red;'>$error</p>";
        }
        ?>
        <h1>Login</h1>
        <form method="POST" action="login.php">
            <label for="username">Username: </label>
            <input type="text" name="username" id="username" required><br><br>

            <label for="password">Password: </label>
            <input type="password" name="password" id="password" required><br><br>

            <button type="submit" name="Login">Увійти</button>
        </form>
        <h1>Помилка авторизації</h1>
        <p>
            <a href="index.php">Повернутися на головну сторінку</a>
        </p>
    </body>
</html>          
