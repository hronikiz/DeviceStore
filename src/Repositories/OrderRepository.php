<?php

declare(strict_types=1);

namespace DeviceStore\Repositories;

use DeviceStore\Core\FileDatabase;

/**
 * Выполняет операции хранения заказов.
 */
final class OrderRepository
{
    private FileDatabase $db;

    /**
     * Сохраняет зависимость базы данных.
     *
     * @param FileDatabase $db База данных приложения.
     */
    public function __construct(FileDatabase $db)
    {
        $this->db = $db;
    }

    /**
     * Возвращает все заказы с сортировкой от новых к старым.
     *
     * @return array<int, array<string, mixed>> Записи заказов.
     */
    public function all(): array
    {
        $orders = $this->db->all('orders');
        usort($orders, static fn (array $a, array $b): int => (int) $b['id'] <=> (int) $a['id']);

        return $orders;
    }

    /**
     * Возвращает заказы пользователя.
     *
     * @param int $userId Идентификатор пользователя.
     * @return array<int, array<string, mixed>> Заказы пользователя.
     */
    public function forUser(int $userId): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (array $order): bool => (int) $order['user_id'] === $userId
        ));
    }

    /**
     * Создает запись заказа.
     *
     * @param array<string, mixed> $data Значения заказа.
     * @return array<string, mixed> Созданный заказ.
     */
    public function create(array $data): array
    {
        return $this->db->insert('orders', array_merge($data, [
            'status' => 'new',
            'created_at' => date('c'),
            'updated_at' => date('c'),
        ]));
    }

    /**
     * Обновляет статус заказа.
     *
     * @param int $id Идентификатор заказа.
     * @param string $status Новый статус.
     * @return array<string, mixed>|null Обновленный заказ или null.
     */
    public function updateStatus(int $id, string $status): ?array
    {
        if (!in_array($status, ['new', 'processing', 'shipped', 'done', 'cancelled'], true)) {
            return null;
        }

        return $this->db->update('orders', $id, [
            'status' => $status,
            'updated_at' => date('c'),
        ]);
    }

    /**
     * Считает заказы по статусу.
     *
     * @param string|null $status Необязательный фильтр статуса.
     * @return int Количество заказов.
     */
    public function count(?string $status = null): int
    {
        if ($status === null) {
            return count($this->all());
        }

        return count(array_filter(
            $this->all(),
            static fn (array $order): bool => (string) $order['status'] === $status
        ));
    }
}
