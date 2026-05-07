<?php

declare(strict_types=1);

use DeviceStore\Repositories\CategoryRepository;
use DeviceStore\Repositories\ProductRepository;

final class TelegramBot
{
    private TelegramClient $client;
    private ProductRepository $products;
    private CategoryRepository $categories;
    private string $offsetPath;
    private int $offset;
    private array $categoryMap = [];

    public function __construct(string $token, ProductRepository $products, CategoryRepository $categories)
    {
        $this->client = new TelegramClient($token);
        $this->products = $products;
        $this->categories = $categories;
        $this->offsetPath = dirname(__DIR__) . '/data/bot_offset.txt';
        $this->offset = $this->readOffset();
    }

    public function run(): void
    {
        echo "Telegram-бот DeviceStore запущен. Для остановки нажмите Ctrl+C.\n";

        while (true) {
            try {
                $updates = $this->client->getUpdates($this->offset, 25, ['message', 'callback_query']);
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

                if ($updateId < $this->offset) {
                    continue;
                }

                $this->offset = $updateId + 1;
                $this->saveOffset($this->offset);
                $this->handleUpdate($update);
            }
        }
    }

    private function handleUpdate(array $update): void
    {
        if (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
            return;
        }

        if (!isset($update['message']) || !is_array($update['message'])) {
            return;
        }

        $this->handleMessage($update['message']);
    }

    private function handleMessage(array $message): void
    {
        if (!isset($message['chat']['id'])) {
            return;
        }

        $chatId = (int) $message['chat']['id'];
        $text = trim((string) ($message['text'] ?? ''));

        switch (true) {
            case $text === '/start':
                $this->sendMainMenu($chatId);
                break;
            case $text === '/help':
                $this->sendHelp($chatId);
                break;
            case $text === '/catalog':
                $this->sendCatalog($chatId);
                break;
            case $text === '/support':
                $this->sendSupport($chatId);
                break;
            case $text === '/site':
                $this->sendSiteLink($chatId);
                break;
            default:
                $this->sendUnknownCommand($chatId);
                break;
        }
    }

    private function handleCallbackQuery(array $callback): void
    {
        if (!isset($callback['id'], $callback['data'], $callback['message']['chat']['id'])) {
            return;
        }

        $callbackId = (string) $callback['id'];
        $data = (string) $callback['data'];
        $chatId = (int) $callback['message']['chat']['id'];

        if (preg_match('/^view_product_(\d+)$/', $data, $matches) === 1) {
            $this->sendProduct($chatId, (int) $matches[1]);
            $this->client->answerCallbackQuery($callbackId, 'Открываю товар');
            return;
        }

        if (preg_match('/^catalog_category_(\d+)$/', $data, $matches) === 1) {
            $this->sendCategoryProducts($chatId, (int) $matches[1]);
            $this->client->answerCallbackQuery($callbackId, 'Категория');
            return;
        }

        if ($data === 'catalog') {
            $this->sendCatalog($chatId);
            $this->client->answerCallbackQuery($callbackId, 'Каталог');
            return;
        }

        if ($data === 'support') {
            $this->sendSupport($chatId);
            $this->client->answerCallbackQuery($callbackId, 'Поддержка');
            return;
        }

        if ($data === 'help') {
            $this->sendHelp($chatId);
            $this->client->answerCallbackQuery($callbackId, 'Помощь');
            return;
        }

        if ($data === 'site') {
            $this->sendSiteLink($chatId);
            $this->client->answerCallbackQuery($callbackId, 'Сайт');
            return;
        }

        $this->client->answerCallbackQuery($callbackId, 'Команда не распознана', true);
    }

    private function sendMainMenu(int $chatId): void
    {
        $text = "<b>Добро пожаловать в DeviceStore!</b>\n\n";
        $text .= "Выберите действие:\n";

        $keyboard = [
            [
                ['text' => '📦 Каталог', 'callback_data' => 'catalog'],
                ['text' => '💬 Поддержка', 'callback_data' => 'support'],
            ],
            [
                ['text' => '🌐 Сайт', 'callback_data' => 'site'],
                ['text' => '❓ Помощь', 'callback_data' => 'help'],
            ],
        ];

        $this->client->sendMessage($chatId, $text, ['reply_markup' => json_encode(['inline_keyboard' => $keyboard])]);
    }

