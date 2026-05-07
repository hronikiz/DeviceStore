<?php

declare(strict_types=1);

namespace DeviceStore\Repositories;

use DeviceStore\Core\FileDatabase;

/**
 * Выполняет операции хранения пользователей.
 */
final class UserRepository
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
     * Возвращает всех пользователей с сортировкой по идентификатору.
     *
     * @return array<int, array<string, mixed>> Записи пользователей.
     */
    public function all(): array
    {
        return $this->db->all('users');
    }

    /**
     * Ищет пользователя по идентификатору.
     *
     * @param int $id Идентификатор пользователя.
     * @return array<string, mixed>|null Запись пользователя или null.
     */
    public function find(int $id): ?array
    {
        return $this->db->find('users', $id);
    }

    /**
     * Ищет пользователя по email.
     *
     * @param string $email Email-адрес.
     * @return array<string, mixed>|null Запись пользователя или null.
     */
    public function findByEmail(string $email): ?array
    {
        $email = mb_strtolower(trim($email));
        $users = $this->db->where(
            'users',
            static fn (array $user): bool => mb_strtolower((string) $user['email']) === $email
        );

        return $users[0] ?? null;
    }

    /**
     * Создает пользователя с безопасно хешированным паролем.
     *
     * @param string $name Отображаемое имя.
     * @param string $email Email-адрес.
     * @param string $password Пароль в открытом виде.
     * @param string $role Роль пользователя.
     * @return array<string, mixed> Созданный пользователь.
     */
    public function create(string $name, string $email, string $password, string $role = 'user'): array
    {
        return $this->db->insert('users', [
            'name' => $name,
            'email' => mb_strtolower(trim($email)),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'reset_token_hash' => null,
            'reset_expires_at' => null,
            'created_at' => date('c'),
        ]);
    }

    /**
     * Создает и сохраняет токен восстановления пароля для пользователя.
     *
     * @param string $email Email пользователя.
     * @return string|null Открытый токен для отправки или null, если пользователя нет.
     */
    public function createResetToken(string $email): ?string
    {
        $user = $this->findByEmail($email);

        if ($user === null) {
            return null;
        }

        $token = bin2hex(random_bytes(24));
        $this->db->update('users', (int) $user['id'], [
            'reset_token_hash' => hash('sha256', $token),
            'reset_expires_at' => date('c', time() + 3600),
        ]);

        return $token;
    }

    /**
     * Ищет пользователя по токену восстановления пароля.
     *
     * @param string $token Открытый токен восстановления.
     * @return array<string, mixed>|null Запись пользователя или null.
     */
    public function findByResetToken(string $token): ?array
    {
        $tokenHash = hash('sha256', $token);
        $users = $this->db->where(
            'users',
            static function (array $user) use ($tokenHash): bool {
                if (($user['reset_token_hash'] ?? null) !== $tokenHash) {
                    return false;
                }

                return strtotime((string) ($user['reset_expires_at'] ?? '')) > time();
            }
        );

        return $users[0] ?? null;
    }

    /**
     * Обновляет пароль пользователя и очищает данные токена восстановления.
     *
     * @param int $id Идентификатор пользователя.
     * @param string $password Новый пароль в открытом виде.
     * @return bool True, если пользователь был обновлен.
     */
    public function updatePassword(int $id, string $password): bool
    {
        return $this->db->update('users', $id, [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'reset_token_hash' => null,
            'reset_expires_at' => null,
        ]) !== null;
    }
}
