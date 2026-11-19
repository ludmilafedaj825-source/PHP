<?php
declare(strict_types=1);

/** Виняток валідації вхідних даних (може містити кілька помилок). */
class ValidationException extends TaskManagerException
{
    /** @var string[] */
    private array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct('Помилка валідації даних форми: ' . implode(', ', $errors), 422);
    }

    /** @return string[] */
    public function getErrors(): array
    {
        return $this->errors;
    }
}