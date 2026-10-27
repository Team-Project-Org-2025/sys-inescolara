<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Role;

/**
 * Doble de Role respaldado por SQLite en memoria.
 * SqliteRole::boot($pdo) debe invocarse en setUp().
 */
final class SqliteRole extends Role
{
    use SqliteTrait;

    public function __construct()
    {
    }
}
