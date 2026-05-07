<?php

declare(strict_types=1);

namespace BotGear\Repositories;

use BotGear\Core\FileDatabase;

/**
 * Provides order persistence operations.
 */
final class OrderRepository
{
    private FileDatabase $db;

    /**
     * Stores the database dependency.
     *
     * @param FileDatabase $db Application database.
     */
    public function __construct(FileDatabase $db)
    {
        $this->db = $db;
    }

    /**
     * Returns all orders sorted by newest first.
     *
     * @return array<int, array<string, mixed>> Order records.
     */
    public function all(): array
    {
        $orders = $this->db->all('orders');
        usort($orders, static fn (array $a, array $b): int => (int) $b['id'] <=> (int) $a['id']);

        return $orders;
    }

    /**
     * Returns orders for a user.
     *
     * @param int $userId User identifier.
     * @return array<int, array<string, mixed>> User orders.
     */
    public function forUser(int $userId): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (array $order): bool => (int) $order['user_id'] === $userId
        ));
    }

    /**
     * Creates an order record.
     *
     * @param array<string, mixed> $data Order values.
     * @return array<string, mixed> Created order.
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
     * Updates an order status.
     *
     * @param int $id Order identifier.
     * @param string $status New status.
     * @return array<string, mixed>|null Updated order or null.
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
     * Counts orders by status.
     *
     * @param string|null $status Optional status filter.
     * @return int Number of orders.
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
