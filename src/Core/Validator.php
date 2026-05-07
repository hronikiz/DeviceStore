<?php

declare(strict_types=1);

namespace BotGear\Core;

/**
 * Validates and normalizes user input from application forms.
 */
final class Validator
{
    /**
     * Validates registration data.
     *
     * @param array<string, mixed> $input Raw form input.
     * @return array{errors:array<string,string>,data:array<string,mixed>} Validation result.
     */
    public static function register(array $input): array
    {
        $data = [
            'name' => trim((string) ($input['name'] ?? '')),
            'email' => mb_strtolower(trim((string) ($input['email'] ?? ''))),
            'password' => (string) ($input['password'] ?? ''),
            'password_confirmation' => (string) ($input['password_confirmation'] ?? ''),
        ];
        $errors = [];

        if (mb_strlen($data['name']) < 2) {
            $errors['name'] = 'Введите имя длиной минимум 2 символа.';
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Введите корректный email.';
        }

        self::validatePassword($data['password'], $errors);

        if ($data['password'] !== $data['password_confirmation']) {
            $errors['password_confirmation'] = 'Пароли должны совпадать.';
        }

        return ['errors' => $errors, 'data' => $data];
    }

    /**
     * Validates login data.
     *
     * @param array<string, mixed> $input Raw form input.
     * @return array{errors:array<string,string>,data:array<string,mixed>} Validation result.
     */
    public static function login(array $input): array
    {
        $data = [
            'email' => mb_strtolower(trim((string) ($input['email'] ?? ''))),
            'password' => (string) ($input['password'] ?? ''),
        ];
        $errors = [];

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Введите корректный email.';
        }

        if ($data['password'] === '') {
            $errors['password'] = 'Введите пароль.';
        }

