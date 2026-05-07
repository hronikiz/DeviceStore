<?php require BASE_PATH . '/src/Views/admin/_nav.php'; ?>

<section class="page-heading">
    <p class="eyebrow">Управление данными</p>
    <h1>Заказы</h1>
    <p>Администратор видит заявки покупателей и меняет статус обработки.</p>
</section>

<?php if ($orders === []): ?>
    <div class="empty-state">Заказов пока нет.</div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Покупатель</th>
                    <th>Товар</th>
                    <th>Telegram</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <?php
                    $product = $productMap[(int) $order['product_id']] ?? null;
                    $buyer = $userMap[(int) $order['user_id']] ?? null;
                    ?>
                    <tr>
                        <td>#<?= h($order['id']) ?></td>
                        <td><?= h($buyer['name'] ?? 'Пользователь удален') ?></td>
                        <td><?= h($product['name'] ?? 'Товар удален') ?> x <?= h($order['quantity']) ?></td>
                        <td><?= h($order['customer_telegram']) ?></td>
                        <td><?= h(money($order['total_price'])) ?></td>
                        <td>
                            <form class="status-form" method="post">
                                <?= \BotGear\Core\Csrf::input() ?>
                                <input type="hidden" name="id" value="<?= h($order['id']) ?>">
                                <select name="status">
                                    <?php foreach (['new', 'processing', 'shipped', 'done', 'cancelled'] as $status): ?>
                                        <option value="<?= h($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>>
                                            <?= h(order_status_label($status)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="button small ghost" type="submit">OK</button>
                            </form>
                        </td>
                    </tr>
                    <tr class="order-note">
                        <td colspan="6">
                            <strong>Адрес:</strong> <?= h($order['delivery_address']) ?>.
                            <strong>Телефон:</strong> <?= h($order['customer_phone']) ?>.
                            <?php if ($order['note'] !== ''): ?>
                                <strong>Комментарий:</strong> <?= h($order['note']) ?>.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
