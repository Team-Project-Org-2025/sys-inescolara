<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use PDO;
use RuntimeException;

/**
 * Inyecta un PDO SQLite en memoria en un modelo real.
 *
 * El doble conserva todas las consultas del modelo (INSERT/UPDATE/SELECT,
 * JOIN, subconsultas) y solo sustituye la conexión MySQL. Cada clase que usa
 * el trait obtiene su propia conexión estática.
 */
trait SqliteTrait
{
    private static ?PDO $sqlitePdo = null;

    public static function boot(?PDO $pdo): void
    {
        self::$sqlitePdo = $pdo;
    }

    protected function db(): PDO
    {
        if (self::$sqlitePdo === null) {
            throw new RuntimeException('SqliteTrait::boot() no fue invocado');
        }

        return self::$sqlitePdo;
    }
}
