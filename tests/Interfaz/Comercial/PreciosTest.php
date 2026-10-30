<?php

declare(strict_types=1);

namespace SysInescolara\Tests\Interfaz\Comercial;

use PHPUnit\Framework\Attributes\Test;
use SysInescolara\Tests\Interfaz\Support\Fixtures;
use SysInescolara\Tests\Interfaz\Support\InterfazTestCase;

final class PreciosTest extends InterfazTestCase
{
    #[Test]
    public function actualiza_costo_y_porcentaje_de_ganancia_de_un_lote(): void
    {
        $lote = Fixtures::lote();

        $this->visit('dashboard/precios');
        $this->waitForDataTable('preciosTable');
        $row = $this->tableRowWithText('preciosTable', $lote['planta']['name']);
        $this->clickIn($row, '.btn-edit-precio');
        $this->waitForVisibleCss('#editarPrecioModal.show');

        $this->fill('#editCostoUnitario', '3.75');
        $this->fill('#editPorcentajeGanancia', '42');
        $this->click('#btnGuardarPrecio');

        $this->waitUntilGone('#editarPrecioModal.show');
        $this->assertToastContains('Precio actualizado');
        $this->assertRowExists('preciosTable', $lote['planta']['name']);
    }
}
