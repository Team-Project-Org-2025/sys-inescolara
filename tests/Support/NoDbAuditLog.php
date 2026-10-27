<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\AuditLog;

/**
 * Doble de AuditLog sin conexión a base de datos.
 * Permite probar getters, setters, fill y validaciones sin MySQL.
 */
final class NoDbAuditLog extends AuditLog
{
    use NoDbTrait;

    public function __construct()
    {
    }
}
