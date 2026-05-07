<?php

declare(strict_types=1);

use DeviceStore\Core\FileDatabase;
use DeviceStore\Repositories\CategoryRepository;
use DeviceStore\Repositories\ProductRepository;

$config = require dirname(__DIR__) . '/src/bootstrap.php';

require __DIR__ . '/src/TelegramClient.php';
require __DIR__ . '/src/TelegramBot.php';

$token = getenv('BOT_TOKEN');

if ($token === false || trim($token) === '') {
    fwrite(STDERR, "Укажите BOT_TOKEN перед запуском бота.\n");
    exit(1);
}

$db = new FileDatabase((string) $config['database_path']);
$products = new ProductRepository($db);
$categories = new CategoryRepository($db);

$bot = new TelegramBot($token, $products, $categories);
$bot->run();

