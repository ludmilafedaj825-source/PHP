<?php
declare(strict_types=1);

require_once __DIR__ . '/Task.php';

interface TaskStorageInterface
{
    /**
     * Повертає список завдань конкретного користувача з урахуванням пагінації.
     *
     * @return Task[]
     */
    public function findAllByUser(int $userId, int $page = 1, int $perPage = 5): array;

    /**
     * Повертає загальну кількість завдань користувача.
     */
    public function countByUser(int $userId): int;

    /**
     * Повертає кількість виконаних завдань користувача.
     */
    public function countCompletedByUser(int $userId): int;

    /**
     * Повертає кількість прострочених завдань користувача.
     */
    public function countOverdueByUser(int $userId): int;

    /**
     * Знаходить завдання за id та перевіряє право власності користувача.
     */
    public function findByIdAndUser(int $id, int $userId): ?Task;

    /**
     * Додає нове завдання до бази даних.
     *
     * @throws ValidationException якщо назва або дедлайн не проходять валідацію
     */
    public function add(int $userId, string $title, ?string $deadline = null): Task;

    /**
     * Позначає завдання як виконане.
     *
     * @throws TaskNotFoundException якщо завдання не існує або належить іншому користувачу
     */
    public function complete(int $id, int $userId): bool;

    /**
     * Видаляє завдання користувача з бази даних.
     *
     * @throws TaskNotFoundException якщо завдання не існує або належить іншому користувачу
     */
    public function delete(int $id, int $userId): bool;
}