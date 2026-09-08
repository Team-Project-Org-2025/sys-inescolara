#!/usr/bin/env php
<?php
/**
 * SYSINECOLARA - Script de Migración de Base de Datos
 *
 * Ejecuta las migraciones de esquema y seed data de los modelos.
 * Este script debe ejecutarse UNA SOLA VEZ después de la instalación inicial
 * o después de actualizar el código con nuevos cambios de esquema.
 *
 * Uso:
 *   php scripts/migrate.php
 *
 * NOTA: En producción, ejecutar después de cada deploy que incluya cambios de esquema.
 */

declare(strict_types=1);

// Cargar variables de entorno
require_once __DIR__ . '/../app/helpers/env_loader.php';
loadEnvFile(__DIR__ . '/../.env');

// Cargar autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

use SysInescolara\models\Usuario;
use SysInescolara\models\Proveedor;
use SysInescolara\models\Cliente;

echo "╔══════════════════════════════════════════════════╗\n";
echo "║   SYSINECOLARA - Migración de Base de Datos     ║\n";
echo "╚══════════════════════════════════════════════════╝\n\n";

$totalStart = microtime(true);

// --- Migración de Usuarios, Permisos y Roles ---
echo "[1/3] Migrando usuarios, permisos y roles...\n";
$start = microtime(true);
try {
    $usuario = new Usuario(runBootstrap: true);
    $elapsed = round(microtime(true) - $start, 2);
    echo "      ✓ Completado ({$elapsed}s)\n";
} catch (\Throwable $e) {
    echo "      ✗ Error: " . $e->getMessage() . "\n";
    error_log("Migrate error (Usuario): " . $e->getMessage());
}

// --- Migración de Proveedores ---
echo "[2/3] Migrando proveedores...\n";
$start = microtime(true);
try {
    $proveedor = new Proveedor(runBootstrap: true);
    $elapsed = round(microtime(true) - $start, 2);
    echo "      ✓ Completado ({$elapsed}s)\n";
} catch (\Throwable $e) {
    echo "      ✗ Error: " . $e->getMessage() . "\n";
    error_log("Migrate error (Proveedor): " . $e->getMessage());
}

// --- Migración de Clientes ---
echo "[3/3] Migrando clientes...\n";
$start = microtime(true);
try {
    $cliente = new Cliente(runBootstrap: true);
    $elapsed = round(microtime(true) - $start, 2);
    echo "      ✓ Completado ({$elapsed}s)\n";
} catch (\Throwable $e) {
    echo "      ✗ Error: " . $e->getMessage() . "\n";
    error_log("Migrate error (Cliente): " . $e->getMessage());
}

$totalElapsed = round(microtime(true) - $totalStart, 2);
echo "\n╔══════════════════════════════════════════════════╗\n";
echo "║   Migración completada en {$totalElapsed}s                    ║\n";
echo "╚══════════════════════════════════════════════════╝\n";
