<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Compras;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbPurchase;

final class PurchaseTest extends TestCase
{
    #[Test]
    public function obtener_todas_sin_base_de_datos_devuelve_arreglo_vacio(): void
    {
        $this->assertSame([], (new NoDbPurchase())->getAll());
    }

    #[Test]
    public function obtener_por_id_sin_base_de_datos_devuelve_null(): void
    {
        $this->assertNull((new NoDbPurchase())->getById(1));
    }

    #[Test]
    public function obtener_detalles_sin_base_de_datos_devuelve_arreglo_vacio(): void
    {
        $this->assertSame([], (new NoDbPurchase())->obtenerDetalles(1));
    }

    #[Test]
    public function agregar_sin_fecha_lanza_excepcion_de_validacion(): void
    {
        $compra = new NoDbPurchase();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: fecha_compra');

        $compra->agregar(1, '', 'Factura', null, 100.0, 16.0, 116.0, null);
    }

    #[Test]
    public function agregar_sin_tipo_de_comprobante_lanza_excepcion_de_validacion(): void
    {
        $compra = new NoDbPurchase();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: tipo_comprobante');

        $compra->agregar(1, '2026-10-27', '', null, 100.0, 16.0, 116.0, null);
    }

    #[Test]
    public function agregar_con_subtotal_con_mas_de_dos_decimales_lanza_excepcion(): void
    {
        $compra = new NoDbPurchase();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Precio inválido (formato: 0.00)');

        $compra->agregar(1, '2026-10-27', 'Factura', null, 100.999, 16.0, 116.999, null);
    }

    #[Test]
    public function agregar_con_iva_negativo_lanza_excepcion(): void
    {
        $compra = new NoDbPurchase();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Precio inválido (formato: 0.00)');

        $compra->agregar(1, '2026-10-27', 'Factura', null, 100.0, -1.0, 99.0, null);
    }

    #[Test]
    public function agregar_con_datos_validos_requiere_base_de_datos(): void
    {
        $compra = new NoDbPurchase();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $compra->agregar(1, '2026-10-27', 'Factura', 'F-001', 100.0, 16.0, 116.0, 'Compra de prueba');
    }

    #[Test]
    public function actualizar_con_fecha_vacia_lanza_excepcion_de_validacion(): void
    {
        $compra = new NoDbPurchase();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: fecha_compra');

        $compra->actualizar(1, 1, '', 'Factura', null, 100.0, 16.0, 116.0, null);
    }

    #[Test]
    public function actualizar_con_datos_validos_requiere_base_de_datos(): void
    {
        $compra = new NoDbPurchase();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $compra->actualizar(1, 1, '2026-10-27', 'Factura', 'F-001', 100.0, 16.0, 116.0, null);
    }

    #[Test]
    public function actualizar_estado_invalido_lanza_excepcion(): void
    {
        $compra = new NoDbPurchase();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Estado inválido.');

        $compra->actualizarEstado(1, 'despachada');
    }

    #[Test]
    public function actualizar_estado_sin_compra_lanza_excepcion(): void
    {
        $compra = new NoDbPurchase();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No existe la compra.');

        $compra->actualizarEstado(1, 'recibida');
    }

    #[Test]
    public function marcar_recibida_sin_compra_lanza_excepcion(): void
    {
        $compra = new NoDbPurchase();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No existe la compra.');

        $compra->marcarRecibida(1);
    }

    #[Test]
    public function aplicar_stock_sin_base_de_datos_lanza_excepcion(): void
    {
        $compra = new NoDbPurchase();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $compra->aplicarStock(1);
    }

    #[Test]
    public function eliminar_sin_base_de_datos_lanza_excepcion(): void
    {
        $compra = new NoDbPurchase();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $compra->delete(1);
    }

    #[Test]
    public function restaurar_sin_base_de_datos_lanza_excepcion(): void
    {
        $compra = new NoDbPurchase();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $compra->restaurar(1);
    }

    #[Test]
    public function existe_sin_base_de_datos_lanza_excepcion(): void
    {
        $compra = new NoDbPurchase();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $compra->exists(1);
    }

    #[Test]
    public function iniciar_transaccion_sin_base_de_datos_lanza_excepcion(): void
    {
        $compra = new NoDbPurchase();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $compra->iniciarTransaccion();
    }

    #[Test]
    public function crear_cuenta_pagar_sin_base_de_datos_lanza_excepcion(): void
    {
        $compra = new NoDbPurchase();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $compra->crearCuentaPagar(1, 116.0);
    }

    #[Test]
    public function obtener_ultimo_id_sin_base_de_datos_lanza_excepcion(): void
    {
        $compra = new NoDbPurchase();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base de datos no disponible en tests unitarios');

        $compra->obtenerUltimoId();
    }
}
