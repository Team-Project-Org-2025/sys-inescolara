<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Inventory;

/**
 * Doble de Inventory respaldado por SQLite en memoria.
 * SqliteInventory::boot($pdo) debe invocarse en setUp().
 */
final class SqliteInventory extends Inventory
{
    use SqliteTrait;

    public function __construct()
    {
    }
}
