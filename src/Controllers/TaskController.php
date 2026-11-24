<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\TaskService;
use InvalidArgumentException;

class TaskController extends BaseController
{
    public function __construct(
        private TaskService $taskService
    ) {}

    public function index(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $data = $this->taskService->getTasksPaginated($userId, $page, perPage: 5);

        $this->render('tasks/index', [
            'pageTitle'  => 'Мої завдання — TaskFlow',
            'tasks'      => $data['tasks'],
            'page'       => $data['currentPage'],
            'totalPages' => $data['totalPages'],
            'totalTasks' => $data['totalTasks'],
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();

        $this->render('tasks/create', [
            'pageTitle' => 'Нове завдання — TaskFlow',
            'errors'    => [],
            'old'       => [],
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];
        $title = $_POST['title'] ?? '';
        $deadline = $_POST['deadline'] ?? '';

        try {
            $this->taskService->createTask($userId, $title, !empty($deadline) ? $deadline : null);
            $this->redirect('/');
        } catch (InvalidArgumentException $e) {
            $this->render('tasks/create', [
                'pageTitle' => 'Нове завдання — TaskFlow',
                'errors'    => [$e->getMessage()],
                'old'       => ['title' => $title, 'deadline' => $deadline],
            ]);
        }
    }

    public function complete(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];
        $taskId = (int) ($_POST['id'] ?? 0);

        $this->taskService->toggleTask($taskId, $userId);
        $this->redirect('/');
    }

    public function delete(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];
        $taskId = (int) ($_POST['id'] ?? 0);

        $this->taskService->deleteTask($taskId, $userId);
        $this->redirect('/');
    }
}