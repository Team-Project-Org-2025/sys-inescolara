<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Sistema;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Support\CrudConfigs;
use SysInescolara\Tests\Interfaz\Support\CrudTestCase;

final class RolesTest extends CrudTestCase
{
    #[Test]
    public function crud_de_roles(): void
    {
        $name = $this->unique('Rol', 30);
        $edited = mb_substr($name . ' edit', 0, 30);

        $this->runCrud(
            CrudConfigs::roles(),
            ['nombre_rol' => $name, 'descripcion_rol' => 'Rol E2E'],
            ['nombre_rol' => $edited, 'descripcion_rol' => 'Rol E2E editado'],
            $name,
            $edited
        );
    }
}
