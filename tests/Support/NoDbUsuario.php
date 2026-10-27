<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Usuario;

/**
 * Doble de Usuario sin conexión a base de datos.
 * Permite probar getters, setters, fill y validaciones sin MySQL.
 */
final class NoDbUsuario extends Usuario
{
    use NoDbTrait;

    public function __construct(bool $runBootstrap = false)
    {
    }
}
