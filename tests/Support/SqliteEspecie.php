<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Especie;

/**
 * Doble de Especie respaldado por SQLite en memoria.
 * SqliteEspecie::boot($pdo) debe invocarse en setUp().
 */
final class SqliteEspecie extends Especie
{
    use SqliteTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
