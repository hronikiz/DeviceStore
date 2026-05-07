<?php

declare(strict_types=1);

use DeviceStore\Core\FileDatabase;
use DeviceStore\Repositories\CategoryRepository;
use DeviceStore\Repositories\ProductRepository;

$config = require dirname(__DIR__) . '/src/bootstrap.php';

$token = getenv('BOT_TOKEN');

if ($token === false || trim($token) === '') {
    fwrite(STDERR, "Укажите BOT_TOKEN перед запуском бота.\n");
    exit(1);
}

$db = new FileDatabase((string) $config['database_path']);
$products = new ProductRepository($db);
$categories = new CategoryRepository($db);
$categoryMap = [];

foreach ($categories->all() as $category) {
    $categoryMap[(int) $category['id']] = (string) $category['name'];
}

$offsetPath = dirname(__DIR__) . '/data/bot_offset.txt';
$offset = is_file($offsetPath) ? (int) trim((string) file_get_contents($offsetPath)) : 0;

echo "Telegram-бот DeviceStore запущен. Для остановки нажмите Ctrl+C.\n";
$processedUpdateIds = [];

while (true) {
    try {
        $updates = telegramRequest($token, 'getUpdates', [
            'offset' => $offset,
            'timeout' => 25,
            'allowed_updates' => ['message'],
        ]);
    } catch (RuntimeException $exception) {
        fwrite(STDERR, $exception->getMessage() . "\n");
        sleep(5);
        continue;
    }

    foreach (($updates['result'] ?? []) as $update) {
        if (!isset($update['update_id'])) {
            continue;
        }

        $updateId = (int) $update['update_id'];

        if (isset($processedUpdateIds[$updateId])) {
            continue;
        }

        $processedUpdateIds[$updateId] = true;
        $offset = max($offset, $updateId + 1);
        file_put_contents($offsetPath, (string) $offset, LOCK_EX);

        $message = $update['message'] ?? null;

        if (!is_array($message) || !isset($message['chat']['id'])) {
            continue;
        }

        $chatId = (int) $message['chat']['id'];
        $text = trim((string) ($message['text'] ?? ''));
        $reply = handleCommand($text, $products, $categoryMap);

        try {
            telegramRequest($token, 'sendMessage', [
                'chat_id' => $chatId,
                'text' => $reply,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);
        } catch (RuntimeException $exception) {
            fwrite(STDERR, $exception->getMessage() . "\n");
        }
    }
}

/**
 * Отправляет запрос к Telegram Bot API.
 *
 * @param string $token Токен бота от BotFather.
 * @param string $method Название метода Telegram API.
 * @param array<string, mixed> $payload Данные запроса.
 * @return array<string, mixed> Раскодированный ответ Telegram.
 */
function telegramRequest(string $token, string $method, array $payload): array
{
    $ch = curl_init('https://api.telegram.org/bot' . $token . '/' . $method);
    curl_setopt_array($ch, telegramCurlOptions($payload));

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        $errorCode = curl_errno($ch);
        curl_close($ch);

        if ($errorCode === 60 || str_contains($error, 'SSL certificate') || str_contains($error, 'self-signed certificate')) {
            enableTelegramInsecureFallback();
            $ch = curl_init('https://api.telegram.org/bot' . $token . '/' . $method);
            curl_setopt_array($ch, telegramCurlOptions($payload));
            $response = curl_exec($ch);

            if ($response !== false) {
                curl_close($ch);
                $decoded = json_decode((string) $response, true);

                return is_array($decoded) ? $decoded : [];
            }

            $error = curl_error($ch);
            curl_close($ch);
        }

        throw new RuntimeException('Запрос к Telegram не выполнен: ' . $error);
    }

    curl_close($ch);

    $decoded = json_decode((string) $response, true);

    return is_array($decoded) ? $decoded : [];
}

/**
 * Формирует настройки cURL для запросов Telegram.
 *
 * @param array<string, mixed> $payload Данные запроса.
 * @return array<int, mixed> Настройки cURL.
 */
function telegramCurlOptions(array $payload): array
{
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 35,
    ];

    $caPath = getenv('BOT_CACERT');
    $localCaPath = dirname(__DIR__) . '/certs/cacert.pem';

    if (is_string($caPath) && $caPath !== '' && is_file($caPath)) {
        $options[CURLOPT_CAINFO] = $caPath;
    } elseif (is_file($localCaPath)) {
        $options[CURLOPT_CAINFO] = $localCaPath;
    }

    if (getenv('BOT_ALLOW_INSECURE_SSL') === '1' || ($GLOBALS['telegram_insecure_ssl'] ?? false) === true) {
        $options[CURLOPT_SSL_VERIFYPEER] = false;
        $options[CURLOPT_SSL_VERIFYHOST] = false;
    }

    return $options;
}

