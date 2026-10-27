<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Inventory;

/**
 * Doble de Inventory sin conexión a base de datos.
 * Permite probar getters, setters, fill y validaciones sin MySQL.
 */
final class NoDbInventory extends Inventory
{
    use NoDbTrait;

    public function __construct()
    {
    }
}
