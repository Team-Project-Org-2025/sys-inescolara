<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Ventas;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SysInescolara\Tests\Support\NoDbVenta;

final class VentaTest extends TestCase
{
    #[Test]
    public function valores_por_defecto(): void
    {
        $venta = new NoDbVenta();

        $this->assertNull($venta->getId());
        $this->assertNull($venta->getReferencia());
        $this->assertNull($venta->getIdCliente());
        $this->assertNull($venta->getIdUsuario());
        $this->assertSame('contado', $venta->getTipoVenta());
        $this->assertSame('completada', $venta->getEstado());
        $this->assertSame(16.0, $venta->getIvaPorcentaje());
        $this->assertNull($venta->getFechaVenta());
        $this->assertNull($venta->getFechaVencimiento());
        $this->assertNull($venta->getObservaciones());
        $this->assertTrue($venta->isActivo());
    }

    #[Test]
    public function los_setters_son_fluentes_y_normalizan_los_valores(): void
    {
        $venta = new NoDbVenta();

        $this->assertSame($venta, $venta->setReferencia('VEN-20261027-001'));
        $this->assertSame('VEN-20261027-001', $venta->getReferencia());

        $this->assertSame($venta, $venta->setIdCliente(7));
        $this->assertSame(7, $venta->getIdCliente());

        $this->assertSame($venta, $venta->setIdUsuario(3));
        $this->assertSame(3, $venta->getIdUsuario());

        $this->assertSame($venta, $venta->setTipoVenta('credito'));
        $this->assertSame('credito', $venta->getTipoVenta());

        $this->assertSame($venta, $venta->setEstado('pendiente'));
        $this->assertSame('pendiente', $venta->getEstado());

        $this->assertSame($venta, $venta->setFechaVenta('2026-10-27 10:00:00'));
        $this->assertSame('2026-10-27 10:00:00', $venta->getFechaVenta());

        $this->assertSame($venta, $venta->setFechaVencimiento('2026-11-26'));
        $this->assertSame('2026-11-26', $venta->getFechaVencimiento());

        $this->assertSame($venta, $venta->setObservaciones('  Entrega en vivero  '));
        $this->assertSame('Entrega en vivero', $venta->getObservaciones());

        $this->assertSame($venta, $venta->setObservaciones('   '));
        $this->assertSame('', $venta->getObservaciones());

        $this->assertSame($venta, $venta->setObservaciones(null));
        $this->assertNull($venta->getObservaciones());

        $this->assertSame($venta, $venta->setActivo(false));
        $this->assertFalse($venta->isActivo());

        $venta->setActivo(true);
        $this->assertTrue($venta->isActivo());
    }

    #[Test]
    public function setIvaPorcentaje_no_acepta_valores_negativos(): void
    {
        $venta = new NoDbVenta();

        $this->assertSame($venta, $venta->setIvaPorcentaje(-5.0));
        $this->assertSame(0.0, $venta->getIvaPorcentaje());

        $venta->setIvaPorcentaje(8.5);
        $this->assertSame(8.5, $venta->getIvaPorcentaje());
    }

    #[Test]
    public function fill_solo_acepta_campos_fillable(): void
    {
        $venta = new NoDbVenta();
        $venta->fill([
            'id_venta'    => 50,
            'referencia'  => 'VEN-1',
            'id_cliente'  => 4,
            'id_usuario'  => 2,
            'tipo_venta'  => 'credito',
            'estado'      => 'pendiente',
            'iva_porcentaje' => 8.0,
            'observaciones' => 'Prueba',
            'activo'      => 0,
            'productos'   => [['id_lote' => 1]],
            'desconocido' => 'ignorado',
        ]);

        $this->assertNull($venta->getId());
        $this->assertSame('VEN-1', $venta->getReferencia());
        $this->assertSame(4, $venta->getIdCliente());
        $this->assertSame(2, $venta->getIdUsuario());
        $this->assertSame('credito', $venta->getTipoVenta());
        $this->assertSame('pendiente', $venta->getEstado());
        $this->assertSame(8.0, $venta->getIvaPorcentaje());
        $this->assertSame('Prueba', $venta->getObservaciones());
        $this->assertFalse($venta->isActivo());
    }

    #[Test]
    public function agregar_sin_id_usuario_lanza_excepcion_de_validacion(): void
    {
        $venta = new NoDbVenta();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este campo es requerido: id_usuario');

        $venta->agregar([
            'id_cliente' => 1,
            'productos'  => [['id_lote' => 1, 'cantidad' => 1, 'precio_unitario' => 10]],
        ]);
    }

    #[Test]
    public function agregar_con_datos_validos_no_puede_abrir_transaccion_sin_base_de_datos(): void
    {
        $venta = new NoDbVenta();

        $this->expectException(RuntimeException::class);

        $venta->agregar([
            'id_cliente' => 1,
            'id_usuario' => 1,
            'productos'  => [['id_lote' => 1, 'cantidad' => 1, 'precio_unitario' => 10]],
        ]);
    }

    #[Test]
    public function getAll_devuelve_lista_vacia_sin_base_de_datos(): void
    {
        $this->assertSame([], (new NoDbVenta())->getAll());
    }

    #[Test]
    public function getById_devuelve_null_sin_base_de_datos(): void
    {
        $this->assertNull((new NoDbVenta())->getById(1));
    }

    #[Test]
    public function obtenerVentaConDetalles_devuelve_null_sin_base_de_datos(): void
    {
        $this->assertNull((new NoDbVenta())->obtenerVentaConDetalles(1));
    }

    #[Test]
    public function obtenerDetalles_y_obtenerPagos_devuelven_listas_vacias_sin_base_de_datos(): void
    {
        $venta = new NoDbVenta();

        $this->assertSame([], $venta->obtenerDetalles(1));
        $this->assertSame([], $venta->obtenerPagos(1));
    }

    #[Test]
    public function exists_devuelve_false_sin_base_de_datos(): void
    {
        $this->assertFalse((new NoDbVenta())->exists(1));
    }

    #[Test]
    public function obtenerUltimoId_y_getLastInsertId_devuelven_null_sin_base_de_datos(): void
    {
        $venta = new NoDbVenta();

        $this->assertNull($venta->obtenerUltimoId());
        $this->assertNull($venta->getLastInsertId());
    }

    #[Test]
    public function buscarClientes_y_obtenerLotesDisponibles_devuelven_listas_vacias_sin_base_de_datos(): void
    {
        $venta = new NoDbVenta();

        $this->assertSame([], $venta->buscarClientes('Ana'));
        $this->assertSame([], $venta->obtenerLotesDisponibles('Rosa'));
        $this->assertSame([], $venta->obtenerTrabajadoresActivos());
    }
}
