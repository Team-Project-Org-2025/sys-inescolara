<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Compras;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbCuentaPagar;

final class CuentaPagarTest extends TestCase
{
    #[Test]
    public function valores_por_defecto(): void
    {
        $cuenta = new NoDbCuentaPagar();

        $this->assertNull($cuenta->getId());
        $this->assertNull($cuenta->getIdCompra());
        $this->assertSame(0.0, $cuenta->getMontoTotal());
        $this->assertSame(0.0, $cuenta->getSaldoPendiente());
        $this->assertNull($cuenta->getFechaVencimiento());
        $this->assertSame('pendiente', $cuenta->getEstado());
        $this->assertNull($cuenta->getObservacion());
        $this->assertTrue($cuenta->isActivo());
        $this->assertNull($cuenta->getCreatedAt());
        $this->assertNull($cuenta->getUpdatedAt());
    }

    #[Test]
    public function los_setters_son_fluentes(): void
    {
        $cuenta = new NoDbCuentaPagar();

        $this->assertSame($cuenta, $cuenta->setIdCompra(7));
        $this->assertSame(7, $cuenta->getIdCompra());

        $this->assertSame($cuenta, $cuenta->setMontoTotal(150.75));
        $this->assertSame(150.75, $cuenta->getMontoTotal());

        $this->assertSame($cuenta, $cuenta->setFechaVencimiento('2026-11-30'));
        $this->assertSame('2026-11-30', $cuenta->getFechaVencimiento());

        $this->assertSame($cuenta, $cuenta->setObservacion('Pago a 30 días'));
        $this->assertSame('Pago a 30 días', $cuenta->getObservacion());

        $this->assertSame($cuenta, $cuenta->setActivo(false));
        $this->assertFalse($cuenta->isActivo());

        $cuenta->setActivo(true);
        $this->assertTrue($cuenta->isActivo());
    }

    #[Test]
    public function el_monto_nunca_es_negativo_al_usar_el_setter(): void
    {
        $cuenta = new NoDbCuentaPagar();

        $cuenta->setMontoTotal(-50.0);

        $this->assertSame(0.0, $cuenta->getMontoTotal());
    }

    #[Test]
    public function fill_solo_acepta_campos_fillable(): void
    {
        $cuenta = new NoDbCuentaPagar();
        $cuenta->fill([
            'id_compra'         => 3,
            'monto_total'       => 100.0,
            'fecha_vencimiento' => '2026-11-30',
            'observacion'       => 'Pago diferido',
            'saldo_pendiente'   => 999.0,
            'estado'            => 'pagada',
            'id_cuenta_pagar'   => 99,
            'activo'            => 0,
        ]);

        $this->assertSame(3, $cuenta->getIdCompra());
        $this->assertSame(100.0, $cuenta->getMontoTotal());
        $this->assertSame('2026-11-30', $cuenta->getFechaVencimiento());
        $this->assertSame('Pago diferido', $cuenta->getObservacion());
        $this->assertSame(0.0, $cuenta->getSaldoPendiente());
        $this->assertSame('pendiente', $cuenta->getEstado());
        $this->assertNull($cuenta->getId());
        $this->assertTrue($cuenta->isActivo());
    }

    #[Test]
    public function el_constructor_aplica_fill(): void
    {
        $cuenta = new NoDbCuentaPagar([
            'id_compra'   => 5,
            'monto_total' => 80.0,
        ]);

        $this->assertSame(5, $cuenta->getIdCompra());
        $this->assertSame(80.0, $cuenta->getMontoTotal());
        $this->assertNull($cuenta->getId());
    }

    #[Test]
    public function save_sin_id_compra_lanza_excepcion_de_validacion(): void
    {
        $cuenta = new NoDbCuentaPagar();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: id_compra');

        $cuenta->save();
    }

    #[Test]
    public function save_con_monto_negativo_lanza_excepcion(): void
    {
        $cuenta = new NoDbCuentaPagar([
            'id_compra'   => 1,
            'monto_total' => -10.5,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Precio inválido (formato: 0.00)');

        $cuenta->save();
    }

    #[Test]
    public function save_con_mas_de_dos_decimales_lanza_excepcion(): void
    {
        $cuenta = new NoDbCuentaPagar([
            'id_compra'   => 1,
            'monto_total' => 10.999,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Precio inválido (formato: 0.00)');

        $cuenta->save();
    }

    #[Test]
    public function save_con_datos_validos_sin_base_de_datos_devuelve_false(): void
    {
        $cuenta = new NoDbCuentaPagar([
            'id_compra'         => 1,
            'monto_total'       => 116.0,
            'fecha_vencimiento' => '2026-11-30',
            'observacion'       => 'Compra de insumos',
        ]);

        $this->assertFalse($cuenta->save());
    }

    #[Test]
    public function crear_con_datos_validos_sin_base_de_datos_devuelve_false(): void
    {
        $cuenta = new NoDbCuentaPagar();

        $this->assertFalse($cuenta->crear(1, 116.0, '2026-11-30', 'Compra de insumos'));
    }

    #[Test]
    public function crear_con_monto_negativo_lanza_excepcion_de_validacion(): void
    {
        $cuenta = new NoDbCuentaPagar();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Precio inválido (formato: 0.00)');

        $cuenta->crear(1, -10.0);
    }

    #[Test]
    public function registrar_pago_sin_cuenta_lanza_excepcion(): void
    {
        $cuenta = new NoDbCuentaPagar();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No existe la cuenta por pagar.');

        $cuenta->registrarPago(1, 50.0, '2026-10-27', 'transferencia');
    }

    #[Test]
    public function obtener_pagos_sin_base_de_datos_devuelve_arreglo_vacio(): void
    {
        $this->assertSame([], (new NoDbCuentaPagar())->obtenerPagos(1));
    }

    #[Test]
    public function obtener_por_compra_sin_base_de_datos_devuelve_null(): void
    {
        $this->assertNull((new NoDbCuentaPagar())->obtenerPorCompra(1));
    }

    #[Test]
    public function getAll_sin_base_de_datos_devuelve_arreglo_vacio(): void
    {
        $this->assertSame([], (new NoDbCuentaPagar())->getAll());
    }

    #[Test]
    public function get_by_id_sin_base_de_datos_devuelve_null(): void
    {
        $this->assertNull((new NoDbCuentaPagar())->getById(1));
    }

    #[Test]
    public function load_by_id_sin_base_de_datos_lanza_excepcion(): void
    {
        $cuenta = new NoDbCuentaPagar();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $cuenta->loadById(1);
    }

    #[Test]
    public function anular_pago_sin_base_de_datos_lanza_excepcion(): void
    {
        $cuenta = new NoDbCuentaPagar();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $cuenta->anularPago(1);
    }

    #[Test]
    public function eliminar_sin_base_de_datos_lanza_excepcion(): void
    {
        $cuenta = new NoDbCuentaPagar();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $cuenta->delete(1);
    }

    #[Test]
    public function restaurar_sin_base_de_datos_lanza_excepcion(): void
    {
        $cuenta = new NoDbCuentaPagar();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $cuenta->restaurar(1);
    }

    #[Test]
    public function existe_sin_base_de_datos_lanza_excepcion(): void
    {
        $cuenta = new NoDbCuentaPagar();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $cuenta->exists(1);
    }

    #[Test]
    public function obtener_ultimo_id_sin_base_de_datos_lanza_excepcion(): void
    {
        $cuenta = new NoDbCuentaPagar();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $cuenta->obtenerUltimoId();
    }
}