    private function sendHelp(int $chatId): void
    {
        $text = "Команды бота:\n";
        $text .= "/start — меню\n";
        $text .= "/catalog — показать каталог\n";
        $text .= "/support — поддержка\n";
        $text .= "/site — ссылка на сайт\n";
        $text .= "\nИспользуйте кнопки, чтобы выбирать товары и переходить на сайт для покупки.";

        $keyboard = [
            [
                ['text' => '📦 Каталог', 'callback_data' => 'catalog'],
                ['text' => '💬 Поддержка', 'callback_data' => 'support'],
            ],
        ];

        $this->client->sendMessage($chatId, $text, ['reply_markup' => json_encode(['inline_keyboard' => $keyboard])]);
    }

    private function sendSupport(int $chatId): void
    {
        $text = "Если нужна помощь, напишите нам:\n";
        $text .= "@devicestore_support\n";
        $text .= "\nИли откройте сайт и оформите заказ через форму, чтобы сохранить контакты и историю.";

        $keyboard = [
            [
                ['text' => '🌐 Сайт', 'url' => $this->buildSiteUrl('home')],
                ['text' => '📦 Каталог', 'callback_data' => 'catalog'],
            ],
        ];

        $this->client->sendMessage($chatId, $text, ['reply_markup' => json_encode(['inline_keyboard' => $keyboard])]);
    }

    private function sendSiteLink(int $chatId): void
    {
        $text = sprintf("Открыть сайт магазина: <a href=\"%s\">%s</a>", $this->escape($this->buildSiteUrl('home')), $this->escape($this->buildSiteUrl('home')));
        $this->client->sendMessage($chatId, $text);
    }

    private function sendCatalog(int $chatId): void
    {
        $categories = $this->categories->all();

        if ($categories === []) {
            $this->client->sendMessage($chatId, 'Каталог пуст.');
            return;
        }

        $lines = ['<b>Каталог DeviceStore</b>', 'Выберите категорию:'];
        $buttons = [];

        foreach ($categories as $category) {
            $categoryId = (int) $category['id'];
            $categoryName = $this->escape((string) $category['name']);
            $count = $this->products->countByCategory($categoryId);

            $lines[] = sprintf('• %s (%d)', $categoryName, $count);
            $buttons[] = [
                'text' => sprintf('%s (%d)', $categoryName, $count),
                'callback_data' => 'catalog_category_' . $categoryId,
            ];
        }

        $keyboard = array_chunk($buttons, 1);
        $keyboard[] = [
            ['text' => '💬 Поддержка', 'callback_data' => 'support'],
            ['text' => '🌐 Сайт', 'callback_data' => 'site'],
        ];

        $text = implode("\n", $lines);
        $this->client->sendMessage($chatId, $text, ['reply_markup' => json_encode(['inline_keyboard' => $keyboard])]);
    }

    private function sendCategoryProducts(int $chatId, int $categoryId): void
    {
        $categoryName = $this->getCategoryName($categoryId);
        $products = $this->products->search(['category_id' => $categoryId]);

        if ($products === []) {
            $text = sprintf("<b>%s</b>\nВ этой категории пока нет товаров.", $this->escape($categoryName));
            $keyboard = [
                [['text' => '⬅️ К категориям', 'callback_data' => 'catalog']],
                [['text' => '💬 Поддержка', 'callback_data' => 'support']],
            ];
            $this->client->sendMessage($chatId, $text, ['reply_markup' => json_encode(['inline_keyboard' => $keyboard])]);
            return;
        }

        $lines = [sprintf('<b>Категория: %s</b>', $this->escape($categoryName)), 'Выберите товар:'];
        $buttons = [];

        foreach ($products as $product) {
            $lines[] = sprintf(
                '#%d %s — %s',
                (int) $product['id'],
                $this->escape((string) $product['name']),
                $this->formatMoney($product['price'])
            );

            $buttons[] = ['text' => sprintf('#%d', (int) $product['id']), 'callback_data' => 'view_product_' . (int) $product['id']];
        }

        $keyboard = array_chunk($buttons, 2);
        $keyboard[] = [
            ['text' => '⬅️ К категориям', 'callback_data' => 'catalog'],
            ['text' => '💬 Поддержка', 'callback_data' => 'support'],
        ];

        $text = implode("\n", $lines);
        $this->client->sendMessage($chatId, $text, ['reply_markup' => json_encode(['inline_keyboard' => $keyboard])]);
    }

