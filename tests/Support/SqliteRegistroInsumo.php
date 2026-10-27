<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\RegistroInsumo;

/**
 * Doble de RegistroInsumo respaldado por SQLite en memoria.
 * SqliteRegistroInsumo::boot($pdo) debe invocarse en setUp().
 */
final class SqliteRegistroInsumo extends RegistroInsumo
{
    use SqliteTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
