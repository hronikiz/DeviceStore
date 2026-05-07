<?php

declare(strict_types=1);

namespace BotGear\Core;

use BotGear\Repositories\UserRepository;

/**
 * Manages user sessions and authorization checks.
 */
final class Auth
{
    private UserRepository $users;

    /**
     * Starts a secure session and stores the user repository.
     *
     * @param UserRepository $users Repository used to load users.
     */
    public function __construct(UserRepository $users)
    {
        $this->users = $users;
        $this->startSession();
    }

    /**
     * Attempts to authenticate a user by email and password.
     *
     * @param string $email User email.
     * @param string $password Plain password from the login form.
     * @return bool True when credentials are valid.
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
     * Stores an authenticated user in the session.
     *
     * @param array<string, mixed> $user User record.
     * @return void
     */
    public function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
    }

    /**
     * Removes the authenticated user from the session.
     *
     * @return void
     */
    public function logout(): void
    {
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
    }

    /**
     * Returns the current authenticated user.
     *
     * @return array<string, mixed>|null User record or null.
     */
    public function user(): ?array
    {
        $id = $_SESSION['user_id'] ?? null;

        return is_int($id) ? $this->users->find($id) : null;
    }

    /**
     * Checks whether a visitor is authenticated.
     *
     * @return bool True when a user is logged in.
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Checks whether the current user has administrator privileges.
     *
     * @return bool True for administrators.
     */
    public function isAdmin(): bool
    {
        $user = $this->user();

        return $user !== null && ($user['role'] ?? '') === 'admin';
    }

    /**
     * Starts a PHP session with safer cookie options.
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
