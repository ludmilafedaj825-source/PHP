<?php
declare(strict_types=1);

class AuthController extends BaseController
{
    /**
     * Відображення форми входу
     */
    public function showLogin(): void
    {
        // Якщо користувач уже авторизований — відправляємо на головну
        if (!empty($_SESSION['user_id'])) {
            $this->redirect('/');
        }

        $this->render('auth/login', [
            'pageTitle' => 'Вхід у систему — TaskFlow',
            'error'     => null,
        ]);
    }

    /**
     * Обробка авторизації користувача
     */
    public function login(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $this->render('auth/login', [
                'pageTitle'   => 'Вхід у систему — TaskFlow',
                'error'       => 'Будь ласка, заповніть усі поля форми.',
                'oldUsername' => $username,
            ]);
            return;
        }

        // У навчальних цілях для ЛР №14 встановлюємо сесію користувача id=1
        // Повна верифікація паролів через password_verify() реалізується в ЛР №16
        $_SESSION['user_id'] = 1;
        $_SESSION['user_name'] = htmlspecialchars($username);

        $this->redirect('/');
    }

    /**
     * Завершення сесії користувача (Logout)
     */
    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        $this->redirect('/login');
    }
}
