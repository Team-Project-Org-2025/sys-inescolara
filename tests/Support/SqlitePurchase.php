<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Purchase;

/**
 * Doble de Purchase respaldado por SQLite en memoria.
 * SqlitePurchase::boot($pdo) debe invocarse en setUp().
 */
final class SqlitePurchase extends Purchase
{
    use SqliteTrait;

    public function __construct()
    {
    }
}