        return ['errors' => $errors, 'data' => $data];
    }

    /**
     * Validates product creation or editing data.
     *
     * @param array<string, mixed> $input Raw form input.
     * @return array{errors:array<string,string>,data:array<string,mixed>} Validation result.
     */
    public static function product(array $input): array
    {
        $data = [
            'name' => trim((string) ($input['name'] ?? '')),
            'category_id' => (int) ($input['category_id'] ?? 0),
            'product_type' => (string) ($input['product_type'] ?? ''),
            'price' => (float) str_replace(',', '.', (string) ($input['price'] ?? 0)),
            'stock' => (int) ($input['stock'] ?? 0),
            'image_url' => trim((string) ($input['image_url'] ?? 'assets/component.svg')),
            'description' => trim((string) ($input['description'] ?? '')),
            'is_featured' => isset($input['is_featured']),
        ];
        $errors = [];

        if (mb_strlen($data['name']) < 3) {
            $errors['name'] = 'Название должно содержать минимум 3 символа.';
        }

        if ($data['category_id'] <= 0) {
            $errors['category_id'] = 'Выберите категорию.';
        }

        if (!in_array($data['product_type'], ['headset', 'component', 'peripheral'], true)) {
            $errors['product_type'] = 'Выберите тип товара.';
        }

        if ($data['price'] <= 0 || $data['price'] > 50000) {
            $errors['price'] = 'Цена должна быть больше 0 и не выше 50 000.';
        }

        if ($data['stock'] < 0 || $data['stock'] > 10000) {
            $errors['stock'] = 'Количество должно быть от 0 до 10 000.';
        }

        if (!self::isValidImagePath($data['image_url'])) {
            $errors['image_url'] = 'Укажите URL изображения или локальный путь из папки assets.';
        }

        if (mb_strlen($data['description']) < 20) {
            $errors['description'] = 'Описание должно содержать минимум 20 символов.';
        }

        return ['errors' => $errors, 'data' => $data];
    }

    /**
     * Validates order data.
     *
     * @param array<string, mixed> $input Raw form input.
     * @param array<string, mixed> $product Product being ordered.
     * @return array{errors:array<string,string>,data:array<string,mixed>} Validation result.
     */
    public static function order(array $input, array $product): array
    {
        $data = [
            'quantity' => (int) ($input['quantity'] ?? 1),
            'customer_telegram' => trim((string) ($input['customer_telegram'] ?? '')),
            'customer_phone' => trim((string) ($input['customer_phone'] ?? '')),
            'delivery_address' => trim((string) ($input['delivery_address'] ?? '')),
            'note' => trim((string) ($input['note'] ?? '')),
        ];
        $errors = [];

        if ($data['quantity'] < 1) {
            $errors['quantity'] = 'Количество должно быть минимум 1.';
        }

        if ($data['quantity'] > (int) $product['stock']) {
            $errors['quantity'] = 'На складе нет такого количества товара.';
        }

        if (!preg_match('/^@?[A-Za-z0-9_]{5,32}$/', $data['customer_telegram'])) {
            $errors['customer_telegram'] = 'Введите Telegram username, например @botgear_user.';
        }

        if (!preg_match('/^[0-9+()\\s-]{6,20}$/', $data['customer_phone'])) {
            $errors['customer_phone'] = 'Введите корректный номер телефона.';
        }

        if (mb_strlen($data['delivery_address']) < 8) {
            $errors['delivery_address'] = 'Введите адрес доставки.';
        }

        if (mb_strlen($data['note']) > 500) {
            $errors['note'] = 'Комментарий не должен превышать 500 символов.';
        }

        return ['errors' => $errors, 'data' => $data];
    }

    /**
     * Validates category data.
     *
     * @param array<string, mixed> $input Raw form input.
     * @return array{errors:array<string,string>,data:array<string,mixed>} Validation result.
     */
    public static function category(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $errors = [];

        if (mb_strlen($name) < 3) {
            $errors['name'] = 'Название категории должно содержать минимум 3 символа.';
        }

        return ['errors' => $errors, 'data' => ['name' => $name]];
    }

    /**
     * Validates administrator creation data.
     *
     * @param array<string, mixed> $input Raw form input.
     * @return array{errors:array<string,string>,data:array<string,mixed>} Validation result.
     */
    public static function adminUser(array $input): array
    {
        $result = self::register($input);
        $result['data']['role'] = 'admin';

        return $result;
    }

    /**
     * Validates password reset request data.
     *
     * @param array<string, mixed> $input Raw form input.
     * @return array{errors:array<string,string>,data:array<string,mixed>} Validation result.
     */
    public static function forgot(array $input): array
    {
        $email = mb_strtolower(trim((string) ($input['email'] ?? '')));
        $errors = [];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Введите корректный email.';
        }

        return ['errors' => $errors, 'data' => ['email' => $email]];
    }

    /**
     * Validates new password data for reset flow.
     *
     * @param array<string, mixed> $input Raw form input.
     * @return array{errors:array<string,string>,data:array<string,mixed>} Validation result.
     */
    public static function resetPassword(array $input): array
    {
        $data = [
            'password' => (string) ($input['password'] ?? ''),
            'password_confirmation' => (string) ($input['password_confirmation'] ?? ''),
        ];
        $errors = [];

        self::validatePassword($data['password'], $errors);

        if ($data['password'] !== $data['password_confirmation']) {
            $errors['password_confirmation'] = 'Пароли должны совпадать.';
        }

        return ['errors' => $errors, 'data' => $data];
    }

    /**
     * Adds password validation errors when password rules are not met.
     *
     * @param string $password Password value.
     * @param array<string, string> $errors Mutable error collection.
     * @return void
     */
    private static function validatePassword(string $password, array &$errors): void
    {
        if (mb_strlen($password) < 8) {
            $errors['password'] = 'Пароль должен содержать минимум 8 символов.';
        }
    }

    /**
     * Checks whether an image path is a safe local asset or a valid URL.
     *
     * @param string $path Image path.
     * @return bool True when the path is acceptable.
     */
    private static function isValidImagePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return true;
        }

        return str_starts_with($path, 'assets/') && !str_contains($path, '..');
    }
}
