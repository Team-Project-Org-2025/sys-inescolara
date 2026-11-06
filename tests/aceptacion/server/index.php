<?php

declare(strict_types=1);

/**
 * Entry point exclusivo para las pruebas de aceptación (Codeception).
 *
 * Sirve la aplicación real con el servidor embebido de PHP, sin modificar
 * `index.php` ni `.env`. La única diferencia con producción es que fuerza
 * las claves de prueba oficiales de Google reCAPTCHA v2, de modo que el
 * formulario de login pueda automatizarse de forma determinista.
 *
 * Uso:
 *   php -S 127.0.0.1:8099 -t <raíz del proyecto> tests/aceptacion/server/router.php
 */

date_default_timezone_set('America/Caracas');

$root = dirname(__DIR__, 3);

require_once $root . '/vendor/autoload.php';
require_once $root . '/app/helpers/env_loader.php';

loadEnvFile($root . '/.env');

// Claves de prueba publicadas por Google: la verificación server-side
// siempre responde success=true. No se usan en producción.
$recaptchaTestSiteKey = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
$recaptchaTestSecretKey = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

foreach ([
    'RECAPTCHA_SITE_KEY' => $recaptchaTestSiteKey,
    'RECAPTCHA_SECRET_KEY' => $recaptchaTestSecretKey,
    'APP_ENV' => 'acceptance',
    'APP_DEBUG' => 'true',
] as $key => $value) {
    putenv($key . '=' . $value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

// BASE_URL relativa a la raíz del servidor de pruebas. Así las vistas y los
// AJAX funcionan tanto en / como en cualquier otro puerto.
define('BASE_URL', '/');

// FrontController calcula el prefijo de subcarpeta con SCRIPT_NAME. En el
// servidor embebido apunta al router, así que se normaliza a /index.php.
$_SERVER['SCRIPT_NAME'] = '/index.php';

use SysInescolara\controllers\FrontController;

new FrontController();
