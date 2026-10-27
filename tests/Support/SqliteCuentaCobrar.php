<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\CuentaCobrar;

/**
 * Doble de CuentaCobrar respaldado por SQLite en memoria.
 * SqliteCuentaCobrar::boot($pdo) debe invocarse en setUp().
 */
final class SqliteCuentaCobrar extends CuentaCobrar
{
    use SqliteTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
