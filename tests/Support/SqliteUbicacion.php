<?php

namespace SysInescolara\Tests\Support;

use PDO;
use RuntimeException;
use SysInescolara\models\Ubicacion;

/**
 * Doble de Ubicacion respaldado por SQLite en memoria.
 *
 * Nota: en SQLite no existe information_schema, por lo que tipoColumn()
 * cae en 'zona' (la columna real del esquema definitivo). El esquema de
 * prueba debe crear la columna `zona`, no `tipo`.
 *
 * boot() debe invocarse en setUp().
 */
class SqliteUbicacion extends Ubicacion
{
    private static ?PDO $pdo = null;

    public static function boot(?PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }

    protected function db(): PDO
    {
        if (self::$pdo === null) {
            throw new RuntimeException('SqliteUbicacion::boot() no fue invocado');
        }
        return self::$pdo;
    }
}
