<?php
declare(strict_types=1);

class Task
{   use TimestampableTrait;

    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        private string $title,
        private bool $isCompleted = false,
        private ?string $deadline = null,
    ){
        if(empty($this->title)){
            throw new InvalidArgumentException('Назва завдання не може бути порожньою.');
        }
    }

    // --- Геттери ---

    public function getTitle(): string {return $this -> title;}
    public function isCompleted(): bool {return $this -> isCompleted;}
    public function getDeadline(): ?string {return $this -> deadline;}

     // --- Бізнес-методи ---
     
    public function isOverdue(): bool
    {
        if($this ->isCompleted || $this ->deadline===null){
            return false;
        }
        return strtotime($this->deadline) < time();
    }

    public function complete(): void {
        $this -> isCompleted = true;
         $this->touch(); // Оновлюємо мітку часу модифікації
    }

    public function getStatusLabel(): string {
        if($this -> isCompleted){
            return 'Виконано';
        } 
        if($this -> isOverdue()){
            return 'Прострочено';
        } 
        return 'У процесі';
    }

    
    // Статичний метод: з масиву (прочитаного з JSON) створює об'єкт Task
   public static function fromArray(array $data): self
    {
        $task = new self(
            id: (int) $data['id'],
            userId: (int) ($data['user_id'] ?? $data['userId'] ?? 0),
            title: (string) $data['title'],
            isCompleted: (bool) ($data['is_completed'] ?? $data['isCompleted'] ?? false),
            deadline: $data['deadline'] ?? null,
        );

        $task->initTimestamps(
            createdAt: $data['created_at'] ?? $data['createdAt'] ?? null,
            updatedAt: $data['updated_at'] ?? $data['updatedAt'] ?? null,
        );

    return $task;
}   
    // Перетворює об'єкт на звичайний масив для JSON-кодування
    public function toArray(): array
    {
        return [
            'id' => $this -> id,
            'userId' => $this -> userId,
            'title' => $this -> title,
            'isCompleted' => $this -> isCompleted,
            'deadline' => $this -> deadline,
            'createdAt' => $this -> getCreatedAt(),
            'updatedAt' => $this -> getUpdatedAt(),
        ];
    }

}
?>
