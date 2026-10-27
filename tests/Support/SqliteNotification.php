<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Notification;

/**
 * Doble de Notification respaldado por SQLite en memoria.
 * SqliteNotification::boot($pdo) debe invocarse en setUp().
 */
final class SqliteNotification extends Notification
{
    use SqliteTrait;

    public function __construct()
    {
    }
}
