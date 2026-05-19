<?php

declare(strict_types=1);

/**
 * Controlador para Vistas Públicas
 */

function renderPublicView(string $viewFile, string $title, string $currentPage): void
{
    $view = ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $viewFile;

    if (!is_file($view)) {
        http_response_code(500);
        echo 'Vista pública no encontrada.';
        return;
    }

    ob_start();
    require $view;
    $content = ob_get_clean();

    require ROOT_PATH . 'app/views/layouts/main.php';
}

function catalogo(): void
{
    renderPublicView('public/catalogo.php', 'Catálogo de Plantas', 'catalogo');
}

function home(): void
{
    renderPublicView('public/home.php', 'Inicio', 'home');
}

function servicios(): void
{
    renderPublicView('public/servicios.php', 'Servicios', 'servicios');
}

function nosotros(): void
{
    renderPublicView('public/nosotros.php', 'Nosotros', 'nosotros');
}

function contacto(): void
{
    renderPublicView('public/contacto.php', 'Contacto', 'contacto');
}
