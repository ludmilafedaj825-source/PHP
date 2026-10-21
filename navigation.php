<?php ?>

<nav>
    <ul>
        <li>
            <a href="index.php">Головна</a>
        </li>
        <?php
        if(!empty($_SESSION['is_logged_in'])):
        ?>
        <li><a href="cabinet.php">Кабінет</a></li>
        <li><a href="create.php">Додати нове завдання</a></li>
        <li><a href="logout.php">Вихід</a></li>
        <?php endif; ?>
    </ul>    
</nav>