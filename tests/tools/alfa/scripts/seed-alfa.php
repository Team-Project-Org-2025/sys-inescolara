<?php

declare(strict_types=1);

/**
 * Crea o actualiza el usuario de pruebas `alfa_admin`.
 *
 * Lo usa `run-alfa.php` antes de ejecutar los grupos funcionales. Solo toca la
 * base de datos de seguridad y es idempotente:
 *
 *   - Garantiza que exista el rol 1 (Administrador), porque Auth::isAdmin()
 *     compara el rol con 1.
 *   - Crea o actualiza `alfa_admin`, activo y con rol 1.
 *
 * No imprime contraseñas: la sesión de las pruebas se inyecta desde el servidor
 * alfa, no se obtiene con un login real.
 *
 * Uso:
 *   php tests/tools/alfa/scripts/seed-alfa.php
 *   php tests/tools/alfa/scripts/seed-alfa.php --usuario=otro_alfa
 */

const EXIT_OK = 0;
const EXIT_ENV = 3;

$root = dirname(__DIR__, 4);

require_once $root . '/app/helpers/env_loader.php';

if (is_file($root . '/.env')) {
    loadEnvFile($root . '/.env');
}

$username = 'alfa_admin';
$email = 'alfa_admin@example.test';

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--usuario=')) {
        $username = trim(substr($arg, strlen('--usuario=')));
    } elseif (str_starts_with($arg, '--email=')) {
        $email = trim(substr($arg, strlen('--email=')));
    } elseif ($arg === '-h' || $arg === '--help') {
        fwrite(STDOUT, 'Uso: php tests/tools/alfa/scripts/seed-alfa.php [--usuario=NOMBRE] [--email=CORREO]' . PHP_EOL);
        exit(EXIT_OK);
    }
}

if ($username === '') {
    fwrite(STDERR, '[ERROR] El usuario no puede estar vacío.' . PHP_EOL);
    exit(EXIT_ENV);
}

if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
    fwrite(STDERR, '[ERROR] Falta el driver pdo_mysql de PHP.' . PHP_EOL);
    exit(EXIT_ENV);
}

$host = getenv('DB_SEC_HOST') ?: (getenv('DB_HOST') ?: 'localhost');
$port = getenv('DB_SEC_PORT') ?: (getenv('DB_PORT') ?: '3306');
$name = getenv('DB_SEC_NAME') ?: (getenv('DB_NAME') ?: 'SysInescolara-Seguridad');
$user = getenv('DB_SEC_USER') ?: (getenv('DB_USER') ?: 'root');
$password = getenv('DB_SEC_PASSWORD');
if ($password === false) {
    $password = getenv('DB_PASSWORD') ?: '';
}

try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, '[ERROR] No se pudo conectar a la base de datos de seguridad: ' . $e->getMessage() . PHP_EOL);
    fwrite(STDERR, '        Revise DB_SEC_* en .env y que MySQL esté iniciado.' . PHP_EOL);
    exit(EXIT_ENV);
}

try {
    $role = $pdo->query('SELECT id_rol FROM roles WHERE id_rol = 1 LIMIT 1')->fetch();
    if (!is_array($role)) {
        $stmt = $pdo->prepare('INSERT INTO roles (id_rol, nombre_rol, descripcion_rol) VALUES (1, ?, ?)');
        $stmt->execute(['Administrador', 'Rol administrador usado por las pruebas alfa']);
        fwrite(STDOUT, 'Rol 1 (Administrador) creado.' . PHP_EOL);
    }

    $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE nombre_usuario = ? LIMIT 1');
    $stmt->execute([$username]);
    $existing = $stmt->fetch();

    if (is_array($existing)) {
        $stmt = $pdo->prepare(
            'UPDATE usuarios
             SET correo_electronico = ?, password_hash = ?, id_rol = 1, estatus = ?, intentos_fallidos = 0
             WHERE id_usuario = ?'
        );
        $stmt->execute([$email, $hash, 'Activo', $existing['id_usuario']]);
        fwrite(STDOUT, sprintf(
            'Usuario de pruebas actualizado: %s (id %d, rol 1).%s',
            $username,
            $existing['id_usuario'],
            PHP_EOL
        ));
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO usuarios (nombre_usuario, correo_electronico, password_hash, id_rol, estatus)
             VALUES (?, ?, ?, 1, ?)'
        );
        $stmt->execute([$username, $email, $hash, 'Activo']);
        fwrite(STDOUT, sprintf(
            'Usuario de pruebas creado: %s (id %d, rol 1).%s',
            $username,
            (int) $pdo->lastInsertId(),
            PHP_EOL
        ));
    }
} catch (Throwable $e) {
    fwrite(STDERR, '[ERROR] No se pudo preparar el usuario alfa: ' . $e->getMessage() . PHP_EOL);
    exit(EXIT_ENV);
}

exit(EXIT_OK);
