<?php

namespace SysInescolara\Tests\Support;

use PDO;
use RuntimeException;
use SysInescolara\models\Especie;

/**
 * Doble de Especie respaldado por SQLite en memoria.
 * Permite ejercitar las queries reales del modelo sin necesitar MySQL.
 * boot() debe invocarse en setUp().
 */
class SqliteEspecie extends Especie
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
            throw new RuntimeException('SqliteEspecie::boot() no fue invocado');
        }
        return self::$pdo;
    }
}
