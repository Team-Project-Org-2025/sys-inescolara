<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Support;

use PDO;
use RuntimeException;

/**
 * Permite instanciar un modelo sin abrir MySQL.
 *
 * El modelo real sigue ejecutando su constructor, pero el doble evita
 * parent::__construct() y hace que cualquier intento de tocar la base de
 * datos falle de forma explícita. Así una prueba unitaria no puede pasar
 * "por accidente" usando MySQL.
 */
trait NoDbTrait
{
    protected function db(): PDO
    {
        throw new RuntimeException('Base de datos no disponible en tests unitarios');
    }
}
