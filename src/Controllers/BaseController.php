<?php
declare(strict_types=1);

abstract class BaseController
{
    /**
     * Рендеринг шаблону всередині базового макета (Layout)
     *
     * @param string $view Відносний шлях до файлу шаблону (наприклад, 'tasks/index')
     * @param array<string, mixed> $data Змінні, що передаються у представлення
     */
    protected function render(string $view, array $data = []): void
    {
        // Перетворюємо ключі асоціативного масиву на окремі локальні змінні
        extract($data);

        $viewFile = __DIR__ . '/../../views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new RuntimeException("Файл представлення не знайдено: {$viewFile}");
        }

        // Запускаємо буферизацію виводу для отримання згенерованого HTML сторінки
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Підключаємо загальний макет, куди підставляється змінна $content
        $layoutFile = __DIR__ . '/../../views/layouts/main.php';

        if (!file_exists($layoutFile)) {
            throw new RuntimeException("Файл макета не знайдено: {$layoutFile}");
        }

        require $layoutFile;
    }

    /**
     * Перенаправлення користувача на інший URL
     */
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * Перевірка автентифікації користувача
     */
    protected function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }
}