/**
 * Включает резервный режим для локальных Windows-сред, где PHP не видит корневые сертификаты.
 *
 * @return void
 */
function enableTelegramInsecureFallback(): void
{
    if (($GLOBALS['telegram_insecure_ssl'] ?? false) === true) {
        return;
    }

    $GLOBALS['telegram_insecure_ssl'] = true;
    fwrite(
        STDERR,
        "Предупреждение: PHP не смог проверить SSL-сертификат Telegram. "
        . "Для локального запуска включен резервный режим без проверки SSL. "
        . "Лучшее решение: скачать cacert.pem и указать путь в BOT_CACERT.\n"
    );
}

/**
 * Формирует ответ бота на текстовую команду.
 *
 * @param string $text Текст сообщения пользователя.
 * @param ProductRepository $products Репозиторий товаров.
 * @param array<int, string> $categoryMap Названия категорий по идентификаторам.
 * @return string Текст ответа.
 */
function handleCommand(string $text, ProductRepository $products, array $categoryMap): string
{
    if ($text === '' || $text === '/start') {
        return "Здравствуйте! Это DeviceStore.\n\n"
            . "Команды:\n"
            . "/catalog - показать товары\n"
            . "/product_1 - открыть товар по номеру\n"
            . "/site - перейти на сайт магазина\n"
            . "/help - помощь";
    }

    if ($text === '/help') {
        return "Выберите товар через /catalog, затем отправьте команду вида /product_1. "
            . "Заказ оформляется на сайте, чтобы сохранить контактные данные и историю заявок. "
            . "Для быстрого перехода используйте /site.";
    }

    if ($text === '/site') {
        return sprintf(
            "Открыть магазин: <a href=\"%s\">%s</a>",
            htmlspecialchars(buildSiteUrl('home'), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars(buildSiteUrl('home'), ENT_QUOTES, 'UTF-8')
        );
    }

    if ($text === '/catalog') {
        $lines = ["<b>Каталог DeviceStore</b>"];

        foreach (array_slice($products->all(), 0, 8) as $product) {
            $productUrl = buildSiteUrl('product', ['id' => (int) $product['id']]);

            $lines[] = sprintf(
                "#%d %s - %s\n%s",
                (int) $product['id'],
                htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'),
                formatBotMoney($product['price']),
                htmlspecialchars($productUrl, ENT_QUOTES, 'UTF-8')
            );
        }

        $lines[] = sprintf(
            "\nОформите заказ на сайте: <a href=\"%s\">Перейти в каталог</a>",
            htmlspecialchars(buildSiteUrl('home'), ENT_QUOTES, 'UTF-8')
        );
        $lines[] = 'Отправьте /product_1, чтобы открыть товар по номеру.';

        return implode("\n", $lines);
    }

    if (preg_match('/^\/product_(\d+)$/', $text, $matches) === 1) {
        $product = $products->find((int) $matches[1]);

        if ($product === null) {
            return 'Товар не найден. Откройте список командой /catalog.';
        }

        $category = $categoryMap[(int) $product['category_id']] ?? 'Каталог';
        $productUrl = buildSiteUrl('product', ['id' => (int) $product['id']]);

        return sprintf(
            "<b>%s</b>\n%s\nЦена: %s\nВ наличии: %d\nКатегория: %s\n\nОткрыть страницу товара: <a href=\"%s\">Перейти</a>",
            htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) $product['description'], ENT_QUOTES, 'UTF-8'),
            formatBotMoney($product['price']),
            (int) $product['stock'],
            htmlspecialchars($category, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($productUrl, ENT_QUOTES, 'UTF-8')
        );
    }

    return 'Команда не распознана. Откройте /help или /catalog.';
}

/**
 * Возвращает базовый URL сайта для ссылок в боте.
 *
 * @return string
 */
function getBotSiteUrl(): string
{
    $siteUrl = getenv('BOT_SITE_URL');

    if (!is_string($siteUrl) || trim($siteUrl) === '') {
        return 'http://127.0.0.1:8000/index.php';
    }

    return rtrim($siteUrl, '/');
}

/**
 * Формирует полный URL страницы сайта.
 *
 * @param string $page
 * @param array<string, mixed> $params
 * @return string
 */
function buildSiteUrl(string $page, array $params = []): string
{
    $baseUrl = getBotSiteUrl();
    $params = array_merge(['page' => $page], $params);

    return $baseUrl . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

/**
 * Форматирует цену для сообщений Telegram.
 *
 * @param int|float|string $price Числовое значение цены.
 * @return string Отформатированная цена.
 */
function formatBotMoney(int|float|string $price): string
{
    return number_format((float) $price, 2, '.', ' ') . ' MDL';
}
