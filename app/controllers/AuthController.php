<?php

declare(strict_types=1);

/**
 * Controlador de Autenticación
 */

function login(): void
{
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'auth' . DIRECTORY_SEPARATOR . 'login.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de login no encontrada.';
        return;
    }

    require $view;
}
