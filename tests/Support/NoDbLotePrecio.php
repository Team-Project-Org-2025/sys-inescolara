<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\LotePrecio;

/**
 * Doble de LotePrecio sin conexión a base de datos.
 * Permite probar getters, setters, fill y validaciones sin MySQL.
 */
final class NoDbLotePrecio extends LotePrecio
{
    use NoDbTrait;

    public function __construct()
    {
    }
}
