<?php

declare(strict_types=1);

/**
 * Router del servidor embebido de PHP para la suite de aceptación.
 *
 * Devuelve `false` para los archivos estáticos que existen en el proyecto
 * (CSS, JS, imágenes) y delega el resto a `server/index.php`.
 */

$root = dirname(__DIR__, 3);
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($uri !== '/') {
    $target = realpath($root . $uri);

    if ($target !== false && is_file($target) && str_starts_with($target, $root)) {
        return false;
    }
}

require __DIR__ . '/index.php';
