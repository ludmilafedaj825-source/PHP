<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Task;

interface TaskStorageInterface
{
    public function add(int $userId, string $title, ?string $deadline = null): int;
    
    /**
     * @return Task[]
     */
    public function findAllByUser(int $userId, int $page = 1, int $perPage = 5): array;
    
    public function countByUser(int $userId): int;
    
    public function complete(int $id, int $userId): bool;
    
    public function delete(int $id, int $userId): bool;
}