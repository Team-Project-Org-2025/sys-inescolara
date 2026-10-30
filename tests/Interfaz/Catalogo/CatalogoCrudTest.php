<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Catalogo;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Support\CrudConfigs;
use SysInescolara\Tests\Interfaz\Support\CrudTestCase;
use SysInescolara\Tests\Interfaz\Support\Fixtures;

final class CatalogoCrudTest extends CrudTestCase
{
    #[Test]
    public function crud_de_especies(): void
    {
        $name = $this->unique('Especie', 50);
        $edited = $name . ' edit';

        $this->runCrud(
            CrudConfigs::especies(),
            ['nombre_especie' => $name, 'descripcion' => 'Descripción E2E'],
            ['nombre_especie' => $edited, 'descripcion' => 'Descripción actualizada E2E'],
            $name,
            $edited
        );
    }

    #[Test]
    public function crud_de_ubicaciones(): void
    {
        $name = $this->unique('Ubicacion', 100);
        $edited = $name . ' edit';

        $this->runCrud(
            CrudConfigs::ubicaciones(),
            ['nombre_ubicacion' => $name, 'descripcion' => 'Ubicación E2E', 'tipo' => 'Interno'],
            ['nombre_ubicacion' => $edited, 'descripcion' => 'Ubicación actualizada E2E', 'tipo' => 'Externo'],
            $name,
            $edited
        );
    }

    #[Test]
    public function crud_de_plantas(): void
    {
        $especie = Fixtures::especie();
        $name = $this->unique('Planta', 50);
        $edited = $name . ' edit';

        $this->runCrud(
            CrudConfigs::plantas(),
            [
                'nombre_comun' => $name,
                'nombre_tecnico' => 'E2E technical',
                'especie_id' => $especie['id'],
            ],
            [
                'nombre_comun' => $edited,
                'nombre_tecnico' => 'E2E technical editada',
            ],
            $name,
            $edited
        );
    }
}