    private function sendProduct(int $chatId, int $productId): void
    {
        $product = $this->products->find($productId);

        if ($product === null) {
            $this->client->sendMessage($chatId, 'Товар не найден.');
            return;
        }

        $caption = $this->buildProductCaption($product);
        $keyboard = $this->buildProductKeyboard($productId);
        $photo = (string) ($product['image_url'] ?? '');

        if ($photo !== '' && !$this->isHttpUrl($photo)) {
            $localPhoto = $this->getLocalImagePath($photo);

            if ($localPhoto !== null) {
                $photo = $localPhoto;
            } else {
                $photo = $this->buildAssetUrl($photo);
            }
        }

        if ($photo === '') {
            $this->client->sendMessage($chatId, $caption, ['reply_markup' => json_encode(['inline_keyboard' => $keyboard])]);
            return;
        }

        try {
            $this->client->sendPhoto($chatId, $photo, $caption, ['reply_markup' => json_encode(['inline_keyboard' => $keyboard])]);
        } catch (RuntimeException $exception) {
            $this->client->sendMessage($chatId, $caption, ['reply_markup' => json_encode(['inline_keyboard' => $keyboard])]);
        }
    }

    private function getLocalImagePath(string $path): ?string
    {
        $root = dirname(__DIR__, 2);
        $relativePath = ltrim($path, '/\\');
        $possiblePaths = [
            $root . '/public/' . $relativePath,
            $root . '/' . $relativePath,
        ];

        foreach ($possiblePaths as $filePath) {
            if (is_file($filePath)) {
                return $filePath;
            }
        }

        return null;
    }

    private function isHttpUrl(string $value): bool
    {
        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
    }

    private function buildAssetUrl(string $path): string
    {
        $baseUrl = getenv('BOT_SITE_URL');

        if (!is_string($baseUrl) || trim($baseUrl) === '') {
            $baseUrl = 'http://127.0.0.1:8000/index.php';
        }

        $rootUrl = rtrim(preg_replace('#/[^/]*$#', '', $baseUrl), '/');

        return $rootUrl . '/' . ltrim($path, '/');
    }

    private function buildProductCaption(array $product): string
    {
        $category = $this->getCategoryName((int) ($product['category_id'] ?? 0));

        return sprintf(
            '<b>%s</b>%s Цена: %s. В наличии: %d. Категория: %s',
            $this->escape((string) $product['name']),
            $this->escape((string) $product['description']),
            $this->formatMoney($product['price']),
            (int) ($product['stock'] ?? 0),
            $this->escape($category)
        );
    }

    private function buildProductKeyboard(int $productId): array
    {
        return [
            [
                ['text' => '🛒 Купить', 'url' => $this->buildSiteUrl('product', ['id' => $productId])],
            ],
            [
                ['text' => '📦 В каталог', 'callback_data' => 'catalog'],
                ['text' => '💬 Поддержка', 'callback_data' => 'support'],
            ],
        ];
    }

    private function sendUnknownCommand(int $chatId): void
    {
        $text = "Команда не распознана.\n\nИспользуйте /help или кнопку <b>Каталог</b>.";
        $keyboard = [[['text' => '❓ Помощь', 'callback_data' => 'help']]];
        $this->client->sendMessage($chatId, $text, ['reply_markup' => json_encode(['inline_keyboard' => $keyboard])]);
    }

    private function buildSiteUrl(string $page, array $params = []): string
    {
        $baseUrl = getenv('BOT_SITE_URL');

        if (!is_string($baseUrl) || trim($baseUrl) === '') {
            $baseUrl = 'http://127.0.0.1:8000/index.php';
        }

        $params = array_merge(['page' => $page], $params);

        return rtrim($baseUrl, '/') . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    private function getCategoryName(int $categoryId): string
    {
        $this->categoryMap = $this->categoryMap ?: $this->loadCategoryMap();

        return $this->categoryMap[$categoryId] ?? 'Каталог';
    }

    private function loadCategoryMap(): array
    {
        $map = [];

        foreach ($this->categories->all() as $category) {
            $map[(int) $category['id']] = (string) $category['name'];
        }

        return $map;
    }

    private function readOffset(): int
    {
        if (!is_file($this->offsetPath)) {
            return 0;
        }

        $file = fopen($this->offsetPath, 'rb');

        if ($file === false) {
            return 0;
        }

        flock($file, LOCK_SH);
        $value = trim((string) fread($file, 8192));
        flock($file, LOCK_UN);
        fclose($file);

        return is_numeric($value) ? (int) $value : 0;
    }

    private function saveOffset(int $offset): void
    {
        $directory = dirname($this->offsetPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $file = fopen($this->offsetPath, 'cb');

        if ($file === false) {
            return;
        }

        flock($file, LOCK_EX);
        ftruncate($file, 0);
        fwrite($file, (string) $offset);
        fflush($file);
        flock($file, LOCK_UN);
        fclose($file);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function formatMoney(int|float|string $price): string
    {
        return number_format((float) $price, 2, '.', ' ') . ' MDL';
    }
}
