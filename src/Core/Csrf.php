<?php

declare(strict_types=1);

namespace DeviceStore\Core;

/**
 * Генерирует и проверяет CSRF-токены для форм.
 */
final class Csrf
{
    /**
     * Возвращает текущий CSRF-токен и создает его при необходимости.
     *
     * @return string CSRF-токен.
     */
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_token'];
    }

    /**
     * Проверяет отправленный CSRF-токен.
     *
     * @param string|null $token Токен из формы.
     * @return bool True, если токен совпадает с токеном в сессии.
     */
    public static function validate(?string $token): bool
    {
        return is_string($token)
            && isset($_SESSION['csrf_token'])
            && hash_equals((string) $_SESSION['csrf_token'], $token);
    }

    /**
     * Формирует скрытое HTML-поле с CSRF-токеном.
     *
     * @return string Скрытое поле CSRF.
     */
    public static function input(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . \h(self::token()) . '">';
    }
}
