<section class="hero">
    <div class="hero-copy">
        <p class="eyebrow">PHP-проект средней сложности</p>
        <h1>Магазин гарнитур и комплектующих для Telegram-бота</h1>
        <p>
            Публичная витрина показывает товары из базы, покупатель оформляет заказ после входа,
            а администратор управляет каталогом, категориями, заказами и учетными записями.
        </p>
        <div class="hero-actions">
            <a class="button" href="<?= h(url('catalog')) ?>">Открыть каталог</a>
            <?php if (!$auth->check()): ?>
                <a class="button ghost" href="<?= h(url('login')) ?>">Войти в аккаунт</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="bot-panel" aria-label="Демо-сообщение магазина">
        <div class="chat-line bot">/catalog</div>
        <div class="chat-line shop">Найдено <?= h($stats['products']) ?> товаров в <?= h($stats['categories']) ?> категориях</div>
        <div class="chat-line bot">/order Pulse H7 Wireless</div>
        <div class="chat-line shop">Заказ попадет в админ-панель</div>
    </div>
</section>

<section class="stats-row" aria-label="Статистика проекта">
    <div><strong><?= h($stats['products']) ?></strong><span>товаров</span></div>
    <div><strong><?= h($stats['categories']) ?></strong><span>категорий</span></div>
    <div><strong><?= h($stats['orders']) ?></strong><span>заказов</span></div>
</section>

<section class="section-head">
    <div>
        <p class="eyebrow">Динамические элементы</p>
        <h2>Рекомендуемые товары</h2>
    </div>
    <a href="<?= h(url('catalog')) ?>">Все товары</a>
</section>

<div class="product-grid">
    <?php foreach ($featuredProducts as $product): ?>
        <article class="product-card">
            <img src="<?= h($product['image_url']) ?>" alt="<?= h($product['name']) ?>">
            <div class="product-card-body">
                <span class="badge"><?= h(product_type_label((string) $product['product_type'])) ?></span>
                <h3><?= h($product['name']) ?></h3>
                <p><?= h($product['description']) ?></p>
                <div class="product-meta">
                    <strong><?= h(money($product['price'])) ?></strong>
                    <span>Склад: <?= h($product['stock']) ?></span>
                </div>
                <a class="button full" href="<?= h(url('product', ['id' => $product['id']])) ?>">Подробнее</a>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<section class="section-head">
    <div>
        <p class="eyebrow">Категории из базы</p>
        <h2>Разделы магазина</h2>
    </div>
</section>

<div class="category-strip">
    <?php foreach ($categories as $category): ?>
        <a href="<?= h(url('catalog', ['category_id' => $category['id']])) ?>"><?= h($category['name']) ?></a>
    <?php endforeach; ?>
</div>

<section class="section-head">
    <div>
        <p class="eyebrow">Последние записи</p>
        <h2>Недавно добавлено</h2>
    </div>
</section>

<div class="compact-list">
    <?php foreach ($latestProducts as $product): ?>
        <a href="<?= h(url('product', ['id' => $product['id']])) ?>">
            <span><?= h($product['name']) ?></span>
            <strong><?= h(money($product['price'])) ?></strong>
        </a>
    <?php endforeach; ?>
</div>
