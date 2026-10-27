<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Venta;

/**
 * Doble de Venta respaldado por SQLite en memoria.
 * SqliteVenta::boot($pdo) debe invocarse en setUp().
 */
final class SqliteVenta extends Venta
{
    use SqliteTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
