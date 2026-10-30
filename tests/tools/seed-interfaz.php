<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Interfaz/bootstrap.php';

use SysInescolara\Tests\Interfaz\Support\Config;
use SysInescolara\Tests\Interfaz\Support\Db;

$role = Db::row('SELECT id_rol FROM roles ORDER BY id_rol ASC LIMIT 1', [], 'security');
if ($role === null) {
    Db::execute(
        'INSERT INTO roles (nombre_rol, descripcion_rol) VALUES (?, ?)',
        ['Administrador', 'Rol usado por la suite de interfaz'],
        'security'
    );
    $roleId = (int) Db::lastId('security');
} else {
    $roleId = (int) $role['id_rol'];
}

$user = Config::user();
$email = 'e2e_admin@example.test';
$hash = password_hash(Config::password(), PASSWORD_DEFAULT);

$existing = Db::row(
    'SELECT id_usuario FROM usuarios WHERE nombre_usuario = ?',
    [$user],
    'security'
);

if ($existing === null) {
    Db::execute(
        'INSERT INTO usuarios (nombre_usuario, correo_electronico, password_hash, id_rol, estatus) VALUES (?, ?, ?, ?, ?)',
        [$user, $email, $hash, $roleId, 'Activo'],
        'security'
    );
    $userId = (int) Db::lastId('security');
    fwrite(STDOUT, "Usuario de prueba creado: {$user} (id {$userId})\n");
} else {
    Db::execute(
        'UPDATE usuarios SET correo_electronico = ?, password_hash = ?, id_rol = ?, estatus = ? WHERE id_usuario = ?',
        [$email, $hash, $roleId, 'Activo', $existing['id_usuario']],
        'security'
    );
    fwrite(STDOUT, "Usuario de prueba actualizado: {$user} (id {$existing['id_usuario']})\n");
}

fwrite(STDOUT, "Rol asignado: {$roleId}\n");
fwrite(STDOUT, "Credenciales: {$user} / " . Config::password() . "\n");
