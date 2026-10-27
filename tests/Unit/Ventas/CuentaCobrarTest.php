<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Ventas;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbCuentaCobrar;

final class CuentaCobrarTest extends TestCase
{
    #[Test]
    public function fill_acepta_atributos_aunque_el_modelo_no_declare_propiedades(): void
    {
        $cuenta = new NoDbCuentaCobrar([
            'id_venta'   => 10,
            'monto'      => 50.5,
            'metodo'     => 'efectivo',
            'referencia' => null,
        ]);

        $this->assertSame([], get_object_vars($cuenta));
        $this->assertSame($cuenta, $cuenta->fill(['monto' => 75.0]));
    }

    #[Test]
    public function obtenerPorId_devuelve_null_para_ids_invalidos(): void
    {
        $cuenta = new NoDbCuentaCobrar();

        $this->assertNull($cuenta->obtenerPorId(0));
        $this->assertNull($cuenta->obtenerPorId(-5));
    }

    #[Test]
    public function obtenerPorId_devuelve_null_sin_base_de_datos(): void
    {
        $this->assertNull((new NoDbCuentaCobrar())->obtenerPorId(1));
    }

    #[Test]
    public function obtenerTodos_devuelve_estructura_vacia_sin_base_de_datos(): void
    {
        $this->assertSame(
            ['data' => [], 'total' => 0, 'filtered' => 0],
            (new NoDbCuentaCobrar())->obtenerTodos()
        );
    }

    #[Test]
    public function obtenerEstadisticas_devuelve_ceros_sin_base_de_datos(): void
    {
        $this->assertSame([
            'total_cuentas'    => 0,
            'total_por_cobrar' => 0,
            'total_pagadas'    => 0,
            'total_vencidas'   => 0,
            'total_vigentes'   => 0,
            'monto_vigente'    => 0,
            'monto_vencido'    => 0,
            'cobrado_mes'      => 0,
        ], (new NoDbCuentaCobrar())->obtenerEstadisticas());
    }

    #[Test]
    public function obtenerClientes_y_obtenerPagos_devuelven_listas_vacias_sin_base_de_datos(): void
    {
        $cuenta = new NoDbCuentaCobrar();

        $this->assertSame([], $cuenta->obtenerClientes());
        $this->assertSame([], $cuenta->obtenerPagos(1));
    }

    #[Test]
    public function registrarPago_con_id_de_venta_invalido_lanza_excepcion(): void
    {
        $cuenta = new NoDbCuentaCobrar();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ID de venta inválido.');

        $cuenta->registrarPago(0, 100.0, 'efectivo', null, date('Y-m-d'), null, 1, null);
    }

    #[Test]
    public function registrarPago_con_monto_cero_lanza_excepcion(): void
    {
        $cuenta = new NoDbCuentaCobrar();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El monto debe ser mayor a cero.');

        $cuenta->registrarPago(1, 0.0, 'efectivo', null, date('Y-m-d'), null, 1, null);
    }

    #[Test]
    public function registrarPago_con_monto_negativo_lanza_excepcion_de_formato(): void
    {
        $cuenta = new NoDbCuentaCobrar();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Precio inválido (formato: 0.00)');

        $cuenta->registrarPago(1, -5.0, 'efectivo', null, date('Y-m-d'), null, 1, null);
    }

    #[Test]
    public function registrarPago_sin_usuario_lanza_excepcion(): void
    {
        $cuenta = new NoDbCuentaCobrar();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe seleccionar el trabajador que cobró.');

        $cuenta->registrarPago(1, 100.0, 'efectivo', null, date('Y-m-d'), null, 0, null);
    }

    #[Test]
    public function registrarPago_con_metodo_invalido_lanza_excepcion(): void
    {
        $cuenta = new NoDbCuentaCobrar();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Método de pago inválido.');

        $cuenta->registrarPago(1, 100.0, 'bitcoin', null, date('Y-m-d'), null, 1, null);
    }

    #[Test]
    public function registrarPago_con_fecha_con_formato_invalido_lanza_excepcion(): void
    {
        $cuenta = new NoDbCuentaCobrar();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Formato de fecha de pago inválido (Y-m-d).');

        $cuenta->registrarPago(1, 100.0, 'efectivo', null, '31-12-2025', null, 1, null);
    }

    #[Test]
    public function registrarPago_con_fecha_futura_lanza_excepcion(): void
    {
        $cuenta = new NoDbCuentaCobrar();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La fecha de pago no puede ser posterior al día de hoy.');

        $cuenta->registrarPago(1, 100.0, 'efectivo', null, date('Y-m-d', strtotime('+2 days')), null, 1, null);
    }

    #[Test]
    public function registrarPago_con_datos_validos_no_encuentra_la_venta_sin_base_de_datos(): void
    {
        $cuenta = new NoDbCuentaCobrar();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Venta no encontrada.');

        $cuenta->registrarPago(1, 100.0, 'efectivo', null, date('Y-m-d'), null, 1, null);
    }

    #[Test]
    public function iniciarTransaccion_lanza_excepcion_sin_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbCuentaCobrar())->iniciarTransaccion();
    }

    #[Test]
    public function confirmarTransaccion_lanza_excepcion_sin_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbCuentaCobrar())->confirmarTransaccion();
    }

    #[Test]
    public function revertirTransaccion_lanza_excepcion_sin_base_de_datos(): void
    {
        $this->expectException(RuntimeException::class);

        (new NoDbCuentaCobrar())->revertirTransaccion();
    }
}
