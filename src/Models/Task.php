<?php
declare(strict_types=1);

namespace App\Models;

class Task
{
    use TimestampableTrait;

    public function __construct(
        public readonly ?int $id,
        public int $userId,
        public string $title,
        public bool $isCompleted = false,
        public ?string $deadline = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt;
    }

    public function isOverdue(): bool
    {
        if ($this->isCompleted || empty($this->deadline)) {
            return false;
        }

        return strtotime($this->deadline) < time();
    }
}