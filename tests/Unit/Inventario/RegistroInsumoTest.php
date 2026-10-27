<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Inventario;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbRegistroInsumo;

final class RegistroInsumoTest extends TestCase
{
    #[Test]
    public function valores_por_defecto(): void
    {
        $registro = new NoDbRegistroInsumo();

        $this->assertNull($registro->getId());
        $this->assertNull($registro->getIdLote());
        $this->assertNull($registro->getIdInsumo());
        $this->assertNull($registro->getIdAsignacion());
        $this->assertSame(0.0, $registro->getCantidad());
        $this->assertSame(0.0, $registro->getCostoUnitario());
        $this->assertNull($registro->getFechaRegistro());
    }

    #[Test]
    public function fill_solo_acepta_campos_fillable(): void
    {
        $registro = new NoDbRegistroInsumo();
        $registro->fill([
            'id_lote'            => 3,
            'id_insumo'          => 4,
            'id_asignacion'      => 5,
            'cantidad'           => 2,
            'costo_unitario'     => 1.75,
            'fecha_registro'     => '2026-03-01',
            'id_registro_insumo' => 88,
            'desconocido'        => 'ignorado',
        ]);

        $this->assertSame(3, $registro->getIdLote());
        $this->assertSame(4, $registro->getIdInsumo());
        $this->assertSame(5, $registro->getIdAsignacion());
        $this->assertSame(2.0, $registro->getCantidad());
        $this->assertSame(1.75, $registro->getCostoUnitario());
        $this->assertSame('2026-03-01', $registro->getFechaRegistro());
        $this->assertNull($registro->getId());
    }

    #[Test]
    public function save_sin_datos_requeridos_lanza_excepcion_de_validacion(): void
    {
        $registro = new NoDbRegistroInsumo();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Este campo es requerido: id_lote; Este campo es requerido: id_insumo; Este campo es requerido: fecha_registro'
        );

        $registro->save();
    }

    #[Test]
    public function save_con_cantidad_fraccionaria_lanza_excepcion(): void
    {
        $registro = new NoDbRegistroInsumo([
            'id_lote'        => 1,
            'id_insumo'      => 1,
            'cantidad'       => 2.5,
            'costo_unitario' => 1.5,
            'fecha_registro' => '2026-03-01',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe ser un número entero positivo');

        $registro->save();
    }

    #[Test]
    public function save_con_costo_negativo_lanza_excepcion(): void
    {
        $registro = new NoDbRegistroInsumo([
            'id_lote'        => 1,
            'id_insumo'      => 1,
            'cantidad'       => 2,
            'costo_unitario' => -5,
            'fecha_registro' => '2026-03-01',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Precio inválido (formato: 0.00)');

        $registro->save();
    }

    #[Test]
    public function save_con_datos_validos_devuelve_false_sin_base_de_datos(): void
    {
        $registro = new NoDbRegistroInsumo([
            'id_lote'        => 1,
            'id_insumo'      => 1,
            'cantidad'       => 3,
            'costo_unitario' => 2.5,
            'fecha_registro' => '2026-03-01',
        ]);

        $this->assertFalse($registro->save());
    }

    #[Test]
    public function getByLote_degrada_a_lista_vacia_sin_base_de_datos(): void
    {
        $registro = new NoDbRegistroInsumo();

        $this->assertSame([], $registro->getByLote(1));
    }

    #[Test]
    public function getByAsignacion_degrada_a_lista_vacia_sin_base_de_datos(): void
    {
        $registro = new NoDbRegistroInsumo();

        $this->assertSame([], $registro->getByAsignacion(1));
    }

    #[Test]
    public function getById_degrada_a_null_sin_base_de_datos(): void
    {
        $registro = new NoDbRegistroInsumo();

        $this->assertNull($registro->getById(1));
    }

    #[Test]
    public function eliminarPorLote_exige_base_de_datos(): void
    {
        $registro = new NoDbRegistroInsumo();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $registro->eliminarPorLote(1);
    }

    #[Test]
    public function eliminarPorAsignacion_exige_base_de_datos(): void
    {
        $registro = new NoDbRegistroInsumo();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $registro->eliminarPorAsignacion(1);
    }
}
