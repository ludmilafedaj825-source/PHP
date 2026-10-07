<?php
$appName = "Task Manager";
$title = '';
$description = '';
$priority = '';
$errors = [];
$debug_mode = true;

if($_SERVER['REQUEST_METHOD']==='POST') {
 // ?? '' — оператор об'єднання з null: якщо в $_POST чомусь немає такого ключа
    $raw_title = trim($_POST['title'] ?? '');
    $raw_description = trim($_POST['description'] ?? '');
    $raw_priority = trim($_POST['priority'] ?? '');

    $title=htmlspecialchars($raw_title, ENT_QUOTES, 'UTF-8');
    $description=htmlspecialchars($raw_description, ENT_QUOTES, 'UTF-8');
    $priority=htmlspecialchars($raw_priority, ENT_QUOTES, 'UTF-8');//захист від XSS-атак
//перевірка назви
    if(empty($title)){
        $errors[]="Поле «Назва завдання» є обов'язковим до заповнення!";
    }

    if(empty($description)){
        $errors[]="Поле «Опис завдання» є обов'язковим до заповнення!";
    }

    $allowed_priorities=['Low', 'Medium', 'High'];
//in_array - перевіряє чи є значення одним із дозволених
    if(empty($priority) || !in_array($priority, $allowed_priorities, true)){
        $errors[]="Будь ласка, оберіть пріоритет із запропонованих: Low, Medium, High!";
    }
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $maxSize = 20 * 1024 * 1024;
            if ($_FILES['avatar']['size'] > $maxSize) {
                $errors[] = "Файл завеликий!";
            }

            $allowedExtensions = ['jpg', 'jpeg', 'png'];
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if(!in_array($ext, $allowedExtensions, true)){
                $errors[] = "Дозволено завантажувати файли тільки у форматі: jpg, jpeg, png.";
            }
        }

    if(empty($errors)){
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK){
            move_uploaded_file($_FILES['avatar']['tmp_name'], 'uploads/' . $_FILES['avatar']['name']);
        }

        $file='data.json';
        $tasks=[]; // асоціативний масив

        
        if(file_exists($file)){
            // Зчитування всього вмісту файлу у вигляді тексту
            $json_content = file_get_contents($file);
            // Перетворення JSON-тексту в звичайний PHP-масив.
            // Параметр true означає: перетворити об'єкти на асоціативні масиви.
            $decoded = json_decode($json_content, true);

            if(is_array($decoded)){
            $tasks= $decoded;
            }
        }

        $tasks[] = [
            'id' => uniqid(),
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'created_at' => date('Y-m-d H:i:s')
        ];
        // Перетворюємо масив назад у текстовий формат JSON:
        // JSON_UNESCAPED_UNICODE — щоб українські літери залишалися гарними літерами, а не \u041f...
        // JSON_PRETTY_PRINT — щоб текст у файлі був із відступами, зручний для читання людиною.
        $json_data = json_encode($tasks, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        // Записування оновлених даних у файл:
        // LOCK_EX — блокує файл під час запису, щоб два користувачі не зіпсували файл одночасно.
        file_put_contents($file, $json_data, LOCK_EX);
        // Перенаправляємо користувача назад на головну сторінку index.php
        if(!$debug_mode){
            // Параметр ?created=1 повідомить головній сторінці, що все успішно збережено!
            header('Location: index.php?created=1');
            exit;
        }  // Зупиняємо виконання скрипта після перенаправлення
    }
}

?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $appName ?></title>
    <style>
        .task-done{
            color: green;
            text-decoration:underline;
        }
        .task-pending{
            color: gray;
        }
        .alert.alert-danger{
            text-align: center;
            color: red;
        }
    </style>
</head>
<body>
<main>
    <header>
        <h1><?= $appName; ?></h1>
        <a href="index.php"> Повернутися до списку </a>
    </header>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <p>Виникли помилки при збереженні завдання.</p>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
     <form action="create.php" method = "POST" enctype="multipart/form-data">
            <div>
                <label for="title">Назва завдання:  <span class="required">*</span> </label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    placeholder="Введіть назву завдання"
                    value="<?= $title ?? '' ?>">
            </div>

            <div> 
                <label for="description">Опис завдання: <span class="required">*</span></label>
                <textarea
                    id="description"
                    name="description"
                    placeholder="Введіть опис завдання"><?=  $description ?? '' ?></textarea>
            </div>
            <div>
                <label for="priority">Пріоритет завдання: <span class="required">*</span></label>
                <select id="priority" name="priority">
                    <option  value="" disabled <?= empty($priority) ? 'selected' : '' ?>> Оберіть варіант</option>
                    <option value="Low"<?= $priority === 'Low' ? 'selected' : '' ?>>Low(Низький)</option>
                    <option value="Medium" <?= $priority === 'Medium' ? 'selected' : '' ?>>Medium(Середній)</option>
                    <option value="High" <?= $priority === 'High' ? 'selected' : '' ?>>High(Високий)</option>
                </select>
            </div>
            <input type="file" name="avatar" accept="image/png, image/jpeg">
            <button type="submit"> Зберегти </button>
    </form>
    <?php if ($_SERVER['REQUEST_METHOD']==='POST' && empty($errors)): ?>
        <strong> Демонстрація прехоплення даних через var_dump($_POST)(Лаб №6): </strong>
        <pre> <?php var_dump($_POST); ?> </pre>
        <pre> <?php var_dump($_FILES) ?></pre>
    <?php endif; ?>
</main>
</body>
</html>