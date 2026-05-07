<?php

declare(strict_types=1);

use BotGear\Core\FileDatabase;
use BotGear\Repositories\CategoryRepository;
use BotGear\Repositories\ProductRepository;

$config = require dirname(__DIR__) . '/src/bootstrap.php';

$token = getenv('BOT_TOKEN');

if ($token === false || trim($token) === '') {
    fwrite(STDERR, "Set BOT_TOKEN before running the bot.\n");
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

echo "BotGear Telegram bot is running. Press Ctrl+C to stop.\n";

while (true) {
    $updates = telegramRequest($token, 'getUpdates', [
        'offset' => $offset,
        'timeout' => 25,
        'allowed_updates' => ['message'],
    ]);

    foreach (($updates['result'] ?? []) as $update) {
        $offset = (int) $update['update_id'] + 1;
        file_put_contents($offsetPath, (string) $offset, LOCK_EX);

        $message = $update['message'] ?? null;

        if (!is_array($message) || !isset($message['chat']['id'])) {
            continue;
        }

        $chatId = (int) $message['chat']['id'];
        $text = trim((string) ($message['text'] ?? ''));
        $reply = handleCommand($text, $products, $categoryMap);

        telegramRequest($token, 'sendMessage', [
            'chat_id' => $chatId,
            'text' => $reply,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);
    }
}

/**
 * Sends a request to the Telegram Bot API.
 *
 * @param string $token Bot token from BotFather.
 * @param string $method Telegram API method name.
 * @param array<string, mixed> $payload Request payload.
 * @return array<string, mixed> Decoded Telegram response.
 */
function telegramRequest(string $token, string $method, array $payload): array
{
    $ch = curl_init('https://api.telegram.org/bot' . $token . '/' . $method);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 35,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('Telegram request failed: ' . $error);
    }

    curl_close($ch);

    $decoded = json_decode((string) $response, true);

    return is_array($decoded) ? $decoded : [];
}

/**
 * Builds a bot response for a text command.
 *
 * @param string $text User message text.
 * @param ProductRepository $products Product repository.
 * @param array<int, string> $categoryMap Category names indexed by identifier.
 * @return string Response text.
 */
function handleCommand(string $text, ProductRepository $products, array $categoryMap): string
{
    if ($text === '' || $text === '/start') {
        return "Здравствуйте! Это BotGear Store.\n\n"
            . "Команды:\n"
            . "/catalog - показать товары\n"
            . "/product_1 - открыть товар по номеру\n"
            . "/help - помощь";
    }

    if ($text === '/help') {
        return "Выберите товар через /catalog, затем отправьте команду вида /product_1. "
            . "Заказ оформляется на сайте, чтобы сохранить контактные данные и историю заявок.";
    }

    if ($text === '/catalog') {
        $lines = ["<b>Каталог BotGear</b>"];

        foreach (array_slice($products->all(), 0, 8) as $product) {
            $lines[] = sprintf(
                "#%d %s - %s",
                (int) $product['id'],
                htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'),
                formatBotMoney($product['price'])
            );
        }

        $lines[] = "\nОтправьте /product_1, чтобы открыть товар по номеру.";

        return implode("\n", $lines);
    }

    if (preg_match('/^\/product_(\d+)$/', $text, $matches) === 1) {
        $product = $products->find((int) $matches[1]);

        if ($product === null) {
            return 'Товар не найден. Откройте список командой /catalog.';
        }

        $category = $categoryMap[(int) $product['category_id']] ?? 'Каталог';

        return sprintf(
            "<b>%s</b>\n%s\nЦена: %s\nВ наличии: %d\nКатегория: %s\n\nОформите заказ на сайте магазина.",
            htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) $product['description'], ENT_QUOTES, 'UTF-8'),
            formatBotMoney($product['price']),
            (int) $product['stock'],
            htmlspecialchars($category, ENT_QUOTES, 'UTF-8')
        );
    }

    return 'Команда не распознана. Откройте /help или /catalog.';
}

/**
 * Formats a price for Telegram messages.
 *
 * @param int|float|string $price Numeric price.
 * @return string Formatted price.
 */
function formatBotMoney(int|float|string $price): string
{
    return number_format((float) $price, 2, '.', ' ') . ' MDL';
}
