<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\UnidadMedida;

/**
 * Doble de UnidadMedida respaldado por SQLite en memoria.
 * SqliteUnidadMedida::boot($pdo) debe invocarse en setUp().
 */
final class SqliteUnidadMedida extends UnidadMedida
{
    use SqliteTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
