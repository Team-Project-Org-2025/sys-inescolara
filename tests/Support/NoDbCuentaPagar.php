<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\CuentaPagar;

/**
 * Doble de CuentaPagar sin conexión a base de datos.
 * Permite probar getters, setters, fill y validaciones sin MySQL.
 */
final class NoDbCuentaPagar extends CuentaPagar
{
    use NoDbTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
