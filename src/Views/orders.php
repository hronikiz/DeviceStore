<section class="page-heading">
    <p class="eyebrow">Защищенный компонент</p>
    <h1>Мои заказы</h1>
    <p>Этот раздел доступен только после входа в систему.</p>
</section>

<?php if ($orders === []): ?>
    <div class="empty-state">У вас пока нет заказов.</div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Товар</th>
                    <th>Количество</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Дата</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <?php $product = $productMap[(int) $order['product_id']] ?? null; ?>
                    <tr>
                        <td>#<?= h($order['id']) ?></td>
                        <td><?= h($product['name'] ?? 'Товар удален') ?></td>
                        <td><?= h($order['quantity']) ?></td>
                        <td><?= h(money($order['total_price'])) ?></td>
                        <td><span class="status <?= h($order['status']) ?>"><?= h(order_status_label((string) $order['status'])) ?></span></td>
                        <td><?= h(date('d.m.Y H:i', strtotime((string) $order['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
