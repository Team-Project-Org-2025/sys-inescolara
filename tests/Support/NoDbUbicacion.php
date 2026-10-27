<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Ubicacion;

/**
 * Doble de Ubicacion sin conexión a base de datos.
 * Permite probar getters, setters, fill y validaciones sin MySQL.
 */
final class NoDbUbicacion extends Ubicacion
{
    use NoDbTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
