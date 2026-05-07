<?php

declare(strict_types=1);

/**
 * Экранирует значение для безопасного вывода в HTML.
 *
 * @param mixed $value Значение для вывода в HTML.
 * @return string Экранированная строка.
 */
function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Формирует ссылку приложения с query-параметрами.
 *
 * @param string $page Название маршрута страницы.
 * @param array<string, mixed> $params Дополнительные параметры запроса.
 * @return string Относительная ссылка для front controller.
 */
function url(string $page, array $params = []): string
{
    $query = http_build_query(array_merge(['page' => $page], $params));

    return 'index.php?' . $query;
}

/**
 * Перенаправляет браузер на другую страницу приложения.
 *
 * @param string $page Название маршрута страницы.
 * @param array<string, mixed> $params Дополнительные параметры запроса.
 * @return never
 */
function redirect(string $page, array $params = []): never
{
    header('Location: ' . url($page, $params));
    exit;
}

/**
 * Сохраняет короткое сообщение в текущей сессии.
 *
 * @param string $type Тип сообщения: success, error или info.
 * @param string $message Текст сообщения.
 * @return void
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Возвращает и очищает flash-сообщения из текущей сессии.
 *
 * @return array<int, array{type:string,message:string}> Список сообщений.
 */
function consume_flash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $messages;
}

/**
 * Возвращает отправленное значение или значение по умолчанию для повторного заполнения формы.
 *
 * @param array<string, mixed> $source Массив входных данных.
 * @param string $key Название поля.
 * @param mixed $default Значение по умолчанию.
 * @return mixed Значение поля или значение по умолчанию.
 */
function field_value(array $source, string $key, mixed $default = ''): mixed
{
    return $source[$key] ?? $default;
}

/**
 * Форматирует цену для вывода.
 *
 * @param int|float|string $price Числовое значение цены.
 * @return string Цена в удобном формате с валютой.
 */
function money(int|float|string $price): string
{
    return number_format((float) $price, 2, '.', ' ') . ' MDL';
}

/**
 * Возвращает читаемое название типа товара.
 *
 * @param string $type Тип товара из хранилища.
 * @return string Название типа товара.
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
 * Возвращает читаемое название статуса заказа.
 *
 * @param string $status Статус заказа из хранилища.
 * @return string Название статуса.
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
