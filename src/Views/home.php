<?php
/** @var \DeviceStore\Core\Auth|null $auth */
$auth = $auth ?? null;
$featuredProducts = $featuredProducts ?? [];
$latestProducts = $latestProducts ?? [];
$categories = $categories ?? [];
?>
<section class="hero">
    <div class="hero-copy">
        <p class="eyebrow">DeviceStore</p>
        <h1>Гарнитуры и комплектующие для вашего ПК</h1>
        <p>
            Подберите гарнитуру, клавиатуру, мышь или комплектующие и оформите заказ онлайн.
            После заявки менеджер свяжется с вами в Telegram.
        </p>
        <div class="hero-actions">
            <a class="button" href="<?= h(url('catalog')) ?>">Смотреть каталог</a>
            <?php if ($auth === null || !$auth->check()): ?>
                <a class="button ghost" href="<?= h(url('login')) ?>">Войти</a>
            <?php endif; ?>
        </div>
    </div>
    <?php $heroProduct = $featuredProducts[0] ?? $latestProducts[0] ?? null; ?>
    <?php if ($heroProduct !== null): ?>
        <a class="hero-media" href="<?= h(url('product', ['id' => $heroProduct['id']])) ?>">
            <img src="<?= h($heroProduct['image_url']) ?>" alt="<?= h($heroProduct['name']) ?>">
        </a>
    <?php endif; ?>
</section>

<section class="section-head">
    <div>
        <p class="eyebrow">Подборка</p>
        <h2>Популярные товары</h2>
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
                    <span>В наличии: <?= h($product['stock']) ?></span>
                </div>
                <a class="button full" href="<?= h(url('product', ['id' => $product['id']])) ?>">Подробнее</a>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<section class="section-head">
    <div>
        <p class="eyebrow">Категории</p>
        <h2>Быстрый выбор</h2>
    </div>
</section>

<div class="category-strip">
    <?php foreach ($categories as $category): ?>
        <a href="<?= h(url('catalog', ['category_id' => $category['id']])) ?>"><?= h($category['name']) ?></a>
    <?php endforeach; ?>
</div>
