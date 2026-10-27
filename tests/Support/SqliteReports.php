<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Reports;

/**
 * Doble de Reports respaldado por SQLite en memoria.
 * SqliteReports::boot($pdo) debe invocarse en setUp().
 */
final class SqliteReports extends Reports
{
    use SqliteTrait;

    public function __construct()
    {
    }
}
