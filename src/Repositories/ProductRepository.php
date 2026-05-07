<?php

declare(strict_types=1);

namespace DeviceStore\Repositories;

use DeviceStore\Core\FileDatabase;

/**
 * Выполняет операции хранения и поиска товаров.
 */
final class ProductRepository
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
     * Возвращает все товары с сортировкой от новых к старым.
     *
     * @return array<int, array<string, mixed>> Записи товаров.
     */
    public function all(): array
    {
        $products = $this->db->all('products');
        usort($products, static fn (array $a, array $b): int => (int) $b['id'] <=> (int) $a['id']);

        return $products;
    }

    /**
     * Ищет товар по идентификатору.
     *
     * @param int $id Идентификатор товара.
     * @return array<string, mixed>|null Запись товара или null.
     */
    public function find(int $id): ?array
    {
        return $this->db->find('products', $id);
    }

    /**
     * Возвращает рекомендованные товары для главной страницы.
     *
     * @param int $limit Максимальное количество товаров.
     * @return array<int, array<string, mixed>> Рекомендованные товары.
     */
    public function featured(int $limit = 3): array
    {
        return array_slice(
            array_values(array_filter($this->all(), static fn (array $product): bool => (bool) $product['is_featured'])),
            0,
            $limit
        );
    }

    /**
     * Ищет товары по тексту и фильтрам.
     *
     * @param array<string, mixed> $filters Критерии поиска.
     * @return array<int, array<string, mixed>> Найденные товары.
     */
    public function search(array $filters): array
    {
        $query = mb_strtolower(trim((string) ($filters['q'] ?? '')));
        $categoryId = (int) ($filters['category_id'] ?? 0);
        $productType = (string) ($filters['product_type'] ?? '');
        $maxPrice = (float) ($filters['max_price'] ?? 0);
        $availableOnly = isset($filters['available']) && (string) $filters['available'] === '1';

        return array_values(array_filter(
            $this->all(),
            static function (array $product) use ($query, $categoryId, $productType, $maxPrice, $availableOnly): bool {
                if ($query !== '') {
                    $haystack = mb_strtolower((string) $product['name'] . ' ' . (string) $product['description']);

                    if (!str_contains($haystack, $query)) {
                        return false;
                    }
                }

                if ($categoryId > 0 && (int) $product['category_id'] !== $categoryId) {
                    return false;
                }

                if ($productType !== '' && (string) $product['product_type'] !== $productType) {
                    return false;
                }

                if ($maxPrice > 0 && (float) $product['price'] > $maxPrice) {
                    return false;
                }

                if ($availableOnly && (int) $product['stock'] <= 0) {
                    return false;
                }

                return true;
            }
        ));
    }

    /**
     * Создает товар.
     *
     * @param array<string, mixed> $data Значения товара.
     * @return array<string, mixed> Созданный товар.
     */
    public function create(array $data): array
    {
        $now = date('c');

        return $this->db->insert('products', array_merge($data, [
            'created_at' => $now,
            'updated_at' => $now,
        ]));
    }

    /**
     * Обновляет товар по идентификатору.
     *
     * @param int $id Идентификатор товара.
     * @param array<string, mixed> $data Значения товара.
     * @return array<string, mixed>|null Обновленный товар или null.
     */
    public function update(int $id, array $data): ?array
    {
        return $this->db->update('products', $id, array_merge($data, [
            'updated_at' => date('c'),
        ]));
    }

    /**
     * Удаляет товар по идентификатору.
     *
     * @param int $id Идентификатор товара.
     * @return bool True, если товар был удален.
     */
    public function delete(int $id): bool
    {
        return $this->db->delete('products', $id);
    }

    /**
     * Уменьшает остаток на складе после успешного заказа.
     *
     * @param int $id Идентификатор товара.
     * @param int $quantity Купленное количество.
     * @return bool True, если остаток был обновлен.
     */
    public function decreaseStock(int $id, int $quantity): bool
    {
        $product = $this->find($id);

        if ($product === null) {
            return false;
        }

        $newStock = max(0, (int) $product['stock'] - $quantity);

        return $this->update($id, ['stock' => $newStock]) !== null;
    }

    /**
     * Считает товары в категории.
     *
     * @param int $categoryId Идентификатор категории.
     * @return int Количество товаров.
     */
    public function countByCategory(int $categoryId): int
    {
        return count(array_filter(
            $this->all(),
            static fn (array $product): bool => (int) $product['category_id'] === $categoryId
        ));
    }

    /**
     * Считает товары с низким остатком.
     *
     * @param int $threshold Порог остатка.
     * @return int Количество товаров с низким остатком.
     */
    public function countLowStock(int $threshold = 3): int
    {
        return count(array_filter(
            $this->all(),
            static fn (array $product): bool => (int) $product['stock'] <= $threshold
        ));
    }
}
