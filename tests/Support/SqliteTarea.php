<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Tarea;

/**
 * Doble de Tarea respaldado por SQLite en memoria.
 * SqliteTarea::boot($pdo) debe invocarse en setUp().
 */
final class SqliteTarea extends Tarea
{
    use SqliteTrait;

    public function __construct()
    {
    }
}
