<?php
declare(strict_types=1);

/** Виняток, що виникає при зверненні до завдання за неіснуючим ID. */
class TaskNotFoundException extends TaskManagerException
{
    public function __construct(int $taskId)
    {
        parent::__construct("Завдання з ідентифікатором #{$taskId} не знайдено.", 404);
    }
}