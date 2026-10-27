<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Trazabilidad;

/**
 * Doble de Trazabilidad respaldado por SQLite en memoria.
 * SqliteTrazabilidad::boot($pdo) debe invocarse en setUp().
 */
final class SqliteTrazabilidad extends Trazabilidad
{
    use SqliteTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
