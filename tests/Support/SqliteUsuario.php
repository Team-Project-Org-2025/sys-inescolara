<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Usuario;

/**
 * Doble de Usuario respaldado por SQLite en memoria.
 * SqliteUsuario::boot($pdo) debe invocarse en setUp().
 */
final class SqliteUsuario extends Usuario
{
    use SqliteTrait;

    public function __construct(bool $runBootstrap = false)
    {
    }
}
