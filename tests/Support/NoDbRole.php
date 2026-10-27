<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Role;

/**
 * Doble de Role sin conexión a base de datos.
 * Permite probar getters, setters, fill y validaciones sin MySQL.
 */
final class NoDbRole extends Role
{
    use NoDbTrait;

    public function __construct()
    {
    }
}
