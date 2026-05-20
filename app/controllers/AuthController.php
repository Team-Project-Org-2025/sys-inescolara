<?php

declare(strict_types=1);

/**
 * Controlador de Autenticación
 */

function login(): void
{
    header('Location: ' . BASE_URL . 'login');
    exit();
}

function index(): void
{
    login();
}
