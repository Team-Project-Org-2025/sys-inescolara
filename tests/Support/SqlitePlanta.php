<?php

namespace SysInescolara\Tests\Support;

use PDO;
use RuntimeException;
use SysInescolara\models\Planta;

/**
 * Doble de Planta respaldado por SQLite en memoria.
 * Permite ejercitar las queries reales del modelo (INSERT/UPDATE/SELECT/
 * soft delete) sin necesitar MySQL. boot() debe invocarse en setUp().
 */
class SqlitePlanta extends Planta
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
            throw new RuntimeException('SqlitePlanta::boot() no fue invocado');
        }
        return self::$pdo;
    }
}
