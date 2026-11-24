<?php
declare(strict_types=1);

namespace App\Models;

trait TimestampableTrait
{
    public ?string $createdAt = null;
    public ?string $updatedAt = null;

    public function initTimestamps(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function touch(): void
    {
        $this->updatedAt = date('Y-m-d H:i:s');
    }
}