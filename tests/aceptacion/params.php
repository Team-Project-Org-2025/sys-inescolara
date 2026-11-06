<?php

declare(strict_types=1);

/**
 * Parámetros de la suite de aceptación.
 *
 * Todos los valores se pueden sobrescribir con variables de entorno para
 * ejecutar la suite en otra máquina o en CI.
 */
return [
    'ACEPTACION_URL' => getenv('ACEPTACION_URL') ?: 'http://127.0.0.1:8099/',
    'ACEPTACION_BROWSER' => getenv('ACEPTACION_BROWSER') ?: '/opt/brave-bin/brave',
    'ACEPTACION_USER' => getenv('ACEPTACION_USER') ?: 'e2e_admin',
    'ACEPTACION_PASSWORD' => getenv('ACEPTACION_PASSWORD') ?: 'E2eAdmin123!',
    'ACEPTACION_DRIVER_PORT' => getenv('ACEPTACION_DRIVER_PORT') ?: '9515',
];
