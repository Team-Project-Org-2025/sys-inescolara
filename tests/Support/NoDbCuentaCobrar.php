<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\CuentaCobrar;

/**
 * Doble de CuentaCobrar sin conexión a base de datos.
 * Permite probar getters, setters, fill y validaciones sin MySQL.
 */
final class NoDbCuentaCobrar extends CuentaCobrar
{
    use NoDbTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
