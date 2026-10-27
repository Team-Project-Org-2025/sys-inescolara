<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Insumo;

/**
 * Doble de Insumo respaldado por SQLite en memoria.
 * SqliteInsumo::boot($pdo) debe invocarse en setUp().
 */
final class SqliteInsumo extends Insumo
{
    use SqliteTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
