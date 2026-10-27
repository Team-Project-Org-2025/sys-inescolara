<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Proveedor;

/**
 * Doble de Proveedor respaldado por SQLite en memoria.
 * SqliteProveedor::boot($pdo) debe invocarse en setUp().
 */
final class SqliteProveedor extends Proveedor
{
    use SqliteTrait;

    public function __construct(array $attributes = [], bool $runBootstrap = false)
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
