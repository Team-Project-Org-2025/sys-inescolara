<?php

declare(strict_types=1);

define('PROJECT_ROOT', dirname(__DIR__, 2));

require_once PROJECT_ROOT . '/vendor/autoload.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'SysInescolara\\Tests\\Interfaz\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

date_default_timezone_set('America/Caracas');
error_reporting(E_ALL);
ini_set('display_errors', '1');
