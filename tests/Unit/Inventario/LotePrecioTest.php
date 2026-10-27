<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Unit\Inventario;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SysInescolara\Tests\Support\NoDbLotePrecio;

final class LotePrecioTest extends TestCase
{
    #[Test]
    public function calcularPrecioLote_degrada_a_null_sin_base_de_datos(): void
    {
        $this->assertNull(NoDbLotePrecio::calcularPrecioLote(1));
    }

    #[Test]
    public function getDetalleInsumos_degrada_a_lista_vacia_sin_base_de_datos(): void
    {
        $this->assertSame([], NoDbLotePrecio::getDetalleInsumos(1));
    }

    #[Test]
    public function getPreciosLotes_degrada_a_lista_vacia_sin_base_de_datos(): void
    {
        $this->assertSame([], NoDbLotePrecio::getPreciosLotes());
    }

    #[Test]
    public function getAll_degrada_a_lista_vacia_sin_base_de_datos(): void
    {
        $this->assertSame([], (new NoDbLotePrecio())->getAll());
    }

    #[Test]
    public function getById_degrada_a_null_sin_base_de_datos(): void
    {
        $this->assertNull((new NoDbLotePrecio())->getById(1));
    }

    #[Test]
    public function actualizarCostoUnitario_degrada_a_false_sin_base_de_datos(): void
    {
        $this->assertFalse(NoDbLotePrecio::actualizarCostoUnitario(1, 12.5));
    }

    #[Test]
    public function actualizarPorcentajeGanancia_degrada_a_false_sin_base_de_datos(): void
    {
        $this->assertFalse(NoDbLotePrecio::actualizarPorcentajeGanancia(1, 40.0));
    }
}
