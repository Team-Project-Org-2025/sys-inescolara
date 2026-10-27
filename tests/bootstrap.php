<?php

declare(strict_types=1);

/**
 * Bootstrap de la suite PHPUnit de Sys Inescolara.
 *
 * No abre conexiones a MySQL ni carga .env: las pruebas unitarias y de humo
 * deben ser deterministas. Las pruebas de integración que lo necesiten
 * inyectan un PDO SQLite en memoria a través de los dobles de tests/Support.
 */

$projectRoot = dirname(__DIR__);

define('ROOT_PATH', $projectRoot . DIRECTORY_SEPARATOR);

require_once $projectRoot . '/vendor/autoload.php';

// Autoload PSR-4 para SysInescolara\Tests\ => tests/.
spl_autoload_register(static function (string $class): void {
    $prefix = 'SysInescolara\\Tests\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
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

// AuditLog::record() llama session_start() si no hay sesión activa. En CLI
// eso emite un warning después de que PHPUnit empieza a escribir salida, así
// que la sesión se inicia aquí, antes de cualquier output.
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_cookies', '0');
    ini_set('session.cache_limiter', '');
    @session_start();
}

$_SESSION = [];
$_SERVER['REQUEST_URI'] = '/phpunit';
$_SERVER['REQUEST_METHOD'] = 'GET';
