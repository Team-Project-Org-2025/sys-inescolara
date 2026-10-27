<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Cliente;

/**
 * Doble de Cliente sin conexión a base de datos.
 * Permite probar getters, setters, fill y validaciones sin MySQL.
 */
final class NoDbCliente extends Cliente
{
    use NoDbTrait;

    public function __construct(array $attributes = [], bool $runBootstrap = false)
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
