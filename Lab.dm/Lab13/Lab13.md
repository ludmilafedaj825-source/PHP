```php

    public function findAllByUser(int $userId, int $page = 1, int $perPage = 5): array
    {
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT id, user_id, title, is_completed, deadline, created_at, updated_at
                FROM tasks 
                WHERE user_id = :userId 
                ORDER BY id 
                DESC LIMIT :limit OFFSET :offset
        ';

        $stmt = $this->pdo->prepare($sql);
        // При вимкненій емуляції параметри LIMIT та OFFSET мають бути строго цілими числами
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        return array_map(fn(array $row) => Task::fromArray($row), $rows);
    }

     public function add(int $userId, string $title, ?string $deadline = null): Task
    {   
        $errors=[];
        $trimmedTitle = trim($title);

        if(empty($trimmedTitle)){
            $errors['title'] = "Назва завдання є обов'язковою для заповнення.";
        } elseif(mb_strlen($trimmedTitle)<3){
            $errors['title'] = "Назва завдання має містити не менше трьох символів.";
        }

        if(!empty($deadline) && strtotime($deadline) === false){
                $errors['deadline'] = "Вказано некоректну дату дедлайну.";
            }

        if(!empty($errors)){
            throw new ValidationException($errors);
        }

        $sql = 'INSERT INTO tasks (user_id, title, is_completed, deadline, created_at) 
                VALUES (:user_id, :title, 0, :deadline, NOW())
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':title' => $trimmedTitle,
            ':deadline' => !empty($deadline) ? $deadline : null,
        ]);
        
        $newId = (int) $this->pdo->lastInsertId();

        $createdTask = $this->findByIdAndUser($newId, $userId);
        if($createdTask === null){
            throw new RuntimeException("Не вдалося отримати створене завдання з бази даних.");
        }
        return $createdTask;
    }

    public function complete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare('
        UPDATE tasks 
        SET is_completed = 1, updated_at = NOW()
         WHERE id = :id AND user_id = :user_id
        ');

        $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId
        ]);

        if ($stmt->rowCount() === 0) {
            // Перевіряємо, чи взагалі існує завдання
            if ($this->findByIdAndUser($id, $userId) === null) {
                throw new TaskNotFoundException($id);
            }
        }

        return true;
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM tasks WHERE id = :id AND user_id = :user_id');
        $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId
        ]);

        if ($stmt->rowCount() === 0) {
            throw new TaskNotFoundException($id);
        }

        return true;
    }
```
# Контрольні запитання

1)Що таке розширення PDO у PHP та які суттєві переваги воно надає у порівнянні з застарілим mysql_* та процедурним стилем mysqli?

PDO (PHP Data Objects) це об'єктно-орієнтований інтерфейс для роботи з різними СКБД через єдиний API. Він підтримує підготовлені вирази, які захищають від SQL-ін'єкцій, повідомляє про помилки через винятки та працює з багатьма базами даних, що не пітримують функцій mysql_*. Також mysql_* застарілий і видалений у PHP 7.

2)Яким чином підготовлені вирази (Prepared Statements) гарантують захист від SQL-ін’єкцій, і чому конкатенація змінних безпосередньо в SQL-рядок вважається критичною вразливістю?

Спочатку на сервер БД надсилається шаблон запиту з плейсхолдерами, і структура запиту фіксується. Потім окремо передаються значення, які сервер сприймає лише як дані, а не як частину SQL-коду. При конкатенації ввід користувача стає частиною самого SQL-рядка. Зловмисник може отримати чужі дані, змінити або видалити їх, тому це вважається критичною вразливістю.

3)Чому для параметрів LIMIT та OFFSET у методі TaskRepository::findAllByUser() використовується метод bindValue() з типом PDO::PARAM_INT, а не передача масиву значень у execute()?

LIMIT та OFFSET приймають лише цілі числа, а через  execute() усі значення за замовчуванням передаються як рядки, що дасть синтаксичну помилку MySQL, а метод bindValue() дозволяє вказати тип параметра через третій аргумент, тобто явно задає тип параметра, який буде передаватися.

4)Для чого призначений метод PDO::lastInsertId(), у який момент його необхідно викликати і що він повертає?

Метод PDO::lastInsertId() призначений для повертання автоінкрементованого id останнього доданого запису у поточному з'єднанні. Викликати потрібно одразу після успішного execute() вставки. Повертає рядок, тому в коді його приводять до int. Потім за цим id отримаують щойно створений запис.

5)Як саме реалізується постранична пагінація в реляційних базах даних? Поясніть формулу розрахунку зміщення: offset = (page - 1) * perPage.

Пагінація реалізується через LIMIT та OFFSET. LIMIT бере потрібну кількість рядків, а через OFFSET пропускаються попередні. Формула (page - 1) * perPage означає: перед поточною сторінкою є page - 1 повних сторінок по perPage записів, і саме стільки треба пропустити. Для стабільного результату потрібен ORDER BY.