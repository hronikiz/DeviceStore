<?php

declare(strict_types=1);

namespace DeviceStore\Core;

use DeviceStore\Repositories\UserRepository;

/**
 * Управляет пользовательскими сессиями и проверками доступа.
 */
final class Auth
{
    private UserRepository $users;

    /**
     * Запускает защищенную сессию и сохраняет репозиторий пользователей.
     *
     * @param UserRepository $users Репозиторий для загрузки пользователей.
     */
    public function __construct(UserRepository $users)
    {
        $this->users = $users;
        $this->startSession();
    }

    /**
     * Пытается авторизовать пользователя по email и паролю.
     *
     * @param string $email Email пользователя.
     * @param string $password Пароль из формы входа.
     * @return bool True, если данные входа корректны.
     */
    public function attempt(string $email, string $password): bool
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        $this->login($user);

        return true;
    }

    /**
     * Сохраняет авторизованного пользователя в сессии.
     *
     * @param array<string, mixed> $user Запись пользователя.
     * @return void
     */
    public function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
    }

    /**
     * Удаляет авторизованного пользователя из сессии.
     *
     * @return void
     */
    public function logout(): void
    {
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
    }

    /**
     * Возвращает текущего авторизованного пользователя.
     *
     * @return array<string, mixed>|null Запись пользователя или null.
     */
    public function user(): ?array
    {
        $id = $_SESSION['user_id'] ?? null;

        return is_int($id) ? $this->users->find($id) : null;
    }

    /**
     * Проверяет, авторизован ли посетитель.
     *
     * @return bool True, если пользователь вошел в систему.
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Проверяет, есть ли у текущего пользователя права администратора.
     *
     * @return bool True для администраторов.
     */
    public function isAdmin(): bool
    {
        $user = $this->user();

        return $user !== null && ($user['role'] ?? '') === 'admin';
    }

    /**
     * Запускает PHP-сессию с более безопасными настройками cookie.
     *
     * @return void
     */
    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}
