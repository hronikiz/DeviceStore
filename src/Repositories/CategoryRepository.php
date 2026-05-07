<?php

declare(strict_types=1);

namespace DeviceStore\Repositories;

use DeviceStore\Core\FileDatabase;

/**
 * Выполняет операции хранения категорий.
 */
final class CategoryRepository
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
     * Возвращает все категории товаров.
     *
     * @return array<int, array<string, mixed>> Записи категорий.
     */
    public function all(): array
    {
        return $this->db->all('categories');
    }

    /**
     * Ищет категорию по идентификатору.
     *
     * @param int $id Идентификатор категории.
     * @return array<string, mixed>|null Запись категории или null.
     */
    public function find(int $id): ?array
    {
        return $this->db->find('categories', $id);
    }

    /**
     * Создает категорию со сгенерированным slug.
     *
     * @param string $name Название категории.
     * @return array<string, mixed> Созданная категория.
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
     * Удаляет категорию по идентификатору.
     *
     * @param int $id Идентификатор категории.
     * @return bool True, если категория была удалена.
     */
    public function delete(int $id): bool
    {
        return $this->db->delete('categories', $id);
    }

    /**
     * Формирует безопасный для URL slug из названия категории.
     *
     * @param string $name Название категории.
     * @return string Сгенерированный slug.
     */
    private function slug(string $name): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($name));

        return trim((string) $slug, '-') ?: 'category';
    }
}
