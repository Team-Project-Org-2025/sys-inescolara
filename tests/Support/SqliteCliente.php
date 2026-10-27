<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Cliente;

/**
 * Doble de Cliente respaldado por SQLite en memoria.
 * SqliteCliente::boot($pdo) debe invocarse en setUp().
 */
final class SqliteCliente extends Cliente
{
    use SqliteTrait;

    public function __construct(array $attributes = [], bool $runBootstrap = false)
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
