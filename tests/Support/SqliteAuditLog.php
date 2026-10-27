<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\AuditLog;

/**
 * Doble de AuditLog respaldado por SQLite en memoria.
 * SqliteAuditLog::boot($pdo) debe invocarse en setUp().
 */
final class SqliteAuditLog extends AuditLog
{
    use SqliteTrait;

    public function __construct()
    {
    }
}
