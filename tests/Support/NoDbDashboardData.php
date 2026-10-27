<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\DashboardData;

/**
 * Doble de DashboardData sin conexión a base de datos.
 * Permite probar getters, setters, fill y validaciones sin MySQL.
 */
final class NoDbDashboardData extends DashboardData
{
    use NoDbTrait;

    public function __construct()
    {
    }
}
