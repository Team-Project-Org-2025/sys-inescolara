<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\DashboardData;

/**
 * Doble de DashboardData respaldado por SQLite en memoria.
 * SqliteDashboardData::boot($pdo) debe invocarse en setUp().
 */
final class SqliteDashboardData extends DashboardData
{
    use SqliteTrait;

    public function __construct()
    {
    }
}
