<?php

declare(strict_types=1);

/**
 * Controlador del Dashboard
 */

function index(): void
{
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'index.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de dashboard (index) no encontrada.';
        return;
    }

    require $view;
}

function asistente(): void
{
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'asistente.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de asistente no encontrada.';
        return;
    }

    require $view;
}

function inventario(): void
{
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'inventario.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de inventario no encontrada.';
        return;
    }

    require $view;
}

function ventas(): void
{
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'ventas.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de ventas no encontrada.';
        return;
    }

    require $view;
}

function usuarios(): void
{
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'usuarios.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de usuarios no encontrada.';
        return;
    }

    require $view;
}

function plants(): void
{
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'plants.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de plantas no encontrada.';
        return;
    }

    require $view;
}

function suppliers(): void
{
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'suppliers.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de proveedores no encontrada.';
        return;
    }

    require $view;
}

function supplies(): void
{
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'supplies.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de suministros no encontrada.';
        return;
    }

    require $view;
}

function employees(): void
{
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
        . 'dashboard' . DIRECTORY_SEPARATOR . 'employees.php';

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista de empleados no encontrada.';
        return;
    }

    require $view;
}