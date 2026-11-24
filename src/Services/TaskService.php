<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\TaskStorageInterface;
use InvalidArgumentException;

class TaskService
{
    public function __construct(
        private TaskStorageInterface $repository
    ) {}

    /**
     * Отримання списку завдань користувача з розрахунком пагінації
     *
     * @return array{tasks: array, totalTasks: int, totalPages: int, currentPage: int}
     */
    public function getTasksPaginated(int $userId, int $page = 1, int $perPage = 5): array
    {
        $page = max(1, $page);
        $totalTasks = $this->repository->countByUser($userId);
        $totalPages = (int) ceil($totalTasks / $perPage);
        $tasks = $this->repository->findAllByUser($userId, $page, $perPage);

        return [
            'tasks'       => $tasks,
            'totalTasks'  => $totalTasks,
            'totalPages'  => $totalPages,
            'currentPage' => $page,
        ];
    }

    /**
     * Створення нового завдання з валідацією бізнес-правил
     *
     * @throws InvalidArgumentException
     */
    public function createTask(int $userId, string $title, ?string $deadline): int
    {
        $trimmedTitle = trim($title);

        if (mb_strlen($trimmedTitle) < 3) {
            throw new InvalidArgumentException('Назва завдання повинна містити щонайменше 3 символи.');
        }

        if (mb_strlen($trimmedTitle) > 255) {
            throw new InvalidArgumentException('Назва завдання не може перевищувати 255 символів.');
        }

        $formattedDeadline = null;
        if (!empty($deadline)) {
            $timestamp = strtotime($deadline);
            if ($timestamp === false) {
                throw new InvalidArgumentException('Вказано некоректний формат дати дедлайну.');
            }
            $formattedDeadline = date('Y-m-d H:i:s', $timestamp);
        }

        return $this->repository->add($userId, $trimmedTitle, $formattedDeadline);
    }

    /**
     * Перемикання статусу завдання
     */
    public function toggleTask(int $taskId, int $userId): bool
    {
        if ($taskId <= 0) {
            return false;
        }

        return $this->repository->complete($taskId, $userId);
    }

    /**
     * Видалення завдання
     */
    public function deleteTask(int $taskId, int $userId): bool
    {
        if ($taskId <= 0) {
            return false;
        }

        return $this->repository->delete($taskId, $userId);
    }
}