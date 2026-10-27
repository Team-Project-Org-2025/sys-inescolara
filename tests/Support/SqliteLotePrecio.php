<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\LotePrecio;

/**
 * Doble de LotePrecio respaldado por SQLite en memoria.
 * SqliteLotePrecio::boot($pdo) debe invocarse en setUp().
 */
final class SqliteLotePrecio extends LotePrecio
{
    use SqliteTrait;

    public function __construct()
    {
    }
}
