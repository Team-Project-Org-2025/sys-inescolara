<?php

namespace SysInescolara\Tests\Support;

use PDO;
use RuntimeException;
use SysInescolara\models\Planta;

/**
 * Doble de Planta sin conexión a base de datos.
 * El constructor no llama a parent::__construct() (evita MySQL y su exit()
 * en fallo de conexión) y db() lanza una excepción para demostrar que
 * los caminos unitarios nunca tocan la BD.
 */
class NoDbPlanta extends Planta
{
    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }

    protected function db(): PDO
    {
        throw new RuntimeException('Base de datos no disponible en tests unitarios');
    }
}
