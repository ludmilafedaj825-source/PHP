```php
<?php
declare(strict_types=1);

$host     = '127.0.0.1';
$port     = '3306';
$dbname   = 'task_manager';
$username = 'root';
$password = ''; 
$charset  = 'utf8mb4';

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

$tables = [];
$tasks = [];
$connectionStatus = false;
$errorMessage = null;
$serverInfo = '';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $connectionStatus = true;
    $serverInfo = (string)$pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

    $tablesStmt = $pdo->query('SHOW TABLES');
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

    if (in_array('tasks', $tables, true)) {
        $tasksStmt = $pdo->query('SELECT * FROM tasks ORDER BY id ASC');
        $tasks = $tasksStmt->fetchAll();
    }
} catch (PDOException $e) {

    $connectionStatus = false;
    $errorMessage = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Діагностика підключення до БД (MySQL PDO)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="card">
        <h1>Діагностика підключення до БД MySQL</h1>
        <p>Перевірка зв'язку вебдодатка на PHP із базою даних <code><?= htmlspecialchars($dbname) ?></code> через інтерфейс PDO.</p>

        <?php if ($connectionStatus): ?>
            <div class="alert-success">
                <strong>✓ Успішне підключення через PDO!</strong><br>
                PHP успішно з'єднався із сервером бази даних (версія MySQL: <code><?= htmlspecialchars($serverInfo) ?></code>).
            </div>

            <h2>1. Таблиці у базі даних (запит <code>SHOW TABLES</code>)</h2>
            <?php if (!empty($tables)): ?>
                <ul class="tables-list">
                    <?php foreach ($tables as $table): ?>
                        <li>📁 <?= htmlspecialchars($table) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>У базі даних немає створених таблиць. Перевірте виконання Кроків 3 та 4.</p>
            <?php endif; ?>

            <h2>2. Тестові записи з таблиці <code>tasks</code> (запит <code>SELECT * FROM tasks</code>)</h2>
            <?php if (!empty($tasks)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Назва завдання</th>
                            <th>User ID</th>
                            <th>Пріоритет</th>
                            <th>Опис</th>
                            <th>Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><strong>#<?= htmlspecialchars((string)($task['id'] ?? '')) ?></strong></td>
                                <td><?= htmlspecialchars((string)($task['title'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($task['user_id'] ?? '')) ?></td>
                                <td>
                                    <?php
                                    $p = $task['priority'] ?? 'Medium';
                                    $badgeClass = match ($p) {
                                        'High' => 'badge-danger',
                                        'Low' => 'badge-info',
                                        default => 'badge-warning',
                                    };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars((string)$p) ?></span>
                                </td>
                                <td><?= htmlspecialchars((string)($task['description'] ?? '—')) ?></td>
                                <td>
                                    <?php if (!empty($task['is_completed'])): ?>
                                        <span class="badge badge-success">Виконано</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">В процесі</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>У таблиці <code>tasks</code> поки немає записів. Додайте 2–3 тестові завдання через вкладку «Вставити» (Insert) у phpMyAdmin.</p>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert-error">
                <strong>✗ Помилка підключення до СКБД!</strong><br>
                Не вдалося встановити зв'язок із сервером MySQL.<br>
                <em>Повідомлення PDO:</em> <?= htmlspecialchars($errorMessage ?? 'Невідома помилка') ?>
            </div>

            <p><strong>Поради для діагностики:</strong></p>
            <ul>
                <li>Переконайтеся, що модуль <strong>MySQL</strong> запущено в XAMPP Control Panel (порт 3306 підсвічений зеленим);</li>
                <li>Перевірте коректність назви бази даних у DSN (має бути <code>task_manager</code>);</li>
                <li>Перевірте параметри облікового запису (за замовчуванням користувач <code>root</code> із порожнім паролем).</li>
            </ul>
        <?php endif; ?>

        <div class="nav-links">
            <a href="index.php">← Повернутися до «Менеджера завдань» (index.php)</a>
        </div>
    </div>

</body>
</html>
```

# Контрольні запитання
1) Що таке СКБД (DBMS)? У чому полягає різниця між безпосередньо сервером бази даних (MySQL) та веб-інтерфейсом phpMyAdmin?

СКБД (система керування базами даних) це програмне забезпечення для створення, зберігання, зміни та отримання даних, яка забезпечує їх цілісність, безпеку та одночасний доступ багатьом користувачам. MySQL це сам сервер бази даних, який лише зберігає дані і виконує SQL-запити. phpMyAdmin у свою чергу це веб-інтерфейс для PHP, який надсилає запити до сервера. Без сервера phpMyAdmin нічого не покаже, а сервер працює і без нього.

2) Для чого використовується атрибут Auto Increment (A_I), який призначається первинному полю id?

Атрибут автоматично присвоює кожному новому запису наступне унікальне значення в полі ID. Це дає змогу не вводити вручну індентифікатори і запобігає конфліктам при одночасному додаванні даних.

3) Що таке PRIMARY KEY (Первинний ключ) і для чого він критично потрібен у кожній реляційній таблиці?

Первинний ключ це поле, значення якого завжди унікальне і не може бути порожнім. Він ідентифікує кожен рядок таблиці. За допомогою нього створюються зв'язки між таблицями, а також без нього неможливо надійно видаляти та змінювати записи.

4) Назвіть щонайменше 2 текстові типи даних у MySQL (VARCHAR, TEXT) та поясніть різницю між ними.

VARCHAR зберігає рядок змінної довжини з обмеженням, що задається при створенні атрибута, і може мати значення за замовчуванням. TEXT призначений для довгих текстів без заданого ліміту довжини, не може мати значення за замовчуванням.

5) Чому для збереження паролів виділено стовпець VARCHAR(255), а не поле меншої фіксованої довжини?

Тому що  в базі зберігається не сам пароль, а його хешування. Хеш завжди довше за початковий пароль, а з часом хешування може змінитися і результат буде довшим. Якщо поле буде закоротке, то хеш обріжеться і користувач не зможе увійти.

6) Що таке інтерфейс PDO (PHP Data Objects) у PHP та які суттєві переваги він надає порівняно із застарілим набором функцій mysql_*?

PDO (PHP Data Objects) це об'єктно-орієнтований інтерфейс для роботи з різними СКБД через єдиний API. Він підтримує підготовлені вирази, які захищають від SQL-ін'єкцій, повідомляє про помилки через винятки та працює з багатьма базами даних, - що не пітримують функцій mysql_*.
7) Що таке рядок DSN (Data Source Name) та які обов’язкові компоненти входять до його складу під час з’єднання з MySQL?

DSN (Data Source Name) це рядок, який описує, до якого джерела даних і як підключатися. Для MySQL обов'язковими є назва драйвера, адреса сервера та назва бази даних, також додатково можна вказувати порт і кодування.

8) Чому операцію встановлення з’єднання з базою даних через PDO необхідно огортати в блок try...catch (PDOException)?

Підключення може не вдатися з причин, не пов'язаних із самим кодом: сервер вимкнений, неправильні облікові дані або назва бази. Блок catch дозволяє перехопити виняток і показати зрозуміле повідомлення про помилку, не ламаючи решту програми. Якщо ж його не буде і виникне помилка, то дані, які мають бути захищені, будуть відкриті. 
