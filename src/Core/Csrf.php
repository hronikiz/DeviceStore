<?php

declare(strict_types=1);

namespace BotGear\Core;

/**
 * Provides CSRF token generation and validation for forms.
 */
final class Csrf
{
    /**
     * Returns the current CSRF token and creates it when needed.
     *
     * @return string CSRF token.
     */
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_token'];
    }

    /**
     * Validates a submitted CSRF token.
     *
     * @param string|null $token Token from a form.
     * @return bool True when the token matches the session.
     */
    public static function validate(?string $token): bool
    {
        return is_string($token)
            && isset($_SESSION['csrf_token'])
            && hash_equals((string) $_SESSION['csrf_token'], $token);
    }

    /**
     * Builds a hidden HTML input with the CSRF token.
     *
     * @return string Hidden CSRF input.
     */
    public static function input(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . \h(self::token()) . '">';
    }
}
