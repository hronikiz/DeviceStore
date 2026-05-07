<?php require BASE_PATH . '/src/Views/admin/_nav.php'; ?>

<section class="section-head">
    <div>
        <p class="eyebrow">CRUD</p>
        <h1>Управление товарами</h1>
    </div>
    <a class="button" href="<?= h(url('admin-product-create')) ?>">Добавить товар</a>
</section>

<form class="filter-bar" method="get" data-validate="search">
    <input type="hidden" name="page" value="admin-products">
    <label>
        Поиск
        <input type="search" name="q" value="<?= h($filters['q']) ?>" placeholder="название или описание">
    </label>
    <label>
        Категория
        <select name="category_id">
            <option value="0">Все категории</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= h($category['id']) ?>" <?= (int) $filters['category_id'] === (int) $category['id'] ? 'selected' : '' ?>>
                    <?= h($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        Тип
        <select name="product_type">
            <option value="">Все типы</option>
            <option value="headset" <?= $filters['product_type'] === 'headset' ? 'selected' : '' ?>>Гарнитура</option>
            <option value="component" <?= $filters['product_type'] === 'component' ? 'selected' : '' ?>>Комплектующее</option>
            <option value="peripheral" <?= $filters['product_type'] === 'peripheral' ? 'selected' : '' ?>>Периферия</option>
        </select>
    </label>
    <label class="check-line">
        <input type="checkbox" name="available" value="1" <?= $filters['available'] === '1' ? 'checked' : '' ?>>
        В наличии
    </label>
    <button class="button" type="submit">Фильтр</button>
</form>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Товар</th>
                <th>Категория</th>
                <th>Тип</th>
                <th>Цена</th>
                <th>Склад</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td>#<?= h($product['id']) ?></td>
                    <td><?= h($product['name']) ?></td>
                    <td><?= h($categoryMap[(int) $product['category_id']] ?? 'Без категории') ?></td>
                    <td><?= h(product_type_label((string) $product['product_type'])) ?></td>
                    <td><?= h(money($product['price'])) ?></td>
                    <td><?= h($product['stock']) ?></td>
                    <td class="actions-cell">
                        <a class="button small ghost" href="<?= h(url('admin-product-edit', ['id' => $product['id']])) ?>">Изменить</a>
                        <form method="post" action="<?= h(url('admin-product-delete')) ?>" onsubmit="return confirm('Удалить товар?')">
                            <?= \BotGear\Core\Csrf::input() ?>
                            <input type="hidden" name="id" value="<?= h($product['id']) ?>">
                            <button class="button small danger" type="submit">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
