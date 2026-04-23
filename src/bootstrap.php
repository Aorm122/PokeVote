<?php

declare(strict_types=1);

use App\Core\Env;

$rootPath = dirname(__DIR__);

require_once $rootPath . '/src/Core/Env.php';

Env::load($rootPath . '/.env');

date_default_timezone_set(Env::get('APP_TIMEZONE', 'UTC'));

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});
