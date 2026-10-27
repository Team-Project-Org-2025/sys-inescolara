<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Reports;

/**
 * Doble de Reports sin conexión a base de datos.
 * Permite probar getters, setters, fill y validaciones sin MySQL.
 */
final class NoDbReports extends Reports
{
    use NoDbTrait;

    public function __construct()
    {
    }
}
