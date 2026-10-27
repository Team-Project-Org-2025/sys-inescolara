<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Inventario;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbUsoHerramienta;

final class UsoHerramientaTest extends TestCase
{
    #[Test]
    public function add_sin_datos_requeridos_lanza_excepcion_de_validacion(): void
    {
        $uso = new NoDbUsoHerramienta();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Este campo es requerido: id_asignacion; Este campo es requerido: id_herramienta; Este campo es requerido: fecha_uso'
        );

        $uso->add([]);
    }

    #[Test]
    public function add_con_fecha_vacia_lanza_excepcion_de_validacion(): void
    {
        $uso = new NoDbUsoHerramienta();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: fecha_uso');

        $uso->add([
            'id_asignacion'  => 1,
            'id_herramienta' => 1,
            'fecha_uso'      => '',
        ]);
    }

    #[Test]
    public function add_con_datos_completos_exige_base_de_datos(): void
    {
        $uso = new NoDbUsoHerramienta();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $uso->add([
            'id_asignacion'               => 1,
            'id_herramienta'              => 2,
            'fecha_uso'                   => '2026-03-01',
            'observacion'                 => 'Uso en invernadero',
            'estado_herramienta_post_uso' => 'ok',
        ]);
    }

    #[Test]
    public function add_acepta_los_campos_opcionales_ausentes(): void
    {
        $uso = new NoDbUsoHerramienta();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $uso->add([
            'id_asignacion'  => 1,
            'id_herramienta' => 2,
            'fecha_uso'      => '2026-03-01',
        ]);
    }

    #[Test]
    public function getAll_exige_base_de_datos(): void
    {
        $uso = new NoDbUsoHerramienta();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $uso->getAll();
    }

    #[Test]
    public function getById_exige_base_de_datos(): void
    {
        $uso = new NoDbUsoHerramienta();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $uso->getById(1);
    }

    #[Test]
    public function exists_exige_base_de_datos(): void
    {
        $uso = new NoDbUsoHerramienta();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $uso->exists(1);
    }

    #[Test]
    public function getByAssignment_exige_base_de_datos(): void
    {
        $uso = new NoDbUsoHerramienta();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $uso->getByAssignment(1);
    }

    #[Test]
    public function delete_exige_base_de_datos(): void
    {
        $uso = new NoDbUsoHerramienta();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $uso->delete(1);
    }
}
