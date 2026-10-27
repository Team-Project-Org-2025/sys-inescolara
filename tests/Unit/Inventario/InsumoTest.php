<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Inventario;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbInsumo;

final class InsumoTest extends TestCase
{
    #[Test]
    public function valores_por_defecto(): void
    {
        $insumo = new NoDbInsumo();

        $this->assertNull($insumo->getId());
        $this->assertSame('', $insumo->getNombreInsumo());
        $this->assertNull($insumo->getIdUnidadMedida());
        $this->assertNull($insumo->getCategoria());
        $this->assertSame(0.0, $insumo->getStockActual());
        $this->assertSame(0.0, $insumo->getCostoUnitarioActual());
        $this->assertTrue($insumo->isActivo());
    }

    #[Test]
    public function los_setters_son_fluentes_y_normalizan_los_valores(): void
    {
        $insumo = new NoDbInsumo();

        $this->assertSame($insumo, $insumo->setNombreInsumo('  Abono organico  '));
        $this->assertSame('Abono organico', $insumo->getNombreInsumo());

        $this->assertSame($insumo, $insumo->setIdUnidadMedida(3));
        $this->assertSame(3, $insumo->getIdUnidadMedida());

        $this->assertSame($insumo, $insumo->setCategoria('  Fertilizante  '));
        $this->assertSame('Fertilizante', $insumo->getCategoria());

        $insumo->setCategoria('');
        $this->assertNull($insumo->getCategoria());

        $this->assertSame($insumo, $insumo->setActivo(false));
        $this->assertFalse($insumo->isActivo());

        $insumo->setActivo(true);
        $this->assertTrue($insumo->isActivo());
    }

    #[Test]
    public function los_setters_numericos_no_admiten_valores_negativos(): void
    {
        $insumo = new NoDbInsumo();

        $this->assertSame($insumo, $insumo->setStockActual(-5.5));
        $this->assertSame(0.0, $insumo->getStockActual());

        $this->assertSame($insumo, $insumo->setCostoUnitarioActual(-1));
        $this->assertSame(0.0, $insumo->getCostoUnitarioActual());

        $insumo->setStockActual(12.75);
        $this->assertSame(12.75, $insumo->getStockActual());
    }

    #[Test]
    public function fill_solo_acepta_campos_fillable(): void
    {
        $insumo = new NoDbInsumo();
        $insumo->fill([
            'nombre_insumo'         => 'Semilla',
            'id_unidad_medida'      => 2,
            'categoria'             => 'Semillas',
            'stock_actual'          => 5,
            'costo_unitario_actual' => 1.5,
            'activo'                => 0,
            'id_insumo'             => 99,
            'desconocido'           => 'ignorado',
        ]);

        $this->assertSame('Semilla', $insumo->getNombreInsumo());
        $this->assertSame(2, $insumo->getIdUnidadMedida());
        $this->assertSame('Semillas', $insumo->getCategoria());
        $this->assertSame(5.0, $insumo->getStockActual());
        $this->assertSame(1.5, $insumo->getCostoUnitarioActual());
        $this->assertFalse($insumo->isActivo());
        $this->assertNull($insumo->getId());
    }

    #[Test]
    public function save_sin_nombre_lanza_excepcion_de_validacion(): void
    {
        $insumo = new NoDbInsumo(['id_unidad_medida' => 1]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: nombre_insumo');

        $insumo->save();
    }

    #[Test]
    public function save_sin_unidad_de_medida_lanza_excepcion_de_validacion(): void
    {
        $insumo = new NoDbInsumo(['nombre_insumo' => 'Abono']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: id_unidad_medida');

        $insumo->save();
    }

    #[Test]
    public function save_con_nombre_invalido_lanza_excepcion(): void
    {
        $insumo = new NoDbInsumo([
            'nombre_insumo'    => '###',
            'id_unidad_medida' => 1,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nombre inválido (1-50 caracteres)');

        $insumo->save();
    }

    #[Test]
    public function save_con_datos_validos_devuelve_false_sin_base_de_datos(): void
    {
        $insumo = new NoDbInsumo([
            'nombre_insumo'         => 'Abono',
            'id_unidad_medida'      => 1,
            'categoria'             => 'Fertilizante',
            'stock_actual'          => 10.5,
            'costo_unitario_actual' => 2.25,
        ]);

        $this->assertFalse($insumo->save());
    }

    #[Test]
    public function findByNameAndCategory_degrada_a_null_sin_base_de_datos(): void
    {
        $insumo = new NoDbInsumo();

        $this->assertNull($insumo->findByNameAndCategory('Abono', 'Fertilizante'));
    }

    #[Test]
    public function increaseStock_degrada_a_false_sin_base_de_datos(): void
    {
        $insumo = new NoDbInsumo();

        $this->assertFalse($insumo->increaseStock(1, 5.0));
    }

    #[Test]
    public function getById_degrada_a_null_sin_base_de_datos(): void
    {
        $insumo = new NoDbInsumo();

        $this->assertNull($insumo->getById(1));
    }

    #[Test]
    public function find_propaga_la_excepcion_sin_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        NoDbInsumo::find(1);
    }
}
