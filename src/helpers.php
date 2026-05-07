<?php

declare(strict_types=1);

/**
 * Escapes a value for safe HTML output.
 *
 * @param mixed $value Value that should be printed in HTML.
 * @return string Escaped string.
 */
function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Builds an application URL with query parameters.
 *
 * @param string $page Page route name.
 * @param array<string, mixed> $params Additional query parameters.
 * @return string Relative URL for the front controller.
 */
function url(string $page, array $params = []): string
{
    $query = http_build_query(array_merge(['page' => $page], $params));

    return 'index.php?' . $query;
}

/**
 * Redirects the browser to another application page.
 *
 * @param string $page Page route name.
 * @param array<string, mixed> $params Additional query parameters.
 * @return never
 */
function redirect(string $page, array $params = []): never
{
    header('Location: ' . url($page, $params));
    exit;
}

/**
 * Stores a short message in the current session.
 *
 * @param string $type Message type such as success, error, or info.
 * @param string $message Message text.
 * @return void
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Returns and clears flash messages from the current session.
 *
 * @return array<int, array{type:string,message:string}> Flash messages.
 */
function consume_flash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $messages;
}

/**
 * Reads a submitted value or fallback value for form repopulation.
 *
 * @param array<string, mixed> $source Input array.
 * @param string $key Field name.
 * @param mixed $default Default value.
 * @return mixed Existing field value or default.
 */
function field_value(array $source, string $key, mixed $default = ''): mixed
{
    return $source[$key] ?? $default;
}

/**
 * Formats a price for display.
 *
 * @param int|float|string $price Numeric price.
 * @return string Human-readable price with currency.
 */
function money(int|float|string $price): string
{
    return number_format((float) $price, 2, '.', ' ') . ' MDL';
}

/**
 * Returns a readable product type label.
 *
 * @param string $type Stored product type.
 * @return string Product type label.
 */
function product_type_label(string $type): string
{
    return match ($type) {
        'headset' => 'Гарнитура',
        'component' => 'Комплектующее',
        'peripheral' => 'Периферия',
        default => 'Товар',
    };
}

/**
 * Returns a readable order status label.
 *
 * @param string $status Stored order status.
 * @return string Status label.
 */
function order_status_label(string $status): string
{
    return match ($status) {
        'new' => 'Новый',
        'processing' => 'В обработке',
        'shipped' => 'Отправлен',
        'done' => 'Завершен',
        'cancelled' => 'Отменен',
        default => 'Неизвестно',
    };
}
