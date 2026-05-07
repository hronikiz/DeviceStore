<?php require BASE_PATH . '/src/Views/admin/_nav.php'; ?>
<?php
$old = $old ?? [];
$errors = $errors ?? [];
$categories = $categories ?? [];
$mode = $mode ?? 'create';
$product = $product ?? [];
$isEdit = $mode === 'edit';
$heading = $isEdit ? 'Редактирование товара' : 'Добавление товара';
$action = $isEdit ? url('admin-product-edit', ['id' => $product['id']]) : url('admin-product-create');
?>

<section class="page-heading">
    <p class="eyebrow">Товар</p>
    <h1><?= h($heading) ?></h1>
    <p>Заполните карточку товара для каталога.</p>
</section>

<form class="form-panel wide" method="post" action="<?= h($action) ?>" data-validate="product">
    <?= \DeviceStore\Core\Csrf::input() ?>
    <div class="form-grid two">
        <label>
            Название
            <input type="text" name="name" minlength="3" value="<?= h(field_value($old, 'name')) ?>" required>
            <small class="field-error" data-error-for="name"><?= h($errors['name'] ?? '') ?></small>
        </label>
        <label>
            Категория
            <select name="category_id" required>
                <option value="">Выберите категорию</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= h($category['id']) ?>" <?= (int) field_value($old, 'category_id', 0) === (int) $category['id'] ? 'selected' : '' ?>>
                        <?= h($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="field-error" data-error-for="category_id"><?= h($errors['category_id'] ?? '') ?></small>
        </label>
        <fieldset class="radio-group">
            <legend>Тип товара</legend>
            <?php foreach (['headset' => 'Гарнитура', 'component' => 'Комплектующее', 'peripheral' => 'Периферия'] as $value => $label): ?>
                <label>
                    <input type="radio" name="product_type" value="<?= h($value) ?>" <?= field_value($old, 'product_type') === $value ? 'checked' : '' ?> required>
                    <?= h($label) ?>
                </label>
            <?php endforeach; ?>
            <small class="field-error" data-error-for="product_type"><?= h($errors['product_type'] ?? '') ?></small>
        </fieldset>
        <label>
            Цена, MDL
            <input type="number" name="price" min="1" max="50000" step="0.01" value="<?= h(field_value($old, 'price')) ?>" required>
            <small class="field-error" data-error-for="price"><?= h($errors['price'] ?? '') ?></small>
        </label>
        <label>
            Количество на складе
            <input type="number" name="stock" min="0" max="10000" value="<?= h(field_value($old, 'stock', 0)) ?>" required>
            <small class="field-error" data-error-for="stock"><?= h($errors['stock'] ?? '') ?></small>
        </label>
        <label>
            Изображение
            <input type="text" name="image_url" value="<?= h(field_value($old, 'image_url', 'https://images.unsplash.com/photo-1753557346289-7f7bd0576d05?auto=format&fit=crop&w=1200&q=80')) ?>" required>
            <small class="field-error" data-error-for="image_url"><?= h($errors['image_url'] ?? '') ?></small>
        </label>
    </div>
    <label>
        Описание
        <textarea name="description" rows="5" minlength="20" required><?= h(field_value($old, 'description')) ?></textarea>
        <small class="field-error" data-error-for="description"><?= h($errors['description'] ?? '') ?></small>
    </label>
    <label class="check-line">
        <input type="checkbox" name="is_featured" value="1" <?= field_value($old, 'is_featured') ? 'checked' : '' ?>>
        Показывать в рекомендованных
    </label>
    <div class="form-actions">
        <button class="button" type="submit"><?= $isEdit ? 'Сохранить' : 'Добавить' ?></button>
        <a class="button ghost" href="<?= h(url('admin-products')) ?>">Отмена</a>
    </div>
</form>
