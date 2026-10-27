<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use SysInescolara\models\SeedCollection;

/**
 * Doble de SeedCollection sin conexión a base de datos.
 * Permite probar getters, setters, fill y validaciones sin MySQL.
 */
final class NoDbSeedCollection extends SeedCollection
{
    use NoDbTrait;

    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
}
