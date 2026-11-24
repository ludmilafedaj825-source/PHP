<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Task;
use PDO;

class TaskRepository implements TaskStorageInterface
{
    public function __construct(private PDO $pdo) {}

    public function add(int $userId, string $title, ?string $deadline = null): int
    {
        $sql = "INSERT INTO tasks (user_id, title, is_completed, deadline, created_at) 
                VALUES (:user_id, :title, 0, :deadline, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id'  => $userId,
            ':title'    => $title,
            ':deadline' => $deadline,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findAllByUser(int $userId, int $page = 1, int $perPage = 5): array
    {
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT id, user_id, title, is_completed, deadline, created_at, updated_at 
                FROM tasks 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $tasks = [];
        while ($row = $stmt->fetch()) {
            $tasks[] = new Task(
                id: (int) $row['id'],
                userId: (int) $row['user_id'],
                title: $row['title'],
                isCompleted: (bool) $row['is_completed'],
                deadline: $row['deadline'],
                createdAt: $row['created_at'],
                updatedAt: $row['updated_at']
            );
        }

        return $tasks;
    }

    public function countByUser(int $userId): int
    {
        $sql = "SELECT COUNT(*) FROM tasks WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    public function complete(int $id, int $userId): bool
    {
        $sql = "UPDATE tasks 
                SET is_completed = CASE WHEN is_completed = 1 THEN 0 ELSE 1 END,
                    updated_at = NOW() 
                WHERE id = :id AND user_id = :user_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id, ':user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id, int $userId): bool
    {
        $sql = "DELETE FROM tasks WHERE id = :id AND user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id, ':user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }
}