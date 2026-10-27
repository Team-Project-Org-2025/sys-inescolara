<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\UsoHerramienta;

/**
 * Doble de UsoHerramienta respaldado por SQLite en memoria.
 * SqliteUsoHerramienta::boot($pdo) debe invocarse en setUp().
 */
final class SqliteUsoHerramienta extends UsoHerramienta
{
    use SqliteTrait;

    public function __construct()
    {
    }
}
