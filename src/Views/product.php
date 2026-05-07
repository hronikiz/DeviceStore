<section class="product-detail">
    <div class="product-media">
        <img src="<?= h($product['image_url']) ?>" alt="<?= h($product['name']) ?>">
    </div>
    <div class="product-info">
        <a class="back-link" href="<?= h(url('catalog')) ?>">Назад в каталог</a>
        <p class="eyebrow"><?= h($category['name'] ?? 'Каталог') ?> / <?= h(product_type_label((string) $product['product_type'])) ?></p>
        <h1><?= h($product['name']) ?></h1>
        <p><?= h($product['description']) ?></p>
        <div class="detail-meta">
            <strong><?= h(money($product['price'])) ?></strong>
            <span>На складе: <?= h($product['stock']) ?></span>
        </div>

        <?php if (!$auth->check()): ?>
            <div class="notice">
                Для оформления заказа нужно войти или зарегистрироваться.
                <a href="<?= h(url('login')) ?>">Войти</a>
            </div>
        <?php elseif ((int) $product['stock'] <= 0): ?>
            <div class="notice error">Товар временно отсутствует на складе.</div>
        <?php else: ?>
            <form class="form-panel" method="post" data-validate="order">
                <?= \BotGear\Core\Csrf::input() ?>
                <h2>Оформить заказ</h2>
                <div class="form-grid two">
                    <label>
                        Количество
                        <input type="number" name="quantity" min="1" max="<?= h($product['stock']) ?>" value="<?= h(field_value($old, 'quantity', 1)) ?>" required>
                        <small class="field-error" data-error-for="quantity"><?= h($errors['quantity'] ?? '') ?></small>
                    </label>
                    <label>
                        Telegram
                        <input type="text" name="customer_telegram" value="<?= h(field_value($old, 'customer_telegram')) ?>" placeholder="@username" required>
                        <small class="field-error" data-error-for="customer_telegram"><?= h($errors['customer_telegram'] ?? '') ?></small>
                    </label>
                    <label>
                        Телефон
                        <input type="tel" name="customer_phone" value="<?= h(field_value($old, 'customer_phone')) ?>" placeholder="+373 60000000" required>
                        <small class="field-error" data-error-for="customer_phone"><?= h($errors['customer_phone'] ?? '') ?></small>
                    </label>
                    <label>
                        Адрес доставки
                        <textarea name="delivery_address" rows="3" required><?= h(field_value($old, 'delivery_address')) ?></textarea>
                        <small class="field-error" data-error-for="delivery_address"><?= h($errors['delivery_address'] ?? '') ?></small>
                    </label>
                </div>
                <label>
                    Комментарий
                    <textarea name="note" rows="3"><?= h(field_value($old, 'note')) ?></textarea>
                    <small class="field-error" data-error-for="note"><?= h($errors['note'] ?? '') ?></small>
                </label>
                <button class="button" type="submit">Создать заказ</button>
            </form>
        <?php endif; ?>
    </div>
</section>
