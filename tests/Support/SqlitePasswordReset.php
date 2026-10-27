<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\PasswordReset;

/**
 * Doble de PasswordReset respaldado por SQLite en memoria.
 * SqlitePasswordReset::boot($pdo) debe invocarse en setUp().
 */
final class SqlitePasswordReset extends PasswordReset
{
    use SqliteTrait;

    public function __construct()
    {
    }
}
