<?php require BASE_PATH . '/src/Views/admin/_nav.php'; ?>

<section class="section-head">
    <div>
        <p class="eyebrow">Справочник</p>
        <h1>Категории</h1>
    </div>
</section>

<form class="form-panel inline-form" method="post" data-validate="category">
    <?= \BotGear\Core\Csrf::input() ?>
    <input type="hidden" name="action" value="create">
    <label>
        Новая категория
        <input type="text" name="name" minlength="3" value="<?= h(field_value($old, 'name')) ?>" required>
        <small class="field-error" data-error-for="name"><?= h($errors['name'] ?? '') ?></small>
    </label>
    <button class="button" type="submit">Добавить</button>
</form>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Slug</th>
                <th>Товаров</th>
                <th>Действие</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $category): ?>
                <tr>
                    <td>#<?= h($category['id']) ?></td>
                    <td><?= h($category['name']) ?></td>
                    <td><?= h($category['slug']) ?></td>
                    <td><?= h($productCounts[(int) $category['id']] ?? 0) ?></td>
                    <td>
                        <form method="post" onsubmit="return confirm('Удалить категорию?')">
                            <?= \BotGear\Core\Csrf::input() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= h($category['id']) ?>">
                            <button class="button small danger" type="submit">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
