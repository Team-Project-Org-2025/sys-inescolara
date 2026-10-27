<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Planta;

/**
 * Doble de Planta respaldado por SQLite en memoria.
 * SqlitePlanta::boot($pdo) debe invocarse en setUp().
 */
final class SqlitePlanta extends Planta
{
    use SqliteTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
