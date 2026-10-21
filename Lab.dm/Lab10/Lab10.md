# Код файлу Task.php
```php
<?php
declare(strict_types=1);

class Task
{
    /**
     * Конструктор: створює нове завдання та одразу записує дані у властивості класу.
     * Якщо назва порожня, кидає виняток, тому некоректне завдання створити неможливо.
     */
    public function __construct(
        public readonly int $id,
        private string $title,
        private bool $isCompleted = false,
        private ?string $deadline = null,
    ){
       if(empty(trim($title))){
        throw new InvalidArgumentException("Назва завдання не може бути порожньою!");
       }
    }

    /**Повертає унікальний номер id*/
    public function getId(): int {return $this -> id;}

     /**Повертає назву завдання*/
    public function getTitle(): string {return $this -> title;}

     /**Повертає true, якщо завдання виконано, false, якщо ні*/
    public function isCompleted(): bool {return $this -> isCompleted;}

     /**Повертає дедлайн у вигляді рядка, або null, якщо не вказано*/
    public function getDeadline(): ?string {return $this -> deadline;}

    /**Перевіряє, чи прострочене завдання. Якщо завдання виконане або без дедлайну, то його прострочити не може, в інших випадках порівнюється дедлайн із поточним часом.*/
    public function isOverdue(): bool
    {
        if($this ->isCompleted || $this ->deadline===null){
            return false;
        }
        return strtotime($this->deadline) < time();
    }

    /** Позначає завдання виконаним. */
    public function complete(): void {$this -> isCompleted = true;}

     /** Повертає текстовий статус завдання, який відображається на сторінці. Спочатку перевіряє «Виконано», потім «Прострочено», в іншому випадку повертає «У процесі».*/
    public function getStatusLabel(): string {
        if($this -> isCompleted) return 'Виконано';
        if($this -> isOverdue()) return 'Прострочено';
        return 'У процесі';
    }

    /** Перетворює об'єкт на звичайний масив для JSON-кодування*/
    public function toArray(): array
    {
        return [
            'id' => $this -> id,
            'title' => $this -> title,
            'isCompleted' => $this -> isCompleted,
            'deadline' => $this -> deadline,
        ];
    }

    /** Статичний метод: з масиву (прочитаного з JSON) створює об'єкт Task*/
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            title: $data['title'],
            isCompleted: $data['isCompleted'] ?? false,
            deadline: $data['deadline'] ?? null,
        );
    }
}
?>
```

# Код файлу TaskRepository.php
```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/Task.php';

class TaskRepository
{
    /** @var Task[] */
    private array $tasks = [];

    /**Конструктор: запам'ятовує шлях до файлу з даними та одразу завантажує з нього завдання, тож після створення репозиторію список $tasks уже заповнений.*/
    public function __construct(private string $storageFile)
    {
        $this->load();
    }

    /**Повертає масив усіх завдань. */
    public function findAll(): array  { return $this->tasks; }

    /**Шукає завдання за id: перебирає список і повертає знайдений об'єкт Task. Якщо такого id немає, повертає null.*/
    public function findById(int $id): ?Task
    {
        foreach ($this->tasks as $task) {
            if ($task->getId() === $id) return $task;
        }
        return null;
    }

    /**Створює об'єкт Task з новим id, кладе його у список і зберігає список у файл. Повертає створене завдання.*/
    public function add(string $title, ?string $deadline = null): Task
    {
        $task = new Task($this->generateNextId(), $title, false, $deadline);
        $this->tasks[] = $task;
        $this->save();
        return $task;
    }

    /**Позначає завдання виконаним: знаходить його за id, викликає $task->complete() і зберігає зміни у файл. Повертає false, якщо завдання з таким id не знайдено.*/
    public function complete(int $id): bool
    {
        $task = $this->findById($id);
        if (!$task) return false;
        $task->complete();
        $this->save();
        return true;
    }

     /** Видаляє завдання за id: прибирає його зі списку, перенумеровує масив і зберігає зміни у файл. Повертає true, якщо завдання було видалено, і false, якщо його не знайдено.*/
    public function delete(int $id): bool
    {
        foreach ($this->tasks as $key => $task) {
            if ($task->getId() === $id) {
                unset($this->tasks[$key]);
                $this->tasks = array_values($this->tasks);
                $this->save();
                return true;
            }
        }
        return false;
    }

    /** Рахує, скільки завдань виконано, повертає кількість. */
    public function countCompleted(): int
    {
        return count(array_filter($this->tasks, fn(Task $t) => $t->isCompleted()));
    }

    /** Рахує, скільки завдань прострочено, повертає кількість. */
    public function countOverdue(): int
    {
        return count(array_filter($this->tasks, fn(Task $t) => $t->isOverdue()));
    }

    /** Генерує id для нового завдання: 1, якщо список порожній, інакше найбільший наявний id плюс 1. Метод приватний, бо потрібен лише всередині репозиторію.*/
    private function generateNextId(): int
    {
        if (empty($this->tasks)) return 1;
        return max(array_map(fn(Task $t) => $t->getId(), $this->tasks)) + 1;
    }

    /** Читає файл tasks.json, перетворює JSON на масив і з кожного запису створює об'єкт Task через fromArray(). Якщо файлу немає, список залишається порожнім.*/
    private function load(): void
    {
        if (!file_exists($this->storageFile)) { $this->tasks = []; return; }
        $data = json_decode(file_get_contents($this->storageFile), true) ?? [];
        $this->tasks = array_map(fn(array $item) => Task::fromArray($item), $data);
    }

    /** Перетворює кожне завдання на масив, кодує весь список у JSON і записує його в tasks.json.*/
    private function save(): void
    {
        $data = array_map(fn(Task $t) => $t->toArray(), $this->tasks);
        file_put_contents(
            $this->storageFile,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}
```

