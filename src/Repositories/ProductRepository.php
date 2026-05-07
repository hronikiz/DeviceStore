<?php

declare(strict_types=1);

namespace BotGear\Repositories;

use BotGear\Core\FileDatabase;

/**
 * Provides product persistence and search operations.
 */
final class ProductRepository
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
     * Returns all products sorted by newest first.
     *
     * @return array<int, array<string, mixed>> Product records.
     */
    public function all(): array
    {
        $products = $this->db->all('products');
        usort($products, static fn (array $a, array $b): int => (int) $b['id'] <=> (int) $a['id']);

        return $products;
    }

    /**
     * Finds a product by identifier.
     *
     * @param int $id Product identifier.
     * @return array<string, mixed>|null Product record or null.
     */
    public function find(int $id): ?array
    {
        return $this->db->find('products', $id);
    }

    /**
     * Returns featured products for the public home page.
     *
     * @param int $limit Maximum number of products.
     * @return array<int, array<string, mixed>> Featured products.
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
     * Searches products by text and filter criteria.
     *
     * @param array<string, mixed> $filters Search criteria.
     * @return array<int, array<string, mixed>> Matching products.
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
     * Creates a product.
     *
     * @param array<string, mixed> $data Product values.
     * @return array<string, mixed> Created product.
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
     * Updates a product by identifier.
     *
     * @param int $id Product identifier.
     * @param array<string, mixed> $data Product values.
     * @return array<string, mixed>|null Updated product or null.
     */
    public function update(int $id, array $data): ?array
    {
        return $this->db->update('products', $id, array_merge($data, [
            'updated_at' => date('c'),
        ]));
    }

    /**
     * Deletes a product by identifier.
     *
     * @param int $id Product identifier.
     * @return bool True when a product was removed.
     */
    public function delete(int $id): bool
    {
        return $this->db->delete('products', $id);
    }

    /**
     * Decreases stock after a successful order.
     *
     * @param int $id Product identifier.
     * @param int $quantity Purchased quantity.
     * @return bool True when stock was updated.
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
     * Counts products in a category.
     *
     * @param int $categoryId Category identifier.
     * @return int Number of products.
     */
    public function countByCategory(int $categoryId): int
    {
        return count(array_filter(
            $this->all(),
            static fn (array $product): bool => (int) $product['category_id'] === $categoryId
        ));
    }

    /**
     * Counts products with low stock.
     *
     * @param int $threshold Stock threshold.
     * @return int Number of low-stock products.
     */
    public function countLowStock(int $threshold = 3): int
    {
        return count(array_filter(
            $this->all(),
            static fn (array $product): bool => (int) $product['stock'] <= $threshold
        ));
    }
}
