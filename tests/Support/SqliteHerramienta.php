<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Herramienta;

/**
 * Doble de Herramienta respaldado por SQLite en memoria.
 * SqliteHerramienta::boot($pdo) debe invocarse en setUp().
 */
final class SqliteHerramienta extends Herramienta
{
    use SqliteTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
