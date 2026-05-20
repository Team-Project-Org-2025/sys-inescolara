<?php

declare(strict_types=1);

/**
 * Página de inicio. Las acciones son funciones (convención del FrontController).
 */
function index(): void
{
    header('Location: ' . BASE_URL);
    exit();
}
