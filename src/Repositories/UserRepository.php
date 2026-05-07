<?php

declare(strict_types=1);

namespace BotGear\Repositories;

use BotGear\Core\FileDatabase;

/**
 * Provides user persistence operations.
 */
final class UserRepository
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
     * Returns all users ordered by identifier.
     *
     * @return array<int, array<string, mixed>> User records.
     */
    public function all(): array
    {
        return $this->db->all('users');
    }

    /**
     * Finds a user by identifier.
     *
     * @param int $id User identifier.
     * @return array<string, mixed>|null User record or null.
     */
    public function find(int $id): ?array
    {
        return $this->db->find('users', $id);
    }

    /**
     * Finds a user by email address.
     *
     * @param string $email Email address.
     * @return array<string, mixed>|null User record or null.
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
     * Creates a user with a securely hashed password.
     *
     * @param string $name Display name.
     * @param string $email Email address.
     * @param string $password Plain password.
     * @param string $role User role.
     * @return array<string, mixed> Created user.
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
     * Creates and stores a password reset token for a user.
     *
     * @param string $email User email.
     * @return string|null Plain token for demo delivery or null when user is absent.
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
     * Finds a user by password reset token.
     *
     * @param string $token Plain reset token.
     * @return array<string, mixed>|null User record or null.
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
     * Updates a user password and clears reset token data.
     *
     * @param int $id User identifier.
     * @param string $password New plain password.
     * @return bool True when the user was updated.
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
