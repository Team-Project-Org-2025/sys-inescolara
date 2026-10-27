<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\Merma;

/**
 * Doble de Merma sin conexión a base de datos.
 * Permite probar getters, setters, fill y validaciones sin MySQL.
 */
final class NoDbMerma extends Merma
{
    use NoDbTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
