<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\RegistroInsumo;

/**
 * Doble de RegistroInsumo sin conexión a base de datos.
 * Permite probar getters, setters, fill y validaciones sin MySQL.
 */
final class NoDbRegistroInsumo extends RegistroInsumo
{
    use NoDbTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
