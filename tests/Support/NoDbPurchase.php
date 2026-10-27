<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Purchase;

/**
 * Doble de Purchase sin conexión a base de datos.
 * Permite probar getters, setters, fill y validaciones sin MySQL.
 */
final class NoDbPurchase extends Purchase
{
    use NoDbTrait;

    public function __construct()
    {
    }
}
