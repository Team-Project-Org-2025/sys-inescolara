<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Inventario;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbHerramienta;

final class HerramientaTest extends TestCase
{
    #[Test]
    public function valores_por_defecto(): void
    {
        $herramienta = new NoDbHerramienta();

        $this->assertNull($herramienta->getId());
        $this->assertSame('', $herramienta->getNombreHerramienta());
        $this->assertSame(1, $herramienta->getCantidad());
        $this->assertSame('disponible', $herramienta->getEstado());
        $this->assertNull($herramienta->getFechaUltimoMantenimiento());
        $this->assertNull($herramienta->getObservacion());
        $this->assertTrue($herramienta->isActivo());
    }

    #[Test]
    public function los_setters_son_fluentes_y_normalizan_los_valores(): void
    {
        $herramienta = new NoDbHerramienta();

        $this->assertSame($herramienta, $herramienta->setNombreHerramienta('  Pala  '));
        $this->assertSame('Pala', $herramienta->getNombreHerramienta());

        $this->assertSame($herramienta, $herramienta->setEstado('mantenimiento'));
        $this->assertSame('mantenimiento', $herramienta->getEstado());

        $this->assertSame($herramienta, $herramienta->setFechaUltimoMantenimiento('2026-01-15'));
        $this->assertSame('2026-01-15', $herramienta->getFechaUltimoMantenimiento());

        $this->assertSame($herramienta, $herramienta->setObservacion('  Mango suelto  '));
        $this->assertSame('Mango suelto', $herramienta->getObservacion());

        $herramienta->setObservacion('');
        $this->assertNull($herramienta->getObservacion());

        $this->assertSame($herramienta, $herramienta->setActivo(false));
        $this->assertFalse($herramienta->isActivo());

        $herramienta->setActivo(true);
        $this->assertTrue($herramienta->isActivo());
    }

    #[Test]
    public function setCantidad_nunca_baja_de_uno(): void
    {
        $herramienta = new NoDbHerramienta();

        $this->assertSame($herramienta, $herramienta->setCantidad(0));
        $this->assertSame(1, $herramienta->getCantidad());

        $herramienta->setCantidad(-4);
        $this->assertSame(1, $herramienta->getCantidad());

        $herramienta->setCantidad(7);
        $this->assertSame(7, $herramienta->getCantidad());
    }

    #[Test]
    public function fill_solo_acepta_campos_fillable(): void
    {
        $herramienta = new NoDbHerramienta();
        $herramienta->fill([
            'nombre_herramienta'         => 'Carretilla',
            'cantidad'                   => 4,
            'estado'                     => 'mantenimiento',
            'fecha_ultimo_mantenimiento' => '2026-02-01',
            'observacion'                => 'Rueda nueva',
            'activo'                     => 0,
            'id_herramienta'             => 77,
            'tipo'                       => 'ignorado',
        ]);

        $this->assertSame('Carretilla', $herramienta->getNombreHerramienta());
        $this->assertSame(4, $herramienta->getCantidad());
        $this->assertSame('mantenimiento', $herramienta->getEstado());
        $this->assertSame('2026-02-01', $herramienta->getFechaUltimoMantenimiento());
        $this->assertSame('Rueda nueva', $herramienta->getObservacion());
        $this->assertFalse($herramienta->isActivo());
        $this->assertNull($herramienta->getId());
    }

    #[Test]
    public function save_sin_nombre_lanza_excepcion_de_validacion(): void
    {
        $herramienta = new NoDbHerramienta(['cantidad' => 2]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: nombre_herramienta');

        $herramienta->save();
    }

    #[Test]
    public function save_con_nombre_invalido_lanza_excepcion(): void
    {
        $herramienta = new NoDbHerramienta(['nombre_herramienta' => '@@@']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nombre inválido (1-50 caracteres)');

        $herramienta->save();
    }

    #[Test]
    public function save_con_cantidad_negativa_lanza_excepcion(): void
    {
        $herramienta = new NoDbHerramienta([
            'nombre_herramienta' => 'Pala',
            'cantidad'           => -2,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe ser un número entero positivo');

        $herramienta->save();
    }

    #[Test]
    public function save_con_datos_validos_devuelve_false_sin_base_de_datos(): void
    {
        $herramienta = new NoDbHerramienta([
            'nombre_herramienta'         => 'Pala',
            'cantidad'                   => 3,
            'estado'                     => 'disponible',
            'fecha_ultimo_mantenimiento' => '2026-01-10',
        ]);

        $this->assertFalse($herramienta->save());
    }

    #[Test]
    public function getById_degrada_a_null_sin_base_de_datos(): void
    {
        $herramienta = new NoDbHerramienta();

        $this->assertNull($herramienta->getById(1));
    }

    #[Test]
    public function exists_degrada_a_false_sin_base_de_datos(): void
    {
        $herramienta = new NoDbHerramienta();

        $this->assertFalse($herramienta->exists(1));
    }

    #[Test]
    public function getAllWithAvailability_degrada_a_lista_vacia_sin_base_de_datos(): void
    {
        $herramienta = new NoDbHerramienta();

        $this->assertSame([], $herramienta->getAllWithAvailability());
    }

    #[Test]
    public function getUsages_degrada_a_lista_vacia_sin_base_de_datos(): void
    {
        $herramienta = new NoDbHerramienta();

        $this->assertSame([], $herramienta->getUsages(1));
    }

    #[Test]
    public function recordUsageWithStateUpdate_exige_base_de_datos(): void
    {
        $herramienta = new NoDbHerramienta();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $herramienta->recordUsageWithStateUpdate([
            'id_asignacion'  => 1,
            'id_herramienta' => 1,
            'fecha_uso'      => '2026-01-01',
        ]);
    }
}
