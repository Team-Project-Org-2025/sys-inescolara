<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\CuentaPagar;

/**
 * Doble de CuentaPagar respaldado por SQLite en memoria.
 * SqliteCuentaPagar::boot($pdo) debe invocarse en setUp().
 */
final class SqliteCuentaPagar extends CuentaPagar
{
    use SqliteTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
