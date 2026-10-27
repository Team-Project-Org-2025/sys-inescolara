<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Ampliacion;

/**
 * Doble de Ampliacion respaldado por SQLite en memoria.
 * SqliteAmpliacion::boot($pdo) debe invocarse en setUp().
 */
final class SqliteAmpliacion extends Ampliacion
{
    use SqliteTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
