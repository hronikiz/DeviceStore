<?php

declare(strict_types=1);

namespace BotGear\Repositories;

use BotGear\Core\FileDatabase;

/**
 * Provides category persistence operations.
 */
final class CategoryRepository
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
     * Returns all product categories.
     *
     * @return array<int, array<string, mixed>> Category records.
     */
    public function all(): array
    {
        return $this->db->all('categories');
    }

    /**
     * Finds a category by identifier.
     *
     * @param int $id Category identifier.
     * @return array<string, mixed>|null Category record or null.
     */
    public function find(int $id): ?array
    {
        return $this->db->find('categories', $id);
    }

    /**
     * Creates a category with a generated slug.
     *
     * @param string $name Category name.
     * @return array<string, mixed> Created category.
     */
    public function create(string $name): array
    {
        return $this->db->insert('categories', [
            'name' => $name,
            'slug' => $this->slug($name),
            'created_at' => date('c'),
        ]);
    }

    /**
     * Deletes a category by identifier.
     *
     * @param int $id Category identifier.
     * @return bool True when the category was removed.
     */
    public function delete(int $id): bool
    {
        return $this->db->delete('categories', $id);
    }

    /**
     * Builds a URL-safe slug from category text.
     *
     * @param string $name Category name.
     * @return string Generated slug.
     */
    private function slug(string $name): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($name));

        return trim((string) $slug, '-') ?: 'category';
    }
}
