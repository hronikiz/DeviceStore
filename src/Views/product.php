<?php
/** @var \DeviceStore\Core\Auth|null $auth */
$auth = $auth ?? null;
$product = $product ?? [];
$category = $category ?? [];
$old = $old ?? [];
$errors = $errors ?? [];
$specs = $product['specs'] ?? [];
$subtitle = trim((string) ($product['description'] ?? ''));
?>
<section class="product-detail">
    <div class="product-media">
        <img src="<?= h($product['image_url'] ?? '') ?>" alt="<?= h($product['name'] ?? '') ?>">
    </div>

    <aside class="product-detail-right">
        <div class="product-info">
            <a class="back-link" href="<?= h(url('catalog')) ?>">Назад в каталог</a>
            <p class="eyebrow"><?= h($category['name'] ?? 'Каталог') ?> / <?= h(product_type_label((string) $product['product_type'] ?? '')) ?></p>
            <div class="product-top">
                <h1><?= h($product['name'] ?? '') ?></h1>
                <?php if ($subtitle !== ''): ?>
                    <p class="product-subtitle"><?= h($subtitle) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="product-summary-card">
            <div class="summary-row">
                <div>
                    <span>На складе</span>
                    <strong><?= h($product['stock'] ?? 0) ?></strong>
                </div>
                <div>
                    <span>ID товара</span>
                    <strong><?= h(sprintf('00-%06d', $product['id'] ?? 0)) ?></strong>
                </div>
            </div>
            <div class="price-row">
                <span>Цена</span>
                <strong><?= h(money($product['price'] ?? 0)) ?></strong>
            </div>
        </div>
    </aside>
</section>

<section class="order-section">
    <?php if ($auth === null || !$auth->check()): ?>
        <div class="notice">
            Для оформления заказа нужно войти или зарегистрироваться.
            <a href="<?= h(url('login')) ?>">Войти</a>
        </div>
    <?php elseif ((int) ($product['stock'] ?? 0) <= 0): ?>
        <div class="notice error">Товар временно отсутствует на складе.</div>
    <?php else: ?>
        <form class="form-panel order-form" method="post" data-validate="order">
            <?= \DeviceStore\Core\Csrf::input() ?>
            <h2>Оформить заказ</h2>
            <div class="form-grid two">
                <label>
                    Количество
                    <input type="number" name="quantity" min="1" max="<?= h($product['stock'] ?? 0) ?>" value="<?= h(field_value($old ?? [], 'quantity', 1)) ?>" required>
                    <small class="field-error" data-error-for="quantity"><?= h($errors['quantity'] ?? '') ?></small>
                </label>
                <label>
                    Telegram
                    <input type="text" name="customer_telegram" value="<?= h(field_value($old ?? [], 'customer_telegram')) ?>" placeholder="@username" required>
                    <small class="field-error" data-error-for="customer_telegram"><?= h($errors['customer_telegram'] ?? '') ?></small>
                </label>
                <label>
                    Телефон
                    <input type="tel" name="customer_phone" value="<?= h(field_value($old ?? [], 'customer_phone')) ?>" placeholder="+373 60000000" required>
                    <small class="field-error" data-error-for="customer_phone"><?= h($errors['customer_phone'] ?? '') ?></small>
                </label>
                <label>
                    Адрес доставки
                    <textarea name="delivery_address" rows="3" required><?= h(field_value($old ?? [], 'delivery_address')) ?></textarea>
                    <small class="field-error" data-error-for="delivery_address"><?= h($errors['delivery_address'] ?? '') ?></small>
                </label>
            </div>
            <label>
                Комментарий
                <textarea name="note" rows="3"><?= h(field_value($old ?? [], 'note')) ?></textarea>
                <small class="field-error" data-error-for="note"><?= h($errors['note'] ?? '') ?></small>
            </label>
            <button class="button" type="submit">Создать заказ</button>
        </form>
    <?php endif; ?>
</section>

<div class="product-tabs">
        <a href="#specs">Характеристики</a>
        <a href="#description">Описание</a>
        <a href="#reviews">Отзывы (0)</a>
    </div>

    <section class="product-specs" id="specs">
        <div class="spec-panel">
            <div class="spec-panel-header">
                <p class="eyebrow">Основные</p>
                <h2>Полные характеристики</h2>
            </div>
            <?php if ($specs !== []): ?>
                <dl class="spec-list">
                    <?php foreach ($specs as $name => $value): ?>
                        <div class="spec-row">
                            <dt><?= h($name) ?></dt>
                            <dd><?= h($value) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            <?php else: ?>
                <p class="empty-state">Полные характеристики пока не добавлены.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="product-description" id="description">
        <div class="section-heading">
            <p class="eyebrow">Описание</p>
            <h2>О товаре</h2>
        </div>
        <p><?= h($product['description'] ?? '') ?></p>
    </section>

    <section class="product-reviews" id="reviews">
        <div class="section-heading">
            <p class="eyebrow">Отзывы</p>
            <h2>Пока нет отзывов</h2>
        </div>
        <div class="empty-state">Здесь появятся отзывы покупателей.</div>
    </section>
</section>
