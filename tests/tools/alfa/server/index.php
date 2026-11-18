<?php

declare(strict_types=1);

/**
 * Punto de entrada exclusivo de las pruebas ALFA.
 *
 * Sirve la aplicación real con el servidor embebido de PHP sin modificar
 * `index.php`, `app/` ni `.env`. Añade dos cosas que solo existen en el
 * entorno de pruebas alfa:
 *
 *   1. Fuerza APP_ENV=alfa y las claves de prueba de Google reCAPTCHA v2.
 *   2. Permite inyectar una sesión autenticada cuando la petición trae la
 *      cabecera `X-Alfa-Token` con el valor de la variable ALFA_SESSION_TOKEN.
 *
 * La inyección de sesión evita depender de Google reCAPTCHA (y de la red) para
 * probar los módulos internos. El usuario inyectado es `alfa_admin`, creado por
 * `scripts/seed-alfa.php`; nunca se usa en producción porque este archivo solo
 * lo sirve el runner de pruebas alfa.
 *
 * Uso:
 *   ALFA_SESSION_TOKEN=<token> php -S 127.0.0.1:8098 -t <raíz> tests/tools/alfa/server/router.php
 */

date_default_timezone_set('America/Caracas');

$root = dirname(__DIR__, 4);

require_once $root . '/vendor/autoload.php';
require_once $root . '/app/helpers/env_loader.php';

loadEnvFile($root . '/.env');

// Claves de prueba publicadas por Google: la verificación server-side siempre
// responde success=true. No se usan en producción.
$recaptchaTestSiteKey = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
$recaptchaTestSecretKey = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

foreach ([
    'RECAPTCHA_SITE_KEY' => $recaptchaTestSiteKey,
    'RECAPTCHA_SECRET_KEY' => $recaptchaTestSecretKey,
    'APP_ENV' => 'alfa',
    'APP_DEBUG' => 'true',
] as $key => $value) {
    putenv($key . '=' . $value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

// BASE_URL relativa a la raíz del servidor de pruebas. Así las vistas y los
// AJAX funcionan en cualquier puerto.
define('BASE_URL', '/');

// FrontController calcula el prefijo de subcarpeta con SCRIPT_NAME. En el
// servidor embebido apunta al router, así que se normaliza a /index.php.
$_SERVER['SCRIPT_NAME'] = '/index.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Inyecta la sesión del usuario alfa cuando la petición presenta el token.
 */
$sessionToken = (string) (getenv('ALFA_SESSION_TOKEN') ?: '');
$providedToken = (string) ($_SERVER['HTTP_X_ALFA_TOKEN'] ?? '');

if ($sessionToken !== '' && $providedToken !== '' && hash_equals($sessionToken, $providedToken)) {
    try {
        $prefix = 'DB_SEC_';
        $host = getenv($prefix . 'HOST') ?: (getenv('DB_HOST') ?: 'localhost');
        $port = getenv($prefix . 'PORT') ?: (getenv('DB_PORT') ?: '3306');
        $name = getenv($prefix . 'NAME') ?: (getenv('DB_NAME') ?: 'SysInescolara-Seguridad');
        $user = getenv($prefix . 'USER') ?: (getenv('DB_USER') ?: 'root');
        $password = getenv($prefix . 'PASSWORD');
        if ($password === false) {
            $password = getenv('DB_PASSWORD') ?: '';
        }

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $stmt = $pdo->prepare(
            'SELECT id_usuario, nombre_usuario, correo_electronico, avatar, id_rol
             FROM usuarios
             WHERE nombre_usuario = ? AND estatus = ?
             LIMIT 1'
        );
        $stmt->execute(['alfa_admin', 'Activo']);
        $alfaUser = $stmt->fetch();

        if (is_array($alfaUser)) {
            $_SESSION['user_id'] = (int) $alfaUser['id_usuario'];
            $_SESSION['user_nombre'] = (string) $alfaUser['nombre_usuario'];
            $_SESSION['user_email'] = $alfaUser['correo_electronico'] ?? null;
            $_SESSION['user_avatar'] = $alfaUser['avatar'] ?? null;
            $_SESSION['user_rol_id'] = (int) $alfaUser['id_rol'];
            $_SESSION['user_permisos'] = [];
        }
    } catch (Throwable $e) {
        // Sin sesión inyectada la aplicación responde como anónimo. El runner
        // ya comprobó la base de datos antes de arrancar el servidor.
        error_log('[alfa] No se pudo inyectar la sesión: ' . $e->getMessage());
    }
}

use SysInescolara\controllers\FrontController;

new FrontController();
