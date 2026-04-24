<?php

declare(strict_types=1);

/**
 * Controlador para Vistas Públicas
 */

function catalogo(): void
{
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'public' . DIRECTORY_SEPARATOR . 'catalogo.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de catálogo no encontrada.';
        return;
    }

    require $view;
}

function home(): void
{
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'public' . DIRECTORY_SEPARATOR . 'home.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de home no encontrada.';
        return;
    }

    require $view;
}
