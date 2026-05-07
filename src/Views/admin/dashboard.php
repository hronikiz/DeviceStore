<?php require BASE_PATH . '/src/Views/admin/_nav.php'; ?>

<section class="page-heading">
    <p class="eyebrow">Панель управления</p>
    <h1>Админ-панель</h1>
    <p>Краткая статистика магазина и быстрые действия.</p>
</section>

<div class="stats-grid">
    <div><strong><?= h($stats['products']) ?></strong><span>товаров</span></div>
    <div><strong><?= h($stats['categories']) ?></strong><span>категорий</span></div>
    <div><strong><?= h($stats['orders']) ?></strong><span>заказов всего</span></div>
    <div><strong><?= h($stats['newOrders']) ?></strong><span>новых заказов</span></div>
    <div><strong><?= h($stats['lowStock']) ?></strong><span>мало на складе</span></div>
    <div><strong><?= h($stats['users']) ?></strong><span>пользователей</span></div>
</div>

<section class="quick-actions">
    <a class="button" href="<?= h(url('admin-product-create')) ?>">Добавить товар</a>
    <a class="button ghost" href="<?= h(url('admin-orders')) ?>">Открыть заказы</a>
    <a class="button ghost" href="<?= h(url('admin-users')) ?>">Создать администратора</a>
</section>
