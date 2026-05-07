<?php
$filters = $filters ?? [];
$products = $products ?? [];
$categories = $categories ?? [];
$categoryMap = $categoryMap ?? [];
?>
<section class="page-heading">
    <p class="eyebrow">Каталог</p>
    <h1>Каталог товаров</h1>
    <p>Фильтруйте товары по категории, типу, цене и наличию.</p>
</section>

<form class="filter-bar" method="get" data-validate="search">
    <input type="hidden" name="page" value="catalog">
    <label>
        Поиск
        <input type="search" name="q" value="<?= h($filters['q'] ?? '') ?>" placeholder="гарнитура, блок питания">
    </label>
    <label>
        Категория
        <select name="category_id">
            <option value="0">Все категории</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= h($category['id']) ?>" <?= (int) ($filters['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>>
                    <?= h($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        Тип
        <select name="product_type">
            <option value="">Все типы</option>
            <option value="headset" <?= ($filters['product_type'] ?? '') === 'headset' ? 'selected' : '' ?>>Гарнитура</option>
            <option value="component" <?= ($filters['product_type'] ?? '') === 'component' ? 'selected' : '' ?>>Комплектующее</option>
            <option value="peripheral" <?= ($filters['product_type'] ?? '') === 'peripheral' ? 'selected' : '' ?>>Периферия</option>
        </select>
    </label>
    <label>
        Цена до
        <input type="number" name="max_price" min="0" step="1" value="<?= h(($filters['max_price'] ?? '') ?: '') ?>" placeholder="1500">
    </label>
    <label class="check-line">
        <input type="checkbox" name="available" value="1" <?= ($filters['available'] ?? '') === '1' ? 'checked' : '' ?>>
        В наличии
    </label>
    <button class="button" type="submit">Найти</button>
</form>

<?php if ($products === []): ?>
    <div class="empty-state">По выбранным критериям товаров не найдено.</div>
<?php else: ?>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <article class="product-card">
                <img src="<?= h($product['image_url']) ?>" alt="<?= h($product['name']) ?>">
                <div class="product-card-body">
                    <span class="badge"><?= h($categoryMap[(int) $product['category_id']] ?? 'Без категории') ?></span>
                    <h2><?= h($product['name']) ?></h2>
                    <p><?= h($product['description']) ?></p>
                    <div class="product-meta">
                        <strong><?= h(money($product['price'])) ?></strong>
                        <span><?= (int) $product['stock'] > 0 ? 'В наличии: ' . h($product['stock']) : 'Нет в наличии' ?></span>
                    </div>
                    <a class="button full" href="<?= h(url('product', ['id' => $product['id']])) ?>">Открыть</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