# Контрольні запитання
1) Що таке клас і чим він відрізняється від звичайного масиву у PHP? Чому завдання зручніше представити об’єктом класу Task, ніж масивом ['id' => 1, 'title' => '...']?

Клас (Class) — шаблон (blueprint) для створення об'єктів; описує їхні властивості та поведінку. Масив — це впорядкована структура даних, яка зберігає зв'язок між ключами та значеннями. У масиві ['id' => 1, 'title' => '...'] можна помилитися в ключі, записати будь-який тип або забути поле, і PHP цього не помітить. Об'єкт Task має чітко задані типи, перевіряє дані в конструкторі (наприклад, назва не порожня) і вміє сам виконувати дії: complete(), isOverdue(), getStatusLabel(). Тому код читається простіше й надійніше.

2) Що таке private-властивість і навіщо $title у класі Task зроблено private? Як зовнішній код може прочитати її значення?

Private-властивість доступна лише всередині класу, ззовні до неї звернутися не можна. $title зроблено private, щоб ніхто не міг змінити назву в обхід класу. Зовнішній код читає значення через публічний геттер: $task->getTitle(). 

3) Поясніть, що відбувається у рядку public readonly int $id у конструкторі. Чому $id є readonly, а $isCompleted — ні?

У рядку public readonly int $id оголошується властивість $id, задається їй тип int і одразу присвоюється значення з параметра. readonly означає, що значення можна задати лише один раз при створенні, а потім змінити не вийде. $id — це ідентифікатор завдання, його не можна змінювати, інакше втратиться зв'язок із записом. А $isCompleted змінюється, коли користувач виконує завдання, тому вона не readonly.

4) Що робить статичний метод Task::fromArray($data)? Для чого він оголошений як static, а не як звичайний метод?

Task::fromArray($data) бере масив і створює з нього об'єкт Task. Він статичний, бо його викликають тоді, коли об'єкта ще немає: саме цей метод його й створює. Звичайний метод потребує вже наявного об'єкта, тому тут потрібен static.

5) Опишіть «ланцюжок» збереження даних: що відбувається під капотом від моменту виклику $repo->add('Нове завдання') до появи нового рядка у файлі tasks.json?

Репозиторій генерує новий id (завжди +1 до найбільшого наявного), створює об'єкт Task і кладе його до списку всіх завдань. Потім перетворює кожне завдання зі списку на звичайний масив (метод toArray()), а весь список кодує в текст формату JSON. Цей текст записується у файл tasks.json, так у файлі з'являється новий запис.