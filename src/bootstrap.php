<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/src/helpers.php';

spl_autoload_register(
    /**
     * Loads project classes from the src directory.
     *
     * @param string $class Fully qualified class name.
     * @return void
     */
    static function (string $class): void {
        $prefix = 'BotGear\\';

        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $relativeClass = substr($class, strlen($prefix));
        $path = BASE_PATH . '/src/' . str_replace('\\', '/', $relativeClass) . '.php';

        if (is_file($path)) {
            require $path;
        }
    }
);

return [
    'app_name' => 'BotGear Store',
    'database_path' => BASE_PATH . '/data/store.json',
];
