<?php

declare(strict_types=1);

namespace BotGear\Core;

/**
 * Stores application tables in a JSON file and provides small CRUD helpers.
 */
final class FileDatabase
{
    private string $path;

    /** @var array<string, array<int, array<string, mixed>>> */
    private array $data = [];

    /** @var array<int, string> */
    private array $tables = ['users', 'categories', 'products', 'orders'];

    /**
     * Creates a database instance and initializes seed data when the file is absent.
     *
     * @param string $path Absolute path to the JSON database file.
     */
    public function __construct(string $path)
    {
        $this->path = $path;
        $this->load();
    }

    /**
     * Returns all rows from a table.
     *
     * @param string $table Table name.
     * @return array<int, array<string, mixed>> Table rows.
     */
    public function all(string $table): array
    {
        $this->guardTable($table);

        return array_values($this->data[$table]);
    }

    /**
     * Finds a single row by numeric identifier.
     *
     * @param string $table Table name.
     * @param int $id Row identifier.
     * @return array<string, mixed>|null Row data or null when not found.
     */
    public function find(string $table, int $id): ?array
    {
        $this->guardTable($table);

        foreach ($this->data[$table] as $row) {
            if ((int) $row['id'] === $id) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Returns rows that satisfy a predicate callback.
     *
     * @param string $table Table name.
     * @param callable(array<string, mixed>):bool $predicate Filtering callback.
     * @return array<int, array<string, mixed>> Filtered rows.
     */
    public function where(string $table, callable $predicate): array
    {
        $this->guardTable($table);

        return array_values(array_filter($this->data[$table], $predicate));
    }

    /**
     * Inserts a row into a table and persists the database.
     *
     * @param string $table Table name.
     * @param array<string, mixed> $row Row values.
     * @return array<string, mixed> Inserted row with identifier.
     */
    public function insert(string $table, array $row): array
    {
        $this->guardTable($table);

        $row['id'] = $this->nextId($table);
        $this->data[$table][] = $row;
        $this->persist();

        return $row;
    }

    /**
     * Updates a row by identifier and persists the database.
     *
     * @param string $table Table name.
     * @param int $id Row identifier.
     * @param array<string, mixed> $values Values to merge into the row.
     * @return array<string, mixed>|null Updated row or null when not found.
     */
    public function update(string $table, int $id, array $values): ?array
    {
        $this->guardTable($table);

        foreach ($this->data[$table] as $index => $row) {
            if ((int) $row['id'] === $id) {
                $this->data[$table][$index] = array_merge($row, $values);
                $this->persist();

                return $this->data[$table][$index];
            }
        }

        return null;
    }

    /**
     * Deletes a row by identifier and persists the database.
     *
     * @param string $table Table name.
     * @param int $id Row identifier.
     * @return bool True when a row was removed.
     */
    public function delete(string $table, int $id): bool
    {
        $this->guardTable($table);

        foreach ($this->data[$table] as $index => $row) {
            if ((int) $row['id'] === $id) {
                array_splice($this->data[$table], $index, 1);
                $this->persist();

                return true;
            }
        }

        return false;
    }

    /**
     * Loads the database from disk or creates a seeded database.
     *
     * @return void
     */
    private function load(): void
    {
        $directory = dirname($this->path);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (!is_file($this->path)) {
            $this->data = $this->seedData();
            $this->persist();

            return;
        }

        $decoded = json_decode((string) file_get_contents($this->path), true);
        $this->data = is_array($decoded) ? $decoded : $this->seedData();

        foreach ($this->tables as $table) {
            $this->data[$table] ??= [];
        }
    }

    /**
     * Persists the current database state to disk.
     *
     * @return void
     */
    private function persist(): void
    {
        file_put_contents(
            $this->path,
            json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    /**
     * Calculates the next numeric identifier for a table.
     *
     * @param string $table Table name.
     * @return int Next identifier.
     */
    private function nextId(string $table): int
    {
        $ids = array_map(static fn (array $row): int => (int) $row['id'], $this->data[$table]);

        return $ids === [] ? 1 : max($ids) + 1;
    }

    /**
     * Validates that a table exists in the database.
     *
     * @param string $table Table name.
     * @return void
     */
    private function guardTable(string $table): void
    {
        if (!in_array($table, $this->tables, true)) {
            throw new \InvalidArgumentException('Unknown table: ' . $table);
        }
    }

    /**
     * Returns the default database content for the first launch.
     *
     * @return array<string, array<int, array<string, mixed>>> Seeded tables.
     */
    private function seedData(): array
    {
        $now = date('c');

        return [
            'users' => [
                [
                    'id' => 1,
                    'name' => 'Главный администратор',
                    'email' => 'admin@botgear.local',
                    'password_hash' => password_hash('Admin12345!', PASSWORD_DEFAULT),
                    'role' => 'admin',
                    'reset_token_hash' => null,
                    'reset_expires_at' => null,
                    'created_at' => $now,
                ],
                [
                    'id' => 2,
                    'name' => 'Демо покупатель',
                    'email' => 'user@botgear.local',
                    'password_hash' => password_hash('User12345!', PASSWORD_DEFAULT),
                    'role' => 'user',
                    'reset_token_hash' => null,
                    'reset_expires_at' => null,
                    'created_at' => $now,
                ],
            ],
            'categories' => [
                ['id' => 1, 'name' => 'Гарнитуры', 'slug' => 'headsets', 'created_at' => $now],
                ['id' => 2, 'name' => 'Клавиатуры', 'slug' => 'keyboards', 'created_at' => $now],
                ['id' => 3, 'name' => 'Мыши', 'slug' => 'mice', 'created_at' => $now],
                ['id' => 4, 'name' => 'Комплектующие', 'slug' => 'components', 'created_at' => $now],
                ['id' => 5, 'name' => 'Микрофоны', 'slug' => 'microphones', 'created_at' => $now],
            ],
            'products' => [
                [
                    'id' => 1,
                    'category_id' => 1,
                    'name' => 'Pulse H7 Wireless',
                    'product_type' => 'headset',
                    'price' => 899.00,
                    'stock' => 12,
                    'description' => 'Беспроводная игровая гарнитура с мягкими амбушюрами, шумоподавлением микрофона и автономностью до 32 часов.',
                    'image_url' => 'assets/headset.svg',
                    'is_featured' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => 2,
                    'category_id' => 2,
                    'name' => 'KeyForge TKL RGB',
                    'product_type' => 'peripheral',
                    'price' => 1190.00,
                    'stock' => 7,
                    'description' => 'Компактная механическая клавиатура формата TKL с горячей заменой переключателей и несколькими профилями подсветки.',
                    'image_url' => 'assets/keyboard.svg',
                    'is_featured' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => 3,
                    'category_id' => 3,
                    'name' => 'AeroClick Pro',
                    'product_type' => 'peripheral',
                    'price' => 540.00,
                    'stock' => 15,
                    'description' => 'Легкая мышь для игр и работы с точным сенсором, боковыми кнопками и настройкой чувствительности.',
                    'image_url' => 'assets/mouse.svg',
                    'is_featured' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => 4,
                    'category_id' => 4,
                    'name' => 'Nova PSU 650W',
                    'product_type' => 'component',
                    'price' => 1450.00,
                    'stock' => 5,
                    'description' => 'Блок питания 650W с сертификатом 80 Plus Bronze, тихим вентилятором и защитой от перегрузки.',
                    'image_url' => 'assets/component.svg',
                    'is_featured' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => 5,
                    'category_id' => 5,
                    'name' => 'StreamMic Mini',
                    'product_type' => 'peripheral',
                    'price' => 690.00,
                    'stock' => 9,
                    'description' => 'USB-микрофон для стримов, голосовых чатов и записи уроков с настольной стойкой в комплекте.',
                    'image_url' => 'assets/microphone.svg',
                    'is_featured' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => 6,
                    'category_id' => 4,
                    'name' => 'Arctic Fan Pack 120',
                    'product_type' => 'component',
                    'price' => 380.00,
                    'stock' => 18,
                    'description' => 'Набор из трех корпусных вентиляторов 120 мм для улучшения охлаждения игрового компьютера.',
                    'image_url' => 'assets/component.svg',
                    'is_featured' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
            'orders' => [],
        ];
    }
}
