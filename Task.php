<?php
declare(strict_types=1);

require_once __DIR__ . '/TimestampableTrait.php';

class Task
{   use TimestampableTrait;

    public function __construct(
        public readonly int $id,
        private string $title,
        private bool $isCompleted = false,
        private ?string $deadline = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
    ){
        $this->initTimestamps($createdAt, $updatedAt);
    }

    // --- Геттери ---

    public function getId(): int {return $this -> id;}
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

    // Перетворює об'єкт на звичайний масив для JSON-кодування
    public function toArray(): array
    {
        return [
            'id' => $this -> id,
            'title' => $this -> title,
            'isCompleted' => $this -> isCompleted,
            'deadline' => $this -> deadline,
            'createdAt' => $this -> getCreatedAt(),
            'updatedAt' => $this -> getUpdatedAt(),
        ];
    }

    // Статичний метод: з масиву (прочитаного з JSON) створює об'єкт Task
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            title: (string) $data['title'],
            isCompleted: (bool) ($data['isCompleted'] ?? false),
            deadline: $data['deadline'] ?? null,
            createdAt: $data['createdAt'] ?? null,
            updatedAt: $data['updatedAt'] ?? null,
        );
    }
}
?>
