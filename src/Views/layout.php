<?php

use BotGear\Core\Csrf;

$user = $auth->user();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> - <?= h($appName) ?></title>
    <link rel="stylesheet" href="assets/styles.css">
    <script src="assets/app.js" defer></script>
</head>
<body>
    <header class="topbar">
        <a class="brand" href="<?= h(url('home')) ?>">
            <span class="brand-mark">BG</span>
            <span>
                <strong><?= h($appName) ?></strong>
                <small>витрина для Telegram-магазина</small>
            </span>
        </a>

        <nav class="nav" aria-label="Основная навигация">
            <a class="<?= $currentPage === 'home' ? 'active' : '' ?>" href="<?= h(url('home')) ?>">Главная</a>
            <a class="<?= $currentPage === 'catalog' ? 'active' : '' ?>" href="<?= h(url('catalog')) ?>">Каталог</a>
            <?php if ($user !== null): ?>
                <a class="<?= $currentPage === 'orders' ? 'active' : '' ?>" href="<?= h(url('orders')) ?>">Мои заказы</a>
            <?php endif; ?>
            <?php if ($auth->isAdmin()): ?>
                <a class="<?= str_starts_with($currentPage, 'admin') ? 'active' : '' ?>" href="<?= h(url('admin')) ?>">Админка</a>
            <?php endif; ?>
        </nav>

        <div class="account">
            <?php if ($user === null): ?>
                <a class="button ghost" href="<?= h(url('login')) ?>">Войти</a>
                <a class="button" href="<?= h(url('register')) ?>">Регистрация</a>
            <?php else: ?>
                <span class="account-name"><?= h($user['name']) ?></span>
                <form class="logout-form" method="post" action="<?= h(url('logout')) ?>">
                    <?= Csrf::input() ?>
                    <button class="button ghost" type="submit">Выйти</button>
                </form>
            <?php endif; ?>
        </div>
    </header>

    <main class="page">
        <?php foreach ($flashMessages as $message): ?>
            <div class="flash <?= h($message['type']) ?>">
                <?= h($message['message']) ?>
            </div>
        <?php endforeach; ?>

        <?= $content ?>
    </main>

    <footer class="footer">
        <span><?= h($appName) ?>, учебный PHP-проект</span>
        <span>PHP, сессии, хеширование паролей, файловая база данных</span>
    </footer>
</body>
</html>
