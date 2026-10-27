<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Ubicacion;

/**
 * Doble de Ubicacion respaldado por SQLite en memoria.
 * SqliteUbicacion::boot($pdo) debe invocarse en setUp().
 */
final class SqliteUbicacion extends Ubicacion
{
    use SqliteTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
