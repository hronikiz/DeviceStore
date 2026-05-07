<?php

declare(strict_types=1);

namespace DeviceStore\Core;

/**
 * Хранит таблицы приложения в JSON-файле и предоставляет простые CRUD-операции.
 */
final class FileDatabase
{
    private string $path;

    /** @var array<string, array<int, array<string, mixed>>> */
    private array $data = [];

    /** @var array<int, string> */
    private array $tables = ['users', 'categories', 'products', 'orders'];

    /**
     * Создает экземпляр базы и добавляет начальные данные, если файла еще нет.
     *
     * @param string $path Абсолютный путь к JSON-файлу базы данных.
     */
    public function __construct(string $path)
    {
        $this->path = $path;
        $this->load();
    }

    /**
     * Возвращает все строки из таблицы.
     *
     * @param string $table Название таблицы.
     * @return array<int, array<string, mixed>> Строки таблицы.
     */
    public function all(string $table): array
    {
        $this->guardTable($table);

        return array_values($this->data[$table]);
    }

    /**
     * Ищет одну строку по числовому идентификатору.
     *
     * @param string $table Название таблицы.
     * @param int $id Идентификатор строки.
     * @return array<string, mixed>|null Данные строки или null, если запись не найдена.
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
     * Возвращает строки, которые удовлетворяют условию callback-функции.
     *
     * @param string $table Название таблицы.
     * @param callable(array<string, mixed>):bool $predicate Callback для фильтрации.
     * @return array<int, array<string, mixed>> Отфильтрованные строки.
     */
    public function where(string $table, callable $predicate): array
    {
        $this->guardTable($table);

        return array_values(array_filter($this->data[$table], $predicate));
    }

    /**
     * Добавляет строку в таблицу и сохраняет базу данных.
     *
     * @param string $table Название таблицы.
     * @param array<string, mixed> $row Значения строки.
     * @return array<string, mixed> Добавленная строка с идентификатором.
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
     * Обновляет строку по идентификатору и сохраняет базу данных.
     *
     * @param string $table Название таблицы.
     * @param int $id Идентификатор строки.
     * @param array<string, mixed> $values Значения для объединения со строкой.
     * @return array<string, mixed>|null Обновленная строка или null, если запись не найдена.
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
     * Удаляет строку по идентификатору и сохраняет базу данных.
     *
     * @param string $table Название таблицы.
     * @param int $id Идентификатор строки.
     * @return bool True, если строка была удалена.
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
     * Загружает базу с диска или создает базу с начальными данными.
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
     * Сохраняет текущее состояние базы данных на диск.
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
     * Вычисляет следующий числовой идентификатор для таблицы.
     *
     * @param string $table Название таблицы.
     * @return int Следующий идентификатор.
     */
    private function nextId(string $table): int
    {
        $ids = array_map(static fn (array $row): int => (int) $row['id'], $this->data[$table]);

        return $ids === [] ? 1 : max($ids) + 1;
    }

    /**
     * Проверяет, что таблица существует в базе данных.
     *
     * @param string $table Название таблицы.
     * @return void
     */
    private function guardTable(string $table): void
    {
        if (!in_array($table, $this->tables, true)) {
            throw new \InvalidArgumentException('Неизвестная таблица: ' . $table);
        }
    }

    /**
     * Возвращает начальное содержимое базы данных для первого запуска.
     *
     * @return array<string, array<int, array<string, mixed>>> Таблицы с начальными данными.
     */
    private function seedData(): array
    {
        $now = date('c');

        return [
            'users' => [
                [
                    'id' => 1,
                    'name' => 'Главный администратор',
                    'email' => 'admin@devicestore.local',
                    'password_hash' => password_hash('Admin12345!', PASSWORD_DEFAULT),
                    'role' => 'admin',
                    'reset_token_hash' => null,
                    'reset_expires_at' => null,
                    'created_at' => $now,
                ],
                [
                    'id' => 2,
                    'name' => 'Демо покупатель',
                    'email' => 'user@devicestore.local',
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
                    'image_url' => 'https://images.unsplash.com/photo-1760377821978-636dcc65eb48?auto=format&fit=crop&w=1200&q=80',
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
                    'image_url' => 'https://images.unsplash.com/photo-1743862558369-5dcea79ccbff?auto=format&fit=crop&w=1200&q=80',
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
                    'image_url' => 'https://images.unsplash.com/photo-1554876194-024e06bbc3cf?auto=format&fit=crop&w=1200&q=80',
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
                    'image_url' => 'https://images.unsplash.com/photo-1753557346289-7f7bd0576d05?auto=format&fit=crop&w=1200&q=80',
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
                    'image_url' => 'https://images.unsplash.com/photo-1552820081-00b3187b7a57?auto=format&fit=crop&w=1200&q=80',
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
                    'image_url' => 'https://images.unsplash.com/photo-1752348703679-b6004d44b71d?auto=format&fit=crop&w=1200&q=80',
                    'is_featured' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
            'orders' => [],
        ];
    }
}
