<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\UsoHerramienta;

/**
 * Doble de UsoHerramienta sin conexión a base de datos.
 * Permite probar getters, setters, fill y validaciones sin MySQL.
 */
final class NoDbUsoHerramienta extends UsoHerramienta
{
    use NoDbTrait;

    public function __construct()
    {
    }
}
