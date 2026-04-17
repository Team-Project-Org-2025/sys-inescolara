<?php

declare(strict_types=1);

/**
 * Página de inicio. Las acciones son funciones (convención del FrontController).
 */
function index(): void
{
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'inicio' . DIRECTORY_SEPARATOR . 'index.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de inicio no encontrada.';
        return;
    }

    require $view;
}
